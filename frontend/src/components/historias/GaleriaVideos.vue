<template>
  <TmFormDialog :model-value="modelValue" titulo="Vídeos da história" max-width="1200" @update:model-value="(v) => emit('update:modelValue', v)">
    <p v-if="!itens.length" class="vazio">Nenhum vídeo nesta história.</p>
    <ul v-else class="grid-videos">
      <li v-for="(video, i) in itens" :key="video.uuid">
        <button type="button" class="thumb" :aria-label="`Reproduzir ${video.titulo}`" @click="abrir(i)">
          <img v-if="video.thumbnail" :src="video.thumbnail" alt="" loading="lazy" class="poster" />
          <v-icon size="56" aria-hidden="true" class="play-i">mdi-play-circle</v-icon>
          <span class="rotulo">{{ video.titulo }}</span>
        </button>
      </li>
    </ul>
  </TmFormDialog>

  <TmLightbox v-model="lightbox" :itens="itens" :inicio="inicio" tipo="video" />
</template>

<script setup>
import { ref, computed } from "vue";
import { urlVideo } from "@/services/midia.url";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmLightbox from "@/components/ui/TmLightbox.vue";

const props = defineProps({
  videos: { type: Array, default: () => [] },
  midia: { type: Array, default: null },
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);

const lightbox = ref(false);
const inicio = ref(0);

// Sem <video> por card: nada é baixado antes de o usuário abrir um vídeo.
const itens = computed(() => {
  const base = props.midia?.length ? props.midia : props.videos.map((uuid) => ({ uuid }));
  return base.map((v, i) => ({
    uuid: v.uuid,
    url: v.url || urlVideo(v.uuid),
    thumbnail: v.poster_url || null,
    titulo: v.titulo || `Vídeo ${i + 1} de ${base.length}`,
  }));
});

function abrir(i) {
  inicio.value = i;
  lightbox.value = true;
}
</script>

<style scoped>
.vazio {
  text-align: center;
  padding: var(--tm-space-7);
  color: var(--tm-ink-muted);
}
.grid-videos {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--tm-space-4);
}
.thumb {
  width: 100%;
  aspect-ratio: 16 / 9;
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: linear-gradient(180deg, rgba(199, 164, 58, 0.18), rgba(0, 0, 0, 0.35));
  color: rgb(var(--v-theme-on-surface));
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--tm-space-2);
  cursor: pointer;
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.thumb:hover {
  transform: translateY(-4px);
}
.thumb {
  position: relative;
  overflow: hidden;
}
.poster {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.play-i,
.rotulo {
  position: relative;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
}
.rotulo {
  font-weight: 600;
}
</style>
