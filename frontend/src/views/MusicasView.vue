<template>
  <section class="musicas-page">
    <header class="musicas-cab">
      <div class="musicas-titulo">
        <h1 class="tm-title">Músicas</h1>
        <p class="musicas-sub" aria-live="polite">{{ subtitulo }}</p>
      </div>
      <div class="musicas-acoes">
        <v-btn color="primary" variant="flat" size="large" prepend-icon="mdi-play" class="tocar-tudo" :disabled="!lista.itens.value.length" :loading="preparando === 'tudo'" @click="tocarTudo(false)">
          Tocar tudo
        </v-btn>
        <v-btn variant="tonal" size="large" prepend-icon="mdi-shuffle-variant" :disabled="!lista.itens.value.length" :loading="preparando === 'aleatorio'" @click="tocarTudo(true)">
          Aleatório
        </v-btn>
        <v-btn v-if="auth.isMembro" variant="tonal" size="large" prepend-icon="mdi-music-note-plus" @click="abrirNova">Enviar música</v-btn>
      </div>
    </header>

    <div class="musicas-ferramentas">
      <v-text-field
        v-model="buscaInput"
        label="Buscar músicas"
        prepend-inner-icon="mdi-magnify"
        clearable
        hide-details
        autocomplete="off"
        density="comfortable"
        variant="outlined"
        class="musicas-busca"
      />
      <v-select v-model="ordem" :items="ORDENS" item-title="titulo" item-value="valor" label="Ordenar por" hide-details density="comfortable" variant="outlined" class="musicas-ordem" />
    </div>

    <TmErrorState v-if="lista.erro.value && !lista.itens.value.length" texto="Não conseguimos carregar as músicas." @retry="lista.recarregar()" />

    <div v-else-if="lista.carregando.value && !lista.itens.value.length" class="musicas-esqueleto" aria-busy="true">
      <span v-for="n in 8" :key="n" class="esq-linha" aria-hidden="true"></span>
      <span class="sr-only" role="status">Carregando músicas</span>
    </div>

    <TmEmptyState
      v-else-if="lista.vazio.value"
      :icone="q ? 'mdi-music-off' : 'mdi-music-note-outline'"
      :titulo="q ? `Nada encontrado para '${q}'` : 'Nenhuma música ainda'"
      :texto="q ? 'Tente outras palavras.' : 'As músicas da rua aparecerão aqui.'"
    >
      <v-btn v-if="q" variant="tonal" @click="buscaInput = ''">Limpar busca</v-btn>
      <v-btn v-else-if="auth.isMembro" variant="flat" color="primary" prepend-icon="mdi-music-note-plus" @click="abrirNova">Enviar música</v-btn>
    </TmEmptyState>

    <template v-else>
      <table v-if="mdAndUp" class="musicas-tabela">
        <caption class="sr-only">Faixas</caption>
        <thead>
          <tr>
            <th scope="col" class="c-num">#</th>
            <th scope="col">Título</th>
            <th scope="col" class="c-album">Álbum</th>
            <th scope="col" class="c-dur">Duração</th>
            <th scope="col" class="c-acoes"><span class="sr-only">Ações</span></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in lista.itens.value"
            :key="m.uuid"
            class="m-linha"
            :class="{ atual: m.uuid === player.uuidAtual }"
            tabindex="0"
            :aria-label="`${m.titulo}, ${m.artista || 'Artista desconhecido'}`"
            :aria-current="m.uuid === player.uuidAtual ? 'true' : undefined"
            :data-uuid="m.uuid"
            @dblclick="tocarDe(i)"
            @keydown.enter.self.prevent="acionar(m, i)"
            @keydown.space.self.prevent="acionar(m, i)"
          >
            <td class="c-num">
              <button type="button" class="m-play" tabindex="-1" :aria-label="rotuloPlay(m)" @click="acionar(m, i)">
                <MusicaEqualizador v-if="m.uuid === player.uuidAtual" class="m-eq" :tocando="player.tocando" />
                <span class="m-numero">{{ m.uuid === player.uuidAtual ? "" : i + 1 }}</span>
                <v-icon class="m-icone-play" size="22" aria-hidden="true">{{ m.uuid === player.uuidAtual && player.tocando ? "mdi-pause" : "mdi-play" }}</v-icon>
              </button>
            </td>
            <td>
              <div class="m-titulo-cel">
                <MusicaCapa :src="m.capa_url" :tamanho="40" />
                <span class="m-textos">
                  <strong class="m-titulo">{{ m.titulo }}</strong>
                  <small class="m-artista">{{ m.artista || "Artista desconhecido" }}</small>
                </span>
              </div>
            </td>
            <td class="c-album">{{ m.album || "—" }}</td>
            <td class="c-dur">{{ m.duracao_segundos ? formatarDuracao(m.duracao_segundos) : "—" }}</td>
            <td class="c-acoes">
              <MusicaMenu :musica="m" :pode-editar="auth.isMembro" @acao="(a) => aoAcao(a, m)" />
            </td>
          </tr>
        </tbody>
      </table>

      <ul v-else class="musicas-cartoes" aria-label="Faixas">
        <li v-for="(m, i) in lista.itens.value" :key="m.uuid" class="m-cartao" :class="{ atual: m.uuid === player.uuidAtual }" :aria-current="m.uuid === player.uuidAtual ? 'true' : undefined">
          <button type="button" class="m-cartao-tocar" :aria-label="rotuloPlay(m)" @click="acionar(m, i)">
            <MusicaCapa :src="m.capa_url" :tamanho="48" />
            <span class="m-textos">
              <strong class="m-titulo">{{ m.titulo }}</strong>
              <small class="m-artista">{{ m.artista || "Artista desconhecido" }}</small>
            </span>
            <MusicaEqualizador v-if="m.uuid === player.uuidAtual" :tocando="player.tocando" />
            <span class="m-dur">{{ m.duracao_segundos ? formatarDuracao(m.duracao_segundos) : "" }}</span>
          </button>
          <MusicaMenu :musica="m" :pode-editar="auth.isMembro" @acao="(a) => aoAcao(a, m)" />
        </li>
      </ul>

      <div v-if="lista.erro.value" class="musicas-aviso" role="alert">
        Não deu para carregar mais.
        <v-btn size="small" variant="tonal" @click="lista.carregarProxima()">Tentar de novo</v-btn>
      </div>
      <div v-else-if="lista.temMais.value && !lista.carregando.value" class="musicas-aviso">
        <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
      </div>
    </template>
    <div :ref="lista.sentinela" aria-hidden="true"></div>

    <MusicaFormDialog v-if="formMontado" v-model="formAberto" :musica="editando" @salvo="aoSalvar" />
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import { listarMusicas, excluirMusica } from "@/services/musica.service";
import { useAuthStore } from "@/stores/auth";
import { usePlayerStore } from "@/stores/player";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useConfirm } from "@/composables/useConfirm";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { formatarDuracao } from "@/composables/useFormat";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import MusicaCapa from "@/components/musicas/MusicaCapa.vue";
import MusicaEqualizador from "@/components/musicas/MusicaEqualizador.vue";
import MusicaMenu from "@/components/musicas/MusicaMenu.vue";

const MusicaFormDialog = defineAsyncComponent(() => import("@/components/musicas/MusicaFormDialog.vue"));

const ORDENS = [
  { titulo: "Mais recentes", valor: "recentes" },
  { titulo: "Mais antigas", valor: "antigas" },
  { titulo: "Título", valor: "titulo" },
  { titulo: "Artista", valor: "artista" },
  { titulo: "Álbum", valor: "album" },
];

const auth = useAuthStore();
const player = usePlayerStore();
const route = useRoute();
const router = useRouter();
const { mdAndUp } = useDisplay();
const { confirmar } = useConfirm();
const { tratar } = useApiError();
const notify = useNotify();

const buscaInput = ref("");
const q = ref("");
const ordem = ref("recentes");
const preparando = ref("");

const lista = useListaPaginada((p) => {
  const params = { per_page: 50, ordem: ordem.value };
  if (q.value) params.q = q.value;
  return listarMusicas(p, params);
});

const subtitulo = computed(() => {
  const n = lista.total.value;
  if (n == null) return "";
  return `${n} ${n === 1 ? "faixa" : "faixas"}`;
});

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
watch(ordem, () => lista.recarregar());

/* ---------- reprodução ---------- */
function tocarDe(i) {
  player.tocarLista(lista.itens.value, i);
}
function acionar(m, i) {
  if (m.uuid === player.uuidAtual) player.alternar();
  else tocarDe(i);
}
const rotuloPlay = (m) => (m.uuid === player.uuidAtual && player.tocando ? `Pausar ${m.titulo}` : `Tocar ${m.titulo}`);

async function tocarTudo(embaralhado) {
  preparando.value = embaralhado ? "aleatorio" : "tudo";
  try {
    await lista.carregarAte(Number.MAX_SAFE_INTEGER);
    if (lista.erro.value) notify.erro("Algumas faixas não foram carregadas.");
    player.tocarLista(lista.itens.value, 0, { embaralhado });
  } finally {
    preparando.value = "";
  }
}

/* ---------- ações por faixa ---------- */
const formMontado = ref(false);
const formAberto = ref(false);
const editando = ref(null);

function abrirNova() {
  editando.value = null;
  formMontado.value = true;
  formAberto.value = true;
}
function aoSalvar() {
  lista.recarregar();
}

async function aoAcao(acao, m) {
  if (acao === "proxima") player.tocarAProxima(m);
  else if (acao === "fila") player.adicionarAFila(m);
  else if (acao === "editar") {
    editando.value = m;
    formMontado.value = true;
    formAberto.value = true;
  } else if (acao === "excluir") {
    const ok = await confirmar({ titulo: `Excluir "${m.titulo}"?`, texto: "Essa ação não pode ser desfeita.", rotuloConfirmar: "Excluir" });
    if (!ok) return;
    try {
      await excluirMusica(m.uuid);
      const i = player.fila.findIndex((f) => f.uuid === m.uuid);
      if (i >= 0) player.remover(i);
      lista.itens.value = lista.itens.value.filter((x) => x.uuid !== m.uuid);
      notify.sucesso("Música excluída.");
    } catch (e) {
      tratar(e);
    }
  }
}

onMounted(async () => {
  await lista.carregarProxima();
  // Vindo do menu Criar > Música
  if (route.query.nova && auth.isMembro) {
    abrirNova();
    router.replace({ query: {} });
  }
});
</script>

<style scoped>
.musicas-page {
  max-width: 1100px;
  margin: 0 auto;
  min-height: 100dvh;
  padding: calc(var(--tm-navbar-h) + 24px) 16px 64px;
  color: var(--tm-ink);
}
.musicas-cab {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.musicas-titulo h1 {
  margin: 0;
  font-size: var(--tm-fs-3xl);
}
.musicas-sub {
  margin: 0;
  color: var(--tm-ink-muted);
  min-height: 1.6em;
}
.musicas-acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.tocar-tudo {
  color: var(--tm-ink);
}
.musicas-ferramentas {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}
.musicas-tabela {
  width: 100%;
  border-collapse: collapse;
}
.musicas-tabela th {
  text-align: left;
  padding: 8px;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  border-bottom: 1px solid var(--tm-border);
}
.m-linha {
  height: 56px;
  border-bottom: 1px solid transparent;
  cursor: default;
}
.m-linha td {
  padding: 0 8px;
}
.m-linha:hover,
.m-linha:focus-visible {
  background: var(--tm-paper-2);
}
.m-linha.atual .m-titulo {
  color: var(--tm-gold-text);
}
.c-num {
  width: 56px;
  text-align: center;
}
.c-dur {
  width: 96px;
  font-variant-numeric: tabular-nums;
}
.c-album {
  color: var(--tm-ink-muted);
}
.c-acoes {
  width: 56px;
  text-align: right;
}
.m-play {
  position: relative;
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
  border-radius: 50%;
}
.m-play > * {
  grid-area: 1 / 1;
}
.m-icone-play {
  opacity: 0;
}
.m-linha:hover .m-icone-play,
.m-linha:focus-within .m-icone-play {
  opacity: 1;
}
.m-linha:hover .m-numero,
.m-linha:focus-within .m-numero,
.m-linha:hover .m-eq,
.m-linha:focus-within .m-eq {
  opacity: 0;
}
.m-titulo-cel {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}
.m-textos {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.3;
  text-align: left;
}
.m-titulo,
.m-artista {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.m-artista {
  color: var(--tm-ink-muted);
}

.musicas-cartoes {
  list-style: none;
  margin: 0;
  padding: 0;
}
.m-cartao {
  display: flex;
  align-items: center;
  min-height: 64px;
  border-radius: var(--tm-radius-sm);
}
.m-cartao.atual {
  background: color-mix(in srgb, var(--tm-gold) 14%, transparent);
}
.m-cartao.atual .m-titulo {
  color: var(--tm-gold-text);
}
.m-cartao-tocar {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
}
.m-dur {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
}
.musicas-esqueleto {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.esq-linha {
  height: 56px;
  border-radius: var(--tm-radius-xs);
  background: var(--tm-paper-2);
}
.musicas-aviso {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 16px;
}
@media (max-width: 599px) {
  .musicas-ferramentas {
    grid-template-columns: 1fr;
  }
}
</style>
