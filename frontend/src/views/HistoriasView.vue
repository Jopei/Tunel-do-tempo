<template>
  <section class="historias-page historias-era tm-immersive">
    <!-- Capa da rede -->
    <header class="hx-capa">
      <div class="hx-capa__fundo hx-grao" aria-hidden="true"></div>
      <div class="hx-capa__conteudo">
        <span class="hx-capa__avatar" aria-hidden="true"><RuaLogo :tamanho="64" mono class="hx-capa__logo" /></span>
        <div class="hx-capa__textos">
          <h1 class="hx-capa__nome tm-title">{{ REDE.nome }} <span class="hx-capa__anos">{{ REDE.anoInicio }} — {{ REDE.anoFim }}</span></h1>
          <p class="hx-capa__sub">{{ REDE.subtitulo }}</p>
          <p class="hx-capa__contador" aria-live="polite">{{ rotuloContador }}</p>
        </div>
        <v-btn
          v-if="auth.isMembro"
          to="/historias/criar"
          variant="flat"
          color="primary"
          prepend-icon="mdi-plus"
          class="hx-capa__cta d-none d-sm-inline-flex"
        >
          Nova publicação
        </v-btn>
      </div>
    </header>

    <HistoriaRegua
      v-if="mostrarRegua"
      :historias="filtradas"
      :ativo="anoAtivo"
      @ir="irParaAno"
    />

    <div class="hx-coluna">
      <!-- Barra: compositor + botão que abre os filtros -->
      <div class="hx-barra">
        <router-link v-if="auth.isMembro" to="/historias/criar" class="hx-compositor">
          <AvatarUsuario :nome="auth.usuario?.nome" :src="auth.fotoPerfilUrl" :size="40" />
          <span class="hx-compositor__texto">Conte uma história da Rua…</span>
        </router-link>
        <button
          ref="botaoFiltrosRef"
          type="button"
          class="hx-filtros-btn"
          :class="{ 'hx-filtros-btn--aberto': filtrosAbertos, 'hx-filtros-btn--ativo': nFiltros > 0 }"
          :aria-expanded="filtrosAbertos"
          aria-controls="hx-filtros-painel"
          :aria-label="nFiltros ? `Filtros, ${nFiltros} ${nFiltros === 1 ? 'ativo' : 'ativos'}` : 'Filtros'"
          title="Buscar e filtrar"
          @click="alternarFiltros"
          @keydown.esc="fecharFiltros"
        >
          <v-icon size="24" aria-hidden="true">mdi-tune-variant</v-icon>
          <span v-if="nFiltros" class="hx-filtros-btn__badge" aria-hidden="true">{{ nFiltros }}</span>
        </button>
      </div>

      <!-- Painel de filtros: oculto por padrão (grid 0fr -> 1fr) -->
      <div
        id="hx-filtros-painel"
        class="hx-filtros"
        :class="{ 'hx-filtros--aberto': filtrosAbertos }"
        role="search"
        aria-label="Filtros de histórias"
        @keydown.esc="fecharFiltros"
      >
        <div class="hx-filtros__miolo">
          <div class="hx-filtros__conteudo">
            <v-text-field
              ref="buscaRef"
              v-model="buscaInput"
              label="Buscar histórias"
              prepend-inner-icon="mdi-magnify"
              clearable
              hide-details
              autocomplete="off"
              class="hx-filtros__busca"
              hint="Atalho: /"
            />
            <div class="hx-filtros__selects">
              <v-select v-model="membro" :items="membrosItens" label="Membro" clearable hide-details />
              <v-select v-model="ordem" :items="ORDENS" item-title="titulo" item-value="valor" label="Ordenar por" hide-details />
            </div>
            <div class="hx-filtros__chips" role="group" aria-label="Filtrar por tipo">
              <v-chip
                v-for="t in TIPOS"
                :key="t.valor"
                :variant="tipo === t.valor ? 'flat' : 'outlined'"
                :color="tipo === t.valor ? 'primary' : undefined"
                :aria-pressed="tipo === t.valor"
                tag="button"
                class="hx-chip-tipo"
                @click="tipo = t.valor"
              >
                {{ t.titulo }}
              </v-chip>
            </div>
          </div>
        </div>
      </div>

      <!-- Fora do painel: resumo dos filtros ativos + modo de exibição (troca frequente) -->
      <div class="hx-subbarra">
        <p v-if="resumoFiltros.length" class="hx-resumo" aria-live="polite">
          <span class="hx-resumo__texto">{{ resumoFiltros.join(" · ") }}</span>
          <button type="button" class="hx-resumo__limpar" @click="limparTudo">Limpar</button>
        </p>
        <v-btn-toggle v-model="visao" mandatory density="comfortable" class="hx-filtros__visao" aria-label="Modo de exibição">
          <v-btn value="feed" icon="mdi-timeline-text-outline" aria-label="Feed" title="Feed" />
          <v-btn value="cards" icon="mdi-view-grid-outline" aria-label="Cartões" title="Cartões" />
          <v-btn value="lista" icon="mdi-format-list-bulleted" aria-label="Lista" title="Lista" />
        </v-btn-toggle>
      </div>

      <!-- Estados -->
      <div v-if="carregando" class="hx-esqueleto" aria-busy="true" aria-label="Carregando histórias">
        <div v-for="n in 3" :key="n" class="hx-esqueleto__post">
          <div class="hx-esqueleto__topo">
            <span class="hx-skeleton hx-esqueleto__avatar"></span>
            <span class="hx-esqueleto__linhas">
              <span class="hx-skeleton hx-esqueleto__linha"></span>
              <span class="hx-skeleton hx-esqueleto__linha hx-esqueleto__linha--curta"></span>
            </span>
          </div>
          <span class="hx-skeleton hx-esqueleto__midia"></span>
          <span class="hx-skeleton hx-esqueleto__barra"></span>
        </div>
      </div>
      <TmErrorState v-else-if="erro" texto="Não conseguimos carregar as histórias." @retry="carregar" />
      <TmEmptyState
        v-else-if="!filtradas.length && temFiltro"
        icone="mdi-magnify-close"
        :titulo="q ? `Nenhuma história encontrada para “${q}”` : 'Nenhuma história com esses filtros'"
        texto="Tente outras palavras ou limpe os filtros."
      >
        <v-btn variant="tonal" @click="limparFiltros">Limpar busca</v-btn>
      </TmEmptyState>
      <TmEmptyState
        v-else-if="!filtradas.length"
        icone="mdi-book-open-page-variant-outline"
        :titulo="`As ${REDE.nome} ainda estão em silêncio.`"
        texto="Seja o primeiro a publicar."
      >
        <v-btn v-if="auth.isMembro" to="/historias/criar" variant="flat" color="primary">Contar uma história</v-btn>
      </TmEmptyState>
    </div>

    <!-- Feed por capítulos -->
    <Transition name="hx-modo" mode="out-in">
    <div v-if="!carregando && !erro && filtradas.length && modoCapitulos" :key="`feed-${visao}`" class="hx-feed" aria-label="Histórias por ano">
      <HistoriaAno v-for="c in capitulos" :key="c.id" :capitulo="c" :lista="visao === 'lista'" @keydown="navegarLinhas">
        <template #default="{ historia: h, destaque }">
          <HistoriaLinha
            v-if="visao === 'lista'"
            :historia="h"
            :termo="q"
            :pode-editar="auth.isMembro"
            @editar="editar(h)"
            @excluir="excluir(h)"
          />
          <HistoriaCard
            v-else
            :historia="h"
            :termo="q"
            :destaque="destaque"
            :pode-editar="auth.isMembro"
            @editar="editar(h)"
            @excluir="excluir(h)"
            @curtir="curtir(h)"
            @compartilhar="compartilhar(h)"
          />
        </template>
      </HistoriaAno>
    </div>

    <!-- Lista plana: A–Z ou cartões -->
    <div v-else-if="!carregando && !erro && filtradas.length" :key="`plano-${visao}`" class="hx-coluna">
      <ul class="hx-plano" :class="{ 'hx-plano--cards': visao === 'cards', 'hx-plano--lista': visao === 'lista' }" @keydown="navegarLinhas">
        <li v-for="h in filtradas" :key="h.uuid" class="hx-plano__item">
          <HistoriaLinha
            v-if="visao === 'lista'"
            :historia="h"
            :termo="q"
            :pode-editar="auth.isMembro"
            @editar="editar(h)"
            @excluir="excluir(h)"
          />
          <HistoriaCard
            v-else
            :historia="h"
            :termo="q"
            :compacto="visao === 'cards'"
            :pode-editar="auth.isMembro"
            @editar="editar(h)"
            @excluir="excluir(h)"
            @curtir="curtir(h)"
            @compartilhar="compartilhar(h)"
          />
        </li>
      </ul>
    </div>
    </Transition>

    <v-btn
      v-if="auth.isMembro"
      to="/historias/criar"
      icon="mdi-plus"
      color="primary"
      size="large"
      class="hx-fab d-sm-none"
      aria-label="Nova publicação"
    />

    <EditarHistoriaModal v-model="dialogEditar" :historia="historiaParaEditar" @confirmar="carregar" />
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import { buscarHistoriasResumo, deletarHistoria } from "@/services/historia.service";
import { favoritarHistoria, desfavoritarHistoria } from "@/services/favorito.service";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { REDE } from "@/config/esquina";
import { parseDataLocal } from "@/composables/useFormat";
import { agruparCapitulos, ordenarAsc } from "@/composables/useCapitulos";
import { animarEsquina } from "@/composables/useMovimentoEsquina";
import EditarHistoriaModal from "@/components/historias/EditarHistoriaModal.vue";
import HistoriaCard from "@/components/historias/HistoriaCard.vue";
import HistoriaRegua from "@/components/historias/HistoriaRegua.vue";
import HistoriaLinha from "@/components/historias/HistoriaLinha.vue";
import HistoriaAno from "@/components/historias/HistoriaAno.vue";
import RuaLogo from "@/components/brand/RuaLogo.vue";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import "@/assets/historias-era.css";
import "@/assets/historias-motion.css";

const TIPOS = [
  { titulo: "Todas", valor: "" },
  { titulo: "Principal", valor: "Principal" },
  { titulo: "Paralela", valor: "Paralela" },
  { titulo: "Pessoal", valor: "Pessoal" },
];
const ORDENS = [
  { titulo: `Cronológica (${REDE.anoInicio}→${REDE.anoFim})`, valor: "cronologica" },
  { titulo: "Mais recentes primeiro", valor: "recentes" },
  { titulo: "A–Z", valor: "az" },
];

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const { confirmar } = useConfirm();
const { tratar } = useApiError();
const notify = useNotify();

const historias = ref([]);
const carregando = ref(true);
const erro = ref(false);
const dialogEditar = ref(false);
const historiaParaEditar = ref(null);
const buscaRef = ref(null);
const botaoFiltrosRef = ref(null);
const filtrosAbertos = ref(false);
const anoAtivo = ref("");

// Estado dos filtros vive na URL (?q=&tipo=&membro=&ordem=&visao=)
const lerOrdem = (v) => (["recentes", "az"].includes(v) ? v : "cronologica");
const CHAVE_MODO = "esquina-modo";
const MODOS = ["feed", "cards", "lista"];
function modoSalvo() {
  try {
    const v = localStorage.getItem(CHAVE_MODO);
    return MODOS.includes(v) ? v : "feed";
  } catch {
    return "feed";
  }
}
// ?modo= (novo) ou ?visao= (antigo); sem nada na URL vale a preferência salva. "linha" (antigo) vira feed.
const lerVisao = (qy) => {
  const v = qy.modo ?? qy.visao;
  return MODOS.includes(v) ? v : v ? "feed" : modoSalvo();
};
const q = ref(String(route.query.q ?? ""));
const buscaInput = ref(q.value);
const tipo = ref(String(route.query.tipo ?? ""));
const membro = ref(route.query.membro ? String(route.query.membro) : null);
const ordem = ref(lerOrdem(route.query.ordem));
const visao = ref(lerVisao(route.query));

let debounce = null;
watch(buscaInput, (v) => {
  clearTimeout(debounce);
  debounce = setTimeout(() => (q.value = (v ?? "").trim()), 250);
});

watch([q, tipo, membro, ordem, visao], () => {
  const query = {};
  if (q.value) query.q = q.value;
  if (tipo.value) query.tipo = tipo.value;
  if (membro.value) query.membro = membro.value;
  if (ordem.value !== "cronologica") query.ordem = ordem.value;
  if (visao.value !== "feed") query.modo = visao.value;
  try {
    localStorage.setItem(CHAVE_MODO, visao.value);
  } catch {
    /* armazenamento indisponível: segue sem salvar */
  }
  router.replace({ query, hash: route.hash });
});

// Botão voltar do navegador altera a URL: sincroniza de volta.
watch(
  () => route.query,
  (qy) => {
    if (route.name !== "historias-listar") return;
    q.value = String(qy.q ?? "");
    if (buscaInput.value !== q.value) buscaInput.value = q.value;
    tipo.value = String(qy.tipo ?? "");
    membro.value = qy.membro ? String(qy.membro) : null;
    ordem.value = lerOrdem(qy.ordem);
    visao.value = lerVisao(qy);
  },
);

function normalizar(s) {
  return String(s ?? "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "");
}

const membrosItens = computed(() => {
  const mapa = new Map();
  historias.value.forEach((h) => h.usuarios?.forEach((u) => mapa.set(u.uuid, u.nome)));
  return [...mapa].map(([value, title]) => ({ value, title })).sort((a, b) => a.title.localeCompare(b.title, "pt-BR"));
});

const nomeMembro = computed(() => membrosItens.value.find((m) => m.value === membro.value)?.title ?? "Membro");
const resumoFiltros = computed(() => {
  const r = [];
  if (q.value) r.push(`“${q.value}”`);
  if (membro.value) r.push(nomeMembro.value);
  if (tipo.value) r.push(tipo.value);
  if (ordem.value !== "cronologica") r.push(ORDENS.find((o) => o.valor === ordem.value)?.titulo ?? "");
  return r;
});
const nFiltros = computed(() => resumoFiltros.value.length);

async function abrirFiltros() {
  filtrosAbertos.value = true;
  await nextTick();
  buscaRef.value?.focus();
}
function fecharFiltros() {
  if (!filtrosAbertos.value) return;
  filtrosAbertos.value = false;
  botaoFiltrosRef.value?.focus();
}
function alternarFiltros() {
  return filtrosAbertos.value ? fecharFiltros() : abrirFiltros();
}

const temFiltro = computed(() => !!(q.value || tipo.value || membro.value));

// Histórias do tipo Pessoal só aparecem quando o filtro de tipo é "Pessoal".
const filtradas = computed(() => {
  const termo = normalizar(q.value);
  const base = historias.value.filter((h) => {
    if (tipo.value ? h.tipo_historia !== tipo.value : h.tipo_historia === "Pessoal") return false;
    if (membro.value && !h.usuarios?.some((u) => u.uuid === membro.value)) return false;
    if (!termo) return true;
    return normalizar([h.titulo, h.descricao_curta, ...(h.usuarios ?? []).map((u) => u.nome)].join(" ")).includes(termo);
  });
  if (ordem.value === "az") return [...base].sort((a, b) => a.titulo.localeCompare(b.titulo, "pt-BR"));
  const asc = ordenarAsc(base);
  if (ordem.value !== "recentes") return asc;
  // "Sem data" fica por último também na ordem decrescente.
  const datadas = asc.filter((h) => parseDataLocal(h.data_historia));
  return [...datadas.reverse(), ...asc.filter((h) => !parseDataLocal(h.data_historia))];
});

const modoCapitulos = computed(() => visao.value !== "cards" && ordem.value !== "az");
const capitulos = computed(() => agruparCapitulos(filtradas.value, ordem.value === "recentes"));
const mostrarRegua = computed(() => !carregando.value && !erro.value && modoCapitulos.value && historias.value.length > 0);

const rotuloContador = computed(() => {
  if (carregando.value || erro.value) return "";
  const membros = new Set();
  historias.value.forEach((h) => h.usuarios?.forEach((u) => membros.add(u.uuid)));
  const n = filtradas.value.length;
  const hist = `${n} ${n === 1 ? "história" : "histórias"}`;
  return membros.size ? `${membros.size} ${membros.size === 1 ? "membro" : "membros"} · ${hist}` : hist;
});

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    const r = await buscarHistoriasResumo({ ordem: "asc" });
    historias.value = Array.isArray(r) ? r : (r?.data ?? []);
  } catch {
    erro.value = true;
  } finally {
    carregando.value = false;
  }
}

function limparFiltros() {
  buscaInput.value = "";
  q.value = "";
  tipo.value = "";
  membro.value = null;
}
function limparTudo() {
  limparFiltros();
  ordem.value = "cronologica";
}

function editar(h) {
  historiaParaEditar.value = h;
  dialogEditar.value = true;
}

async function excluir(h) {
  const ok = await confirmar({
    titulo: `Excluir a história “${h.titulo}”?`,
    texto: "Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  try {
    await deletarHistoria(h.uuid);
    historias.value = historias.value.filter((x) => x.uuid !== h.uuid);
    notify.sucesso("História excluída.");
  } catch (e) {
    tratar(e);
  }
}

// Curtir = favoritar. Otimista; desfaz se a API falhar.
async function curtir(h) {
  const novo = !h.favoritado;
  const antes = h.favoritos_count || 0;
  h.favoritado = novo;
  h.favoritos_count = Math.max(0, antes + (novo ? 1 : -1));
  try {
    await (novo ? favoritarHistoria : desfavoritarHistoria)(h.uuid);
  } catch {
    h.favoritado = !novo;
    h.favoritos_count = antes;
    notify.erro("Não foi possível atualizar. Tente de novo.");
  }
}

async function compartilhar(h) {
  const url = `${location.origin}${router.resolve({ name: "historias-show", params: { uuid: h.uuid } }).href}`;
  if (navigator.share) {
    try {
      await navigator.share({ title: h.titulo, text: h.descricao_curta || h.titulo, url });
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

const reduzirMovimento = () =>
  matchMedia("(prefers-reduced-motion: reduce)").matches || document.documentElement.dataset.reduzir === "1";

function irParaAno(id, instantaneo = false) {
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: instantaneo || reduzirMovimento() ? "auto" : "smooth", block: "start" });
  el.querySelector("h2")?.focus({ preventScroll: true });
  anoAtivo.value = id;
  // pulso no selo do ano, depois que a rolagem assenta
  setTimeout(
    () => animarEsquina(el.querySelector(".hx-ano__selo"), [{ transform: "scale(1)" }, { transform: "scale(1.15)" }, { transform: "scale(1)" }], { duration: 400, easing: "ease-out" }),
    instantaneo ? 0 : 450,
  );
}

// Scroll-spy: o capítulo cujo topo cruzou a faixa logo abaixo da navbar + régua.
let observador = null;
const visiveis = new Set();
async function observarCapitulos() {
  observador?.disconnect();
  visiveis.clear();
  await nextTick();
  const secoes = [...document.querySelectorAll(".hx-ano")];
  if (!secoes.length || !("IntersectionObserver" in window)) return;
  if (!secoes.some((s) => s.id === anoAtivo.value)) anoAtivo.value = secoes[0].id;
  observador = new IntersectionObserver(
    (entradas) => {
      entradas.forEach((e) => (e.isIntersecting ? visiveis.add(e.target.id) : visiveis.delete(e.target.id)));
      const primeira = secoes.find((s) => visiveis.has(s.id));
      if (primeira) anoAtivo.value = primeira.id;
    },
    { rootMargin: "-130px 0px -60% 0px" },
  );
  secoes.forEach((s) => observador.observe(s));
}
watch(capitulos, () => modoCapitulos.value && !carregando.value && observarCapitulos());
watch(modoCapitulos, (v) => (v ? observarCapitulos() : observador?.disconnect()));

// Lista: setas cima/baixo (e Home/End) movem o foco entre as linhas.
function navegarLinhas(e) {
  if (visao.value !== "lista" || !["ArrowDown", "ArrowUp", "Home", "End"].includes(e.key)) return;
  if (!e.target.matches?.("[data-linha-link]")) return;
  const links = [...document.querySelectorAll(".hx-linha [data-linha-link]")];
  const i = links.indexOf(e.target);
  const alvo = { ArrowDown: links[i + 1], ArrowUp: links[i - 1], Home: links[0], End: links.at(-1) }[e.key];
  if (alvo) {
    e.preventDefault();
    alvo.focus();
  }
}

function atalho(e) {
  const alvo = e.target;
  const digitando = alvo?.tagName === "INPUT" || alvo?.tagName === "TEXTAREA" || alvo?.isContentEditable;
  if (e.key === "/" && !digitando && !e.ctrlKey && !e.metaKey) {
    e.preventDefault();
    abrirFiltros();
  }
}

onMounted(async () => {
  window.addEventListener("keydown", atalho);
  await carregar();
  await observarCapitulos();
  if (route.hash.startsWith("#ano-")) irParaAno(route.hash.slice(1), true);
});
onBeforeUnmount(() => {
  window.removeEventListener("keydown", atalho);
  observador?.disconnect();
  clearTimeout(debounce);
});
</script>

<style scoped>
.historias-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding-top: var(--tm-navbar-h);
  padding-bottom: var(--tm-space-8);
}

/* Capa da rede */
.hx-capa {
  position: relative;
  min-height: 160px;
  background: linear-gradient(135deg, hsl(38 55% 30%), hsl(340 40% 26%));
  color: #fff;
  overflow: hidden;
}
.hx-capa__fundo {
  position: absolute;
  inset: 0;
}
.hx-capa__conteudo {
  position: relative;
  max-width: calc(var(--hx-feed-w) + 260px);
  margin: 0 auto;
  padding: var(--tm-space-5) var(--tm-space-4);
  min-height: 160px;
  display: flex;
  align-items: center;
  gap: var(--tm-space-4);
}
.hx-capa__avatar {
  flex: none;
  display: grid;
  place-items: center;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: var(--tm-paper);
  color: var(--tm-gold-text);
  border: 4px solid var(--tm-paper);
  box-shadow: var(--tm-shadow-md);
}
.hx-capa__logo {
  width: 78%;
  height: 78%;
}
.hx-capa__textos {
  flex: 1;
  min-width: 0;
}
.hx-capa__nome {
  font-size: var(--tm-fs-3xl);
  margin: 0;
  color: #fff;
}
.hx-capa__anos {
  display: block;
  margin-top: 0.15em;
  font-size: 0.55em;
  font-weight: 400;
  letter-spacing: 0.04em;
  opacity: 0.9;
}
.hx-capa__sub,
.hx-capa__contador {
  margin: 0;
  color: rgba(255, 255, 255, 0.92);
}
.hx-capa__contador {
  min-height: 1.5em;
  font-size: var(--tm-fs-sm);
}

/* Coluna central */
.hx-coluna {
  max-width: var(--hx-feed-w);
  margin: var(--tm-space-4) auto 0;
  padding-inline: var(--tm-space-3);
}
.hx-barra {
  display: flex;
  align-items: stretch;
  justify-content: flex-end;
  gap: var(--tm-space-2);
  margin-bottom: var(--tm-space-3);
}
.hx-compositor {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  padding: var(--tm-space-3) var(--tm-space-4);
  min-height: 64px;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  text-decoration: none;
  box-shadow: var(--tm-shadow-sm);
}
.hx-compositor__texto {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 10px 16px;
  border-radius: var(--tm-radius-pill);
  background: var(--hx-balao);
  color: var(--tm-ink-muted);
}
.hx-compositor:hover .hx-compositor__texto {
  color: var(--tm-ink);
}
.hx-compositor:focus-visible,
.hx-filtros-btn:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}

/* Botão de filtros */
.hx-filtros-btn {
  position: relative;
  flex: none;
  display: grid;
  place-items: center;
  width: 64px;
  min-height: 64px;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  box-shadow: var(--tm-shadow-sm);
  cursor: pointer;
}
.hx-filtros-btn:hover,
.hx-filtros-btn--aberto {
  background: var(--hx-balao);
}
.hx-filtros-btn__badge {
  position: absolute;
  top: 6px;
  right: 6px;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 10px;
  background: var(--tm-gold-text);
  color: #fff;
  font-size: var(--tm-fs-xs);
  font-weight: 700;
  line-height: 20px;
  text-align: center;
}

/* Painel (abre por grid-template-rows 0fr -> 1fr) */
.hx-filtros {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 250ms ease;
}
.hx-filtros--aberto {
  grid-template-rows: 1fr;
}
.hx-filtros__miolo {
  min-height: 0;
  overflow: hidden;
  visibility: hidden;
  transition: visibility 0s 250ms;
}
.hx-filtros--aberto .hx-filtros__miolo {
  visibility: visible;
  transition: visibility 0s;
}
.hx-filtros__conteudo {
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3);
  margin-bottom: var(--tm-space-3);
}
.hx-filtros__selects {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-3);
  margin-top: var(--tm-space-3);
}
.hx-filtros__chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-2);
  align-items: center;
  margin-top: var(--tm-space-3);
}
.hx-chip-tipo {
  min-height: 36px;
  cursor: pointer;
}

/* Resumo + modo de exibição */
.hx-subbarra {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  margin-bottom: var(--tm-space-4);
}
.hx-resumo {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  margin: 0;
  padding: 4px 12px;
  border-radius: var(--tm-radius-pill);
  background: var(--tm-paper);
  color: var(--tm-ink);
  font-size: var(--tm-fs-sm);
}
.hx-resumo__texto {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.hx-resumo__limpar {
  flex: none;
  min-height: 36px;
  padding: 0 8px;
  border: 0;
  background: none;
  color: var(--tm-gold-text);
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
}
.hx-resumo__limpar:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 1px;
}
.hx-filtros__visao {
  margin-left: auto;
  flex: none;
  background: var(--tm-paper);
}
@media (prefers-reduced-motion: reduce) {
  .hx-filtros,
  .hx-filtros__miolo {
    transition: none;
  }
}
html[data-reduzir="1"] .hx-filtros,
html[data-reduzir="1"] .hx-filtros__miolo {
  transition: none;
}

/* Esqueleto (mesmas medidas do post: sem layout shift) */
.hx-esqueleto {
  display: flex;
  flex-direction: column;
  gap: var(--hx-gap-post);
}
.hx-esqueleto__post {
  background: var(--tm-paper);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-4);
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-3);
}
.hx-esqueleto__topo {
  display: flex;
  gap: var(--tm-space-3);
  align-items: center;
}
.hx-esqueleto__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}
.hx-esqueleto__linhas {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.hx-esqueleto__linha {
  height: 12px;
  width: 70%;
}
.hx-esqueleto__linha--curta {
  width: 35%;
}
.hx-esqueleto__midia {
  aspect-ratio: 16 / 10;
}
.hx-esqueleto__barra {
  height: 32px;
}

/* Feed em capítulos */
.hx-feed {
  max-width: 1100px;
  margin: var(--tm-space-5) auto 0;
  padding-inline: var(--tm-space-3);
}
.hx-plano {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--hx-gap-post);
}
.hx-plano--cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}
.hx-plano--lista {
  gap: var(--tm-space-2);
}
.hx-plano__item {
  display: flex;
}
.hx-coluna:has(.hx-plano--cards) {
  max-width: 1100px;
}

.hx-fab {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 100;
}

@media (max-width: 959px) {
  .hx-capa__conteudo {
    max-width: var(--hx-feed-w);
  }
}
@media (min-width: 960px) {
  .hx-capa {
    min-height: 240px;
  }
  .hx-capa__conteudo {
    min-height: 240px;
  }
  .hx-capa__avatar {
    width: 120px;
    height: 120px;
  }
}
@media (max-width: 599px) {
  .hx-coluna,
  .hx-feed {
    padding-inline: 0;
  }
  .hx-barra {
    gap: 0;
  }
  .hx-barra > * {
    border-radius: 0;
    border-inline: 0;
  }
  .hx-filtros__conteudo {
    border-radius: 0;
    border-inline: 0;
  }
  .hx-subbarra {
    padding-inline: var(--tm-space-3);
  }
  .hx-capa__avatar {
    width: 64px;
    height: 64px;
  }
  .hx-filtros__selects {
    grid-template-columns: 1fr;
  }
}
</style>
