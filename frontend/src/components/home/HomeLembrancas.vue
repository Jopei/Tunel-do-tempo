<template>
  <section class="lemb" aria-labelledby="lemb-t" data-intro="lembrancas">
    <h2 id="lemb-t" class="lemb-titulo">Lembranças</h2>
    <ul class="lemb-lista">
      <li v-for="(c, i) in lembrancas" :key="c.id" class="lemb-item" :class="{ 'lemb-item--extra': i > 0 && !todas }">
        <span class="lemb-avatar" aria-hidden="true">{{ c.nome.charAt(0) }}</span>
        <div class="lemb-balao">
          <p class="lemb-topo">
            <strong>{{ c.nome }}</strong>
            <span class="lemb-data">{{ c.data }}</span>
          </p>
          <p class="lemb-texto">{{ c.texto }}</p>
        </div>
      </li>
    </ul>
    <button type="button" class="lemb-mais" :aria-expanded="todas" @click="todas = !todas">
      {{ todas ? "Ver menos" : "Ver mais lembranças" }}
    </button>
    <router-link :to="destino" class="lemb-link">
      Ver as lembranças de verdade
      <v-icon v-if="!auth.logado" size="16">mdi-lock</v-icon>
      <span v-if="!auth.logado" class="sr-only">Exige entrar</span>
    </router-link>
  </section>
</template>

<script setup>
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { lembrancas } from "./home-config";

const auth = useAuthStore();
const todas = ref(false);
const destino = computed(() => (auth.logado ? "/historias" : { name: "login", query: { redirect: "/historias" } }));
</script>

<style scoped>
.lemb-titulo {
  margin: 0 0 var(--tm-space-3);
  font-family: var(--tm-font-title);
  font-size: var(--tm-fs-xl);
  font-weight: 600;
}
.lemb-lista {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--tm-space-3);
}
.lemb-item {
  display: flex;
  gap: var(--tm-space-3);
  align-items: flex-start;
}
.lemb-avatar {
  flex: 0 0 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--tm-gold);
  color: #2a2112;
  font-weight: 600;
}
.lemb-balao {
  background: var(--hx-balao, var(--tm-paper-2));
  border-radius: var(--tm-radius-sm);
  padding: var(--tm-space-2) var(--tm-space-3);
  min-width: 0;
}
.lemb-topo {
  margin: 0;
  display: flex;
  gap: var(--tm-space-2);
  align-items: baseline;
}
.lemb-topo strong {
  font-weight: 600;
}
.lemb-data {
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
}
.lemb-texto {
  margin: 2px 0 0;
  font-size: 15px;
  line-height: 1.5;
}
.lemb-mais {
  display: none;
  min-height: 44px;
  margin-top: var(--tm-space-2);
  padding: 0 var(--tm-space-3);
  background: none;
  border: 0;
  color: var(--tm-gold-text);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.lemb-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  margin-top: var(--tm-space-2);
  color: var(--tm-gold-text);
  font-weight: 600;
}
@media (max-width: 599px) {
  .lemb-item--extra {
    display: none;
  }
  .lemb-mais {
    display: block;
  }
}
</style>
