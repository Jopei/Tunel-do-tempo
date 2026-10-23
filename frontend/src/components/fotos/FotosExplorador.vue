<template>
  <div ref="raiz" class="exp" :class="{ 'com-scrubber': mostrarScrubber && mdAndUp }">
    <div class="exp-barra" :class="{ 'em-selecao': sel.ativa.value }">
      <Transition name="barra-troca" mode="out-in">
        <FotosBarraSelecao
          v-if="sel.ativa.value"
          key="selecao"
          :quantidade="sel.quantidade.value"
          :pode-editar="podeEditar"
          :logado="auth.logado"
          :em-album="!!albumUuid"
          @limpar="sel.limpar()"
          @album="abrirSeletorAlbum(sel.lista.value)"
          @excluir="excluirSelecionadas"
          @remover="removerDoAlbum"
          @capa="usarComoCapa"
        />
        <div v-else key="contexto" class="exp-ctx">
          <slot name="contexto" :capitulos="capitulosDisponiveis" :ir-capitulo="irParaCapitulo" />
        </div>
      </Transition>
    </div>

    <FotosDestaques
      v-if="destaques"
      class="exp-destaques"
      :logado="auth.logado"
      :ordem="ordem"
      @abrir-foto="abrirContexto"
      @ir-capitulo="irParaCapitulo"
    />

    <TmErrorState
      v-if="lista.erro.value && !lista.itens.value.length"
      texto="Não conseguimos carregar as fotos."
      @retry="recarregar"
    />

    <div v-else-if="lista.carregando.value && !lista.itens.value.length" class="exp-corpo">
      <FotosEsqueleto :altura="Math.round(alturaBase * escala)" />
      <span class="sr-only" role="status">Carregando fotos</span>
    </div>

    <TmEmptyState
      v-else-if="lista.vazio.value"
      class="fv-vazio"
      moldura
      :icone="q ? 'mdi-image-search-outline' : 'mdi-image-outline'"
      :titulo="q ? `Nenhuma foto com '${q}'` : tituloVazio"
      :texto="q ? 'Tente um nome, um ano (ex.: 2014) ou outra palavra.' : textoVazio"
    >
      <v-btn v-if="q" variant="tonal" @click="$emit('limpar-busca')">Limpar busca</v-btn>
      <slot v-else name="vazio-acoes">
        <v-btn v-if="podeEditar" variant="flat" color="primary" prepend-icon="mdi-upload" @click="escolherArquivos">Enviar fotos</v-btn>
      </slot>
    </TmEmptyState>

    <div v-else class="exp-corpo">
      <FotosGrade
        ref="grade"
        :grupos="grupos"
        :selecionados="sel.marcados.value"
        :selecao-ativa="sel.ativa.value"
        :sem-cabecalho="!agrupar"
        :escala="escala"
        :capitulos="capitulosAtivos"
        :totais-ano="totaisAno"
        :ocupado="lista.carregando.value || saltando"
        :rotulo-grade="albumUuid ? 'Fotos do álbum' : 'Fotos'"
        @abrir="abrirLightbox"
        @alternar="sel.alternar"
        @intervalo="sel.intervalo"
        @grupo="sel.alternarGrupo"
        @longo="sel.marcar"
        @mes-visivel="(m) => (mesAtual = m)"
      />

      <FotosEsqueleto v-if="lista.carregando.value && lista.itens.value.length" :grupos="1" :altura="Math.round(alturaBase * escala)" />

      <div v-if="lista.erro.value" class="exp-aviso" role="alert">
        Não deu para carregar mais.
        <v-btn size="small" variant="tonal" @click="lista.carregarProxima()">Tentar de novo</v-btn>
      </div>
      <div v-else-if="lista.temMais.value && !lista.carregando.value" class="exp-aviso">
        <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
      </div>
      <div :ref="lista.sentinela" aria-hidden="true"></div>
    </div>

    <FotosScrubber v-if="mostrarScrubber" :meses="meses" :atual="mesAtual" @ir="irParaMes" />
    <p class="sr-only" role="status" aria-live="polite">{{ anuncioBusca }}</p>

    <FotoLightbox
      v-model="lbAberto"
      :itens="itensLightbox"
      :inicio="lbInicio"
      :pode-editar="podeEditar"
      :logado="auth.logado"
      @mudou="aoMudarLightbox"
      @compartilhar="copiarLink"
      @album="(f) => abrirSeletorAlbum([f.uuid])"
      @excluir="excluirUma"
      @editado="aplicarEdicao"
    />

    <AlbumSeletorDialog v-if="seletorMontado" v-model="seletorAberto" :uuids="uuidsAlbum" @adicionado="aoAdicionarAlbum" />

    <input ref="inputArquivos" type="file" class="sr-only" tabindex="-1" accept="image/*" multiple aria-label="Escolher fotos para enviar" @change="aoEscolherArquivos" />

    <Transition name="exp-drop-fade">
      <div v-if="arrastando && podeEditar" class="exp-drop" aria-hidden="true">
        <div class="exp-drop-caixa">
          <v-icon size="48">mdi-cloud-upload-outline</v-icon>
          <strong>Solte as fotos para enviar</strong>
          <span v-if="albumUuid">Elas entram neste álbum.</span>
        </div>
      </div>
    </Transition>

    <v-progress-linear v-if="saltando" class="exp-salto" indeterminate color="primary" height="3" aria-label="Indo para a data" />
    <p class="sr-only" role="status" aria-live="polite">{{ anuncio }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import { listarFotos, listarLinhaDoTempo, excluirFoto, excluirFotosLote, buscarDetalhesFoto } from "@/services/foto.service";
import { removerFotosAlbum, atualizarAlbum } from "@/services/album.service";
import { urlFoto } from "@/services/midia.url";
import { useAuthStore } from "@/stores/auth";
import { useUploadStore } from "@/stores/upload";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useSelecao } from "@/composables/useSelecao";
import { useDropArquivos } from "@/composables/useDropArquivos";
import { fotoCriada } from "@/composables/useMidiaEventos";
import { prefereMenosMovimento } from "@/composables/useTema";
import { agruparPorDia, diaDaFoto } from "@/composables/useFotoDatas";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import FotosGrade from "./FotosGrade.vue";
import FotosEsqueleto from "./FotosEsqueleto.vue";
import FotosScrubber from "./FotosScrubber.vue";
import FotosBarraSelecao from "./FotosBarraSelecao.vue";
import FotoLightbox from "./FotoLightbox.vue";
import FotosDestaques from "./FotosDestaques.vue";
import { chaveDoAno, anoDoTexto, pluralFotos, ordemCapitulos, metaCapitulo } from "@/composables/useEraFotosVideos";

const AlbumSeletorDialog = defineAsyncComponent(() => import("./AlbumSeletorDialog.vue"));

const MAX_LOTE = 100;

const props = defineProps({
  /** Se informado, lista só as fotos do álbum. */
  albumUuid: { type: String, default: null },
  q: { type: String, default: "" },
  ordem: { type: String, default: "recentes" },
  /** Agrupar por data (cabeçalhos + scrubber). */
  agrupar: { type: Boolean, default: true },
  escala: { type: Number, default: 1 },
  vazioTitulo: { type: String, default: "" },
  vazioTexto: { type: String, default: "" },
  /** Mostra os destaques (neste dia, memórias por ano, álbuns recentes) acima da grade. */
  destaques: Boolean,
});
const emit = defineEmits(["limpar-busca", "total", "mudou"]);

const auth = useAuthStore();
const upload = useUploadStore();
const route = useRoute();
const router = useRouter();
const { mdAndUp, width } = useDisplay();
const { confirmar } = useConfirm();
const { tratar } = useApiError();
const notify = useNotify();

const podeEditar = computed(() => auth.isMembro);
const alturaBase = computed(() => (width.value < 600 ? 120 : width.value < 960 ? 180 : 220));

const raiz = ref(null);
const grade = ref(null);
const inputArquivos = ref(null);
const anuncio = ref("");
const saltando = ref(false);
const mesAtual = ref("");

/* ---------- lista ---------- */
function params(pagina) {
  const p = { per_page: 60 };
  if (props.agrupar) p.agrupar = "data";
  if (props.q) p.q = props.q;
  if (props.ordem !== "recentes") p.ordem = props.ordem;
  if (props.albumUuid) p.album_uuid = props.albumUuid;
  return listarFotos(pagina, p);
}
const lista = useListaPaginada(params);

const meses = ref([]);
async function carregarLinhaDoTempo() {
  if (!props.agrupar) {
    meses.value = [];
    return;
  }
  try {
    const p = {};
    if (props.q) p.q = props.q;
    if (props.albumUuid) p.album_uuid = props.albumUuid;
    const dados = await listarLinhaDoTempo(p);
    meses.value = props.ordem === "antigas" ? [...dados].reverse() : dados;
  } catch {
    meses.value = [];
  }
}

async function recarregar() {
  sel.limpar();
  await Promise.all([lista.recarregar(), carregarLinhaDoTempo()]);
}

watch(() => [props.q, props.ordem, props.albumUuid, props.agrupar], recarregar);
// Upload concluído (painel global): recarrega.
watch(fotoCriada, recarregar);
watch(
  () => lista.total.value,
  (t) => t != null && emit("total", t),
);

/* ---------- agrupamento ---------- */
const fotosOrdenadas = computed(() => {
  if (!props.agrupar) return lista.itens.value;
  const sinal = props.ordem === "antigas" ? 1 : -1;
  // sort é estável: empates mantêm a ordem do servidor.
  return [...lista.itens.value].sort((a, b) => {
    const da = diaDaFoto(a);
    const db = diaDaFoto(b);
    if (!da || !db) return da === db ? 0 : da ? -1 : 1; // sem data sempre por último
    return sinal * da.localeCompare(db);
  });
});
const grupos = computed(() =>
  props.agrupar ? agruparPorDia(fotosOrdenadas.value) : [{ chave: "todas", mes: "", rotulo: "", itens: fotosOrdenadas.value }],
);
const uuidsVisiveis = computed(() => fotosOrdenadas.value.map((f) => f.uuid));
const mostrarScrubber = computed(() => props.agrupar && meses.value.length > 1 && lista.itens.value.length > 0);

const tituloVazio = computed(() => {
  if (props.vazioTitulo) return props.vazioTitulo;
  return auth.isMembro ? "O álbum da Rua ainda está em branco" : "Ainda não revelaram nenhuma foto";
});
const textoVazio = computed(() => {
  if (props.vazioTexto) return props.vazioTexto;
  return auth.isMembro ? "As fotos da turma vão aparecer aqui, do mais antigo ao mais novo." : "Quando alguém enviar, ela aparece aqui.";
});
const capitulosDisponiveis = computed(() => {
  const presentes = new Set(Object.keys(totaisAno.value));
  return ordemCapitulos(props.ordem)
    .filter((k) => presentes.has(k))
    .map(metaCapitulo);
});
const capitulosAtivos = computed(() => props.agrupar);
const totaisAno = computed(() => {
  const t = {};
  for (const m of meses.value) {
    const k = chaveDoAno(anoDoTexto(m.data));
    t[k] = (t[k] ?? 0) + m.total;
  }
  return t;
});
const anuncioBusca = computed(() => (props.q && lista.total.value != null && !lista.carregando.value ? `${pluralFotos(lista.total.value)} para '${props.q}'` : ""));

/* ---------- seleção ---------- */
const sel = useSelecao(uuidsVisiveis);

function aoTeclaGlobal(e) {
  if (e.key === "Escape" && sel.ativa.value && !lbAberto.value && !seletorAberto.value) {
    sel.limpar();
  }
}
onMounted(() => window.addEventListener("keydown", aoTeclaGlobal));
onBeforeUnmount(() => window.removeEventListener("keydown", aoTeclaGlobal));

watch(
  () => sel.quantidade.value,
  (n, antes) => {
    if (n !== antes) anuncio.value = n ? `${n} ${n === 1 ? "foto selecionada" : "fotos selecionadas"}` : "Seleção limpa";
  },
);

/* ---------- scrubber ---------- */
async function irParaMes(mes) {
  const idx = meses.value.findIndex((m) => m.data === mes);
  if (idx < 0) return;
  const antes = meses.value.slice(0, idx).reduce((n, m) => n + m.total, 0);
  saltando.value = true;
  await lista.carregarAte(antes + 1);
  saltando.value = false;
  await nextTick();
  const alvo = raiz.value.querySelector(`[data-mes="${mes}"]`) ?? alvoMaisProximo(mes);
  alvo?.scrollIntoView({ behavior: prefereMenosMovimento() ? "auto" : "smooth", block: "start" });
  mesAtual.value = mes;
}
/** Rola até o capítulo do ano (cartões "Memórias por ano") e foca o h2. */
async function irParaCapitulo(chave) {
  const mes = meses.value.find((m) => chaveDoAno(anoDoTexto(m.data)) === chave);
  if (!mes) return;
  await irParaMes(mes.data);
  await nextTick();
  grade.value?.focarCapitulo?.(chave);
  const el = raiz.value?.querySelector(`[data-capitulo="${chave}"]`);
  if (el) {
    el.classList.remove("fv-pulso"); // gancho motion 2
    void el.offsetWidth;
    el.classList.add("fv-pulso");
    setTimeout(() => el.classList.remove("fv-pulso"), 600);
  }
}
function alvoMaisProximo(mes) {
  const todos = Array.from(raiz.value.querySelectorAll("[data-mes]"));
  return todos.find((el) => (props.ordem === "antigas" ? el.dataset.mes >= mes : el.dataset.mes <= mes));
}

/* ---------- lightbox / deep link ---------- */
const lbAberto = ref(false);
const lbInicio = ref(0);
const fotoExtra = ref(null); // foto de deep link fora das páginas carregadas
const lbContexto = ref(null); // tira dos destaques como contexto do lightbox
const itensLightbox = computed(() => (fotoExtra.value ? [fotoExtra.value] : (lbContexto.value ?? fotosOrdenadas.value)));

function abrirContexto(fotos, uuid) {
  fotoExtra.value = null;
  lbContexto.value = fotos;
  lbInicio.value = Math.max(0, fotos.findIndex((f) => f.uuid === uuid));
  lbAberto.value = true;
}
function abrirLightbox(uuid) {
  fotoExtra.value = null;
  lbContexto.value = null;
  lbInicio.value = Math.max(0, uuidsVisiveis.value.indexOf(uuid));
  lbAberto.value = true;
}

function aoMudarLightbox(foto) {
  if (!foto) return;
  if (!lbContexto.value) grade.value?.focar?.(foto.uuid);
  if (route.query.foto !== foto.uuid) router.replace({ query: { ...route.query, foto: foto.uuid } });
}
watch(lbAberto, (aberto) => {
  if (!aberto) {
    fotoExtra.value = null;
    lbContexto.value = null;
    if (route.query.foto) {
      const { foto, ...resto } = route.query;
      void foto;
      router.replace({ query: resto });
    }
  }
});

async function abrirDeepLink() {
  const uuid = route.query.foto;
  if (!uuid || lbAberto.value) return;
  const i = uuidsVisiveis.value.indexOf(String(uuid));
  if (i >= 0) return abrirLightbox(String(uuid));
  try {
    const d = await buscarDetalhesFoto(String(uuid));
    fotoExtra.value = { ...d, uuid: String(uuid), url: d.url ?? urlFoto(String(uuid)) };
    lbInicio.value = 0;
    lbAberto.value = true;
  } catch {
    notify.erro("Não encontramos essa foto.");
  }
}

async function copiarLink(foto) {
  try {
    await navigator.clipboard.writeText(`${location.origin}/galeria-fotos?foto=${foto.uuid}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

function aplicarEdicao(foto, patch) {
  const alvo = lista.itens.value.find((f) => f.uuid === foto.uuid);
  if (alvo) Object.assign(alvo, patch);
  if (fotoExtra.value) Object.assign(fotoExtra.value, patch);
  const noContexto = lbContexto.value?.find((f) => f.uuid === foto.uuid);
  if (noContexto) Object.assign(noContexto, patch);
  if ("data_foto" in patch) carregarLinhaDoTempo();
}

/* ---------- excluir ---------- */
function removerLocal(uuids) {
  const fora = new Set(uuids);
  lista.itens.value = lista.itens.value.filter((f) => !fora.has(f.uuid));
  if (lbContexto.value) lbContexto.value = lbContexto.value.filter((f) => !fora.has(f.uuid));
  sel.remover(uuids);
  carregarLinhaDoTempo();
}

async function excluirUma(foto) {
  const ok = await confirmar({ titulo: "Excluir esta foto?", texto: "Essa ação não pode ser desfeita.", rotuloConfirmar: "Excluir" });
  if (!ok) return;
  try {
    await excluirFoto(foto.uuid);
    removerLocal([foto.uuid]);
    notify.sucesso("Foto excluída.");
  } catch (e) {
    tratar(e);
  }
}

async function excluirSelecionadas() {
  const uuids = sel.lista.value;
  if (uuids.length > MAX_LOTE) {
    notify.erro(`Selecione até ${MAX_LOTE} por vez.`);
    return;
  }
  const n = uuids.length;
  const ok = await confirmar({
    titulo: `Excluir ${n} ${n === 1 ? "foto" : "fotos"}?`,
    texto: "Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  try {
    const r = await excluirFotosLote(uuids);
    removerLocal(uuids);
    sel.limpar();
    notify.sucesso(`${r.excluidas ?? n} ${(r.excluidas ?? n) === 1 ? "foto excluída" : "fotos excluídas"}.`);
  } catch (e) {
    tratar(e);
  }
}

/* ---------- álbuns ---------- */
const seletorMontado = ref(false);
const seletorAberto = ref(false);
const uuidsAlbum = ref([]);

function abrirSeletorAlbum(uuids) {
  if (uuids.length > 500) {
    notify.erro("Selecione até 500 fotos por vez.");
    return;
  }
  uuidsAlbum.value = [...uuids];
  seletorMontado.value = true;
  seletorAberto.value = true;
}
function aoAdicionarAlbum() {
  sel.limpar();
}

async function removerDoAlbum() {
  const uuids = sel.lista.value;
  const n = uuids.length;
  const ok = await confirmar({
    titulo: `Remover ${n} ${n === 1 ? "foto" : "fotos"} do álbum?`,
    texto: "As fotos continuam na biblioteca; só saem deste álbum.",
    rotuloConfirmar: "Remover do álbum",
    destrutivo: false,
  });
  if (!ok) return;
  try {
    await removerFotosAlbum(props.albumUuid, uuids);
    removerLocal(uuids);
    sel.limpar();
    emit("mudou");
    notify.sucesso("Removidas do álbum.");
  } catch (e) {
    tratar(e);
  }
}

async function usarComoCapa() {
  const [uuid] = sel.lista.value;
  if (!uuid) return;
  try {
    await atualizarAlbum(props.albumUuid, { capa_foto_uuid: uuid });
    sel.limpar();
    emit("mudou");
    notify.sucesso("Capa do álbum atualizada.");
  } catch (e) {
    tratar(e);
  }
}

/* ---------- upload ---------- */
function enviar(arquivos) {
  if (!podeEditar.value || !arquivos.length) return;
  const recusadas = upload.adicionarFotos(arquivos, { albumUuid: props.albumUuid });
  if (recusadas.length) notify.erro(recusadas.join(" "));
}
function escolherArquivos() {
  inputArquivos.value?.click();
}
function aoEscolherArquivos(e) {
  enviar(Array.from(e.target.files || []));
  e.target.value = "";
}
const { arrastando } = useDropArquivos((arquivos) => enviar(arquivos), { ativo: () => podeEditar.value });

function aoColar(e) {
  const alvo = e.target;
  if (alvo?.tagName === "INPUT" || alvo?.tagName === "TEXTAREA" || alvo?.isContentEditable) return;
  const arquivos = Array.from(e.clipboardData?.files || []).filter((f) => f.type.startsWith("image/"));
  if (arquivos.length) enviar(arquivos);
}
onMounted(() => document.addEventListener("paste", aoColar));
onBeforeUnmount(() => document.removeEventListener("paste", aoColar));

/* ---------- início ---------- */
onMounted(async () => {
  await Promise.all([lista.carregarProxima(), carregarLinhaDoTempo()]);
  abrirDeepLink();
});

defineExpose({ escolherArquivos, recarregar, irParaCapitulo, irParaMes });
</script>

<style scoped>
.exp {
  position: relative;
  min-height: 60dvh;
}
.exp.com-scrubber {
  padding-right: 56px;
}
.exp-barra {
  position: sticky;
  top: var(--tm-navbar-h);
  z-index: 5;
  height: 56px;
  background: var(--tm-paper);
  border-bottom: 1px solid var(--tm-border);
}
.exp-barra.em-selecao {
  z-index: 6;
}
.exp-ctx {
  height: 100%;
}
/* Troca barra de contexto <-> barra de ações: cross-fade + 8px (transform/opacity). */
.barra-troca-enter-active,
.barra-troca-leave-active {
  transition:
    opacity 200ms var(--tm-ease),
    transform 200ms var(--tm-ease);
}
.barra-troca-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.barra-troca-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
/* Overlay de soltura: fade + caixa sobe um pouco. */
.exp-drop-fade-enter-active,
.exp-drop-fade-leave-active {
  transition: opacity 150ms var(--tm-ease);
}
.exp-drop-fade-enter-from,
.exp-drop-fade-leave-to {
  opacity: 0;
}
.exp-drop-fade-enter-active .exp-drop-caixa {
  animation: exp-drop-sobe 200ms var(--tm-ease) backwards;
}
@keyframes exp-drop-sobe {
  from {
    transform: scale(0.96);
    opacity: 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .barra-troca-enter-active,
  .barra-troca-leave-active,
  .exp-drop-fade-enter-active,
  .exp-drop-fade-leave-active,
  .exp-drop-fade-enter-active .exp-drop-caixa {
    transition: none;
    animation: none;
  }
}
html[data-reduzir="1"] .exp-drop-fade-enter-active .exp-drop-caixa {
  animation: none;
}
.exp-corpo {
  padding: 4px 4px 80px;
}
.exp-aviso {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 16px;
}
.exp-drop {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: grid;
  place-items: center;
  background: color-mix(in srgb, var(--tm-paper) 88%, transparent);
  border: 3px dashed var(--tm-gold);
  margin: 8px;
  border-radius: var(--tm-radius-md);
  pointer-events: none;
}
.exp-drop-caixa {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: var(--tm-ink);
  font-size: var(--tm-fs-lg);
}
.exp-salto {
  position: fixed;
  top: calc(var(--tm-navbar-h) + 56px);
  left: 0;
  right: 0;
  z-index: 6;
}
</style>
