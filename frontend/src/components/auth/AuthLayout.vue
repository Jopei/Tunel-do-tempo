<template>
  <section class="auth tm-immersive" :class="{ 'auth--larga': larga }">
    <div class="auth-foto" aria-hidden="true">
      <img :src="imagem" alt="" width="1200" height="900" fetchpriority="high" decoding="async" />
      <div class="auth-foto-veu"></div>
      <p class="auth-foto-legenda tm-title">Túnel do Tempo<span>A rua, as histórias, a gente.</span></p>
    </div>

    <div class="auth-painel">
      <div class="auth-conteudo">
        <router-link to="/" class="auth-marca tm-title" aria-label="Túnel do Tempo, ir para o início">
          Túnel do Tempo
        </router-link>
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({ larga: { type: Boolean, default: false } });

// Sorteio síncrono: evita o flash de imagem vazia
const imagem = `/login/${Math.floor(Math.random() * 10) + 1}.jpg`;
</script>

<style scoped>
.auth {
  /* 100dvh acompanha a barra de endereço do mobile; desconta o player fixo quando visível */
  height: calc(100dvh - var(--tm-player-h, 0px));
  min-height: 0;
  display: grid;
  grid-template-rows: clamp(120px, 26dvh, 240px) minmax(0, 1fr);
  grid-template-columns: minmax(0, 1fr);
  overflow: hidden;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  animation: tm-fade-up 0.6s var(--tm-ease) both;
}

.auth-foto {
  position: relative;
  overflow: hidden;
  background: rgb(var(--v-theme-surface-variant));
}
.auth-foto img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 35%;
  display: block;
}
.auth-foto-veu {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(20, 12, 4, 0.7), rgba(20, 12, 4, 0.05) 60%);
}
.auth-foto-legenda {
  position: absolute;
  left: var(--tm-space-5);
  right: var(--tm-space-5);
  bottom: var(--tm-space-4);
  margin: 0;
  color: #fff;
  font-size: var(--tm-fs-xl);
  text-shadow: 0 1px 10px rgba(0, 0, 0, 0.5);
}
.auth-foto-legenda span {
  display: block;
  font-family: var(--tm-font-body, inherit);
  font-size: var(--tm-fs-sm);
  opacity: 0.9;
}

.auth-painel {
  min-height: 0;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  padding: var(--tm-space-4) var(--tm-space-4) calc(var(--tm-space-4) + env(safe-area-inset-bottom));
  padding-left: max(var(--tm-space-4), env(safe-area-inset-left));
  padding-right: max(var(--tm-space-4), env(safe-area-inset-right));
}
/* margin:auto centraliza na vertical e, se faltar altura, o painel rola sem cortar o topo */
.auth-conteudo {
  width: 100%;
  max-width: 440px;
  margin: auto;
}
.auth--larga .auth-conteudo {
  max-width: 720px;
}
.auth-marca {
  display: none;
  color: var(--tm-gold-text);
  font-size: var(--tm-fs-lg);
  margin-bottom: var(--tm-space-5);
  text-decoration: none;
}

/* Tablet: ainda empilhado, com mais respiro */
@media (min-width: 600px) {
  .auth-painel {
    padding: var(--tm-space-5);
  }
}

/* Desktop: split em tela cheia */
@media (min-width: 960px) {
  .auth {
    grid-template-rows: minmax(0, 1fr);
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
  }
  .auth--larga {
    grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
  }
  .auth-foto-legenda {
    left: var(--tm-space-7);
    bottom: var(--tm-space-7);
    font-size: var(--tm-fs-3xl);
  }
  .auth-foto-legenda span {
    font-size: var(--tm-fs-md);
  }
  .auth-painel {
    padding: var(--tm-space-6) var(--tm-space-7);
  }
  .auth-marca {
    display: inline-block;
  }
}

/* Paisagem baixa (celular deitado): foto some para o formulário caber */
@media (max-height: 520px) and (max-width: 959px) {
  .auth {
    grid-template-rows: minmax(0, 1fr);
  }
  .auth-foto {
    display: none;
  }
}
</style>
