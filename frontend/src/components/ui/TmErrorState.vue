<template>
  <div class="tm-error" role="alert">
    <v-icon icon="mdi-alert-circle-outline" size="48" color="error" class="mb-3" aria-hidden="true" />
    <h2 class="text-h6 mb-1">{{ titulo }}</h2>
    <p class="text-medium-emphasis mb-4">{{ texto }}</p>
    <v-btn variant="tonal" prepend-icon="mdi-refresh" @click="$emit('retry')">Tentar novamente</v-btn>
  </div>
</template>

<script setup>
defineProps({
  titulo: { type: String, default: "Não foi possível carregar" },
  texto: { type: String, default: "Verifique sua conexão e tente de novo." },
});
defineEmits(["retry"]);
</script>

<style scoped>
.tm-error {
  text-align: center;
  padding: 48px 16px;
  color: rgb(var(--v-theme-on-surface));
}
</style>
