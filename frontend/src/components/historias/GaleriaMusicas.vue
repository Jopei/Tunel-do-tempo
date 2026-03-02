<template>
  <v-dialog v-model="dialog" max-width="900">
    <v-card class="galeria-card">
      <v-card-title class="titulo">
        Galeria de Músicas
      </v-card-title>

      <v-card-text>

        <div v-if="!musicasCarregadas.length" class="sem-musicas">
          Nenhuma música disponível.
        </div>

        <div v-else class="lista-musicas">
          <div
            v-for="musica in musicasCarregadas"
            :key="musica.uuid"
            class="musica-item"
          >
            <v-icon size="28">mdi-music</v-icon>

            <audio
              :src="musica.url"
              controls
              class="audio-player"
            />
          </div>
        </div>

      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn @click="dialog = false">
          Fechar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { buscarMusicaPorUuid } from "@/services/musica.show.service";

const props = defineProps({
  musicas: {
    type: Array,
    default: () => []
  },
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["update:modelValue"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val)
});

const musicasCarregadas = ref([]);

watch(
  () => dialog.value,
  async (aberto) => {
    if (aberto && props.musicas.length) {
      musicasCarregadas.value = [];

      for (const uuid of props.musicas) {
        try {
          const url = await buscarMusicaPorUuid(uuid);
          musicasCarregadas.value.push({ uuid, url });
        } catch (e) {
          console.error("Erro ao carregar música:", uuid);
        }
      }
    }
  }
);
</script>

<style scoped>

.galeria-card {
  background: #fbf6e6;
  border-radius: 32px;
  padding: 28px;
  box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

.titulo {
  font-weight: 800;
  font-size: 22px;
  text-align: center;
  color: #333;
  margin-bottom: 10px;
}

.sem-musicas {
  text-align: center;
  font-weight: 700;
  padding: 40px;
  color: #777;
}

.lista-musicas {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.musica-item {
  background: rgba(199, 164, 58, 0.12);
  border: 2px solid rgba(199, 164, 58, 0.35);
  border-radius: 22px;
  padding: 16px;

  display: flex;
  align-items: center;
  gap: 20px;

  transition: all .25s ease;
}

.musica-item:hover {
  background: rgba(199, 164, 58, 0.22);
  border-color: #c7a43a;
  transform: translateY(-4px);
  box-shadow: 0 18px 40px rgba(0, 0, 0, .18);
}

.audio-player {
  flex: 1;
}

@media (max-width: 600px) {
  .galeria-card {
    padding: 20px;
    border-radius: 24px 24px 0 0;
  }

  .musica-item {
    flex-direction: column;
    align-items: flex-start;
  }

  .audio-player {
    width: 100%;
  }
}

</style>