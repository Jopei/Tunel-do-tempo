<template>
  <PaginaMensagem
    titulo="Em breve"
    texto="Esta parte da rua ainda está sendo construída. Volte em alguns dias."
  >
    <v-btn to="/a-rua" color="primary" variant="flat" size="large">Voltar à rua</v-btn>
    <v-btn to="/" variant="text" color="white" size="large">Início</v-btn>
  </PaginaMensagem>
</template>

<script setup>
import { onMounted } from "vue";
import { finalizarTransicao } from "@/composables/useTransicao";
import PaginaMensagem from "@/components/ui/PaginaMensagem.vue";

onMounted(finalizarTransicao);
</script>
