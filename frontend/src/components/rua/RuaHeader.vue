<template>
  <header class="rua-header">
    <nav class="nav">
      <button
        v-if="!authStore.logado"
        class="login-btn mobile-login"
        @click="irParaLogin"
      >
        Login
      </button>

      <div class="nav-items">
        <span @click="irParaHome">Home</span>
        <span>Videos</span>
        <span @click="irParaHistorias">Historias</span>
        <span>Fotos</span>
      </div>

      <button class="menu-mobile" @click="abrirMenu">
        ☰
      </button>

      <button
        v-if="!authStore.logado"
        class="login-btn desktop-login"
        @click="irParaLogin"
      >
        Login
      </button>
    </nav>

    <div
      v-if="menuAberto"
      class="drawer-overlay"
      :class="{ fechar: fechando }"
      @click="fecharMenu"
    ></div>

    <div
      v-if="menuAberto"
      class="mobile-drawer"
      :class="{ fechar: fechando }"
    >
      <div class="drawer-header">
        <span>Menu</span>
        <button class="close-btn" @click="fecharMenu">✕</button>
      </div>

      <span @click="navegar('/')">Home</span>
      <span @click="navegar('/historias')">Historias</span>
      <span>Videos</span>
      <span>Fotos</span>
    </div>

    <!-- DOTS -->
    <img src="/decorations/dots.svg" class="dots d1" />
    <img src="/decorations/dots.svg" class="dots d2" />
    <img src="/decorations/dots.svg" class="dots d3" />
    <img src="/decorations/dots.svg" class="dots d4" />
  </header>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

const menuAberto = ref(false);
const fechando = ref(false);

function abrirMenu() {
  menuAberto.value = true;
}

function fecharMenu() {
  fechando.value = true;

  setTimeout(() => {
    menuAberto.value = false;
    fechando.value = false;
  }, 250);
}

function irParaLogin() {
  router.push("/login");
}

function irParaHome() {
  router.push("/");
}

function irParaHistorias() {
  router.push("/historias");
}

function navegar(path) {
  fecharMenu();
  setTimeout(() => {
    router.push(path);
  }, 250);
}
</script>

<style scoped>
.rua-header {
  position: relative;
  padding-bottom: 24px;
}

/* NAV */
.nav {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 24px 48px;
}

.nav-items {
  display: flex;
  gap: 48px;
}

.nav-items span {
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
}

/* LOGIN */
.login-btn {
  background: #c7a43a;
  border: none;
  border-radius: 12px;
  padding: 10px 22px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

.desktop-login {
  position: absolute;
  right: 48px;
}

/* MOBILE */
.menu-mobile {
  display: none;
  position: absolute;
  right: 24px;
  font-size: 28px;
  background: none;
  border: none;
  cursor: pointer;
}

.mobile-login {
  display: none;
  position: absolute;
  left: 24px;
}

/* DRAWER OVERLAY */
.drawer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 998;
  animation: overlayFadeIn 0.25s ease forwards;
}

.drawer-overlay.fechar {
  animation: overlayFadeOut 0.25s ease forwards;
}

/* DRAWER */
.mobile-drawer {
  position: fixed;
  top: 0;
  left: 0;
  width: 80%;
  max-width: 300px;
  height: 100vh;
  background: #fbf6e6;
  z-index: 999;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 22px;
  animation: drawerSlideIn 0.3s ease forwards;
}

.mobile-drawer.fechar {
  animation: drawerSlideOut 0.25s ease forwards;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  font-weight: 700;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 22px;
  cursor: pointer;
}

/* DOTS */
.dots {
  position: absolute;
  width: 72px;
  opacity: 0.7;
  pointer-events: none;
}

/* MOBILE ONLY */
@media (max-width: 600px) {
  .nav {
    padding: 16px 20px;
    justify-content: space-between;
  }

  .nav-items,
  .desktop-login {
    display: none;
  }

  .menu-mobile {
    display: block;
  }

  .mobile-login {
    display: block;
  }

  .dots {
    opacity: 0.4;
    transform: scale(0.75);
  }
}

/* ANIMAÇÕES */
@keyframes drawerSlideIn {
  from {
    transform: translateX(-100%);
  }
  to {
    transform: translateX(0);
  }
}

@keyframes drawerSlideOut {
  from {
    transform: translateX(0);
  }
  to {
    transform: translateX(-100%);
  }
}

@keyframes overlayFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes overlayFadeOut {
  from {
    opacity: 1;
  }
  to {
    opacity: 0;
  }
}
</style>
