<template>
  <img v-if="tipo === 'img'" class="ms-img" :src="simbolo" alt="" />
  <v-icon v-else-if="tipo === 'mdi'" class="ms-icone" aria-hidden="true">{{ simbolo }}</v-icon>
  <span v-else-if="tipo === 'texto'" class="ms-texto" aria-hidden="true">{{ simbolo }}</span>
  <img v-else-if="foto" class="ms-img" :src="foto" alt="" />
  <span v-else class="ms-iniciais" aria-hidden="true">{{ iniciais(nome) || "?" }}</span>
</template>

<script setup>
import { computed } from "vue";
import { iniciais } from "@/composables/useFormat";

const props = defineProps({
  simbolo: { type: String, default: "" },
  nome: { type: String, default: "" },
  foto: { type: String, default: null },
});

const tipo = computed(() => {
  const s = props.simbolo;
  if (!s) return null;
  if (s.startsWith("mdi-")) return "mdi";
  if (/^(\/|https?:|data:)/.test(s) || /\.(svg|png|webp|jpe?g)$/i.test(s)) return "img";
  return "texto";
});
</script>

<style scoped>
.ms-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.ms-icone {
  font-size: clamp(64px, 14vw, 128px);
}
.ms-texto,
.ms-iniciais {
  font-family: var(--tm-font-title);
  font-size: clamp(56px, 12vw, 112px);
  line-height: 1;
}
.ms-iniciais {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  background: var(--tm-gold);
  color: #2a2112;
}
</style>
