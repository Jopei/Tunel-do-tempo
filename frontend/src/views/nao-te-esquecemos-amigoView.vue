<template>
  <section class="homenagem-page tm-immersive">
    <div ref="particulasRef" id="particles-js" aria-hidden="true">
      <canvas ref="canvasRef" class="particulas-canvas"></canvas>
    </div>

    <div class="conteudo">
      <h1 class="sr-only">Italo, aquele que sonhou</h1>

      <blockquote class="frase">
        <p v-for="(l, i) in linhas" :key="l.letra" class="linha reveal" :style="{ '--delay': `${i * 0.35}s` }">
          <span class="sr-only">{{ l.letra }}{{ l.resto }}</span>
          <span aria-hidden="true" class="visual">
            <span class="letra">{{ l.letra }}</span>
            <span class="texto">{{ l.resto }}</span>
          </span>
        </p>
      </blockquote>
    </div>

    <v-btn to="/a-rua" variant="flat" color="surface" prepend-icon="mdi-arrow-left" class="voltar">
      Voltar à rua
    </v-btn>

    <button
      type="button"
      class="audio-control"
      :aria-pressed="tocando"
      :aria-label="tocando ? 'Pausar música' : 'Tocar música'"
      @click="alternarAudio"
    >
      <v-icon aria-hidden="true">{{ tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
      <span class="rotulo">{{ tocando ? "Pausar música" : "Tocar música" }}</span>
    </button>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { usePlayerStore } from "@/stores/player";
import { finalizarTransicao } from "@/composables/useTransicao";
import { iniciarParticulas } from "@/composables/useParticulas";

const linhas = [
  { letra: "I", resto: "niciou sua jornada," },
  { letra: "T", resto: "eve amigos," },
  { letra: "A", resto: "creditou nos seus sonhos," },
  { letra: "L", resto: "utou durante sua vida," },
  { letra: "O", resto: "chamaram: aquele que sonhou." },
];

const tocando = ref(false);
const particulasRef = ref(null);
const canvasRef = ref(null);
let audio = null;
let pararParticulas = () => {};

// Sem autoplay: a música só começa por um clique explícito.
function alternarAudio() {
  if (!audio) {
    audio = new Audio();
    audio.preload = "none";
    audio.src = "/sons/italoSong.mp3";
    audio.loop = true;
    audio.volume = 0.3;
  }
  if (tocando.value) {
    audio.pause();
    tocando.value = false;
    return;
  }
  usePlayerStore().pausarPorMidia(); // um som por vez: pausa a música global
  audio
    .play()
    .then(() => (tocando.value = true))
    .catch(() => (tocando.value = false));
}

// um som por vez: o player global pausa esta trilha ao começar a tocar
function pausarLocal() {
  if (!audio) return;
  audio.pause();
  tocando.value = false;
}
const desregistrar = usePlayerStore().registrarMidiaLocal(pausarLocal);

onMounted(() => {
  finalizarTransicao();
  pararParticulas = iniciarParticulas(particulasRef.value, canvasRef.value);
});

onBeforeUnmount(() => {
  desregistrar();
  const a = audio;
  audio = null;
  if (a) {
    a.pause();
    a.src = "";
  }
  pararParticulas();
});
</script>

<style scoped>
.homenagem-page {
  min-height: 100dvh;
  background: url("/backgrounds/italo-bg.svg") center/cover no-repeat;
  position: relative;
  overflow: hidden;
}

#particles-js {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}

.particulas-canvas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: block;
}

.conteudo {
  position: relative;
  z-index: 2;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: var(--tm-space-6);
  padding: calc(var(--tm-navbar-h) + 72px) var(--tm-space-4) 120px;
}

.frase {
  max-width: 560px;
  margin: 0;
}

.linha {
  font-size: clamp(1.375rem, 4vw, 2rem);
  font-weight: 800;
  margin: 0 0 14px;
}
.visual {
  display: flex;
  align-items: baseline;
}

.letra {
  color: #a93226;
  font-size: 1.25em;
  font-weight: 900;
  margin-right: 6px;
  font-family: var(--tm-font-title);
}

.texto {
  color: #000;
  font-weight: 800;
}

.voltar {
  position: fixed;
  top: calc(var(--tm-navbar-h) + 16px);
  left: 16px;
  z-index: 1100;
  color: rgb(var(--v-theme-on-surface)) !important;
}

.audio-control {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1100;
  min-height: 48px;
  padding: 0 20px 0 14px;
  border-radius: var(--tm-radius-pill);
  background: rgba(0, 0, 0, 0.82);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background var(--tm-dur-fast) var(--tm-ease);
}
.audio-control:hover {
  background: #000;
}

@media (max-width: 600px) {
  .audio-control {
    bottom: 16px;
    right: 16px;
  }
  .voltar {
    top: calc(var(--tm-navbar-h) + 12px);
    left: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .audio-control {
    transition: none;
  }
}
</style>
