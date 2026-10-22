<template>
  <article
    class="video-card hx-ano"
    :class="{ selecionado, 'modo-selecao': modoSelecao, 'sem-poster': !poster }"
    :style="anoHue != null ? { '--hx-ano-h': anoHue } : null"
    :data-uuid="video.uuid"
    :draggable="podeArrastar"
    @dragstart="$emit('arrastar', $event, video)"
    @contextmenu.prevent="abrirMenuPonteiro"
    @keydown="aoTecla"
    @pointerdown="inicioToque"
    @pointerup="fimToque"
    @pointermove="moveuToque"
    @pointercancel="fimToque"
  >
    <button type="button" class="video-abrir" :aria-label="`Abrir vídeo ${video.titulo}`" @click="aoClicar">
      <span class="video-thumb">
        <img v-if="poster" :src="poster" alt="" loading="lazy" decoding="async" draggable="false" />
        <v-icon v-else size="48" class="video-thumb-icone" aria-hidden="true">mdi-play-circle-outline</v-icon>
        <span v-if="anoCurto" class="video-ano-selo" aria-hidden="true">'{{ anoCurto }}</span>
        <span class="video-play-central" aria-hidden="true"><v-icon size="26">mdi-play</v-icon></span>
        <span v-if="video.duracao_segundos" class="video-duracao">{{ formatarDuracao(video.duracao_segundos) }}</span>
        <!-- barra de controles falsa (decorativa); gancho motion 10: .video-prog-fill varre em hover -->
        <span class="video-barra-falsa" aria-hidden="true">
          <v-icon size="14">mdi-play</v-icon>
          <span class="video-prog"><span class="video-prog-fill"></span></span>
          <v-icon size="14" class="video-barra-extra">mdi-volume-high</v-icon>
          <v-icon size="14" class="video-barra-extra">mdi-fullscreen</v-icon>
        </span>
      </span>
      <span class="video-info" :title="video.tamanho_bytes ? formatarTamanho(video.tamanho_bytes) : undefined">
        <strong class="video-titulo">{{ video.titulo }}</strong>
        <small class="video-meta">
          <template v-if="video.cadastrado_por?.nome">Enviado por {{ video.cadastrado_por.nome }}<template v-if="quando"> · </template></template>
          <time v-if="quando" :datetime="dataRef" :title="dataCompleta(dataRef)">{{ video.data_video ? "" : "enviado " }}{{ quando }}</time>
        </small>
      </span>
    </button>
    <button
      type="button"
      class="video-check"
      role="checkbox"
      tabindex="-1"
      :aria-checked="selecionado"
      :aria-label="selecionado ? `Desmarcar ${video.titulo}` : `Selecionar ${video.titulo}`"
      @click.stop="$emit('check', video, $event)"
    >
      <v-icon size="18" aria-hidden="true">mdi-check</v-icon>
    </button>
    <v-btn
      icon
      size="small"
      variant="flat"
      color="surface"
      class="video-mais"
      aria-haspopup="menu"
      :aria-label="`Ações do vídeo ${video.titulo}`"
      @click.stop="abrirMenuBotao"
    >
      <v-icon>mdi-dots-vertical</v-icon>
    </v-btn>
  </article>
</template>

<script setup>
import { computed } from "vue";
import { formatarDuracao, tempoRelativo, dataCompleta, parseDataLocal } from "@/composables/useFormat";
import { hueDoAno } from "@/composables/useCapitulos";
import { formatarTamanho } from "@/composables/useArquivos";

const props = defineProps({
  video: { type: Object, required: true },
  selecionado: Boolean,
  modoSelecao: Boolean,
  podeArrastar: Boolean,
});
const emit = defineEmits(["abrir", "check", "menu", "arrastar"]);

const poster = computed(() => props.video.poster_url || props.video.thumbnail || null);
// Data de referência: data_video, senão o envio (rotulado "enviado").
const dataRef = computed(() => props.video.data_video || String(props.video.created_at ?? "").slice(0, 10) || "");
const quando = computed(() => (dataRef.value ? tempoRelativo(dataRef.value) : ""));
const anoVideo = computed(() => (props.video.data_video ? parseDataLocal(props.video.data_video)?.getFullYear() : null));
const anoCurto = computed(() => (anoVideo.value ? String(anoVideo.value).slice(-2) : ""));
const anoHue = computed(() => (anoVideo.value ? hueDoAno(anoVideo.value) : null));

function abrirMenuBotao(e) {
  const el = e.currentTarget;
  const r = el.getBoundingClientRect();
  emit("menu", props.video, { x: r.left, y: r.bottom }, el);
}
function abrirMenuPonteiro(e) {
  emit("menu", props.video, { x: e.clientX, y: e.clientY }, e.currentTarget.querySelector(".video-abrir"));
}
function aoTecla(e) {
  if (e.key === "ContextMenu" || (e.key === "F10" && e.shiftKey)) {
    e.preventDefault();
    const botao = e.currentTarget.querySelector(".video-abrir");
    const r = botao.getBoundingClientRect();
    emit("menu", props.video, { x: r.left + 24, y: r.top + 48 }, botao);
  } else if (e.key === " " && e.target.classList?.contains("video-abrir")) {
    e.preventDefault();
    emit("check", props.video, e);
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
  const alvo = e.currentTarget.querySelector(".video-abrir");
  timer = setTimeout(() => {
    longo = true;
    navigator.vibrate?.(10);
    emit("menu", props.video, { x, y }, alvo);
  }, 500);
}
function moveuToque(e) {
  if (origem && (Math.abs(e.clientX - origem.x) > 8 || Math.abs(e.clientY - origem.y) > 8)) fimToque();
}
function fimToque() {
  clearTimeout(timer);
  origem = null;
}
function aoClicar(e) {
  if (longo) {
    longo = false;
    return;
  }
  emit("abrir", props.video, e);
}
</script>

<style scoped>
.video-card {
  position: relative;
  border-radius: var(--tm-radius-md);
  background: var(--tm-paper);
  border: 1px solid var(--tm-border);
  overflow: hidden;
  color: var(--tm-ink);
  -webkit-touch-callout: none;
  /* Entrada escalonada (--i, limitado pela tela de vídeos): sobe e aparece. */
  animation: video-card-entra 280ms var(--tm-ease) backwards;
  animation-delay: calc(var(--i, 0) * 30ms);
}
@keyframes video-card-entra {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
}
.video-card[draggable="true"] {
  cursor: grab;
}
.video-card.selecionado {
  border-color: var(--tm-gold);
  box-shadow: 0 0 0 2px var(--tm-gold);
  background: color-mix(in srgb, var(--tm-gold) 10%, var(--tm-paper));
}
.video-abrir {
  display: block;
  width: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.video-thumb {
  position: relative;
  display: grid;
  place-items: center;
  aspect-ratio: 16 / 9;
  background: var(--fv-player-bg, #14110b);
  color: rgba(255, 255, 255, 0.85);
  border-radius: 8px 8px 0 0;
  overflow: hidden;
}
.video-card.sem-poster .video-thumb {
  background: var(--hx-ano-suave, var(--tm-paper-2));
  color: var(--tm-ink);
}
.video-thumb img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.video-duracao {
  position: absolute;
  right: 8px;
  bottom: 36px;
  padding: 2px 6px;
  border-radius: 3px;
  background: var(--fv-player-barra, rgba(0, 0, 0, 0.72));
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  line-height: 1.4;
  font-variant-numeric: tabular-nums;
}
.video-ano-selo {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 1;
  padding: 1px 7px;
  border-radius: var(--tm-radius-pill);
  background: var(--hx-ano-selo);
  color: #fff;
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: 12px;
  font-variant-numeric: tabular-nums;
}
/* Play central: só em hover/foco (desktop); sempre em placeholders */
.video-play-central {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  margin: -22px 0 0 -22px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  opacity: 0;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
  pointer-events: none;
}
.video-card:hover .video-play-central,
.video-card:focus-within .video-play-central,
.video-card.sem-poster .video-play-central {
  opacity: 1;
}
.video-card.sem-poster .video-thumb-icone {
  display: none;
}
@media (hover: none) {
  .video-play-central {
    opacity: 1;
  }
}
/* Barra falsa: 28px, trilho dourado em 0% (sem vídeo "visto" ainda) */
.video-barra-falsa {
  position: absolute;
  inset: auto 0 0 0;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 28px;
  padding: 0 8px;
  background: var(--fv-player-barra, rgba(0, 0, 0, 0.72));
  color: #fff;
}
.video-prog {
  flex: 1;
  height: 3px;
  border-radius: 2px;
  background: rgba(255, 255, 255, 0.28);
  overflow: hidden;
}
.video-prog-fill {
  display: block;
  width: 100%;
  height: 100%;
  background: var(--fv-player-prog, var(--tm-gold));
  transform: scaleX(0);
  transform-origin: left;
}
@media (max-width: 599px) {
  /* Mobile: só a linha de progresso (4px) */
  .video-barra-falsa {
    height: 4px;
    padding: 0;
    gap: 0;
    background: transparent;
  }
  .video-barra-falsa > .v-icon,
  .video-barra-extra {
    display: none;
  }
  .video-prog {
    height: 4px;
    border-radius: 0;
  }
  .video-duracao {
    bottom: 10px;
  }
}
.video-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 10px 12px 12px;
}
.video-titulo {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.3;
  font-weight: 600;
  font-size: 15px;
}
.video-meta {
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.video-check {
  position: absolute;
  top: 0;
  left: 0;
  width: 44px;
  height: 44px;
  padding: 0;
  border: 0;
  background: transparent;
  display: grid;
  place-items: center;
  opacity: 0;
  cursor: pointer;
  z-index: 2;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.video-check::before {
  content: "";
  grid-area: 1 / 1;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid #fff;
  background: rgba(0, 0, 0, 0.35);
}
.video-check :deep(.v-icon) {
  grid-area: 1 / 1;
  color: transparent;
}
.video-card:hover .video-check,
.video-card:focus-within .video-check,
.video-card.modo-selecao .video-check {
  opacity: 1;
}
.video-card.selecionado .video-check::before {
  background: var(--tm-gold);
  border-color: var(--tm-gold);
}
.video-card.selecionado .video-check :deep(.v-icon) {
  color: #2a2112;
}
.video-mais {
  position: absolute;
  top: 6px;
  right: 6px;
  z-index: 2;
  opacity: 0;
  transition: opacity var(--tm-dur-fast) var(--tm-ease);
}
.video-card:hover .video-mais,
.video-card:focus-within .video-mais {
  opacity: 1;
}
@media (hover: none) {
  .video-check,
  .video-mais {
    opacity: 1;
  }
}
@media (prefers-reduced-motion: reduce) {
  .video-check,
  .video-mais,
  .video-play-central {
    transition: none;
  }
  .video-card {
    animation: none;
  }
}
html[data-reduzir="1"] .video-card {
  animation: none;
}
</style>
