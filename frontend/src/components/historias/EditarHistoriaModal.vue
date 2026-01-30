<template>
  <v-dialog v-model="aberto" max-width="640" :persistent="loading">
    <v-card class="modal-card">
      <v-card-title class="modal-title">
        Editar História
      </v-card-title>

      <v-card-text class="modal-body">
        <v-text-field
          v-model="form.titulo"
          label="Título"
          variant="outlined"
          density="comfortable"
          :disabled="loading"
        />

        <v-textarea
          v-model="form.descricao_curta"
          label="Descrição curta"
          variant="outlined"
          rows="2"
          :disabled="loading"
        />

        <v-select
          v-model="form.tipo_historia"
          label="Tipo da História"
          :items="tipos"
          variant="outlined"
          :disabled="loading"
        />

        <div class="usuarios">
          <span class="usuarios-label">Usuários</span>

          <v-checkbox
            v-for="u in usuarios"
            :key="u.uuid"
            v-model="form.usuarios"
            :label="u.nome"
            :value="u.uuid"
            density="compact"
            :disabled="loading"
          />
        </div>
      </v-card-text>

      <v-card-actions class="modal-actions">
        <v-btn
          variant="text"
          @click="fechar"
          :disabled="loading"
        >
          Cancelar
        </v-btn>

        <v-btn
          color="primary"
          :loading="loading"
          :disabled="loading"
          @click="confirmar"
        >
          Salvar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from "vue";
import { atualizarHistoria } from "@/services/historia.service";

const props = defineProps({
  modelValue: Boolean,
  historia: Object,
});

const emit = defineEmits(["update:modelValue", "confirmar"]);

const aberto = ref(false);
const loading = ref(false);

const form = ref({
  titulo: "",
  descricao_curta: "",
  tipo_historia: "",
  usuarios: [],
});

const tipos = ["Pessoal", "Principal", "Paralela"];
const usuarios = ref([]);

watch(
  () => props.modelValue,
  v => (aberto.value = v)
);

watch(
  () => props.historia,
  historia => {
    if (!historia) return;

    form.value = {
      titulo: historia.titulo,
      descricao_curta: historia.descricao_curta ?? "",
      tipo_historia: historia.tipo_historia,
      usuarios: historia.usuarios.map(u => u.uuid),
    };

    usuarios.value = historia.usuarios;
  },
  { immediate: true }
);

function fechar() {
  if (loading.value) return;
  emit("update:modelValue", false);
}

async function confirmar() {
  if (!props.historia?.uuid) return;

  loading.value = true;

  try {
    await atualizarHistoria(props.historia.uuid, {
      titulo: form.value.titulo,
      descricao_curta: form.value.descricao_curta,
      tipo_historia: form.value.tipo_historia,
      usuarios: form.value.usuarios,
    });

    emit("update:modelValue", false);
    emit("confirmar");
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal-card {
  background: #fbf6e6;
  border-radius: 28px;
}

.modal-title {
  font-weight: 700;
  padding-bottom: 0;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.usuarios {
  margin-top: 8px;
}

.usuarios-label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
}

.modal-actions {
  justify-content: flex-end;
  gap: 12px;
}
</style>
