<template>
  <section class="historia-show-page tm-immersive">
    <v-progress-linear
      v-if="historia"
      :model-value="progresso"
      color="primary"
      height="3"
      class="barra-leitura no-print"
      aria-hidden="true"
    />

    <div class="painel">
      <nav class="migalhas no-print" aria-label="Você está em">
        <router-link to="/historias"><v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> Histórias</router-link>
        <template v-if="historia">
          <span aria-hidden="true">/</span>
          <span aria-current="page" class="atual">{{ historia.titulo }}</span>
        </template>
      </nav>

      <!-- carregando -->
      <div v-if="carregando" class="carregando" aria-busy="true" aria-label="Carregando história">
        <v-skeleton-loader type="heading" />
        <v-skeleton-loader type="paragraph, paragraph, paragraph" />
      </div>

      <!-- não encontrada -->
      <TmEmptyState
        v-else-if="status === 404"
        icone="mdi-book-remove-outline"
        titulo="Esta história não existe ou foi removida"
        texto="Ela pode ter mudado de lugar. Volte à lista para continuar."
      >
        <v-btn to="/historias" variant="flat" color="primary">Voltar às histórias</v-btn>
      </TmEmptyState>

      <!-- erro -->
      <TmErrorState
        v-else-if="erro"
        :titulo="status === 403 ? 'Você não pode ver esta história' : 'Não foi possível carregar a história'"
        :texto="status === 403 ? 'Peça acesso a um membro da rua.' : 'Verifique sua conexão e tente de novo.'"
        @retry="carregar"
      />

      <template v-else-if="historia">
        <header class="cabecalho">
          <h1 class="titulo">{{ historia.titulo }}</h1>
          <p v-if="historia.descricao_curta" class="descricao">{{ historia.descricao_curta }}</p>

          <div class="meta">
            <v-chip size="small" label :color="historia.tipo_historia === 'Principal' ? 'primary' : undefined">
              {{ historia.tipo_historia }}
            </v-chip>
            <span v-if="historia.data_historia" class="meta-item">
              <v-icon size="16" aria-hidden="true">mdi-calendar</v-icon>
              <time :datetime="historia.data_historia">{{ formatarData(historia.data_historia) }}</time>
            </span>
            <span class="meta-item">
              <v-icon size="16" aria-hidden="true">mdi-clock-outline</v-icon>
              {{ minutos }} min de leitura
            </span>
          </div>

          <div v-if="historia.usuarios?.length" class="membros">
            <span class="por">Por</span>
            <router-link
              v-for="u in historia.usuarios"
              :key="u.uuid"
              :to="{ name: 'membro', params: { uuid: u.uuid } }"
              class="membro"
            >
              <AvatarUsuario :nome="u.nome" :size="28" />
              <span>{{ u.nome }}</span>
            </router-link>
          </div>
        </header>

        <div class="barra-acoes no-print" role="toolbar" aria-label="Ações da história">
          <v-btn
            variant="tonal"
            prepend-icon="mdi-image-multiple-outline"
            :disabled="!(historia.fotos_midia ?? historia.fotos)?.length"
            @click="mostrarGaleriaFotos = true"
          >
            Fotos ({{ (historia.fotos_midia ?? historia.fotos)?.length || 0 }})
            <v-tooltip v-if="!(historia.fotos_midia ?? historia.fotos)?.length" activator="parent" location="top">Sem fotos</v-tooltip>
          </v-btn>
          <v-btn
            variant="tonal"
            prepend-icon="mdi-video-outline"
            :disabled="!(historia.videos_midia ?? historia.videos)?.length"
            @click="mostrarGaleriaVideos = true"
          >
            Vídeos ({{ (historia.videos_midia ?? historia.videos)?.length || 0 }})
            <v-tooltip v-if="!(historia.videos_midia ?? historia.videos)?.length" activator="parent" location="top">Sem vídeos</v-tooltip>
          </v-btn>
          <v-btn
            variant="tonal"
            prepend-icon="mdi-music"
            :disabled="!(historia.musicas_midia ?? historia.musicas)?.length"
            @click="mostrarGaleriaMusicas = true"
          >
            Músicas ({{ (historia.musicas_midia ?? historia.musicas)?.length || 0 }})
            <v-tooltip v-if="!(historia.musicas_midia ?? historia.musicas)?.length" activator="parent" location="top">Sem músicas</v-tooltip>
          </v-btn>

          <v-spacer />

          <v-btn-group density="comfortable" variant="text" class="tamanho" aria-label="Tamanho do texto">
            <v-btn aria-label="Diminuir texto" :disabled="tamanho <= 16" @click="mudarTamanho(-1)">A-</v-btn>
            <v-btn aria-label="Aumentar texto" :disabled="tamanho >= 22" @click="mudarTamanho(1)">A+</v-btn>
          </v-btn-group>
          <v-btn
            variant="text"
            :prepend-icon="historia.favoritado ? 'mdi-heart' : 'mdi-heart-outline'"
            :aria-pressed="!!historia.favoritado"
            :color="historia.favoritado ? 'error' : undefined"
            @click="alternarFavorito"
          >
            {{ historia.favoritado ? "Guardada na memória" : "Guardar na memória" }}
          </v-btn>
          <v-btn icon variant="text" aria-label="Compartilhar história" @click="compartilhar">
            <v-icon>mdi-share-variant-outline</v-icon>
          </v-btn>
          <v-btn icon variant="text" aria-label="Imprimir ou salvar em PDF" @click="imprimir">
            <v-icon>mdi-printer-outline</v-icon>
          </v-btn>
          <v-btn v-if="auth.isMembro" icon variant="text" aria-label="Editar história" @click="dialogEditar = true">
            <v-icon>mdi-pencil-outline</v-icon>
          </v-btn>
        </div>

        <nav v-if="indice.length >= 3" class="indice no-print" aria-label="Índice da história">
          <h2 class="indice-titulo">Nesta história</h2>
          <ul>
            <li v-for="h in indice" :key="h.id" :class="`n${h.nivel}`">
              <a :href="`#${h.id}`" @click.prevent="irPara(h.id)">{{ h.texto }}</a>
            </li>
          </ul>
        </nav>

        <article ref="leituraRef" class="tm-prose conteudo" :style="{ '--tm-reading-size': `${tamanho}px` }" v-html="conteudoHtml"></article>

        <LembrancasSecao :key="historia.uuid" :historia-uuid="historia.uuid" class="no-print" />

        <nav v-if="anterior || proxima" class="vizinhas no-print" aria-label="Outras histórias">
          <router-link v-if="anterior" :to="{ name: 'historias-show', params: { uuid: anterior.uuid } }" class="viz ant">
            <span class="dir"><v-icon size="16" aria-hidden="true">mdi-arrow-left</v-icon> Anterior</span>
            <strong>{{ anterior.titulo }}</strong>
          </router-link>
          <router-link v-if="proxima" :to="{ name: 'historias-show', params: { uuid: proxima.uuid } }" class="viz prox">
            <span class="dir">Próxima <v-icon size="16" aria-hidden="true">mdi-arrow-right</v-icon></span>
            <strong>{{ proxima.titulo }}</strong>
          </router-link>
        </nav>
      </template>
    </div>

    <template v-if="historia">
      <GaleriaFotos
        v-model="mostrarGaleriaFotos"
        :fotos="historia.fotos"
        :midia="historia.fotos_midia"
        :titulo-historia="historia.titulo"
      />
      <GaleriaVideos v-model="mostrarGaleriaVideos" :videos="historia.videos" :midia="historia.videos_midia" />
      <GaleriaMusicas v-model="mostrarGaleriaMusicas" :musicas="historia.musicas" :midia="historia.musicas_midia" />
      <EditarHistoriaModal v-if="auth.isMembro" v-model="dialogEditar" :historia="historia" @confirmar="carregar" />
    </template>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useRoute } from "vue-router";
import api from "@/services/api";
import { buscarHistoriasResumo } from "@/services/historia.service";
import { useAuthStore } from "@/stores/auth";
import { useNotify } from "@/composables/useNotify";
import { sanitizar } from "@/composables/useSanitize";
import { formatarData, tempoLeitura } from "@/composables/useFormat";
import GaleriaFotos from "@/components/historias/GaleriaFotos.vue";
import GaleriaVideos from "@/components/historias/GaleriaVideos.vue";
import GaleriaMusicas from "@/components/historias/GaleriaMusicas.vue";
import EditarHistoriaModal from "@/components/historias/EditarHistoriaModal.vue";
import LembrancasSecao from "@/components/ui/LembrancasSecao.vue";
import { favoritarHistoria, desfavoritarHistoria } from "@/services/favorito.service";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";

const CHAVE_TAMANHO = "tunel-leitura-tamanho";

const route = useRoute();
const auth = useAuthStore();
const notify = useNotify();

const historia = ref(null);
const carregando = ref(true);
const erro = ref(false);
const status = ref(null);
const lista = ref([]);
const progresso = ref(0);
const leituraRef = ref(null);
const tamanho = ref(Number(localStorage.getItem(CHAVE_TAMANHO)) || 18);

const mostrarGaleriaFotos = ref(false);
const mostrarGaleriaVideos = ref(false);
const mostrarGaleriaMusicas = ref(false);
const dialogEditar = ref(false);

// Conteúdo sanitizado + ids nos títulos para o índice
const conteudoProcessado = computed(() => {
  const limpo = sanitizar(historia.value?.conteudo ?? "");
  const doc = new DOMParser().parseFromString(`<div>${limpo}</div>`, "text/html");
  const titulos = [];
  doc.querySelectorAll("h1, h2, h3").forEach((h, i) => {
    const id = `secao-${i + 1}`;
    h.id = id;
    titulos.push({ id, nivel: Number(h.tagName[1]), texto: h.textContent.trim() });
  });
  return { html: doc.body.firstElementChild.innerHTML, titulos };
});
const conteudoHtml = computed(() => conteudoProcessado.value.html);
const indice = computed(() => conteudoProcessado.value.titulos.filter((t) => t.texto));
const minutos = computed(() => tempoLeitura(historia.value?.conteudo ?? ""));

const posicao = computed(() => lista.value.findIndex((h) => h.uuid === route.params.uuid));
const anterior = computed(() => (posicao.value > 0 ? lista.value[posicao.value - 1] : null));
const proxima = computed(() =>
  posicao.value >= 0 && posicao.value < lista.value.length - 1 ? lista.value[posicao.value + 1] : null,
);

async function carregar() {
  carregando.value = true;
  erro.value = false;
  status.value = null;
  try {
    const { data } = await api.get(`/historias/${route.params.uuid}`);
    historia.value = data.data ?? data;
    document.title = `${historia.value.titulo} · Túnel do Tempo`;
  } catch (e) {
    historia.value = null;
    status.value = e.response?.status ?? null;
    erro.value = status.value !== 404;
  } finally {
    carregando.value = false;
  }
}

async function carregarVizinhas() {
  try {
    const r = await buscarHistoriasResumo();
    lista.value = Array.isArray(r) ? r : (r?.data ?? []);
  } catch {
    lista.value = [];
  }
}

// Estado otimista: inverte já e desfaz se a API falhar.
async function alternarFavorito() {
  const h = historia.value;
  const novo = !h.favoritado;
  h.favoritado = novo;
  try {
    await (novo ? favoritarHistoria : desfavoritarHistoria)(h.uuid);
    notify.sucesso(novo ? "Guardada na sua memória." : "Removida das suas memórias.");
  } catch {
    h.favoritado = !novo;
    notify.erro("Não foi possível atualizar. Tente de novo.");
  }
}

function mudarTamanho(delta) {
  tamanho.value = Math.min(22, Math.max(16, tamanho.value + delta));
  localStorage.setItem(CHAVE_TAMANHO, String(tamanho.value));
}

async function compartilhar() {
  const url = location.href;
  const dados = { title: historia.value.titulo, text: historia.value.descricao_curta || historia.value.titulo, url };
  if (navigator.share) {
    try {
      await navigator.share(dados);
    } catch {
      /* cancelado pelo usuário */
    }
    return;
  }
  try {
    await navigator.clipboard.writeText(url);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

function imprimir() {
  window.print();
}

function irPara(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function aoRolar() {
  const el = leituraRef.value;
  if (!el) return;
  const r = el.getBoundingClientRect();
  const total = r.height - window.innerHeight * 0.6;
  progresso.value = total <= 0 ? 100 : Math.min(100, Math.max(0, (-r.top / total) * 100));
}

watch(
  () => route.params.uuid,
  (novo, antigo) => {
    if (novo && novo !== antigo && route.name === "historias-show") carregar();
  },
);

onMounted(async () => {
  window.addEventListener("scroll", aoRolar, { passive: true });
  await carregar();
  await nextTick();
  carregarVizinhas();
});
onBeforeUnmount(() => window.removeEventListener("scroll", aoRolar));
</script>

<style scoped>
.historia-show-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-7);
}
.barra-leitura {
  position: fixed !important;
  top: var(--tm-navbar-h);
  left: 0;
  right: 0;
  z-index: 999;
}
.painel {
  max-width: 900px;
  margin: 0 auto;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6) var(--tm-space-7) var(--tm-space-7);
  box-shadow: var(--tm-shadow-lg);
}
.migalhas {
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  margin-bottom: var(--tm-space-5);
  min-width: 0;
}
.migalhas a {
  color: var(--tm-gold-text);
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
}
.migalhas .atual {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.cabecalho {
  text-align: center;
  margin-bottom: var(--tm-space-5);
}
.titulo {
  font-size: var(--tm-fs-3xl);
  margin-bottom: var(--tm-space-2);
}
.descricao {
  font-size: var(--tm-fs-lg);
  color: var(--tm-ink-muted);
  margin: 0 auto var(--tm-space-4);
  max-width: 60ch;
}
.meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-3);
  justify-content: center;
  align-items: center;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.membros {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-2);
  justify-content: center;
  align-items: center;
  margin-top: var(--tm-space-4);
}
.por {
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.membro {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: inherit;
  text-decoration: none;
  padding: 2px 10px 2px 2px;
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-pill);
  min-height: 36px;
}
.membro:hover {
  background: rgb(var(--v-theme-surface-variant));
}
.barra-acoes {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--tm-space-2);
  padding: var(--tm-space-3) 0;
  border-block: 1px solid var(--tm-border);
  margin-bottom: var(--tm-space-5);
}
.indice {
  background: rgb(var(--v-theme-surface-variant));
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-4);
  margin-bottom: var(--tm-space-5);
}
.indice-titulo {
  font-size: var(--tm-fs-md);
  margin-bottom: var(--tm-space-2);
}
.indice ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.indice li.n2 {
  padding-left: var(--tm-space-4);
}
.indice li.n3 {
  padding-left: var(--tm-space-6);
}
.indice a {
  color: var(--tm-gold-text);
  display: inline-block;
  padding: 4px 0;
}
.vizinhas {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-4);
  margin-top: var(--tm-space-7);
  padding-top: var(--tm-space-5);
  border-top: 1px solid var(--tm-border);
}
.viz {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: var(--tm-space-3) var(--tm-space-4);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  color: inherit;
  text-decoration: none;
}
.viz:hover {
  background: rgb(var(--v-theme-surface-variant));
}
.viz.prox {
  text-align: right;
  grid-column: 2;
}
.dir {
  color: var(--tm-gold-text);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
  .vizinhas {
    grid-template-columns: 1fr;
  }
  .viz.prox {
    grid-column: 1;
  }
  .barra-acoes .tamanho {
    display: none;
  }
}
@media print {
  .historia-show-page {
    background: #fff;
    padding: 0;
  }
  .painel {
    box-shadow: none;
    padding: 0;
    background: #fff;
    color: #000;
  }
  .barra-acoes,
  .migalhas {
    display: none;
  }
}
</style>
