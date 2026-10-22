<template>
  <section class="album-page fv-era tm-immersive" :class="{ 'fv-filtro-on': filtroAntigo }">
    <header class="album-cab">
      <nav aria-label="Caminho" class="album-migalhas">
        <ol>
          <li><router-link :to="{ name: 'fotos-albuns' }">Álbuns</router-link></li>
          <li aria-current="page">{{ album?.titulo || "..." }}</li>
        </ol>
      </nav>

      <TmErrorState v-if="erro" :titulo="naoEncontrado ? 'Álbum não encontrado' : 'Não foi possível carregar'" :texto="naoEncontrado ? 'Ele pode ter sido excluído.' : 'Verifique sua conexão e tente de novo.'" @retry="carregar" />
      <div v-else-if="album" class="album-aberto hx-ano">
        <div class="album-capa-aberta fv-moldura fv-vinheta">
          <img v-if="capaUrl" class="fv-img" :src="capaUrl" alt="" decoding="async" />
          <v-icon v-else size="56" aria-hidden="true">mdi-image-multiple-outline</v-icon>
        </div>
        <div class="album-info">
          <div class="album-titulo-linha">
            <h1 class="tm-title album-titulo">{{ album.titulo }}</h1>
            <v-btn v-if="auth.isMembro" icon variant="text" class="tm-touch" aria-label="Editar álbum" @click="formAberto = true">
              <v-icon>mdi-pencil-outline</v-icon>
            </v-btn>
          </div>
          <p v-if="album.descricao" class="album-descricao">{{ album.descricao }}</p>
          <p class="album-contagem">{{ pluralFotos(album.fotos_count ?? 0) }}<template v-if="anosAlbum"> · {{ anosAlbum }}</template></p>
          <div class="album-acoes">
            <v-btn v-if="auth.isMembro" variant="flat" color="primary" prepend-icon="mdi-image-plus" @click="seletorAberto = true">
              Adicionar fotos
            </v-btn>
            <v-btn v-if="auth.isMembro" variant="tonal" prepend-icon="mdi-upload" @click="explorador?.escolherArquivos()">Enviar novas</v-btn>
            <v-btn variant="tonal" prepend-icon="mdi-link-variant" @click="copiarLink">Compartilhar</v-btn>
            <v-btn v-if="auth.isMembro" variant="text" color="error" prepend-icon="mdi-delete-outline" @click="excluir">Excluir álbum</v-btn>
          </div>
        </div>
      </div>
      <div v-else class="album-fantasma" aria-busy="true"><span class="sr-only" role="status">Carregando álbum</span></div>
    </header>

    <FotosExplorador
      v-if="album"
      ref="explorador"
      :album-uuid="album.uuid"
      :agrupar="agrupar"
      vazio-titulo="Este álbum ainda está vazio"
      vazio-texto="Adicione fotos da galeria ou envie novas."
      @mudou="carregar"
    >
      <template #contexto>
        <div class="album-barra">
          <v-switch v-model="agrupar" label="Ordenar por data" color="primary" density="compact" hide-details inset />
          <v-spacer />
          <v-switch v-model="filtroAntigo" color="primary" density="compact" hide-details inset aria-label="Aplicar filtro de foto antiga">
            <template #label><v-icon size="18" class="mr-1" aria-hidden="true">mdi-image-filter-vintage</v-icon>Filtro antigo</template>
          </v-switch>
        </div>
      </template>
      <template #vazio-acoes>
        <v-btn v-if="auth.isMembro" variant="flat" color="primary" prepend-icon="mdi-image-plus" @click="seletorAberto = true">Adicionar fotos</v-btn>
      </template>
    </FotosExplorador>

    <AlbumFormDialog v-if="album" v-model="formAberto" :album="album" @salvo="(a) => (album = { ...album, ...a })" />
    <FotosSeletorDialog v-if="album" v-model="seletorAberto" :album-uuid="album.uuid" @adicionado="aoAdicionar" />
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { buscarAlbum, excluirAlbum } from "@/services/album.service";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import FotosExplorador from "@/components/fotos/FotosExplorador.vue";
import AlbumFormDialog from "@/components/fotos/AlbumFormDialog.vue";
import FotosSeletorDialog from "@/components/fotos/FotosSeletorDialog.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import { filtroAntigo, pluralFotos } from "@/composables/useEraFotosVideos";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { confirmar } = useConfirm();
const { tratar } = useApiError();
const notify = useNotify();

const album = ref(null);
const erro = ref(false);
const naoEncontrado = ref(false);
const agrupar = ref(false);
const formAberto = ref(false);
const seletorAberto = ref(false);
const explorador = ref(null);
const capaUrl = computed(() => album.value?.capa?.thumbnail_url || album.value?.capa?.url || null);
const anosAlbum = computed(() => {
  const { ano_inicio: i, ano_fim: f } = album.value ?? {};
  if (!i) return "";
  return !f || f === i ? String(i) : `${i}-${f}`;
});

async function carregar() {
  erro.value = false;
  naoEncontrado.value = false;
  try {
    album.value = await buscarAlbum(route.params.uuid);
    document.title = `${album.value.titulo} · Túnel do Tempo`;
  } catch (e) {
    erro.value = true;
    naoEncontrado.value = e?.response?.status === 404;
  }
}
watch(() => route.params.uuid, carregar);
onMounted(carregar);

function aoAdicionar() {
  carregar();
  explorador.value?.recarregar();
}

async function copiarLink() {
  try {
    await navigator.clipboard.writeText(`${location.origin}/fotos/albuns/${album.value.uuid}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

async function excluir() {
  const ok = await confirmar({
    titulo: `Excluir o álbum "${album.value.titulo}"?`,
    texto: "As fotos não são apagadas, só o álbum.",
    rotuloConfirmar: "Excluir álbum",
  });
  if (!ok) return;
  try {
    await excluirAlbum(album.value.uuid);
    notify.sucesso("Álbum excluído.");
    router.replace({ name: "fotos-albuns" });
  } catch (e) {
    tratar(e);
  }
}
</script>

<style scoped>
.album-page {
  min-height: 100dvh;
  padding-top: var(--tm-navbar-h);
  background: var(--tm-paper);
  color: var(--tm-ink);
}
.album-cab {
  max-width: 1400px;
  margin: 0 auto;
  padding: 16px 16px 8px;
}
.album-migalhas ol {
  display: flex;
  gap: 8px;
  list-style: none;
  margin: 0 0 8px;
  padding: 0;
  font-size: var(--tm-fs-sm);
}
.album-migalhas li + li::before {
  content: "/";
  margin-right: 8px;
  color: var(--tm-ink-muted);
}
.album-migalhas a {
  color: var(--tm-gold-text);
}
.album-migalhas li[aria-current] {
  font-weight: 600;
}
.album-titulo-linha {
  display: flex;
  align-items: center;
  gap: 8px;
}
.album-titulo {
  margin: 0;
  font-size: var(--tm-fs-3xl);
  overflow-wrap: anywhere;
}
.album-descricao {
  margin: 4px 0 0;
  max-width: 68ch;
}
.album-contagem {
  margin: 4px 0 12px;
  color: var(--tm-ink-muted);
}
.album-acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 8px;
}
.album-aberto {
  --hx-ano-h: 38;
  display: grid;
  grid-template-columns: minmax(220px, 320px) minmax(0, 1fr);
  gap: 24px;
  align-items: end;
  margin-top: 8px;
}
.album-capa-aberta {
  aspect-ratio: var(--fv-capa-album, 4 / 3);
  display: grid;
  place-items: center;
  color: var(--hx-ano-cor);
}
.album-capa-aberta img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
@media (max-width: 599px) {
  .album-aberto {
    grid-template-columns: minmax(0, 1fr);
    gap: 12px;
  }
}
.album-fantasma {
  height: 120px;
  border-radius: var(--tm-radius-md);
  background: var(--tm-paper-2);
}
.album-barra {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  height: 56px;
  padding: 0 16px;
}
</style>
