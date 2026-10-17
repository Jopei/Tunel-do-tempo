<template>
  <TmFormDialog
    v-model="dialog"
    :titulo="atualizacao ? 'Editar atualização' : 'Nova atualização'"
    max-width="900"
    :bloqueado="loading"
    :sujo="sujo"
    @descartar="limpar"
  >
    <form id="form-atualizacao" novalidate @submit.prevent="salvar">
      <v-text-field v-model="titulo" label="Título *" :error-messages="erros.titulo" :disabled="loading" autocomplete="off" />
      <v-text-field
        v-model="linkMusica"
        label="Link da música (opcional)"
        type="url"
        inputmode="url"
        placeholder="https://"
        :error-messages="erros.link_musica"
        :disabled="loading"
      />
      <div class="editor-bloco" :class="{ invalido: erros.conteudo_markdown }">
        <label class="editor-label" @click="editor?.focus()">Conteúdo (Markdown) *</label>
        <div v-if="carregandoEditor" class="editor-carregando" role="status">Carregando editor...</div>
        <div ref="editorRef"></div>
        <p v-if="erros.conteudo_markdown" class="erro-campo" role="alert">{{ erros.conteudo_markdown }}</p>
      </div>
    </form>

    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="loading" @click="dialog = false">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-atualizacao" :loading="loading" :disabled="loading">
        {{ atualizacao ? "Salvar alterações" : "Publicar atualização" }}
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount } from "vue";
import { criarAtualizacao, atualizarAtualizacao } from "@/services/atualizacao.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { atualizacaoCriada } from "@/composables/useMidiaEventos";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  /** Quando informado, o modal edita esta atualização. */
  atualizacao: { type: Object, default: null },
});
const emit = defineEmits(["update:modelValue", "sucesso"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
});

const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const titulo = ref("");
const linkMusica = ref("");
const loading = ref(false);
const carregandoEditor = ref(false);
const erros = ref({});
const editorRef = ref(null);
let editor = null;
let inicial = { titulo: "", link: "", md: "" };

const sujo = computed(
  () =>
    titulo.value !== inicial.titulo ||
    linkMusica.value !== inicial.link ||
    (editor ? editor.getMarkdown() !== inicial.md : false),
);

// O editor (toast-ui) só é baixado quando o modal abre pela primeira vez.
async function garantirEditor() {
  if (editor) return;
  carregandoEditor.value = true;
  const [{ Editor }] = await Promise.all([
    import("@toast-ui/editor"),
    import("@toast-ui/editor/dist/toastui-editor.css"),
  ]);
  carregandoEditor.value = false;
  await nextTick();
  if (!editorRef.value) return;
  editor = new Editor({
    el: editorRef.value,
    height: "300px",
    initialEditType: "markdown",
    previewStyle: "vertical",
    placeholder: "Descreva as mudanças...",
  });
}

watch(
  () => props.modelValue,
  async (aberto) => {
    if (!aberto) return;
    erros.value = {};
    await nextTick();
    await garantirEditor();
    titulo.value = props.atualizacao?.titulo ?? "";
    linkMusica.value = props.atualizacao?.link_musica ?? "";
    editor?.setMarkdown(props.atualizacao?.conteudo_markdown ?? "");
    inicial = { titulo: titulo.value, link: linkMusica.value, md: editor?.getMarkdown() ?? "" };
  },
  { immediate: true },
);

function linkValido(v) {
  if (!v) return true;
  try {
    const u = new URL(v);
    return u.protocol === "https:" || u.protocol === "http:";
  } catch {
    return false;
  }
}

async function salvar() {
  erros.value = {};
  const md = editor?.getMarkdown() ?? "";
  if (!titulo.value.trim()) erros.value.titulo = "Informe um título.";
  if (!md.trim()) erros.value.conteudo_markdown = "Escreva o conteúdo da atualização.";
  if (!linkValido(linkMusica.value)) erros.value.link_musica = "Informe um link válido (https://...).";
  if (Object.keys(erros.value).length) {
    focarPrimeiroInvalido();
    return;
  }

  loading.value = true;
  const payload = {
    titulo: titulo.value.trim(),
    conteudo_markdown: md,
    link_musica: linkMusica.value || null,
  };
  try {
    if (props.atualizacao?.uuid) await atualizarAtualizacao(props.atualizacao.uuid, payload);
    else await criarAtualizacao(payload);
    notify.sucesso(props.atualizacao ? "Atualização salva." : "Atualização publicada.");
    atualizacaoCriada.value++;
    emit("sucesso");
    limpar();
    dialog.value = false;
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    loading.value = false;
  }
}

function limpar() {
  titulo.value = "";
  linkMusica.value = "";
  editor?.setMarkdown("");
  inicial = { titulo: "", link: "", md: "" };
}

onBeforeUnmount(() => {
  editor?.destroy?.();
  editor = null;
});
</script>

<style scoped>
.editor-bloco {
  border: 1px solid var(--tm-border);
  padding: var(--tm-space-3);
  border-radius: var(--tm-radius-md);
}
.editor-bloco.invalido {
  border-color: rgb(var(--v-theme-error));
}
.editor-label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--tm-space-2);
}
.editor-carregando {
  padding: var(--tm-space-5);
  text-align: center;
  color: var(--tm-ink-muted);
}
.erro-campo {
  margin: var(--tm-space-2) 0 0;
  color: rgb(var(--v-theme-error));
  font-size: var(--tm-fs-sm);
}
</style>
