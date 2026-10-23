<template>
  <div class="fotos-esqueleto" aria-hidden="true">
    <div v-for="g in grupos" :key="g" class="esq-grupo">
      <span class="esq-cab"></span>
      <div class="esq-linha">
        <span v-for="(f, i) in fatores[g]" :key="i" class="esq-bloco" :style="{ flexGrow: f, flexBasis: `${f * 60}px`, height: `${altura}px` }"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
// Larguras fixas (seed estável): o esqueleto não "pula" entre renderizações.
const fatores = [
  [2.1, 1.3, 1.6, 1.0, 1.8, 1.2, 1.5],
  [1.4, 1.9, 1.1, 2.2, 1.3, 1.6],
  [1.7, 1.2, 2.0, 1.4, 1.1, 1.9, 1.3],
];
defineProps({
  grupos: { type: Number, default: 3 },
  altura: { type: Number, default: 160 },
});
</script>

<style scoped>
.esq-grupo {
  margin-bottom: 20px;
}
.esq-cab {
  display: block;
  width: 180px;
  height: 18px;
  margin: 12px 4px;
  border-radius: 4px;
  background: var(--tm-paper-2);
}
.esq-linha {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.esq-bloco {
  border-radius: 4px;
  background: linear-gradient(100deg, var(--tm-paper-2) 30%, var(--tm-border) 50%, var(--tm-paper-2) 70%) 0 0 / 200% 100%;
  animation: esq-shimmer 1.4s linear infinite;
}
@keyframes esq-shimmer {
  to {
    background-position: -200% 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .esq-bloco {
    animation: none;
  }
}
:global([data-reduzir="1"]) .esq-bloco {
  animation: none;
}
</style>
