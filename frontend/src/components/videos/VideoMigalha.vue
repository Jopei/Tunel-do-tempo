<template>
  <component
    :is="atual ? 'span' : 'button'"
    class="migalha"
    :class="{ atual, 'alvo-drop': sobre }"
    :type="atual ? undefined : 'button'"
    :aria-current="atual ? 'page' : undefined"
    :data-uuid="uuid ?? 'raiz'"
    v-bind="atual ? {} : handlers"
    @click="!atual && $emit('ir', uuid)"
  >
    <v-icon v-if="raiz" size="20" aria-hidden="true">mdi-filmstrip</v-icon>
    <span>{{ nome }}</span>
  </component>
</template>

<script setup>
import { useAlvoDrop } from "@/composables/useAlvoDrop";

const props = defineProps({
  uuid: { type: String, default: null },
  nome: { type: String, required: true },
  raiz: Boolean,
  atual: Boolean,
  podeSoltar: Boolean,
});
const emit = defineEmits(["ir", "soltar"]);

const { sobre, handlers } = useAlvoDrop((payload) => emit("soltar", props.uuid, props.nome, payload), {
  ativo: () => props.podeSoltar,
});
</script>

<style scoped>
.migalha {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 40px;
  padding: 0 10px;
  border: 2px solid transparent;
  border-radius: var(--tm-radius-pill);
  background: transparent;
  color: var(--tm-ink);
  font: inherit;
  max-width: 220px;
  cursor: pointer;
  position: relative;
}
.migalha > span:last-child {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.migalha:hover:not(.atual) {
  background: var(--tm-paper-2);
}
.migalha.atual {
  font-weight: 600;
  cursor: default;
}
.alvo-drop {
  border-color: var(--tm-gold);
  background: color-mix(in srgb, var(--tm-gold) 12%, transparent);
}
.alvo-drop::after {
  content: "";
  position: absolute;
  inset: -4px;
  border-radius: inherit;
  border: 2px solid var(--tm-gold);
  pointer-events: none;
  animation: alvo-pulso 1.1s var(--tm-ease) infinite;
}
@keyframes alvo-pulso {
  0% {
    transform: scale(0.97);
    opacity: 0.9;
  }
  70%,
  100% {
    transform: scale(1.04);
    opacity: 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .alvo-drop::after {
    animation: none;
    opacity: 0.6;
  }
}
html[data-reduzir="1"] .alvo-drop::after {
  animation: none;
  opacity: 0.6;
}
</style>
