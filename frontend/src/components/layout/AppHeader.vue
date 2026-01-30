<template>
  <section class="app-header">
    <nav class="nav">
      <div class="nav-items">
        <span class="nav-item">Videos</span>
        <span class="nav-item" @click="irParaHistorias">
          Historias
        </span>
        <span class="nav-item" @click="irParaRua">
          A Rua
        </span>
        <span class="nav-item">Fotos</span>
      </div>

      <button v-if="!authStore.logado" class="login-btn" @click="irParaLogin">
        Login
      </button>
    </nav>

    <img src="/decorations/dots.svg" class="dots dots-1" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-2" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-3" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-4" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-5" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-6" alt="" />
    <img src="/decorations/dots.svg" class="dots dots-7" alt="" />

    <div class="video-area">
      <slot />
    </div>
  </section>
</template>

<script setup>

import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

function irParaLogin() {
  router.push("/login");
}

function irParaHistorias() {
  router.push("/historias");
}

function irParaRua() {
  router.push("/a-rua");
}

</script>

<style scoped>
.app-header {
  position: relative;
  width: 100%;
  background: #fbf6e6;
  border-radius: 48px 48px 0 0;
  margin-top: 48px;
  padding: 32px 0 120px;
  overflow: hidden;
  z-index: 2;
  
}

.nav {
  padding: 28px 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.nav-items {
  display: flex;
  gap: 48px;
}

.nav-item {
  font-size: 18px;
  font-weight: 700;
  color: #000;
  cursor: pointer;
  padding: 8px 18px;
  border-radius: 14px;
  transition: background-color 0.25s ease;
}

.nav-item:hover {
  background-color: rgba(199, 164, 58, 0.18);
}

.login-btn {
  position: absolute;
  right: 48px;
  background: #c7a43a;
  color: #ffffff;
  border: none;
  border-radius: 12px;
  padding: 10px 22px;
  font-weight: 600;
  cursor: pointer;
}

.video-area {
  margin-top: 48px;
  display: flex;
  justify-content: center;
  position: relative;
  z-index: 3;
}

.dots {
  position: absolute;
  width: 72px;
  opacity: 0.8;
  filter: saturate(1.4);
  pointer-events: none;
  z-index: 1;
}

.dots-1 {
  top: 140px;
  left: 64px;
}

.dots-2 {
  top: 300px;
  left: 96px;
}

.dots-3 {
  top: 220px;
  right: 120px;
}

.dots-4 {
  top: 420px;
  right: 80px;
}

.dots-5 {
  top: 520px;
  left: 140px;
}

.dots-6 {
  bottom: 180px;
  left: 220px;
}

.dots-7 {
  bottom: 120px;
  right: 260px;
}
</style>
