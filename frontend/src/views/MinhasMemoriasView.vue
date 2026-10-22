<template>
  <section class="pag tm-immersive historias-era">
    <div class="painel">
      <header class="cabeca">
        <h1 class="tm-title">Minhas memórias</h1>
        <p class="sub" aria-live="polite">As histórias que você guardou.</p>
      </header>

      <TmSkeletonGrid v-if="lista.carregando.value && !lista.itens.value.length" :quantidade="6" tipo="article" minimo="260px" />
      <TmErrorState v-else-if="lista.erro.value && !lista.itens.value.length" texto="Não conseguimos carregar suas memórias." @retry="lista.recarregar" />
      <TmEmptyState
        v-else-if="lista.vazio.value"
        icone="mdi-heart-outline"
        titulo="Você ainda não guardou nenhuma história."
        texto="Abra uma história e toque em “Guardar na memória”."
      >
        <v-btn to="/historias" variant="flat" color="primary">Ver histórias</v-btn>
      </TmEmptyState>

      <ul v-else class="grid">
        <li v-for="h in lista.itens.value" :key="h.uuid" class="item">
          <HistoriaCard :historia="{ ...h, usuarios: h.usuarios ?? [], favoritado: true }" compacto @curtir="descurtir(h)" @compartilhar="compartilhar(h)" />
        </li>
      </ul>

      <div v-if="lista.temMais.value" class="mais">
        <v-btn variant="tonal" :loading="lista.carregando.value" @click="lista.carregarProxima()">Carregar mais</v-btn>
      </div>
      <div :ref="lista.sentinela" aria-hidden="true"></div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { listarFavoritos, desfavoritarHistoria } from "@/services/favorito.service";
import { useNotify } from "@/composables/useNotify";
import "@/assets/historias-era.css";
import { useListaPaginada } from "@/composables/useListaPaginada";
import HistoriaCard from "@/components/historias/HistoriaCard.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const lista = useListaPaginada((page) => listarFavoritos({ page, per_page: 15 }));
const notify = useNotify();

// Nesta lista todo item está curtido: "Curtido" remove das memórias.
async function descurtir(h) {
  try {
    await desfavoritarHistoria(h.uuid);
    lista.itens.value = lista.itens.value.filter((i) => i.uuid !== h.uuid);
    notify.sucesso("Removida das suas memórias.");
  } catch {
    notify.erro("Não foi possível atualizar. Tente de novo.");
  }
}

async function compartilhar(h) {
  try {
    await navigator.clipboard.writeText(`${location.origin}/historias/${h.uuid}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

onMounted(() => lista.carregarProxima());
</script>

<style scoped>
.pag {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
}
.painel {
  max-width: 1100px;
  margin: 0 auto;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
.cabeca h1 {
  font-size: var(--tm-fs-3xl);
}
.sub {
  color: var(--tm-ink-muted);
  margin: 0 0 var(--tm-space-5);
}
.grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--tm-space-4);
}
.item {
  display: flex;
}
.mais {
  text-align: center;
  margin-top: var(--tm-space-5);
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
