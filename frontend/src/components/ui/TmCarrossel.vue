<template>
  <div class="tm-carrossel" :class="{ 'tm-carrossel--esq': podeEsq, 'tm-carrossel--dir': podeDir }">
    <button
      v-show="transborda"
      type="button"
      class="tm-carrossel__seta tm-carrossel__seta--esq"
      :aria-label="rotuloAnterior"
      :aria-disabled="!podeEsq"
      :class="{ 'tm-carrossel__seta--off': !podeEsq }"
      @click="podeEsq && rolar(-1)"
    >
      <v-icon size="24" aria-hidden="true">mdi-chevron-left</v-icon>
    </button>
    <!-- atributos (class, role, tabindex, aria-label) vão para a faixa rolável -->
    <div ref="tiraRef" v-bind="$attrs" class="tm-carrossel__tira" @scroll.passive="atualizar">
      <slot />
    </div>
    <button
      v-show="transborda"
      type="button"
      class="tm-carrossel__seta tm-carrossel__seta--dir"
      :aria-label="rotuloProximo"
      :aria-disabled="!podeDir"
      :class="{ 'tm-carrossel__seta--off': !podeDir }"
      @click="podeDir && rolar(1)"
    >
      <v-icon size="24" aria-hidden="true">mdi-chevron-right</v-icon>
    </button>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { prefereMenosMovimento } from "@/composables/useTema";

defineOptions({ inheritAttrs: false });
defineProps({
  rotuloAnterior: { type: String, default: "Rolar para a esquerda" },
  rotuloProximo: { type: String, default: "Rolar para a direita" },
});

const tiraRef = ref(null);
const transborda = ref(false);
const podeEsq = ref(false);
const podeDir = ref(false);

function atualizar() {
  const b = tiraRef.value;
  if (!b) return;
  transborda.value = b.scrollWidth - b.clientWidth > 2;
  podeEsq.value = b.scrollLeft > 2;
  podeDir.value = b.scrollLeft + b.clientWidth < b.scrollWidth - 2;
}
function rolar(dir) {
  const b = tiraRef.value;
  if (!b) return;
  b.scrollBy({ left: dir * b.clientWidth * 0.8, behavior: prefereMenosMovimento() ? "auto" : "smooth" });
}

let ro = null;
let mo = null;
onMounted(() => {
  atualizar();
  if ("ResizeObserver" in window) {
    ro = new ResizeObserver(atualizar);
    ro.observe(tiraRef.value);
  }
  if ("MutationObserver" in window) {
    mo = new MutationObserver(atualizar);
    mo.observe(tiraRef.value, { childList: true });
  }
});
onBeforeUnmount(() => {
  ro?.disconnect();
  mo?.disconnect();
});
defineExpose({ atualizar, rolar });
</script>

<style scoped>
.tm-carrossel {
  position: relative;
  min-width: 0;
}
.tm-carrossel__tira {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.tm-carrossel__tira::-webkit-scrollbar {
  display: none;
}
/* fade nas bordas com conteúdo além */
.tm-carrossel::before,
.tm-carrossel::after {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  width: 40px;
  z-index: 1;
  pointer-events: none;
  opacity: 0;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.tm-carrossel::before {
  left: 0;
  background: linear-gradient(to right, var(--tm-paper), transparent);
}
.tm-carrossel::after {
  right: 0;
  background: linear-gradient(to left, var(--tm-paper), transparent);
}
.tm-carrossel--esq::before,
.tm-carrossel--dir::after {
  opacity: 1;
}
.tm-carrossel__seta {
  position: absolute;
  top: 50%;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  margin-top: -28px; /* centraliza na área dos cards (desconta o respiro inferior) */
  border: 1px solid var(--tm-gold);
  border-radius: 50%;
  background: color-mix(in srgb, var(--tm-paper) 88%, transparent);
  color: var(--tm-gold-text);
  box-shadow: var(--tm-shadow-sm);
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
  transition: opacity var(--tm-dur-fast) var(--tm-ease), background var(--tm-dur-fast) var(--tm-ease);
}
.tm-carrossel__seta--esq {
  left: 4px;
}
.tm-carrossel__seta--dir {
  right: 4px;
}
.tm-carrossel__seta:hover:not(.tm-carrossel__seta--off) {
  background: var(--tm-paper-2);
}
.tm-carrossel__seta--off:not(:focus-visible) {
  opacity: 0;
  pointer-events: none;
}
.tm-carrossel__seta:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: 2px;
}
/* toque rola direto: setas só em ponteiro fino */
@media (hover: none), (pointer: coarse) {
  .tm-carrossel__seta {
    display: none;
  }
}
@media (prefers-reduced-motion: reduce) {
  .tm-carrossel::before,
  .tm-carrossel::after,
  .tm-carrossel__seta {
    transition: none;
  }
}
html[data-reduzir="1"] .tm-carrossel::before,
html[data-reduzir="1"] .tm-carrossel::after,
html[data-reduzir="1"] .tm-carrossel__seta {
  transition: none;
}
</style>
