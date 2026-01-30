<template>
  <div class="orbita">
    <!-- CENTRAL -->
    <div class="central" @click="onCentralClick" ref="centralRef">
      <img src="/imgs/1.svg" />
    </div>

    <!-- ANEL 1 -->
    <div class="anel a1">
      <div class="avatar-wrapper">
        <img
          src="/login/7.jpg"
          class="avatar"
          @click.stop="onA1Click"
        />
      </div>
    </div>

    <!-- ANEL 2 -->
    <div class="anel a2">
      <div class="avatar-wrapper">
        <img
          src="/login/3.jpg"
          class="avatar"
          @click.stop="onA2Click"
        />
      </div>
    </div>

    <!-- ANEL 3 -->
    <div class="anel a3">
      <div class="avatar-wrapper">
        <img
          src="/login/4.jpg"
          class="avatar"
          @click.stop="onA3Click"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import gsap from "gsap";
import { useRouter } from "vue-router";

const router = useRouter();
const centralRef = ref(null);
let audio = null;
const tocando = ref(false);

function onCentralClick() {
  const centralEl = centralRef.value;
  const img = centralEl.querySelector("img");

  const rect = img.getBoundingClientRect();

  const overlay = document.getElementById("transition-overlay");
  const overlayImg = document.getElementById("transition-image");

  overlayImg.src = img.src;

  gsap.set(overlayImg, {
    position: "fixed",
    top: rect.top,
    left: rect.left,
    width: rect.width,
    height: rect.height,
  });

  overlay.style.display = "flex";

  const tl = gsap.timeline({
    onComplete: () => {
      router.push("/aquele-que-sonhou");
    },
  });

  tl.to(overlay, {
    backgroundColor: "#000",
    duration: 0.4,
    ease: "power1.out",
  });

  tl.to(overlayImg, {
    top: "80%",
    left: "50%",
    xPercent: -50,
    yPercent: -50,
    width: 180,
    scale: 2.0,
    height: 180,
    duration: 1.3,
    ease: "power1.inOut",
  }, "<");

}

function onA1Click(event) {
  transicionarPara("/rota-1", event.currentTarget);
}

function onA2Click(event) {
  transicionarPara("/rota-2", event.currentTarget);
}

function onA3Click(event) {
  transicionarPara("/rota-3", event.currentTarget);
}

function transicionarPara(url, imgEl) {
  const overlay = document.getElementById("transition-overlay");
  const overlayImg = document.getElementById("transition-image");

  if (!overlay || !overlayImg) {
    router.push(url);
    return;
  }

  const rect = imgEl.getBoundingClientRect();

  overlayImg.src = imgEl.src;

  gsap.set(overlayImg, {
    position: "fixed",
    top: rect.top,
    left: rect.left,
    width: rect.width,
    height: rect.height,
  });

  overlay.style.display = "flex";

  gsap.timeline({
    onComplete: () => router.push(url),
  })
  .to(overlay, {
    backgroundColor: "#000",
    duration: 0.4,
    ease: "power2.out",
  })
  .to(
    overlayImg,
    {
      top: "50%",
      left: "50%",
      xPercent: -50,
      yPercent: -50,
      scale: 2.4,
      duration: 1,
      ease: "power3.inOut",
    },
    "<"
  );
}
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
  background: #222;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  cursor: pointer;
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

/* ===== ANÉIS ===== */
.anel {
  position: absolute;
  border: 2px solid rgba(0, 0, 0, 0.25);
  border-radius: 50%;
  animation: girar linear infinite;
  pointer-events: none;
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

/* AVATAR */
.avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  cursor: pointer;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.avatar:hover {
  transform: scale(1.15);
  box-shadow: 0 0 0 4px rgba(199, 164, 58, 0.5);
}

/* ===== ANIMAÇÃO ===== */
@keyframes girar {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
