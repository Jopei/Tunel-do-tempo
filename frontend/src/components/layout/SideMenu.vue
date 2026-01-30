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

        <!-- MENU -->
        <transition name="slide">
            <nav v-if="aberto" class="side-menu">
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
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { computed } from "vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

const fotoPerfilUrl = computed(() => {
    if (!authStore.usuario?.foto?.uuid) {
        return null;
    }

    return `${import.meta.env.VITE_API_BASE_URL}/fotos/${authStore.usuario.foto.uuid}`;
});
const router = useRouter();
const aberto = ref(false);

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

    box-shadow: 0 8px 24px rgba(0, 0, 0, .25);
}

/* MENU */
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

    box-shadow: 0 25px 70px rgba(0, 0, 0, .3);
}

/* BOTÕES */
.side-menu button {
    width: 52px;
    height: 52px;
    border-radius: 14px;

    border: none;
    background: rgba(199, 164, 58, 0.15);
    font-size: 24px;

    cursor: pointer;
    transition: all .2s ease;
}

.side-menu button:hover {
    background: rgba(199, 164, 58, 0.35);
    transform: translateX(-2px);
}

/* ANIMAÇÃO */
.slide-enter-active,
.slide-leave-active {
    transition: all .25s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.slide-enter-to {
    opacity: 1;
    transform: translateX(0);
}

.slide-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
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

    box-shadow: 0 8px 24px rgba(0, 0, 0, .25);
}

/* MENU */
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

    box-shadow: 0 25px 70px rgba(0, 0, 0, .3);
}

/* BOTÕES */
.side-menu button {
    width: 52px;
    height: 52px;
    border-radius: 14px;

    border: none;
    background: rgba(199, 164, 58, 0.15);
    font-size: 24px;

    cursor: pointer;
    transition: all .2s ease;
}

.side-menu button:hover {
    background: rgba(199, 164, 58, 0.35);
    transform: translateX(-2px);
}

/* ANIMAÇÃO */
.slide-enter-active,
.slide-leave-active {
    transition: all .25s ease;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.slide-enter-to {
    opacity: 1;
    transform: translateX(0);
}

.slide-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
