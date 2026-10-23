<template>
  <div
    class="pasta-card hx-ano"
    :class="{ 'alvo-drop': sobre, 'pasta-ano': anoPasta }"
    :style="anoPasta ? { '--hx-ano-h': hueDoAno(anoPasta) } : null"
    :data-uuid="pasta.uuid"
    v-bind="handlers"
    @contextmenu.prevent="abrirMenuPonteiro"
    @keydown="aoTecla"
    @pointerdown="inicioToque"
    @pointerup="fimToque"
    @pointermove="moveuToque"
    @pointercancel="fimToque"
  >
    <button type="button" class="pasta-abrir" :aria-label="`Pasta ${pasta.nome}, ${contagem}`" @click="aoClicar">
      <v-icon size="24" class="pasta-icone" aria-hidden="true">mdi-folder</v-icon>
      <span class="pasta-texto">
        <strong>{{ pasta.nome }}</strong>
        <small>{{ contagem }}</small>
      </span>
    </button>
    <v-btn
      ref="botaoMais"
      icon
      size="small"
      variant="text"
      class="tm-touch"
      aria-haspopup="menu"
      :aria-label="`Ações da pasta ${pasta.nome}`"
      @click.stop="abrirMenuBotao"
    >
      <v-icon>mdi-dots-vertical</v-icon>
    </v-btn>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useAlvoDrop } from "@/composables/useAlvoDrop";
import { hueDoAno } from "@/composables/useCapitulos";
import { ANOS } from "@/config/esquina";

const props = defineProps({
  pasta: { type: Object, required: true },
  podeSoltar: Boolean,
});
const emit = defineEmits(["abrir", "menu", "soltar"]);

const botaoMais = ref(null);
// Pasta com nome de ano da era: aba colorida pelo matiz do ano (heurística visual; sem dado novo).
const anoPasta = computed(() => {
  const n = /^\s*(\d{4})\s*$/.exec(props.pasta.nome)?.[1];
  return n && ANOS.includes(Number(n)) ? Number(n) : null;
});
const contagem = computed(() => `${props.pasta.videos_count} ${props.pasta.videos_count === 1 ? "vídeo" : "vídeos"}`);
const { sobre, handlers } = useAlvoDrop((payload) => emit("soltar", props.pasta.uuid, props.pasta.nome, payload), {
  ativo: () => props.podeSoltar,
});

function abrirMenuBotao(e) {
  const el = (e.currentTarget ?? botaoMais.value?.$el);
  const r = el.getBoundingClientRect();
  emit("menu", props.pasta, { x: r.left, y: r.bottom }, el);
}
function abrirMenuPonteiro(e) {
  emit("menu", props.pasta, { x: e.clientX, y: e.clientY }, e.currentTarget.querySelector(".pasta-abrir"));
}
function aoTecla(e) {
  if (e.key === "ContextMenu" || (e.key === "F10" && e.shiftKey)) {
    e.preventDefault();
    const botao = e.currentTarget.querySelector(".pasta-abrir");
    const r = botao.getBoundingClientRect();
    emit("menu", props.pasta, { x: r.left + 16, y: r.bottom }, botao);
  }
}

// Long-press (500 ms) abre o menu em telas de toque.
let timer = null;
let longo = false;
let origem = null;
function inicioToque(e) {
  if (e.pointerType === "mouse") return;
  longo = false;
  clearTimeout(timer);
  const { clientX: x, clientY: y } = e;
  origem = { x, y };
  const alvo = e.currentTarget.querySelector(".pasta-abrir");
  timer = setTimeout(() => {
    longo = true;
    navigator.vibrate?.(10);
    emit("menu", props.pasta, { x, y }, alvo);
  }, 500);
}
function moveuToque(e) {
  if (origem && (Math.abs(e.clientX - origem.x) > 8 || Math.abs(e.clientY - origem.y) > 8)) fimToque();
}
function fimToque() {
  clearTimeout(timer);
  origem = null;
}
function aoClicar() {
  if (longo) {
    longo = false;
    return;
  }
  emit("abrir", props.pasta);
}
</script>

<style scoped>
.pasta-card {
  position: relative;
  display: flex;
  align-items: center;
  height: 56px;
  padding-right: 4px;
  background: var(--tm-paper-2);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-sm);
  color: var(--tm-ink);
  transition: border-color var(--tm-dur-fast) var(--tm-ease), background-color var(--tm-dur-fast) var(--tm-ease);
}
.pasta-card:hover {
  border-color: var(--tm-gold);
}
.pasta-abrir {
  flex: 1;
  min-width: 0;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 12px;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
  border-radius: var(--tm-radius-sm);
}
.pasta-ano {
  border-left: 4px solid var(--hx-ano-cor);
}
.pasta-ano .pasta-icone {
  color: var(--hx-ano-cor);
}
.pasta-icone {
  color: var(--tm-gold);
  flex: none;
}
.pasta-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.25;
}
.pasta-texto strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pasta-texto small {
  color: var(--tm-ink-muted);
}
/* Alvo de soltura: fundo e contorno interno (sem mudar a borda, sem deslocar layout). */
.alvo-drop {
  box-shadow: inset 0 0 0 2px var(--tm-gold);
  background: color-mix(in srgb, var(--tm-gold) 12%, var(--tm-paper-2));
}
/* Pulso: anel que se expande e apaga em loop (transform/opacity). */
.alvo-drop::after {
  content: "";
  position: absolute;
  inset: -5px;
  border-radius: inherit;
  border: 2px solid var(--tm-gold);
  pointer-events: none;
  animation: alvo-pulso 1.1s var(--tm-ease) infinite;
}
@keyframes alvo-pulso {
  0% {
    transform: scale(0.97);
    opacity: 0.9;
  }
  70%,
  100% {
    transform: scale(1.04);
    opacity: 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .alvo-drop::after {
    animation: none;
    opacity: 0.6;
  }
}
html[data-reduzir="1"] .alvo-drop::after {
  animation: none;
  opacity: 0.6;
}
</style>
