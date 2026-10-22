<template>
  <section class="albuns-page fv-era tm-immersive" :class="{ 'fv-filtro-on': filtroAntigo }">
    <h1 class="sr-only">Álbuns</h1>
    <div class="albuns-barra">
      <FotosAbas v-model:q="buscaInput" ativa="albuns" rotulo-busca="Buscar álbuns" logado :pode-enviar="false" :opcoes="false" />
    </div>

    <div class="albuns-corpo">
      <TmErrorState v-if="lista.erro.value && !lista.itens.value.length" texto="Não conseguimos carregar os álbuns." @retry="lista.recarregar()" />

      <div v-else-if="lista.carregando.value && !lista.itens.value.length" class="albuns-grade" aria-busy="true">
        <span v-for="n in 8" :key="n" class="album-fantasma" aria-hidden="true"></span>
        <span class="sr-only" role="status">Carregando álbuns</span>
      </div>

      <template v-else>
        <ul v-if="!lista.vazio.value" class="albuns-grade" aria-label="Álbuns">
          <li v-if="auth.isMembro && !q">
            <button type="button" class="album-novo" @click="formAberto = true">
              <v-icon size="32" aria-hidden="true">mdi-plus</v-icon>
              <span>Novo álbum</span>
            </button>
          </li>
          <li v-for="(a, i) in lista.itens.value" :key="a.uuid" class="album-item" :style="{ '--i': i % 12 }">
            <AlbumCard :album="a" />
          </li>
        </ul>

        <TmEmptyState
          v-if="lista.vazio.value"
          class="fv-vazio"
          moldura
          :icone="q ? 'mdi-image-search-outline' : 'mdi-image-multiple-outline'"
          :titulo="q ? `Nenhum álbum com '${q}'` : 'Nenhum álbum por aqui'"
          :texto="q ? 'Tente outras palavras.' : 'Álbuns juntam fotos de um mesmo momento: uma viagem, um verão, uma festa.'"
        >
          <v-btn v-if="q" variant="tonal" @click="buscaInput = ''">Limpar busca</v-btn>
          <v-btn v-else-if="auth.isMembro" variant="flat" color="primary" prepend-icon="mdi-plus" @click="formAberto = true">Criar álbum</v-btn>
        </TmEmptyState>

        <div v-if="lista.erro.value" class="albuns-aviso" role="alert">
          Não deu para carregar mais.
          <v-btn size="small" variant="tonal" @click="lista.carregarProxima()">Tentar de novo</v-btn>
        </div>
        <div v-else-if="lista.temMais.value && !lista.carregando.value" class="albuns-aviso">
          <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
        </div>
      </template>
      <div :ref="lista.sentinela" aria-hidden="true"></div>
    </div>

    <AlbumFormDialog v-model="formAberto" @salvo="(a) => router.push({ name: 'fotos-album', params: { uuid: a.uuid } })" />
  </section>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import { listarAlbuns } from "@/services/album.service";
import { useAuthStore } from "@/stores/auth";
import { useListaPaginada } from "@/composables/useListaPaginada";
import FotosAbas from "@/components/fotos/FotosAbas.vue";
import AlbumCard from "@/components/fotos/AlbumCard.vue";
import AlbumFormDialog from "@/components/fotos/AlbumFormDialog.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import { filtroAntigo } from "@/composables/useEraFotosVideos";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";

const auth = useAuthStore();
const router = useRouter();
const buscaInput = ref("");
const q = ref("");
const formAberto = ref(false);

const lista = useListaPaginada((p) => listarAlbuns(p, { per_page: 24, ...(q.value ? { q: q.value } : {}) }));

let t = null;
watch(buscaInput, (v) => {
  clearTimeout(t);
  t = setTimeout(() => {
    const novo = (v ?? "").trim();
    if (novo !== q.value) {
      q.value = novo;
      lista.recarregar();
    }
  }, 300);
});

lista.carregarProxima();
</script>

<style scoped>
.albuns-page {
  min-height: 100dvh;
  padding-top: var(--tm-navbar-h);
  background: var(--tm-paper);
  color: var(--tm-ink);
}
.albuns-barra {
  position: sticky;
  top: var(--tm-navbar-h);
  z-index: 5;
  height: 56px;
  background: var(--tm-paper);
  border-bottom: 1px solid var(--tm-border);
}
.albuns-corpo {
  max-width: 1400px;
  margin: 0 auto;
  padding: 16px 16px 80px;
}
.albuns-grade {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}
.album-novo {
  width: 100%;
  aspect-ratio: var(--fv-capa-album, 4 / 3);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 2px dashed var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: transparent;
  color: var(--tm-ink);
  font-weight: 600;
  cursor: pointer;
}
.album-novo:hover {
  background: var(--tm-paper-2);
}
.album-fantasma {
  aspect-ratio: var(--fv-capa-album, 4 / 3);
  border-radius: var(--tm-radius-md);
  background: var(--tm-paper-2);
}
.albuns-aviso {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 16px;
}
@media (max-width: 599px) {
  .albuns-grade {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
}
</style>
