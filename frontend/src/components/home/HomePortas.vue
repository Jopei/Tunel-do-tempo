<template>
  <aside class="portas" aria-labelledby="portas-t" data-intro="portas">
    <h2 id="portas-t" class="portas-titulo">Continue assistindo</h2>
    <ul class="portas-lista">
      <li v-for="p in portas" :key="p.id">
        <router-link :to="destino(p)" class="porta" :data-porta="p.id">
          <span class="porta-thumb" aria-hidden="true">
            <v-icon size="32">{{ p.icone }}</v-icon>
            <v-icon v-if="trancada(p)" class="porta-cadeado" size="16">mdi-lock</v-icon>
          </span>
          <span class="porta-texto">
            <span class="porta-nome">{{ p.titulo }}</span>
            <span class="porta-sub">{{ p.sub }}</span>
          </span>
          <span v-if="trancada(p)" class="sr-only">Exige entrar</span>
        </router-link>
      </li>
    </ul>
  </aside>
</template>

<script setup>
import { useAuthStore } from "@/stores/auth";
import { portas } from "./home-config";

const auth = useAuthStore();
const trancada = (p) => p.trava && !auth.logado;
const destino = (p) => (trancada(p) ? { name: "login", query: { redirect: p.para } } : p.para);
</script>

<style scoped>
.portas-titulo {
  margin: 0 0 var(--tm-space-3);
  font-family: var(--tm-font-body);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--tm-ink-muted);
}
.portas-lista {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--tm-space-3);
}
.porta {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  min-height: 72px;
  padding: var(--tm-space-3);
  background: var(--tm-paper-2);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  color: var(--tm-ink);
  text-decoration: none;
  transition:
    border-color var(--tm-dur-fast) var(--tm-ease),
    transform var(--tm-dur-fast) var(--tm-ease);
}
.porta:hover,
.porta:focus-visible {
  border-color: var(--tm-gold);
  transform: translateY(-2px);
}
.porta-thumb {
  position: relative;
  flex: 0 0 96px;
  height: 54px;
  display: grid;
  place-items: center;
  border-radius: var(--tm-radius-xs);
  background: linear-gradient(135deg, rgba(199, 164, 58, 0.45), rgba(42, 33, 18, 0.9));
  color: var(--tm-ink);
}
.porta-cadeado {
  position: absolute;
  right: 4px;
  bottom: 4px;
  padding: 2px;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.7);
}
.porta-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.porta-nome {
  font-family: var(--tm-font-title);
  font-size: 17px;
  line-height: 1.25;
}
.porta-sub {
  font-size: 13px;
  color: var(--tm-ink-muted);
}

@media (min-width: 600px) and (max-width: 1099px) {
  .portas-lista {
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 599px) {
  .porta-thumb {
    flex-basis: 72px;
    height: 72px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .porta {
    transition: none;
  }
  .porta:hover,
  .porta:focus-visible {
    transform: none;
  }
}
</style>
