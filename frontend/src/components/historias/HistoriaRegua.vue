<template>
  <nav class="hx-regua" aria-label="Anos">
    <button
      v-show="transborda"
      type="button"
      class="hx-regua__seta hx-regua__seta--esq"
      aria-label="Anos anteriores"
      :disabled="!podeEsq"
      @click="rolar(-1)"
    >
      <v-icon size="22" aria-hidden="true">mdi-chevron-left</v-icon>
    </button>
    <div class="hx-regua__faixa" :class="{ 'hx-regua__faixa--esq': podeEsq, 'hx-regua__faixa--dir': podeDir }">
    <ul ref="listaRef" class="hx-regua__lista" @scroll.passive="atualizarBordas">
      <!-- pílula de destaque: desliza até o chip ativo (transform), posicionada por JS -->
      <li ref="pilulaRef" class="hx-regua__pilula" aria-hidden="true"></li>
      <li v-for="c in itens" :key="c.chave" class="hx-regua__item">
        <a
          :href="`#${c.id}`"
          class="hx-regua__chip"
          :class="{ 'hx-regua__chip--ativo': c.id === ativo, 'hx-regua__chip--vazio': !c.total }"
          :aria-current="c.id === ativo ? 'true' : undefined"
          :aria-disabled="!c.total ? 'true' : undefined"
          :tabindex="!c.total ? -1 : undefined"
          :data-id="c.id"
          @click.prevent="c.total && $emit('ir', c.id)"
        >
          {{ c.rotulo }}
          <span v-if="c.total" class="hx-regua__qtd" aria-hidden="true">{{ c.total }}</span>
          <span v-if="c.total" class="sr-only">, {{ c.total }} {{ c.total === 1 ? "história" : "histórias" }}</span>
        </a>
      </li>
    </ul>
    </div>
    <button
      v-show="transborda"
      type="button"
      class="hx-regua__seta hx-regua__seta--dir"
      aria-label="Próximos anos"
      :disabled="!podeDir"
      @click="rolar(1)"
    >
      <v-icon size="22" aria-hidden="true">mdi-chevron-right</v-icon>
    </button>
  </nav>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch, nextTick } from "vue";
import { ANOS } from "@/config/esquina";
import { chaveCapitulo } from "@/composables/useCapitulos";

const props = defineProps({
  /** histórias já filtradas */
  historias: { type: Array, default: () => [] },
  ativo: { type: String, default: "" },
});
defineEmits(["ir"]);

const listaRef = ref(null);

const reduzir = () => matchMedia("(prefers-reduced-motion: reduce)").matches || document.documentElement.dataset.reduzir === "1";

// Setas de rolagem: só habilitam do lado em que ainda há conteúdo.
const transborda = ref(false);
const podeEsq = ref(false);
const podeDir = ref(false);
function atualizarBordas() {
  const b = listaRef.value;
  if (!b) return;
  transborda.value = b.scrollWidth - b.clientWidth > 2;
  podeEsq.value = b.scrollLeft > 2;
  podeDir.value = b.scrollLeft + b.clientWidth < b.scrollWidth - 2;
}
function rolar(dir) {
  const b = listaRef.value;
  if (!b) return;
  b.scrollBy({ left: dir * b.clientWidth * 0.8, behavior: reduzir() ? "auto" : "smooth" });
}
let ro = null;

const itens = computed(() => {
  const cont = {};
  props.historias.forEach((h) => {
    const k = chaveCapitulo(h);
    cont[k] = (cont[k] || 0) + 1;
  });
  const mk = (k, rotulo, id) => ({ chave: k, rotulo, id, total: cont[k] || 0 });
  const lista = [];
  if (cont.antes) lista.push(mk("antes", "Antes da Rua", "ano-antes"));
  ANOS.forEach((a) => lista.push(mk(String(a), String(a), `ano-${a}`)));
  if (cont.depois) lista.push(mk("depois", "Depois da Rua", "ano-depois"));
  if (cont.sem) lista.push(mk("sem", "Sem data", "ano-sem-data"));
  return lista;
});

// Pílula de destaque: mede o chip ativo e desliza até ele (só transform; largura/altura só na medição).
const pilulaRef = ref(null);
let medida = false;
function posicionarPilula() {
  const box = listaRef.value;
  const p = pilulaRef.value;
  if (!box || !p) return;
  const el = props.ativo ? box.querySelector(`[data-id="${props.ativo}"]`) : null;
  if (!el) {
    p.style.opacity = "0";
    return;
  }
  if (!medida) {
    p.style.transition = "none";
    medida = true;
    requestAnimationFrame(() => (p.style.transition = ""));
  }
  p.style.width = `${el.offsetWidth}px`;
  p.style.height = `${el.offsetHeight}px`;
  p.style.transform = `translate3d(${el.offsetLeft}px, ${el.offsetTop}px, 0)`;
  p.style.opacity = "1";
}
watch(
  () => [props.ativo, itens.value],
  () => nextTick(() => {
    posicionarPilula();
    atualizarBordas();
  }),
);
onMounted(() => {
  posicionarPilula();
  atualizarBordas();
  window.addEventListener("resize", posicionarPilula);
  if ("ResizeObserver" in window && listaRef.value) {
    ro = new ResizeObserver(atualizarBordas);
    ro.observe(listaRef.value);
  }
});
onBeforeUnmount(() => {
  window.removeEventListener("resize", posicionarPilula);
  ro?.disconnect();
});

// Mantém o chip ativo visível na faixa rolável (mobile) sem mexer na rolagem da página.
watch(
  () => props.ativo,
  async (id) => {
    await nextTick();
    const el = listaRef.value?.querySelector(`[data-id="${id}"]`);
    const box = listaRef.value;
    if (!el || !box || box.scrollWidth <= box.clientWidth) return;
    box.scrollTo({ left: el.offsetLeft - (box.clientWidth - el.offsetWidth) / 2, behavior: reduzir() ? "auto" : "smooth" });
  },
);
</script>

<style scoped>
.hx-regua {
  position: sticky;
  top: var(--tm-navbar-h);
  z-index: 20;
  background: var(--tm-paper);
  border-bottom: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-sm);
  display: flex;
  align-items: center;
  max-width: 1100px;
  margin-inline: auto;
  padding-inline: var(--tm-space-2);
  gap: var(--tm-space-1);
}
.hx-regua__faixa {
  position: relative;
  flex: 1;
  min-width: 0;
}
/* fade nas bordas com conteúdo além */
.hx-regua__faixa::before,
.hx-regua__faixa::after {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  width: 28px;
  z-index: 1;
  pointer-events: none;
  opacity: 0;
  transition: opacity 150ms;
}
.hx-regua__faixa::before {
  left: 0;
  background: linear-gradient(to right, var(--tm-paper), transparent);
}
.hx-regua__faixa::after {
  right: 0;
  background: linear-gradient(to left, var(--tm-paper), transparent);
}
.hx-regua__faixa--esq::before,
.hx-regua__faixa--dir::after {
  opacity: 1;
}
.hx-regua__lista {
  list-style: none;
  margin: 0;
  padding: var(--tm-space-2) var(--tm-space-3);
  display: flex;
  gap: var(--tm-space-2);
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  scroll-snap-type: x proximity;
  justify-content: safe center;
  user-select: none;
  -webkit-user-select: none;
}
.hx-regua__lista::-webkit-scrollbar {
  display: none;
}
.hx-regua__seta {
  flex: none;
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: var(--tm-ink);
  cursor: pointer;
}
.hx-regua__seta:hover:not(:disabled) {
  background: var(--hx-balao);
}
.hx-regua__seta:disabled {
  opacity: 0.3;
  cursor: default;
}
.hx-regua__seta:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
}
.hx-regua__item {
  flex: none;
  scroll-snap-align: center;
}
.hx-regua__chip {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  padding: 0 14px;
  border-radius: var(--tm-radius-pill);
  color: var(--tm-ink);
  font-weight: 600;
  font-size: var(--tm-fs-sm);
  text-decoration: none;
  white-space: nowrap;
  border: 1px solid transparent;
  user-select: none;
  -webkit-user-select: none;
  -webkit-user-drag: none;
}
.hx-regua__chip:hover {
  background: var(--hx-balao);
}
.hx-regua__chip--ativo {
  background: var(--tm-ink);
  color: var(--tm-paper);
}
.hx-regua__chip--ativo:hover {
  background: var(--tm-ink);
}
.hx-regua__chip--vazio {
  color: var(--tm-ink-muted);
  opacity: 0.6;
  cursor: default;
}
.hx-regua__chip--vazio:hover {
  background: transparent;
}
.hx-regua__qtd {
  font-size: var(--tm-fs-xs);
  font-variant-numeric: tabular-nums;
  opacity: 0.85;
}
.hx-regua__chip:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
}
</style>
