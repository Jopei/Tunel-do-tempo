<template>
  <section class="homenagem-page tm-immersive">
    <div ref="particulasRef" id="particles-js" aria-hidden="true"></div>

    <div class="conteudo">
      <h1 class="sr-only">Aquele que sonhou</h1>

      <blockquote class="frase">
        <p v-for="(l, i) in linhas" :key="l.letra" class="linha reveal" :style="{ '--delay': `${i * 0.35}s` }">
          <span class="sr-only">{{ l.letra }}{{ l.resto }}</span>
          <span aria-hidden="true" class="visual">
            <span class="letra">{{ l.letra }}</span>
            <span class="texto">{{ l.resto }}</span>
          </span>
        </p>
      </blockquote>

      <div class="lembrancas-card">
        <LembrancasSecao v-if="auth.logado" />
        <p v-else class="entre">
          <router-link :to="{ name: 'login', query: { redirect: '/aquele-que-sonhou' } }">Entre</router-link>
          para deixar uma lembrança.
        </p>
      </div>

      <v-btn to="/a-rua" variant="flat" color="surface" prepend-icon="mdi-arrow-left" class="voltar">
        Voltar à rua
      </v-btn>
    </div>

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
import { useAuthStore } from "@/stores/auth";
import LembrancasSecao from "@/components/ui/LembrancasSecao.vue";
import { finalizarTransicao } from "@/composables/useTransicao";
import { prefereMenosMovimento } from "@/composables/useTema";

const linhas = [
  { letra: "I", resto: "niciou sua jornada," },
  { letra: "T", resto: "eve amigos," },
  { letra: "A", resto: "creditou nos seus sonhos," },
  { letra: "L", resto: "utou durante sua vida," },
  { letra: "O", resto: "chamaram: aquele que sonhou." },
];

const auth = useAuthStore();
const tocando = ref(false);
const particulasRef = ref(null);
let audio = null;
let particulasAtivas = false;

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
  audio
    .play()
    .then(() => (tocando.value = true))
    .catch(() => (tocando.value = false));
}

function aoMudarVisibilidade() {
  if (document.visibilityState === "hidden" && tocando.value) {
    audio?.pause();
    tocando.value = false;
  }
}

async function iniciarParticulas() {
  if (prefereMenosMovimento()) return;
  await import("particles.js");
  if (!window.particlesJS || !particulasRef.value) return;
  const pequena = window.matchMedia("(max-width: 768px)").matches;
  window.particlesJS("particles-js", {
    particles: {
      number: { value: pequena ? 30 : 50 },
      color: { value: "#000000" },
      shape: { type: "circle" },
      opacity: { value: 1, random: true },
      size: { value: 5, random: true },
      line_linked: { enable: false },
      move: { enable: true, speed: 0.6, random: true, out_mode: "out" },
    },
    interactivity: { events: { onhover: { enable: false }, onclick: { enable: false } } },
    retina_detect: true,
  });
  particulasAtivas = true;
}

onMounted(() => {
  finalizarTransicao();
  document.addEventListener("visibilitychange", aoMudarVisibilidade);
  iniciarParticulas();
});

onBeforeUnmount(() => {
  document.removeEventListener("visibilitychange", aoMudarVisibilidade);
  if (audio) {
    audio.pause();
    audio.src = "";
    audio = null;
  }
  if (particulasAtivas && Array.isArray(window.pJSDom) && window.pJSDom.length) {
    try {
      window.pJSDom[0].pJS.fn.vendors.destroypJS();
    } catch {
      /* já destruído */
    }
    window.pJSDom = [];
  }
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
  padding: calc(var(--tm-navbar-h) + 48px) var(--tm-space-4) 120px;
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

.lembrancas-card {
  width: 100%;
  max-width: 560px;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-5);
}
.lembrancas-card :deep(.lembrancas) {
  margin-top: 0;
  padding-top: 0;
  border-top: 0;
}
.entre {
  margin: 0;
  text-align: center;
}
.entre a {
  color: var(--tm-gold-text);
  font-weight: 700;
}
.voltar {
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
}
</style>
