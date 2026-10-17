<template>
  <section class="pag tm-immersive">
    <div class="painel">
      <header class="cabeca">
        <h1 class="tm-title">Minhas notificações</h1>
        <v-btn variant="tonal" prepend-icon="mdi-check-all" :disabled="!temNaoLida" @click="marcarTodas">Marcar todas como lidas</v-btn>
      </header>

      <div class="chips" role="group" aria-label="Filtrar notificações">
        <v-chip
          v-for="f in FILTROS"
          :key="f.valor"
          tag="button"
          :variant="status === f.valor ? 'flat' : 'outlined'"
          :color="status === f.valor ? 'primary' : undefined"
          :aria-pressed="status === f.valor"
          @click="status = f.valor"
        >
          {{ f.titulo }}
        </v-chip>
      </div>

      <TmSkeletonGrid v-if="lista.carregando.value && !lista.itens.value.length" :quantidade="4" tipo="list-item-two-line" minimo="100%" />
      <TmErrorState v-else-if="lista.erro.value && !lista.itens.value.length" @retry="lista.recarregar" />
      <TmEmptyState
        v-else-if="lista.vazio.value"
        icone="mdi-bell-check-outline"
        :titulo="status === 'nao_lidas' ? 'Tudo em dia por aqui.' : 'Nenhuma notificação ainda.'"
      />

      <ul v-else class="itens">
        <li v-for="n in lista.itens.value" :key="n.id" class="item" :class="{ lida: n.lida }">
          <button type="button" class="corpo" @click="abrir(n)">
            <span class="linha1">
              <span v-if="!n.lida" class="ponto" aria-label="Não lida"></span>
              <strong>{{ n.titulo }}</strong>
              <v-chip v-if="n.tema" size="x-small" label>{{ n.tema }}</v-chip>
            </span>
            <span class="data">{{ dataRelativa(n.created_at) }}</span>
          </button>
          <div v-if="aberta === n.id && n.descricao" class="tm-prose desc" v-html="sanitizar(n.descricao)"></div>
        </li>
      </ul>

      <div v-if="lista.temMais.value" class="mais">
        <v-btn variant="tonal" :loading="lista.carregando.value" @click="lista.carregarProxima()">Carregar mais</v-btn>
      </div>
      <div :ref="lista.sentinela" aria-hidden="true"></div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { listarNotificacoesPaginado, marcarLida, marcarTodasLidas } from "@/services/notificacao.service";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { dataRelativa } from "@/composables/useFormat";
import { sanitizar } from "@/composables/useSanitize";
import { useApiError } from "@/composables/useApiError";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const FILTROS = [
  { titulo: "Todas", valor: "todas" },
  { titulo: "Não lidas", valor: "nao_lidas" },
];

const router = useRouter();
const { tratar } = useApiError();
const status = ref("todas");
const aberta = ref(null);

const lista = useListaPaginada((page) => listarNotificacoesPaginado({ status: status.value, page, per_page: 20 }));
const temNaoLida = computed(() => lista.itens.value.some((n) => !n.lida));

watch(status, () => lista.recarregar());
onMounted(() => lista.carregarProxima());

async function abrir(n) {
  if (!n.lida) {
    try {
      await marcarLida(n.id);
      n.lida = true;
    } catch (e) {
      tratar(e);
    }
  }
  if (n.link) {
    if (/^https?:\/\//i.test(n.link)) window.open(n.link, "_blank", "noopener,noreferrer");
    else router.push(n.link.startsWith("/") ? n.link : "/" + n.link);
    return;
  }
  aberta.value = aberta.value === n.id ? null : n.id;
}

async function marcarTodas() {
  try {
    await marcarTodasLidas();
    lista.itens.value.forEach((n) => (n.lida = true));
    if (status.value === "nao_lidas") lista.itens.value = [];
  } catch (e) {
    tratar(e);
  }
}
</script>

<style scoped>
.pag {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
}
.painel {
  max-width: 760px;
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
  align-items: center;
  flex-wrap: wrap;
  gap: var(--tm-space-3);
  margin-bottom: var(--tm-space-4);
}
.cabeca h1 {
  font-size: var(--tm-fs-3xl);
}
.chips {
  display: flex;
  gap: var(--tm-space-2);
  margin-bottom: var(--tm-space-4);
}
.itens {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-2);
}
.item {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: rgb(var(--v-theme-surface-variant));
}
.item.lida {
  background: transparent;
}
.corpo {
  width: 100%;
  min-height: 56px;
  text-align: left;
  padding: var(--tm-space-3) var(--tm-space-4);
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: none;
  border: 0;
  color: inherit;
  cursor: pointer;
}
.linha1 {
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  flex-wrap: wrap;
}
.ponto {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--tm-gold);
}
.data {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.desc {
  padding: 0 var(--tm-space-4) var(--tm-space-3);
  font-size: var(--tm-fs-md);
}
.mais {
  text-align: center;
  margin-top: var(--tm-space-4);
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
}
</style>
