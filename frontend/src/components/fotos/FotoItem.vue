<template>
  <div
    class="foto-item"
    :class="{ selecionada, 'modo-selecao': modoSelecao, carregada, novo }"
    role="gridcell"
    :data-uuid="foto.uuid"
    :style="{ width: `${w}px`, height: `${h}px`, '--atraso': `${atraso}ms` }"
  >
    <button
      type="button"
      class="foto-abrir"
      :tabindex="foco ? 0 : -1"
      :aria-label="rotulo"
      :aria-selected="selecionada"
      @click="aoClicar"
      @keydown.space.prevent="$emit('marcar', foto, $event)"
      @pointerdown="inicioToque"
      @pointermove="moveuToque"
      @pointerup="fimToque"
      @pointercancel="fimToque"
      @pointerleave="fimToque"
      @contextmenu="aoMenu"
    >
      <span class="foto-molde">
        <img
          class="foto-img"
          :src="src"
          alt=""
          loading="lazy"
          decoding="async"
          draggable="false"
          :width="w"
          :height="h"
          @load="carregada = true"
          @error="carregada = true"
        />
      </span>
    </button>
    <button
      type="button"
      class="foto-check"
      role="checkbox"
      :aria-checked="selecionada"
      :aria-label="selecionada ? `Desmarcar ${rotulo}` : `Selecionar ${rotulo}`"
      tabindex="-1"
      @click.stop="$emit('check', foto, $event)"
    >
      <v-icon size="18" class="foto-check-icone" aria-hidden="true">mdi-check</v-icon>
    </button>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { urlMiniatura, rotuloDia, diaDaFoto } from "@/composables/useFotoDatas";

const props = defineProps({
  foto: { type: Object, required: true },
  w: { type: Number, required: true },
  h: { type: Number, required: true },
  selecionada: Boolean,
  modoSelecao: Boolean,
  foco: Boolean,
  /** Item acabou de entrar na grade: toca a entrada escalonada (só CSS). */
  novo: Boolean,
  atraso: { type: Number, default: 0 },
});
const emit = defineEmits(["abrir", "marcar", "check", "longo"]);

const carregada = ref(false);
const src = computed(() => urlMiniatura(props.foto));
const rotulo = computed(() => `Foto ${props.foto.titulo || "sem título"}, ${rotuloDia(diaDaFoto(props.foto))}`);

// Long-press (400 ms) para entrar na seleção em telas de toque.
let timer = null;
let origem = null;
let longoDisparado = false;

function inicioToque(e) {
  if (e.pointerType === "mouse") return;
  longoDisparado = false;
  origem = { x: e.clientX, y: e.clientY };
  clearTimeout(timer);
  timer = setTimeout(() => {
    longoDisparado = true;
    navigator.vibrate?.(10);
    emit("longo", props.foto);
  }, 400);
}
function moveuToque(e) {
  if (!origem) return;
  if (Math.abs(e.clientX - origem.x) > 8 || Math.abs(e.clientY - origem.y) > 8) fimToque();
}
function fimToque() {
  clearTimeout(timer);
  origem = null;
}
function aoMenu(e) {
  // Evita o menu nativo de imagem ao segurar o dedo.
  if (longoDisparado || timer) e.preventDefault();
}
function aoClicar(e) {
  if (longoDisparado) {
    longoDisparado = false;
    return;
  }
  emit("abrir", props.foto, e);
}
</script>

<style scoped>
.foto-item {
  position: relative;
  flex: none;
  background: var(--tm-paper-2);
  border-radius: 4px;
  overflow: hidden;
  -webkit-touch-callout: none;
  user-select: none;
  transition: background-color var(--tm-dur-fast) var(--tm-ease);
}
.foto-abrir {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  display: block;
  touch-action: manipulation;
}
.foto-abrir:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: -3px;
}
/* Encolhe 100 -> 88% (só transform) ao selecionar. */
.foto-molde {
  position: absolute;
  inset: 0;
  display: block;
  overflow: hidden;
  border-radius: 4px;
  transform-origin: 50% 50%;
  transition: transform 150ms var(--tm-ease);
}
.foto-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0;
  /* Blur-up curto da miniatura ao carregar. */
  filter: blur(10px);
  transform: scale(1.04);
  transition:
    opacity var(--tm-dur-base) var(--tm-ease),
    transform var(--tm-dur-base) var(--tm-ease),
    filter var(--tm-dur-base) var(--tm-ease);
}
.carregada .foto-img {
  opacity: 1;
  filter: none;
  transform: none;
}
/* Filtro de foto antiga (toggle; ver fotos-videos-era.css). Só sépia na grade: sem vinheta. */
:global(.fv-filtro-on) .carregada .foto-img {
  filter: var(--hx-filtro-foto);
}
/* Placeholder com brilho que passa (translate, sem animar background-position). */
.foto-item:not(.carregada)::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(100deg, transparent 30%, var(--tm-border) 50%, transparent 70%);
  transform: translateX(-100%);
  animation: foto-brilho 1.4s linear infinite;
  pointer-events: none;
}
@keyframes foto-brilho {
  to {
    transform: translateX(100%);
  }
}
/* Entrada de itens novos: opacity + translate, escalonada pelo --atraso (limitado no componente). */
.foto-item.novo {
  animation: foto-entra var(--tm-dur-base) var(--tm-ease) backwards;
  animation-delay: var(--atraso, 0ms);
}
@keyframes foto-entra {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
}
.foto-item::after {
  content: "";
  position: absolute;
  inset: 0;
  z-index: 2;
  background: rgba(0, 0, 0, 0.12);
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.foto-item:hover::after,
.foto-item:focus-within::after {
  opacity: 1;
}
.foto-item.selecionada::after {
  opacity: 0;
}
.foto-item.selecionada {
  background: var(--tm-paper-2);
}
.foto-item.selecionada .foto-molde {
  transform: scale(0.88);
}
.foto-check {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 3;
  width: 44px;
  height: 44px;
  padding: 0;
  border: 0;
  background: transparent;
  display: grid;
  place-items: center;
  opacity: 0;
  cursor: pointer;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.foto-check::before {
  content: "";
  grid-area: 1 / 1;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #fff;
  background: rgba(0, 0, 0, 0.18);
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.25);
}
.foto-check-icone {
  grid-area: 1 / 1;
  color: transparent;
}
.foto-item:hover .foto-check,
.foto-item:focus-within .foto-check,
.foto-item.modo-selecao .foto-check,
.foto-check:focus-visible {
  opacity: 1;
}
.foto-item.selecionada .foto-check::before {
  background: var(--tm-gold);
  border-color: var(--tm-gold);
}
.foto-item.selecionada .foto-check-icone {
  color: #2a2112;
}
.foto-check:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: -3px;
}
@media (hover: none) {
  /* Em telas de toque o check só aparece em modo seleção (long-press). */
  .foto-item:hover .foto-check {
    opacity: 0;
  }
  .foto-item.modo-selecao:hover .foto-check {
    opacity: 1;
  }
}
@media (prefers-reduced-motion: reduce) {
  .foto-item:not(.carregada)::before,
  .foto-item.novo {
    animation: none;
  }
  .foto-molde,
  .foto-img,
  .foto-check,
  .foto-item::after {
    transition: none;
  }
}
:global([data-reduzir="1"]) .foto-item:not(.carregada)::before,
:global([data-reduzir="1"]) .foto-item.novo {
  animation: none;
}
html[data-reduzir="1"] .foto-img {
  filter: none;
  transform: none;
}

/* Seleção: o check "pula" ao ser marcado (transform/opacity). */
.foto-item.selecionada .foto-check-icone {
  animation: foto-check-pop 200ms var(--tm-ease);
}
@keyframes foto-check-pop {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
  }
}
@media (prefers-reduced-motion: reduce) {
  .foto-item.selecionada .foto-check-icone {
    animation: none;
  }
}
html[data-reduzir="1"] .foto-item.selecionada .foto-check-icone {
  animation: none;
}
</style>
