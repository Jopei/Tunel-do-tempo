<template>
  <header class="rua-header">
    <nav class="nav">
      <div class="nav-items">
        <span @click="irParaHome">Home</span>
        <span>Videos</span>
        <span class="nav-item" @click="irParaHistorias">
          Historias
        </span>
        <span>Fotos</span>
      </div>

      <button v-if="!authStore.logado" class="login-btn" @click="irParaLogin">
        Login
      </button>
    </nav>

    <img src="/decorations/dots.svg" class="dots d1" />
    <img src="/decorations/dots.svg" class="dots d2" />
    <img src="/decorations/dots.svg" class="dots d3" />
    <img src="/decorations/dots.svg" class="dots d4" />
  </header>
</template>
<script setup>
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const authStore = useAuthStore();

function irParaLogin() {
  router.push("/login");
}
function irParaHome() {
  router.push("/");
}

function irParaHistorias() {
  router.push("/historias");
}

function irParaRua() {
  router.push("/a-rua");
}
</script>
<style scoped>
.rua-header {
  position: relative;
  padding-bottom: 24px;
}

.nav {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
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

.nav-items .ativo {
  font-weight: 900;
}

.login {
  position: absolute;
  right: 0;
  background: #c7a43a;
  border: none;
  border-radius: 12px;
  padding: 10px 24px;
  color: #fff;
  font-weight: 600;
}

/* DOTS */
.dots {
  position: absolute;
  width: 72px;
  opacity: 0.7;
}

.d1 {
  top: 120px;
  left: 40px;
}

.d2 {
  top: 260px;
  left: 480px;
}

.d3 {
  top: 700px;
  right: 200px;
}

.d4 {
  bottom: 80px;
  right: 160px;
}
</style>
