<template>
  <div class="tm-empty" role="status">
    <v-icon :icon="icone" size="48" class="mb-3" aria-hidden="true" />
    <h2 class="text-h6 mb-1">{{ titulo }}</h2>
    <p v-if="texto" class="text-medium-emphasis mb-4">{{ texto }}</p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  icone: { type: String, default: "mdi-package-variant" },
  titulo: { type: String, required: true },
  texto: { type: String, default: "" },
});
</script>

<style scoped>
.tm-empty {
  text-align: center;
  padding: 48px 16px;
  color: rgb(var(--v-theme-on-surface));
}
</style>
