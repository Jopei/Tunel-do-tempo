<template>
  <section class="cadastrar-historia-page">
    <UserMenu />

    <div class="card">
      <h1>Cadastrar História</h1>

      <div class="grid">
        <div class="field">
          <label>Título</label>
          <input v-model="titulo" type="text" :class="{ erro: errors.titulo }" />
        </div>

        <div class="field">
          <label>Data da História</label>
          <input v-model="dataHistoria" type="date" :class="{ erro: errors.data_historia }" />
        </div>
      </div>

      <div class="field">
        <label>Descrição curta</label>
        <textarea v-model="descricaoCurta" rows="2" :class="{ erro: errors.descricao_curta }"></textarea>
      </div>

      <div class="field">
        <label>Tipo da História</label>
        <select v-model="tipoHistoria" :class="{ erro: errors.tipo_historia }">
          <option value="Pessoal">Pessoal</option>
          <option value="Principal">Principal</option>
          <option value="Paralela">Paralela</option>
        </select>
      </div>

      <div class="field destaque">
        <label>Conteúdo da História</label>
        <div ref="editorRef" class="editor"></div>
      </div>

      <div class="field">
        <label>Usuários da História</label>

        <div class="usuarios-checkbox">
          <label v-for="u in usuarios" :key="u.uuid" class="checkbox-item">
            <input type="checkbox" :value="u.uuid" v-model="usuariosSelecionados" />
            <span class="checkbox-label">{{ u.nome }}</span>
          </label>
        </div>
      </div>

      <div class="uploads">
        <label class="upload-box">
          <span class="icon">🖼️</span>
          <span>Adicionar Fotos</span>
          <input type="file" multiple accept="image/*" @change="onFotos" />
        </label>

        <label class="upload-box">
          <span class="icon">🎬</span>
          <span>Adicionar Vídeos</span>
          <input type="file" multiple accept="video/*" @change="onVideos" />
        </label>

        <label class="upload-box">
          <span class="icon">🎵</span>
          <span>Adicionar Músicas</span>
          <input type="file" multiple accept="audio/*" @change="onMusicas" />
        </label>
      </div>

      <div v-if="musicas.length" class="musicas-lista">
        <div v-for="(m, i) in musicas" :key="i" class="musica-item">
          <span>{{ m.name }}</span>
          <button type="button" @click="removerMusica(i)">×</button>
        </div>
      </div>

      <div class="preview">
        <div v-for="(f, i) in fotosPreview" :key="'f' + i" class="preview-item">
          <img :src="f" />
          <button type="button" class="remove-btn" @click="removerFoto(i)">×</button>
        </div>

        <div v-for="(v, i) in videosPreview" :key="'v' + i" class="preview-item">
          <video :src="v" controls />
          <button type="button" class="remove-btn" @click="removerVideo(i)">×</button>
        </div>
      </div>

      <button @click="salvar" :disabled="loading">
        <span v-if="loading">Enviando...</span>
        <span v-else>Salvar História</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Quill from "quill";
import "quill/dist/quill.snow.css";
import api from "@/services/api";
import { listarUsuarios } from "@/services/usuario.service";
import UserMenu from "@/components/layout/UserMenu.vue";
import { toast } from "vue3-toastify";

const titulo = ref("");
const descricaoCurta = ref("");
const dataHistoria = ref("");
const tipoHistoria = ref("Pessoal");

const usuarios = ref([]);
const usuariosSelecionados = ref([]);

const fotos = ref([]);
const videos = ref([]);
const musicas = ref([]);

const fotosPreview = ref([]);
const videosPreview = ref([]);

const loading = ref(false);
const errors = ref({});

const editorRef = ref(null);
let editor;

onMounted(async () => {
  editor = new Quill(editorRef.value, {
    theme: "snow",
    placeholder: "Escreva a história aqui...",
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ["bold", "italic", "underline"],
        [{ list: "ordered" }, { list: "bullet" }],
        ["link", "image"],
        ["clean"],
      ],
    },
  });

  carregarUsuarios();
});

async function carregarUsuarios() {
  usuarios.value = await listarUsuarios();
}

function onFotos(e) {
  fotos.value = Array.from(e.target.files);
  fotosPreview.value = fotos.value.map((f) => URL.createObjectURL(f));
}

function onVideos(e) {
  videos.value = Array.from(e.target.files);
  videosPreview.value = videos.value.map((v) => URL.createObjectURL(v));
}

function onMusicas(e) {
  musicas.value = Array.from(e.target.files);
}

function removerFoto(i) {
  fotos.value.splice(i, 1);
  fotosPreview.value.splice(i, 1);
}

function removerVideo(i) {
  videos.value.splice(i, 1);
  videosPreview.value.splice(i, 1);
}

function removerMusica(i) {
  musicas.value.splice(i, 1);
}

async function salvar() {
  loading.value = true;
  errors.value = {};

  const form = new FormData();
  form.append("titulo", titulo.value);
  form.append("descricao_curta", descricaoCurta.value);
  form.append("conteudo", editor.root.innerHTML);
  form.append("data_historia", dataHistoria.value);
  form.append("tipo_historia", tipoHistoria.value);

  usuariosSelecionados.value.forEach((u) => {
    form.append("usuarios[]", u);
  });

  fotos.value.forEach((f) => form.append("fotos[]", f));
  videos.value.forEach((v) => form.append("videos[]", v));
  musicas.value.forEach((m) => form.append("musicas[]", m));

  try {
    await api.post("/cadastrar/historias", form);

    toast.success("História cadastrada com sucesso 🎉");

    limparFormulario();
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {};

      const apiErrors = e.response.data.errors;

      const primeiraMensagem =
        Object.values(apiErrors)[0]?.[0] || "Erro de validação.";

      toast.error(primeiraMensagem);
    } else {
      const mensagem =
        e.response?.data?.message ||
        "Erro inesperado ao cadastrar história.";

      toast.error(mensagem);
    }
  } finally {
    loading.value = false;
  }
}

function limparFormulario() {
  titulo.value = "";
  descricaoCurta.value = "";
  dataHistoria.value = "";
  tipoHistoria.value = "Pessoal";
  usuariosSelecionados.value = [];
  fotos.value = [];
  videos.value = [];
  musicas.value = [];
  fotosPreview.value = [];
  videosPreview.value = [];
  editor.setContents([]);
}
</script>

<style scoped>
.cadastrar-historia-page {
  min-height: 100vh;
  background: url("/backgrounds/cadastrar-bg.svg") center/cover;
  display: flex;
  justify-content: center;
  align-items: center;
}

.card {
  width: 100%;
  max-width: 1100px;
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

.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

.field {
  margin-bottom: 26px;
}

.field label {
  font-weight: 600;
  margin-bottom: 10px;
  display: block;
}

/* CHECKBOX USUÁRIOS */
.checkbox-item {
  display: grid;
  grid-template-columns: auto 3fr;
  align-items: center;

  column-gap: 32px;
  /* 👈 AQUI aumenta o espaço real */

  max-width: 300px;
  background: rgba(199, 164, 58, 0.15);
  padding: 16px 14px;
  border-radius: 14px;

  cursor: pointer;
  font-weight: 600;
}

/* checkbox */
.checkbox-item input {
  width: 16px;
  height: 16px;
  transform: scale(1.15);
}

/* nome */
.checkbox-label {
  padding-left: 12px;
  /* 👈 ajuste fino visual */
  line-height: 1;
  text-align: left;
}


input,
textarea {
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

.uploads {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin: 28px 0;
}

.upload-box {
  background: rgba(199, 164, 58, 0.15);
  border: 2px dashed #c7a43a;
  border-radius: 18px;
  padding: 22px;
  text-align: center;
  cursor: pointer;
  font-weight: 600;
  transition: all .2s ease;
}

.upload-box:hover {
  background: rgba(199, 164, 58, 0.25);
  transform: translateY(-2px);
}

.upload-box input {
  display: none;
}

.icon {
  display: block;
  font-size: 28px;
  margin-bottom: 8px;
}

.preview {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 24px;
}

.preview img,
.preview video {
  width: 120px;
  border-radius: 12px;
}

button {
  display: block;
  margin: 0 auto;
  padding: 14px 46px;
  border-radius: 18px;
  background: #c7a43a;
  color: #fff;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: background .2s ease, transform .15s ease, box-shadow .15s ease;
}

button:hover {
  background: #b89630;
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, .25);
}



.preview-item {
  position: relative;
}

.remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;

  width: 16px;
  height: 16px;
  border-radius: 100%;

  border: none;
  background: #c0392b;
  color: #fff;
  font-size: 18px;
  font-weight: bold;

  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;

  transition: transform .15s ease, background .2s ease;
}

.remove-btn:hover {
  background: #a93226;
  transform: scale(1.1);
}

.musicas-lista {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 24px;
}

.musica-item {
  position: relative;
  /* 👈 referência para o botão */
  display: flex;
  align-items: center;

  max-width: 420px;
  background: rgba(199, 164, 58, 0.15);
  padding: 10px 44px 10px 16px;
  /* 👈 espaço pro botão */
  border-radius: 12px;

  font-weight: 600;
}

/* botão no canto direito */
.musica-item button {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);

  width: 26px;
  height: 26px;
  border-radius: 50%;

  border: none;
  background: #c0392b;
  color: #fff;
  font-size: 18px;
  font-weight: bold;

  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;

  transition: background .2s ease, transform .15s ease;
}

.musica-item button:hover {
  background: #a93226;
  transform: translateY(-50%) scale(1.1);
}

.menu-toggle {
  transform: none;
}

.side-menu-wrapper {
  position: fixed;

  bottom: 32px;
  /* visualmente topo */
  right: 32px;
  /* 👈 visualmente esquerdo */

  z-index: 2000;

}

select {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #ccc;
  background-color: #fff;

  font-size: 15px;
  font-weight: 600;
  color: #333;

  cursor: pointer;

  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;

  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23c7a43a' viewBox='0 0 16 16'%3E%3Cpath d='M1.5 5.5l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 14px;
}

/* foco */
select:focus {
  outline: none;
  border-color: #c7a43a;
  box-shadow: 0 0 0 2px rgba(199, 164, 58, 0.25);
}
.usuarios-checkbox {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 10px;
  }

  .checkbox-item {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    column-gap: 12px;

    width: 100%;
    background: rgba(199, 164, 58, 0.15);
    padding: 16px 14px;
    border-radius: 14px;

    cursor: pointer;
    font-weight: 600;
  }

  .usuarios-checkbox {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
  }
/* hover */
select:hover {
  border-color: #c7a43a;
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

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

:deep(.ql-toolbar) {
  border: none;
  background: rgba(199, 164, 58, 0.15);
  border-radius: 12px 12px 0 0;
}

:deep(.ql-container) {
  border: none;
  min-height: 300px;
  border-radius: 0 0 12px 12px;
  font-size: 16px;
}

/* ========================= */
/* MOBILE ONLY – ADAPTACAO */
/* ========================= */
@media (max-width: 600px) {
  .cadastrar-historia-page {
    align-items: flex-start;
    padding: 16px 0;
  }

  .card {
    width: 100%;
    margin-top: 0;
    padding: 24px;
    border-radius: 20px 20px 0 0;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  .uploads {
    grid-template-columns: 1fr;
  }

  .usuarios-checkbox {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 10px;
  }

  .checkbox-item {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    column-gap: 12px;

    width: 100%;
    background: rgba(199, 164, 58, 0.15);
    padding: 16px 14px;
    border-radius: 14px;

    cursor: pointer;
    font-weight: 600;
  }

  .usuarios-checkbox {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
  }

  .preview img,
  .preview video {
    width: 100px;
  }

  button {
    width: 100%;
  }

  .editor {
    overflow-x: hidden;
  }
}
</style>
