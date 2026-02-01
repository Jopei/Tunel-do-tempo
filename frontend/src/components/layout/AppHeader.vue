<template>
  <section class="app-header">
    <nav class="nav">
      <!-- LOGIN MOBILE ESQUERDA -->
      <button
        v-if="!authStore.logado"
        class="login-btn mobile-login"
        @click="irParaLogin"
      >
        Login
      </button>

      <!-- DESKTOP MENU -->
      <div class="nav-items">
        <span class="nav-item">Videos</span>
        <span class="nav-item" @click="irParaHistorias">Historias</span>
        <span class="nav-item" @click="irParaRua">A Rua</span>
        <span class="nav-item">Fotos</span>
      </div>

      <!-- MOBILE MENU BUTTON -->
      <button class="menu-mobile" @click="abrirMenu">
        ☰
      </button>

      <!-- LOGIN DESKTOP -->
      <button
        v-if="!authStore.logado"
        class="login-btn desktop-login"
        @click="irParaLogin"
      >
        Login
      </button>
    </nav>

    <!-- OVERLAY -->
    <div
      v-if="menuAberto"
      class="drawer-overlay"
      :class="{ fechar: fechando }"
      @click="fecharMenu"
    ></div>

    <!-- MOBILE DRAWER -->
    <div
      v-if="menuAberto"
      class="mobile-drawer"
      :class="{ fechar: fechando }"
    >
      <div class="drawer-header">
        <span>Menu</span>
        <button class="close-btn" @click="fecharMenu">✕</button>
      </div>

      <span @click="navegar('/historias')">Historias</span>
      <span @click="navegar('/a-rua')">A Rua</span>
      <span>Videos</span>
      <span>Fotos</span>
    </div>

    <div class="video-area">
      <slot />
    </div>
  </section>
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

function irParaHistorias() {
  router.push("/historias");
}

function irParaRua() {
  router.push("/a-rua");
}

function navegar(path) {
  fecharMenu();
  setTimeout(() => {
    router.push(path);
  }, 250);
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
  z-index: 2;
}

.nav {
  padding: 28px 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

/* DESKTOP */
.nav-items {
  display: flex;
  gap: 48px;
}

.nav-item {
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  padding: 8px 18px;
  border-radius: 14px;
}

.login-btn {
  background: #c7a43a;
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 10px 22px;
  font-weight: 600;
  cursor: pointer;
}

.desktop-login {
  position: absolute;
  right: 48px;
}

.menu-mobile {
  display: none;
  position: absolute;
  right: 24px;
  font-size: 28px;
  background: none;
  border: none;
  cursor: pointer;
}

/* MOBILE LOGIN */
.mobile-login {
  display: none;
  position: absolute;
  left: 24px;
}

/* MOBILE */
@media (max-width: 600px) {
  .nav {
    padding: 20px;
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

  .app-header {
    margin-top: 16px;
    border-radius: 24px 24px 0 0;
    padding-bottom: 64px;
  }
}

/* OVERLAY */
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
  gap: 20px;
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

/* KEYFRAMES */
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
