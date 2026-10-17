<template>
  <v-app>
    <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
    <AppNavbar />
    <v-main tag="main" id="conteudo" tabindex="-1" class="tm-main">
      <router-view v-slot="{ Component, route }">
        <transition name="pagina" mode="out-in">
          <component :is="Component" :key="route.path" />
        </transition>
      </router-view>
    </v-main>
    <ConfirmDialog />
    <AtalhosDialog />
    <BuscaGlobal v-if="auth.logado" />
  </v-app>
  <div id="transition-overlay" aria-hidden="true">
    <img id="transition-image" alt="" />
  </div>
</template>

<script setup>
import AppNavbar from "@/components/layout/AppNavbar.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { useAuthStore } from "@/stores/auth";
import BuscaGlobal from "@/components/ui/BuscaGlobal.vue";
import AtalhosDialog from "@/components/ui/AtalhosDialog.vue";

const auth = useAuthStore();
</script>

<style>
.tm-main {
  --v-layout-top: 0px !important;
  padding-top: 0 !important;
  min-height: 100dvh;
}

#transition-overlay {
  position: fixed;
  inset: 0;
  background: #000;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}

#transition-image {
  width: 220px;
  height: 220px;
  border-radius: 50%;
  object-fit: cover;
}
</style>
