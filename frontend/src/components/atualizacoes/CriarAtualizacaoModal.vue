<template>
  <v-dialog v-model="dialog" max-width="900">
    <v-card class="card">

      <v-icon class="fechar" @click="fechar">
        mdi-close
      </v-icon>

      <h1>Nova Atualização</h1>

      <div v-if="sucesso" class="sucesso-msg">
        Atualização publicada 🚀
      </div>

      <div class="field">
        <label>Título</label>
        <input v-model="titulo" />
      </div>

      <div class="field">
        <label>Link da Música</label>
        <input v-model="linkMusica" />
      </div>

      <!-- EDITOR MARKDOWN -->
      <div class="field destaque">
        <label>Conteúdo (Markdown)</label>
        <div ref="editorRef"></div>
      </div>

      <div class="botao-container">
        <button @click="salvar" :disabled="loading">
          <span v-if="loading">Publicando...</span>
          <span v-else>Publicar Atualização</span>
        </button>
      </div>

    </v-card>
  </v-dialog>
</template>
<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { Editor } from "@toast-ui/editor";
import "@toast-ui/editor/dist/toastui-editor.css";
import { criarAtualizacao } from "@/services/atualizacao.service";

const props = defineProps({
  modelValue: Boolean
});

const emit = defineEmits(["update:modelValue", "sucesso"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val)
});

const titulo = ref("");
const linkMusica = ref("");
const loading = ref(false);
const sucesso = ref(false);

const editorRef = ref(null);
let editor = null;

/* 🔥 INICIALIZA SOMENTE QUANDO ABRIR */
watch(dialog, async (val) => {
  if (val) {
    await nextTick();

    if (!editor) {
      editor = new Editor({
        el: editorRef.value,
        height: "300px",
        initialEditType: "markdown",
        previewStyle: "vertical",
        placeholder: "Descreva as mudanças..."
      });
    }
  }
});

async function salvar() {
  loading.value = true;
  sucesso.value = false;

  try {
    await criarAtualizacao({
      titulo: titulo.value,
      conteudo_markdown: editor.getMarkdown(),
      link_musica: linkMusica.value
    });

    sucesso.value = true;
    emit("sucesso");

    setTimeout(() => {
      fechar();
      limpar();
    }, 800);

  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function limpar() {
  titulo.value = "";
  linkMusica.value = "";
  if (editor) editor.setMarkdown("");
}

function fechar() {
  dialog.value = false;
}
</script>
<style scoped>

.card {
  position: relative;
  background: #fbf6e6;
  padding: 50px;
  border-radius: 32px;
  box-shadow: 0 30px 80px rgba(0,0,0,.35);
}

h1 {
  text-align: center;
  margin-bottom: 30px;
}

.fechar {
  position: absolute;
  top: 20px;
  right: 20px;
  cursor: pointer;
}

.field {
  margin-bottom: 24px;
}

.field label {
  font-weight: 600;
  margin-bottom: 8px;
  display: block;
}

input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #ccc;
}

.destaque {
  border: 2px solid #c7a43a;
  padding: 18px;
  border-radius: 18px;
}

.botao-container {
  margin-top: 30px;
  text-align: center;
}

button {
  padding: 14px 46px;
  border-radius: 18px;
  background: #c7a43a;
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all .2s ease;
}

button:hover {
  background: #b89630;
  transform: translateY(-2px);
}

button:disabled {
  opacity: .7;
  cursor: not-allowed;
}

.sucesso-msg {
  background: rgba(46,204,113,.2);
  border: 2px solid #2ecc71;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-weight: 600;
}

/* EDITOR */

:deep(.ql-toolbar){
  border:none;
  background:rgba(199,164,58,0.15);
  border-radius:12px 12px 0 0;
}

:deep(.ql-container){
  border:none;
  min-height:250px;
  border-radius:0 0 12px 12px;
}

@media (max-width: 600px) {
  .card {
    padding: 24px;
    border-radius: 20px;
  }

  button {
    width: 100%;
  }
}

</style>