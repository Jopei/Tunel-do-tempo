<template>
  <section class="atualizacoes-page tm-immersive">
    <div class="container">
      <h1 class="page-title">Atualizações</h1>
      <p class="sub">O que mudou no Túnel do Tempo.</p>

      <div v-if="carregando" class="skeletons" aria-busy="true" aria-label="Carregando atualizações">
        <v-skeleton-loader v-for="n in 3" :key="n" type="article" class="mb-6" />
      </div>

      <div class="painel-estado" v-else-if="erro">
        <TmErrorState texto="Não conseguimos carregar as atualizações." @retry="carregar" />
      </div>

      <div class="painel-estado" v-else-if="!grupos.length">
        <TmEmptyState icone="mdi-update" titulo="Ainda não há atualizações." texto="Quando algo novo chegar, aparece aqui." />
      </div>

      <template v-else>
        <div v-for="g in grupos" :key="g.chave" class="grupo">
          <h2 class="mes">{{ g.rotulo }}</h2>
          <article v-for="item in g.itens" :id="item.uuid" :key="item.uuid" class="update-card">
            <time class="data" :datetime="item.created_at">{{ formatarData(item.created_at) }}</time>

            <div class="conteudo">
              <div class="cabeca">
                <span v-if="item.isNova" class="badge-nova">Novo</span>
                <h3 class="titulo tm-title">{{ item.titulo }}</h3>
                <div class="acoes-card">
                  <v-btn
                    icon
                    size="small"
                    variant="text"
                    color="on-surface"
                    :aria-label="`Copiar link da atualização ${item.titulo}`"
                    @click="copiarLink(item)"
                  >
                    <v-icon>mdi-link-variant</v-icon>
                  </v-btn>
                  <v-btn
                    v-if="podeEditar(item)"
                    icon
                    size="small"
                    variant="text"
                    color="on-surface"
                    :aria-label="`Editar atualização ${item.titulo}`"
                    @click="editar(item)"
                  >
                    <v-icon>mdi-pencil-outline</v-icon>
                  </v-btn>
                </div>
              </div>

              <div class="tm-prose" v-html="item.html"></div>

              <div class="footer">
                <span class="autor">Por {{ item.usuario_nome }}</span>
                <a
                  v-if="item.link_musica"
                  :href="item.link_musica"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="musica"
                >
                  <v-icon size="18" aria-hidden="true">mdi-music</v-icon>
                  Escutar música (abre em nova aba)
                </a>
              </div>
            </div>
          </article>
        </div>
      </template>
    </div>

    <CriarAtualizacaoModal v-if="modalMontado" v-model="modalEditar" :atualizacao="emEdicao" />
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineAsyncComponent } from "vue";
import { useRoute } from "vue-router";
import { listarAtualizacoes } from "@/services/atualizacao.service";
import { marcarAtualizacoesVistas, ultimaVistaTs } from "@/composables/useAtualizacoesNovas";
import { atualizacaoCriada } from "@/composables/useMidiaEventos";
import { sanitizar } from "@/composables/useSanitize";
import { useNotify } from "@/composables/useNotify";
import { useAuthStore } from "@/stores/auth";
import { formatarData } from "@/composables/useFormat";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const CriarAtualizacaoModal = defineAsyncComponent(() => import("@/components/atualizacoes/CriarAtualizacaoModal.vue"));

const DIAS_NOVO = 14;

const auth = useAuthStore();
const route = useRoute();
const notify = useNotify();

const atualizacoes = ref([]);
const carregando = ref(true);
const erro = ref(false);
const modalMontado = ref(false);
const modalEditar = ref(false);
const emEdicao = ref(null);

const grupos = computed(() => {
  const mapa = new Map();
  for (const a of atualizacoes.value) {
    const d = new Date(a.created_at);
    const chave = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    if (!mapa.has(chave)) {
      const rotulo = d.toLocaleDateString("pt-BR", { month: "long", year: "numeric" });
      mapa.set(chave, { chave, rotulo, itens: [] });
    }
    mapa.get(chave).itens.push(a);
  }
  return [...mapa.values()];
});

function podeEditar(item) {
  return auth.logado && (auth.isAdmin || (auth.usuario?.nome && item.usuario_nome === auth.usuario.nome));
}

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    const [{ marked }, data] = await Promise.all([import("marked"), listarAtualizacoes()]);
    const visto = ultimaVistaTs();
    const limite = Date.now() - DIAS_NOVO * 86400000;
    atualizacoes.value = [...data]
      .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
      .map((item) => {
        const ts = new Date(item.created_at).getTime();
        return {
          ...item,
          html: sanitizar(marked.parse(item.conteudo_markdown || "")),
          isNova: ts > limite || (visto > 0 && ts > visto),
        };
      });
    marcarAtualizacoesVistas(data);
  } catch {
    erro.value = true;
  } finally {
    carregando.value = false;
  }
  if (route.hash && !erro.value) {
    setTimeout(() => document.getElementById(route.hash.slice(1))?.scrollIntoView({ block: "start" }), 50);
  }
}

async function copiarLink(item) {
  const url = `${location.origin}/atualizacoes#${item.uuid}`;
  try {
    await navigator.clipboard.writeText(url);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}

function editar(item) {
  emEdicao.value = item;
  modalMontado.value = true;
  modalEditar.value = true;
}

watch(atualizacaoCriada, carregar);
onMounted(carregar);
</script>

<style scoped>
.atualizacoes-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 48px) var(--tm-space-4) var(--tm-space-8);
}

.container {
  max-width: 1100px;
  margin: 0 auto;
}

.page-title {
  font-size: var(--tm-fs-hero);
  color: #fff;
  text-align: center;
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
}
.sub {
  text-align: center;
  color: #fff;
  margin: var(--tm-space-2) 0 var(--tm-space-7);
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
}

.painel-estado,
.skeletons {
  background: rgb(var(--v-theme-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-4);
}

.mes {
  color: #fff;
  text-transform: capitalize;
  font-size: var(--tm-fs-xl);
  margin: 0 0 var(--tm-space-5);
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
}
.grupo {
  margin-bottom: var(--tm-space-6);
}

.update-card {
  display: flex;
  gap: var(--tm-space-6);
  margin-bottom: var(--tm-space-6);
  position: relative;
}

.data {
  min-width: 130px;
  font-weight: 600;
  color: #fff;
  margin-top: var(--tm-space-3);
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.6);
}

.conteudo {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  padding: var(--tm-space-6);
  border-radius: var(--tm-radius-lg);
  box-shadow: var(--tm-shadow-lg);
  flex: 1;
  min-width: 0;
}

.cabeca {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--tm-space-3);
  margin-bottom: var(--tm-space-4);
}
.titulo {
  font-size: var(--tm-fs-2xl);
  margin: 0;
  flex: 1;
}
.acoes-card {
  display: flex;
}

.badge-nova {
  background: var(--tm-gold);
  color: #2a2112;
  font-weight: 700;
  font-size: var(--tm-fs-sm);
  padding: 4px 12px;
  border-radius: var(--tm-radius-pill);
}

.conteudo :deep(.tm-prose) {
  margin-inline: 0;
  font-size: var(--tm-fs-md);
}

.footer {
  margin-top: var(--tm-space-6);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--tm-space-3);
  flex-wrap: wrap;
  border-top: 1px solid var(--tm-border);
  padding-top: var(--tm-space-4);
}
.autor {
  font-weight: 600;
  color: var(--tm-ink-muted);
}
.musica {
  font-weight: 700;
  color: var(--tm-gold-text);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 44px;
}

@media (max-width: 900px) {
  .update-card {
    flex-direction: column;
    gap: var(--tm-space-2);
  }
  .data {
    margin-top: 0;
  }
}
@media (max-width: 600px) {
  .conteudo {
    padding: var(--tm-space-4);
  }
}
</style>
