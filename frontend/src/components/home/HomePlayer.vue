<template>
  <div
    ref="raiz"
    class="hp"
    :class="[`hp--${estado}`, { 'hp--controles': controlesVisiveis, 'hp--carimbo': carimbo, 'hp--fora': !visivel }]"
    :aria-busy="estado === 'buffer' ? 'true' : undefined"
    data-intro="player"
    @keydown="atalhos"
    @pointermove="mostrarControles"
    @pointerleave="agendarEsconder"
    @focusin="mostrarControles"
  >
    <video
      ref="videoRef"
      class="hp-video"
      :src="homeConfig.videoSrc"
      :poster="homeConfig.poster"
      preload="none"
      playsinline
      :aria-label="`Vídeo: ${homeConfig.titulo}`"
      @click="alternar"
      @play="aoPlay"
      @pause="aoPause"
      @playing="aoPlaying"
      @waiting="aoWaiting"
      @timeupdate="aoTempo"
      @progress="aoBuffer"
      @durationchange="aoDuracao"
      @volumechange="aoVolume"
      @ended="aoFim"
      @error="aoErro"
    />

    <!-- Poster (imagem real para detectar falha e poder trocar por fallback) -->
    <div v-if="!iniciado" class="hp-poster" aria-hidden="true">
      <img
        v-if="posterOk"
        :src="homeConfig.poster"
        :srcset="`${homeConfig.poster640} 640w, ${homeConfig.poster} 1280w`"
        sizes="(min-width: 1100px) 880px, 100vw"
        alt=""
        width="1280"
        height="720"
        fetchpriority="high"
        class="hp-poster-img"
        @error="posterOk = false"
      />
      <div v-else class="hp-poster-falta">
        <img :src="homeConfig.canal.avatar" alt="" width="96" height="96" />
      </div>
    </div>

    <!-- Retrô de dentro do vídeo: grão e vinheta com tokens --hx-* -->
    <div class="hp-grao" aria-hidden="true" />
    <div class="hp-vinheta" aria-hidden="true" />
    <span class="hp-carimbo" aria-hidden="true">{{ homeConfig.carimbo }}</span>

    <button
      v-if="estado !== 'fim' && estado !== 'erro'"
      type="button"
      class="hp-play"
      :class="{ 'hp-play--pequeno': iniciado }"
      :aria-label="tocando ? 'Pausar' : `Reproduzir: ${homeConfig.titulo}`"
      @click="alternar"
    >
      <span class="hp-play-anel" aria-hidden="true" />
      <span class="hp-play-circulo" aria-hidden="true">
        <v-icon :size="iniciado ? 28 : 44">{{ tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
      </span>
    </button>

    <div v-if="estado === 'buffer'" class="hp-spinner" aria-hidden="true" />
    <span v-if="estado === 'buffer'" class="sr-only" role="status">Carregando vídeo</span>

    <div v-if="estado === 'erro'" class="hp-aviso" role="alert">
      <p>Não conseguimos carregar o vídeo agora.</p>
      <button type="button" class="hp-btn" @click="tentarDeNovo">Tentar de novo</button>
    </div>

    <!-- Sequência de fim (CSS, ~2,6 s; pulada com prefers-reduced-motion). aria-hidden: o cartão é o que lê. -->
    <div v-if="estado === 'fim' && !direta" class="hp-cena" aria-hidden="true">
      <span class="hp-cena-flicker" />
      <span class="hp-cena-queima" />
      <span class="hp-cena-iris" />
      <span v-for="n in 3" :key="n" class="hp-fim-anel" :style="{ '--d': `${(n - 1) * 150}ms` }" />
      <span class="hp-cena-carimbo">{{ carimboFim }}</span>
    </div>

    <!-- Cartão final: nunca navega sozinho. Foco no título só se o foco já estiver no player ou em lugar nenhum. -->
    <div v-if="estado === 'fim'" class="hp-fim" role="group" aria-labelledby="hp-fim-t" data-hook="cartao-final">
      <p id="hp-fim-t" ref="tituloRef" class="hp-fim-titulo" tabindex="-1" aria-live="polite">
        <template v-for="(p, i) in palavrasTitulo" :key="i">
          <span class="hp-fim-palavra" :style="{ '--i': i }">{{ p }}</span>{{ " " }}
        </template>
      </p>
      <div class="hp-fim-acoes">
        <HomeCtas />
        <button type="button" class="hp-btn hp-btn--ghost" @click="rever">
          <v-icon size="18">mdi-replay</v-icon> Rever
        </button>
      </div>
    </div>

    <div class="hp-controles">
      <button type="button" class="hp-ic" :aria-label="tocando ? 'Pausar' : 'Reproduzir'" :aria-pressed="tocando" @click="alternar">
        <v-icon>{{ tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
      </button>
      <span class="hp-tempo" aria-hidden="true">{{ fmt(atual) }} / {{ duracao ? fmt(duracao) : homeConfig.duracaoRotulo }}</span>
      <div
        ref="trilhoRef"
        class="hp-trilho"
        role="slider"
        tabindex="0"
        aria-label="Progresso do vídeo"
        aria-valuemin="0"
        :aria-valuemax="Math.round(duracaoEf)"
        :aria-valuenow="Math.round(atual)"
        :aria-valuetext="`${fmt(atual)} de ${duracao ? fmt(duracao) : homeConfig.duracaoRotulo}`"
        @pointerdown="buscarPonteiro"
        @keydown="tecladoSlider"
      >
        <span class="hp-buffer" :style="{ width: `${pct(buffer)}%` }" />
        <span class="hp-prog" :style="{ width: `${pct(atual)}%` }" />
        <span class="hp-marca" :style="{ left: `${pct(atual)}%` }" />
      </div>
      <button type="button" class="hp-ic" :aria-label="mudo ? 'Ativar som' : 'Silenciar'" :aria-pressed="mudo" @click="alternarMudo">
        <v-icon>{{ mudo ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
      </button>
      <button type="button" class="hp-ic" aria-label="Tela cheia" :aria-pressed="cheia" @click="alternarCheia">
        <v-icon>{{ cheia ? "mdi-fullscreen-exit" : "mdi-fullscreen" }}</v-icon>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from "vue";
import { prefereMenosMovimento } from "@/composables/useTema";
import { REDE } from "@/config/esquina";
import { homeConfig } from "./home-config";
import HomeCtas from "./HomeCtas.vue";

// Estados (gancho de classe .hp--<estado>): parado | tocando | pausado | buffer | fim | erro
const estado = ref("parado");
const raiz = ref(null);
const videoRef = ref(null);
const trilhoRef = ref(null);
const tituloRef = ref(null);
const direta = ref(false);
const anoFim = ref(homeConfig.anoVideo);
const TITULO_FIM = "Isso foi só o começo.";
const palavrasTitulo = TITULO_FIM.split(" ");
const carimboFim = computed(() => homeConfig.carimbo.replace(/\d{4}$/, String(anoFim.value)));
let contador = null;
const iniciado = ref(false);
const posterOk = ref(true);
const atual = ref(0);
const duracao = ref(0);
const buffer = ref(0);
const mudo = ref(false);
const cheia = ref(false);
const carimbo = ref(true);
const controlesVisiveis = ref(true);
// Pulso do play só enquanto o player está visível na tela (IntersectionObserver).
const visivel = ref(true);
let io = null;
let timerControles = null;
let timerCarimbo = null;

// Carimbo avança de 2011 até REDE.anoFim em ~1,1 s (a fita "continua no tempo").
function pararContador() {
  clearInterval(contador);
  contador = null;
}
function contarAno() {
  pararContador();
  if (direta.value) {
    anoFim.value = REDE.anoFim;
    return;
  }
  anoFim.value = homeConfig.anoVideo;
  const passos = Math.max(1, REDE.anoFim - homeConfig.anoVideo);
  contador = setInterval(() => {
    anoFim.value += 1;
    if (anoFim.value >= REDE.anoFim) pararContador();
  }, Math.round(1100 / passos));
}

const tocando = computed(() => estado.value === "tocando" || estado.value === "buffer");
// Duração real quando o metadado chegar; antes disso usa a duração medida do arquivo (home-config).
const duracaoEf = computed(() => duracao.value || homeConfig.duracaoSegundos);
const pct = (v) => Math.min(100, Math.max(0, (v / duracaoEf.value) * 100));

function fmt(s) {
  const t = Math.max(0, Math.floor(s || 0));
  return `${Math.floor(t / 60)}:${String(t % 60).padStart(2, "0")}`;
}

function alternar() {
  const v = videoRef.value;
  if (!v) return;
  if (v.paused || v.ended) v.play().catch(() => (estado.value = "erro"));
  else v.pause();
}
function aoPlay() {
  pararContador();
  iniciado.value = true;
  estado.value = "buffer";
  clearTimeout(timerCarimbo);
  timerCarimbo = setTimeout(() => (carimbo.value = false), 3000);
  agendarEsconder();
}
function aoPlaying() {
  estado.value = "tocando";
  agendarEsconder();
}
function aoWaiting() {
  if (estado.value === "tocando") estado.value = "buffer";
}
function aoPause() {
  if (estado.value === "fim" || estado.value === "erro") return;
  estado.value = "pausado";
  controlesVisiveis.value = true;
}
function aoTempo() {
  atual.value = videoRef.value?.currentTime || 0;
}
function aoDuracao() {
  duracao.value = Number.isFinite(videoRef.value?.duration) ? videoRef.value.duration : 0;
}
function aoBuffer() {
  const v = videoRef.value;
  if (v?.buffered?.length) buffer.value = v.buffered.end(v.buffered.length - 1);
}
function aoVolume() {
  mudo.value = !!videoRef.value?.muted;
}
async function aoFim() {
  estado.value = "fim";
  controlesVisiveis.value = true;
  direta.value = prefereMenosMovimento();
  contarAno();
  await nextTick();
  const ativo = document.activeElement;
  const livre = !ativo || ativo === document.body || raiz.value?.contains(ativo);
  if (tituloRef.value && livre) tituloRef.value.focus({ preventScroll: true });
}
function aoErro() {
  // Com preload="none" e sem src carregado não há erro; só reage depois de uma tentativa de play.
  if (iniciado.value || videoRef.value?.error) estado.value = "erro";
}
function tentarDeNovo() {
  estado.value = "parado";
  videoRef.value?.load();
  alternar();
}
function rever() {
  const v = videoRef.value;
  if (!v) return;
  v.currentTime = 0;
  estado.value = "pausado";
  v.play().catch(() => (estado.value = "erro"));
}

function alternarMudo() {
  if (videoRef.value) videoRef.value.muted = !videoRef.value.muted;
}
async function alternarCheia() {
  const el = raiz.value;
  try {
    if (document.fullscreenElement) await document.exitFullscreen();
    else if (el?.requestFullscreen) await el.requestFullscreen();
    else videoRef.value?.webkitEnterFullscreen?.();
  } catch {
    /* fullscreen negado: ignora */
  }
}
function aoMudarCheia() {
  cheia.value = document.fullscreenElement === raiz.value;
}

function buscar(t) {
  const v = videoRef.value;
  if (!v) return;
  const max = duracao.value || 0;
  v.currentTime = Math.min(max || Infinity, Math.max(0, t));
  atual.value = v.currentTime;
}
function buscarPonteiro(e) {
  const r = trilhoRef.value.getBoundingClientRect();
  const frac = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
  buscar(frac * duracaoEf.value);
}
function tecladoSlider(e) {
  const passos = { ArrowRight: 5, ArrowUp: 5, ArrowLeft: -5, ArrowDown: -5, PageUp: 10, PageDown: -10 };
  if (e.key in passos) buscar(atual.value + passos[e.key]);
  else if (e.key === "Home") buscar(0);
  else if (e.key === "End") buscar(duracaoEf.value);
  else return;
  e.preventDefault();
  e.stopPropagation();
}

// Atalhos só com o foco dentro do player (nunca globais).
function atalhos(e) {
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  const k = e.key.toLowerCase();
  const emBotao = e.target.tagName === "BUTTON";
  if ((k === " " && !emBotao) || k === "k") {
    e.preventDefault();
    alternar();
  } else if (k === "m") alternarMudo();
  else if (k === "f") alternarCheia();
}

// Autoesconde 2,5 s após play com mouse parado; nunca com foco dentro nem com reduced-motion.
function mostrarControles() {
  controlesVisiveis.value = true;
  agendarEsconder();
}
function agendarEsconder() {
  clearTimeout(timerControles);
  if (estado.value !== "tocando" || prefereMenosMovimento()) return;
  timerControles = setTimeout(() => {
    if (!raiz.value?.contains(document.activeElement)) controlesVisiveis.value = false;
  }, 2500);
}

onMounted(() => {
  document.addEventListener("fullscreenchange", aoMudarCheia);
  if ("IntersectionObserver" in window && raiz.value) {
    io = new IntersectionObserver(([e]) => (visivel.value = e.isIntersecting), { threshold: 0.5 });
    io.observe(raiz.value);
  }
  // Sem play em 8 s o carimbo apaga sozinho.
  timerCarimbo = setTimeout(() => !iniciado.value && (carimbo.value = false), 8000);
});
onBeforeUnmount(() => {
  document.removeEventListener("fullscreenchange", aoMudarCheia);
  io?.disconnect();
  clearTimeout(timerControles);
  clearTimeout(timerCarimbo);
  pararContador();
  videoRef.value?.pause?.();
});
</script>

<style scoped>
.hp {
  position: relative;
  width: min(100%, calc((100dvh - 340px) * 16 / 9));
  min-width: min(100%, 480px);
  aspect-ratio: 16 / 9;
  background: #000;
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  box-shadow: var(--tm-shadow-lg);
  overflow: hidden;
  color: #f1e8d2;
}
.hp-video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #000;
  filter: var(--hx-filtro-foto);
  transition: opacity 400ms var(--tm-ease);
}
.hp--fim .hp-video {
  opacity: 0.35;
}
.hp-poster {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #5a4513, #1e1a12);
  filter: var(--hx-filtro-foto);
}
.hp-poster-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.hp-poster-falta {
  height: 100%;
  display: grid;
  place-items: center;
}
.hp-poster-falta img {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  border: 2px solid var(--tm-gold);
  object-fit: cover;
}
.hp-grao,
.hp-vinheta {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.hp-grao {
  background-image: var(--hx-grao);
  opacity: 0.07;
  mix-blend-mode: overlay;
}
.hp-vinheta {
  background: var(--hx-vinheta);
}
.hp-carimbo {
  position: absolute;
  right: 16px;
  bottom: 64px;
  font-weight: 600;
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.08em;
  color: #f2a33a;
  text-shadow: 0 1px 3px #000;
  opacity: 0;
  pointer-events: none;
  transition: opacity 250ms var(--tm-ease);
}
.hp--carimbo .hp-carimbo {
  opacity: 1;
}
.hp-play {
  position: absolute;
  inset: 0 0 64px;
  width: 100%;
  display: grid;
  place-items: center;
  background: transparent;
  border: 0;
  cursor: pointer;
  color: #2a2112;
}
.hp-play--pequeno {
  inset: 0 0 64px;
  opacity: 0;
}
.hp--pausado .hp-play--pequeno,
.hp:hover.hp--pausado .hp-play--pequeno {
  opacity: 1;
}
.hp-play--pequeno .hp-play-circulo {
  width: 56px;
  height: 56px;
}
.hp-play-circulo,
.hp-play-anel {
  grid-area: 1 / 1;
  border-radius: 50%;
}
.hp-play-circulo {
  width: 88px;
  height: 88px;
  display: grid;
  place-items: center;
  background: var(--tm-gold);
  box-shadow: 0 0 40px rgba(199, 164, 58, 0.35);
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.hp-play-circulo :deep(.v-icon) {
  color: #2a2112;
}
.hp-play:hover .hp-play-circulo {
  transform: scale(1.06);
}
.hp-play-anel {
  width: 88px;
  height: 88px;
  border: 1px solid rgba(199, 164, 58, 0.4);
}
.hp-play--pequeno .hp-play-anel {
  display: none;
}
/* Pulso do play: 2,4 s de expansão + 0,6 s de pausa (ciclo de 3 s), só parado e visível */
.hp--parado:not(.hp--fora) .hp-play-anel {
  animation: hp-pulso 3s ease-out infinite;
}
@keyframes hp-pulso {
  0% {
    transform: scale(1);
    opacity: 0.5;
  }
  80%,
  100% {
    transform: scale(1.25);
    opacity: 0;
  }
}
.hp-spinner {
  position: absolute;
  top: calc(50% - 16px);
  left: calc(50% - 16px);
  width: 32px;
  height: 32px;
  border: 3px solid rgba(199, 164, 58, 0.3);
  border-top-color: var(--tm-gold);
  border-radius: 50%;
  animation: hp-gira 900ms linear infinite;
  pointer-events: none;
}
@keyframes hp-gira {
  to {
    transform: rotate(360deg);
  }
}
.hp-controles {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 56px;
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  padding: 0 var(--tm-space-3);
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
  transition: opacity var(--tm-dur-base) var(--tm-ease);
}
.hp--parado .hp-controles {
  opacity: 0.7;
}
.hp:not(.hp--controles) .hp-controles {
  opacity: 0;
}
.hp-ic {
  flex: 0 0 44px;
  height: 44px;
  display: grid;
  place-items: center;
  background: none;
  border: 0;
  border-radius: 50%;
  color: #f1e8d2;
  cursor: pointer;
}
.hp-tempo {
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.hp-trilho {
  position: relative;
  flex: 1;
  height: 44px;
  cursor: pointer;
}
.hp-trilho::before {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: calc(50% - 2px);
  height: 4px;
  border-radius: 2px;
  background: rgba(241, 232, 210, 0.25);
  transition: height var(--tm-dur-fast);
}
.hp-buffer,
.hp-prog {
  position: absolute;
  left: 0;
  top: calc(50% - 2px);
  height: 4px;
  border-radius: 2px;
  pointer-events: none;
}
.hp-buffer {
  background: rgba(224, 190, 90, 0.3);
}
.hp-prog {
  background: var(--tm-gold);
}
.hp-marca {
  position: absolute;
  top: calc(50% - 7px);
  width: 14px;
  height: 14px;
  margin-left: -7px;
  border-radius: 50%;
  background: var(--tm-gold);
  pointer-events: none;
}
.hp-trilho:hover::before,
.hp-trilho:focus-visible::before,
.hp-trilho:hover .hp-buffer,
.hp-trilho:hover .hp-prog,
.hp-trilho:focus-visible .hp-buffer,
.hp-trilho:focus-visible .hp-prog {
  height: 8px;
  top: calc(50% - 4px);
}
.hp-aviso,
.hp-fim {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--tm-space-4);
  padding: var(--tm-space-4);
  text-align: center;
}
.hp-aviso {
  background: rgba(0, 0, 0, 0.8);
}
.hp-cena,
.hp-cena-flicker,
.hp-cena-queima,
.hp-cena-iris,
.hp-fim-anel,
.hp-cena-carimbo {
  position: absolute;
  pointer-events: none;
}
.hp-cena,
.hp-cena-flicker,
.hp-cena-queima,
.hp-cena-iris {
  inset: 0;
}
.hp-cena-flicker {
  background: #fff3d6;
  opacity: 0;
  animation: hp-cena-flicker 420ms linear both;
}
@keyframes hp-cena-flicker {
  0% { opacity: 0; }
  12% { opacity: 0.22; }
  24% { opacity: 0; }
  40% { opacity: 0.12; }
  52%, 100% { opacity: 0; }
}
.hp-cena-queima {
  background: radial-gradient(ellipse at center, rgba(242, 163, 58, 0.45), rgba(120, 60, 10, 0.15) 70%, transparent);
  mix-blend-mode: screen;
  opacity: 0;
  animation: hp-cena-queima 700ms ease-out both;
}
@keyframes hp-cena-queima {
  0% { opacity: 0; }
  25% { opacity: 0.9; }
  100% { opacity: 0; }
}
/* Vinheta fechando: a íris escurece as bordas até o cartão */
.hp-cena-iris {
  background: radial-gradient(circle at 50% 50%, transparent 30%, rgba(0, 0, 0, 0.9) 75%);
  opacity: 0;
  transform: scale(1.4);
  animation: hp-cena-iris 1100ms var(--tm-ease) 150ms both;
}
@keyframes hp-cena-iris {
  0% { opacity: 0; transform: scale(1.4); }
  100% { opacity: 1; transform: scale(1); }
}
/* Anéis dourados partem do centro do play (mesma linguagem da abertura) */
.hp-fim-anel {
  left: 50%;
  top: calc(50% - 32px);
  width: 64px;
  height: 64px;
  margin-left: -32px;
  border-radius: 50%;
  border: 1px solid rgba(199, 164, 58, 0.7);
  box-shadow: 0 0 24px rgba(199, 164, 58, 0.35);
  opacity: 0;
  animation: hp-fim-anel 1000ms var(--tm-ease) calc(180ms + var(--d)) both;
}
@keyframes hp-fim-anel {
  0% { opacity: 0.9; transform: scale(1); }
  100% { opacity: 0; transform: scale(5.5); }
}
.hp-cena-carimbo {
  right: 16px;
  bottom: 64px;
  font-weight: 600;
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.08em;
  color: #f2a33a;
  text-shadow: 0 1px 3px #000;
  animation: hp-cena-entra 300ms var(--tm-ease) 150ms both;
}
@keyframes hp-cena-entra {
  from { opacity: 0; }
}
.hp--fim .hp-grao {
  animation: hp-grao-tremula 500ms linear both;
}
@keyframes hp-grao-tremula {
  0% { opacity: 0.07; }
  20% { opacity: 0.2; }
  40% { opacity: 0.05; }
  60% { opacity: 0.16; }
  100% { opacity: 0.07; }
}
.hp-fim-palavra {
  display: inline-block;
  animation: hp-palavra 420ms var(--tm-ease) both;
  animation-delay: calc(800ms + var(--i) * 90ms);
}
@keyframes hp-palavra {
  from { opacity: 0; transform: translateY(0.5em); }
}
.hp-fim-titulo {
  margin: 0;
  font-family: var(--tm-font-title);
  font-size: var(--tm-fs-3xl);
  font-weight: 600;
}
.hp-fim-acoes {
  animation: hp-sobe 450ms var(--tm-ease) 1500ms both;
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-3);
  justify-content: center;
  align-items: center;
}
.hp-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  padding: 0 var(--tm-space-4);
  border: 1px solid var(--tm-gold);
  border-radius: var(--tm-radius-pill);
  background: transparent;
  color: #f1e8d2;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.hp-btn--ghost {
  border-color: transparent;
}
@keyframes hp-sobe {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
}
.hp-fim :deep(.cta) {
  /* dentro do vídeo os CTAs ficam em linha */
  width: auto;
}

@media (max-width: 599px) {
  .hp {
    width: 100%;
    min-width: 0;
    border-radius: 0;
    border-inline: 0;
  }
  .hp-play-circulo,
  .hp-play-anel {
    width: 72px;
    height: 72px;
  }
  .hp-fim-acoes {
    flex-direction: column;
    width: 100%;
  }
  .hp-tempo {
    display: none;
  }
}
@media (prefers-reduced-motion: reduce) {
  .hp-spinner {
    animation-duration: 2s;
  }
  .hp-play-anel {
    animation: none !important;
  }
  .hp-fim-palavra,
  .hp-fim-acoes,
  .hp--fim .hp-grao {
    animation: none;
  }
}
:global(html[data-reduzir="1"]) .hp-fim-palavra,
:global(html[data-reduzir="1"]) .hp-fim-acoes,
:global(html[data-reduzir="1"]) .hp--fim .hp-grao {
  animation: none;
}
</style>
