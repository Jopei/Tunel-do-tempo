<template>
  <header v-if="!esconder" class="app-navbar">
    <div class="barra">
      <v-btn
        class="d-md-none"
        icon
        variant="text"
        color="on-surface"
        aria-label="Abrir menu"
        :aria-expanded="drawer"
        @click="drawer = true"
      >
        <v-icon>mdi-menu</v-icon>
      </v-btn>

      <router-link to="/" class="marca" aria-label="Túnel do Tempo, ir para o início">
        <span class="tm-title">Túnel do Tempo</span>
      </router-link>

      <nav class="links d-none d-md-flex" aria-label="Principal">
        <v-btn
          v-for="item in itens"
          :key="item.to"
          :class="{ 'link-mais-item': item.mais, 'v-btn--active': ehRua(item) }"
          :to="item.to"
          :exact="item.to === '/'"
          variant="text"
          color="on-surface"
          class="link"
          :prepend-icon="item.bloqueado ? 'mdi-lock-outline' : undefined"
          :title="item.bloqueado ? 'Entre para acessar' : undefined"
        >
          {{ item.titulo }}
        </v-btn>

        <!-- 960-1179px: Músicas e Atualizações agrupadas em "Mais" -->
        <v-menu location="bottom start">
          <template #activator="{ props }">
            <v-btn
              v-bind="props"
              variant="text"
              color="on-surface"
              class="link link-mais"
              :class="{ 'v-btn--active': maisAtivo }"
              prepend-icon="mdi-dots-horizontal"
            >
              Mais
            </v-btn>
          </template>
          <v-list density="comfortable" aria-label="Mais">
            <v-list-item
              v-for="item in itensMais"
              :key="item.to"
              :to="item.to"
              :prepend-icon="item.bloqueado ? 'mdi-lock-outline' : item.icone"
              :title="item.titulo"
            />
          </v-list>
        </v-menu>
      </nav>

      <v-spacer />

      <div class="acoes">
        <v-btn
          v-if="!auth.logado"
          class="d-none d-md-inline-flex"
          icon
          variant="text"
          color="on-surface"
          :aria-label="tema.efetivo.value === 'escuro' ? 'Usar tema claro' : 'Usar tema escuro'"
          :aria-pressed="tema.efetivo.value === 'escuro'"
          @click="tema.alternar()"
        >
          <v-icon>{{ tema.efetivo.value === "escuro" ? "mdi-weather-sunny" : "mdi-weather-night" }}</v-icon>
        </v-btn>

        <template v-if="auth.logado">
          <v-menu v-if="auth.isMembro" location="bottom end">
            <template #activator="{ props }">
              <v-btn v-bind="props" variant="tonal" color="primary" prepend-icon="mdi-plus" class="criar-btn">
                <span class="d-none d-sm-inline">Criar</span>
                <span class="d-sm-none sr-only">Criar</span>
              </v-btn>
            </template>
            <v-list density="comfortable" aria-label="Criar">
              <v-list-item prepend-icon="mdi-book-open-page-variant-outline" title="História" to="/historias/criar" />
              <v-list-item prepend-icon="mdi-image-plus" title="Foto(s)" @click="abrirModal('foto')" />
              <v-list-item prepend-icon="mdi-video-plus-outline" title="Vídeo" @click="abrirModal('video')" />
              <v-list-item prepend-icon="mdi-music-note-plus" title="Música" to="/musicas?nova=1" />
              <v-list-item prepend-icon="mdi-update" title="Atualização" @click="abrirModal('atualizacao')" />
            </v-list>
          </v-menu>

          <v-btn icon variant="text" color="on-surface" aria-label="Buscar (Ctrl+K)" @click="abrirBusca">
            <v-icon>mdi-magnify</v-icon>
          </v-btn>
          <NotificacaoBell />
          <UserMenu @abrir-atualizacao="abrirModal('atualizacao')" />
        </template>

        <v-btn v-else to="/login" color="primary" variant="flat" class="entrar">Entrar</v-btn>
      </div>
    </div>

    <v-navigation-drawer v-model="drawer" temporary location="left" width="300" aria-label="Menu principal">
      <v-list nav density="comfortable">
        <v-list-item class="mb-2">
          <v-list-item-title class="tm-title text-h6">Túnel do Tempo</v-list-item-title>
        </v-list-item>
        <v-list-item
          v-for="item in itens"
          :key="item.to"
          :to="item.to"
          :exact="item.to === '/'"
          :active="ehRua(item) || undefined"
          :prepend-icon="item.icone"
          :title="item.titulo"
          @click="drawer = false"
        >
          <template v-if="item.bloqueado" #append>
            <v-icon size="18" aria-label="Exige login">mdi-lock-outline</v-icon>
          </template>
        </v-list-item>
        <v-list-item
          to="/aquele-que-sonhou"
          prepend-icon="mdi-candle"
          title="Em memória de Italo"
          @click="drawer = false"
        />
      </v-list>
      <template #append>
        <div class="pa-3 d-flex flex-column ga-2">
          <p v-if="!auth.logado" class="text-body-2 text-medium-emphasis mb-0">
            Entre para ver as histórias da rua.
          </p>
          <v-btn v-if="!auth.logado" to="/login" color="primary" block variant="flat" @click="drawer = false">
            Entrar
          </v-btn>
          <v-btn
            variant="text"
            block
            color="on-surface"
            :prepend-icon="tema.efetivo.value === 'escuro' ? 'mdi-weather-sunny' : 'mdi-weather-night'"
            :aria-pressed="tema.efetivo.value === 'escuro'"
            @click="tema.alternar()"
          >
            {{ tema.efetivo.value === "escuro" ? "Tema claro" : "Tema escuro" }}
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <CriarFotoModal v-if="montado.foto" v-model="modal.foto" />
    <CriarVideoModal v-if="montado.video" v-model="modal.video" />
    <CriarAtualizacaoModal v-if="montado.atualizacao" v-model="modal.atualizacao" />
  </header>
</template>

<script setup>
import { ref, reactive, computed, onMounted, defineAsyncComponent } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useTema } from "@/composables/useTema";
import { verificarAtualizacoesNovas } from "@/composables/useAtualizacoesNovas";
import { abrirBusca } from "@/composables/useBusca";
import NotificacaoBell from "@/components/notificacoes/NotificacaoBell.vue";
import UserMenu from "@/components/layout/UserMenu.vue";

// Os modais (e o editor toast-ui) só são baixados quando alguém abre um deles.
const CriarFotoModal = defineAsyncComponent(() => import("@/components/fotos/CriarFotoModal.vue"));
const CriarVideoModal = defineAsyncComponent(() => import("@/components/videos/CriarVideoModal.vue"));
const CriarAtualizacaoModal = defineAsyncComponent(
  () => import("@/components/atualizacoes/CriarAtualizacaoModal.vue"),
);

const auth = useAuthStore();
const route = useRoute();
const tema = useTema();

const drawer = ref(false);
const modal = reactive({ foto: false, video: false, atualizacao: false });
const montado = reactive({ foto: false, video: false, atualizacao: false });

const esconder = computed(() => route.meta.hideNavbar);

const itens = computed(() => [
  { titulo: "Início", to: "/", icone: "mdi-home-outline" },
  { titulo: "Histórias", to: "/historias", icone: "mdi-book-open-page-variant-outline", bloqueado: !auth.logado },
  { titulo: "A Rua", to: "/a-rua", icone: "mdi-road-variant" },
  { titulo: "Fotos", to: "/galeria-fotos", icone: "mdi-image-multiple-outline" },
  { titulo: "Vídeos", to: "/galeria-videos", icone: "mdi-video-outline", bloqueado: !auth.logado },
  { titulo: "Músicas", to: "/musicas", icone: "mdi-music-note-outline", bloqueado: !auth.logado, mais: true },
  { titulo: "Atualizações", to: "/atualizacoes", icone: "mdi-update", mais: true },
]);
// A Rua fica ativa nas páginas de membro (/membros/:slug).
const ehRua = (item) => item.to === "/a-rua" && route.path.startsWith("/membros/");
const itensMais = computed(() => itens.value.filter((i) => i.mais));
const maisAtivo = computed(() => itensMais.value.some((i) => route.path.startsWith(i.to)));

function abrirModal(nome) {
  montado[nome] = true;
  modal[nome] = true;
}

onMounted(() => {
  verificarAtualizacoesNovas();
});
</script>

<style scoped>
.app-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  height: var(--tm-navbar-h);
  background: rgba(var(--v-theme-surface), 0.9);
  color: rgb(var(--v-theme-on-surface));
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--tm-border);
}

.barra {
  height: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.marca {
  color: inherit;
  text-decoration: none;
  font-size: 1.15rem;
  white-space: nowrap;
  padding: 8px;
  border-radius: var(--tm-radius-xs);
}

.links {
  gap: 4px;
  margin-left: 16px;
}

.link {
  font-weight: 600;
  letter-spacing: 0;
}

/* Entre 960 e 1179px os itens "mais" ficam no menu; a partir de 1180px aparecem na barra. */
.link-mais-item {
  display: none !important;
}
.link-mais {
  display: inline-flex;
}
@media (min-width: 1180px) {
  .link-mais-item {
    display: inline-flex !important;
  }
  .link-mais {
    display: none;
  }
}

/* Rota ativa: sublinhado ouro (não depende só da cor) */
.link.v-btn--active {
  box-shadow: inset 0 -3px 0 var(--tm-gold);
  border-radius: 0;
}
.link.v-btn--active :deep(.v-btn__overlay) {
  opacity: 0;
}

.acoes {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Microinterações: marca sobe 1px no hover; botões de ação afundam no clique (transform) */
.marca .tm-title {
  display: inline-block;
  transition: transform var(--tm-dur-fast) var(--tm-ease);
}
.marca:hover .tm-title {
  transform: translateY(-1px);
}
.entrar,
.criar-btn {
  transition: transform var(--tm-dur-fast) var(--tm-ease);
}
.entrar:active,
.criar-btn:active {
  transform: scale(0.97);
}
@media (prefers-reduced-motion: reduce) {
  .marca .tm-title,
  .entrar,
  .criar-btn {
    transition: none;
  }
  .marca:hover .tm-title,
  .entrar:active,
  .criar-btn:active {
    transform: none;
  }
}
html[data-reduzir="1"] .marca .tm-title,
html[data-reduzir="1"] .entrar,
html[data-reduzir="1"] .criar-btn {
  transition: none;
}
html[data-reduzir="1"] .marca:hover .tm-title,
html[data-reduzir="1"] .entrar:active,
html[data-reduzir="1"] .criar-btn:active {
  transform: none;
}

.entrar {
  color: rgb(var(--v-theme-on-primary)) !important;
  font-weight: 700;
}

@media (max-width: 600px) {
  .marca {
    font-size: 1rem;
  }
  .criar-btn {
    min-width: 44px;
  }
}
</style>
