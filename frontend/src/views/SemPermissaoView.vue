<template>
  <PaginaMensagem
    titulo="Você não tem permissão para esta página."
    texto="Esta área é reservada a quem cuida da rua. Se isso parece um engano, fale com um dos administradores."
  >
    <v-btn to="/" color="primary" variant="flat" size="large">Voltar ao início</v-btn>
  </PaginaMensagem>
</template>

<script setup>
import PaginaMensagem from "@/components/ui/PaginaMensagem.vue";
</script>
