<template>
  <section class="rua-page tm-immersive">
    <div class="rua-card page-reveal">
      <div class="rua-body">
        <div class="texto">
          <RuaLogo variante="completa" :tamanho="64" class="rua-marca" />
          <p class="dica">
            <v-icon size="20" aria-hidden="true">mdi-gesture-tap</v-icon>
            {{ mobile ? "Toque" : "Clique" }} em uma esfera para conhecer um membro
          </p>

          <h1>
            Um projeto feito<br />
            por <span>amigos.</span>
          </h1>

          <v-btn v-if="!auth.logado" to="/cadastrar/usuarios" size="large" variant="flat" color="primary" class="cta">
            Quero fazer parte
          </v-btn>
          <v-btn v-else to="/membros" size="large" variant="flat" color="primary" class="cta">Ver membros</v-btn>
        </div>

        <RuaOrbita />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { useDisplay } from "vuetify";
import { useAuthStore } from "@/stores/auth";
import { finalizarTransicao } from "@/composables/useTransicao";
import RuaLogo from "@/components/brand/RuaLogo.vue";
import RuaOrbita from "@/components/rua/RuaOrbita.vue";

const auth = useAuthStore();
const { mobile } = useDisplay();

onMounted(finalizarTransicao);
</script>

<style scoped>
.rua-page {
  min-height: 100dvh;
  background: url("/backgrounds/home-bg.svg") center/cover no-repeat;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-top: calc(var(--tm-navbar-h) + 16px);
}

.rua-card {
  width: min(1200px, 92%);
  min-height: calc(100dvh - var(--tm-navbar-h) - 32px);
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: 48px 48px 0 0;
  padding: var(--tm-space-7) var(--tm-space-8);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  z-index: 2;
}

.rua-body {
  flex: 1;
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  align-items: center;
  gap: var(--tm-space-5);
}

.texto {
  padding-left: var(--tm-space-5);
}

.rua-marca {
  margin-bottom: var(--tm-space-5);
  max-width: 100%;
  height: auto;
}

.dica {
  color: var(--tm-gold-text);
  font-size: var(--tm-fs-md);
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  margin: 0;
}

h1 {
  margin-top: var(--tm-space-4);
  font-size: clamp(2.125rem, 5vw, 3.25rem);
  font-weight: 700;
  line-height: 1.2;
}

h1 span {
  color: var(--tm-gold-text);
}

.cta {
  margin-top: var(--tm-space-6);
  min-height: 48px;
  font-weight: 700;
}
.cta :deep(.v-btn__content) {
  color: rgb(var(--v-theme-on-primary));
}

.rua-body > :last-child {
  justify-self: center;
}

.page-reveal {
  opacity: 0;
  animation: tm-fade-up 0.9s var(--tm-ease) forwards;
}

@media (max-width: 960px) {
  .rua-card {
    padding: var(--tm-space-6) var(--tm-space-5);
  }
  .rua-body {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .texto {
    padding-left: 0;
  }
  .dica {
    justify-content: center;
  }
}

@media (max-width: 600px) {
  .rua-card {
    width: 100%;
    border-radius: 24px 24px 0 0;
    padding: var(--tm-space-5) var(--tm-space-4);
  }
  .cta {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .page-reveal {
    opacity: 1;
    animation: none;
  }
}
</style>
