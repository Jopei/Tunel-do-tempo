<template>
  <section class="cadastrar-usuario-page">
    <UserMenu />

    <div class="card">
      <h1>Cadastrar Usuário</h1>

      <div v-if="sucesso" class="sucesso-msg">
        Usuário cadastrado com sucesso 🎉
      </div>

      <!-- FOTO PERFIL -->
      <div class="foto-perfil-container">
        <label class="foto-upload">
          <img v-if="fotoPreview" :src="fotoPreview" class="foto-preview" />
          <div v-else class="foto-placeholder">
            <span>Adicionar Foto *</span>
          </div>
          <input type="file" accept="image/*" @change="onFoto" />
        </label>
      </div>

      <div class="grid">
        <div class="field">
          <label>Nome *</label>
          <input v-model="nome" type="text" :class="{ erro: errors.nome }" />
        </div>

        <div class="field">
          <label>Email *</label>
          <input v-model="email" type="email" :class="{ erro: errors.email }" />
        </div>
      </div>

      <div class="grid">
        <div class="field senha-field">
          <label>Senha *</label>

          <div class="senha-wrapper">
            <input v-model="senha" :type="mostrarSenha ? 'text' : 'password'" :class="{ erro: errors.senha }" />

            <span class="toggle-senha" @click="mostrarSenha = !mostrarSenha">
              {{ mostrarSenha ? "🙈" : "👁️" }}
            </span>
          </div>
        </div>

        <div class="field">
          <label>Tipo de Usuário *</label>
          <select v-model="tipoUsuarioId" :class="{ erro: errors.tipo_usuario_id }">
            <option disabled value="">Selecione</option>
            <option :value="1">Membro ADM</option>
            <option :value="2">Membro Rua</option>
            <option :value="3">Revisor</option>
          </select>
        </div>
      </div>

      <div class="grid">
        <div class="field">
          <label>Aniversário</label>
          <input v-model="aniversario" type="date" />
        </div>

        <div class="field">
          <label>Telefone</label>
          <input v-model="telefone" type="text" />
        </div>
      </div>

      <div class="field">
        <label>Descrição</label>
        <textarea v-model="descricao" rows="2"></textarea>
      </div>

      <!-- CHAVE -->
      <div class="grid destaque">
        <div class="field">
          <label>Nome da Chave *</label>
          <input v-model="chaveNome" type="text" :class="{ erro: errors.chave_nome }" />
        </div>

        <div class="field">
          <label>Código da Chave *</label>
          <input v-model="chaveCodigo" type="text" :class="{ erro: errors.chave_codigo }" />
        </div>
      </div>

      <div class="botao-container">
        <button @click="salvar" :disabled="loading">
          <span v-if="loading">Enviando...</span>
          <span v-else>Cadastrar Usuário</span>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { cadastrarUsuario } from "@/services/usuario.service";
import UserMenu from "@/components/layout/UserMenu.vue";
import { toast } from "vue3-toastify";

const mostrarSenha = ref(false);

const nome = ref("");
const email = ref("");
const senha = ref("");
const tipoUsuarioId = ref("");
const aniversario = ref("");
const telefone = ref("");
const descricao = ref("");

const chaveNome = ref("");
const chaveCodigo = ref("");

const foto = ref(null);
const fotoPreview = ref(null);

const loading = ref(false);
const errors = ref({});

function onFoto(e) {
  foto.value = e.target.files[0];

  if (foto.value) {
    fotoPreview.value = URL.createObjectURL(foto.value);
  }
}

async function salvar() {
  loading.value = true;
  errors.value = {};

  const form = new FormData();
  form.append("nome", nome.value);
  form.append("email", email.value);
  form.append("senha", senha.value);
  form.append("tipo_usuario_id", tipoUsuarioId.value);
  form.append("aniversario", aniversario.value);
  form.append("telefone", telefone.value);
  form.append("descricao", descricao.value);
  form.append("chave_nome", chaveNome.value);
  form.append("chave_codigo", chaveCodigo.value);
  form.append("imagem", foto.value);

  try {
    await cadastrarUsuario(form);

    toast.success("Usuário cadastrado com sucesso 🎉");

    limpar();
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
        "Erro inesperado ao cadastrar usuário.";

      toast.error(mensagem);

    }

  } finally {
    loading.value = false;
  }
}

function limpar() {
  nome.value = "";
  email.value = "";
  senha.value = "";
  tipoUsuarioId.value = "";
  aniversario.value = "";
  telefone.value = "";
  descricao.value = "";
  chaveNome.value = "";
  chaveCodigo.value = "";
  foto.value = null;
  fotoPreview.value = null;
}
</script>

<style scoped>
.cadastrar-usuario-page {
  min-height: 100vh;
  background: url("/backgrounds/cadastrar-bg.svg") center/cover;
  display: flex;
  justify-content: center;
  align-items: center;
}

.card {
  width: 100%;
  max-width: 950px;
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

input,
textarea,
select {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #ccc;
}

.destaque {
  border: 2px solid #c7a43a;
  padding: 22px;
  border-radius: 18px;
  margin-bottom: 30px;
}

.botao-container {
  margin-top: 36px;
  /* 👈 afastamento do campo código */
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
  opacity: 0.7;
  cursor: not-allowed;
}

.erro {
  border-color: #c0392b !important;
  box-shadow: 0 0 0 2px rgba(192, 57, 43, 0.25);
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

/* FOTO PERFIL */

.foto-perfil-container {
  display: flex;
  justify-content: center;
  margin-bottom: 32px;
}

.foto-upload input {
  display: none;
}

.foto-placeholder,
.foto-preview {
  width: 170px;
  height: 170px;
  border-radius: 100%;
  background: rgba(199, 164, 58, 0.15);
  border: 3px dashed #c7a43a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.foto-preview {
  object-fit: cover;
  border: 3px solid #c7a43a;
}

.senha-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.senha-wrapper input {
  width: 100%;
  padding-right: 44px;
}

.toggle-senha {
  position: absolute;
  right: 14px;
  cursor: pointer;
  font-size: 18px;
  user-select: none;
  transition: transform .15s ease;
}

.toggle-senha:hover {
  transform: scale(1.1);
}

/* MOBILE */

@media (max-width: 768px) {
  .card {
    padding: 28px;
    border-radius: 24px;
  }

  .grid {
    grid-template-columns: 1fr;
  }

  .foto-placeholder,
  .foto-preview {
    width: 140px;
    height: 140px;
  }

  button {
    width: 100%;
  }
}
</style>