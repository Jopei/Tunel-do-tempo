<template>
  <div v-if="visivel">
    <Transition name="player-sobe" appear>
    <section
      class="player-barra"
      :class="{ mobile, tocando: player.tocando, carregando: player.carregando }"
      role="region"
      aria-label="Player de música"
    >
      <!-- Mobile mini: progresso fino colado no topo -->
      <div v-if="mobile" class="player-mini-trilho" aria-hidden="true">
        <span class="player-mini-preenche" :style="{ transform: `scaleX(${fracao})` }"></span>
      </div>

      <!-- Esquerda -->
      <div class="player-zona esq">
        <component :is="mobile ? 'button' : 'div'" class="player-faixa" :type="mobile ? 'button' : undefined" :aria-label="mobile ? 'Abrir player' : undefined" @click="mobile && (player.expandido = true)">
          <MusicaCapa class="player-capa" :src="player.atual.capa_url" :tamanho="mobile ? 40 : 48" />
          <Transition name="faixa-troca" mode="out-in">
            <span :key="player.atual.uuid" class="player-texto">
              <strong class="player-titulo">{{ player.atual.titulo }}</strong>
              <small class="player-artista">{{ player.atual.artista || "Artista desconhecido" }}</small>
            </span>
          </Transition>
        </component>
        <router-link v-if="!mobile" :to="{ name: 'musicas' }" class="player-link" aria-label="Ir para a biblioteca de músicas">
          <v-icon size="20">mdi-music-box-multiple-outline</v-icon>
        </router-link>
      </div>

      <!-- Centro -->
      <div class="player-zona centro">
        <div class="player-controles">
          <v-btn
            v-if="!mobile"
            icon
            variant="text"
            class="tm-touch"
            :color="player.aleatorio ? 'primary-text' : undefined"
            :aria-pressed="player.aleatorio"
            :aria-label="player.aleatorio ? 'Aleatório ligado' : 'Aleatório desligado'"
            @click="player.alternarAleatorio()"
          >
            <v-icon>mdi-shuffle-variant</v-icon>
          </v-btn>
          <v-btn v-if="!mobile" icon variant="text" class="tm-touch" aria-label="Faixa anterior" @click="player.anterior()">
            <v-icon>mdi-skip-previous</v-icon>
          </v-btn>
          <v-btn
            icon
            class="player-play"
            color="primary"
            variant="flat"
            :aria-label="player.tocando ? 'Pausar' : 'Tocar'"
            @click="player.alternar()"
          >
            <v-progress-circular v-if="player.carregando && player.tocando" indeterminate size="22" width="3" />
            <v-icon v-else size="28">{{ player.tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
          </v-btn>
          <v-btn icon variant="text" class="tm-touch" aria-label="Próxima faixa" @click="player.proxima()">
            <v-icon>mdi-skip-next</v-icon>
          </v-btn>
          <v-btn
            v-if="!mobile"
            icon
            variant="text"
            class="tm-touch"
            :color="player.repetir !== 'off' ? 'primary-text' : undefined"
            :aria-pressed="player.repetir !== 'off'"
            :aria-label="rotuloRepetir"
            @click="player.ciclarRepetir()"
          >
            <v-icon>{{ player.repetir === "uma" ? "mdi-repeat-once" : "mdi-repeat" }}</v-icon>
          </v-btn>
        </div>

        <div v-if="!mobile" class="player-progresso-linha">
          <span class="player-tempo" aria-hidden="true">{{ formatarDuracao(valorProgresso) }}</span>
          <input
            class="player-progresso"
            type="range"
            min="0"
            :max="Math.max(1, Math.floor(player.duracaoTotal))"
            step="1"
            :value="valorProgresso"
            :style="{ '--p': `${fracao * 100}%` }"
            aria-label="Progresso da faixa"
            :aria-valuetext="`${duracaoPorExtenso(valorProgresso)} de ${duracaoPorExtenso(player.duracaoTotal)}`"
            @input="aoArrastar"
            @change="confirmarBusca"
            @keydown.left.prevent="player.buscar(player.tempo - 5)"
            @keydown.right.prevent="player.buscar(player.tempo + 5)"
          />
          <span class="player-tempo" aria-hidden="true">{{ formatarDuracao(player.duracaoTotal) }}</span>
        </div>
      </div>

      <!-- Direita -->
      <div v-if="!mobile" class="player-zona dir">
        <v-btn
          icon
          variant="text"
          class="tm-touch"
          :color="player.filaAberta ? 'primary-text' : undefined"
          :aria-pressed="player.filaAberta"
          aria-label="Fila de reprodução"
          @click="player.filaAberta = !player.filaAberta"
        >
          <v-icon>mdi-playlist-music</v-icon>
        </v-btn>
        <v-btn icon variant="text" class="tm-touch" :aria-label="player.mudo ? 'Ativar som' : 'Silenciar'" :aria-pressed="player.mudo" @click="player.alternarMudo()">
          <v-icon>{{ iconeVolume }}</v-icon>
        </v-btn>
        <input
          class="player-volume"
          type="range"
          min="0"
          max="1"
          step="0.05"
          :value="player.mudo ? 0 : player.volume"
          :style="{ '--p': `${(player.mudo ? 0 : player.volume) * 100}%` }"
          aria-label="Volume"
          @input="(e) => player.definirVolume(Number(e.target.value))"
        />
        <v-btn icon variant="text" class="tm-touch" aria-label="Fechar player e limpar fila" @click="player.fechar()">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <v-btn v-if="player.pausadoPorOutro" size="small" variant="tonal" class="player-retomar" @click="player.tocar()">
        Pausado pelo vídeo. Retomar
      </v-btn>

      <p class="sr-only" role="status" aria-live="polite">{{ player.anuncio }}</p>
    </section>
    </Transition>

    <Transition name="fila-desliza">
      <PlayerFila v-if="player.filaAberta && !mobile" />
    </Transition>

    <!-- Mobile expandido -->
    <Transition :css="false" @enter="entrarFolha" @leave="sairFolha">
    <div
      v-if="mobile && player.expandido"
      ref="folhaRef"
      class="player-expandida"
      role="dialog"
      aria-modal="true"
      aria-label="Tocando agora"
      :style="folhaEstilo"
    >
      <header
        class="exp-topo"
        @pointerdown="comecarFolha"
        @pointermove="moverFolha"
        @pointerup="fimFolha"
        @pointercancel="fimFolha"
      >
        <v-btn icon variant="text" class="tm-touch" aria-label="Recolher player" @click="player.expandido = false">
          <v-icon size="28">mdi-chevron-down</v-icon>
        </v-btn>
        <div class="exp-abas" role="tablist" aria-label="Visualização">
          <button type="button" role="tab" :aria-selected="!mostrarFila" :class="{ ativa: !mostrarFila }" @click="mostrarFila = false">Tocando agora</button>
          <button type="button" role="tab" :aria-selected="mostrarFila" :class="{ ativa: mostrarFila }" @click="mostrarFila = true">Fila</button>
        </div>
        <span class="exp-espaco"></span>
      </header>

      <PlayerFila v-if="mostrarFila" embutida />
      <div v-else class="exp-corpo">
        <Transition name="capa-troca" mode="out-in">
          <MusicaCapa :key="player.atual.uuid" class="player-capa-grande" :src="player.atual.capa_url" :tamanho="capaGrande" />
        </Transition>
        <div class="exp-info">
          <h2 class="exp-titulo">{{ player.atual.titulo }}</h2>
          <p class="exp-artista">{{ player.atual.artista || "Artista desconhecido" }}</p>
        </div>

        <div class="exp-progresso">
          <input
            class="player-progresso grande"
            type="range"
            min="0"
            :max="Math.max(1, Math.floor(player.duracaoTotal))"
            step="1"
            :value="valorProgresso"
            :style="{ '--p': `${fracao * 100}%` }"
            aria-label="Progresso da faixa"
            :aria-valuetext="`${duracaoPorExtenso(valorProgresso)} de ${duracaoPorExtenso(player.duracaoTotal)}`"
            @input="aoArrastar"
            @change="confirmarBusca"
          />
          <div class="exp-tempos" aria-hidden="true">
            <span>{{ formatarDuracao(valorProgresso) }}</span>
            <span>{{ formatarDuracao(player.duracaoTotal) }}</span>
          </div>
        </div>

        <div class="exp-controles">
          <v-btn icon variant="text" class="tm-touch" :color="player.aleatorio ? 'primary-text' : undefined" :aria-pressed="player.aleatorio" aria-label="Aleatório" @click="player.alternarAleatorio()">
            <v-icon>mdi-shuffle-variant</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="large" aria-label="Faixa anterior" @click="player.anterior()"><v-icon size="32">mdi-skip-previous</v-icon></v-btn>
          <v-btn icon color="primary" variant="flat" class="exp-play" :aria-label="player.tocando ? 'Pausar' : 'Tocar'" @click="player.alternar()">
            <v-icon size="40">{{ player.tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="large" aria-label="Próxima faixa" @click="player.proxima()"><v-icon size="32">mdi-skip-next</v-icon></v-btn>
          <v-btn icon variant="text" class="tm-touch" :color="player.repetir !== 'off' ? 'primary-text' : undefined" :aria-pressed="player.repetir !== 'off'" :aria-label="rotuloRepetir" @click="player.ciclarRepetir()">
            <v-icon>{{ player.repetir === "uma" ? "mdi-repeat-once" : "mdi-repeat" }}</v-icon>
          </v-btn>
        </div>
      </div>
    </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useDisplay } from "vuetify";
import gsap from "gsap";
import { prefereMenosMovimento } from "@/composables/useTema";
import { usePlayerStore } from "@/stores/player";
import { useAuthStore } from "@/stores/auth";
import { formatarDuracao, duracaoPorExtenso } from "@/composables/useFormat";
import MusicaCapa from "./MusicaCapa.vue";
import PlayerFila from "./PlayerFila.vue";

const player = usePlayerStore();
const auth = useAuthStore();
const { mdAndUp, width } = useDisplay();
const mobile = computed(() => !mdAndUp.value);

const visivel = computed(() => auth.logado && player.temFila);
const arrastandoProgresso = ref(false);
const valorLocal = ref(0);
const mostrarFila = ref(false);

const valorProgresso = computed(() => Math.floor(arrastandoProgresso.value ? valorLocal.value : player.tempo));
const fracao = computed(() => {
  const d = player.duracaoTotal;
  return d > 0 ? Math.min(1, Math.max(0, valorProgresso.value / d)) : 0;
});
const iconeVolume = computed(() => (player.mudo || player.volume === 0 ? "mdi-volume-off" : player.volume < 0.5 ? "mdi-volume-medium" : "mdi-volume-high"));
const rotuloRepetir = computed(
  () => ({ off: "Repetir: desligado", todas: "Repetir: todas as faixas", uma: "Repetir: esta faixa" })[player.repetir],
);
const capaGrande = computed(() => Math.min(340, Math.round(width.value * 0.8)));

function aoArrastar(e) {
  arrastandoProgresso.value = true;
  valorLocal.value = Number(e.target.value);
}
function confirmarBusca(e) {
  player.buscar(Number(e.target.value));
  arrastandoProgresso.value = false;
}

/* ---------- folha expandida: arrastar para baixo recolhe ---------- */
const arrasto = ref(0);
const folhaRef = ref(null);
let inicioY = null;
const folhaEstilo = computed(() => (arrasto.value ? { transform: `translateY(${arrasto.value}px)`, transition: "none" } : null));
function comecarFolha(e) {
  if (e.target.closest("button")) return;
  inicioY = e.clientY;
  e.currentTarget.setPointerCapture?.(e.pointerId);
}
function moverFolha(e) {
  if (inicioY == null) return;
  arrasto.value = Math.max(0, e.clientY - inicioY);
}
function fimFolha() {
  if (inicioY == null) return;
  const el = folhaRef.value;
  if (arrasto.value > 120) {
    // Fecha: a saída (sairFolha) parte da posição atual do dedo.
    player.expandido = false;
  } else if (el && arrasto.value) {
    // Solta abaixo do limite: volta suavemente para o topo.
    const de = arrasto.value;
    gsap.fromTo(el, { y: de }, {
      y: 0,
      duration: 0.25,
      ease: "power2.out",
      onComplete: () => {
        arrasto.value = 0;
        gsap.set(el, { clearProps: "transform" });
      },
    });
  } else {
    arrasto.value = 0;
  }
  inicioY = null;
}

/* ---------- entrada/saída da folha com a capa (FLIP da mini capa) ---------- */
const capaMini = () => document.querySelector(".player-barra .player-capa")?.getBoundingClientRect() ?? null;
function entrarFolha(el, done) {
  const capa = el.querySelector(".player-capa-grande");
  if (prefereMenosMovimento()) return done();
  const H = el.offsetHeight || window.innerHeight;
  // Medidas com a folha ainda em repouso (sem transform).
  const mini = capaMini();
  const fim = capa?.getBoundingClientRect();
  if (capa && mini && fim?.width) {
    gsap.fromTo(
      capa,
      { x: mini.left - fim.left, y: mini.top - fim.top - H, scale: mini.width / fim.width, transformOrigin: "0 0" },
      { x: 0, y: 0, scale: 1, duration: 0.3, ease: "power3.out", clearProps: "transform,transformOrigin" },
    );
  }
  gsap.fromTo(el, { y: H }, { y: 0, duration: 0.3, ease: "power3.out", clearProps: "transform", onComplete: done });
}
function sairFolha(el, done) {
  const capa = el.querySelector(".player-capa-grande");
  if (prefereMenosMovimento()) return done();
  const H = el.offsetHeight || window.innerHeight;
  // Posição atual da folha (arrasto em andamento usa translateY inline).
  const m = /translateY\((-?[\d.]+)px\)/.exec(el.style.transform || "");
  const atualY = m ? Number(m[1]) : 0;
  const mini = capaMini();
  const fim = capa?.getBoundingClientRect();
  if (capa && mini && fim?.width) {
    const repousoTopo = fim.top - atualY;
    gsap.fromTo(
      capa,
      { x: 0, y: 0, scale: 1, transformOrigin: "0 0" },
      {
        x: mini.left - fim.left,
        y: mini.top - repousoTopo - H,
        scale: mini.width / fim.width,
        duration: 0.25,
        ease: "power2.in",
        clearProps: "transform,transformOrigin",
      },
    );
  }
  gsap.fromTo(el, { y: atualY }, { y: H, duration: 0.25, ease: "power2.in", onComplete: done });
}

/* ---------- altura reservada para o conteúdo ---------- */
function definirAltura() {
  const h = visivel.value ? (mobile.value ? 64 : 72) : 0;
  document.documentElement.style.setProperty("--tm-player-h", `${h}px`);
}
watch([visivel, mobile], definirAltura, { immediate: true });
watch(
  () => player.expandido,
  (aberto) => {
    mostrarFila.value = false;
    // Reabrir começa da posição de repouso (o arrasto anterior já foi resolvido na saída).
    if (aberto) arrasto.value = 0;
    document.documentElement.style.overflow = aberto ? "hidden" : "";
  },
);
watch(mobile, (m) => {
  if (!m) player.expandido = false;
});

// Sessão encerrada: para o áudio e limpa a fila.
watch(
  () => auth.logado,
  (logado) => {
    if (logado) player.iniciar();
    else player.fechar();
  },
);

function aoTecla(e) {
  if (e.key === "Escape" && player.expandido) player.expandido = false;
  else if (e.key === "Escape" && player.filaAberta) player.filaAberta = false;
}

onMounted(() => {
  if (auth.logado) player.iniciar();
  window.addEventListener("keydown", aoTecla);
});
onBeforeUnmount(() => {
  gsap.killTweensOf(folhaRef.value);
  window.removeEventListener("keydown", aoTecla);
  document.documentElement.style.setProperty("--tm-player-h", "0px");
  document.documentElement.style.overflow = "";
});
</script>

<style scoped>
.player-barra {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1800;
  height: 72px;
  display: grid;
  grid-template-columns: 30% 40% 30%;
  align-items: center;
  gap: 8px;
  padding: 0 16px;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border-top: 1px solid var(--tm-border);
  box-shadow: 0 -6px 20px rgba(42, 33, 18, 0.12);
}
.player-barra.mobile {
  height: 64px;
  grid-template-columns: 1fr auto;
  padding: 0 8px 0 12px;
}
.player-zona {
  display: flex;
  align-items: center;
  min-width: 0;
}
.player-zona.esq {
  gap: 8px;
}
.player-zona.centro {
  flex-direction: column;
  justify-content: center;
  gap: 2px;
}
.player-zona.dir {
  justify-content: flex-end;
  gap: 4px;
}
.player-barra.mobile .player-zona.centro {
  flex-direction: row;
}
.player-faixa {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  flex: 1;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
}
button.player-faixa {
  cursor: pointer;
}
.player-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.3;
}
.player-titulo,
.player-artista {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.player-artista {
  color: var(--tm-ink-muted);
}
.player-link {
  color: var(--tm-ink-muted);
  display: inline-grid;
  place-items: center;
  width: 44px;
  height: 44px;
}
.player-controles {
  display: flex;
  align-items: center;
  gap: 4px;
}
.player-play {
  width: 44px;
  height: 44px;
  color: #2a2112;
}
.player-progresso-linha {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}
.player-tempo {
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
  min-width: 36px;
  text-align: center;
}
.player-mini-trilho {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--tm-border);
}
.player-mini-preenche {
  display: block;
  height: 100%;
  background: var(--tm-gold);
  transform-origin: left;
}
.player-retomar {
  position: absolute;
  top: -40px;
  right: 16px;
}

/* Sliders estilizados */
.player-progresso,
.player-volume {
  -webkit-appearance: none;
  appearance: none;
  height: 24px;
  background: transparent;
  cursor: pointer;
}
.player-progresso {
  flex: 1;
  min-width: 0;
}
.player-volume {
  width: 96px;
}
.player-progresso::-webkit-slider-runnable-track,
.player-volume::-webkit-slider-runnable-track {
  height: 4px;
  border-radius: 2px;
  background: linear-gradient(to right, var(--tm-gold) var(--p, 0%), var(--tm-border) var(--p, 0%));
}
.player-progresso::-moz-range-track,
.player-volume::-moz-range-track {
  height: 4px;
  border-radius: 2px;
  background: var(--tm-border);
}
.player-progresso::-moz-range-progress,
.player-volume::-moz-range-progress {
  height: 4px;
  border-radius: 2px;
  background: var(--tm-gold);
}
.player-progresso::-webkit-slider-thumb,
.player-volume::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 14px;
  height: 14px;
  margin-top: -5px;
  border-radius: 50%;
  border: 0;
  background: var(--tm-gold-text);
}
.player-progresso::-moz-range-thumb,
.player-volume::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 0;
  background: var(--tm-gold-text);
}
.player-progresso:hover::-webkit-slider-runnable-track,
.player-progresso:focus-visible::-webkit-slider-runnable-track {
  height: 6px;
}
.player-progresso.grande {
  width: 100%;
  height: 44px;
}

/* Expandido (mobile) */
.player-expandida {
  position: fixed;
  inset: 0;
  z-index: 2300;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  background: var(--tm-paper-2);
  color: var(--tm-ink);
  /* Movimento (subir/descer) é feito em JS (GSAP) com a capa junto. */
}
/* Barra do player: sobe 250ms ao aparecer. */
.player-sobe-enter-active,
.player-sobe-leave-active {
  transition: transform 250ms var(--tm-ease), opacity 250ms var(--tm-ease);
}
.player-sobe-enter-from,
.player-sobe-leave-to {
  transform: translateY(100%);
  opacity: 0;
}
/* Fila (desktop): desliza da direita. */
.fila-desliza-enter-active,
.fila-desliza-leave-active {
  transition: transform 250ms var(--tm-ease);
}
.fila-desliza-enter-from,
.fila-desliza-leave-to {
  transform: translateX(100%);
}
/* Troca de faixa: texto e capa esmaecem e deslizam curto. */
.faixa-troca-enter-active,
.faixa-troca-leave-active {
  transition: opacity 180ms var(--tm-ease), transform 180ms var(--tm-ease);
}
.faixa-troca-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.faixa-troca-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
.capa-troca-enter-active,
.capa-troca-leave-active {
  transition: opacity 200ms var(--tm-ease);
}
.capa-troca-enter-from,
.capa-troca-leave-to {
  opacity: 0;
}
.exp-topo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px;
  touch-action: none;
}
.exp-espaco {
  width: 48px;
}
.exp-abas {
  display: inline-flex;
  gap: 4px;
}
.exp-abas button {
  min-height: 44px;
  padding: 0 14px;
  border: 0;
  border-radius: var(--tm-radius-pill);
  background: transparent;
  color: var(--tm-ink-muted);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.exp-abas button.ativa {
  color: var(--tm-ink);
  background: var(--tm-paper);
}
.exp-corpo {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-evenly;
  padding: 8px 24px calc(24px + env(safe-area-inset-bottom));
  overflow-y: auto;
}
.player-capa-grande {
  border-radius: var(--tm-radius-lg);
  box-shadow: var(--tm-shadow-lg);
}
.exp-info {
  width: 100%;
  text-align: center;
}
.exp-titulo {
  margin: 0;
  font-size: 22px;
}
.exp-artista {
  margin: 4px 0 0;
  color: var(--tm-ink-muted);
}
.exp-progresso {
  width: 100%;
}
.exp-tempos {
  display: flex;
  justify-content: space-between;
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
}
.exp-controles {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.exp-play {
  width: 72px;
  height: 72px;
  color: #2a2112;
}
@media (prefers-reduced-motion: reduce) {
  .player-sobe-enter-active,
  .player-sobe-leave-active,
  .fila-desliza-enter-active,
  .fila-desliza-leave-active,
  .faixa-troca-enter-active,
  .faixa-troca-leave-active,
  .capa-troca-enter-active,
  .capa-troca-leave-active {
    transition: none;
  }
}
html[data-reduzir="1"] .player-sobe-enter-active,
html[data-reduzir="1"] .player-sobe-leave-active,
html[data-reduzir="1"] .fila-desliza-enter-active,
html[data-reduzir="1"] .fila-desliza-leave-active,
html[data-reduzir="1"] .faixa-troca-enter-active,
html[data-reduzir="1"] .faixa-troca-leave-active,
html[data-reduzir="1"] .capa-troca-enter-active,
html[data-reduzir="1"] .capa-troca-leave-active {
  transition: none;
}
</style>
