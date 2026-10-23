<template>
  <div class="orbita-wrap">
    <div v-if="carregando" class="orbita orbita--fantasma" aria-busy="true" aria-label="Carregando a rua">
      <span class="fantasma fantasma--central" />
      <span v-for="n in 6" :key="n" class="fantasma fantasma--sat" :style="{ '--n': n }" />
    </div>

    <TmErrorState v-else-if="erro" texto="Não conseguimos carregar os membros da rua." @retry="carregar" />
    <TmEmptyState v-else-if="!central" icone="mdi-account-group-outline" titulo="Ainda não há membros na rua." />

    <template v-else>
      <p class="sr-only" aria-live="polite">
        {{ membros.length }} membros na rua. {{ central.nome }} está em destaque.
        <template v-if="selecionado">Selecionado: {{ selecionado.nome }}.</template>
      </p>

      <!-- Ganchos do motion: .orbita, .orbita-anel-guia, .orbita-item[data-anel], .esfera, .rotulo -->
      <div
        ref="palcoRef"
        class="orbita"
        :class="{ pausada: pausada || parada, parada }"
        :style="{ '--s': `${tamanhoSat}px`, '--c': `${tamanhoCentral}px` }"
        @keydown.esc="limpar"
        @mouseenter="emHover = true"
        @mouseleave="emHover = false"
        @focusin="emFoco = true"
        @focusout="emFoco = false"
      >
        <span
          v-for="(r, i) in raios"
          :key="`guia-${i}`"
          class="orbita-anel-guia"
          aria-hidden="true"
          :style="{ width: `${r * 2}px`, height: `${r * 2}px` }"
        />

        <ul class="orbita-lista" @keydown="aoTeclar">
          <li class="orbita-item orbita-central" data-anel="0" :style="{ '--i': 0 }">
            <!-- Troca do central: o novo sai do satélite em voo (ver voarTroca); sem crossfade. -->
            <button
              :key="central.uuid"
              :ref="(el) => (botoes[0] = el)"
              type="button"
              class="esfera esfera--central"
              :tabindex="foco === 0 ? 0 : -1"
              :style="{ width: 'var(--c)', height: 'var(--c)' }"
              :aria-label="`${central.nome}, membro em destaque hoje. Abrir página`"
              @click="abrir(central, $event)"
              @focus="foco = 0"
            >
              <Conteudo :m="central" :simbolos="simbolos" :falhas="falhas" @falha="marcarFalha" />
            </button>
            <span class="rotulo-central" aria-hidden="true">
              <small>Hoje na rua</small>
              <strong>{{ central.nome }}</strong>
            </span>
          </li>

          <li
            v-for="(s, k) in satelites"
            :key="`slot-${k}`"
            class="orbita-item orbita-sat"
            :class="{ ativo: selecionado?.uuid === s.m.uuid }"
            :data-anel="s.anel + 1"
            :data-uuid="s.m.uuid"
            :style="{
              '--dur': `${duracoes[s.anel]}s`,
              '--dir': SENTIDO[s.anel],
              '--dir-contra': SENTIDO[s.anel] === 'normal' ? 'reverse' : 'normal',
              '--i': k + 1,
            }"
          >
            <div
              class="sat-pos"
              :style="{ '--ang': `${s.ang}deg`, '--r': `${raios[s.anel]}px` }"
            >
              <div class="sat-giro">
                <button
                  :ref="(el) => (botoes[k + 1] = el)"
                  type="button"
                  class="esfera"
                  :tabindex="foco === k + 1 ? 0 : -1"
                  :aria-label="`${s.m.nome}, membro da rua. Abrir página`"
                  @click="aoClicarSat(s.m, $event)"
                  @focus="foco = k + 1"
                >
                  <Conteudo :m="s.m" :simbolos="simbolos" :falhas="falhas" @falha="marcarFalha" />
                </button>
                <span class="rotulo" aria-hidden="true">{{ s.m.nome }}</span>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Mobile/toque: cartão fixo sob o palco (1o toque seleciona, 2o abre) -->
      <div v-if="toque" class="rotulo-toque" aria-live="polite">
        <template v-if="selecionado">
          <span class="rotulo-toque-nome">{{ selecionado.nome }}</span>
          <v-btn color="primary" variant="flat" min-height="44" @click="abrir(selecionado)">Abrir página</v-btn>
        </template>
        <span v-else class="rotulo-toque-dica">Toque em uma esfera para conhecer</span>
      </div>

      <v-btn to="/membros" variant="text" color="primary-text" class="ver-lista">Ver todos os membros em lista</v-btn>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, h } from "vue";
import { useRouter } from "vue-router";
import { transicionarPara } from "@/composables/useTransicao";
import { voarFantasma } from "@/composables/useVoo";
import { prefereMenosMovimento } from "@/composables/useTema";
import { iniciais } from "@/composables/useFormat";
import { listarMembrosPublicos, chaveMembro } from "@/services/membro.service";
import { registry } from "@/membros/registry";
import MembroSimbolo from "@/membros/MembroSimbolo.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const CHAVE_CENTRAL = "tm-rua-central";
const ANEIS = 3;
const MAX_SAT = 12;
const duracoes = [90, 150, 120]; // segundos por volta (anel 1..3)
// Proporção de satélites por anel (interno → externo) e defasagem inicial de cada anel.
const PESOS = [3, 4, 5];
const DEFASAGEM = [0, 45, 17];
// Anéis 2 e 3 giram em sentidos opostos ao padrão alternado antigo (que desenhava braços em cruz).
const SENTIDO = ["normal", "normal", "reverse"];

const router = useRouter();
const palcoRef = ref(null);
const botoes = [];
const membros = ref([]);
const carregando = ref(true);
const erro = ref(false);
const lado = ref(600);
const pausada = ref(false);
const parada = ref(false);
const selecionado = ref(null);
const foco = ref(0);
const falhas = ref({});
const toque = ref(false);
const observer = new ResizeObserver(([entry]) => {
  lado.value = entry.contentRect.width || lado.value;
});
// O palco só existe depois dos dados (e some/volta com erro + "tentar de novo").
watch(
  palcoRef,
  (el, antigo) => {
    if (antigo) observer.unobserve(antigo);
    if (el) observer.observe(el);
  },
  { flush: "post" },
);

const simbolos = Object.fromEntries(Object.entries(registry).map(([slug, e]) => [slug, e.simbolo]));
// Imagem própria da esfera (registry.esfera) tem prioridade sobre a foto de perfil.
const esferas = Object.fromEntries(Object.entries(registry).map(([slug, e]) => [slug, e.esfera]));

// Esfera: foto (ou iniciais) + medalhão com o símbolo do registry.
const Conteudo = (props, { emit }) => {
  const m = props.m;
  const simbolo = props.simbolos[m.slug];
  const foto = esferas[m.slug] || m.foto_url;
  const nodes = [];
  if (foto && !props.falhas[m.uuid]) {
    nodes.push(
      h("img", {
        class: "esfera-img",
        src: foto,
        alt: "",
        loading: "lazy",
        draggable: false,
        onError: () => emit("falha", m.uuid),
      }),
    );
  } else {
    nodes.push(h("span", { class: "esfera-iniciais", "aria-hidden": "true" }, iniciais(m.nome) || "?"));
  }
  if (simbolo) {
    nodes.push(h("span", { class: "medalhao", "aria-hidden": "true" }, [h(MembroSimbolo, { simbolo, nome: m.nome })]));
  }
  return nodes;
};
Conteudo.props = ["m", "simbolos", "falhas"];
Conteudo.emits = ["falha"];

function marcarFalha(uuid) {
  falhas.value = { ...falhas.value, [uuid]: true };
}

const centralUuid = ref(null);
const central = computed(() => membros.value.find((x) => x.uuid === centralUuid.value) ?? null);

// Sorteia o central a cada visita, evitando repetir o último exibido (guardado na sessão).
function resolverCentral() {
  if (!membros.value.length) return;
  let ultimo = null;
  try { ultimo = sessionStorage.getItem(CHAVE_CENTRAL); } catch { /* storage indisponível */ }
  const candidatos = membros.value.length > 1 ? membros.value.filter((x) => x.uuid !== ultimo) : membros.value;
  definirCentral(candidatos[Math.floor(Math.random() * candidatos.length)], { reiniciar: true });
}

// Slots fixos: cada satélite ocupa um índice (anel/ângulo) que não muda quando o central troca;
// quem sai do centro assume o slot de quem entrou.
const slots = ref([]);
function montarSlots(centralId) {
  slots.value = membros.value
    .filter((x) => x.uuid !== centralId)
    .sort((a, b) => a.nome.localeCompare(b.nome, "pt-BR"))
    .slice(0, MAX_SAT)
    .map((x) => x.uuid);
}

function definirCentral(m, { reiniciar = false } = {}) {
  const anterior = centralUuid.value;
  const idx = slots.value.indexOf(m.uuid);
  if (reiniciar || idx < 0 || !anterior) montarSlots(m.uuid);
  else slots.value = slots.value.map((u, i) => (i === idx ? anterior : u));
  centralUuid.value = m.uuid;
  try { sessionStorage.setItem(CHAVE_CENTRAL, m.uuid); } catch { /* storage indisponível */ }
}

// Ciclo: troca o central por outro membro aleatório (voo do satélite para o centro).
const INTERVALO_TROCA = 7000;
const voos = new Set();
let timerTroca = null;
const emHover = ref(false);
const emFoco = ref(false);
const trocaPausada = computed(
  () => pausada.value || emHover.value || emFoco.value || !!selecionado.value || parada.value,
);

function trocarCentral() {
  const visiveis = slots.value.map((u) => membros.value.find((m) => m.uuid === u)).filter(Boolean);
  if (!visiveis.length) return;
  voarTroca(visiveis[Math.floor(Math.random() * visiveis.length)]);
}

/**
 * Troca do central com voo: o novo central sai da posição do seu satélite e vai ao centro;
 * o antigo sai do centro e voa para o anel (onde o satélite novo aparece). Só transform/opacity;
 * os elementos reais ficam ocultos durante o voo (um fantasma fixo faz a viagem).
 */
function voarTroca(novo) {
  const palco = palcoRef.value;
  const anterior = centralUuid.value;
  const satEl = palco?.querySelector(`.orbita-sat[data-uuid="${CSS.escape(novo.uuid)}"] .esfera`);
  const centralAntEl = palco?.querySelector(".orbita-central .esfera");
  const deNovo = satEl?.getBoundingClientRect();
  const deAnterior = centralAntEl?.getBoundingClientRect();
  definirCentral(novo);
  if (!palco || !deNovo?.width || !deAnterior?.width || prefereMenosMovimento()) return;

  nextTick(() => {
    const centralEl = palco.querySelector(".orbita-central .esfera");
    const anelEl = anterior ? palco.querySelector(`.orbita-sat[data-uuid="${CSS.escape(anterior)}"] .esfera`) : null;
    if (!centralEl) return;
    // A CSS de entrada das esferas (orbita-entra) sobrescreveria a opacidade; desliga só nos que voam.
    centralEl.style.animation = "none";
    centralEl.style.opacity = "0";
    if (anelEl) {
      anelEl.style.animation = "none";
      anelEl.style.opacity = "0";
    }
    let ida;
    ida = voarFantasma(satEl, {
      de: deNovo,
      para: () => centralEl.getBoundingClientRect(),
      duracao: 0.7,
      ease: "power2.inOut",
      onFim: () => {
        centralEl.style.opacity = "";
        voos.delete(ida);
      },
    });
    if (ida.fantasma) voos.add(ida);

    if (anelEl) {
      let volta;
      volta = voarFantasma(centralAntEl, {
        de: deAnterior,
        para: () => anelEl.getBoundingClientRect(),
        duracao: 0.7,
        ease: "power2.inOut",
        onFim: () => {
          anelEl.style.opacity = "";
          voos.delete(volta);
        },
      });
      if (volta.fantasma) voos.add(volta);
    }
  });
}

function iniciarCiclo() {
  pararCiclo();
  if (parada.value) return;
  timerTroca = setInterval(() => {
    if (!trocaPausada.value) trocarCentral();
  }, INTERVALO_TROCA);
}
function pararCiclo() {
  clearInterval(timerTroca);
  timerTroca = null;
}

const tamanhoCentral = computed(() => Math.max(150, Math.min(240, lado.value * 0.34)));
const tamanhoSat = computed(() => Math.max(52, Math.min(76, lado.value * 0.12)));

// Raios dos 3 anéis: do rente ao central até a borda do palco.
const raios = computed(() => {
  const s = tamanhoSat.value;
  const interno = tamanhoCentral.value / 2 + s / 2 + 14;
  const externo = Math.max(interno + 2 * s, lado.value / 2 - s / 2 - 6);
  const passo = (externo - interno) / (ANEIS - 1);
  return Array.from({ length: ANEIS }, (_, i) => interno + passo * i);
});

// Até 12 satélites repartidos em 3 anéis na proporção 3/4/5, com defasagens diferentes por anel,
// para que os anéis nunca se alinhem em braços simétricos; a posição vem do índice do slot.
function quantidadesPorAnel(total) {
  const soma = PESOS.reduce((a, b) => a + b, 0);
  const qtds = PESOS.map((p) => Math.floor((total * p) / soma));
  for (let anel = ANEIS - 1; qtds.reduce((a, b) => a + b, 0) < total; anel = (anel + ANEIS - 1) % ANEIS) qtds[anel]++;
  return qtds;
}
const satelites = computed(() => {
  const lista = slots.value.map((u) => membros.value.find((m) => m.uuid === u)).filter(Boolean);
  const qtds = quantidadesPorAnel(lista.length);
  const out = [];
  let idx = 0;
  for (let anel = 0; anel < ANEIS; anel++) {
    for (let j = 0; j < qtds[anel]; j++) {
      out.push({ m: lista[idx++], anel, ang: (360 / qtds[anel]) * j + DEFASAGEM[anel] });
    }
  }
  return out;
});

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    membros.value = await listarMembrosPublicos();
    resolverCentral();
  } catch {
    erro.value = true;
  } finally {
    carregando.value = false;
  }
}

function abrir(m, event) {
  const img = (event?.currentTarget ?? botoes[foco.value])?.querySelector?.("img.esfera-img") ?? null;
  transicionarPara(router, `/membros/${chaveMembro(m)}`, img);
}

// Toque: 1o toque seleciona (rótulo + pausa), 2o abre.
function aoClicarSat(m, event) {
  if (toque.value && selecionado.value?.uuid !== m.uuid) {
    selecionado.value = m;
    return;
  }
  abrir(m, event);
}

function limpar() {
  selecionado.value = null;
  document.activeElement?.blur?.();
}

// Roving tabindex: setas/Home/End percorrem a lista na ordem do DOM.
function aoTeclar(e) {
  const total = satelites.value.length + 1;
  let alvo = null;
  if (e.key === "ArrowRight" || e.key === "ArrowDown") alvo = (foco.value + 1) % total;
  else if (e.key === "ArrowLeft" || e.key === "ArrowUp") alvo = (foco.value - 1 + total) % total;
  else if (e.key === "Home") alvo = 0;
  else if (e.key === "End") alvo = total - 1;
  if (alvo === null) return;
  e.preventDefault();
  foco.value = alvo;
  botoes[alvo]?.focus();
}

function aoMudarVisibilidade() {
  pausada.value = document.visibilityState === "hidden";
}
function aoClicarFora(e) {
  if (toque.value && palcoRef.value && !palcoRef.value.contains(e.target) && !e.target.closest(".rotulo-toque")) {
    selecionado.value = null;
  }
}

onMounted(() => {
  toque.value = window.matchMedia("(pointer: coarse)").matches;
  parada.value = prefereMenosMovimento();
  document.addEventListener("visibilitychange", aoMudarVisibilidade);
  document.addEventListener("click", aoClicarFora);
  iniciarCiclo();
  carregar();
});
onBeforeUnmount(() => {
  voos.forEach((v) => {
    v.tween?.kill();
    v.fantasma?.remove();
  });
  voos.clear();
  document.removeEventListener("visibilitychange", aoMudarVisibilidade);
  document.removeEventListener("click", aoClicarFora);
  pararCiclo();
  observer.disconnect();
});
</script>

<style scoped>
.orbita-wrap {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-3);
}

.orbita {
  position: relative;
  width: min(100%, 640px);
  aspect-ratio: 1;
}

.orbita-anel-guia {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  border: 1px dashed var(--tm-border);
  border-radius: 50%;
  pointer-events: none;
  animation: orbita-anel-surge 500ms var(--tm-ease) backwards;
}

.orbita-lista {
  list-style: none;
  margin: 0;
  padding: 0;
}
.orbita-item {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

/* Cada item gira em torno do centro do palco; o conteúdo contra-gira para a foto ficar de pé. */
.orbita-sat {
  animation: orbita-giro var(--dur) linear infinite;
  animation-direction: var(--dir);
}
.sat-pos {
  position: absolute;
  top: 50%;
  left: 50%;
  width: var(--s);
  height: var(--s);
  margin: calc(var(--s) / -2) 0 0 calc(var(--s) / -2);
  transform: rotate(var(--ang)) translateY(calc(var(--r) * -1)) rotate(calc(var(--ang) * -1));
  pointer-events: auto;
}
.sat-giro {
  width: 100%;
  height: 100%;
  position: relative;
  animation: orbita-giro var(--dur) linear infinite;
  animation-direction: var(--dir-contra);
}
.pausada .orbita-sat,
.pausada .sat-giro,
.orbita:has(.esfera:hover) .orbita-sat,
.orbita:has(.esfera:hover) .sat-giro,
.orbita:has(.esfera:focus-visible) .orbita-sat,
.orbita:has(.esfera:focus-visible) .sat-giro,
.orbita-sat.ativo,
.orbita-sat.ativo .sat-giro {
  animation-play-state: paused;
}
.parada .orbita-sat,
.parada .sat-giro {
  animation: none;
}

.orbita-central {
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}
.orbita-sat {
  z-index: 5;
}
.orbita-sat:hover,
.orbita-sat:focus-within,
.orbita-sat.ativo {
  z-index: 30;
}

.esfera {
  pointer-events: auto;
  position: relative;
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 2px solid var(--tm-gold);
  border-radius: 50%;
  background: var(--tm-paper-2);
  box-shadow: 0 0 0 4px rgba(199, 164, 58, 0.25), var(--tm-shadow-md);
  cursor: pointer;
  overflow: visible;
  transition: transform var(--tm-dur-base) var(--tm-ease);
  /* Entrada: surge dos anéis para fora, em stagger pelo --i (backwards: não prende o hover). */
  animation: orbita-entra 450ms var(--tm-ease) backwards;
  animation-delay: calc(var(--i, 0) * 60ms);
}
/* Anel ouro de destaque: anima opacity (não box-shadow). */
.esfera::before {
  content: "";
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  box-shadow: 0 0 0 2px rgba(199, 164, 58, 0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.esfera:hover,
.esfera:focus-visible,
.ativo .esfera {
  transform: scale(1.12);
}
.esfera:hover::before,
.esfera:focus-visible::before,
.ativo .esfera::before {
  opacity: 1;
}
.esfera:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 3px;
}
.esfera--central:hover,
.esfera--central:focus-visible {
  transform: scale(1.04);
}
.esfera--central {
  animation-delay: 0ms;
}
.esfera :deep(.esfera-img) {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: block;
}
.esfera :deep(.esfera-iniciais) {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--tm-gold);
  color: #2a2112;
  font-family: var(--tm-font-title);
  font-size: 1.25rem;
  font-weight: 700;
}
.esfera--central :deep(.esfera-iniciais) {
  font-size: 4rem;
}
.esfera :deep(.medalhao) {
  position: absolute;
  right: -2px;
  bottom: -2px;
  width: 28%;
  height: 28%;
  border-radius: 50%;
  background: var(--tm-paper);
  border: 2px solid var(--tm-gold);
  display: grid;
  place-items: center;
  overflow: hidden;
  font-size: 0.6rem;
}

/* Hit area mínima de 48px mesmo se a esfera encolher */
.esfera::after {
  content: "";
  position: absolute;
  inset: -4px;
  border-radius: 50%;
}

.rotulo {
  position: absolute;
  bottom: calc(100% + 10px);
  left: 50%;
  transform: translate(-50%, 4px);
  white-space: nowrap;
  padding: 8px 12px;
  border-radius: var(--tm-radius-sm);
  background: var(--tm-paper);
  color: var(--tm-ink);
  box-shadow: var(--tm-shadow-md);
  font-weight: 600;
  font-size: var(--tm-fs-sm);
  opacity: 0;
  pointer-events: none;
  transition: opacity 160ms var(--tm-ease), transform 160ms var(--tm-ease);
}
.sat-giro:hover .rotulo,
.sat-giro:focus-within .rotulo,
.ativo .rotulo {
  opacity: 1;
  transform: translate(-50%, 0);
}

.rotulo-central {
  position: absolute;
  left: 50%;
  top: calc(50% + var(--c) / 2 - 12px);
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 6px 14px;
  border-radius: var(--tm-radius-sm);
  background: var(--tm-paper);
  color: var(--tm-ink);
  box-shadow: var(--tm-shadow-md);
  pointer-events: none;
  white-space: nowrap;
  z-index: 11;
}
.rotulo-central small {
  font-size: 12px;
  color: var(--tm-ink-muted);
}

.rotulo-toque {
  min-height: 72px;
  width: min(92vw, 360px);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--tm-space-3);
  padding: 8px 12px;
  border-radius: var(--tm-radius-md);
  background: var(--tm-paper);
  color: var(--tm-ink);
  box-shadow: var(--tm-shadow-md);
}
.rotulo-toque-nome {
  font-weight: 600;
}
.rotulo-toque-dica {
  color: var(--tm-ink-muted);
}

/* Estado de carregamento */
.orbita--fantasma {
  display: grid;
  place-items: center;
}
.fantasma {
  position: absolute;
  border-radius: 50%;
  background: var(--tm-paper-2);
  animation: orbita-pulso 1.6s ease-in-out infinite;
}
.fantasma--central {
  width: 34%;
  height: 34%;
}
.fantasma--sat {
  width: 12%;
  height: 12%;
  top: 50%;
  left: 50%;
  transform: rotate(calc(var(--n) * 60deg)) translateY(-210%);
}

@keyframes orbita-giro {
  to {
    transform: rotate(360deg);
  }
}
@keyframes orbita-entra {
  from {
    transform: scale(0.6);
    opacity: 0;
  }
}
@keyframes orbita-anel-surge {
  from {
    opacity: 0;
  }
}
@keyframes orbita-pulso {
  50% {
    opacity: 0.5;
  }
}

@media (max-width: 599px) {
  .orbita {
    width: min(100%, 100vw);
  }
}
@media (orientation: landscape) and (max-height: 500px) {
  .orbita {
    width: min(86dvh, 60vw);
  }
}
@media (prefers-reduced-motion: reduce) {
  .orbita-sat,
  .sat-giro,
  .fantasma,
  .esfera,
  .orbita-anel-guia {
    animation: none;
  }
}
html[data-reduzir="1"] .orbita-sat,
html[data-reduzir="1"] .sat-giro,
html[data-reduzir="1"] .esfera,
html[data-reduzir="1"] .orbita-anel-guia {
  animation: none;
}
</style>
