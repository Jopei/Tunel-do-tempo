<template>
  <span class="eq" :class="{ tocando }" aria-hidden="true">
    <span class="eq-barra"></span>
    <span class="eq-barra"></span>
    <span class="eq-barra"></span>
  </span>
</template>

<script setup>
defineProps({ tocando: { type: Boolean, default: true } });
</script>

<style scoped>
.eq {
  display: inline-flex;
  align-items: flex-end;
  gap: 2px;
  width: 16px;
  height: 16px;
}
.eq-barra {
  flex: 1;
  height: 40%;
  background: var(--tm-gold-text);
  border-radius: 1px;
  transform-origin: bottom;
}
.eq-barra:nth-child(2) {
  height: 90%;
}
.eq-barra:nth-child(3) {
  height: 60%;
}
.eq.tocando .eq-barra {
  animation: eq-onda 900ms ease-in-out infinite alternate;
}
.eq.tocando .eq-barra:nth-child(2) {
  animation-delay: -300ms;
}
.eq.tocando .eq-barra:nth-child(3) {
  animation-delay: -600ms;
}
@keyframes eq-onda {
  from {
    transform: scaleY(0.35);
  }
  to {
    transform: scaleY(1);
  }
}
@media (prefers-reduced-motion: reduce) {
  .eq.tocando .eq-barra {
    animation: none;
  }
}
:global([data-reduzir="1"]) .eq.tocando .eq-barra {
  animation: none;
}
</style>
