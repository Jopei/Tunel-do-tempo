<template>
  <span class="musica-capa" :style="{ width: `${tamanho}px`, height: `${tamanho}px` }" :class="{ vazia: !src || falhou }">
    <img v-if="src && !falhou" :src="src" alt="" loading="lazy" decoding="async" draggable="false" @error="falhou = true" />
    <v-icon v-else :size="Math.round(tamanho * 0.5)" aria-hidden="true">mdi-music-note</v-icon>
  </span>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  src: { type: String, default: null },
  tamanho: { type: Number, default: 48 },
});
const falhou = ref(false);
watch(() => props.src, () => (falhou.value = false));
</script>

<style scoped>
.musica-capa {
  display: inline-grid;
  place-items: center;
  flex: none;
  overflow: hidden;
  border-radius: var(--tm-radius-xs);
  background: var(--tm-paper-2);
  color: var(--tm-gold);
}
.musica-capa img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
