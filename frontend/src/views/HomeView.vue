<template>
  <section class="home-view">
    <div v-if="authStore.logado" class="user-menu">
      <div class="avatar" :class="{ active: menuAberto }" @click="toggleMenu">
        <template v-if="fotoPerfilUrl">
          <img :src="fotoPerfilUrl" alt="Perfil" />
        </template>
        <template v-else>
          <span class="avatar-icon">👤</span>
        </template>
      </div>

      <div v-if="menuAberto" class="dropdown">
        <span @click="irParaCriarHistoria">
          Adicionar História
        </span>
        <span @click="router.push('/fotos/criar')">Adicionar Foto</span>
        <span @click="router.push('/videos/criar')">Adicionar Vídeo</span>
        <span @click="router.push('/galeria')">Ver Galeria</span>
        <span @click="router.push('/configuracoes')">Configurações</span>
        <span class="sair" @click="logout">Sair</span>
      </div>
    </div>

    <div class="hero-content">
      <h1 class="reveal" style="--delay: 0s">Túnel do Tempo</h1>

      <p class="subtitle reveal" style="--delay: 0.15s">
        O tempo não apaga o que a luz tocou, apenas o transforma em memória.
      </p>

      <AppHeader class="reveal" style="--delay: 0.3s">
        <FeaturedVideoCard class="reveal" style="--delay: 0.45s" />
      </AppHeader>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { computed } from "vue";
import AppHeader from "@/components/layout/AppHeader.vue";
import FeaturedVideoCard from "@/components/video/FeaturedVideoCard.vue";
import { onMounted, onBeforeUnmount } from "vue";


const authStore = useAuthStore();
const router = useRouter();
const menuAberto = ref(false);

function handleClickOutside(e) {
  const menu = document.querySelector(".user-menu");
  if (menu && !menu.contains(e.target)) {
    menuAberto.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

function toggleMenu() {
  menuAberto.value = !menuAberto.value;
}

function irParaCriarHistoria() {
  router.push("/historias/criar");
}

function logout() {
  authStore.logout();
  router.push("/");
}

const fotoPerfilUrl = computed(() => {
  if (!authStore.usuario?.foto?.uuid) {
    return null;
  }

  return `${import.meta.env.VITE_API_BASE_URL}/fotos/${authStore.usuario.foto.uuid}`;
});
</script>


<style scoped>
/* ===== FUNDO ===== */
.home-view {
  min-height: 100vh;
  background-image: url("/backgrounds/home-bg.svg");
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: center;
}

/* ===== CONTEÚDO ===== */
.hero-content {
  width: 100%;
  max-width: 1200px;
  padding: 60px 24px 0;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ===== TEXTO ===== */
h1 {
  font-size: 64px;
  font-weight: 700;
  color: #fff;
  text-align: center;
}

.subtitle {
  text-align: center;
  color: #fff;
  font-style: italic;
  margin-top: 8px;
  margin-bottom: 48px;
  opacity: 0.9;
}

.user-menu {
  position: fixed;
  top: 24px;
  right: 32px;
  z-index: 10;
}

/* CÍRCULO */
.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #c7a43a;
  cursor: pointer;
  background: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-icon {
  font-size: 26px;
  color: #c7a43a;
}


/* feedback ao clicar */
.avatar.active {
  transform: scale(0.96);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

/* DROPDOWN */
.dropdown {
  position: absolute;
  top: 58px;
  right: 0;
  /* ancora no avatar */
  transform: translateX(-10px);
  width: 230px;
  background: #fbf6e6;
  border-radius: 16px;
  padding: 12px 0;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
  animation: dropdownIn 0.25s ease forwards;
}

/* ITENS */
.dropdown span {
  display: block;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
  color: #333;
  transition: background 0.2s ease;
}

.dropdown span:hover {
  background: rgba(199, 164, 58, 0.18);
}

.dropdown .sair {
  color: #b00020;
}


/* ===== ANIMAÇÃO CASCATA ===== */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes dropdownIn {
  from {
    opacity: 0;
    transform: translateX(8px) translateY(-6px);
  }

  to {
    opacity: 1;
    transform: translateX(-10px) translateY(0);
  }
}

.reveal {
  opacity: 0;
  animation: fadeUp 0.8s ease-out forwards;
  animation-delay: var(--delay, 0s);
  will-change: transform, opacity;
}
</style>
