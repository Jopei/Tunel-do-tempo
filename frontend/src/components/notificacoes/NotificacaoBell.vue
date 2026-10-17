<template>
  <v-menu v-model="aberto" :close-on-content-click="false" location="bottom end" min-width="320" max-width="380">
    <template #activator="{ props }">
      <v-btn
        v-bind="props"
        icon
        variant="text"
        class="bell-btn"
        :aria-label="rotuloSino"
        :aria-expanded="aberto"
      >
        <v-badge v-if="naoLidas" :content="contagem" color="error" floating>
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
        <v-icon v-else>mdi-bell-outline</v-icon>
      </v-btn>
    </template>

    <v-card role="dialog" aria-label="Notificações" class="painel">
      <v-card-title class="text-subtitle-1 font-weight-bold">Notificações</v-card-title>

      <div v-if="loading" class="pa-4" aria-busy="true">
        <v-skeleton-loader type="list-item-two-line@3" />
      </div>

      <div v-else-if="erro" class="pa-4 text-center">
        <p class="mb-3">Não foi possível carregar as notificações.</p>
        <v-btn size="small" variant="tonal" @click="carregar()">Tentar novamente</v-btn>
      </div>

      <div v-else-if="!notificacoes.length" class="pa-6 text-center text-medium-emphasis">
        <v-icon size="32" class="mb-2" aria-hidden="true">mdi-bell-check-outline</v-icon>
        <p>Tudo em dia por aqui.</p>
      </div>

      <v-list v-else lines="two" density="comfortable" class="lista">
        <v-list-item
          v-for="n in notificacoes"
          :key="n.id"
          :title="n.titulo"
          @click="abrir(n)"
        >
          <template #subtitle>
            <v-chip v-if="n.tema" size="x-small" label class="mr-2">{{ n.tema }}</v-chip>
            <span v-if="n.created_at">{{ dataRelativa(n.created_at) }}</span>
          </template>
        </v-list-item>
      </v-list>

      <v-divider />
      <v-card-actions>
        <v-btn v-if="notificacoes.length" variant="text" size="small" color="primary-text" @click="marcarTodas">
          Marcar todas como lidas
        </v-btn>
        <v-spacer />
        <v-btn to="/notificacoes/minhas" variant="text" size="small" color="primary-text" @click="aberto = false">
          Ver todas
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-menu>
  <span class="sr-only" aria-live="polite">{{ anuncio }}</span>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import {
  listarNotificacoes,
  marcarTodasLidas,
  marcarLida,
  contagemNotificacoes,
} from "@/services/notificacao.service";
import { dataRelativa } from "@/composables/useFormat";

const router = useRouter();

const notificacoes = ref([]);
const loading = ref(false);
const erro = ref(false);
const aberto = ref(false);
const anuncio = ref("");
let timer = null;

const naoLidas = ref(0);
const contagem = computed(() => (naoLidas.value > 9 ? "9+" : naoLidas.value));
const rotuloSino = computed(() => {
  const n = naoLidas.value;
  return n ? `Notificações, ${n} não lida${n > 1 ? "s" : ""}` : "Notificações";
});

async function carregar(silencioso = false) {
  if (!silencioso) loading.value = true;
  erro.value = false;
  try {
    const dados = await listarNotificacoes();
    const lista = Array.isArray(dados) ? dados : (dados?.data ?? []);
    notificacoes.value = lista;
    naoLidas.value = lista.length;
  } catch {
    if (!silencioso) erro.value = true;
  } finally {
    loading.value = false;
  }
}

// Polling barato: só o número. Se o endpoint não existir, cai para a lista completa.
async function atualizarContagem() {
  try {
    const { nao_lidas } = await contagemNotificacoes();
    const n = Number(nao_lidas) || 0;
    if (n > naoLidas.value) anuncio.value = "Você tem novas notificações.";
    naoLidas.value = n;
  } catch (e) {
    if (e.response?.status === 404) await carregar(true);
  }
}

async function marcarTodas() {
  try {
    await marcarTodasLidas();
    notificacoes.value = [];
    naoLidas.value = 0;
  } catch {
    erro.value = true;
  }
}

async function abrir(n) {
  try {
    await marcarLida(n.id);
    notificacoes.value = notificacoes.value.filter((i) => i.id !== n.id);
    naoLidas.value = Math.max(0, naoLidas.value - 1);
  } catch {
    /* segue para o link mesmo assim */
  }
  aberto.value = false;

  if (!n.link) return;
  if (/^https?:\/\//i.test(n.link)) {
    window.open(n.link, "_blank", "noopener,noreferrer");
  } else {
    router.push(n.link.startsWith("/") ? n.link : "/" + n.link);
  }
}

watch(aberto, (v) => v && carregar());

function aoMudarVisibilidade() {
  if (document.visibilityState === "visible") atualizarContagem();
}

onMounted(() => {
  atualizarContagem();
  timer = setInterval(() => {
    if (document.visibilityState === "visible") atualizarContagem();
  }, 60000);
  document.addEventListener("visibilitychange", aoMudarVisibilidade);
});

onBeforeUnmount(() => {
  clearInterval(timer);
  document.removeEventListener("visibilitychange", aoMudarVisibilidade);
});
</script>

<style scoped>
.lista {
  max-height: 340px;
  overflow-y: auto;
}
.painel {
  background: rgb(var(--v-theme-surface));
}
</style>
