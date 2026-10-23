<template>
  <TmFormDialog v-model="dialog" :titulo="titulo" max-width="1000" :bloqueado="enviando">
    <p class="dica">Toque nas fotos para marcar. {{ sel.quantidade.value }} {{ sel.quantidade.value === 1 ? "marcada" : "marcadas" }}.</p>
    <TmErrorState v-if="lista.erro.value && !lista.itens.value.length" texto="Não conseguimos carregar as fotos." @retry="lista.recarregar()" />
    <p v-else-if="lista.vazio.value" class="dica">Nenhuma foto na biblioteca.</p>
    <FotosGrade
      v-else
      :grupos="grupos"
      :selecionados="sel.marcados.value"
      selecao-ativa
      sem-cabecalho
      :escala="0.7"
      rotulo-grade="Escolher fotos"
      @abrir="sel.alternar"
      @alternar="sel.alternar"
      @intervalo="sel.intervalo"
    />
    <div v-if="lista.carregando.value" class="dica" role="status">Carregando...</div>
    <div v-else-if="lista.temMais.value" class="text-center">
      <v-btn variant="tonal" @click="lista.carregarProxima()">Carregar mais</v-btn>
    </div>
    <div :ref="lista.sentinela" aria-hidden="true"></div>

    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="enviando" @click="dialog = false">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" :disabled="!sel.quantidade.value" :loading="enviando" @click="confirmar">
        Adicionar {{ sel.quantidade.value || "" }}
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { computed, watch, ref } from "vue";
import { listarFotos } from "@/services/foto.service";
import { adicionarFotosAlbum } from "@/services/album.service";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useSelecao } from "@/composables/useSelecao";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import FotosGrade from "./FotosGrade.vue";

const props = defineProps({
  modelValue: Boolean,
  albumUuid: { type: String, required: true },
  titulo: { type: String, default: "Adicionar fotos ao álbum" },
});
const emit = defineEmits(["update:modelValue", "adicionado"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const { tratar } = useApiError();
const notify = useNotify();
const enviando = ref(false);

const lista = useListaPaginada((p) => listarFotos(p, { per_page: 60, agrupar: "data" }));
const grupos = computed(() => [{ chave: "todas", mes: "", rotulo: "", itens: lista.itens.value }]);
const uuids = computed(() => lista.itens.value.map((f) => f.uuid));
const sel = useSelecao(uuids);

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return;
    sel.limpar();
    lista.recarregar();
  },
);

async function confirmar() {
  enviando.value = true;
  try {
    await adicionarFotosAlbum(props.albumUuid, sel.lista.value);
    const n = sel.quantidade.value;
    notify.sucesso(`${n} ${n === 1 ? "foto adicionada" : "fotos adicionadas"}.`);
    emit("adicionado");
    dialog.value = false;
  } catch (e) {
    tratar(e);
  } finally {
    enviando.value = false;
  }
}
</script>

<style scoped>
.dica {
  color: var(--tm-ink-muted);
  margin: 0 0 12px;
}
</style>
