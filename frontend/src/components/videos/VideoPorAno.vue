<template>
  <div class="fv-estante" aria-live="polite">
    <section
      v-for="c in capitulos"
      v-show="!c.estado.carregado || c.estado.itens.length"
      :id="c.meta.id"
      :key="c.meta.chave"
      :ref="(el) => registrar(c.meta.chave, el)"
      class="fv-prateleira hx-ano"
      :class="{ 'fv-prateleira--aberta': c.estado.expandida }"
      :data-capitulo="c.meta.chave"
      :style="{ '--hx-ano-h': c.meta.hue }"
      :aria-labelledby="`${c.meta.id}-h`"
    >
      <header class="fv-prat-cab">
        <span class="hx-selo fv-prat-selo" aria-hidden="true">{{ c.meta.selo }}</span>
        <div class="fv-prat-titulos">
          <h2 :id="`${c.meta.id}-h`" class="fv-prat-titulo">{{ c.meta.rotulo }}</h2>
          <p class="fv-prat-sub">
            <time v-if="c.meta.ano" :datetime="String(c.meta.ano)">{{ haAnos(c.meta.ano) }}</time>
            <template v-else>{{ c.meta.sub }}</template>
            · {{ pluralVideos(c.total) }}
            <span v-if="c.meta.ano" class="sr-only">, ano de {{ c.meta.ano }}</span>
          </p>
        </div>
        <v-btn
          v-if="c.total > LIMITE_TIRA"
          variant="text"
          class="fv-prat-ver"
          :aria-expanded="c.estado.expandida"
          :aria-controls="`${c.meta.id}-corpo`"
          @click="alternar(c)"
        >
          {{ c.estado.expandida ? "Recolher" : "Ver todos" }}
        </v-btn>
      </header>

      <div :id="`${c.meta.id}-corpo`" class="fv-prat-corpo">
        <div v-if="!c.estado.carregado" class="fv-prat-esq" aria-hidden="true">
          <span v-for="n in 4" :key="n" class="fv-prat-esq-item hx-skeleton"></span>
        </div>
        <p v-else-if="c.estado.erro" class="fv-prat-erro" role="alert">
          Não deu para carregar.
          <v-btn size="small" variant="tonal" @click="carregar(c.meta.chave, true)">Tentar de novo</v-btn>
        </p>
        <template v-else>
            <TmCarrossel
              :rotulo-anterior="`Vídeos anteriores de ${c.meta.rotulo}`"
              :rotulo-proximo="`Próximos vídeos de ${c.meta.rotulo}`"
              class="fv-prat-tira"
              :class="{ 'fv-prat-grade': c.estado.expandida }"
              role="region"
              tabindex="0"
              :aria-label="`Vídeos de ${c.meta.rotulo}`"
            >
              <div v-for="(v, i) in visiveis(c)" :key="v.uuid" class="fv-prat-item" :style="{ '--i': i }">
                <VideoCard
                  :video="v"
                  :selecionado="selecionados.has(v.uuid)"
                  :modo-selecao="selecaoAtiva"
                  :pode-arrastar="podeArrastar"
                  :style="{ '--i': i % 12 }"
                  @abrir="(video, e) => $emit('abrir', video, e, c.estado.itens)"
                  @check="(video, e) => $emit('check', video, e)"
                  @menu="(video, pos, el) => $emit('menu', video, pos, el)"
                  @arrastar="(e, video) => $emit('arrastar', e, video)"
                />
              </div>
              <button
                v-if="!c.estado.expandida && c.total > LIMITE_TIRA"
                type="button"
                class="fv-prat-mais"
                :aria-label="`Mostrar mais ${c.total - LIMITE_TIRA} vídeos de ${c.meta.rotulo}`"
                @click="alternar(c)"
              >
                +{{ c.total - LIMITE_TIRA }}
              </button>
            </TmCarrossel>
        </template>
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, computed, watch, onBeforeUnmount, nextTick } from "vue";
import { listarVideos } from "@/services/video.service";
import { chaveDoAno, metaCapitulo, ordemCapitulos, haAnos, pluralVideos } from "@/composables/useEraFotosVideos";
import { prefereMenosMovimento } from "@/composables/useTema";
import TmCarrossel from "@/components/ui/TmCarrossel.vue";
import VideoCard from "./VideoCard.vue";

const LIMITE_TIRA = 8;
const LIMITE_EXPANDIDA = 48;

const props = defineProps({
  /** [{ ano: 2015|null, total }] de GET /videos/anos */
  anos: { type: Array, default: () => [] },
  ordem: { type: String, default: "recentes" }, // recentes | antigas
  q: { type: String, default: "" },
  pastaUuid: { type: String, default: null },
  selecionados: { type: Set, default: () => new Set() },
  selecaoAtiva: Boolean,
  podeArrastar: Boolean,
});
const emit = defineEmits(["abrir", "check", "menu", "arrastar", "itens"]);

const estados = reactive({}); // chave -> { itens, carregado, erro, expandida, geracao }
const elementos = new Map();
let observer = null;
let geracaoGlobal = 0;

const grupos = computed(() => {
  const mapa = new Map();
  for (const a of props.anos) {
    const k = chaveDoAno(a.ano);
    const g = mapa.get(k) ?? { anos: [], total: 0 };
    if (a.ano != null) g.anos.push(a.ano);
    g.total += Number(a.total) || 0;
    mapa.set(k, g);
  }
  return mapa;
});

function estadoDe(chave) {
  if (!estados[chave]) estados[chave] = { itens: [], carregado: false, erro: false, expandida: false };
  return estados[chave];
}

const capitulos = computed(() =>
  ordemCapitulos(props.ordem === "antigas" ? "antigas" : "recentes")
    .filter((k) => grupos.value.has(k))
    .map((k) => ({ meta: metaCapitulo(k), total: grupos.value.get(k).total, estado: estadoDe(k) })),
);

const visiveis = (c) => (c.estado.expandida ? c.estado.itens : c.estado.itens.slice(0, LIMITE_TIRA));

function paramsBase() {
  const p = { ordem: props.ordem === "antigas" ? "data_video" : "data_video_desc" };
  if (props.pastaUuid) p.pasta_uuid = props.pastaUuid;
  else p.todas = 1;
  if (props.q) p.q = props.q;
  return p;
}

async function carregar(chave, forcar = false) {
  const st = estadoDe(chave);
  if (st.carregando || (st.carregado && !forcar)) return;
  const grupo = grupos.value.get(chave);
  if (!grupo) return;
  st.carregando = true;
  st.erro = false;
  const minha = geracaoGlobal;
  const por = st.expandida ? LIMITE_EXPANDIDA : LIMITE_TIRA + 1;
  try {
    const base = { ...paramsBase(), per_page: por };
    const respostas = chave === "sem"
      ? [await listarVideos(1, { ...base, sem_data: 1 })]
      : await Promise.all(grupo.anos.map((ano) => listarVideos(1, { ...base, ano })));
    if (minha !== geracaoGlobal) return;
    const todos = respostas.flatMap((r) => r.data ?? []);
    const sinal = props.ordem === "antigas" ? 1 : -1;
    todos.sort((a, b) => sinal * String(a.data_video ?? "").localeCompare(String(b.data_video ?? "")));
    st.itens = todos.slice(0, por);
    st.carregado = true;
  } catch {
    if (minha !== geracaoGlobal) return;
    st.erro = true;
    st.carregado = true;
  } finally {
    st.carregando = false;
  }
}

function alternar(c) {
  c.estado.expandida = !c.estado.expandida;
  c.estado.carregado = false;
  carregar(c.meta.chave, true);
}

/* ---------- montagem preguiçosa por IntersectionObserver ---------- */
function registrar(chave, el) {
  if (!el) {
    const antigo = elementos.get(chave);
    if (antigo) observer?.unobserve(antigo);
    elementos.delete(chave);
    return;
  }
  if (elementos.get(chave) === el) return;
  elementos.set(chave, el);
  if (!("IntersectionObserver" in window)) return void carregar(chave);
  observer ??= new IntersectionObserver(
    (entradas) => {
      for (const e of entradas) {
        if (e.isIntersecting) {
          observer.unobserve(e.target);
          carregar(e.target.dataset.capitulo);
        }
      }
    },
    { rootMargin: "300px 0px" },
  );
  observer.observe(el);
}

/** Zera e recarrega as prateleiras (filtros mudaram ou itens foram movidos/excluídos). */
async function recarregar() {
  geracaoGlobal++;
  for (const k of Object.keys(estados)) {
    const ex = estados[k].expandida;
    estados[k] = { itens: estados[k].itens, carregado: false, erro: false, expandida: ex };
  }
  await nextTick();
  for (const [chave, el] of elementos) {
    if (!grupos.value.has(chave)) continue;
    const r = el.getBoundingClientRect();
    if (r.top < window.innerHeight + 300 && r.bottom > -300) carregar(chave, true);
    else {
      observer?.unobserve(el);
      observer?.observe(el);
    }
  }
}

watch(() => [props.q, props.ordem, props.pastaUuid], recarregar);
watch(() => props.anos, recarregar);

// Lista plana (na ordem das prateleiras) para seleção e atalhos da tela.
const planos = computed(() => capitulos.value.flatMap((c) => c.estado.itens));
watch(planos, (v) => emit("itens", v), { immediate: true });

/** Rola até a prateleira do ano (chip "Mais de 2014" do player). */
function irParaCapitulo(chave) {
  const el = elementos.get(chave);
  el?.scrollIntoView({ behavior: prefereMenosMovimento() ? "auto" : "smooth", block: "start" });
}

onBeforeUnmount(() => {
  observer?.disconnect();
  geracaoGlobal++;
});
const todasVazias = computed(() => capitulos.value.length > 0 && capitulos.value.every((c) => c.estado.carregado && !c.estado.erro && !c.estado.itens.length));
defineExpose({ recarregar, irParaCapitulo, todasVazias });
</script>

<style scoped>
.fv-estante {
  display: flex;
  flex-direction: column;
  gap: 28px;
}
.fv-prateleira {
  scroll-margin-top: calc(var(--tm-navbar-h) + 64px);
}
.fv-prat-cab {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 56px;
  padding: 0 4px 8px 14px;
}
.fv-prat-cab::before {
  content: "";
  position: absolute;
  left: 0;
  top: 4px;
  bottom: 12px;
  width: 3px;
  border-radius: 2px;
  background: var(--hx-ano-cor);
}
.fv-prat-selo {
  flex: none;
  min-width: 48px;
  height: 48px;
  font-size: 0.95rem;
}
.fv-prat-titulos {
  flex: 1;
  min-width: 0;
}
.fv-prat-titulo {
  margin: 0;
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: var(--tm-fs-2xl);
  line-height: 1.1;
  font-variant-numeric: tabular-nums;
}
.fv-prat-sub {
  margin: 2px 0 0;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.fv-prat-ver {
  flex: none;
  min-height: 44px;
  color: var(--tm-gold-text);
}
.fv-prat-esq {
  display: flex;
  gap: 12px;
  overflow: hidden;
}
.fv-prat-esq-item {
  flex: none;
  width: 240px;
  aspect-ratio: 16 / 11;
  border-radius: 8px;
}
.fv-prat-erro {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  padding: 12px 4px;
}
:deep(.fv-prat-tira) {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  scroll-snap-type: x proximity;
  padding: 4px 4px 12px;
}
:deep(.fv-prat-tira:focus-visible) {
  outline: 3px solid var(--tm-focus);
  outline-offset: 2px;
}
.fv-prat-item {
  flex: none;
  width: 240px;
  scroll-snap-align: start;
}
:deep(.fv-prat-grade) {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  overflow-x: visible;
}
:deep(.fv-prat-grade) .fv-prat-item {
  width: auto;
}
.fv-prat-mais {
  flex: none;
  align-self: stretch;
  width: 120px;
  scroll-snap-align: start;
  border: 2px dashed var(--tm-border);
  border-radius: 8px;
  background: transparent;
  color: var(--tm-gold-text);
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: 1.5rem;
  cursor: pointer;
}
.fv-prat-mais:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: 2px;
}
@media (max-width: 599px) {
  .fv-prat-item,
  .fv-prat-esq-item {
    width: 180px;
  }
  .fv-prat-cab {
    min-height: 48px;
  }
  .fv-prat-selo {
    min-width: 40px;
    height: 40px;
    font-size: 0.8rem;
  }
  :deep(.fv-prat-grade) {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
