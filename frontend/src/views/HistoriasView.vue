<template>
  <section class="historias-page tm-immersive">
    <div class="painel">
      <header class="cabeca">
        <div>
          <h1 class="tm-title">Histórias</h1>
          <p class="contador" aria-live="polite">{{ rotuloContador }}</p>
        </div>
        <v-btn v-if="auth.isMembro" to="/historias/criar" variant="flat" color="primary" prepend-icon="mdi-plus" class="d-none d-sm-inline-flex">
          Contar uma história
        </v-btn>
      </header>

      <div class="filtros">
        <v-text-field
          ref="buscaRef"
          v-model="buscaInput"
          label="Buscar histórias"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          autocomplete="off"
          class="busca"
          hint="Atalho: /"
        />
        <v-select v-model="membro" :items="membrosItens" label="Membro" clearable hide-details class="filtro-sel" />
        <v-select v-model="ordem" :items="ORDENS" item-title="titulo" item-value="valor" label="Ordenar por" hide-details class="filtro-sel" />
      </div>

      <div class="chips" role="group" aria-label="Filtrar por tipo">
        <v-chip
          v-for="t in TIPOS"
          :key="t.valor"
          :variant="tipo === t.valor ? 'flat' : 'outlined'"
          :color="tipo === t.valor ? 'primary' : undefined"
          :aria-pressed="tipo === t.valor"
          tag="button"
          class="chip-tipo"
          @click="tipo = t.valor"
        >
          {{ t.titulo }}
        </v-chip>

        <v-btn-toggle v-if="temDatas" v-model="visao" mandatory density="comfortable" class="ml-auto" aria-label="Modo de exibição">
          <v-btn value="cards" aria-label="Cartões" prepend-icon="mdi-view-grid-outline">Cartões</v-btn>
          <v-btn value="linha" aria-label="Linha do tempo" prepend-icon="mdi-timeline-outline">Linha do tempo</v-btn>
        </v-btn-toggle>
      </div>

      <TmSkeletonGrid v-if="carregando" :quantidade="6" tipo="article" minimo="260px" />
      <TmErrorState v-else-if="erro" texto="Não conseguimos carregar as histórias." @retry="carregar" />
      <TmEmptyState
        v-else-if="!filtradas.length && temFiltro"
        icone="mdi-magnify-close"
        :titulo="q ? `Nenhuma história encontrada para “${q}”` : 'Nenhuma história com esses filtros'"
        texto="Tente outras palavras ou limpe os filtros."
      >
        <v-btn variant="tonal" @click="limparFiltros">Limpar busca</v-btn>
      </TmEmptyState>
      <TmEmptyState v-else-if="!filtradas.length" icone="mdi-book-open-page-variant-outline" titulo="Nenhuma história ainda.">
        <v-btn v-if="auth.isMembro" to="/historias/criar" variant="flat" color="primary">Contar uma história</v-btn>
      </TmEmptyState>

      <ul v-else-if="visao === 'cards' || !temDatas" class="cards-area">
        <li v-for="h in filtradas" :key="h.uuid" class="item">
          <HistoriaCard
            :historia="h"
            :termo="q"
            :pode-editar="auth.isMembro"
            @editar="editar(h)"
            @excluir="excluir(h)"
          />
        </li>
      </ul>

      <div v-else class="linha-tempo">
        <section v-for="g in porAno" :key="g.ano" class="ano">
          <h2 class="tm-title ano-titulo">{{ g.ano }}</h2>
          <ul class="cards-linha">
            <li v-for="h in g.itens" :key="h.uuid" class="item-linha">
              <HistoriaCard
                :historia="h"
                :termo="q"
                :pode-editar="auth.isMembro"
                @editar="editar(h)"
                @excluir="excluir(h)"
              />
            </li>
          </ul>
        </section>
      </div>
    </div>

    <v-btn
      v-if="auth.isMembro"
      to="/historias/criar"
      icon="mdi-plus"
      color="primary"
      size="large"
      class="fab d-sm-none"
      aria-label="Contar uma história"
    />

    <EditarHistoriaModal v-model="dialogEditar" :historia="historiaParaEditar" @confirmar="carregar" />
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { buscarHistoriasResumo, deletarHistoria } from "@/services/historia.service";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import EditarHistoriaModal from "@/components/historias/EditarHistoriaModal.vue";
import HistoriaCard from "@/components/historias/HistoriaCard.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const TIPOS = [
  { titulo: "Todas", valor: "" },
  { titulo: "Principal", valor: "Principal" },
  { titulo: "Paralela", valor: "Paralela" },
  { titulo: "Pessoal", valor: "Pessoal" },
];
const ORDENS = [
  { titulo: "Mais recentes", valor: "recentes" },
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

// Estado dos filtros vive na URL (?q=&tipo=&membro=&ordem=&visao=)
const q = ref(String(route.query.q ?? ""));
const buscaInput = ref(q.value);
const tipo = ref(String(route.query.tipo ?? ""));
const membro = ref(route.query.membro ? String(route.query.membro) : null);
const ordem = ref(String(route.query.ordem ?? "recentes"));
const visao = ref(String(route.query.visao ?? "cards"));

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
  if (ordem.value !== "recentes") query.ordem = ordem.value;
  if (visao.value !== "cards") query.visao = visao.value;
  router.replace({ query });
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
    ordem.value = String(qy.ordem ?? "recentes");
    visao.value = String(qy.visao ?? "cards");
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

const temDatas = computed(() => historias.value.some((h) => h.data_historia));
const temFiltro = computed(() => !!(q.value || tipo.value || membro.value));

const filtradas = computed(() => {
  const termo = normalizar(q.value);
  let lista = historias.value.filter((h) => {
    if (tipo.value && h.tipo_historia !== tipo.value) return false;
    if (membro.value && !h.usuarios?.some((u) => u.uuid === membro.value)) return false;
    if (!termo) return true;
    const alvo = normalizar([h.titulo, h.descricao_curta, ...(h.usuarios ?? []).map((u) => u.nome)].join(" "));
    return alvo.includes(termo);
  });
  if (ordem.value === "az") {
    lista = [...lista].sort((a, b) => a.titulo.localeCompare(b.titulo, "pt-BR"));
  } else if (temDatas.value) {
    lista = [...lista].sort((a, b) => new Date(b.data_historia || 0) - new Date(a.data_historia || 0));
  } else {
    lista = [...lista].reverse(); // sem data: assume que o fim da lista é o mais recente
  }
  return lista;
});

const porAno = computed(() => {
  const mapa = new Map();
  for (const h of filtradas.value) {
    const ano = h.data_historia ? new Date(h.data_historia).getFullYear() : "Sem data";
    if (!mapa.has(ano)) mapa.set(ano, []);
    mapa.get(ano).push(h);
  }
  return [...mapa].map(([ano, itens]) => ({ ano, itens }));
});

const rotuloContador = computed(() => {
  if (carregando.value || erro.value) return "";
  const n = filtradas.value.length;
  return `${n} ${n === 1 ? "história" : "histórias"}`;
});

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    const r = await buscarHistoriasResumo();
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

function atalho(e) {
  const alvo = e.target;
  const digitando = alvo?.tagName === "INPUT" || alvo?.tagName === "TEXTAREA" || alvo?.isContentEditable;
  if (e.key === "/" && !digitando && !e.ctrlKey && !e.metaKey) {
    e.preventDefault();
    buscaRef.value?.focus();
  }
}

onMounted(() => {
  carregar();
  window.addEventListener("keydown", atalho);
});
onBeforeUnmount(() => {
  window.removeEventListener("keydown", atalho);
  clearTimeout(debounce);
});
</script>

<style scoped>
.historias-page {
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
.cabeca {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--tm-space-4);
  margin-bottom: var(--tm-space-4);
}
.cabeca h1 {
  font-size: var(--tm-fs-3xl);
}
.contador {
  margin: 0;
  color: var(--tm-ink-muted);
  min-height: 1.6em;
}
.filtros {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: var(--tm-space-3);
  margin-bottom: var(--tm-space-4);
}
.chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-2);
  align-items: center;
  margin-bottom: var(--tm-space-5);
}
.chip-tipo {
  min-height: 36px;
  cursor: pointer;
}
.cards-area,
.cards-linha {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--tm-space-4);
}
.item,
.item-linha {
  display: flex;
}
.linha-tempo {
  position: relative;
  padding-left: var(--tm-space-5);
  border-left: 2px solid var(--tm-gold);
}
.ano {
  position: relative;
  margin-bottom: var(--tm-space-6);
}
.ano-titulo {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.ano-titulo::before {
  content: "";
  position: absolute;
  left: calc(-1 * var(--tm-space-5) - 8px);
  top: 8px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--tm-gold);
}
.fab {
  position: fixed;
  right: 16px;
  bottom: 16px;
  z-index: 100;
}
@media (max-width: 960px) {
  .filtros {
    grid-template-columns: 1fr 1fr;
  }
  .busca {
    grid-column: 1 / -1;
  }
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
  .filtros {
    grid-template-columns: 1fr;
  }
  .cards-area,
  .cards-linha {
    grid-template-columns: 1fr;
  }
}
</style>
