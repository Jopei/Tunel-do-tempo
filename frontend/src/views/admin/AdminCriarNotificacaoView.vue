<template>
  <section class="cadastrar-notificacao-page">

    <UserMenu />

    <div class="card">
      <h1>Criar Notificação</h1>

      <div v-if="sucesso" class="sucesso-msg">
        Notificação criada com sucesso 🔔
      </div>

      <div class="field">
        <label>Título</label>
        <input v-model="titulo" type="text" :class="{ erro: errors.titulo }" />
      </div>

      <div class="grid">
        <div class="field">
          <label>Tema</label>
          <input v-model="tema" type="text" />
        </div>

        <div class="field">
          <label>Link</label>
          <input v-model="link" type="text" placeholder="/galeria-videos" />
        </div>
      </div>

      <div class="field destaque">
        <label>Descrição (Markdown)</label>
        <div ref="editorRef" class="editor"></div>
      </div>

      <button @click="salvar" :disabled="loading">
        <span v-if="loading">Criando...</span>
        <span v-else>Criar Notificação</span>
      </button>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Quill from "quill";
import "quill/dist/quill.snow.css";
import { criarNotificacao } from "@/services/notificacao.service";
import UserMenu from "@/components/layout/UserMenu.vue";

const titulo = ref("");
const tema = ref("");
const link = ref("");
const loading = ref(false);
const sucesso = ref(false);
const errors = ref({});

const editorRef = ref(null);
let editor;

onMounted(() => {
  editor = new Quill(editorRef.value, {
    theme: "snow",
    placeholder: "Escreva a notificação aqui...",
    modules: {
      toolbar: [
        [{ header: [1,2,3,false] }],
        ["bold","italic","underline"],
        [{ list:"ordered" },{ list:"bullet" }],
        ["link"],
        ["clean"]
      ]
    }
  });
});

async function salvar(){
  loading.value = true;
  sucesso.value = false;
  errors.value = {};

  try{
    await criarNotificacao({
      titulo: titulo.value,
      descricao: editor.root.innerHTML,
      tema: tema.value,
      link: link.value
    });

    sucesso.value = true;
    limpar();
  }
  catch(e){
    if(e.response?.status === 422){
      errors.value = e.response.data.errors || {};
    }
  }
  finally{
    loading.value = false;
  }
}

function limpar(){
  titulo.value = "";
  tema.value = "";
  link.value = "";
  editor.setContents([]);
}
</script>

<style scoped>
.cadastrar-notificacao-page {
  min-height: 100vh;
  background: url("/backgrounds/cadastrar-bg.svg") center/cover;
  display: flex;
  justify-content: center;
  align-items: center;
}

.card {
  width: 100%;
  max-width: 900px;
  background: #fbf6e6;
  padding: 56px;
  border-radius: 32px;
  margin-top: 6%;
  box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
}

h1 {
  text-align: center;
  margin-bottom: 36px;
}

.field {
  margin-bottom: 26px;
}

.field label {
  font-weight: 600;
  margin-bottom: 10px;
  display: block;
}

input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #ccc;
}

.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.destaque {
  border: 2px solid #c7a43a;
  padding: 18px;
  border-radius: 18px;
}

button {
  display: block;
  margin: 30px auto 0;
  padding: 14px 46px;
  border-radius: 18px;
  background: #c7a43a;
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: background .2s ease, transform .15s ease;
}

button:hover {
  background: #b89630;
  transform: translateY(-2px);
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.sucesso-msg {
  background: rgba(46, 204, 113, 0.2);
  border: 2px solid #2ecc71;
  color: #1e7e34;
  padding: 14px;
  border-radius: 14px;
  font-weight: 700;
  text-align: center;
  margin-bottom: 24px;
}

.erro {
  border-color: #c0392b !important;
  box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.25);
}

:deep(.ql-toolbar){
  border:none;
  background:rgba(199,164,58,0.15);
  border-radius:12px 12px 0 0;
}

:deep(.ql-container){
  border:none;
  min-height:250px;
  border-radius:0 0 12px 12px;
  font-size:16px;
}

@media (max-width: 600px) {
  .card {
    padding: 28px;
    border-radius: 20px;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  button {
    width: 100%;
  }
}
</style>