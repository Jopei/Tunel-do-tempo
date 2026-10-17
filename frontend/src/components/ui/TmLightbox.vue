<template>
  <v-dialog
    :model-value="modelValue"
    fullscreen
    :scrim="false"
    transition="fade-transition"
    aria-label="Visualizador de mídia"
    @update:model-value="(v) => !v && fechar()"
    @keydown="aoTecla"
  >
    <div
      class="lb"
      role="dialog"
      aria-modal="true"
      :aria-label="rotulo"
      @touchstart.passive="tocarInicio"
      @touchend.passive="tocarFim"
    >
      <div class="lb-topo">
        <span class="lb-contador" aria-live="polite">{{ indice + 1 }} / {{ itens.length }}</span>
        <div class="lb-acoes">
          <v-btn
            v-if="compartilhavel"
            icon
            variant="text"
            color="white"
            aria-label="Copiar link"
            @click="$emit('compartilhar', atual)"
          >
            <v-icon>mdi-link-variant</v-icon>
          </v-btn>
          <v-btn
            v-if="baixavel && atual?.url"
            icon
            variant="text"
            color="white"
            aria-label="Baixar"
            :href="atual.url"
            target="_blank"
            rel="noopener noreferrer"
            download
          >
            <v-icon>mdi-download</v-icon>
          </v-btn>
          <v-btn icon variant="text" color="white" aria-label="Fechar" @click="fechar">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </div>

      <v-btn
        v-if="itens.length > 1"
        icon
        size="large"
        class="lb-seta esq"
        color="white"
        variant="tonal"
        aria-label="Anterior"
        :disabled="indice === 0"
        @click="ir(-1)"
      >
        <v-icon>mdi-chevron-left</v-icon>
      </v-btn>

      <div class="lb-palco">
        <video
          v-if="tipo === 'video' && atual"
          :key="atual.uuid"
          ref="videoRef"
          class="lb-midia"
          :src="atual.url"
          controls
          preload="metadata"
          playsinline
        />
        <img
          v-else-if="atual"
          :key="atual.uuid"
          class="lb-midia"
          :class="{ zoom }"
          :src="atual.url"
          :alt="atual.titulo || `Foto ${indice + 1} de ${itens.length}`"
          @dblclick="zoom = !zoom"
        />
      </div>

      <v-btn
        v-if="itens.length > 1"
        icon
        size="large"
        class="lb-seta dir"
        color="white"
        variant="tonal"
        aria-label="Próxima"
        :disabled="indice === itens.length - 1"
        @click="ir(1)"
      >
        <v-icon>mdi-chevron-right</v-icon>
      </v-btn>

      <div v-if="atual?.titulo || atual?.descricao" class="lb-legenda">
        <strong v-if="atual.titulo">{{ atual.titulo }}</strong>
        <span v-if="atual.descricao">{{ atual.descricao }}</span>
      </div>
    </div>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  itens: { type: Array, default: () => [] },
  inicio: { type: Number, default: 0 },
  tipo: { type: String, default: "foto" },
  baixavel: { type: Boolean, default: false },
  compartilhavel: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "mudou", "compartilhar"]);

const indice = ref(0);
const zoom = ref(false);
const videoRef = ref(null);
let toqueX = null;
let gatilho = null;

const atual = computed(() => props.itens[indice.value]);
const rotulo = computed(() => (props.tipo === "video" ? "Visualizador de vídeos" : "Visualizador de fotos"));

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      gatilho = document.activeElement;
      indice.value = Math.min(Math.max(props.inicio, 0), Math.max(props.itens.length - 1, 0));
      zoom.value = false;
      emit("mudou", atual.value);
      precarregar();
    } else {
      // devolve o foco ao elemento que abriu o visualizador
      nextTick(() => gatilho?.focus?.());
    }
  },
);

function ir(delta) {
  const novo = indice.value + delta;
  if (novo < 0 || novo >= props.itens.length) return;
  videoRef.value?.pause?.();
  indice.value = novo;
  zoom.value = false;
  emit("mudou", atual.value);
  nextTick(precarregar);
}

function precarregar() {
  if (props.tipo !== "foto") return;
  [-1, 1].forEach((d) => {
    const it = props.itens[indice.value + d];
    if (it?.url) new Image().src = it.url;
  });
}

function fechar() {
  videoRef.value?.pause?.();
  emit("update:modelValue", false);
}

function aoTecla(e) {
  if (e.key === "ArrowLeft") ir(-1);
  else if (e.key === "ArrowRight") ir(1);
}

function tocarInicio(e) {
  toqueX = e.changedTouches[0].clientX;
}
function tocarFim(e) {
  if (toqueX == null) return;
  const dx = e.changedTouches[0].clientX - toqueX;
  toqueX = null;
  if (Math.abs(dx) > 60) ir(dx < 0 ? 1 : -1);
}
</script>

<style scoped>
.lb {
  position: relative;
  width: 100%;
  height: 100dvh;
  background: rgba(11, 9, 5, 0.96);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}
.lb-topo {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  z-index: 2;
}
.lb-contador {
  font-weight: 600;
}
.lb-palco {
  width: 100%;
  height: 100%;
  padding: 64px 16px 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
}
.lb-midia {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: var(--tm-radius-xs);
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.lb-midia.zoom {
  transform: scale(1.8);
  cursor: zoom-out;
}
.lb-seta {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  width: 48px;
  height: 48px;
}
.lb-seta.esq {
  left: 8px;
}
.lb-seta.dir {
  right: 8px;
}
.lb-legenda {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 16px 72px;
  display: flex;
  flex-direction: column;
  text-align: center;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
}
@media (max-width: 600px) {
  .lb-legenda {
    padding: 16px;
  }
  .lb-seta.esq {
    left: 2px;
  }
  .lb-seta.dir {
    right: 2px;
  }
}
</style>
