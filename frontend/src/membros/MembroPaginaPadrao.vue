<template>
  <MembroPagina
    :membro="membro"
    :tema="tema"
    :simbolo="simbolo"
    :carregando="carregando"
    :erro="erro"
    :anterior="anterior"
    :proximo="proximo"
    @retry="$emit('retry')"
  />
</template>

<script setup>
// Fallback: a casca sem personalização. Foto como símbolo; sem foto, iniciais.
import MembroPagina from "./MembroPagina.vue";

defineProps({
  membro: { type: Object, default: null },
  tema: { type: Object, default: () => ({}) },
  simbolo: { type: String, default: "" },
  carregando: { type: Boolean, default: false },
  erro: { type: Boolean, default: false },
  anterior: { type: String, default: null },
  proximo: { type: String, default: null },
});
defineEmits(["retry"]);
</script>
