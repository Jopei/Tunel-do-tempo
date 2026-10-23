<template>
  <TmFormDialog v-model="dialog" :titulo="titulo" max-width="440" :bloqueado="salvando">
    <form :id="idForm" novalidate @submit.prevent="enviar">
      <v-text-field
        v-model="nome"
        :label="rotulo"
        maxlength="255"
        :error-messages="erro"
        :disabled="salvando"
        autocomplete="off"
        autofocus
        @focus="$event.target.select?.()"
      />
    </form>
    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="salvando" @click="dialog = false">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" type="submit" :form="idForm" :loading="salvando">{{ rotuloAcao }}</v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const idForm = `form-nome-${Math.random().toString(36).slice(2, 7)}`;

const props = defineProps({
  modelValue: Boolean,
  titulo: { type: String, default: "Nome" },
  rotulo: { type: String, default: "Nome" },
  rotuloAcao: { type: String, default: "Salvar" },
  valor: { type: String, default: "" },
  salvando: Boolean,
  /** Mensagem de erro vinda do servidor (422). */
  erro: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue", "salvar"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const nome = ref("");
const erroLocal = ref("");
const erro = computed(() => erroLocal.value || props.erro);

watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      nome.value = props.valor;
      erroLocal.value = "";
    }
  },
);
watch(nome, () => (erroLocal.value = ""));

function enviar() {
  const limpo = nome.value.trim();
  if (!limpo) {
    erroLocal.value = "Informe um nome.";
    return;
  }
  emit("salvar", limpo);
}
</script>
