<template>
  <div class="avatar-upload">
    <button type="button" class="alvo" :disabled="disabled" :aria-label="rotulo" @click="input?.click()">
      <img v-if="preview || src" :src="preview || src" :alt="`Foto de ${nome || 'perfil'}`" />
      <span v-else class="iniciais" aria-hidden="true">{{ iniciais(nome) || "?" }}</span>
      <span class="camera" aria-hidden="true"><v-icon size="20">mdi-camera-outline</v-icon></span>
    </button>
    <input ref="input" type="file" class="sr-only" tabindex="-1" accept="image/*" aria-label="Escolher foto" @change="escolher" />
    <p v-if="erro" class="erro" role="alert">{{ erro }}</p>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount } from "vue";
import { iniciais } from "@/composables/useFormat";
import { validarArquivos } from "@/composables/useArquivos";

const props = defineProps({
  nome: { type: String, default: "" },
  src: { type: String, default: null },
  rotulo: { type: String, default: "Escolher foto de perfil" },
  maxMb: { type: Number, default: 5 },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(["arquivo"]);

const input = ref(null);
const preview = ref(null);
const erro = ref("");

function escolher(e) {
  const { validos, mensagens } = validarArquivos(e.target.files, { accept: "image/*", maxMb: props.maxMb });
  e.target.value = "";
  erro.value = mensagens.join(" ");
  if (!validos.length) return;
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = URL.createObjectURL(validos[0]);
  emit("arquivo", validos[0]);
}

function limparPreview() {
  if (preview.value) URL.revokeObjectURL(preview.value);
  preview.value = null;
}
defineExpose({ limparPreview });

onBeforeUnmount(limparPreview);
</script>

<style scoped>
.avatar-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-2);
}
.alvo {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 3px solid var(--tm-gold);
  padding: 0;
  background: rgb(var(--v-theme-surface-variant));
  cursor: pointer;
  overflow: visible;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgb(var(--v-theme-on-surface));
}
.alvo img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}
.iniciais {
  font-family: var(--tm-font-title);
  font-size: 2rem;
  font-weight: 700;
}
.camera {
  position: absolute;
  right: -4px;
  bottom: -4px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--tm-gold);
  color: #2a2112;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgb(var(--v-theme-surface));
}
.erro {
  margin: 0;
  color: rgb(var(--v-theme-error));
  font-size: var(--tm-fs-sm);
  text-align: center;
}
</style>
