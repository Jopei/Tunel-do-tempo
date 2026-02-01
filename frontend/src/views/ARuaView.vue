<template>
  <header class="rua-header">
    <!-- MENU DO USUÁRIO -->
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
        <span @click="router.push('/')">Home</span>
        <span @click="router.push('/historias/criar')">Adicionar História</span>
        <span @click="router.push('/fotos/criar')">Adicionar Foto</span>
        <span @click="router.push('/videos/criar')">Adicionar Vídeo</span>
        <span @click="router.push('/galeria')">Ver Galeria</span>
        <span @click="router.push('/configuracoes')">Configurações</span>
        <span class="sair" @click="logout">Sair</span>
      </div>

      <button class="audio-control" @click="toggleAudio">
        <span v-if="!tocando">▶</span>
        <span v-else>⏸</span>
      </button>
    </div>
  </header>

  <section class="rua-page">
    <div class="rua-card page-reveal">
      <RuaHeader />

      <div class="rua-body">
        <!-- TEXTO -->
        <div class="texto">
          <br>
          <span class="sub">Clique em uma logo.</span>

          <h1>
            Um projeto feito<br />
            por <span>amigos.</span>
          </h1>

          <button class="cta">Siga aqui</button>
        </div>

        <!-- ORBITA -->
        <RuaOrbita />
      </div>
    </div>
  </section>
</template>
<script setup>
import RuaHeader from "@/components/rua/RuaHeader.vue";
import RuaOrbita from "@/components/rua/RuaOrbita.vue";
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const router = useRouter();

const menuAberto = ref(false);
let audio = null;
const tocando = ref(false);

function toggleAudio() {
  if (!audio) return;

  if (tocando.value) {
    audio.pause();
    tocando.value = false;
  } else {
    audio.play().then(() => {
      tocando.value = true;
    }).catch(() => {
      tocando.value = false;
    });
  }
}

function toggleMenu() {
  menuAberto.value = !menuAberto.value;
}

function logout() {
  authStore.logout();
  router.push("/");
}

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

const fotoPerfilUrl = computed(() => {
  if (!authStore.usuario?.foto?.uuid) return null;
  return `${import.meta.env.VITE_API_BASE_URL}/fotos/${authStore.usuario.foto.uuid}`;
});
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
