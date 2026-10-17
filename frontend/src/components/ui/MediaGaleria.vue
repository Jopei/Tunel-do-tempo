<template>
  <section class="galeria-page tm-immersive">
    <div class="painel">
      <header class="cabeca">
        <div>
          <h1 class="tm-title">{{ ehFoto ? "Galeria de fotos" : "Galeria de vídeos" }}</h1>
          <p class="subtitulo" aria-live="polite">{{ subtitulo }}</p>
        </div>
        <div v-if="auth.isMembro" class="cabeca-acoes">
          <v-btn
            :variant="selecao ? 'flat' : 'tonal'"
            :prepend-icon="selecao ? 'mdi-close' : 'mdi-checkbox-multiple-outline'"
            :aria-pressed="selecao"
            @click="alternarSelecao"
          >
            {{ selecao ? "Sair da seleção" : "Selecionar" }}
          </v-btn>
          <v-btn variant="flat" color="primary" prepend-icon="mdi-plus" @click="abrirCriar">
            {{ ehFoto ? "Adicionar foto" : "Adicionar vídeo" }}
          </v-btn>
        </div>
      </header>

      <div class="ferramentas">
        <v-text-field
          v-model="buscaInput"
          :label="ehFoto ? 'Buscar fotos' : 'Buscar vídeos'"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          autocomplete="off"
          class="busca"
        />
        <v-select v-model="ordem" :items="ORDENS" item-title="titulo" item-value="valor" label="Ordenar por" hide-details class="ordem" />
      </div>

      <div v-if="selecao" class="barra-selecao" role="status">
        <span>{{ selecionados.length }} selecionad{{ selecionados.length === 1 ? "o" : "os" }}</span>
        <v-btn
          variant="flat"
          color="error"
          prepend-icon="mdi-delete-outline"
          :disabled="!selecionados.length"
          :loading="excluindoLote"
          @click="excluirSelecionados"
        >
          Excluir selecionados
        </v-btn>
      </div>

      <TmSkeletonGrid v-if="lista.carregando.value && !lista.itens.value.length" :quantidade="12" :minimo="ehFoto ? '160px' : '220px'" />

      <TmErrorState
        v-else-if="lista.erro.value && !lista.itens.value.length"
        :texto="ehFoto ? 'Não conseguimos carregar as fotos.' : 'Não conseguimos carregar os vídeos.'"
        @retry="lista.recarregar"
      />

      <TmEmptyState
        v-else-if="lista.vazio.value"
        :icone="ehFoto ? 'mdi-image-off-outline' : 'mdi-video-off-outline'"
        :titulo="q ? 'Nada encontrado' : ehFoto ? 'Nenhuma foto ainda' : 'Nenhum vídeo ainda'"
        :texto="q ? 'Tente outras palavras.' : 'Quando alguém adicionar, aparece aqui.'"
      >
        <v-btn v-if="q" variant="tonal" @click="buscaInput = ''">Limpar busca</v-btn>
        <v-btn v-else-if="auth.isMembro" variant="flat" color="primary" @click="abrirCriar">
          {{ ehFoto ? "Adicionar foto" : "Adicionar vídeo" }}
        </v-btn>
      </TmEmptyState>

      <template v-else>
        <MediaGrid
          :itens="visiveis"
          :tipo="tipo"
          :pode-editar="auth.isMembro"
          :selecao="selecao"
          :selecionados="selecionados"
          @abrir="abrirViewer"
          @editar="abrirEditar"
          @excluir="excluir"
          @alternar="alternarItem"
        />

        <div v-if="lista.erro.value" class="erro-mais" role="alert">
          Não foi possível carregar mais.
          <v-btn size="small" variant="tonal" @click="lista.carregarProxima()">Tentar novamente</v-btn>
        </div>
        <div v-else-if="lista.carregando.value" class="carregando-mais" aria-busy="true">
          <v-progress-circular indeterminate size="28" color="primary" />
          <span class="sr-only">Carregando mais</span>
        </div>
        <div v-else-if="lista.temMais.value" class="mais">
          <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
        </div>
      </template>

      <div :ref="lista.sentinela" aria-hidden="true"></div>
      <p class="sr-only" aria-live="polite">{{ lista.anuncio.value }}</p>
    </div>

    <TmLightbox
      v-model="viewer"
      :itens="visiveis"
      :inicio="inicioViewer"
      :tipo="tipo"
      :baixavel="ehFoto"
      :compartilhavel="ehFoto"
      @mudou="aoMudarViewer"
      @compartilhar="copiarLink"
    />

    <MediaEditDialog
      v-model="dialogEditar"
      :item="editando"
      :tipo="tipo"
      :salvando="salvando"
      :erros="errosEdicao"
      @salvar="salvarEdicao"
    />

    <CriarFotoModal v-if="ehFoto && criarMontado" v-model="criarAberto" />
    <CriarVideoModal v-if="!ehFoto && criarMontado" v-model="criarAberto" />
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import { listarFotos, excluirFoto, atualizarFoto, buscarDetalhesFoto } from "@/services/foto.service";
import { listarVideos, excluirVideo, atualizarVideo } from "@/services/video.service";
import { urlFoto } from "@/services/midia.url";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { fotoCriada, videoCriado } from "@/composables/useMidiaEventos";
import MediaGrid from "@/components/ui/MediaGrid.vue";
import MediaEditDialog from "@/components/ui/MediaEditDialog.vue";
import TmLightbox from "@/components/ui/TmLightbox.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const CriarFotoModal = defineAsyncComponent(() => import("@/components/fotos/CriarFotoModal.vue"));
const CriarVideoModal = defineAsyncComponent(() => import("@/components/videos/CriarVideoModal.vue"));

const props = defineProps({ tipo: { type: String, default: "foto" } });

const ORDENS = [
  { titulo: "Mais recentes", valor: "recentes" },
  { titulo: "Mais antigas", valor: "antigas" },
  { titulo: "Por título", valor: "titulo" },
];

const ehFoto = computed(() => props.tipo === "foto");
const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { confirmar } = useConfirm();
const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const buscaInput = ref(String(route.query.q ?? ""));
const q = ref(buscaInput.value.trim());
const ordem = ref(String(route.query.ordem ?? "recentes"));

const listar = (pagina) => {
  const params = {};
  if (q.value) params.q = q.value;
  if (ordem.value !== "recentes") params.ordem = ordem.value;
  return (ehFoto.value ? listarFotos : listarVideos)(pagina, params);
};
const lista = useListaPaginada(listar);

// Busca também sobre o que já foi carregado (garante resultado mesmo sem filtro no servidor).
function normalizar(s) {
  return String(s ?? "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "");
}
const visiveis = computed(() => {
  const termo = normalizar(q.value);
  if (!termo) return lista.itens.value;
  return lista.itens.value.filter((i) => normalizar(`${i.titulo} ${i.descricao}`).includes(termo));
});

const subtitulo = computed(() => {
  if (lista.total.value == null) return "";
  const n = lista.total.value;
  const nome = ehFoto.value ? (n === 1 ? "foto" : "fotos") : n === 1 ? "vídeo" : "vídeos";
  return q.value ? `${visiveis.value.length} ${nome} encontrados` : `${n} ${nome}`;
});

let debounce = null;
watch(buscaInput, (v) => {
  clearTimeout(debounce);
  debounce = setTimeout(() => {
    q.value = (v ?? "").trim();
  }, 300);
});
watch([q, ordem], () => {
  const query = {};
  if (q.value) query.q = q.value;
  if (ordem.value !== "recentes") query.ordem = ordem.value;
  if (route.query.foto) query.foto = route.query.foto;
  router.replace({ query });
  lista.recarregar();
});

// Ao criar mídia por outro lugar (navbar), recarrega.
watch(fotoCriada, () => ehFoto.value && lista.recarregar());
watch(videoCriado, () => !ehFoto.value && lista.recarregar());

/* ---------- criar ---------- */
const criarMontado = ref(false);
const criarAberto = ref(false);
function abrirCriar() {
  criarMontado.value = true;
  criarAberto.value = true;
}

/* ---------- visualizador ---------- */
const viewer = ref(false);
const inicioViewer = ref(0);
const itemExtra = ref(null); // foto aberta por deep link e fora da página carregada

function abrirViewer(i) {
  inicioViewer.value = i;
  viewer.value = true;
}

function aoMudarViewer(item) {
  if (!ehFoto.value || !item) return;
  router.replace({ query: { ...route.query, foto: item.uuid } });
}

watch(viewer, (aberto) => {
  if (!aberto && route.query.foto) {
    const { foto, ...resto } = route.query;
    void foto;
    router.replace({ query: resto });
  }
});

async function copiarLink(item) {
  try {
    await navigator.clipboard.writeText(`${location.origin}/galeria-fotos?foto=${item.uuid}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

// Deep link ?foto=uuid
async function abrirDeepLink() {
  const uuid = route.query.foto;
  if (!ehFoto.value || !uuid) return;
  const existente = lista.itens.value.findIndex((f) => f.uuid === uuid);
  if (existente >= 0) {
    abrirViewer(existente);
    return;
  }
  try {
    const d = await buscarDetalhesFoto(uuid);
    itemExtra.value = {
      uuid,
      titulo: d.titulo ?? "",
      descricao: d.descricao ?? "",
      url: d.url ?? urlFoto(uuid),
    };
    lista.itens.value.unshift(itemExtra.value);
    abrirViewer(0);
  } catch {
    notify.erro("Não encontramos essa foto.");
  }
}

/* ---------- editar ---------- */
const dialogEditar = ref(false);
const editando = ref(null);
const salvando = ref(false);
const errosEdicao = ref({});

function abrirEditar(item) {
  editando.value = item;
  errosEdicao.value = {};
  dialogEditar.value = true;
}

async function salvarEdicao(form) {
  if (!editando.value) return;
  salvando.value = true;
  errosEdicao.value = {};
  try {
    await (ehFoto.value ? atualizarFoto : atualizarVideo)(editando.value.uuid, form);
    const alvo = lista.itens.value.find((i) => i.uuid === editando.value.uuid);
    if (alvo) Object.assign(alvo, form);
    notify.sucesso(ehFoto.value ? "Foto atualizada." : "Vídeo atualizado.");
    dialogEditar.value = false;
  } catch (e) {
    const info = tratar(e);
    errosEdicao.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    salvando.value = false;
  }
}

/* ---------- excluir ---------- */
async function excluir(item) {
  const ok = await confirmar({
    titulo: ehFoto.value ? "Excluir esta foto?" : "Excluir este vídeo?",
    texto: "Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  try {
    await (ehFoto.value ? excluirFoto : excluirVideo)(item.uuid);
    lista.itens.value = lista.itens.value.filter((i) => i.uuid !== item.uuid);
    notify.sucesso(ehFoto.value ? "Foto excluída." : "Vídeo excluído.");
  } catch (e) {
    tratar(e);
  }
}

/* ---------- seleção múltipla ---------- */
const selecao = ref(false);
const selecionados = ref([]);
const excluindoLote = ref(false);

function alternarSelecao() {
  selecao.value = !selecao.value;
  selecionados.value = [];
}
function alternarItem(uuid) {
  const i = selecionados.value.indexOf(uuid);
  if (i >= 0) selecionados.value.splice(i, 1);
  else selecionados.value.push(uuid);
}

async function excluirSelecionados() {
  const n = selecionados.value.length;
  const ok = await confirmar({
    titulo: `Excluir ${n} ${ehFoto.value ? (n === 1 ? "foto" : "fotos") : n === 1 ? "vídeo" : "vídeos"}?`,
    texto: "Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  excluindoLote.value = true;
  const apagar = ehFoto.value ? excluirFoto : excluirVideo;
  const resultados = await Promise.allSettled(selecionados.value.map((uuid) => apagar(uuid)));
  const apagados = selecionados.value.filter((_, i) => resultados[i].status === "fulfilled");
  lista.itens.value = lista.itens.value.filter((i) => !apagados.includes(i.uuid));
  const falhas = n - apagados.length;
  selecionados.value = selecionados.value.filter((u) => !apagados.includes(u));
  excluindoLote.value = false;
  if (falhas) notify.erro(`${falhas} não puderam ser excluídos.`);
  else {
    notify.sucesso("Itens excluídos.");
    selecao.value = false;
  }
}

onMounted(async () => {
  await lista.carregarProxima();
  abrirDeepLink();
});
</script>

<style scoped>
.galeria-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/cadastrar-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
}
.painel {
  max-width: 1400px;
  margin: 0 auto;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
.cabeca {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: var(--tm-space-4);
  margin-bottom: var(--tm-space-4);
}
.cabeca h1 {
  font-size: var(--tm-fs-3xl);
}
.subtitulo {
  margin: 0;
  color: var(--tm-ink-muted);
  min-height: 1.6em;
}
.cabeca-acoes {
  display: flex;
  gap: var(--tm-space-2);
  flex-wrap: wrap;
}
.ferramentas {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--tm-space-3);
  margin-bottom: var(--tm-space-5);
}
.barra-selecao {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--tm-space-3);
  padding: var(--tm-space-3) var(--tm-space-4);
  margin-bottom: var(--tm-space-4);
  border-radius: var(--tm-radius-sm);
  background: rgb(var(--v-theme-surface-variant));
  font-weight: 600;
}
.mais,
.carregando-mais,
.erro-mais {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--tm-space-3);
  margin-top: var(--tm-space-5);
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
  .ferramentas {
    grid-template-columns: 1fr;
  }
}
</style>
