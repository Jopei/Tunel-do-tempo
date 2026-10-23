<template>
  <AuthLayout>
    <h1 class="tm-title" tabindex="-1">{{ titulo }}</h1>
    <p v-if="texto" class="texto">{{ texto }}</p>
    <slot />
  </AuthLayout>
</template>

<script setup>
import AuthLayout from "@/components/auth/AuthLayout.vue";

defineProps({ titulo: { type: String, required: true }, texto: { type: String, default: "" } });
</script>

<style scoped>
h1 {
  font-size: var(--tm-fs-2xl);
  margin-bottom: var(--tm-space-2);
}
.texto {
  color: var(--tm-ink-muted);
  margin-bottom: var(--tm-space-5);
}
</style>
