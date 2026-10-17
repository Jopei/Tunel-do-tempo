<template>
  <div
    class="dropzone"
    :class="{ sobre, invalido: !!erro }"
    @dragover.prevent="sobre = true"
    @dragleave.prevent="sobre = false"
    @drop.prevent="soltar"
  >
    <v-icon size="36" aria-hidden="true">{{ icone }}</v-icon>
    <p class="texto">{{ rotulo }}</p>
    <p class="dica">{{ dica }}</p>
    <v-btn variant="tonal" color="primary-text" class="mt-2" prepend-icon="mdi-paperclip" @click="input?.click()">
      {{ multiple ? "Escolher arquivos" : "Escolher arquivo" }}
    </v-btn>
    <input
      ref="input"
      type="file"
      class="sr-only"
      tabindex="-1"
      :accept="accept"
      :multiple="multiple"
      :aria-label="rotulo"
      @change="escolher"
    />
    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { validarArquivos } from "@/composables/useArquivos";

const props = defineProps({
  accept: { type: String, default: "image/*" },
  multiple: { type: Boolean, default: false },
  maxMb: { type: Number, default: 10 },
  rotulo: { type: String, default: "Arraste os arquivos para cá" },
  icone: { type: String, default: "mdi-cloud-upload-outline" },
  erroExterno: { type: String, default: "" },
});
const emit = defineEmits(["arquivos"]);

const input = ref(null);
const sobre = ref(false);
const erroLocal = ref("");
const erro = computed(() => erroLocal.value || props.erroExterno);
const dica = computed(() => `Até ${props.maxMb} MB por arquivo.`);

function processar(lista) {
  const { validos, mensagens } = validarArquivos(lista, { accept: props.accept, maxMb: props.maxMb });
  erroLocal.value = mensagens.join(" ");
  if (validos.length) emit("arquivos", props.multiple ? validos : validos.slice(0, 1));
}

function escolher(e) {
  processar(e.target.files);
  e.target.value = "";
}
function soltar(e) {
  sobre.value = false;
  processar(e.dataTransfer?.files);
}
</script>

<style scoped>
.dropzone {
  border: 2px dashed var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-5);
  text-align: center;
  background: rgb(var(--v-theme-surface-variant));
  transition: border-color var(--tm-dur-fast) var(--tm-ease), background var(--tm-dur-fast) var(--tm-ease);
}
.dropzone.sobre {
  border-color: var(--tm-gold);
  background: rgba(199, 164, 58, 0.14);
}
.dropzone.invalido {
  border-color: rgb(var(--v-theme-error));
}
.texto {
  margin: var(--tm-space-2) 0 0;
  font-weight: 600;
}
.dica {
  margin: 0;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.erro {
  margin: var(--tm-space-2) 0 0;
  color: rgb(var(--v-theme-error));
  font-size: var(--tm-fs-sm);
}
</style>
