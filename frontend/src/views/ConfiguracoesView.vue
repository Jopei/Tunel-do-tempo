<template>
  <section class="config-page">

    <TopActions />

    <div class="container">

      <h1 class="titulo">Editar perfil</h1>

      <!-- CARD PERFIL -->
      <div class="perfil-card">

        <div class="perfil-info">
          <img
            :src="fotoPerfilUrl"
            class="avatar"
          />

          <div class="usuario-dados">
            <strong>{{ form.nome }}</strong>
            <span>{{ form.email }}</span>
          </div>
        </div>

        <button class="btn-foto" @click="selecionarFoto">
          Mudar foto
        </button>

        <input
          ref="inputFoto"
          type="file"
          hidden
          @change="enviarFoto"
        />

      </div>

      <!-- FORM -->
      <div class="form">

        <div class="field">
          <label>Nome</label>
          <input v-model="form.nome" />
        </div>

        <div class="field">
          <label>Email</label>
          <input v-model="form.email" />
        </div>

        <div class="field">
          <label>Telefone</label>
          <input v-model="form.telefone" />
        </div>

        <div class="field">
          <label>Aniversário</label>
          <input type="date" v-model="form.aniversario" />
        </div>

        <div class="field">
          <label>Nova Senha</label>
          <input type="password" v-model="form.senha" />
        </div>

        <div class="field">
          <label>Bio</label>
          <textarea
            v-model="form.descricao"
            maxlength="150"
          ></textarea>

          <small class="contador">
            {{ form.descricao.length }} / 150
          </small>
        </div>

        <button class="btn-salvar" @click="salvar">
          Salvar alterações
        </button>

      </div>

    </div>

  </section>
</template>

<script setup>
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import TopActions from "@/components/layout/TopActions.vue";
import {
  atualizarUsuario,
  atualizarFotoPerfil
} from "@/services/usuario.service";

const authStore = useAuthStore();

const form = ref({
  nome: authStore.usuario?.nome || "",
  email: authStore.usuario?.email || "",
  telefone: authStore.usuario?.telefone || "",
  aniversario: authStore.usuario?.aniversario || "",
  senha: "",
  descricao: authStore.usuario?.descricao || ""
});

const inputFoto = ref(null);
const loading = ref(false);

const fotoPerfilUrl = computed(() => {
  if (!authStore.usuario?.foto_perfil)
    return "/imgs/avatar-placeholder.png";

  const baseUrl = import.meta.env.VITE_API_BASE_URL.replace("/api", "");
  return baseUrl + "/storage/" + authStore.usuario.foto_perfil;
});

function selecionarFoto() {
  inputFoto.value.click();
}

async function enviarFoto(e) {
  const file = e.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append("foto", file);

  try {
    await atualizarFotoPerfil(authStore.usuario.uuid, formData);
    await authStore.refreshUsuario();
  } catch (err) {
    console.error(err);
  }
}

async function salvar() {
  loading.value = true;

  try {
    await atualizarUsuario(authStore.usuario.uuid, form.value);
    await authStore.refreshUsuario();
    alert("Perfil atualizado com sucesso.");
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>

.config-page {
  min-height: 100vh;
  background: url("/backgrounds/home-bg.svg") center/cover;
  padding: 120px 24px 80px;
}

.container {
  max-width: 900px;
  margin: 0 auto;
}

.titulo {
  color: #fff;
  font-size: 42px;
  margin-bottom: 40px;
}

/* PERFIL */

.perfil-card {
  background: linear-gradient(
    135deg,
    rgba(30,30,30,0.95),
    rgba(50,50,50,0.95)
  );
  border-radius: 24px;
  padding: 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 50px;
}

.perfil-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.avatar {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #c7a43a;
}

.usuario-dados strong {
  color: #fff;
  font-size: 20px;
  display: block;
}

.usuario-dados span {
  color: #bbb;
}

.btn-foto {
  background: #c7a43a;
  border: none;
  padding: 10px 22px;
  border-radius: 20px;
  font-weight: 600;
  cursor: pointer;
  transition: .2s ease;
}

.btn-foto:hover {
  background: #b89630;
}

/* FORM */

.form {
  background: #fbf6e6;
  padding: 40px;
  border-radius: 28px;
  box-shadow: 0 30px 80px rgba(0,0,0,.3);
}

.field {
  margin-bottom: 26px;
}

.field label {
  font-weight: 600;
  display: block;
  margin-bottom: 8px;
}

input,
textarea {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #ccc;
}

textarea {
  min-height: 90px;
}

.contador {
  display: block;
  text-align: right;
  margin-top: 6px;
  color: #777;
}

.btn-salvar {
  margin-top: 20px;
  width: 100%;
  padding: 14px;
  border-radius: 20px;
  border: none;
  background: #c7a43a;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
  transition: .2s ease;
}

.btn-salvar:hover {
  background: #b89630;
}

/* MOBILE */

@media (max-width: 600px) {
  .perfil-card {
    flex-direction: column;
    gap: 20px;
    align-items: flex-start;
  }

  .form {
    padding: 24px;
  }

  .titulo {
    font-size: 30px;
  }
}

</style>