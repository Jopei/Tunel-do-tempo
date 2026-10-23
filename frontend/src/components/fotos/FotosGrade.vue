<template>
  <div ref="raiz" class="fotos-grade" role="grid" :aria-label="rotuloGrade" :aria-busy="ocupado" @keydown="aoTecla">
    <section
      v-for="g in blocos"
      :key="g.chave"
      class="foto-grupo"
      :class="{ 'sem-cabecalho': semCabecalho, novo: gruposNovos.has(g.chave) }"
      :data-grupo="g.chave"
      :data-mes="g.mes"
    >
      <header
        v-if="g.capitulo"
        :id="g.capitulo.id"
        class="fv-cap hx-ano"
        :class="{ 'fv-cap--extra': !g.capitulo.ano, 'is-visivel': revelados.has(g.capitulo.chave) }"
        :data-capitulo="g.capitulo.chave"
        :style="{ '--hx-ano-h': g.capitulo.hue }"
      >
        <span class="fv-cap__filete" aria-hidden="true"></span>
        <span class="hx-selo fv-cap__selo" aria-hidden="true">{{ g.capitulo.selo }}</span>
        <div class="fv-cap__texto">
          <h2 :ref="(el) => guardarTitulo(g.capitulo.chave, el)" class="fv-cap__titulo" tabindex="-1">{{ g.capitulo.rotulo }}</h2>
          <p class="fv-cap__sub">
            <time v-if="g.capitulo.ano" :datetime="String(g.capitulo.ano)" :title="`Ano de ${g.capitulo.ano}`">{{ haAnos(g.capitulo.ano) }}</time>
            <span v-if="g.capitulo.ano" class="sr-only">, ano de {{ g.capitulo.ano }}</span>
            <template v-else>{{ g.capitulo.sub }}</template>
            · {{ pluralFotos(g.capitulo.total) }}
          </p>
        </div>
      </header>
      <header v-if="!semCabecalho" class="foto-grupo-cab">
        <component :is="capitulos ? 'h3' : 'h2'" class="foto-grupo-titulo">{{ g.rotulo }}</component>
        <span class="foto-grupo-total" aria-label="fotos">{{ g.itens.length }}</span>
        <button
          type="button"
          class="foto-grupo-check"
          role="checkbox"
          tabindex="-1"
          :aria-checked="estadoGrupo(g) === 2 ? 'true' : estadoGrupo(g) === 1 ? 'mixed' : 'false'"
          :aria-label="`Selecionar todas as fotos de ${g.rotulo}`"
          :class="{ marcado: estadoGrupo(g) === 2, parcial: estadoGrupo(g) === 1, 'modo-selecao': selecaoAtiva }"
          @click="$emit('grupo', g.itens.map((f) => f.uuid))"
        >
          <v-icon size="18" aria-hidden="true">{{ estadoGrupo(g) === 1 ? "mdi-minus" : "mdi-check" }}</v-icon>
        </button>
      </header>
      <div v-for="(linha, li) in g.linhas" :key="li" class="foto-linha" role="row" :style="{ gap: `${gap}px`, height: `${linha.altura}px` }">
        <FotoItem
          v-for="c in linha.itens"
          :key="c.item.uuid"
          :foto="c.item"
          :w="c.w"
          :h="c.h"
          :selecionada="selecionados.has(c.item.uuid)"
          :modo-selecao="selecaoAtiva"
          :foco="c.item.uuid === focoUuid"
          :novo="novos.has(c.item.uuid)"
          :atraso="atrasoDe(c.item.uuid)"
          @abrir="aoAbrir"
          @marcar="(f, e) => aoMarcar(f, e)"
          @check="aoMarcar"
          @longo="(f) => $emit('longo', f.uuid)"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from "vue";
import { useDisplay } from "vuetify";
import FotoItem from "./FotoItem.vue";
import { justificar, useLargura } from "@/composables/useGradeJustificada";
import { chaveDoAno, anoDoTexto, metaCapitulo, haAnos, pluralFotos } from "@/composables/useEraFotosVideos";
import { revelarAoRolar } from "@/composables/useMovimentoEsquina";

const props = defineProps({
  /** [{ chave, mes, rotulo, itens }] */
  grupos: { type: Array, default: () => [] },
  selecionados: { type: Set, default: () => new Set() },
  selecaoAtiva: Boolean,
  semCabecalho: Boolean,
  /** Multiplicador da altura-alvo (densidade). */
  escala: { type: Number, default: 1 },
  ocupado: Boolean,
  rotuloGrade: { type: String, default: "Fotos" },
  /** Cabeçalhos de capítulo por ano (selo + h2). */
  capitulos: Boolean,
  /** Total de fotos por capítulo ({ "2014": 132, antes: 5 }); sem isso soma o que está carregado. */
  totaisAno: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["abrir", "alternar", "intervalo", "grupo", "longo", "mes-visivel"]);

const raiz = ref(null);
const largura = useLargura(raiz);
const { width: larguraTela } = useDisplay();

const gap = computed(() => (larguraTela.value < 600 ? 2 : 4));
const alvo = computed(() => {
  const base = larguraTela.value < 600 ? 120 : larguraTela.value < 960 ? 180 : 220;
  return Math.round(base * props.escala);
});

/* ---------- entrada de itens novos (só os que chegaram agora; sem custo na seleção) ---------- */
const jaVistos = new Set(); // não reativo: cada uuid anima uma vez
const novos = ref(new Map()); // uuid -> posição na leva (para o stagger)
const gruposNovos = ref(new Set());
watch(
  () => props.grupos,
  (grupos) => {
    const mapa = new Map();
    const gr = new Set();
    for (const g of grupos) {
      for (const f of g.itens) {
        if (jaVistos.has(f.uuid)) continue;
        jaVistos.add(f.uuid);
        mapa.set(f.uuid, mapa.size);
        gr.add(g.chave);
      }
    }
    if (mapa.size || novos.value.size) novos.value = mapa;
    if (gr.size || gruposNovos.value.size) gruposNovos.value = gr;
  },
  { immediate: true },
);
// Stagger limitado: no máximo 10 degraus de 30 ms, para leva grande não demorar.
const atrasoDe = (uuid) => Math.min(novos.value.get(uuid) ?? 0, 10) * 30;

// Depende só de grupos/largura (não da seleção): evita re-empacotar tudo a cada clique.
const blocos = computed(() => {
  let anterior = null;
  const carregado = {};
  if (props.capitulos) {
    for (const g of props.grupos) {
      const k = chaveDoAno(anoDoTexto(g.chave));
      carregado[k] = (carregado[k] ?? 0) + g.itens.length;
    }
  }
  return props.grupos.map((g) => {
    let capitulo = null;
    if (props.capitulos) {
      const k = chaveDoAno(anoDoTexto(g.chave));
      if (k !== anterior) {
        capitulo = { ...metaCapitulo(k), total: props.totaisAno[k] ?? carregado[k] };
        anterior = k;
      }
    }
    return { ...g, capitulo, linhas: justificar(g.itens, largura.value, alvo.value, gap.value) };
  });
});

/* títulos dos capítulos (para focar após rolar pelos cartões de ano) */
const titulos = new Map();
function guardarTitulo(chave, el) {
  if (el) titulos.set(chave, el);
  else titulos.delete(chave);
}
/* Motion 3: o capítulo ganha .is-visivel ao entrar na tela (selo pina, filete cresce). Uma vez por capítulo. */
const revelados = reactive(new Set());
const revelaCapitulos = revelarAoRolar((el) => revelados.add(el.dataset.capitulo));
function observarCapitulos() {
  nextTick(() => revelaCapitulos.observar(Array.from(raiz.value?.querySelectorAll(".fv-cap[data-capitulo]") ?? [])));
}
watch(blocos, observarCapitulos, { flush: "post" });
onMounted(observarCapitulos);
onBeforeUnmount(() => revelaCapitulos.parar());

function focarCapitulo(chave) {
  const el = titulos.get(chave);
  if (!el) return false;
  el.focus({ preventScroll: true });
  return true;
}

/** 0 = nenhuma marcada, 1 = parcial, 2 = todas. */
function estadoGrupo(g) {
  if (!props.selecionados.size) return 0;
  let n = 0;
  for (const f of g.itens) if (props.selecionados.has(f.uuid)) n++;
  return n === 0 ? 0 : n === g.itens.length ? 2 : 1;
}

/* ---------- foco (roving tabindex) ---------- */
const focoUuid = ref(null);
const planas = computed(() => {
  // Linhas em ordem visual, com o centro x de cada célula.
  const lista = [];
  for (const g of blocos.value) {
    for (const l of g.linhas) {
      let x = 0;
      lista.push(
        l.itens.map((c) => {
          const centro = x + c.w / 2;
          x += c.w + gap.value;
          return { uuid: c.item.uuid, centro };
        }),
      );
    }
  }
  return lista;
});

watch(
  planas,
  (p) => {
    const existe = p.some((l) => l.some((c) => c.uuid === focoUuid.value));
    if (!existe) focoUuid.value = p[0]?.[0]?.uuid ?? null;
  },
  { immediate: true },
);

function focar(uuid) {
  focoUuid.value = uuid;
  nextTick(() => {
    const el = raiz.value?.querySelector(`[data-uuid="${uuid}"] .foto-abrir`);
    el?.focus();
    el?.scrollIntoView?.({ block: "nearest" });
  });
}

function aoTecla(e) {
  const cel = e.target.closest?.("[data-uuid]");
  if (!cel || !["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Home", "End"].includes(e.key)) return;
  const uuid = cel.dataset.uuid;
  const p = planas.value;
  const li = p.findIndex((l) => l.some((c) => c.uuid === uuid));
  if (li < 0) return;
  const ci = p[li].findIndex((c) => c.uuid === uuid);
  let alvoUuid = null;
  if (e.key === "ArrowRight") alvoUuid = p[li][ci + 1]?.uuid ?? p[li + 1]?.[0]?.uuid;
  else if (e.key === "ArrowLeft") alvoUuid = p[li][ci - 1]?.uuid ?? p[li - 1]?.at(-1)?.uuid;
  else if (e.key === "Home") alvoUuid = e.ctrlKey ? p[0]?.[0]?.uuid : p[li][0].uuid;
  else if (e.key === "End") alvoUuid = e.ctrlKey ? p.at(-1)?.at(-1)?.uuid : p[li].at(-1).uuid;
  else {
    const vizinha = p[li + (e.key === "ArrowDown" ? 1 : -1)];
    if (vizinha) {
      const x = p[li][ci].centro;
      alvoUuid = vizinha.reduce((m, c) => (Math.abs(c.centro - x) < Math.abs(m.centro - x) ? c : m)).uuid;
    }
  }
  if (!alvoUuid) return;
  e.preventDefault();
  if (e.shiftKey) emit("intervalo", alvoUuid);
  focar(alvoUuid);
}

/* ---------- cliques ---------- */
function aoAbrir(foto, e) {
  focoUuid.value = foto.uuid;
  if (e?.shiftKey) emit("intervalo", foto.uuid);
  else if (e?.ctrlKey || e?.metaKey || props.selecaoAtiva) emit("alternar", foto.uuid);
  else emit("abrir", foto.uuid);
}
function aoMarcar(foto, e) {
  focoUuid.value = foto.uuid;
  if (e?.shiftKey) emit("intervalo", foto.uuid);
  else emit("alternar", foto.uuid);
}

/* ---------- mês visível (para o scrubber) ---------- */
let observer = null;
function observar() {
  observer?.disconnect();
  if (!("IntersectionObserver" in window) || !raiz.value) return;
  observer = new IntersectionObserver(
    (entries) => {
      const visivel = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0];
      if (visivel) emit("mes-visivel", visivel.target.dataset.mes);
    },
    { rootMargin: "-130px 0px -75% 0px" },
  );
  raiz.value.querySelectorAll(".foto-grupo").forEach((el) => observer.observe(el));
}
watch(
  () => props.grupos.length + ":" + (props.grupos.at(-1)?.itens.length ?? 0),
  () => nextTick(observar),
);
onMounted(() => nextTick(observar));
onBeforeUnmount(() => observer?.disconnect());

defineExpose({ focar, focarCapitulo });
</script>

<style scoped>
.fotos-grade {
  width: 100%;
}
.foto-grupo {
  margin-bottom: 16px;
  scroll-margin-top: calc(var(--tm-navbar-h) + 56px);
}
.foto-grupo-cab {
  position: sticky;
  top: calc(var(--tm-navbar-h) + 56px);
  z-index: 3;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 44px;
  padding: 6px 4px;
  background: var(--tm-paper);
}
.foto-grupo-titulo {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--tm-ink);
}
.foto-grupo-total {
  margin-left: auto;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.foto-grupo-check {
  width: 28px;
  height: 28px;
  padding: 0;
  border-radius: 50%;
  border: 2px solid var(--tm-ink-muted);
  background: transparent;
  color: transparent;
  display: grid;
  place-items: center;
  cursor: pointer;
  opacity: 0;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.foto-grupo-cab:hover .foto-grupo-check,
.foto-grupo-check:focus-visible,
.foto-grupo-check.modo-selecao {
  opacity: 1;
}
.foto-grupo-check.marcado,
.foto-grupo-check.parcial {
  opacity: 1;
  background: var(--tm-gold);
  border-color: var(--tm-gold);
  color: #2a2112;
}
@media (hover: none) {
  .foto-grupo-check {
    opacity: 1;
  }
}
.foto-linha {
  display: flex;
  margin-bottom: 4px;
}
/* Seção nova: só opacidade, sem translate (não desloca o layout). */
.foto-grupo.novo {
  animation: foto-grupo-entra var(--tm-dur-base) var(--tm-ease) backwards;
}
@keyframes foto-grupo-entra {
  from {
    opacity: 0;
  }
}
html[data-reduzir="1"] .foto-grupo.novo {
  animation: none;
}
@media (max-width: 599px) {
  .foto-linha {
    margin-bottom: 2px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .foto-grupo-check {
    transition: none;
  }
}
</style>
