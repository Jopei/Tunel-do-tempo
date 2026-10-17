<template>
  <section class="curta tm-immersive">
    <div class="caixa">
      <h1 class="tm-title" tabindex="-1">{{ titulo }}</h1>
      <p v-if="texto" class="texto">{{ texto }}</p>
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({ titulo: { type: String, required: true }, texto: { type: String, default: "" } });
</script>

<style scoped>
.curta {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/login-bg.svg") center/cover fixed;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-7);
}
.caixa {
  width: 100%;
  max-width: 460px;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
h1 {
  font-size: var(--tm-fs-2xl);
  margin-bottom: var(--tm-space-2);
}
.texto {
  color: var(--tm-ink-muted);
  margin-bottom: var(--tm-space-5);
}
</style>
