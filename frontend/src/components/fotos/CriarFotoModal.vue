<template>
  <v-dialog v-model="dialog" max-width="800">
    <v-card class="card">

      <v-icon class="fechar" @click="fechar">
        mdi-close
      </v-icon>

      <h1>Cadastrar Foto</h1>

      <div v-if="sucesso" class="sucesso-msg">
        Foto cadastrada com sucesso 🎉
      </div>

      <!-- FOTO -->
      <div class="foto-container">
        <label class="foto-upload">
          <img
            v-if="fotoPreview"
            :src="fotoPreview"
            class="foto-preview"
          />

          <div v-else class="foto-placeholder">
            <span>Adicionar Foto *</span>
          </div>

          <input type="file" accept="image/*" @change="onFoto" />
        </label>
      </div>

      <div class="field">
        <label>Título</label>
        <input v-model="titulo" type="text" :class="{ erro: errors.titulo }" />
      </div>

      <div class="field">
        <label>Descrição</label>
        <textarea v-model="descricao" rows="2"></textarea>
      </div>

      <div class="botao-container">
        <button @click="salvar" :disabled="loading">
          <span v-if="loading">Enviando...</span>
          <span v-else>Cadastrar Foto</span>
        </button>
      </div>

    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed } from "vue";
import { cadastrarFoto } from "@/services/foto.service";

const props = defineProps({
  modelValue: Boolean
});

const emit = defineEmits(["update:modelValue", "sucesso"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val)
});

const titulo = ref("");
const descricao = ref("");
const tipoImagemId = ref(3);
const foto = ref(null);
const fotoPreview = ref(null);

const loading = ref(false);
const sucesso = ref(false);
const errors = ref({});

function onFoto(e) {
  foto.value = e.target.files[0];
  if (foto.value) {
    fotoPreview.value = URL.createObjectURL(foto.value);
  }
}

async function salvar() {
  loading.value = true;
  sucesso.value = false;
  errors.value = {};

  const form = new FormData();
  form.append("titulo", titulo.value);
  form.append("descricao", descricao.value);
  form.append("tipo_imagem_id", tipoImagemId.value);
  form.append("imagem", foto.value);

  try {
    await cadastrarFoto(form);
    sucesso.value = true;
    emit("sucesso");
    limpar();
    setTimeout(() => fechar(), 800);
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {};
    }
  } finally {
    loading.value = false;
  }
}

function limpar() {
  titulo.value = "";
  descricao.value = "";
  tipoImagemId.value = "";
  foto.value = null;
  fotoPreview.value = null;
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
  border-radius: 28px;
  box-shadow: 0 30px 80px rgba(0,0,0,.3);
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

/* FOTO QUADRADA */
.foto-container {
  display: flex;
  justify-content: center;
  margin-bottom: 30px;
}

.foto-upload input {
  display: none;
}

.foto-placeholder,
.foto-preview {
  width: 180px;
  height: 180px;
  border-radius: 16px; /* 🔥 agora quadrado arredondado */
  background: rgba(199,164,58,.15);
  border: 3px dashed #c7a43a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  cursor: pointer;
}

.foto-preview {
  object-fit: cover;
  border: 3px solid #c7a43a;
}

.field {
  margin-bottom: 20px;
}

input,
textarea,
select {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #ccc;
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

:deep(.v-overlay__content) {
  border-radius: 32px !important;
  overflow: hidden;
}

.card {
  position: relative;
  background: #fbf6e6;
  padding: 50px;
  border-radius: 32px; /* mais arredondado */
  box-shadow: 0 30px 80px rgba(0,0,0,.3);
}

.sucesso-msg {
  background: rgba(46,204,113,.2);
  border: 2px solid #2ecc71;
  padding: 12px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-weight: 600;
}

.erro {
  border-color: #c0392b !important;
  box-shadow: 0 0 0 2px rgba(192,57,43,.25);
}

</style>