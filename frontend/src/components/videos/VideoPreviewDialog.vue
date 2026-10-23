<template>
  <v-dialog
    :model-value="modelValue"
    :fullscreen="xs"
    max-width="1000"
    scrollable
    aria-label="Pré-visualização de vídeo"
    class="video-preview-dialog"
    @update:model-value="(v) => !v && fechar()"
    @keydown="aoTecla"
  >
    <v-card v-if="atual" class="vprev fv-era tm-immersive" role="dialog" aria-modal="true" :aria-labelledby="idTitulo">
      <div class="vprev-topo">
        <h2 :id="idTitulo" class="vprev-titulo">{{ atual.titulo }}</h2>
        <v-btn icon variant="text" aria-label="Fechar" @click="fechar"><v-icon>mdi-close</v-icon></v-btn>
      </div>

      <div class="vprev-palco">
        <v-btn
          v-if="itens.length > 1"
          icon
          variant="tonal"
          class="vprev-seta esq"
          aria-label="Vídeo anterior"
          aria-keyshortcuts="ArrowLeft"
          :disabled="indice === 0"
          @click="ir(-1)"
        >
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <!-- gancho motion 13: .vprev-troca (crossfade + 24px), .vprev-moldura (poster .96 -> 1) -->
        <Transition name="vprev-troca" mode="out-in">
          <div :key="atual.uuid" class="vprev-moldura">
            <video
              v-if="tocando"
              ref="videoEl"
              class="vprev-player"
              :src="atual.url"
              :poster="poster || undefined"
              controls
              autoplay
              preload="metadata"
              playsinline
              @play="aoTocar"
            />
            <button v-else type="button" class="vprev-poster" :aria-label="`Reproduzir ${atual.titulo}`" @click="tocando = true">
              <img v-if="poster" :src="poster" alt="" decoding="async" />
              <v-icon v-else size="64" class="vprev-poster-vazio" aria-hidden="true">mdi-play-circle-outline</v-icon>
              <span class="vprev-play" aria-hidden="true"><v-icon size="36">mdi-play</v-icon></span>
              <span v-if="atual.duracao_segundos" class="vprev-duracao">{{ formatarDuracao(atual.duracao_segundos) }}</span>
            </button>
          </div>
        </Transition>
        <v-btn
          v-if="itens.length > 1"
          icon
          variant="tonal"
          class="vprev-seta dir"
          aria-label="Próximo vídeo"
          aria-keyshortcuts="ArrowRight"
          :disabled="indice === itens.length - 1"
          @click="ir(1)"
        >
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>
      </div>
      <p v-if="itens.length > 1" class="vprev-pos" aria-live="polite">{{ indice + 1 }} de {{ itens.length }}</p>

      <div class="vprev-info">
        <p class="vprev-meta">
          <time v-if="dataRef" :datetime="dataRef" :title="dataCompleta(dataRef)">{{ atual.data_video ? "" : "enviado " }}{{ tempoRelativo(dataRef) }}</time>
          <span v-if="atual.duracao_segundos">{{ formatarDuracao(atual.duracao_segundos) }}</span>
          <span v-if="atual.tamanho_bytes">{{ formatarTamanho(atual.tamanho_bytes) }}</span>
          <span v-if="atual.cadastrado_por">Enviado por {{ atual.cadastrado_por.nome }}</span>
        </p>
        <p v-if="trilha" class="vprev-trilha">{{ trilha }}</p>
        <p v-if="atual.descricao" class="vprev-desc">{{ atual.descricao }}</p>
        <div class="vprev-acoes">
          <v-btn
            variant="flat"
            color="primary"
            class="vprev-acao"
            prepend-icon="mdi-open-in-new"
            :to="{ name: 'video', params: { uuid: atual.uuid }, query: { pasta: atual.pasta_uuid || 'raiz' } }"
            @click="fechar"
          >
            Abrir página
          </v-btn>
          <v-btn v-if="podeEditar" variant="tonal" class="vprev-acao" prepend-icon="mdi-folder-move-outline" @click="$emit('mover', atual)">Mover</v-btn>
          <v-btn v-if="podeEditar" variant="tonal" class="vprev-acao" prepend-icon="mdi-pencil-outline" @click="$emit('renomear', atual)">Renomear</v-btn>
          <v-btn v-if="podeEditar" variant="tonal" class="vprev-acao" prepend-icon="mdi-calendar-edit" @click="$emit('data', atual)">Data</v-btn>
          <v-btn variant="tonal" class="vprev-acao" prepend-icon="mdi-link-variant" @click="$emit('copiar', atual)">Copiar link</v-btn>
          <v-btn variant="tonal" class="vprev-acao" prepend-icon="mdi-download" :href="atual.url" target="_blank" rel="noopener noreferrer" download>Baixar</v-btn>
          <v-btn v-if="podeEditar" variant="text" color="error" class="vprev-acao" prepend-icon="mdi-delete-outline" @click="$emit('excluir', atual)">Excluir</v-btn>
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { useDisplay } from "vuetify";
import { usePlayerStore } from "@/stores/player";
import { formatarDuracao, tempoRelativo, dataCompleta } from "@/composables/useFormat";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";
import { formatarTamanho } from "@/composables/useArquivos";

const idTitulo = `vprev-${Math.random().toString(36).slice(2, 7)}`;

const props = defineProps({
  modelValue: Boolean,
  itens: { type: Array, default: () => [] },
  inicio: { type: Number, default: 0 },
  podeEditar: Boolean,
  pastaNome: { type: String, default: "" },
  /** Caminho da pasta atual: [{ uuid, nome }] (para "Vídeos › 2014 › Festas"). */
  caminho: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "mover", "renomear", "data", "copiar", "excluir"]);

const { xs } = useDisplay();
const player = usePlayerStore();
const indice = ref(0);
const videoEl = ref(null);
let gatilho = null;

const atual = computed(() => props.itens[indice.value]);
const tocando = ref(false);
const poster = computed(() => atual.value?.poster_url || atual.value?.thumbnail || null);
const dataRef = computed(() => atual.value?.data_video || String(atual.value?.created_at ?? "").slice(0, 10) || "");
const trilha = computed(() => (props.caminho.length ? ["Vídeos", ...props.caminho.map((p) => p.nome)].join(" › ") : props.pastaNome ? `Vídeos › ${props.pastaNome}` : ""));
watch(() => [atual.value?.uuid, props.modelValue], () => (tocando.value = false));

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      gatilho = document.activeElement;
      indice.value = Math.min(Math.max(props.inicio, 0), Math.max(props.itens.length - 1, 0));
    } else {
      nextTick(() => gatilho?.focus?.());
    }
  },
);

// Item excluído/movido por fora: mantém o índice válido.
watch(
  () => props.itens.length,
  (n) => {
    if (!props.modelValue) return;
    if (!n) return fechar();
    if (indice.value >= n) indice.value = n - 1;
  },
);

function ir(delta) {
  const novo = indice.value + delta;
  if (novo < 0 || novo >= props.itens.length) return;
  videoEl.value?.pause();
  tocando.value = false;
  indice.value = novo;
}
function fechar() {
  videoEl.value?.pause();
  emit("update:modelValue", false);
}
function aoTocar() {
  // Um som por vez: pausa a música.
  player.pausarPorMidia();
}
function aoTecla(e) {
  // Com o foco no próprio player, as setas servem para avançar/voltar o vídeo.
  if (e.target?.tagName === "VIDEO") return;
  if (e.key === "ArrowLeft") ir(-1);
  else if (e.key === "ArrowRight") ir(1);
}
</script>

<style scoped>
.vprev {
  background: var(--tm-paper);
  color: var(--tm-ink);
}
.vprev-topo {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 8px 0 20px;
}
.vprev-titulo {
  flex: 1;
  margin: 0;
  font-size: var(--tm-fs-xl);
  overflow-wrap: anywhere;
}
.vprev-palco {
  position: relative;
  padding: 8px 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.vprev-moldura {
  position: relative;
  width: 100%;
  max-width: 960px;
  aspect-ratio: 16 / 9;
  border-radius: 8px;
  overflow: hidden;
  background: var(--fv-player-bg);
}
.vprev-player {
  width: 100%;
  height: 100%;
  background: #000;
}
.vprev-poster {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  display: grid;
  place-items: center;
  background: var(--fv-player-bg);
  color: rgba(255, 255, 255, 0.85);
  cursor: pointer;
}
.vprev-poster img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.vprev-poster:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: -3px;
}
.vprev-play {
  position: relative;
  display: grid;
  place-items: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
}
.vprev-poster-vazio {
  position: absolute;
  opacity: 0.25;
}
.vprev-duracao {
  position: absolute;
  right: 10px;
  bottom: 10px;
  padding: 2px 6px;
  border-radius: 3px;
  background: var(--fv-player-barra);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
/* Troca ←/→: crossfade com 24px (transform/opacity). Gancho motion 13. */
.vprev-troca-enter-active,
.vprev-troca-leave-active {
  transition:
    opacity 200ms var(--tm-ease),
    transform 200ms var(--tm-ease);
}
.vprev-troca-enter-from {
  opacity: 0;
  transform: translateX(24px);
}
.vprev-troca-leave-to {
  opacity: 0;
  transform: translateX(-24px);
}
.vprev-pos {
  margin: 0;
  text-align: center;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
}
.vprev-trilha {
  margin: 0 0 8px;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.vprev-acao {
  min-height: 44px;
}
@media (prefers-reduced-motion: reduce) {
  .vprev-troca-enter-active,
  .vprev-troca-leave-active {
    transition: none;
  }
}
html[data-reduzir="1"] .vprev-troca-enter-active,
html[data-reduzir="1"] .vprev-troca-leave-active {
  transition: none;
}
@media (max-width: 599px) {
  .vprev-acoes .vprev-acao {
    flex: 1 1 100%;
  }
}
.vprev-seta {
  position: absolute;
  top: 50%;
  margin-top: -20px;
  z-index: 2;
}
.vprev-seta.esq {
  left: 20px;
}
.vprev-seta.dir {
  right: 20px;
}
@media (max-width: 599px) {
  .vprev-palco {
    padding: 8px 12px;
  }
}
.vprev-info {
  padding: 0 20px 20px;
}
.vprev-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 16px;
  margin: 0 0 8px;
  color: var(--tm-ink-muted);
}
.vprev-pasta {
  color: var(--tm-gold-text);
}
.vprev-desc {
  margin: 0 0 12px;
  overflow-wrap: anywhere;
}
.vprev-acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
