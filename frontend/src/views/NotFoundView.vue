<template>
  <PaginaMensagem
    titulo="Essa rua não leva a lugar nenhum."
    texto="O endereço que você tentou abrir não existe ou mudou de lugar."
  >
    <v-btn to="/" color="primary" variant="flat" size="large">Voltar ao início</v-btn>
    <v-btn to="/historias" variant="outlined" color="white" size="large">Ir para Histórias</v-btn>
    <v-btn variant="text" color="white" size="large" @click="voltar">Voltar</v-btn>
  </PaginaMensagem>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import PaginaMensagem from "@/components/ui/PaginaMensagem.vue";

const router = useRouter();

function voltar() {
  if (window.history.length > 1) router.back();
  else router.push("/");
}

let meta;
onMounted(() => {
  meta = document.createElement("meta");
  meta.name = "robots";
  meta.content = "noindex";
  document.head.appendChild(meta);
});
onBeforeUnmount(() => meta?.remove());
</script>
