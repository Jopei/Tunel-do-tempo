<template>
  <!-- Camada decorativa da abertura. Animada pela timeline em home-coreografia.js (ganchos: .hi-fundo, .hi-ano, [data-leg], .hi-anel, .hi-calor, .hi-traco). -->
  <div v-if="ativa && modo === 'completa'" class="hi" aria-hidden="true">
    <div class="hi-fundo" />
    <div class="hi-calor" />
    <div class="hi-aneis">
      <span v-for="n in 5" :key="n" class="hi-anel" />
    </div>
    <p class="hi-legenda">
      <span data-leg="hoje">Hoje</span>
      <span data-leg="voltando">Voltando...</span>
      <span data-leg="chegada">{{ dataVideo }}</span>
    </p>
    <p class="hi-ano">{{ anoHoje }}</p>
    <div class="hi-traco" />
  </div>
</template>

<script setup>
import { homeConfig } from "./home-config";

defineProps({
  ativa: { type: Boolean, default: false },
  modo: { type: String, default: "nenhuma" },
});

// Valor inicial estático: a coreografia reescreve o texto do ano (contagem) sem passar pelo Vue.
const anoHoje = new Date().getFullYear();
const dataVideo = homeConfig.publicadoEmTexto;
</script>

<style scoped>
.hi {
  position: fixed;
  inset: 0;
  z-index: 5;
  display: grid;
  place-items: center;
  overflow: hidden;
  pointer-events: none;
  color: var(--tm-ink);
}
.hi-fundo,
.hi-calor,
.hi-traco,
.hi-anel,
.hi-ano {
  will-change: transform, opacity;
}
.hi-fundo {
  position: absolute;
  inset: 0;
  background: var(--tm-paper);
}
.hi-calor {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(199, 164, 58, 0.25), transparent 70%);
  opacity: 0;
}
.hi-aneis {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
}
.hi-anel {
  grid-area: 1 / 1;
  width: 18vmin;
  height: 18vmin;
  border: 1px solid var(--tm-gold);
  border-radius: 50%;
  opacity: 0;
}
.hi-legenda {
  position: absolute;
  top: calc(50% - clamp(4.5rem, 14vw, 9rem));
  left: 0;
  right: 0;
  height: 1.5em;
  margin: 0;
  text-align: center;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.hi-legenda span {
  position: absolute;
  inset: 0;
  opacity: 0;
}
.hi-legenda span[data-leg="hoje"] {
  opacity: 1;
}
.hi-ano {
  margin: 0;
  font-family: var(--tm-font-title);
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  font-size: clamp(5rem, 22vw, 14rem);
  line-height: 1;
  min-width: 4ch;
  text-align: center;
  color: var(--tm-gold-text);
}
.hi-traco {
  position: absolute;
  top: 50%;
  left: 30vw;
  width: 40vw;
  height: 2px;
  background: var(--tm-gold);
  opacity: 0;
}
</style>
