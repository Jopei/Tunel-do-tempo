<template>
  <TmFormDialog
    :model-value="modelValue"
    :titulo="tipo === 'foto' ? 'Editar foto' : 'Editar vídeo'"
    max-width="560"
    :bloqueado="salvando"
    :sujo="sujo"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <form id="form-editar-midia" novalidate @submit.prevent="emit('salvar', { ...form })">
      <v-text-field v-model="form.titulo" label="Título" :error-messages="erros.titulo" :disabled="salvando" autocomplete="off" />
      <v-textarea v-model="form.descricao" label="Descrição" rows="3" auto-grow :error-messages="erros.descricao" :disabled="salvando" />
    </form>
    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="salvando" @click="emit('update:modelValue', false)">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-editar-midia" :loading="salvando" :disabled="salvando">
        Salvar
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  item: { type: Object, default: null },
  tipo: { type: String, default: "foto" },
  salvando: { type: Boolean, default: false },
  erros: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:modelValue", "salvar"]);

const form = ref({ titulo: "", descricao: "" });
const inicial = ref("");
const sujo = computed(() => JSON.stringify(form.value) !== inicial.value);

watch(
  () => [props.modelValue, props.item],
  ([aberto, item]) => {
    if (!aberto || !item) return;
    form.value = { titulo: item.titulo || "", descricao: item.descricao || "" };
    inicial.value = JSON.stringify(form.value);
  },
  { immediate: true },
);
</script>
