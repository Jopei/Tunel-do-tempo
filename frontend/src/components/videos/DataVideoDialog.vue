<template>
  <TmFormDialog v-model="dialog" :titulo="titulo" max-width="440" :bloqueado="salvando">
    <form :id="idForm" novalidate @submit.prevent="enviar">
      <p v-if="texto" class="dv-texto">{{ texto }}</p>
      <v-text-field
        v-model="data"
        type="date"
        label="Data do vídeo"
        :max="hojeLocal()"
        :error-messages="erro || erroLocal"
        :disabled="salvando"
        hint="Quando foi gravado. Ajuda a montar a estante por ano."
        persistent-hint
        autofocus
      />
    </form>
    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="salvando" @click="dialog = false">Cancelar</v-btn>
      <v-btn v-if="modo === 'envio'" variant="text" :disabled="salvando" @click="$emit('salvar', null)">Enviar sem data</v-btn>
      <v-btn v-else-if="valor" variant="text" :disabled="salvando" @click="$emit('salvar', null)">Remover data</v-btn>
      <v-btn variant="flat" color="primary" type="submit" :form="idForm" :loading="salvando">{{ modo === "envio" ? "Enviar" : "Salvar" }}</v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const idForm = `form-data-video-${Math.random().toString(36).slice(2, 7)}`;
function hojeLocal() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

const props = defineProps({
  modelValue: Boolean,
  titulo: { type: String, default: "Data do vídeo" },
  texto: { type: String, default: "" },
  modo: { type: String, default: "edicao" }, // edicao | envio
  valor: { type: String, default: "" },
  salvando: Boolean,
  erro: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue", "salvar"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const data = ref("");
const erroLocal = ref("");

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      data.value = props.valor || "";
      erroLocal.value = "";
    }
  },
);

function enviar() {
  if (!data.value) {
    if (props.modo === "envio") return emit("salvar", null);
    erroLocal.value = "Informe a data.";
    return;
  }
  if (data.value > hojeLocal()) {
    erroLocal.value = "A data não pode ser no futuro.";
    return;
  }
  emit("salvar", data.value);
}
</script>

<style scoped>
.dv-texto {
  margin: 0 0 12px;
  color: var(--tm-ink-muted);
}
</style>
