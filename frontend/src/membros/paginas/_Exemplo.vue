<!--
  EXEMPLO de página de membro. Copie este arquivo para `<slug>.vue` (ex.: `ze-paulo.vue`),
  ajuste, e registre em ../registry.js:

    "ze-paulo": {
      componente: () => import("./paginas/ze-paulo.vue"),
      simbolo: "/membros/ze-paulo/simbolo.svg",
      cores: { cor: "#C7A43A", texto: "#7A5D0E", hero: "linear-gradient(160deg,#3a2b0b,#14110B)" },
    },

  Regras: (1) troque só variáveis --membro-*, nunca os seletores .mp-* da casca;
  (2) confira contraste texto/fundo; (3) hero com imagem precisa de overlay escuro.
  O prefixo "_" no nome evita que ele seja usado por engano: não está no registry.

  Slots da casca (todos opcionais): topo-extra, hero-fundo, simbolo, hero-conteudo, hero-acoes,
  depois-hero, historia-antes, historia-pessoal, historia-depois, depois-historia,
  midia (ou midia-fotos / midia-videos / midia-musicas), depois-midia, historias,
  depois-historias, rodape-membro.
-->
<template>
  <MembroPagina v-bind="$attrs" :membro="membro" :tema="temaMembro" :simbolo="simbolo" :anterior="anterior" :proximo="proximo">
    <!-- Fundo do hero: imagem + overlay -->
    <template #hero-fundo>
      <div class="exemplo-fundo" />
    </template>

    <!-- Bloco livre logo abaixo do hero -->
    <template #depois-hero>
      <blockquote class="exemplo-citacao">“Uma frase marcante deste membro.”</blockquote>
    </template>

    <!-- Mídia própria (fotos em colagem, vídeos, etc.) -->
    <template #midia>
      <h2 class="exemplo-titulo">Momentos</h2>
      <img src="/login/7.jpg" alt="Descreva a foto aqui" width="320" height="240" loading="lazy" />
    </template>
  </MembroPagina>
</template>

<script setup>
import MembroPagina from "../MembroPagina.vue";

defineOptions({ inheritAttrs: false });
// MembroView passa estas props a qualquer página do registry.
defineProps({
  membro: { type: Object, default: null },
  tema: { type: Object, default: () => ({}) },
  simbolo: { type: String, default: "" },
  anterior: { type: String, default: null },
  proximo: { type: String, default: null },
});

// Variáveis do tema (equivalente a escrever --membro-* num <style>).
const temaMembro = { cor: "#8A3B12", texto: "#6B2D0D" };
</script>

<style scoped>
.exemplo-fundo {
  position: absolute;
  inset: 0;
  background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65)), url("/login/7.jpg") center/cover;
}
.exemplo-citacao {
  max-width: 68ch;
  margin: var(--tm-space-6) auto 0;
  padding: 0 var(--tm-space-4);
  font-style: italic;
}
.exemplo-titulo {
  font-family: var(--membro-fonte-titulo);
  color: var(--membro-cor-texto);
}
</style>
