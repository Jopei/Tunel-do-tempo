<template>
  <section class="historia-show-page historias-era tm-immersive" :style="{ '--hx-ano-h': hue }">
    <v-progress-linear
      v-if="historia"
      :model-value="progresso"
      color="primary"
      height="3"
      class="barra-leitura no-print"
      aria-hidden="true"
    />

    <!-- Barra compacta fixa (aparece quando a barra de reações sai da tela) -->
    <div
      v-if="historia"
      class="hx-barra-fixa no-print"
      :class="{ 'hx-barra-fixa--visivel': barraFixa }"
      :inert="!barraFixa"
      role="toolbar"
      aria-label="Reações rápidas"
    >
      <button type="button" class="hx-barra-fixa__btn" :class="{ 'hx-acao--ativa': historia.favoritado }" :aria-pressed="!!historia.favoritado" @click="alternarFavorito">
        <v-icon aria-hidden="true">{{ historia.favoritado ? "mdi-heart" : "mdi-heart-outline" }}</v-icon>
        <span>{{ historia.favoritos_count || 0 }}</span>
        <span class="sr-only">{{ rotuloCurtidas }}</span>
      </button>
      <button type="button" class="hx-barra-fixa__btn" @click="comentar">
        <v-icon aria-hidden="true">mdi-comment-outline</v-icon>
        <span>{{ lembrancasTotal }}</span>
        <span class="sr-only">{{ lembrancasTotal === 1 ? "comentário" : "comentários" }}</span>
      </button>
      <button type="button" class="hx-barra-fixa__btn" aria-label="Compartilhar história" @click="compartilhar">
        <v-icon aria-hidden="true">mdi-share-variant-outline</v-icon>
      </button>
      <span class="hx-barra-fixa__titulo" aria-hidden="true">{{ historia.titulo }}</span>
    </div>

    <!-- Hero: capa de timeline -->
    <HistoriaHero v-if="historia" :capa="capaHero" :titulo="historia.titulo" :data="historia.data_historia" />
    <div v-else-if="carregando" class="hx-hero-esqueleto hx-skeleton" aria-hidden="true"></div>
    <div v-else class="hx-hero-esqueleto hx-hero-esqueleto--vazio">
      <nav class="hx-hero-esqueleto__migalhas" aria-label="Você está em">
        <router-link to="/historias"><v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> Histórias</router-link>
      </nav>
    </div>

    <div class="hx-folha" :class="{ 'hx-folha--sem-hero': !carregando && !historia }">
      <!-- carregando -->
      <div v-if="carregando" class="hx-carregando" aria-busy="true" aria-label="Carregando história">
        <div class="hx-carregando__autor">
          <span class="hx-skeleton hx-carregando__avatar"></span>
          <span class="hx-carregando__linhas">
            <span class="hx-skeleton hx-carregando__linha"></span>
            <span class="hx-skeleton hx-carregando__linha hx-carregando__linha--curta"></span>
          </span>
        </div>
        <span class="hx-skeleton hx-carregando__titulo"></span>
        <span v-for="n in 4" :key="n" class="hx-skeleton hx-carregando__linha"></span>
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
        <header class="hx-cabecalho">
          <HistoriaAutoria :usuarios="historia.usuarios ?? []" :data="historia.data_historia" :tamanho="40" empilhar>
            <template v-if="ano" #extra>
              <span class="hx-checkin"><v-icon size="14" aria-hidden="true">mdi-map-marker</v-icon> na Rua · {{ ano }}</span>
            </template>
          </HistoriaAutoria>

          <div class="hx-cabecalho__chips">
            <v-chip size="small" label :color="historia.tipo_historia === 'Principal' ? 'primary' : undefined">{{ historia.tipo_historia }}</v-chip>
            <span class="hx-cabecalho__leitura"><v-icon size="16" aria-hidden="true">mdi-clock-outline</v-icon> {{ minutos }} min de leitura</span>
          </div>

          <h1 class="hx-titulo tm-title">{{ historia.titulo }}</h1>
          <p v-if="historia.descricao_curta" class="hx-descricao">{{ historia.descricao_curta }}</p>
        </header>

        <!-- Quem estava lá -->
        <section v-if="historia.usuarios?.length" class="hx-presentes" aria-labelledby="hx-presentes-t">
          <h2 id="hx-presentes-t" class="hx-secao-titulo tm-title">Quem estava lá <span class="hx-contagem">({{ historia.usuarios.length }})</span></h2>
          <ul class="hx-presentes__lista" role="region" tabindex="0" aria-label="Pessoas marcadas nesta história">
            <li v-for="u in presentesVisiveis" :key="u.uuid">
              <router-link :to="{ name: 'membro', params: { slug: chaveMembro(u) } }" class="hx-presente">
                <AvatarUsuario :nome="u.nome" :src="u.foto_url" :size="56" />
                <span class="hx-presente__nome">{{ u.nome }}</span>
              </router-link>
            </li>
            <li v-if="restoPresentes > 0 && !todosPresentes">
              <button type="button" class="hx-presente hx-presente--mais" :aria-label="`Mostrar mais ${restoPresentes} pessoas`" @click="todosPresentes = true">
                <span class="hx-presente__mais">+{{ restoPresentes }}</span>
                <span class="hx-presente__nome">Ver todos</span>
              </button>
            </li>
          </ul>
        </section>

        <!-- Reações -->
        <div ref="reacoesRef" class="hx-reacoes no-print">
          <p class="hx-reacoes__contadores">
            <span v-if="historia.favoritos_count" class="hx-reacoes__curtidas">
              <v-icon size="16" color="error" aria-hidden="true">mdi-heart</v-icon> {{ rotuloCurtidas }}
            </span>
            <span class="hx-reacoes__resto">{{ resumoContadores }}</span>
          </p>
          <div class="hx-reacoes__acoes" role="group" aria-label="Reações">
            <button type="button" class="hx-acao hx-acao--curtir" :class="{ 'hx-acao--ativa': historia.favoritado }" :aria-pressed="!!historia.favoritado" @click="alternarFavorito">
              <v-icon size="22" aria-hidden="true" class="hx-acao__icone">{{ historia.favoritado ? "mdi-heart" : "mdi-heart-outline" }}</v-icon>
              {{ historia.favoritado ? REDE.verbos.curtido : REDE.verbos.curtir }}
            </button>
            <button type="button" class="hx-acao hx-acao--comentar" @click="comentar">
              <v-icon size="22" aria-hidden="true" class="hx-acao__icone">mdi-comment-outline</v-icon>
              {{ REDE.verbos.comentar }}
            </button>
            <button type="button" class="hx-acao hx-acao--compartilhar" @click="compartilhar">
              <v-icon size="22" aria-hidden="true" class="hx-acao__icone">mdi-share-variant-outline</v-icon>
              {{ REDE.verbos.compartilhar }}
            </button>
          </div>
        </div>

        <div class="hx-ferramentas no-print" role="toolbar" aria-label="Mais ações da história">
          <v-btn v-if="totalFotos" variant="text" prepend-icon="mdi-image-multiple-outline" @click="mostrarGaleriaFotos = true">Fotos ({{ totalFotos }})</v-btn>
          <v-btn v-if="totalVideos" variant="text" prepend-icon="mdi-video-outline" @click="mostrarGaleriaVideos = true">Vídeos ({{ totalVideos }})</v-btn>
          <v-btn v-if="totalMusicas" variant="text" prepend-icon="mdi-music" @click="mostrarGaleriaMusicas = true">Músicas ({{ totalMusicas }})</v-btn>
          <v-spacer />
          <v-btn-group density="comfortable" variant="text" class="hx-ferramentas__tamanho" aria-label="Tamanho do texto">
            <v-btn aria-label="Diminuir texto" :disabled="tamanho <= 16" @click="mudarTamanho(-1)">A-</v-btn>
            <v-btn aria-label="Aumentar texto" :disabled="tamanho >= 22" @click="mudarTamanho(1)">A+</v-btn>
          </v-btn-group>
          <v-btn icon variant="text" aria-label="Imprimir ou salvar em PDF" @click="imprimir">
            <v-icon>mdi-printer-outline</v-icon>
          </v-btn>
          <v-btn v-if="auth.isMembro" icon variant="text" aria-label="Editar história" @click="dialogEditar = true">
            <v-icon>mdi-pencil-outline</v-icon>
          </v-btn>
        </div>

        <details v-if="indice.length >= 3" class="hx-indice no-print">
          <summary>Nesta história</summary>
          <ul>
            <li v-for="h in indice" :key="h.id" :class="`n${h.nivel}`">
              <a :href="`#${h.id}`" @click.prevent="irPara(h.id)">{{ h.texto }}</a>
            </li>
          </ul>
        </details>

        <article ref="leituraRef" class="tm-prose hx-conteudo" :style="{ '--tm-reading-size': `${tamanho}px` }" v-html="conteudoHtml"></article>

        <!-- Álbum e mídia -->
        <HistoriaAlbum :fotos="fotosAlbum" class="no-print" @abrir="abrirFoto" />

        <section v-if="videos.length" class="hx-videos no-print" aria-labelledby="hx-videos-t">
          <h2 id="hx-videos-t" class="hx-secao-titulo tm-title">Vídeos</h2>
          <ul class="hx-videos__lista" role="region" tabindex="0" aria-label="Vídeos da história">
            <li v-for="(v, i) in videos.slice(0, 4)" :key="v.uuid">
              <button type="button" class="hx-video hx-foto" :aria-label="`Assistir ${v.titulo || `vídeo ${i + 1}`}`" @click="mostrarGaleriaVideos = true">
                <img v-if="v.poster_url" :src="v.poster_url" alt="" class="hx-foto__img" loading="lazy" decoding="async" />
                <span class="hx-video__play" aria-hidden="true"><v-icon size="32">mdi-play</v-icon></span>
                <span v-if="v.duracao" class="hx-video__duracao" aria-hidden="true">{{ formatarDuracao(v.duracao) }}</span>
              </button>
            </li>
          </ul>
        </section>

        <section v-if="musicas.length" class="hx-musicas no-print" aria-labelledby="hx-musicas-t">
          <h2 id="hx-musicas-t" class="hx-secao-titulo tm-title">Músicas</h2>
          <button type="button" class="hx-musica" @click="mostrarGaleriaMusicas = true">
            <v-icon aria-hidden="true">mdi-music-circle-outline</v-icon>
            <span class="hx-musica__texto">
              <small>Tocando agora</small>
              <strong>{{ musicas[0].titulo || "Trilha da história" }}</strong>
            </span>
            <span v-if="musicas.length > 1" class="hx-musica__mais">+{{ musicas.length - 1 }}</span>
          </button>
        </section>

        <LembrancasSecao ref="lembrancasRef" :key="historia.uuid" :historia-uuid="historia.uuid" comentarios class="no-print" @mudou="aoMudarLembrancas" />

        <HistoriaVizinhas :anterior="historia.anterior" :proxima="historia.proxima" :ano-atual="ano" />
      </template>
    </div>

    <template v-if="historia">
      <GaleriaFotos
        ref="galeriaFotosRef"
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
import { chaveMembro } from "@/services/membro.service";
import { useAuthStore } from "@/stores/auth";
import { useNotify } from "@/composables/useNotify";
import { sanitizar } from "@/composables/useSanitize";
import { tempoLeitura, formatarDuracao } from "@/composables/useFormat";
import { anoDe, hueDoAno } from "@/composables/useCapitulos";
import { REDE } from "@/config/esquina";
import GaleriaFotos from "@/components/historias/GaleriaFotos.vue";
import GaleriaVideos from "@/components/historias/GaleriaVideos.vue";
import GaleriaMusicas from "@/components/historias/GaleriaMusicas.vue";
import EditarHistoriaModal from "@/components/historias/EditarHistoriaModal.vue";
import HistoriaHero from "@/components/historias/HistoriaHero.vue";
import HistoriaAutoria from "@/components/historias/HistoriaAutoria.vue";
import HistoriaAlbum from "@/components/historias/HistoriaAlbum.vue";
import HistoriaVizinhas from "@/components/historias/HistoriaVizinhas.vue";
import LembrancasSecao from "@/components/ui/LembrancasSecao.vue";
import { favoritarHistoria, desfavoritarHistoria } from "@/services/favorito.service";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import "@/assets/historias-era.css";
import "@/assets/historias-motion.css";

const CHAVE_TAMANHO = "tunel-leitura-tamanho";
const MAX_PRESENTES = 8;

const route = useRoute();
const auth = useAuthStore();
const notify = useNotify();

const historia = ref(null);
const carregando = ref(true);
const erro = ref(false);
const status = ref(null);
const progresso = ref(0);
const leituraRef = ref(null);
const reacoesRef = ref(null);
const lembrancasRef = ref(null);
const galeriaFotosRef = ref(null);
const barraFixa = ref(false);
const todosPresentes = ref(false);
const lembrancasTotal = ref(0);
const tamanho = ref(Number(localStorage.getItem(CHAVE_TAMANHO)) || 18);

const mostrarGaleriaFotos = ref(false);
const mostrarGaleriaVideos = ref(false);
const mostrarGaleriaMusicas = ref(false);
const dialogEditar = ref(false);

const ano = computed(() => anoDe(historia.value));
const hue = computed(() => hueDoAno(ano.value));

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

// Mídia
const fotosAlbum = computed(() => historia.value?.fotos_midia ?? []);
const videos = computed(() => historia.value?.videos_midia ?? []);
const musicas = computed(() => historia.value?.musicas_midia ?? []);
const totalFotos = computed(() => (historia.value?.fotos_midia ?? historia.value?.fotos)?.length || 0);
const totalVideos = computed(() => (historia.value?.videos_midia ?? historia.value?.videos)?.length || 0);
const totalMusicas = computed(() => (historia.value?.musicas_midia ?? historia.value?.musicas)?.length || 0);
// Hero em largura total: prefere a foto original; capa_url costuma ser a miniatura.
const capaHero = computed(() => fotosAlbum.value[0]?.url || historia.value?.capa_url || null);

const presentesVisiveis = computed(() => {
  const todos = historia.value?.usuarios ?? [];
  return todosPresentes.value ? todos : todos.slice(0, MAX_PRESENTES);
});
const restoPresentes = computed(() => Math.max(0, (historia.value?.usuarios?.length ?? 0) - MAX_PRESENTES));

const plural = (n, s, p) => `${n} ${n === 1 ? s : p}`;
const rotuloCurtidas = computed(() => {
  const n = historia.value?.favoritos_count || 0;
  return `${n} ${n === 1 ? "pessoa curtiu" : "pessoas curtiram"}`;
});
const resumoContadores = computed(() => {
  const p = [];
  if (lembrancasTotal.value) p.push(plural(lembrancasTotal.value, "comentário", "comentários"));
  if (totalFotos.value) p.push(plural(totalFotos.value, "foto", "fotos"));
  if (totalVideos.value) p.push(plural(totalVideos.value, "vídeo", "vídeos"));
  return p.join(" · ");
});

let tokenCarga = 0;
async function carregar() {
  const meu = ++tokenCarga;
  const uuid = route.params.uuid;
  carregando.value = true;
  erro.value = false;
  status.value = null;
  todosPresentes.value = false;
  try {
    const { data } = await api.get(`/historias/${uuid}`);
    if (meu !== tokenCarga) return; // chegou depois de outra navegação
    historia.value = data.data ?? data;
    lembrancasTotal.value = historia.value.lembrancas_count ?? 0;
    document.title = `${historia.value.titulo} · Túnel do Tempo`;
  } catch (e) {
    if (meu !== tokenCarga) return;
    historia.value = null;
    document.title = "Túnel do Tempo";
    status.value = e.response?.status ?? null;
    erro.value = status.value !== 404;
  } finally {
    if (meu === tokenCarga) carregando.value = false;
  }
  if (meu !== tokenCarga) return;
  await nextTick();
  observarReacoes();
  if (historia.value && route.hash === "#lembrancas") irParaLembrancas();
}

// Estado otimista: inverte já e desfaz se a API falhar.
async function alternarFavorito() {
  const h = historia.value;
  const novo = !h.favoritado;
  const antes = h.favoritos_count || 0;
  h.favoritado = novo;
  h.favoritos_count = Math.max(0, antes + (novo ? 1 : -1));
  try {
    await (novo ? favoritarHistoria : desfavoritarHistoria)(h.uuid);
    notify.sucesso(novo ? "Guardada na sua memória." : "Removida das suas memórias.");
  } catch {
    h.favoritado = !novo;
    h.favoritos_count = antes;
    notify.erro("Não foi possível atualizar. Tente de novo.");
  }
}

function mudarTamanho(delta) {
  tamanho.value = Math.min(22, Math.max(16, tamanho.value + delta));
  localStorage.setItem(CHAVE_TAMANHO, String(tamanho.value));
}

async function compartilhar() {
  const url = location.href.split("#")[0];
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

const reduzirMovimento = () =>
  matchMedia("(prefers-reduced-motion: reduce)").matches || document.documentElement.dataset.reduzir === "1";

function irPara(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: reduzirMovimento() ? "auto" : "smooth", block: "start" });
}

function irParaLembrancas() {
  document.getElementById("lembrancas")?.scrollIntoView({ behavior: reduzirMovimento() ? "auto" : "smooth", block: "start" });
}

function comentar() {
  irParaLembrancas();
  setTimeout(() => lembrancasRef.value?.focar(), reduzirMovimento() ? 0 : 350);
}

function aoMudarLembrancas(total) {
  if (total != null) lembrancasTotal.value = total;
}

function abrirFoto(i) {
  // Último item do mosaico com "+N" abre a grade completa; os demais abrem direto na foto.
  const ultimoComResto = i === 4 && fotosAlbum.value.length > 5;
  if (ultimoComResto) mostrarGaleriaFotos.value = true;
  else galeriaFotosRef.value?.abrirEm(i);
}

function aoRolar() {
  const el = leituraRef.value;
  if (!el) return;
  const r = el.getBoundingClientRect();
  const total = r.height - window.innerHeight * 0.6;
  progresso.value = total <= 0 ? 100 : Math.min(100, Math.max(0, (-r.top / total) * 100));
}

let observador = null;
function observarReacoes() {
  observador?.disconnect();
  if (!reacoesRef.value || !("IntersectionObserver" in window)) return;
  observador = new IntersectionObserver(
    ([e]) => (barraFixa.value = !e.isIntersecting && e.boundingClientRect.top < 0),
    { rootMargin: "-64px 0px 0px 0px" },
  );
  observador.observe(reacoesRef.value);
}

watch(
  () => route.params.uuid,
  (novo, antigo) => {
    if (novo && novo !== antigo && route.name === "historias-show") {
      window.scrollTo({ top: 0 });
      carregar();
    }
  },
);

onMounted(() => {
  window.addEventListener("scroll", aoRolar, { passive: true });
  carregar();
});
onBeforeUnmount(() => {
  window.removeEventListener("scroll", aoRolar);
  tokenCarga++;
  observador?.disconnect();
});
</script>

<style scoped>
.historia-show-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding-top: var(--tm-navbar-h);
  padding-bottom: var(--tm-space-7);
}
.barra-leitura {
  position: fixed !important;
  top: var(--tm-navbar-h);
  left: 0;
  right: 0;
  z-index: 999;
}

/* Barra compacta fixa. Gancho motion: .hx-barra-fixa--visivel */
.hx-barra-fixa {
  position: fixed;
  top: calc(var(--tm-navbar-h) + 3px);
  left: 0;
  right: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--tm-space-2);
  padding: 4px var(--tm-space-3);
  background: color-mix(in srgb, var(--tm-paper) 88%, transparent);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--tm-border);
  color: var(--tm-ink);
  visibility: hidden;
  opacity: 0;
  transform: translateY(-100%);
}
.hx-barra-fixa--visivel {
  visibility: visible;
  opacity: 1;
  transform: none;
}
.hx-barra-fixa__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
  min-width: 44px;
  justify-content: center;
  padding: 0 var(--tm-space-3);
  border: 0;
  border-radius: var(--tm-radius-pill);
  background: transparent;
  color: var(--tm-ink);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.hx-barra-fixa__btn:hover {
  background: var(--hx-balao);
}
.hx-barra-fixa__btn:focus-visible,
.hx-acao:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
}
.hx-barra-fixa__titulo {
  margin-left: var(--tm-space-3);
  max-width: 30ch;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
@media (max-width: 599px) {
  .hx-barra-fixa__titulo {
    display: none;
  }
}

/* Estado sem hero (404/erro) */
.hx-hero-esqueleto {
  height: var(--hx-hero-h);
  border-radius: 0;
}
.hx-hero-esqueleto--vazio {
  height: auto;
  padding: var(--tm-space-4);
  max-width: 900px;
  margin: 0 auto;
}
.hx-hero-esqueleto__migalhas a {
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
  background: rgba(0, 0, 0, 0.6);
  padding: 0 12px;
  border-radius: var(--tm-radius-pill);
}

/* Folha de leitura */
.hx-folha {
  position: relative;
  z-index: 1;
  max-width: var(--hx-feed-w);
  margin: -40px auto 0;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-5) var(--tm-space-5) var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
.hx-folha--sem-hero {
  margin-top: var(--tm-space-4);
}
.hx-cabecalho__chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--tm-space-3);
  margin-top: var(--tm-space-4);
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.hx-cabecalho__leitura {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.hx-checkin {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 0 8px;
  border-radius: var(--tm-radius-pill);
  background: var(--hx-balao);
  margin-left: 4px;
}
.hx-titulo {
  font-size: var(--tm-fs-3xl);
  margin: var(--tm-space-3) 0 var(--tm-space-2);
  overflow-wrap: anywhere;
}
.hx-descricao {
  font-size: var(--tm-fs-lg);
  color: var(--tm-ink-muted);
  margin: 0 0 var(--tm-space-4);
}
.hx-secao-titulo {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.hx-contagem {
  font-size: var(--tm-fs-md);
  color: var(--tm-ink-muted);
  font-weight: 400;
}

/* Quem estava lá */
.hx-presentes {
  margin-block: var(--tm-space-5);
}
.hx-presentes__lista {
  list-style: none;
  margin: 0;
  padding: 0 0 var(--tm-space-2);
  display: flex;
  gap: var(--tm-space-3);
  overflow-x: auto;
}
.hx-presentes__lista:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
@media (min-width: 600px) {
  .hx-presentes__lista {
    flex-wrap: wrap;
    overflow: visible;
  }
}
.hx-presente {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  width: 80px;
  min-height: 44px;
  padding: 4px;
  border: 0;
  background: transparent;
  color: var(--tm-ink);
  text-decoration: none;
  border-radius: var(--tm-radius-sm);
  font: inherit;
  cursor: pointer;
}
.hx-presente:hover {
  background: var(--hx-balao);
}
.hx-presente:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 1px;
}
.hx-presente__nome {
  font-size: var(--tm-fs-sm);
  text-align: center;
  line-height: 1.2;
  overflow-wrap: anywhere;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hx-presente__mais {
  display: grid;
  place-items: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--hx-balao);
  font-weight: 700;
}

/* Reações */
.hx-reacoes {
  border-block: 1px solid var(--tm-border);
  margin-block: var(--tm-space-4);
}
.hx-reacoes__contadores {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--tm-space-2);
  margin: 0;
  padding: var(--tm-space-3) 0 var(--tm-space-2);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  min-height: 1.5em;
}
.hx-reacoes__curtidas {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.hx-reacoes__acoes {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-top: 1px solid var(--tm-border);
}
.hx-acao {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  background: transparent;
  border: 0;
  color: var(--tm-ink-muted);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.hx-acao + .hx-acao {
  border-left: 1px solid var(--tm-border);
}
.hx-acao:hover {
  background: var(--hx-balao);
  color: var(--tm-ink);
}
.hx-acao--ativa {
  color: var(--hx-acao-texto);
}
.hx-acao--ativa .hx-acao__icone,
.hx-barra-fixa__btn.hx-acao--ativa .v-icon {
  color: var(--hx-acao-ativa);
}
.hx-ferramentas {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--tm-space-1);
  margin-bottom: var(--tm-space-5);
}

.hx-indice {
  background: var(--hx-balao);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3) var(--tm-space-4);
  margin-bottom: var(--tm-space-5);
}
.hx-indice summary {
  cursor: pointer;
  font-weight: 700;
  min-height: 44px;
  display: flex;
  align-items: center;
}
.hx-indice ul {
  list-style: none;
  padding: 0;
  margin: 0;
}
.hx-indice li.n2 {
  padding-left: var(--tm-space-4);
}
.hx-indice li.n3 {
  padding-left: var(--tm-space-6);
}
.hx-indice a {
  color: var(--tm-gold-text);
  display: inline-block;
  padding: 8px 0;
}

/* Imagens embutidas no texto com moldura de foto */
.hx-conteudo :deep(img) {
  border: 6px solid #fffdf5;
  border-radius: 4px;
  box-shadow: var(--tm-shadow-sm);
  filter: var(--hx-filtro-foto);
  max-width: 100%;
  height: auto;
}

/* Vídeos e músicas */
.hx-videos,
.hx-musicas {
  margin-block: var(--tm-space-5);
}
.hx-videos__lista {
  list-style: none;
  margin: 0;
  padding: 0 0 var(--tm-space-2);
  display: flex;
  gap: var(--tm-space-3);
  overflow-x: auto;
}
.hx-videos__lista:focus-visible {
  outline: 3px solid #f0c86a;
}
.hx-video {
  width: 200px;
  aspect-ratio: 16 / 9;
  border: 0;
  padding: 0;
  border-radius: var(--tm-radius-sm);
  background: var(--hx-ano-selo);
  cursor: pointer;
  display: grid;
  place-items: center;
}
.hx-video:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
.hx-video__play {
  position: relative;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(20, 15, 5, 0.75);
  color: #fff;
}
.hx-video__duracao {
  position: absolute;
  z-index: 1;
  right: 8px;
  bottom: 8px;
  padding: 0 6px;
  border-radius: 4px;
  background: rgba(20, 15, 5, 0.8);
  color: #fff;
  font-size: var(--tm-fs-xs);
}
.hx-musica {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  width: 100%;
  min-height: 56px;
  padding: var(--tm-space-2) var(--tm-space-4);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: var(--hx-balao);
  color: var(--tm-ink);
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.hx-musica:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
.hx-musica__texto {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.hx-musica__texto small {
  color: var(--tm-ink-muted);
}
.hx-musica__mais {
  color: var(--tm-ink-muted);
  font-weight: 700;
}

/* Carregando */
.hx-carregando {
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-3);
}
.hx-carregando__autor {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
}
.hx-carregando__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}
.hx-carregando__linhas {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.hx-carregando__linha {
  display: block;
  height: 14px;
  width: 100%;
}
.hx-carregando__linha--curta {
  width: 40%;
}
.hx-carregando__titulo {
  display: block;
  height: 40px;
  width: 80%;
}

@media (max-width: 599px) {
  .hx-folha {
    margin-top: -24px;
    border-radius: var(--tm-radius-md);
    padding: var(--tm-space-4);
  }
  .hx-ferramentas__tamanho {
    display: none;
  }
}
@media print {
  .historia-show-page {
    background: #fff;
    padding: 0;
  }
  .hx-folha {
    box-shadow: none;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
    max-width: none;
  }
  .hx-hero-esqueleto,
  .hx-reacoes,
  .hx-ferramentas {
    display: none;
  }
}
</style>
