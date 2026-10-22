<template>
  <v-dialog v-model="buscaAberta" max-width="640" :fullscreen="mobile" scrollable>
    <v-card aria-label="Busca">
      <div class="topo">
        <v-text-field
          ref="campo"
          v-model="q"
          label="Buscar histórias, fotos, vídeos e membros"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          autofocus
          autocomplete="off"
          @keydown.enter="irPrimeiro"
        />
        <v-btn icon variant="text" color="on-surface" aria-label="Fechar busca" @click="buscaAberta = false">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <v-card-text class="corpo" aria-live="polite">
        <p v-if="q.trim().length < 2" class="dica">Digite ao menos 2 letras. Atalho: Ctrl+K</p>
        <v-progress-linear v-else-if="carregando" indeterminate color="primary" aria-label="Buscando" />
        <p v-else-if="erro" class="dica" role="alert">Não foi possível buscar agora. Tente de novo.</p>
        <p v-else-if="!total" class="dica">Nada encontrado para “{{ q.trim() }}”.</p>

        <template v-for="g in grupos" :key="g.chave">
          <div v-if="g.itens.length" class="grupo">
            <h2 class="grupo-titulo">{{ g.titulo }}</h2>
            <ul>
              <li v-for="it in g.itens" :key="it.uuid">
                <router-link :to="g.destino(it)" class="res" @click="buscaAberta = false">
                  <v-icon aria-hidden="true">{{ g.icone }}</v-icon>
                  <span>
                    <strong>{{ it.titulo || it.nome }}</strong>
                    <small v-if="it.descricao_curta">{{ it.descricao_curta }}</small>
                  </span>
                </router-link>
              </li>
            </ul>
          </div>
        </template>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import { buscar } from "@/services/busca.service";
import { buscaAberta } from "@/composables/useBusca";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const auth = useAuthStore();
const { mobile } = useDisplay();

const q = ref("");
const resultado = ref({});
const carregando = ref(false);
const erro = ref(false);
let timer = null;
let controller = null;

const grupos = computed(() => [
  { chave: "historias", titulo: "Histórias", icone: "mdi-book-open-page-variant-outline", itens: resultado.value.historias ?? [], destino: (i) => `/historias/${i.uuid}` },
  { chave: "fotos", titulo: "Fotos", icone: "mdi-image-outline", itens: resultado.value.fotos ?? [], destino: (i) => `/galeria-fotos?foto=${i.uuid}` },
  { chave: "videos", titulo: "Vídeos", icone: "mdi-video-outline", itens: resultado.value.videos ?? [], destino: () => "/galeria-videos" },
  { chave: "membros", titulo: "Membros", icone: "mdi-account-outline", itens: resultado.value.membros ?? [], destino: (i) => `/membros/${i.slug || i.uuid}` },
]);
const total = computed(() => grupos.value.reduce((n, g) => n + g.itens.length, 0));

watch(q, (v) => {
  clearTimeout(timer);
  controller?.abort();
  const termo = (v ?? "").trim();
  if (termo.length < 2) {
    resultado.value = {};
    carregando.value = false;
    return;
  }
  carregando.value = true;
  timer = setTimeout(async () => {
    controller = new AbortController();
    erro.value = false;
    try {
      resultado.value = await buscar(termo, { signal: controller.signal });
    } catch (e) {
      if (e?.code !== "ERR_CANCELED") {
        erro.value = true;
        resultado.value = {};
      }
    } finally {
      carregando.value = false;
    }
  }, 300);
});

watch(buscaAberta, (v) => {
  if (!v) {
    q.value = "";
    resultado.value = {};
  }
});

function irPrimeiro() {
  const g = grupos.value.find((x) => x.itens.length);
  if (!g) return;
  buscaAberta.value = false;
  router.push(g.destino(g.itens[0]));
}

function aoTecla(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k" && auth.logado) {
    e.preventDefault();
    buscaAberta.value = !buscaAberta.value;
  }
}
onMounted(() => window.addEventListener("keydown", aoTecla));
onBeforeUnmount(() => {
  window.removeEventListener("keydown", aoTecla);
  clearTimeout(timer);
});
</script>

<style scoped>
.topo {
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  padding: var(--tm-space-3) var(--tm-space-3) 0 var(--tm-space-4);
}
.corpo {
  min-height: 160px;
}
.dica {
  color: var(--tm-ink-muted);
  margin: var(--tm-space-3) 0;
}
.grupo-titulo {
  font-size: var(--tm-fs-sm);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--tm-ink-muted);
  margin: var(--tm-space-4) 0 var(--tm-space-2);
}
ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.res {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  padding: var(--tm-space-2) var(--tm-space-3);
  min-height: 48px;
  border-radius: var(--tm-radius-sm);
  color: inherit;
  text-decoration: none;
}
.res:hover {
  background: rgb(var(--v-theme-surface-variant));
}
.res span {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.res small {
  color: var(--tm-ink-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
