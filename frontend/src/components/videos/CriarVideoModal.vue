<template>
  <TmFormDialog
    v-model="dialog"
    titulo="Adicionar vídeo"
    max-width="640"
    :bloqueado="loading"
    :sujo="sujo"
    @descartar="limpar"
  >
    <form id="form-video" novalidate @submit.prevent="salvar">
      <div v-if="preview" class="preview-box">
        <video :src="preview" class="preview" controls muted preload="metadata" :aria-label="`Pré-visualização de ${video?.name}`" />
        <v-btn
          icon
          size="small"
          variant="flat"
          color="surface"
          class="remover"
          :aria-label="`Remover ${video?.name}`"
          :disabled="loading"
          @click="removerVideo"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <p class="arquivo">{{ video?.name }} · {{ formatarTamanho(video?.size || 0) }}</p>
      </div>
      <TmDropzone
        v-else
        accept="video/mp4,video/quicktime,video/x-msvideo"
        :max-mb="TAMANHO_MAX_MB"
        icone="mdi-video-outline"
        rotulo="Arraste o vídeo para cá (MP4, MOV ou AVI)"
        :erro-externo="erros.video || ''"
        @arquivos="escolher"
      />

      <v-text-field v-model="titulo" label="Título" class="mt-4" :error-messages="erros.titulo" :disabled="loading" autocomplete="off" />
      <v-text-field
        v-model="dataVideo"
        type="date"
        label="Data do vídeo (opcional)"
        :max="hojeLocal()"
        :error-messages="erros.data_video"
        :disabled="loading"
        hint="Quando foi gravado. Ajuda a montar a estante por ano."
        persistent-hint
        class="mb-2"
      />
      <v-textarea v-model="descricao" label="Descrição" rows="2" auto-grow :error-messages="erros.descricao" :disabled="loading" />

      <div v-if="loading" class="progresso" role="status" aria-live="polite">
        <v-progress-linear :model-value="progresso" color="primary" height="8" rounded />
        <span>Enviando {{ progresso }}% — não feche esta página.</span>
      </div>
    </form>

    <template #acoes>
      <v-btn v-if="loading" variant="text" color="on-surface" @click="cancelar">Cancelar envio</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-video" :loading="loading" :disabled="loading">
        Adicionar vídeo
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import { cadastrarVideo } from "@/services/video.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { videoCriado } from "@/composables/useMidiaEventos";
import { formatarTamanho } from "@/composables/useArquivos";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmDropzone from "@/components/ui/TmDropzone.vue";

// Mesmo limite do servidor.
const TAMANHO_MAX_MB = 50;

const props = defineProps({ modelValue: Boolean });
const emit = defineEmits(["update:modelValue", "sucesso"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const titulo = ref("");
const descricao = ref("");
const dataVideo = ref("");
function hojeLocal() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}
const video = ref(null);
const preview = ref(null);
const loading = ref(false);
const progresso = ref(0);
const erros = ref({});
let controller = null;

const sujo = computed(() => !!(titulo.value || descricao.value || dataVideo.value || video.value));

function escolher([arquivo]) {
  removerVideo();
  video.value = arquivo;
  preview.value = URL.createObjectURL(arquivo);
  erros.value = {};
}

function removerVideo() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = null;
  video.value = null;
}

function cancelar() {
  controller?.abort();
}

async function salvar() {
  erros.value = {};
  if (!video.value) {
    erros.value = { video: "Selecione um vídeo." };
    return;
  }

  const form = new FormData();
  form.append("titulo", titulo.value);
  form.append("descricao", descricao.value);
  if (dataVideo.value) form.append("data_video", dataVideo.value);
  form.append("video", video.value);

  loading.value = true;
  progresso.value = 0;
  controller = new AbortController();

  try {
    await cadastrarVideo(form, {
      signal: controller.signal,
      onUploadProgress: (e) => {
        if (e.total) progresso.value = Math.round((e.loaded / e.total) * 100);
      },
    });
    notify.sucesso("Vídeo adicionado.");
    videoCriado.value++;
    emit("sucesso");
    limpar();
    dialog.value = false;
  } catch (e) {
    if (e?.code === "ERR_CANCELED") {
      notify.info("Envio cancelado.");
    } else {
      const info = tratar(e);
      erros.value = info.campos;
      if (Object.keys(info.campos).length) focarPrimeiroInvalido();
    }
  } finally {
    loading.value = false;
    controller = null;
  }
}

function limpar() {
  titulo.value = "";
  descricao.value = "";
  dataVideo.value = "";
  erros.value = {};
  removerVideo();
}

onBeforeUnmount(() => {
  controller?.abort();
  removerVideo();
});
</script>

<style scoped>
.preview-box {
  position: relative;
  text-align: center;
}
.preview {
  width: 100%;
  max-height: 280px;
  border-radius: var(--tm-radius-md);
  background: #000;
}
.remover {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 44px;
  height: 44px;
}
.arquivo {
  margin: var(--tm-space-2) 0 0;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.progresso {
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-2);
  font-size: var(--tm-fs-sm);
}
</style>
