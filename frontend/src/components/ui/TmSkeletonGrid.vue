<template>
  <div class="tm-skeleton-grid" aria-busy="true" aria-label="Carregando">
    <v-skeleton-loader
      v-for="n in quantidade"
      :key="n"
      :type="tipo"
      class="item"
    />
  </div>
</template>

<script setup>
defineProps({
  quantidade: { type: Number, default: 8 },
  tipo: { type: String, default: "image" },
  minimo: { type: String, default: "160px" },
});
</script>

<style scoped>
.tm-skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(v-bind(minimo), 1fr));
  gap: 16px;
}
</style>
