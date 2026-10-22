<template>
  <div v-if="meses.length" class="scrubber-raiz" :class="{ mobile, visivel: visivel || arrastando, arrastando }">
    <nav class="scrubber-nav" aria-label="Linha do tempo">
      <ul>
        <li v-for="a in anos" :key="a.ano">
          <button type="button" @click="$emit('ir', a.primeiroMes)">{{ a.ano }}</button>
        </li>
      </ul>
    </nav>

    <div
      ref="trilho"
      class="scrubber-trilho"
      aria-hidden="true"
      @pointerdown="comecar"
      @pointermove="mover"
      @pointerup="soltar"
      @pointercancel="cancelar"
      @pointerleave="sair"
    >
      <span v-for="a in anosVisiveis" :key="a.ano" class="scrubber-ano" :style="{ top: `${a.y}px` }">{{ a.ano }}</span>
      <span v-for="m in pontos" :key="m.data" class="scrubber-ponto" :class="{ ativo: m.data === atual }" :style="{ top: `${m.y}px`, '--hx-ano-h': m.hue }"></span>
      <span v-if="posAtual != null" class="scrubber-atual" :style="{ transform: `translateY(${posAtual}px)` }"></span>
      <span v-if="bolha" :key="bolha.texto" class="scrubber-bolha" :style="{ transform: `translateY(${bolha.y}px)` }">{{ bolha.texto }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useDisplay } from "vuetify";
import { rotuloMes } from "@/composables/useFotoDatas";
import { hueDoAno } from "@/composables/useCapitulos";
import { tempoRelativo } from "@/composables/useFormat";

const props = defineProps({
  /** [{ data: "YYYY-MM", total }] na ordem exibida. */
  meses: { type: Array, default: () => [] },
  atual: { type: String, default: "" },
});
const emit = defineEmits(["ir"]);

const { mdAndUp } = useDisplay();
const mobile = computed(() => !mdAndUp.value);

const trilho = ref(null);
const altura = ref(0);
const arrastando = ref(false);
const bolha = ref(null);
const visivel = ref(false);
let ro = null;
let timerVisivel = null;

const totalGeral = computed(() => props.meses.reduce((n, m) => n + m.total, 0) || 1);
const comecos = computed(() => {
  let acc = 0;
  return props.meses.map((m) => {
    const inicio = acc / totalGeral.value;
    acc += m.total;
    return inicio;
  });
});

const pontos = computed(() => props.meses.map((m, i) => ({ data: m.data, y: Math.round(comecos.value[i] * altura.value), hue: hueDoAno(Number(m.data.slice(0, 4))) })));

const anos = computed(() => {
  const mapa = new Map();
  props.meses.forEach((m, i) => {
    const ano = m.data.slice(0, 4);
    if (!mapa.has(ano)) mapa.set(ano, { ano, primeiroMes: m.data, y: Math.round(comecos.value[i] * altura.value) });
  });
  return Array.from(mapa.values());
});

// Rótulos de ano só se couberem (mínimo de 18 px entre eles).
const anosVisiveis = computed(() => {
  let ultimo = -100;
  return anos.value.filter((a) => {
    if (a.y - ultimo < 18) return false;
    ultimo = a.y;
    return true;
  });
});

const posAtual = computed(() => {
  const i = props.meses.findIndex((m) => m.data === props.atual);
  return i < 0 ? null : Math.round(comecos.value[i] * altura.value);
});

function mesEm(y) {
  const f = Math.min(0.9999, Math.max(0, y / (altura.value || 1)));
  let achado = 0;
  for (let i = 0; i < comecos.value.length; i++) {
    if (comecos.value[i] <= f) achado = i;
    else break;
  }
  return props.meses[achado]?.data;
}

function yDoEvento(e) {
  const r = trilho.value.getBoundingClientRect();
  return Math.min(altura.value, Math.max(0, e.clientY - r.top));
}

function mostrarBolha(e) {
  const y = yDoEvento(e);
  const mes = mesEm(y);
  if (mes) bolha.value = { y: Math.max(0, y - 16), texto: `${rotuloMes(mes)} · ${tempoRelativo(`${mes}-01`)}`, mes };
}

function comecar(e) {
  arrastando.value = true;
  trilho.value.setPointerCapture?.(e.pointerId);
  mostrarBolha(e);
}
function mover(e) {
  if (arrastando.value || e.pointerType === "mouse") mostrarBolha(e);
}
function soltar(e) {
  if (!arrastando.value) return;
  mostrarBolha(e);
  const mes = bolha.value?.mes;
  arrastando.value = false;
  bolha.value = null;
  if (mes) emit("ir", mes);
}
function cancelar() {
  arrastando.value = false;
  bolha.value = null;
}
function sair() {
  if (!arrastando.value) bolha.value = null;
}

function aoRolar() {
  if (!mobile.value) return;
  visivel.value = true;
  clearTimeout(timerVisivel);
  timerVisivel = setTimeout(() => (visivel.value = false), 1500);
}

function medir() {
  if (trilho.value) altura.value = trilho.value.clientHeight;
}
onMounted(() => {
  medir();
  if (typeof ResizeObserver !== "undefined" && trilho.value) {
    ro = new ResizeObserver(medir);
    ro.observe(trilho.value);
  }
  window.addEventListener("scroll", aoRolar, { passive: true });
});
watch(() => props.meses.length, () => requestAnimationFrame(medir));
onBeforeUnmount(() => {
  ro?.disconnect();
  window.removeEventListener("scroll", aoRolar);
  clearTimeout(timerVisivel);
});
</script>

<style scoped>
.scrubber-raiz {
  position: fixed;
  right: 0;
  top: calc(var(--tm-navbar-h) + 56px + 44px);
  bottom: calc(var(--tm-player-h, 0px) + 16px);
  width: 56px;
  z-index: 4;
  pointer-events: none;
}
.scrubber-trilho {
  position: relative;
  height: 100%;
  width: 100%;
  pointer-events: auto;
  touch-action: none;
  cursor: pointer;
}
.scrubber-ano {
  position: absolute;
  right: 18px;
  font-size: 12px;
  line-height: 1;
  color: var(--tm-ink-muted);
  transform: translateY(-50%);
  user-select: none;
}
.scrubber-ponto {
  position: absolute;
  right: 6px;
  width: 6px;
  height: 6px;
  margin-top: -3px;
  border-radius: 50%;
  /* Matiz do capítulo (mesmo --hx-ano-h da Esquina); só acento */
  background: hsl(var(--hx-ano-h, 45) 45% 55% / 0.7);
}
.scrubber-ponto.ativo {
  background: var(--tm-gold);
}
.scrubber-atual {
  position: absolute;
  top: -1px;
  right: 2px;
  width: 12px;
  height: 3px;
  border-radius: 2px;
  background: var(--tm-gold);
  transition: transform var(--tm-dur-fast) linear;
}
.scrubber-bolha {
  position: absolute;
  top: 0;
  right: 64px;
  padding: 4px 12px;
  border-radius: var(--tm-radius-pill);
  background: var(--tm-paper);
  color: var(--tm-ink);
  box-shadow: var(--tm-shadow-md);
  font-family: var(--tm-font-title);
  font-size: 14px;
  white-space: nowrap;
  pointer-events: none;
  /* Só aparece (opacity). O transform segue o dedo/cursor sem animação. */
  animation: bolha-aparece 120ms var(--tm-ease) backwards;
}
@keyframes bolha-aparece {
  from {
    opacity: 0;
  }
}
.scrubber-nav {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  pointer-events: auto;
}
.scrubber-nav:focus-within {
  position: fixed;
  right: 64px;
  top: calc(var(--tm-navbar-h) + 120px);
  width: auto;
  height: auto;
  max-height: 60vh;
  overflow: auto;
  clip: auto;
  background: var(--tm-paper);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-xs);
  box-shadow: var(--tm-shadow-md);
  padding: 8px;
}
.scrubber-nav ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.scrubber-nav button {
  padding: 6px 12px;
  color: var(--tm-ink);
  background: transparent;
  border: 0;
  cursor: pointer;
}

/* Mobile: trilho de 24 px na borda, só enquanto rola ou arrasta. */
.scrubber-raiz.mobile {
  width: 24px;
  opacity: 0;
  transition: opacity 200ms var(--tm-ease);
}
.scrubber-raiz.mobile.visivel {
  opacity: 1;
}
.scrubber-raiz.mobile:not(.visivel) .scrubber-trilho {
  pointer-events: none;
}
.scrubber-raiz.mobile .scrubber-ano,
.scrubber-raiz.mobile .scrubber-ponto {
  display: none;
}
.scrubber-raiz.mobile .scrubber-atual {
  right: 0;
  width: 24px;
  height: 44px;
  top: -22px;
  border-radius: 12px 0 0 12px;
  background: var(--tm-gold);
  opacity: 0.9;
}
.scrubber-raiz.mobile .scrubber-bolha {
  right: 36px;
}
@media (prefers-reduced-motion: reduce) {
  .scrubber-raiz.mobile,
  .scrubber-atual,
  .scrubber-bolha {
    transition: none;
    animation: none;
  }
}
html[data-reduzir="1"] .scrubber-bolha {
  animation: none;
}
</style>
