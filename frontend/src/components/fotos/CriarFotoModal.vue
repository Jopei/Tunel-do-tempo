<template>
  <TmFormDialog
    v-model="dialog"
    titulo="Adicionar foto"
    max-width="640"
    :bloqueado="loading"
    :sujo="sujo"
    @descartar="limpar"
  >
    <form id="form-foto" novalidate @submit.prevent="salvar">
      <div v-if="preview" class="preview-box">
        <img :src="preview" :alt="`Pré-visualização de ${foto?.name}`" class="preview" />
        <v-btn
          icon
          size="small"
          variant="flat"
          color="surface"
          class="remover"
          :aria-label="`Remover ${foto?.name}`"
          :disabled="loading"
          @click="removerFoto"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>
        <p class="arquivo">{{ foto?.name }} · {{ formatarTamanho(foto?.size || 0) }}</p>
      </div>
      <TmDropzone v-else accept="image/jpeg,image/png" :max-mb="40" rotulo="Arraste a foto para cá" :erro-externo="erros.imagem || ''" @arquivos="escolher" />

      <v-text-field v-model="titulo" label="Título" class="mt-4" :error-messages="erros.titulo" :disabled="loading" autocomplete="off" />
      <v-textarea v-model="descricao" label="Descrição" rows="2" auto-grow :error-messages="erros.descricao" :disabled="loading" />
      <v-text-field
        v-model="dataFoto"
        label="Data da foto"
        type="date"
        :max="hoje"
        :error-messages="erros.criado_em"
        :disabled="loading"
      />

      <div v-if="loading" class="progresso" role="status" aria-live="polite">
        <v-progress-linear :model-value="progresso" color="primary" height="8" rounded />
        <span>Enviando {{ progresso }}% — não feche esta página.</span>
      </div>
    </form>

    <template #acoes>
      <v-btn v-if="loading" variant="text" color="on-surface" @click="cancelar">Cancelar envio</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-foto" :loading="loading" :disabled="loading">
        Adicionar foto
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import { cadastrarFoto } from "@/services/foto.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { fotoCriada } from "@/composables/useMidiaEventos";
import { formatarTamanho, comprimirImagem } from "@/composables/useArquivos";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmDropzone from "@/components/ui/TmDropzone.vue";

// Tipo de imagem "Galeria" no backend (valor fixo da API atual).
const TIPO_IMAGEM_GALERIA = 3;
const LIMITE_FOTO_MB = 4;

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
const foto = ref(null);
const preview = ref(null);
const dataFoto = ref("");
const loading = ref(false);
const progresso = ref(0);
const erros = ref({});
const hoje = new Date().toISOString().slice(0, 10);
let controller = null;

const sujo = computed(() => !!(titulo.value || descricao.value || foto.value || dataFoto.value));

function escolher([arquivo]) {
  removerFoto();
  foto.value = arquivo;
  preview.value = URL.createObjectURL(arquivo);
  erros.value = {};
}

function removerFoto() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = null;
  foto.value = null;
}

function cancelar() {
  controller?.abort();
}

async function salvar() {
  erros.value = {};
  if (!foto.value) {
    erros.value = { imagem: "Selecione uma foto." };
    return;
  }

  loading.value = true;
  // Reduz sempre que passar do limite do servidor ou não for JPEG/PNG.
  const arquivo = await comprimirImagem(foto.value, { limiarMb: 0, max: 2048, qualidadeInicial: 0.85, alvoMb: LIMITE_FOTO_MB });
  if (arquivo.size > LIMITE_FOTO_MB * 1024 * 1024 || !["image/jpeg", "image/png"].includes(arquivo.type)) {
    erros.value = { imagem: `A foto continua com ${formatarTamanho(arquivo.size)} depois de reduzida. O limite é ${LIMITE_FOTO_MB} MB; escolha outra imagem.` };
    loading.value = false;
    return;
  }
  const form = new FormData();
  form.append("titulo", titulo.value);
  form.append("descricao", descricao.value);
  form.append("tipo_imagem_id", TIPO_IMAGEM_GALERIA);
  form.append("imagem", arquivo);
  if (dataFoto.value) form.append("criado_em", dataFoto.value + " 00:00:00");

  progresso.value = 0;
  controller = new AbortController();

  try {
    await cadastrarFoto(form, {
      signal: controller.signal,
      onUploadProgress: (e) => {
        if (e.total) progresso.value = Math.round((e.loaded / e.total) * 100);
      },
    });
    notify.sucesso("Foto adicionada.");
    fotoCriada.value++;
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
  dataFoto.value = "";
  erros.value = {};
  removerFoto();
}

onBeforeUnmount(() => {
  controller?.abort();
  removerFoto();
});
</script>

<style scoped>
.preview-box {
  position: relative;
  text-align: center;
}
.preview {
  max-width: 100%;
  max-height: 280px;
  border-radius: var(--tm-radius-md);
  object-fit: contain;
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
