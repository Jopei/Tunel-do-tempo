<template>
  <div class="orbita" :class="{ pausada }">
    <button ref="centralRef" type="button" class="central" aria-label="Abrir: Aquele que sonhou" @click="irCentro">
      <img src="/imgs/1.svg" alt="" width="240" height="240" />
    </button>

    <div v-for="(item, i) in itens" :key="item.rotulo" class="anel" :class="`a${i + 1}`" aria-hidden="false">
      <div class="avatar-wrapper">
        <button
          type="button"
          class="avatar-btn"
          :aria-label="`Abrir: ${item.rotulo} (em breve)`"
          @click="irItem($event, item)"
        >
          <img :src="item.img" alt="" class="avatar" width="56" height="56" loading="lazy" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { transicionarPara } from "@/composables/useTransicao";

const router = useRouter();
const centralRef = ref(null);
const pausada = ref(false);

// Destinos ainda não definidos pelo dono do produto: levam à página "Em breve".
const itens = [
  { rotulo: "Primeira parada", img: "/login/7.jpg", to: "/em-breve" },
  { rotulo: "Segunda parada", img: "/login/3.jpg", to: "/em-breve" },
  { rotulo: "Terceira parada", img: "/login/4.jpg", to: "/em-breve" },
];

function irCentro() {
  transicionarPara(router, "/aquele-que-sonhou", centralRef.value?.querySelector("img"), { posicao: "baixo" });
}

function irItem(event, item) {
  transicionarPara(router, item.to, event.currentTarget.querySelector("img"));
}

// Economiza bateria: pausa a órbita quando a aba está oculta.
function aoMudarVisibilidade() {
  pausada.value = document.visibilityState === "hidden";
}
onMounted(() => document.addEventListener("visibilitychange", aoMudarVisibilidade));
onBeforeUnmount(() => document.removeEventListener("visibilitychange", aoMudarVisibilidade));
</script>

<style scoped>
.orbita {
  position: relative;
  width: 620px;
  height: 620px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.central {
  width: 260px;
  height: 260px;
  border-radius: 50%;
  border: 0;
  padding: 0;
  background: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  cursor: pointer;
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.central:hover {
  transform: scale(1.04);
  box-shadow: 0 0 0 6px rgba(199, 164, 58, 0.35);
}
.central img {
  width: 92%;
  height: 92%;
  border-radius: 50%;
}

.anel {
  position: absolute;
  border: 2px solid var(--tm-border);
  border-radius: 50%;
  animation: girar linear infinite;
  pointer-events: none;
}
.pausada .anel {
  animation-play-state: paused;
}
.a1 {
  width: 360px;
  height: 360px;
  animation-duration: 20s;
}
.a2 {
  width: 460px;
  height: 460px;
  animation-duration: 30s;
}
.a3 {
  width: 560px;
  height: 560px;
  animation-duration: 40s;
}

.avatar-wrapper {
  position: absolute;
  top: -28px;
  left: 50%;
  transform: translateX(-50%);
  pointer-events: auto;
  z-index: 20;
}

.avatar-btn {
  display: block;
  min-width: 48px;
  min-height: 48px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.avatar-btn:hover,
.avatar-btn:focus-visible {
  transform: scale(1.15);
  box-shadow: 0 0 0 4px rgba(199, 164, 58, 0.5);
}
.avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

@keyframes girar {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 600px) {
  .orbita {
    width: 340px;
    height: 340px;
  }
  .central {
    width: 160px;
    height: 160px;
  }
  .a1 {
    width: 220px;
    height: 220px;
  }
  .a2 {
    width: 280px;
    height: 280px;
  }
  .a3 {
    width: 330px;
    height: 330px;
  }
  .avatar {
    width: 48px;
    height: 48px;
  }
  .avatar-wrapper {
    top: -24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .anel {
    animation: none;
  }
}
</style>
