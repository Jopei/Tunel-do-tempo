<template>
  <section class="videos-page fv-era tm-immersive">
    <header class="vid-cabeca">
      <div class="vid-cabeca-texto">
        <h1 class="vid-cabeca-titulo">Vídeos<span v-if="conteudo.pasta_atual" class="sr-only">: {{ conteudo.pasta_atual.nome }}</span></h1>
        <p class="vid-cabeca-sub">Os vídeos da Rua, {{ REDE.anoInicio }}-{{ REDE.anoFim }}</p>
      </div>
      <p class="vid-contador" role="status" aria-live="polite">{{ contador }}</p>
    </header>

    <div class="vid-barra" :class="{ 'em-selecao': sel.ativa.value }">
      <div v-if="sel.ativa.value" class="vid-selecao" role="toolbar" aria-label="Ações para os vídeos selecionados">
        <v-btn icon variant="text" class="tm-touch" aria-label="Limpar seleção" @click="sel.limpar()"><v-icon>mdi-close</v-icon></v-btn>
        <span class="vid-selecao-qtd" role="status" aria-live="polite">{{ sel.quantidade.value }} {{ sel.quantidade.value === 1 ? "selecionado" : "selecionados" }}</span>
        <v-spacer />
        <template v-if="auth.isMembro">
          <v-btn variant="text" prepend-icon="mdi-folder-move-outline" @click="pedirMover('video', videosSelecionados)">
            <span class="d-none d-sm-inline">Mover</span><span class="d-sm-none sr-only">Mover</span>
          </v-btn>
          <v-btn v-if="sel.quantidade.value === 1" variant="text" prepend-icon="mdi-pencil-outline" @click="pedirRenomear('video', videosSelecionados[0])">
            <span class="d-none d-sm-inline">Renomear</span><span class="d-sm-none sr-only">Renomear</span>
          </v-btn>
        </template>
        <v-btn v-if="sel.quantidade.value === 1" variant="text" prepend-icon="mdi-download" :href="videosSelecionados[0]?.url" target="_blank" rel="noopener noreferrer" download>
          <span class="d-none d-sm-inline">Baixar</span><span class="d-sm-none sr-only">Baixar</span>
        </v-btn>
        <v-btn v-if="auth.isMembro" variant="text" color="error" prepend-icon="mdi-delete-outline" @click="excluirVideos(videosSelecionados)">
          <span class="d-none d-sm-inline">Excluir</span><span class="d-sm-none sr-only">Excluir</span>
        </v-btn>
      </div>

      <template v-else>
        <VideoBreadcrumb :caminho="conteudo.caminho" :pode-soltar="auth.isMembro" @ir="irPasta" @soltar="aoSoltarEm" />
        <v-spacer />
        <v-btn-toggle v-model="visao" mandatory density="comfortable" variant="outlined" divided aria-label="Modo de exibição">
          <v-btn value="grade" icon aria-label="Grade" :aria-pressed="visao === 'grade'"><v-icon>mdi-view-grid-outline</v-icon></v-btn>
          <v-btn value="lista" icon aria-label="Lista" :aria-pressed="visao === 'lista'"><v-icon>mdi-view-list-outline</v-icon></v-btn>
          <v-btn value="ano" icon aria-label="Agrupar por ano" :aria-pressed="visao === 'ano'"><v-icon>mdi-timeline-outline</v-icon></v-btn>
        </v-btn-toggle>
        <v-menu location="bottom end">
          <template #activator="{ props }">
            <v-btn v-bind="props" variant="text" prepend-icon="mdi-sort" class="vid-ordenar">
              <span class="d-none d-sm-inline">{{ rotuloOrdem }}</span><span class="d-sm-none sr-only">Ordenar</span>
            </v-btn>
          </template>
          <v-list density="comfortable" aria-label="Ordenar vídeos">
            <v-list-item v-for="o in ORDENS" :key="o.id" :title="o.titulo" :active="ordemId === o.id" role="menuitemradio" :aria-checked="ordemId === o.id" @click="definirOrdem(o.id)" />
          </v-list>
        </v-menu>
        <v-menu v-if="auth.isMembro" location="bottom end">
          <template #activator="{ props }">
            <v-btn v-bind="props" color="primary" variant="flat" prepend-icon="mdi-plus">Novo</v-btn>
          </template>
          <v-list density="comfortable" aria-label="Novo">
            <v-list-item prepend-icon="mdi-folder-plus-outline" title="Nova pasta" @click="pedirNovaPasta" />
            <v-list-item prepend-icon="mdi-upload" title="Enviar vídeo" @click="escolherArquivos" />
          </v-list>
        </v-menu>
      </template>
    </div>

    <div class="vid-corpo" :aria-busy="lista.carregando.value || estadoPastas === 'carregando'">
      <TmEmptyState
        v-if="estadoPastas === 'nao-encontrada'"
        icone="mdi-folder-alert-outline"
        titulo="Pasta não encontrada"
        texto="Ela pode ter sido movida ou excluída."
      >
        <v-btn variant="flat" color="primary" @click="irPasta(null)">Ir para Vídeos</v-btn>
      </TmEmptyState>

      <TmErrorState v-else-if="estadoPastas === 'erro' || (lista.erro.value && !lista.itens.value.length)" texto="Não conseguimos carregar os vídeos." @retry="recarregarTudo" />

      <template v-else>
        <v-text-field
          v-model="buscaInput"
          class="vid-busca"
          label="Buscar nesta pasta"
          prepend-inner-icon="mdi-magnify"
          density="comfortable"
          variant="outlined"
          clearable
          hide-details
          autocomplete="off"
        />

        <div v-if="carregandoInicial" class="vid-esqueleto" aria-hidden="true">
          <div class="pastas-grade"><span v-for="n in 4" :key="`p${n}`" class="esq-pasta"></span></div>
          <div class="videos-grade"><span v-for="n in 6" :key="`v${n}`" class="esq-video"></span></div>
        </div>

        <template v-else>
          <div v-if="visao === 'ano'" class="vid-ano-aviso">
            <v-icon size="18" aria-hidden="true">mdi-information-outline</v-icon>
            <span v-if="!pastaUuid">Mostrando todos os vídeos, de todas as pastas.</span>
            <v-chip v-else closable size="large" label @click:close="irPasta(null)">Pasta: {{ conteudo.pasta_atual?.nome }}</v-chip>
            <v-btn variant="text" size="small" class="vid-ano-voltar" @click="visao = 'grade'">Voltar às pastas</v-btn>
          </div>

          <section v-if="conteudo.pastas.length && visao !== 'ano'" aria-labelledby="vid-h-pastas" class="vid-secao-bloco">
            <h2 id="vid-h-pastas" class="vid-secao">Pastas</h2>
            <div class="pastas-grade">
              <VideoPastaCard
                v-for="p in conteudo.pastas"
                :key="p.uuid"
                :pasta="p"
                :pode-soltar="auth.isMembro"
                @abrir="(pasta) => irPasta(pasta.uuid)"
                @menu="(pasta, pos, el) => abrirMenu('pasta', pasta, pos, el)"
                @soltar="aoSoltarEm"
              />
            </div>
          </section>

          <section v-if="visao === 'ano' ? anosVideos.length : videosOrdenados.length" aria-labelledby="vid-h-videos" class="vid-secao-bloco">
            <h2 id="vid-h-videos" :class="visao === 'ano' ? 'sr-only' : 'vid-secao'">Vídeos</h2>
            <Transition name="visao" mode="out-in">
              <div v-if="visao === 'grade'" key="grade" class="videos-grade">
                <VideoCard
                  v-for="(v, i) in videosOrdenados"
                  :key="v.uuid"
                  :video="v"
                  :selecionado="sel.tem(v.uuid)"
                  :modo-selecao="sel.ativa.value"
                  :pode-arrastar="auth.isMembro"
                  :style="{ '--i': i % 12 }"
                  @abrir="(video, e) => aoClicarVideo(video, i, e)"
                  @check="aoMarcar"
                  @menu="(video, pos, el) => abrirMenu('video', video, pos, el)"
                  @arrastar="aoArrastar"
                />
              </div>
              <VideoPorAno
                v-else-if="visao === 'ano'"
                key="ano"
                ref="estante"
                class="videos-ano"
                :anos="anosVideos"
                :ordem="sort.campo === 'data' && sort.dir === 'asc' ? 'antigas' : 'recentes'"
                :q="q"
                :pasta-uuid="pastaUuid"
                :selecionados="sel.marcados.value"
                :selecao-ativa="sel.ativa.value"
                :pode-arrastar="auth.isMembro"
                @itens="(l) => (itensAno = l)"
                @abrir="abrirDaEstante"
                @check="aoMarcar"
                @menu="(video, pos, el) => abrirMenu('video', video, pos, el)"
                @arrastar="aoArrastar"
              />
              <VideoLinhas
                v-else
                key="lista"
                :itens="videosOrdenados"
                :selecionados="sel.marcados.value"
                :sort="sort"
                :pode-arrastar="auth.isMembro"
                @abrir="(video, e) => aoClicarVideo(video, videosOrdenados.indexOf(video), e)"
                @check="aoMarcar"
                @menu="(video, pos, el) => abrirMenu('video', video, pos, el)"
                @arrastar="aoArrastar"
                @ordenar="ordenarPorColuna"
              />
            </Transition>
          </section>

          <TmEmptyState
            v-if="visao === 'ano' && anosCarregados && !carregandoAnos && (!anosVideos.length || estante?.todasVazias)"
            class="fv-vazio"
            moldura
            :icone="q ? 'mdi-video-off-outline' : conteudo.pasta_atual ? 'mdi-folder-open-outline' : 'mdi-calendar-blank-outline'"
            :titulo="q || conteudo.pasta_atual ? tituloVazio : 'Nenhum vídeo com data ainda'"
            :texto="q || conteudo.pasta_atual ? textoVazio : 'Datar os vídeos ajuda a montar a estante por ano.'"
          />
          <TmEmptyState
            v-else-if="visao !== 'ano' && vazioGeral"
            class="fv-vazio"
            moldura
            :icone="q ? 'mdi-video-off-outline' : conteudo.pasta_atual ? 'mdi-folder-open-outline' : 'mdi-filmstrip-off'"
            :titulo="tituloVazio"
            :texto="textoVazio"
          >
            <v-btn v-if="q" variant="tonal" @click="buscaInput = ''">Limpar busca</v-btn>
            <v-btn v-else-if="auth.isMembro" variant="flat" color="primary" prepend-icon="mdi-upload" @click="escolherArquivos">Enviar vídeo</v-btn>
          </TmEmptyState>

          <div v-if="visao === 'ano'"></div>
          <div v-else-if="lista.erro.value" class="vid-aviso" role="alert">
            Não deu para carregar mais.
            <v-btn size="small" variant="tonal" @click="lista.carregarProxima()">Tentar de novo</v-btn>
          </div>
          <div v-else-if="lista.temMais.value && !lista.carregando.value" class="vid-aviso">
            <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
          </div>
        </template>
      </template>
      <div :ref="lista.sentinela" aria-hidden="true"></div>
    </div>

    <VideoMenuContexto
      :aberto="menu.aberto"
      :x="menu.x"
      :y="menu.y"
      :itens="itensMenu"
      :gatilho="menu.gatilho"
      :rotulo="menu.alvo?.tipo === 'pasta' ? 'Ações da pasta' : 'Ações do vídeo'"
      @escolher="aoEscolherMenu"
      @fechar="menu.aberto = false"
    />

    <VideoPreviewDialog
      v-model="previewAberto"
      :itens="previewItens"
      :inicio="previewInicio"
      :pode-editar="auth.isMembro"
      :pasta-nome="conteudo.pasta_atual?.nome ?? ''"
      :caminho="conteudo.caminho"
      @mover="(v) => pedirMover('video', [v])"
      @renomear="(v) => pedirRenomear('video', v)"
      @data="pedirData"
      @copiar="copiarLink"
      @excluir="(v) => excluirVideos([v])"
    />

    <DataVideoDialog
      v-model="dataAberto"
      :titulo="dataCtx.modo === 'envio' ? 'Quando foi gravado?' : 'Data do vídeo'"
      :texto="dataCtx.modo === 'envio' ? 'Opcional. A data coloca o vídeo na estante do ano certo.' : ''"
      :modo="dataCtx.modo"
      :valor="dataCtx.valor"
      :salvando="salvandoData"
      :erro="erroData"
      @salvar="salvarData"
    />

    <VideoMoverDialog
      v-model="moverAberto"
      :titulo="moverAlvo?.tipo === 'pasta' ? 'Mover pasta para' : 'Mover para'"
      :origem-uuid="moverOrigem"
      :pastas-movidas="moverAlvo?.tipo === 'pasta' ? moverAlvo.itens.map((p) => p.uuid) : []"
      :salvando="movendo"
      :erro="erroMover"
      @mover="executarMoverDialogo"
    />

    <NomeDialog
      v-model="nomeAberto"
      :titulo="nomeCtx.titulo"
      :rotulo="nomeCtx.rotulo"
      :rotulo-acao="nomeCtx.acao"
      :valor="nomeCtx.valor"
      :salvando="salvandoNome"
      :erro="erroNome"
      @salvar="salvarNome"
    />

    <input ref="inputArquivos" type="file" class="sr-only" tabindex="-1" multiple accept="video/mp4,video/quicktime,video/x-msvideo,.mp4,.mov,.avi" aria-label="Escolher vídeos para enviar" @change="aoEscolherArquivos" />

    <div v-if="arrastandoArquivos && auth.isMembro" class="vid-drop" aria-hidden="true">
      <div class="vid-drop-caixa">
        <v-icon size="48">mdi-cloud-upload-outline</v-icon>
        <strong>Solte para enviar{{ conteudo.pasta_atual ? ` a ${conteudo.pasta_atual.nome}` : "" }}</strong>
      </div>
    </div>

    <v-snackbar v-model="aviso.aberto" :timeout="8000" class="vid-snack" location="bottom">
      {{ aviso.texto }}
      <template v-if="aviso.acao" #actions>
        <v-btn variant="text" color="primary" @click="executarAcaoAviso">{{ aviso.rotuloAcao }}</v-btn>
      </template>
    </v-snackbar>
    <p class="sr-only" role="status" aria-live="polite">{{ anuncio }}</p>
  </section>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from "vue";
import gsap from "gsap";
import { voarFantasma } from "@/composables/useVoo";
import { useRoute, useRouter } from "vue-router";
import { listarVideos, listarVideosAnos, excluirVideo, atualizarVideo, moverVideo } from "@/services/video.service";
import { listarPastas, criarPasta, renomearPasta, moverPasta, excluirPasta } from "@/services/video.pasta.service";
import { useAuthStore } from "@/stores/auth";
import { useUploadStore } from "@/stores/upload";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useSelecao } from "@/composables/useSelecao";
import { useDropArquivos } from "@/composables/useDropArquivos";
import { MIME_VIDEOS } from "@/composables/useAlvoDrop";
import { videoCriado } from "@/composables/useMidiaEventos";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError, interpretarErro } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import VideoBreadcrumb from "@/components/videos/VideoBreadcrumb.vue";
import VideoPastaCard from "@/components/videos/VideoPastaCard.vue";
import VideoCard from "@/components/videos/VideoCard.vue";
import VideoLinhas from "@/components/videos/VideoLinhas.vue";
import VideoMenuContexto from "@/components/videos/VideoMenuContexto.vue";
import VideoPreviewDialog from "@/components/videos/VideoPreviewDialog.vue";
import VideoMoverDialog from "@/components/videos/VideoMoverDialog.vue";
import NomeDialog from "@/components/videos/NomeDialog.vue";
import VideoPorAno from "@/components/videos/VideoPorAno.vue";
import DataVideoDialog from "@/components/videos/DataVideoDialog.vue";
import { REDE } from "@/config/esquina";
import { lerPref, gravarPref, pluralVideos } from "@/composables/useEraFotosVideos";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";

const CHAVE_VISAO = "tunel-videos-visao";
const ORDENS = [
  { id: "recentes", titulo: "Mais recentes" },
  { id: "antigas", titulo: "Mais antigas" },
  { id: "nome", titulo: "Nome" },
  { id: "duracao", titulo: "Duração" },
  { id: "data_video", titulo: "Data do vídeo" },
];

const auth = useAuthStore();
const upload = useUploadStore();
const route = useRoute();
const router = useRouter();
const { confirmar } = useConfirm();
const { tratar } = useApiError();
const notify = useNotify();

/* ---------- pasta atual e navegação (?pasta=) ---------- */
const pastaUuid = computed(() => (typeof route.query.pasta === "string" && route.query.pasta !== "raiz" && route.query.pasta ? route.query.pasta : null));
const conteudo = ref({ pasta_atual: null, caminho: [], pastas: [] });
const estadoPastas = ref("carregando"); // carregando | ok | erro | nao-encontrada
const anuncio = ref("");

async function carregarPastas({ silencioso = false } = {}) {
  if (!silencioso) estadoPastas.value = "carregando";
  try {
    conteudo.value = await listarPastas(pastaUuid.value);
    estadoPastas.value = "ok";
  } catch (e) {
    estadoPastas.value = e?.response?.status === 404 ? "nao-encontrada" : "erro";
  }
}

function irPasta(uuid) {
  const query = { ...route.query };
  if (uuid) query.pasta = uuid;
  else delete query.pasta;
  router.push({ name: "videos", query });
}

/* ---------- lista de vídeos ---------- */
const VISOES = ["grade", "lista", "ano"];
const visaoSalva = lerPref(CHAVE_VISAO, "grade");
const visao = ref(VISOES.includes(visaoSalva) ? visaoSalva : "grade");
const estante = ref(null);
const itensAno = ref([]);
const anosVideos = ref([]);
const anosCarregados = ref(false);
const carregandoAnos = ref(false);
watch(visao, (v) => {
  gravarPref(CHAVE_VISAO, v);
  if (v === "ano") carregarAnos();
  else {
    sel.limpar();
    // Busca/ordem/pasta podem ter mudado enquanto a lista não era usada.
    lista.recarregar();
  }
});

let tokenAnos = 0;
async function carregarAnos() {
  const meu = ++tokenAnos;
  carregandoAnos.value = true;
  let resultado = [];
  try {
    const params = {};
    if (pastaUuid.value) params.pasta_uuid = pastaUuid.value;
    else params.todas = 1;
    if (q.value) params.q = q.value;
    resultado = await listarVideosAnos(params);
  } catch {
    resultado = [];
  }
  if (meu !== tokenAnos) return; // resposta de uma busca/pasta anterior
  anosVideos.value = resultado;
  carregandoAnos.value = false;
  anosCarregados.value = true;
  // Chip "Mais de 2014" do player chega com #fv-cap-2014: rola até a prateleira.
  const alvo = /^#fv-cap-(.+)$/.exec(route.hash)?.[1];
  if (alvo) setTimeout(() => estante.value?.irParaCapitulo(alvo), 500);
}

const buscaInput = ref("");
const q = ref("");
const sort = reactive({ campo: "data", dir: "desc" }); // data | nome | duracao | tamanho

const ordemId = computed(() => {
  if (sort.campo === "data") return sort.dir === "desc" ? "recentes" : "antigas";
  if (sort.campo === "nome" && sort.dir === "asc") return "nome";
  if (sort.campo === "duracao") return "duracao";
  if (sort.campo === "data_video") return "data_video";
  return "";
});
const rotuloOrdem = computed(() => ORDENS.find((o) => o.id === ordemId.value)?.titulo ?? (sort.campo === "tamanho" ? "Tamanho" : "Ordenar"));

function definirOrdem(id) {
  const mapa = {
    recentes: ["data", "desc"],
    antigas: ["data", "asc"],
    nome: ["nome", "asc"],
    duracao: ["duracao", "desc"],
    data_video: ["data_video", "desc"],
  };
  [sort.campo, sort.dir] = mapa[id];
}
function ordenarPorColuna(campo) {
  if (sort.campo === campo) sort.dir = sort.dir === "asc" ? "desc" : "asc";
  else {
    sort.campo = campo;
    sort.dir = campo === "nome" ? "asc" : "desc";
  }
}

const ordemServidor = computed(() => {
  const desc = sort.dir === "desc";
  if (sort.campo === "data") return desc ? "recentes" : "antigas";
  if (sort.campo === "nome") return desc ? "titulo_desc" : "titulo";
  if (sort.campo === "data_video") return desc ? "data_video_desc" : "data_video";
  return desc ? `${sort.campo}_desc` : sort.campo; // duracao | tamanho
});

const lista = useListaPaginada((p) => {
  const params = { per_page: 48, pasta_uuid: pastaUuid.value ?? "raiz", ordem: ordemServidor.value };
  if (q.value) params.q = q.value;
  return listarVideos(p, params);
});

const videosOrdenados = computed(() => (visao.value === "ano" ? itensAno.value : lista.itens.value)); // a ordem vem do servidor
const contador = computed(() => {
  const pastas = conteudo.value.pastas.length;
  const partes = [];
  if (visao.value === "ano") partes.push(pluralVideos(anosVideos.value.reduce((n, a) => n + (Number(a.total) || 0), 0)));
  else if (lista.total.value != null) partes.push(pluralVideos(lista.total.value));
  if (pastas && visao.value !== "ano") partes.push(`${pastas} ${pastas === 1 ? "pasta" : "pastas"}`);
  return partes.join(" · ");
});

const carregandoInicial = computed(() => estadoPastas.value === "carregando" || (lista.carregando.value && !lista.itens.value.length && !lista.erro.value));
const vazioGeral = computed(() => lista.vazio.value && !conteudo.value.pastas.length);
const tituloVazio = computed(() => {
  if (q.value) return `Nenhum vídeo com '${q.value}'`;
  if (conteudo.value.pasta_atual) return "Esta pasta está vazia";
  return auth.isMembro ? "A estante da Rua está vazia" : "Ainda não gravaram nada por aqui";
});
const textoVazio = computed(() => {
  if (q.value) return "Tente o título, o nome de quem enviou ou um ano.";
  if (conteudo.value.pasta_atual) return auth.isMembro ? "Arraste vídeos para cá ou use Mover." : "Ainda não há vídeos aqui.";
  return auth.isMembro ? "Os vídeos da turma ficam guardados aqui, em pastas, por ano ou na ordem que preferir." : "Quando alguém enviar um vídeo, ele aparece aqui.";
});

/** Recarrega a visão atual (grade/lista usam a lista paginada; "Por ano" recarrega totais e prateleiras). */
async function recarregarVideos() {
  if (visao.value === "ano") {
    await carregarAnos();
    estante.value?.recarregar();
  } else await lista.recarregar();
}

let timerBusca = null;
watch(buscaInput, (v) => {
  clearTimeout(timerBusca);
  timerBusca = setTimeout(() => {
    const novo = (v ?? "").trim();
    if (novo !== q.value) {
      q.value = novo;
      if (visao.value === "ano") carregarAnos();
      else lista.recarregar();
    }
  }, 300);
});
watch(ordemServidor, () => visao.value !== "ano" && lista.recarregar());

async function recarregarTudo() {
  sel.limpar();
  await Promise.all([carregarPastas(), recarregarVideos()]);
}
watch(pastaUuid, () => {
  buscaInput.value = "";
  q.value = "";
  recarregarTudo();
});
// Envio concluído em qualquer lugar: atualiza.
watch(videoCriado, () => {
  carregarPastas({ silencioso: true });
  recarregarVideos();
});

/* ---------- seleção ---------- */
const sel = useSelecao(computed(() => videosOrdenados.value.map((v) => v.uuid)));
const videosSelecionados = computed(() => videosOrdenados.value.filter((v) => sel.tem(v.uuid)));

function aoMarcar(video, e) {
  if (e?.shiftKey) sel.intervalo(video.uuid);
  else sel.alternar(video.uuid);
}
function aoClicarVideo(video, indice, e) {
  if (e?.shiftKey) return sel.intervalo(video.uuid);
  if (e?.ctrlKey || e?.metaKey || sel.ativa.value) return sel.alternar(video.uuid);
  previewLista.value = null;
  previewInicio.value = indice;
  previewAberto.value = true;
}
/** "Por ano": o contexto de navegação ←/→ é a própria prateleira. */
function abrirDaEstante(video, e, itensPrateleira) {
  if (e?.shiftKey) return sel.intervalo(video.uuid);
  if (e?.ctrlKey || e?.metaKey || sel.ativa.value) return sel.alternar(video.uuid);
  previewLista.value = itensPrateleira;
  previewInicio.value = Math.max(0, itensPrateleira.findIndex((v) => v.uuid === video.uuid));
  previewAberto.value = true;
}

function aoTeclaGlobal(e) {
  if (e.key === "Escape" && sel.ativa.value && !previewAberto.value && !moverAberto.value && !nomeAberto.value && !menu.aberto) sel.limpar();
}
onMounted(() => window.addEventListener("keydown", aoTeclaGlobal));
onBeforeUnmount(() => window.removeEventListener("keydown", aoTeclaGlobal));

/* ---------- preview ---------- */
const previewAberto = ref(false);
const previewInicio = ref(0);
const previewLista = ref(null);
const previewItens = computed(() => previewLista.value ?? videosOrdenados.value);

async function copiarLink(video) {
  try {
    await navigator.clipboard.writeText(`${location.origin}/videos/${video.uuid}?pasta=${video.pasta_uuid || "raiz"}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

/* ---------- menu de contexto ---------- */
const menu = reactive({ aberto: false, x: 0, y: 0, alvo: null, gatilho: null });

function abrirMenu(tipo, item, pos, el) {
  menu.alvo = { tipo, item };
  menu.x = pos.x;
  menu.y = pos.y;
  menu.gatilho = el ?? null;
  menu.aberto = true;
}

const itensMenu = computed(() => {
  const a = menu.alvo;
  if (!a) return [];
  const membro = auth.isMembro;
  if (a.tipo === "pasta") {
    return [
      { id: "abrir", titulo: "Abrir", icone: "mdi-folder-open-outline" },
      ...(membro
        ? [
            { id: "renomear", titulo: "Renomear", icone: "mdi-pencil-outline" },
            { id: "mover", titulo: "Mover para...", icone: "mdi-folder-move-outline" },
            { id: "d1", divisor: true },
            { id: "excluir", titulo: "Excluir", icone: "mdi-delete-outline", perigo: true },
          ]
        : []),
    ];
  }
  return [
    { id: "abrir", titulo: "Abrir", icone: "mdi-play-circle-outline" },
    ...(membro
      ? [
          { id: "mover", titulo: "Mover para...", icone: "mdi-folder-move-outline" },
          { id: "renomear", titulo: "Renomear", icone: "mdi-pencil-outline" },
          { id: "data", titulo: "Definir data...", icone: "mdi-calendar-edit" },
        ]
      : []),
    { id: "copiar", titulo: "Copiar link", icone: "mdi-link-variant" },
    { id: "baixar", titulo: "Baixar", icone: "mdi-download" },
    ...(membro ? [{ id: "d1", divisor: true }, { id: "excluir", titulo: "Excluir", icone: "mdi-delete-outline", perigo: true }] : []),
  ];
});

// Ações sobre um vídeo valem para toda a seleção quando ele faz parte dela.
const alvosVideo = (v) => (sel.tem(v.uuid) && sel.quantidade.value > 1 ? videosSelecionados.value : [v]);

function aoEscolherMenu(id) {
  const { tipo, item } = menu.alvo;
  if (tipo === "pasta") {
    if (id === "abrir") irPasta(item.uuid);
    else if (id === "renomear") pedirRenomear("pasta", item);
    else if (id === "mover") pedirMover("pasta", [item]);
    else if (id === "excluir") excluirPastaCom(item);
    return;
  }
  if (id === "abrir") {
    previewLista.value = null;
    previewInicio.value = Math.max(0, videosOrdenados.value.findIndex((v) => v.uuid === item.uuid));
    previewAberto.value = true;
  } else if (id === "mover") pedirMover("video", alvosVideo(item));
  else if (id === "renomear") pedirRenomear("video", item);
  else if (id === "data") pedirData(item);
  else if (id === "copiar") copiarLink(item);
  else if (id === "baixar") window.open(item.url, "_blank", "noopener");
  else if (id === "excluir") excluirVideos(alvosVideo(item));
}

/* ---------- aviso (snackbar) ---------- */
const aviso = reactive({ aberto: false, texto: "", acao: null, rotuloAcao: "" });
function mostrarAviso(texto, acao = null, rotuloAcao = "") {
  aviso.texto = texto;
  aviso.acao = acao;
  aviso.rotuloAcao = rotuloAcao;
  aviso.aberto = true;
  anuncio.value = texto;
}
function executarAcaoAviso() {
  aviso.aberto = false;
  aviso.acao?.();
}

/* ---------- mover ---------- */
const moverAberto = ref(false);
const moverAlvo = ref(null); // { tipo, itens }
const movendo = ref(false);
const erroMover = ref("");
const moverOrigem = computed(() => (moverAlvo.value?.tipo === "pasta" ? (moverAlvo.value.itens[0]?.pai_uuid ?? null) : pastaUuid.value));

function pedirMover(tipo, itens) {
  if (!itens.length) return;
  moverAlvo.value = { tipo, itens };
  erroMover.value = "";
  moverAberto.value = true;
}

/** Move itens para destino ({uuid|null, nome}). Devolve { feitos, falhas, erro }. */
async function moverItens(tipo, itens, destino) {
  const resultados = await Promise.allSettled(
    itens.map((i) => (tipo === "video" ? moverVideo(i.uuid, destino.uuid) : moverPasta(i.uuid, destino.uuid))),
  );
  const feitos = itens.filter((_, i) => resultados[i].status === "fulfilled");
  const falha = resultados.find((r) => r.status === "rejected");
  const erro = falha ? interpretarErro(falha.reason) : null;
  // 422 de ciclo vem em pai_uuid
  const mensagem = erro ? erro.campos.pai_uuid || erro.campos.pasta_uuid || erro.mensagem : "";
  return { feitos, falhas: itens.length - feitos.length, mensagem };
}

async function aplicarMover(tipo, itens, destino, { dialogo = false } = {}) {
  const origens = itens.map((i) => ({ uuid: i.uuid, de: tipo === "video" ? i.pasta_uuid ?? null : i.pai_uuid ?? null }));
  const r = await moverItens(tipo, itens, destino);
  if (r.feitos.length) {
    sel.limpar();
    await Promise.all([carregarPastas({ silencioso: true }), recarregarVideos()]);
    const n = r.feitos.length;
    const feitosSet = new Set(r.feitos.map((i) => i.uuid));
    mostrarAviso(
      `${n} ${tipo === "video" ? (n === 1 ? "vídeo movido" : "vídeos movidos") : n === 1 ? "pasta movida" : "pastas movidas"} para ${destino.nome}`,
      async () => {
        await Promise.allSettled(origens.filter((o) => feitosSet.has(o.uuid)).map((o) => (tipo === "video" ? moverVideo(o.uuid, o.de) : moverPasta(o.uuid, o.de))));
        await Promise.all([carregarPastas({ silencioso: true }), recarregarVideos()]);
        anuncio.value = "Movimento desfeito.";
      },
      "Desfazer",
    );
  }
  if (r.falhas) {
    if (dialogo) erroMover.value = r.mensagem || "Não foi possível mover.";
    else notify.erro(r.mensagem || "Não foi possível mover.");
  }
  return !r.falhas;
}

async function executarMoverDialogo(destino) {
  movendo.value = true;
  erroMover.value = "";
  const ok = await aplicarMover(moverAlvo.value.tipo, moverAlvo.value.itens, destino, { dialogo: true });
  movendo.value = false;
  if (ok) moverAberto.value = false;
}

/* ---------- drag-and-drop ---------- */
/** Marca os cartões/linhas arrastados (atributo, sem re-render) para ficarem "levantados". */
function marcarArrastados(uuids, ligar) {
  document.querySelectorAll(".vid-corpo [data-uuid]").forEach((el) => {
    if (!uuids.includes(el.dataset.uuid)) return;
    if (ligar) el.dataset.arrastando = "1";
    else delete el.dataset.arrastando;
  });
}
function aoArrastar(e, video) {
  const uuids = sel.tem(video.uuid) ? sel.lista.value : [video.uuid];
  const origem = e.currentTarget;
  e.dataTransfer.setData(MIME_VIDEOS, JSON.stringify(uuids));
  // Só depois do fantasma nativo ser capturado: senão ele sairia esmaecido.
  setTimeout(() => marcarArrastados(uuids, true), 0);
  origem?.addEventListener("dragend", () => marcarArrastados(uuids, false), { once: true });
  e.dataTransfer.effectAllowed = "move";
  // Fantasma: miniatura de 64 px com a contagem.
  const g = document.createElement("div");
  g.setAttribute("aria-hidden", "true");
  g.style.cssText = "position:fixed;top:-200px;left:-200px;width:64px;height:64px;border-radius:8px;overflow:hidden;background:#2a2112;color:#fff;font:600 12px sans-serif;";
  const poster = video.poster_url || video.thumbnail;
  if (poster) {
    const img = document.createElement("img");
    img.src = poster;
    img.style.cssText = "width:100%;height:100%;object-fit:cover;";
    g.appendChild(img);
  }
  if (uuids.length > 1) {
    const b = document.createElement("span");
    b.textContent = String(uuids.length);
    b.style.cssText = "position:absolute;top:4px;right:4px;min-width:20px;padding:2px 6px;border-radius:10px;background:#c7a43a;color:#2a2112;text-align:center;";
    g.appendChild(b);
  }
  document.body.appendChild(g);
  e.dataTransfer.setDragImage(g, 32, 32);
  setTimeout(() => g.remove(), 0);
}

/** Voo dos cartões soltos até o alvo (pasta ou migalha), que pulsa ao receber. Só transform/opacity. */
function voarParaDestino(destinoUuid, uuids) {
  const chave = destinoUuid ?? "raiz";
  const alvo = document.querySelector(`.pasta-card[data-uuid="${CSS.escape(chave)}"], .migalha[data-uuid="${CSS.escape(chave)}"]`);
  if (!alvo) return;
  gsap.fromTo(alvo, { scale: 0.94 }, { scale: 1, duration: 0.35, ease: "back.out(2)", clearProps: "transform" });
  uuids.forEach((uuid, n) => {
    const el = document.querySelector(`.vid-corpo [data-uuid="${CSS.escape(uuid)}"]`);
    if (!el) return;
    // Linha da tabela não voa sozinha (tr fora da tabela some): usa o bloco de nome.
    const base = el.tagName === "TR" ? el.querySelector(".vl-abrir") : el;
    if (!base) return;
    const caixa = base.cloneNode(true);
    caixa.classList.add("vid-voo");
    voarFantasma(caixa, {
      de: base.getBoundingClientRect(),
      para: alvo,
      duracao: 0.3 + Math.min(n, 4) * 0.02,
      ease: "power2.in",
      fade: true,
    });
  });
}
async function aoSoltarEm(destinoUuid, destinoNome, payload) {
  if (!auth.isMembro) return;
  if (payload.arquivos) {
    enviarArquivos(payload.arquivos, destinoUuid, destinoNome);
    return;
  }
  const itens = lista.itens.value.filter((v) => payload.videos.includes(v.uuid) && (v.pasta_uuid ?? null) !== destinoUuid);
  if (!itens.length) return;
  voarParaDestino(destinoUuid, itens.map((v) => v.uuid));
  await aplicarMover("video", itens, { uuid: destinoUuid, nome: destinoNome });
}

/* ---------- nomes (renomear / nova pasta) ---------- */
const nomeAberto = ref(false);
const nomeCtx = reactive({ modo: "", titulo: "", rotulo: "Nome", acao: "Salvar", valor: "", item: null });
const salvandoNome = ref(false);
const erroNome = ref("");

function pedirNovaPasta() {
  Object.assign(nomeCtx, { modo: "nova-pasta", titulo: "Nova pasta", rotulo: "Nome da pasta", acao: "Criar", valor: "", item: null });
  erroNome.value = "";
  nomeAberto.value = true;
}
function pedirRenomear(tipo, item) {
  Object.assign(nomeCtx, {
    modo: tipo === "pasta" ? "renomear-pasta" : "renomear-video",
    titulo: tipo === "pasta" ? "Renomear pasta" : "Renomear vídeo",
    rotulo: tipo === "pasta" ? "Nome da pasta" : "Título do vídeo",
    acao: "Salvar",
    valor: tipo === "pasta" ? item.nome : item.titulo,
    item,
  });
  erroNome.value = "";
  nomeAberto.value = true;
}
async function salvarNome(nome) {
  salvandoNome.value = true;
  erroNome.value = "";
  try {
    if (nomeCtx.modo === "nova-pasta") {
      await criarPasta(nome, pastaUuid.value);
      anuncio.value = `Pasta ${nome} criada.`;
      await carregarPastas({ silencioso: true });
    } else if (nomeCtx.modo === "renomear-pasta") {
      await renomearPasta(nomeCtx.item.uuid, nome);
      await carregarPastas({ silencioso: true });
    } else {
      await atualizarVideo(nomeCtx.item.uuid, { titulo: nome });
      const alvo = lista.itens.value.find((v) => v.uuid === nomeCtx.item.uuid);
      if (alvo) alvo.titulo = nome;
      nomeCtx.item.titulo = nome;
    }
    nomeAberto.value = false;
  } catch (e) {
    const info = interpretarErro(e);
    erroNome.value = info.campos.nome || info.campos.titulo || info.mensagem;
  } finally {
    salvandoNome.value = false;
  }
}

/* ---------- data do vídeo (edição e envio) ---------- */
const dataAberto = ref(false);
const dataCtx = reactive({ modo: "edicao", valor: "", item: null, arquivos: [], destino: null });
const salvandoData = ref(false);
const erroData = ref("");

function pedirData(video) {
  Object.assign(dataCtx, { modo: "edicao", valor: video.data_video ?? "", item: video, arquivos: [], destino: null });
  erroData.value = "";
  dataAberto.value = true;
}
function pedirDataEnvio(arquivos, destinoUuid, destinoNome) {
  Object.assign(dataCtx, { modo: "envio", valor: "", item: null, arquivos, destino: { uuid: destinoUuid, nome: destinoNome } });
  erroData.value = "";
  dataAberto.value = true;
}
async function salvarData(data) {
  if (dataCtx.modo === "envio") {
    const { arquivos, destino } = dataCtx;
    dataAberto.value = false;
    const recusados = upload.adicionarVideos(arquivos, { pastaUuid: destino.uuid, pastaNome: destino.nome, dataVideo: data });
    if (recusados.length) notify.erro(recusados.join(" "));
    return;
  }
  salvandoData.value = true;
  erroData.value = "";
  try {
    await atualizarVideo(dataCtx.item.uuid, { data_video: data });
    dataCtx.item.data_video = data;
    dataAberto.value = false;
    notify.sucesso(data ? "Data do vídeo atualizada." : "Data removida.");
    if (visao.value === "ano") recarregarVideos();
    else if (ordemServidor.value.startsWith("data_video")) lista.recarregar();
  } catch (e) {
    const info = interpretarErro(e);
    erroData.value = info.campos.data_video || info.mensagem;
  } finally {
    salvandoData.value = false;
  }
}

/* ---------- excluir ---------- */
async function excluirVideos(itens) {
  if (!itens.length) return;
  const n = itens.length;
  const ok = await confirmar({
    titulo: n === 1 ? `Excluir "${itens[0].titulo}"?` : `Excluir ${n} vídeos?`,
    texto: "Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  const r = await Promise.allSettled(itens.map((v) => excluirVideo(v.uuid)));
  const apagados = new Set(itens.filter((_, i) => r[i].status === "fulfilled").map((v) => v.uuid));
  lista.itens.value = lista.itens.value.filter((v) => !apagados.has(v.uuid));
  sel.remover([...apagados]);
  if (visao.value === "ano") recarregarVideos();
  carregarPastas({ silencioso: true });
  if (apagados.size < n) notify.erro(`${n - apagados.size} não puderam ser excluídos.`);
  else notify.sucesso(n === 1 ? "Vídeo excluído." : "Vídeos excluídos.");
}

async function excluirPastaCom(pasta) {
  const ok = await confirmar({ titulo: `Excluir a pasta "${pasta.nome}"?`, texto: "Só pastas vazias podem ser excluídas.", rotuloConfirmar: "Excluir" });
  if (!ok) return;
  try {
    await excluirPasta(pasta.uuid);
    notify.sucesso("Pasta excluída.");
    carregarPastas({ silencioso: true });
  } catch (e) {
    if (e?.response?.status === 409) mostrarAviso("Esvazie a pasta antes de excluir.", () => irPasta(pasta.uuid), "Abrir pasta");
    else tratar(e);
  }
}

/* ---------- upload ---------- */
const inputArquivos = ref(null);
function escolherArquivos() {
  inputArquivos.value?.click();
}
function aoEscolherArquivos(e) {
  enviarArquivos(Array.from(e.target.files || []), pastaUuid.value, conteudo.value.pasta_atual?.nome ?? "");
  e.target.value = "";
}
function enviarArquivos(arquivos, destinoUuid, destinoNome) {
  if (!auth.isMembro || !arquivos.length) return;
  // Pergunta a data (opcional) antes de colocar na fila de envio.
  pedirDataEnvio(arquivos, destinoUuid, destinoNome);
}
const { arrastando: arrastandoArquivos } = useDropArquivos((arquivos) => enviarArquivos(arquivos, pastaUuid.value, conteudo.value.pasta_atual?.nome ?? ""), {
  ativo: () => auth.isMembro,
});

onMounted(async () => {
  await Promise.all([carregarPastas(), lista.carregarProxima(), visao.value === "ano" ? carregarAnos() : null]);
});
</script>

<style>
.vid-snack .v-snackbar__wrapper {
  margin-bottom: calc(var(--tm-player-h, 0px) + 8px);
}
/* Fantasma do arrasto (clone fora da árvore do componente: sem escopo). */
.vid-voo {
  position: relative;
  padding: 8px 12px;
  border-radius: var(--tm-radius-sm);
  background: var(--tm-paper);
  color: var(--tm-ink);
  box-shadow: var(--tm-shadow-md);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 260px;
}
</style>

<style scoped>
.videos-page {
  min-height: 100dvh;
  padding-top: var(--tm-navbar-h);
  background: var(--tm-paper);
  color: var(--tm-ink);
}
.vid-cabeca {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  max-width: 1400px;
  margin: 0 auto;
  padding: 16px 16px 12px;
}
.vid-cabeca-titulo {
  margin: 0;
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: var(--tm-fs-3xl);
  line-height: 1.1;
}
.vid-cabeca-sub {
  margin: 2px 0 0;
  color: var(--tm-ink-muted);
}
.vid-contador {
  margin: 0;
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
  font-size: var(--tm-fs-sm);
}
.vid-ano-aviso {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.vid-ano-voltar {
  min-height: 44px;
  color: var(--tm-gold-text);
}
.vid-barra {
  position: sticky;
  top: var(--tm-navbar-h);
  z-index: 5;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 56px;
  padding: 0 12px;
  background: var(--tm-paper);
  border-bottom: 1px solid var(--tm-border);
}
.vid-barra.em-selecao {
  background: color-mix(in srgb, var(--tm-gold) 14%, var(--tm-paper));
}
.vid-selecao {
  display: flex;
  align-items: center;
  gap: 4px;
  width: 100%;
}
.vid-selecao-qtd {
  font-weight: 600;
  margin-left: 4px;
}
/* Arrastando: o(s) item(ns) ficam levemente apagados e encolhidos até soltar. */
.vid-corpo [data-arrastando="1"] {
  opacity: 0.45;
  transform: scale(0.97);
  transition:
    opacity 150ms var(--tm-ease),
    transform 150ms var(--tm-ease);
}
/* Troca grade <-> lista: sai esmaecendo, entra subindo 8px. */
.visao-enter-active {
  transition:
    opacity 250ms var(--tm-ease),
    transform 250ms var(--tm-ease);
}
.visao-leave-active {
  transition: opacity 120ms var(--tm-ease);
}
.visao-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.visao-leave-to {
  opacity: 0;
}
.vid-corpo {
  max-width: 1400px;
  margin: 0 auto;
  padding: 16px 16px 96px;
}
.vid-busca {
  max-width: 420px;
  margin-bottom: 16px;
}
.vid-secao-bloco {
  margin-bottom: 24px;
}
.vid-secao {
  margin: 0 0 12px;
  font-family: var(--tm-font-body);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--tm-ink-muted);
}
.pastas-grade {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}
.videos-grade {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}
.esq-pasta,
.esq-video {
  display: block;
  border-radius: var(--tm-radius-sm);
  background: var(--tm-paper-2);
}
.esq-pasta {
  height: 56px;
}
.esq-video {
  aspect-ratio: 16 / 11;
}
.vid-esqueleto .videos-grade {
  margin-top: 24px;
}
.vid-aviso {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 16px;
}
.vid-drop {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: grid;
  place-items: center;
  margin: 8px;
  border: 3px dashed var(--tm-gold);
  border-radius: var(--tm-radius-md);
  background: color-mix(in srgb, var(--tm-paper) 88%, transparent);
  pointer-events: none;
}
.vid-drop-caixa {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  font-size: var(--tm-fs-lg);
}
@media (prefers-reduced-motion: reduce) {
  .vid-corpo [data-arrastando="1"],
  .visao-enter-active,
  .visao-leave-active {
    transition: none;
  }
}
html[data-reduzir="1"] .vid-corpo [data-arrastando="1"],
html[data-reduzir="1"] .visao-enter-active,
html[data-reduzir="1"] .visao-leave-active {
  transition: none;
}
@media (max-width: 599px) {
  .pastas-grade {
    grid-template-columns: 1fr;
  }
  .videos-grade {
    grid-template-columns: 1fr;
  }
}
@media (min-width: 400px) and (max-width: 599px) {
  .pastas-grade {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 480px) and (max-width: 599px) {
  .videos-grade {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
