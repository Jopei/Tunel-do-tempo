<template>
  <div class="menu-wrapper" ref="menuRef">

    <!-- AVATAR -->
    <div class="avatar" @click="toggleMenu">
      <img v-if="fotoPerfilUrl" :src="fotoPerfilUrl" />
      <span v-else>👤</span>
    </div>
    
    <!-- MENU PRINCIPAL -->
    <div v-if="menuAberto" class="dropdown">
      <span @click="navegar('/')">Home</span>

      <span @click="navegar('/historias')">Histórias</span>

      <!-- A RUA -->
      <span @click="toggleRua">
        A Rua
      </span>

      <div v-if="subRuaAberto" class="submenu">
        <span @click="navegar('/a-rua')">Entrar na Rua</span>
      </div>

      <span @click="navegar('/galeria-videos')">Vídeos</span>
      <span @click="navegar('/galeria-fotos')">Fotos</span>
      <span @click="navegar('/atualizacoes')">Atualizações</span>

      <!-- SE LOGADO -->
      <template v-if="authStore.logado">

        <div class="divider"></div>

        <span @click="navegar('/historias/criar')">Adicionar História</span>
        <span @click="openFotoModal">Adicionar Foto</span>
        <span @click="openVideoModal">Adicionar Vídeo</span>

        <!-- CONFIGURAÇÕES -->
        <span @click="toggleConfig">
          Configurações
        </span>

        <div v-if="subConfigAberto" class="submenu">

          <span @click="navegar('/notificacoes')">
            Criar Notificação
          </span>

          <span @click="abrirAtualizacao">
            Adicionar Atualização
          </span>

        </div>

        <span class="sair" @click="logout">Sair</span>

      </template>

    </div>

  </div>

  <CriarFotoModal v-model="abrirModalFoto" />
  <CriarVideoModal v-model="abrirModalVideo" />
  <CriarAtualizacaoModal v-model="abrirModalAtualizacao" />

</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

import CriarFotoModal from "@/components/fotos/CriarFotoModal.vue";
import CriarVideoModal from "@/components/videos/CriarVideoModal.vue";
import CriarAtualizacaoModal from "@/components/atualizacoes/CriarAtualizacaoModal.vue";

const router = useRouter();
const authStore = useAuthStore();

const menuAberto = ref(false);
const subRuaAberto = ref(false);
const subConfigAberto = ref(false);

const abrirModalFoto = ref(false);
const abrirModalVideo = ref(false);
const abrirModalAtualizacao = ref(false);

const menuRef = ref(null);

const fotoPerfilUrl = computed(() => {
  if (!authStore.usuario?.foto_perfil) return null;

  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace("/api", "");
  return baseUrl + "/storage/" + authStore.usuario.foto_perfil;
});

function toggleMenu() {
  menuAberto.value = !menuAberto.value;
  subRuaAberto.value = false;
  subConfigAberto.value = false;
}

function toggleRua() {
  subRuaAberto.value = !subRuaAberto.value;
}

function toggleConfig() {
  subConfigAberto.value = !subConfigAberto.value;
}

function navegar(path) {
  menuAberto.value = false;
  subRuaAberto.value = false;
  subConfigAberto.value = false;
  router.push(path);
}

function openFotoModal() {
  menuAberto.value = false;
  abrirModalFoto.value = true;
}

function openVideoModal() {
  menuAberto.value = false;
  abrirModalVideo.value = true;
}

function abrirAtualizacao() {
  menuAberto.value = false;
  abrirModalAtualizacao.value = true;
}

function logout() {
  authStore.logout();
  menuAberto.value = false;
  router.push("/");
}

function handleClickOutside(event) {
  if (menuRef.value && !menuRef.value.contains(event.target)) {
    menuAberto.value = false;
    subRuaAberto.value = false;
    subConfigAberto.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.menu-wrapper {
  position: relative;
}


.avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #c7a43a;
  background: #fff;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.dropdown {
  position: absolute;
  top: 62px;
  right: 0;
  width: 240px;
  background: #fbf6e6;
  border-radius: 18px;
  padding: 14px 0;
  box-shadow: 0 20px 60px rgba(0,0,0,.25);
  animation: fadeDown .2s ease;
}

.dropdown span {
  display: block;
  padding: 10px 20px;
  cursor: pointer;
  font-weight: 600;
  transition: .2s ease;
}

.dropdown span:hover {
  background: rgba(199,164,58,.18);
}

.submenu {
  background: rgba(199,164,58,.08);
  padding-left: 16px;
}

.divider {
  height: 1px;
  background: rgba(0,0,0,.1);
  margin: 8px 0;
}

.sair {
  color: #b00020;
}

@keyframes fadeDown {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>