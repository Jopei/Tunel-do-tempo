<template>
  <TmFormDialog v-model="dialog" :titulo="album ? 'Editar álbum' : 'Novo álbum'" max-width="520" :bloqueado="salvando">
    <form id="form-album" novalidate @submit.prevent="salvar">
      <v-text-field
        ref="campoTitulo"
        v-model="titulo"
        label="Título"
        counter="80"
        maxlength="80"
        :error-messages="erros.titulo"
        :disabled="salvando"
        autocomplete="off"
        autofocus
      />
      <v-textarea
        v-model="descricao"
        label="Descrição (opcional)"
        counter="300"
        maxlength="300"
        rows="3"
        auto-grow
        :error-messages="erros.descricao"
        :disabled="salvando"
      />
    </form>
    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="salvando" @click="dialog = false">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-album" :loading="salvando">
        {{ album ? "Salvar" : "Criar álbum" }}
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { criarAlbum, atualizarAlbum } from "@/services/album.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  /** Álbum existente (edição); null = novo. */
  album: { type: Object, default: null },
  /** Fotos para já incluir ao criar. */
  fotos: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "salvo"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const titulo = ref("");
const descricao = ref("");
const erros = ref({});
const salvando = ref(false);

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return;
    titulo.value = props.album?.titulo ?? "";
    descricao.value = props.album?.descricao ?? "";
    erros.value = {};
  },
);

async function salvar() {
  erros.value = {};
  if (!titulo.value.trim()) {
    erros.value = { titulo: "Dê um título ao álbum." };
    focarPrimeiroInvalido();
    return;
  }
  salvando.value = true;
  try {
    const payload = { titulo: titulo.value.trim(), descricao: descricao.value.trim() || null };
    const salvo = props.album
      ? await atualizarAlbum(props.album.uuid, payload)
      : await criarAlbum({ ...payload, ...(props.fotos.length ? { fotos: props.fotos } : {}) });
    notify.sucesso(props.album ? "Álbum atualizado." : "Álbum criado.");
    emit("salvo", salvo);
    dialog.value = false;
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    salvando.value = false;
  }
}
</script>
