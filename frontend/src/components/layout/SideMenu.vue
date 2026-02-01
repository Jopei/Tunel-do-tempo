<template>
  <div class="side-menu-wrapper">
    <!-- BOTÃO USUÁRIO -->
    <div class="menu-toggle" @click="toggleMenu">
      <template v-if="fotoPerfilUrl">
        <img :src="fotoPerfilUrl" alt="Perfil" />
      </template>
      <template v-else>
        <span class="icon">👤</span>
      </template>
    </div>

    <!-- DESKTOP MENU -->
    <transition name="slide">
      <nav v-if="aberto && !isMobile" class="side-menu">
        <button @click="ir('/')" title="Home" aria-label="Home">
          <span>🏠</span>
        </button>

        <button @click="ir('/historias')" title="Histórias" aria-label="Histórias">
          <span>📝</span>
        </button>

        <button @click="ir('/videos')" title="Vídeos" aria-label="Vídeos">
          <span>🎬</span>
        </button>

        <button @click="ir('/fotos')" title="Fotos" aria-label="Fotos">
          <span>🖼️</span>
        </button>
      </nav>
    </transition>

    <!-- MOBILE OVERLAY MENU -->
    <transition name="fade">
      <div v-if="aberto && isMobile" class="mobile-menu">
        <button @click="ir('/')">🏠 Home</button>
        <button @click="ir('/historias')">📝 Histórias</button>
        <button @click="ir('/videos')">🎬 Vídeos</button>
        <button @click="ir('/fotos')">🖼️ Fotos</button>
      </div>
    </transition>
  </div>
</template>
<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const router = useRouter();

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const aberto = ref(false);

const fotoPerfilUrl = computed(() => {
  if (!authStore.usuario?.foto?.uuid) {
    return null;
  }

  return `${import.meta.env.VITE_API_BASE_URL}/fotos/${authStore.usuario.foto.uuid}`;
});

function toggleMenu() {
  aberto.value = !aberto.value;
}

function ir(path) {
  aberto.value = false;
  router.push(path);
}
</script>
<style scoped>
.side-menu-wrapper {
  position: fixed;
  top: 32px;
  right: 32px;
  z-index: 1000;
}

/* BOTÃO DO USUÁRIO */
.menu-toggle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #c7a43a;
  color: #fff;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 26px;
  cursor: pointer;

  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.menu-toggle img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

/* DESKTOP MENU */
.side-menu {
  position: absolute;
  top: 0;
  right: 72px;

  display: flex;
  flex-direction: column;
  gap: 14px;

  background: #fbf6e6;
  padding: 18px;
  border-radius: 20px;

  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
}

.side-menu button {
  width: 52px;
  height: 52px;
  border-radius: 14px;

  border: none;
  background: rgba(199, 164, 58, 0.15);
  font-size: 24px;

  cursor: pointer;
  transition: all 0.2s ease;
}

.side-menu button:hover {
  background: rgba(199, 164, 58, 0.35);
  transform: translateX(-2px);
}

/* MOBILE MENU */
.mobile-menu {
  position: fixed;
  inset: 0;
  background: #fbf6e6;
  z-index: 999;

  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 28px;

  padding: 32px;
}

.mobile-menu button {
  font-size: 22px;
  font-weight: 700;
  background: rgba(199, 164, 58, 0.2);
  border: none;
  border-radius: 18px;
  padding: 18px;
  cursor: pointer;
}

/* ANIMAÇÕES */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.25s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* MOBILE AJUSTES */
@media (max-width: 600px) {
  .side-menu-wrapper {
    top: auto;
    bottom: 24px;
    right: 24px;
  }
}
</style>
