<template>
  <section class="vp-page fv-era tm-immersive">
    <TmErrorState v-if="estado === 'erro'" class="vp-estado" texto="Não conseguimos carregar este vídeo." @retry="carregar" />
    <TmEmptyState
      v-else-if="estado === 'nao-encontrado'"
      class="vp-estado fv-vazio"
      moldura
      icone="mdi-video-off-outline"
      titulo="Vídeo não encontrado"
      texto="Ele pode ter sido movido ou excluído."
    >
      <v-btn variant="flat" color="primary" :to="{ name: 'videos' }">Ir para Vídeos</v-btn>
    </TmEmptyState>
    <div v-else-if="estado === 'carregando'" class="vp-corpo">
      <div class="vp-fantasma" aria-busy="true"><span class="sr-only" role="status">Carregando vídeo</span></div>
    </div>

    <template v-else>
      <!-- Faixa escura atrás do player (gancho motion 14: .vp-faixa expande, .vp-player-caixa faz fade) -->
      <div class="vp-faixa">
        <div class="vp-player-caixa">
          <video
            :key="reinicio"
            ref="videoEl"
            class="vp-player"
            :src="video.url"
            :poster="video.poster_url || video.thumbnail || undefined"
            controls
            preload="metadata"
            playsinline
            @play="aoTocar"
            @ended="terminou = !!proximo"
            @error="erroPlayer = true"
          />
          <div v-if="erroPlayer" class="vp-player-erro" role="alert">
            <v-icon size="40" aria-hidden="true">mdi-video-off-outline</v-icon>
            <p>Não foi possível carregar o vídeo.</p>
            <v-btn variant="flat" color="primary" @click="tentarDeNovo">Tentar de novo</v-btn>
          </div>
          <Transition name="vp-proximo">
            <div v-if="terminou && proximo" class="vp-proximo" role="region" aria-label="Assistir a seguir">
              <span class="vp-proximo-rotulo">Assistir a seguir</span>
              <strong>{{ proximo.titulo }}</strong>
              <div class="vp-proximo-acoes">
                <v-btn variant="flat" color="primary" prepend-icon="mdi-play" :to="{ name: 'video', params: { uuid: proximo.uuid }, query: { pasta: pastaQuery } }">Reproduzir</v-btn>
                <v-btn variant="text" color="white" @click="terminou = false">Fechar</v-btn>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <div class="vp-corpo">
        <div class="vp-grade">
          <div class="vp-principal">
            <nav aria-label="Caminho" class="vp-migalhas">
              <ol>
                <li><router-link :to="{ name: 'videos' }">Vídeos</router-link></li>
                <li v-for="p in caminho" :key="p.uuid"><router-link :to="{ name: 'videos', query: { pasta: p.uuid } }">{{ p.nome }}</router-link></li>
                <li aria-current="page">{{ video.titulo }}</li>
              </ol>
            </nav>

            <h1 class="vp-titulo">{{ video.titulo }}</h1>

            <div class="vp-canal">
              <component
                :is="autor?.slug || autor?.uuid ? 'router-link' : 'span'"
                v-if="autor"
                v-bind="autor.slug || autor.uuid ? { to: { name: 'membro', params: { slug: chaveMembro(autor) } } } : {}"
                class="vp-canal-avatar"
                :aria-label="`Perfil de ${autor.nome}`"
              >
                <v-avatar size="40" color="surface-variant">
                  <img v-if="autor.foto_url" :src="autor.foto_url" alt="" loading="lazy" />
                  <span v-else class="vp-iniciais" aria-hidden="true">{{ iniciais(autor.nome) }}</span>
                </v-avatar>
              </component>
              <div class="vp-canal-texto">
                <strong v-if="autor">{{ autor.nome }}</strong>
                <span v-if="dataRef" class="vp-canal-data">
                  <time :datetime="dataRef" :title="dataCompleta(dataRef)">
                    {{ video.data_video ? "Gravado em" : "Publicado em" }} {{ dataCompleta(dataRef) }} · {{ tempoRelativo(dataRef) }}
                  </time>
                </span>
              </div>
              <div class="vp-canal-acoes">
                <v-btn variant="tonal" prepend-icon="mdi-share-variant-outline" class="vp-acao" @click="compartilhar">Compartilhar</v-btn>
                <v-btn variant="tonal" prepend-icon="mdi-download" class="vp-acao" :href="video.url" target="_blank" rel="noopener noreferrer" download>Baixar</v-btn>
              </div>
            </div>

            <div v-if="video.descricao || video.duracao_segundos || video.tamanho_bytes" class="vp-desc-caixa">
              <p class="vp-desc-meta">
                <span v-if="video.duracao_segundos">{{ formatarDuracao(video.duracao_segundos) }}</span>
                <span v-if="video.tamanho_bytes">{{ formatarTamanho(video.tamanho_bytes) }}</span>
              </p>
              <p v-if="video.descricao" id="vp-desc" class="vp-desc" :class="{ 'vp-desc--recolhida': longa && !descAberta }">{{ video.descricao }}</p>
              <button v-if="longa" type="button" class="vp-mais" :aria-expanded="descAberta" aria-controls="vp-desc" @click="descAberta = !descAberta">
                {{ descAberta ? "Mostrar menos" : "Mostrar mais" }}
              </button>
            </div>
          </div>

          <aside v-if="irmaos.length || anoVideo" class="vp-lateral" aria-labelledby="vp-mais">
            <h2 id="vp-mais" class="vp-lateral-titulo">{{ pastaNome ? `Mais desta pasta` : "Mais vídeos" }}</h2>
            <ul class="vp-lista">
              <li v-for="v in irmaos" :key="v.uuid">
                <router-link
                  class="vp-item"
                  :class="{ 'vp-item--atual': v.uuid === video.uuid }"
                  :aria-current="v.uuid === video.uuid ? 'true' : undefined"
                  :to="{ name: 'video', params: { uuid: v.uuid }, query: { pasta: pastaQuery } }"
                >
                  <span class="vp-mini">
                    <img v-if="v.poster_url || v.thumbnail" :src="v.poster_url || v.thumbnail" alt="" loading="lazy" />
                    <v-icon v-else aria-hidden="true">mdi-play-circle-outline</v-icon>
                    <span v-if="v.duracao_segundos" class="vp-mini-duracao">{{ formatarDuracao(v.duracao_segundos) }}</span>
                  </span>
                  <span class="vp-item-texto">
                    <strong>{{ v.titulo }}</strong>
                    <small v-if="v.uuid === video.uuid" class="vp-reproduzindo"><v-icon size="14" aria-hidden="true">mdi-play</v-icon> Reproduzindo</small>
                  </span>
                </router-link>
              </li>
            </ul>
            <router-link v-if="anoVideo" class="vp-chip-ano" :to="{ name: 'videos', hash: `#fv-cap-${chaveDoAno(anoVideo)}` }" @click="gravarPref('tunel-videos-visao', 'ano')">
              <v-icon size="16" aria-hidden="true">mdi-timeline-outline</v-icon>
              Mais de {{ anoVideo }}
            </router-link>
          </aside>
        </div>
      </div>
    </template>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { listarVideos, buscarDetalhesVideo } from "@/services/video.service";
import { listarPastas } from "@/services/video.pasta.service";
import { usePlayerStore } from "@/stores/player";
import { formatarDuracao, iniciais, parseDataLocal } from "@/composables/useFormat";
import { formatarTamanho } from "@/composables/useArquivos";
import { chaveMembro } from "@/services/membro.service";
import { tempoRelativo, dataCompleta, gravarPref, chaveDoAno } from "@/composables/useEraFotosVideos";
import { useNotify } from "@/composables/useNotify";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";

const route = useRoute();
const ROTA = route.name;
const player = usePlayerStore();
const notify = useNotify();
const estado = ref("carregando"); // carregando | ok | erro | nao-encontrado
const video = ref(null);
const irmaos = ref([]);
const caminho = ref([]);
const videoEl = ref(null);
const erroPlayer = ref(false);
const reinicio = ref(0);
const terminou = ref(false);
const descAberta = ref(false);

const pastaQuery = computed(() => (typeof route.query.pasta === "string" && route.query.pasta ? route.query.pasta : "raiz"));
const pastaNome = computed(() => caminho.value.at(-1)?.nome ?? "");
const autor = computed(() => video.value?.cadastrado_por ?? null);
const dataRef = computed(() => video.value?.data_video || String(video.value?.created_at ?? "").slice(0, 10) || "");
const anoVideo = computed(() => (video.value?.data_video ? parseDataLocal(video.value.data_video)?.getFullYear() : null));
const longa = computed(() => (video.value?.descricao?.length ?? 0) > 180 || (video.value?.descricao?.split("\n").length ?? 0) > 3);
const proximo = computed(() => {
  const i = irmaos.value.findIndex((v) => v.uuid === video.value?.uuid);
  return i >= 0 ? (irmaos.value[i + 1] ?? null) : null;
});

function aoTocar() {
  player.pausarPorMidia();
  terminou.value = false;
}
function tentarDeNovo() {
  erroPlayer.value = false;
  reinicio.value++;
}
async function compartilhar() {
  const url = `${location.origin}/videos/${video.value.uuid}?pasta=${video.value.pasta_uuid || "raiz"}`;
  try {
    if (navigator.share) await navigator.share({ title: video.value.titulo, url });
    else {
      await navigator.clipboard.writeText(url);
      notify.sucesso("Link copiado.");
    }
  } catch (e) {
    if (e?.name !== "AbortError") notify.erro("Não foi possível compartilhar.");
  }
}

/** Vídeos da mesma pasta (pastaUuid null = raiz), para a lateral; inclui o atual, marcado "Reproduzindo". */
async function carregarIrmaos(pastaUuid) {
  const r = await listarVideos(1, { per_page: 31, pasta_uuid: pastaUuid ?? "raiz" });
  return (r.data ?? []).slice(0, 30);
}

let seq = 0;
async function carregar() {
  const meu = ++seq;
  const uuid = route.params.uuid;
  estado.value = "carregando";
  video.value = null;
  erroPlayer.value = false;
  terminou.value = false;
  descAberta.value = false;
  try {
    const v = await buscarDetalhesVideo(uuid);
    if (meu !== seq) return;
    video.value = v;
    document.title = `${v.titulo} · Túnel do Tempo`;
    const [lista, pasta] = await Promise.all([
      carregarIrmaos(v.pasta_uuid).catch(() => []),
      v.pasta_uuid ? listarPastas(v.pasta_uuid).catch(() => null) : null,
    ]);
    if (meu !== seq) return;
    irmaos.value = lista;
    caminho.value = pasta?.caminho ?? [];
    estado.value = "ok";
  } catch (e) {
    if (meu !== seq) return;
    estado.value = e?.response?.status === 404 ? "nao-encontrado" : "erro";
  }
}

watch(
  () => route.params.uuid,
  () => {
    if (route.name === ROTA) carregar();
    else seq++; // saiu da rota: descarta respostas em voo
  },
);
onBeforeUnmount(() => seq++);
onMounted(carregar);
</script>

<style scoped>
.vp-page {
  min-height: 100dvh;
  padding-top: var(--tm-navbar-h);
  background: var(--tm-paper);
  color: var(--tm-ink);
}
.vp-estado {
  padding-top: 48px;
}
/* Faixa "tela" atrás do player: escura nos dois temas */
.vp-faixa {
  background: var(--fv-player-bg);
  padding: 0;
}
.vp-player-caixa {
  position: relative;
  width: min(100%, calc(70vh * 16 / 9));
  margin: 0 auto;
}
.vp-player {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #000;
}
.vp-player-erro,
.vp-proximo {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: rgba(0, 0, 0, 0.82);
  color: #fff;
  text-align: center;
}
.vp-player-erro p {
  margin: 0;
}
.vp-proximo {
  inset: auto 16px 64px auto;
  align-items: flex-start;
  max-width: min(360px, calc(100% - 32px));
  border-radius: 12px;
  text-align: left;
}
.vp-proximo-rotulo {
  font-size: 12px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  opacity: 0.8;
}
.vp-proximo-acoes {
  display: flex;
  gap: 8px;
}
.vp-proximo-enter-active {
  transition:
    opacity var(--tm-dur-base) var(--tm-ease),
    transform var(--tm-dur-base) var(--tm-ease);
}
.vp-proximo-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.vp-corpo {
  max-width: 1400px;
  margin: 0 auto;
  padding: 16px 16px 96px;
}
.vp-grade {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 24px;
  align-items: start;
}
.vp-principal {
  max-width: 1000px;
  min-width: 0;
}
.vp-migalhas ol {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0 0 12px;
  padding: 0;
  list-style: none;
  font-size: var(--tm-fs-sm);
}
.vp-migalhas li + li::before {
  content: "›";
  margin-right: 8px;
  color: var(--tm-ink-muted);
}
.vp-migalhas a {
  color: var(--tm-gold-text);
}
.vp-migalhas li[aria-current] {
  font-weight: 600;
}
.vp-titulo {
  margin: 4px 0 12px;
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: var(--tm-fs-2xl);
  overflow-wrap: anywhere;
}
.vp-canal {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.vp-canal-avatar {
  flex: none;
  display: inline-flex;
  border-radius: 50%;
}
.vp-canal-avatar :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.vp-iniciais {
  font-weight: 700;
  font-size: 0.85rem;
}
.vp-canal-texto {
  flex: 1;
  min-width: 200px;
  display: flex;
  flex-direction: column;
  line-height: 1.3;
}
.vp-canal-data {
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.vp-canal-acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.vp-acao {
  min-height: 44px;
}
.vp-desc-caixa {
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--hx-balao);
}
.vp-desc-meta {
  display: flex;
  gap: 16px;
  margin: 0 0 4px;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
  font-variant-numeric: tabular-nums;
}
.vp-desc {
  margin: 0;
  max-width: 68ch;
  overflow-wrap: anywhere;
  white-space: pre-line;
}
.vp-desc--recolhida {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.vp-mais {
  min-height: 44px;
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--tm-gold-text);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.vp-mais:focus-visible,
.vp-item:focus-visible,
.vp-chip-ano:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: 2px;
}
.vp-lateral-titulo {
  margin: 0 0 12px;
  font-size: var(--tm-fs-lg);
}
.vp-lista {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.vp-item {
  display: flex;
  gap: 12px;
  color: inherit;
  text-decoration: none;
  border-radius: 8px;
}
.vp-item--atual {
  background: var(--hx-balao);
}
.vp-mini {
  position: relative;
  flex: none;
  width: 140px;
  aspect-ratio: 16 / 9;
  display: grid;
  place-items: center;
  overflow: hidden;
  border-radius: 6px;
  background: var(--fv-player-bg);
  color: rgba(255, 255, 255, 0.8);
}
.vp-mini img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.vp-mini-duracao {
  position: absolute;
  right: 4px;
  bottom: 4px;
  padding: 1px 5px;
  border-radius: 3px;
  background: var(--fv-player-barra);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
.vp-item-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.3;
}
.vp-reproduzindo {
  color: var(--tm-gold-text);
  font-weight: 600;
}
.vp-chip-ano {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 16px;
  min-height: 44px;
  padding: 0 14px;
  border-radius: var(--tm-radius-pill);
  background: var(--tm-paper-2);
  color: var(--tm-gold-text);
  font-weight: 600;
  text-decoration: none;
}
.vp-fantasma {
  aspect-ratio: 16 / 9;
  max-width: 1000px;
  border-radius: 8px;
  background: var(--tm-paper-2);
}
@media (min-width: 600px) {
  .vp-player-caixa {
    padding: 16px 0;
  }
  .vp-player {
    border-radius: 12px;
    box-shadow: var(--tm-shadow-lg);
  }
}
@media (max-width: 959px) {
  .vp-grade {
    grid-template-columns: minmax(0, 1fr);
  }
  .vp-lista {
    flex-direction: row;
    overflow-x: auto;
    padding-bottom: 8px;
  }
  .vp-item {
    flex-direction: column;
    width: 200px;
    flex: none;
  }
  .vp-mini {
    width: 200px;
  }
  .vp-proximo {
    inset: auto 8px 56px 8px;
    max-width: none;
  }
}
@media (prefers-reduced-motion: reduce) {
  .vp-proximo-enter-active {
    transition: none;
  }
}
html[data-reduzir="1"] .vp-proximo-enter-active {
  transition: none;
}
</style>
