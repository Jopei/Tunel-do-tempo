<template>
  <v-dialog v-model="dialog" max-width="1200">
    <v-card class="galeria-card">
      <v-card-title class="titulo">
        Galeria de Vídeos
      </v-card-title>

      <v-card-text>

        <!-- GRID -->
        <div v-if="!videosCarregados.length" class="sem-videos">
          Nenhum vídeo disponível.
        </div>

        <div v-else class="grid-videos">
          <div
            v-for="video in videosCarregados"
            :key="video.uuid"
            class="video-item"
            @click="selecionarVideo(video)"
          >
            <div class="thumb">
              <v-icon size="56">
                mdi-play-circle
              </v-icon>
            </div>
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

  <!-- PLAYER FULLSCREEN -->
  <v-dialog
    v-model="videoSelecionado"
    fullscreen
    transition="dialog-bottom-transition"
  >
    <v-card class="viewer-card">
      <v-icon
        class="fechar-viewer"
        @click="videoSelecionado = null"
      >
        mdi-close
      </v-icon>

      <video
        v-if="videoSelecionado"
        :src="videoSelecionado.url"
        controls
        autoplay
        class="viewer-video"
      />
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { buscarVideoPorUuid } from "@/services/video.show.service";

const props = defineProps({
  videos: {
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

const videosCarregados = ref([]);
const videoSelecionado = ref(null);

watch(
  () => dialog.value,
  async (aberto) => {
    if (aberto && props.videos.length) {
      videosCarregados.value = [];
      videoSelecionado.value = null;

      for (const uuid of props.videos) {
        try {
          const url = await buscarVideoPorUuid(uuid);
          videosCarregados.value.push({ uuid, url });
        } catch (e) {
          console.error("Erro ao carregar vídeo:", uuid);
        }
      }
    }
  }
);

function selecionarVideo(video) {
  videoSelecionado.value = video;
}
</script>

<style scoped>

/* CARD PADRÃO PROJETO */
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

.sem-videos {
  text-align: center;
  font-weight: 700;
  padding: 40px;
  color: #777;
}

/* GRID */
.grid-videos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 28px;
}

/* VIDEO ITEM */
.video-item {
  background: rgba(199, 164, 58, 0.12);
  border: 2px solid rgba(199, 164, 58, 0.35);
  border-radius: 22px;
  padding: 14px;
  cursor: pointer;
  transition: all .25s ease;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-item:hover {
  background: rgba(199, 164, 58, 0.22);
  border-color: #c7a43a;
  transform: translateY(-8px);
  box-shadow: 0 18px 40px rgba(0, 0, 0, .18);
}

.thumb {
  width: 100%;
  height: 100%;
  border-radius: 16px;
  background: linear-gradient(135deg, #c7a43a, #b89630);
  display: flex;
  align-items: center;
  justify-content: center;
}

.thumb .v-icon {
  color: #fff;
  transition: transform .25s ease;
}

.video-item:hover .v-icon {
  transform: scale(1.2);
}

/* ========================= */
/* VIEWER FULLSCREEN */
/* ========================= */

.viewer-card {
  background: rgba(0, 0, 0, 0.96);
  height: 100vh;
  width: 100vw;

  display: flex;
  justify-content: center;
  align-items: center;

  position: relative;
}

.viewer-video {
  max-width: 90vw;
  max-height: 90vh;
  border-radius: 18px;
  box-shadow: 0 40px 100px rgba(0,0,0,.7);
  background: #000;
}

.fechar-viewer {
  position: absolute;
  top: 30px;
  right: 40px;

  font-size: 34px;
  color: #fff;

  cursor: pointer;
  transition: transform .2s ease, opacity .2s ease;
}

.fechar-viewer:hover {
  transform: scale(1.2);
  opacity: .8;
}

/* RESPONSIVO */

@media (max-width: 600px) {

  .galeria-card {
    padding: 20px;
    border-radius: 24px 24px 0 0;
  }

  .grid-videos {
    grid-template-columns: 1fr;
  }

  .viewer-video {
    max-width: 95vw;
    max-height: 75vh;
  }

  .fechar-viewer {
    top: 18px;
    right: 18px;
    font-size: 28px;
  }

}

</style>