<template>
  <section class="homenagem-page">
    <!-- PARTÍCULAS -->
    <div id="particles-js"></div>

    <SideMenu v-if="authStore.logado" />

    <!-- CONTEÚDO -->
    <div class="conteudo">
      <div class="bloco-texto">
        <div class="frase">
          <p class="linha">
            <span class="letra">I</span>
            <span class="texto">niciou sua jornada,</span>
          </p>

          <p class="linha">
            <span class="letra">T</span>
            <span class="texto">eve amigos,</span>
          </p>

          <p class="linha">
            <span class="letra">A</span>
            <span class="texto">creditou nos seus sonhos,</span>
          </p>

          <p class="linha">
            <span class="letra">L</span>
            <span class="texto">utou durante sua vida,</span>
          </p>

          <p class="linha">
            <span class="letra">O</span>
            <span class="texto">chamaram: aquele que sonhou.</span>
          </p>
        </div>
      </div>
    </div>

    <!-- BOTÃO PLAY / PAUSE -->
    <button class="audio-control" @click="toggleAudio">
      <span v-if="!tocando">▶</span>
      <span v-else>⏸</span>
    </button>
  </section>
</template>
<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import SideMenu from "@/components/layout/SideMenu.vue";
import { useAuthStore } from "@/stores/auth";
import gsap from "gsap";

const authStore = useAuthStore();
const tocando = ref(false);
let audio = null;

/* 🔊 CONTROLE DE ÁUDIO */
function toggleAudio() {
  if (!audio) return;

  if (tocando.value) {
    audio.pause();
    tocando.value = false;
  } else {
    audio.play()
      .then(() => {
        tocando.value = true;
      })
      .catch(() => {
        console.warn("Autoplay bloqueado pelo navegador");
      });
  }
}

/* 🌌 CARREGA PARTICLES.JS */
function carregarParticles() {
  return new Promise((resolve) => {
    if (window.particlesJS) {
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js";
    script.onload = resolve;
    document.body.appendChild(script);
  });
}

onMounted(async () => {
  const overlay = document.getElementById("transition-overlay");

  if (overlay) {
    gsap.to(overlay, {
      opacity: 0,
      duration: 1.2,
      ease: "power2.out",
      onComplete: () => {
        overlay.style.display = "none";
        overlay.style.opacity = 1;
      },
    });
  }

  /* áudio */
  audio = new Audio("/sons/italoSong.mp3");
  audio.loop = true;
  audio.volume = 0.3;

  audio.play()
    .then(() => {
      tocando.value = true;
    })
    .catch(() => {
      tocando.value = false;
    });

  /* particles */
  await carregarParticles();

  window.particlesJS("particles-js", {
    particles: {
      number: { value: 50 },
      color: { value: "#000000" },
      shape: { type: "circle" },
      opacity: { value: 1, random: true },
      size: { value: 5, random: true },
      line_linked: { enable: false },
      move: {
        enable: true,
        speed: 0.6,
        random: true,
        out_mode: "out",
      },
    },
    interactivity: {
      events: {
        onhover: { enable: false },
        onclick: { enable: false },
      },
    },
    retina_detect: true,
  });

  /* animação de texto */
  const linhas = document.querySelectorAll(".linha");
  linhas.forEach((linha, index) => {
    setTimeout(() => {
      linha.classList.add("ativa");
    }, index * 350);
  });
});

onBeforeUnmount(() => {
  if (audio) {
    audio.pause();
    audio.currentTime = 0;
    audio = null;
  }
});
</script>
<style scoped>
.homenagem-page {
  min-height: 100vh;
  background: url("/backgrounds/italo-bg.svg") center/cover no-repeat;
  position: relative;
  overflow: hidden;
  animation: pageIn 1.2s ease-out;
}

#particles-js {
  position: absolute;
  inset: 0;
  z-index: 1;
}

.conteudo {
  position: relative;
  z-index: 2;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 140px;
}

.bloco-texto {
  text-align: left;
}

.frase {
  max-width: 560px;
}

.linha {
  display: flex;
  align-items: baseline;
  font-size: 32px;
  font-weight: 800;
  margin-bottom: 14px;
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 10s ease, transform 0.8s ease;
}

.linha.ativa {
  opacity: 1;
  transform: translateY(0);
}

.letra {
  color: #c0392b;
  font-size: 40px;
  font-weight: 900;
  margin-right: 6px;
}

.texto {
  color: #000;
  font-weight: 800;
}

.audio-control {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9999;
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: rgba(0,0,0,.75);
  color: #fff;
  border: none;
  font-size: 22px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform .2s ease, background .2s ease;
}

.audio-control:hover {
  transform: scale(1.1);
  background: rgba(0,0,0,.9);
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
  .conteudo {
    padding-top: 100px;
    padding-left: 16px;
    padding-right: 16px;
  }

  .frase {
    max-width: 100%;
  }

  .linha {
    font-size: 22px;
  }

  .letra {
    font-size: 28px;
  }

  .audio-control {
    bottom: 18px;
    right: 18px;
    width: 48px;
    height: 48px;
    font-size: 20px;
  }
}

@keyframes pageIn {
  from {
    opacity: 0;
    transform: scale(1.02);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
