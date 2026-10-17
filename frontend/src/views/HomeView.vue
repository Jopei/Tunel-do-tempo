<template>
  <div class="home-view tm-immersive">
    <div class="hero-content">
      <h1 class="reveal" style="--delay: 0s">Túnel do Tempo</h1>

      <figure class="citacao reveal" style="--delay: 0.15s">
        <blockquote>
          <p>O tempo não apaga o que a luz tocou, apenas o transforma em memória.</p>
        </blockquote>
      </figure>

      <div class="ctas reveal" style="--delay: 0.25s">
        <v-btn :to="auth.logado ? '/historias' : { name: 'login', query: { redirect: '/historias' } }" size="large" variant="flat" color="primary">
          Ver histórias
        </v-btn>
        <v-btn to="/a-rua" size="large" variant="outlined" color="white">Conhecer a rua</v-btn>
      </div>

      <FolhaHome class="reveal" style="--delay: 0.35s">
        <FeaturedVideoCard />

        <section class="ultimas" aria-labelledby="ultimas-titulo">
          <h2 id="ultimas-titulo" class="tm-title">Histórias da rua</h2>

          <template v-if="auth.logado">
            <TmSkeletonGrid v-if="carregando" :quantidade="3" tipo="article" minimo="240px" />
            <TmErrorState v-else-if="erro" texto="Não conseguimos carregar as histórias agora." @retry="carregar" />
            <TmEmptyState v-else-if="!historias.length" icone="mdi-book-open-page-variant-outline" titulo="Nenhuma história ainda." />
            <ul v-else class="cards">
              <li v-for="h in historias" :key="h.uuid">
                <router-link :to="`/historias/${h.uuid}`" class="card-hist">
                  <h3 class="tm-title">{{ h.titulo }}</h3>
                  <p>{{ h.descricao_curta }}</p>
                  <span class="ler">Ler história <v-icon size="16" aria-hidden="true">mdi-arrow-right</v-icon></span>
                </router-link>
              </li>
            </ul>
            <div class="text-center mt-4">
              <v-btn to="/historias" variant="text" color="primary-text">Ver todas as histórias</v-btn>
            </div>
          </template>

          <template v-else-if="recentes.length">
            <ul class="cards">
              <li v-for="h in recentes" :key="h.uuid">
                <router-link :to="{ name: 'login', query: { redirect: `/historias/${h.uuid}` } }" class="card-hist">
                  <h3 class="tm-title">{{ h.titulo }}</h3>
                  <p>{{ h.descricao_curta }}</p>
                  <span class="ler">Entre para ler <v-icon size="16" aria-hidden="true">mdi-lock-outline</v-icon></span>
                </router-link>
              </li>
            </ul>
          </template>

          <div v-else class="convite">
            <v-icon size="40" aria-hidden="true">mdi-lock-outline</v-icon>
            <p>As histórias da rua são guardadas só para quem faz parte dela.</p>
            <v-btn :to="{ name: 'login', query: { redirect: '/historias' } }" variant="flat" color="primary">
              Entre para ler
            </v-btn>
          </div>
        </section>

        <AniversariantesMes v-if="auth.logado" />
      </FolhaHome>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import { buscarHistoriasResumo, buscarHistoriasRecentes } from "@/services/historia.service";
import AniversariantesMes from "@/components/ui/AniversariantesMes.vue";
import FolhaHome from "@/components/layout/FolhaHome.vue";
import FeaturedVideoCard from "@/components/video/FeaturedVideoCard.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const auth = useAuthStore();
const historias = ref([]);
const recentes = ref([]);
const carregando = ref(false);
const erro = ref(false);

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    const lista = await buscarHistoriasResumo();
    historias.value = (lista.data ?? lista).slice(0, 3);
  } catch {
    erro.value = true;
  } finally {
    carregando.value = false;
  }
}

async function carregarRecentes() {
  try {
    const r = await buscarHistoriasRecentes(3);
    // Tolerante: só usa itens com título (endpoint antigo devolvia apenas uuid)
    recentes.value = (Array.isArray(r) ? r : []).filter((h) => h.titulo).slice(0, 3);
  } catch {
    recentes.value = [];
  }
}

watch(
  () => auth.logado,
  (v) => (v ? carregar() : carregarRecentes()),
  { immediate: true },
);
</script>

<style scoped>
.home-view {
  min-height: 100dvh;
  background-image: linear-gradient(rgba(0, 0, 0, 0.35), transparent 40%), url("/backgrounds/home-bg.svg");
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  display: flex;
  justify-content: center;
}

[data-tema="escuro"] .home-view {
  background-image: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.3) 40%), url("/backgrounds/home-bg.svg");
}

.hero-content {
  width: 100%;
  max-width: 1200px;
  padding: calc(var(--tm-navbar-h) + 32px) 0 0;
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

h1 {
  font-size: var(--tm-fs-hero);
  color: #fff;
  text-align: center;
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
  padding: 0 var(--tm-space-4);
}

.citacao {
  margin: var(--tm-space-2) var(--tm-space-4) var(--tm-space-5);
  text-align: center;
  color: #fff;
  font-style: italic;
  font-size: var(--tm-fs-lg);
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
}

.ctas {
  display: flex;
  gap: var(--tm-space-3);
  justify-content: center;
  flex-wrap: wrap;
  padding: 0 var(--tm-space-4);
}

.ultimas {
  width: 100%;
}
.ultimas h2 {
  font-size: var(--tm-fs-2xl);
  text-align: center;
  margin-bottom: var(--tm-space-5);
}
.cards {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--tm-space-4);
}
.card-hist {
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-2);
  height: 100%;
  padding: var(--tm-space-5);
  border-radius: var(--tm-radius-md);
  border: 1px solid var(--tm-border);
  background: rgb(var(--v-theme-surface-variant));
  color: inherit;
  text-decoration: none;
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.card-hist:hover {
  transform: translateY(-3px);
  box-shadow: var(--tm-shadow-md);
}
.card-hist h3 {
  font-size: var(--tm-fs-xl);
}
.card-hist p {
  margin: 0;
  color: var(--tm-ink-muted);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.ler {
  margin-top: auto;
  color: var(--tm-gold-text);
  font-weight: 600;
}
.convite {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-3);
}
.convite p {
  margin: 0;
  color: var(--tm-ink-muted);
}
</style>
