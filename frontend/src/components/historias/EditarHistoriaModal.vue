<template>
  <TmFormDialog
    :model-value="modelValue"
    titulo="Editar história"
    max-width="640"
    :bloqueado="loading"
    :sujo="sujo"
    @update:model-value="(v) => emit('update:modelValue', v)"
  >
    <form id="form-editar-historia" novalidate @submit.prevent="confirmar">
      <v-text-field v-model="form.titulo" label="Título" :error-messages="erros.titulo" :disabled="loading" required />
      <v-textarea
        v-model="form.descricao_curta"
        label="Descrição curta"
        rows="2"
        auto-grow
        :error-messages="erros.descricao_curta"
        :disabled="loading"
      />
      <v-select
        v-model="form.tipo_historia"
        label="Tipo da história"
        :items="tipos"
        :error-messages="erros.tipo_historia"
        :disabled="loading"
      />
      <v-autocomplete
        v-model="form.usuarios"
        label="Membros da história"
        :items="usuarios"
        item-title="nome"
        item-value="uuid"
        multiple
        chips
        closable-chips
        :loading="carregandoUsuarios"
        :error-messages="erros.usuarios"
        :disabled="loading"
        no-data-text="Nenhum membro encontrado"
      />
      <p class="aviso">Para editar o texto da história, abra a história e use a página de criação.</p>
    </form>

    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="loading" @click="emit('update:modelValue', false)">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-editar-historia" :loading="loading" :disabled="loading">
        Salvar
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { atualizarHistoria } from "@/services/historia.service";
import { listarUsuarios } from "@/services/usuario.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  historia: { type: Object, default: null },
});
const emit = defineEmits(["update:modelValue", "confirmar"]);

const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const loading = ref(false);
const carregandoUsuarios = ref(false);
const erros = ref({});
const inicial = ref("");
const usuarios = ref([]);
const tipos = ["Pessoal", "Principal", "Paralela"];
const form = ref({ titulo: "", descricao_curta: "", tipo_historia: "", usuarios: [] });

const sujo = computed(() => JSON.stringify(form.value) !== inicial.value);

async function carregarUsuarios() {
  if (usuarios.value.length) return;
  carregandoUsuarios.value = true;
  try {
    const r = await listarUsuarios();
    usuarios.value = r.data ?? r;
  } catch {
    // Fallback: ao menos os membros atuais da história.
    usuarios.value = props.historia?.usuarios ?? [];
  } finally {
    carregandoUsuarios.value = false;
  }
}

watch(
  () => [props.modelValue, props.historia],
  ([aberto, historia]) => {
    if (!aberto || !historia) return;
    erros.value = {};
    form.value = {
      titulo: historia.titulo,
      descricao_curta: historia.descricao_curta ?? "",
      tipo_historia: historia.tipo_historia,
      usuarios: (historia.usuarios ?? []).map((u) => u.uuid),
    };
    inicial.value = JSON.stringify(form.value);
    carregarUsuarios();
  },
  { immediate: true },
);

async function confirmar() {
  if (!props.historia?.uuid) return;
  loading.value = true;
  erros.value = {};
  try {
    await atualizarHistoria(props.historia.uuid, { ...form.value });
    notify.sucesso("História atualizada.");
    emit("update:modelValue", false);
    emit("confirmar");
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.aviso {
  margin: 0;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
</style>
