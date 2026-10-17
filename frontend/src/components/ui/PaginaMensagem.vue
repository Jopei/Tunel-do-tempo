<template>
  <section class="mensagem tm-immersive">
    <div class="conteudo">
      <h1 tabindex="-1">{{ titulo }}</h1>
      <p v-if="texto">{{ texto }}</p>
      <div class="acoes"><slot /></div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  titulo: { type: String, required: true },
  texto: { type: String, default: "" },
});
</script>

<style scoped>
.mensagem {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url("/backgrounds/home-bg.svg") center/cover;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 96px 24px 48px;
  color: #fff;
  text-align: center;
}
.conteudo {
  max-width: 560px;
}
h1 {
  font-size: var(--tm-fs-hero);
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
  margin-bottom: 16px;
}
p {
  font-size: var(--tm-fs-lg);
  margin-bottom: 32px;
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
}
.acoes {
  display: flex;
  gap: 12px;
  justify-content: center;
  flex-wrap: wrap;
}
</style>
