<template>
  <TmFormDialog :model-value="modelValue" titulo="Músicas da história" max-width="720" @update:model-value="(v) => emit('update:modelValue', v)">
    <p v-if="!totalFaixas" class="vazio">Nenhuma música nesta história.</p>
    <ol v-else class="lista-musicas">
      <li v-for="(m, i) in itens" :key="m.uuid" class="musica-item">
        <v-icon size="28" aria-hidden="true">mdi-music</v-icon>
        <div class="faixa">
          <span class="nome">{{ m.titulo || `Faixa ${i + 1} de ${itens.length}` }}</span>
          <v-skeleton-loader v-if="m.estado === 'carregando'" type="text" class="skel" />
          <span v-else-if="m.estado === 'erro'" class="erro">Não foi possível carregar esta música.</span>
          <audio
            v-else
            :ref="(el) => (players[i] = el)"
            :src="m.url"
            controls
            preload="none"
            class="audio-player"
            :aria-label="`Música ${i + 1} de ${itens.length}`"
            @play="aoTocar(i)"
            @ended="proxima(i)"
          />
        </div>
      </li>
    </ol>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { buscarMusicaPorUuid } from "@/services/musica.show.service";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const props = defineProps({
  musicas: { type: Array, default: () => [] },
  midia: { type: Array, default: null },
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue"]);

const itens = ref([]);
const totalFaixas = computed(() => (props.midia?.length ? props.midia.length : props.musicas.length));
const players = [];
let carregadas = false;

function liberar() {
  itens.value.forEach((m) => m.url && URL.revokeObjectURL(m.url));
  itens.value = [];
  carregadas = false;
}

// A API de músicas exige login e devolve o arquivo; baixa em paralelo, com placeholder por item.
async function carregar() {
  const base = props.midia?.length ? props.midia : props.musicas.map((uuid) => ({ uuid }));
  itens.value = base.map((m) => ({ uuid: m.uuid, titulo: m.titulo || "", url: null, estado: "carregando" }));
  carregadas = true;
  await Promise.allSettled(
    itens.value.map(async (m, i) => {
      try {
        itens.value[i] = { ...m, url: await buscarMusicaPorUuid(m.uuid), estado: "ok" };
      } catch {
        itens.value[i] = { ...m, estado: "erro" };
      }
    }),
  );
}

// Toca uma por vez
function aoTocar(atual) {
  players.forEach((p, i) => {
    if (i !== atual && p && !p.paused) p.pause();
  });
}

// Playlist: ao terminar, segue para a próxima faixa
function proxima(i) {
  players[i + 1]?.play?.();
}

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto && !carregadas) carregar();
    if (!aberto) players.forEach((p) => p?.pause?.());
  },
);

onBeforeUnmount(liberar);
</script>

<style scoped>
.vazio {
  text-align: center;
  padding: var(--tm-space-7);
  color: var(--tm-ink-muted);
}
.lista-musicas {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-4);
}
.musica-item {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  padding: var(--tm-space-3);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: rgb(var(--v-theme-surface-variant));
}
.faixa {
  flex: 1;
  min-width: 0;
}
.nome {
  display: block;
  font-weight: 600;
  margin-bottom: var(--tm-space-1);
}
.audio-player {
  width: 100%;
}
.erro {
  color: rgb(var(--v-theme-error));
}
.skel {
  background: transparent;
}
</style>
