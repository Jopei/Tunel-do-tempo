<template>
  <header class="rua-header">
    <UserMenu v-if="authStore.logado" />
  </header>

  <section class="rua-page">
    <div class="rua-card page-reveal">
      <RuaHeader />

      <div class="rua-body">
        <div class="texto">
          <br>
          <span class="sub">Clique em uma logo.</span>

          <h1>
            Um projeto feito<br />
            por <span>amigos.</span>
          </h1>

          <button class="cta" @click="irParaCadastro">
            Siga aqui
          </button>
        </div>

        <RuaOrbita />
      </div>
    </div>
  </section>
</template>

<script setup>
import RuaHeader from "@/components/rua/RuaHeader.vue";
import RuaOrbita from "@/components/rua/RuaOrbita.vue";
import UserMenu from "@/components/layout/UserMenu.vue";

import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const router = useRouter();

function irParaCadastro() {
  router.push("/cadastrar/usuarios");
}
</script>

<style scoped>
.rua-page {
  min-height: 100vh;
  background: url("/backgrounds/home-bg.svg") center/cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rua-card {
  width: 84%;
  height: 88vh;
  background: #fbf6e6;
  border-radius: 48px 48px 0 0;
  padding: 48px 64px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  z-index: 2;
  margin-top: 6%;
}

.rua-body {
  flex: 1;
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  align-items: center;
}

.texto {
  padding-left: 24px;
}

.sub {
  color: #c7a43a;
  font-size: 20px;
  font-weight: 600;
}

h1 {
  margin-top: 16px;
  font-size: 52px;
  font-weight: 900;
  line-height: 1.2;
}

h1 span {
  color: red;
}

.cta {
  margin-top: 32px;
  padding: 14px 36px;
  background: #c7a43a;
  border: none;
  border-radius: 14px;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

/* HEADER USER */
.rua-header {
  position: relative;
  width: 100%;
  height: 0;
}

.user-menu {
  position: absolute;
  top: 24px;
  right: 32px;
  z-index: 20;
}

.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #c7a43a;
  cursor: pointer;
  background: #fff;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-icon {
  font-size: 26px;
  color: #c7a43a;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.dropdown {
  position: absolute;
  top: 58px;
  right: 0;
  width: 230px;
  background: #fbf6e6;
  border-radius: 16px;
  padding: 12px 0;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
}

.dropdown span {
  display: block;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
}

.dropdown span:hover {
  background: rgba(199, 164, 58, 0.18);
}

.dropdown .sair {
  color: #b00020;
}

/* AUDIO */
.audio-control {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: #c7a43a;
  color: #fff;
  font-size: 22px;
  cursor: pointer;
  z-index: 9999;
}

/* ANIMAÇÃO */
.page-reveal {
  opacity: 0;
  animation: fadeUpPage 0.9s ease-out forwards;
}

@keyframes fadeUpPage {
  from {
    opacity: 0;
    transform: translateY(32px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* MOBILE ONLY */
@media (max-width: 600px) {
  .rua-card {
    width: 100%;
    height: auto;
    padding: 24px 20px;
    margin-top: 0;
    border-radius: 24px 24px 0 0;
  }

  .rua-body {
    display: flex;
    flex-direction: column;
    gap: 32px;
    text-align: center;
  }

  .texto {
    padding-left: 0;
  }

  h1 {
    font-size: 34px;
  }

  .sub {
    font-size: 16px;
  }

  .cta {
    width: 100%;
  }

  .user-menu {
    top: 16px;
    right: 16px;
  }
}
</style>
