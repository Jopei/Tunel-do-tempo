<template>
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
            <span @click="router.push('/')">
                Home
            </span>
            <span @click="router.push('/historias/criar')">
                Adicionar História
            </span>
            <span @click="router.push('/fotos/criar')">
                Adicionar Foto
            </span>
            <span @click="router.push('/videos/criar')">
                Adicionar Vídeo
            </span>
            <span @click="router.push('/galeria')">
                Ver Galeria
            </span>
            <span @click="router.push('/configuracoes')">
                Configurações
            </span>
            <span class="sair" @click="logout">
                Sair
            </span>
        </div>
    </div>
    <div class="em-breve">
        <h1 class="texto-elevacao">Em breve...</h1>
    </div>
</template>
<script setup>
import gsap from "gsap";
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const router = useRouter();

const menuAberto = ref(false);

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

   const overlay = document.getElementById("transition-overlay");

  if (overlay) {
    gsap.to(overlay, {
      opacity: 0,
      duration: 1.2,
      ease: "power2.out",
      onComplete: () => {
        overlay.style.display = "none";
        overlay.style.opacity = 1;
      },
    });
  }
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
.em-breve {
    min-height: 100vh;
    background: url("/backgrounds/cadastrar-bg.svg") center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
}

h1 {
    font-size: 48px;
    font-weight: 900;
    color: #000;
}

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
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.avatar.active {
  transform: scale(0.96);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
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
  animation: dropdownIn 0.25s ease forwards;
}
.user-menu {
  position: absolute;
  top: 24px;
  right: 32px;
  z-index: 20;
}
.dropdown span {
  display: block;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
  color: #333;
}

.dropdown span:hover {
  background: rgba(199, 164, 58, 0.18);
}

.dropdown .sair {
  color: #b00020;
}

.texto-elevacao {
  opacity: 0;
  animation: elevacaoTexto 1s ease-out forwards;
}

/* ANIMAÇÃO */
@keyframes dropdownIn {
  from {
    opacity: 0;
    transform: translateX(8px) translateY(-6px);
  }

  to {
    opacity: 1;
    transform: translateX(0) translateY(0);
  }
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

@keyframes elevacaoTexto {
  from {
    opacity: 0;
    transform: translateY(32px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pageIn {
  from {
    opacity: 0;
    transform: scale(1.02);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
