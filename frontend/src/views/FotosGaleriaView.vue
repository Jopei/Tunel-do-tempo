<template>
  <section class="galeria-page">
    <UserMenu />

    <h1 class="titulo-pagina">Galeria de Fotos</h1>

    <div class="card">

      <div v-if="!fotos.length && !carregando" class="sem-fotos">
        Nenhuma foto cadastrada.
      </div>

      <div v-else class="grid-fotos">

        <div v-for="foto in fotos" :key="foto.uuid" class="foto-card">

          <v-menu>
            <template #activator="{ props }">
              <v-btn icon v-bind="props" class="menu-btn">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>

            <v-list>
              <v-list-item @click="abrirEditar(foto)">
                <v-list-item-title>Editar</v-list-item-title>
              </v-list-item>

              <v-list-item @click="confirmarExclusao(foto)">
                <v-list-item-title class="text-red">
                  Excluir
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>

          <div class="thumb-wrapper" @click="ampliarFoto(foto.url)">
            <img :src="foto.url" loading="lazy" />
          </div>

          <div class="info">
            {{ foto.titulo || "Sem título" }}
          </div>

        </div>

      </div>

      <!-- Loader -->
      <div v-if="carregando" class="loader">
        Carregando...
      </div>

      <!-- Sentinela Infinite Scroll -->
      <div ref="sentinela"></div>

    </div>
  </section>

  <!-- MODAL FULLSCREEN -->
  <v-dialog v-model="fotoSelecionada" fullscreen transition="dialog-bottom-transition">
    <v-card class="viewer-card">
      <v-icon class="fechar-viewer" @click="fotoSelecionada = null">
        mdi-close
      </v-icon>

      <img :src="fotoSelecionada" class="viewer-img" />
    </v-card>
  </v-dialog>

  <v-dialog v-model="dialogEditar" max-width="600">
    <v-card class="editar-card">

      <div class="editar-header">
        Editar Foto
      </div>

      <div class="editar-body">

        <v-text-field v-model="formEditar.titulo" label="Título" variant="outlined" density="comfortable"
          class="campo" />

        <v-textarea v-model="formEditar.descricao" label="Descrição" variant="outlined" rows="3" class="campo" />

      </div>

      <div class="editar-footer">
        <v-btn variant="text" class="btn-cancelar" @click="dialogEditar = false">
          Cancelar
        </v-btn>

        <v-btn class="btn-salvar" :loading="salvando" @click="salvarEdicao">
          Salvar
        </v-btn>
      </div>

    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { listarFotos, excluirFoto, atualizarFoto } from "@/services/foto.service";
import UserMenu from "@/components/layout/UserMenu.vue";

const fotos = ref([]);
const pagina = ref(1);
const ultimaPagina = ref(1);
const carregando = ref(false);
const dialogEditar = ref(false);
const fotoEditando = ref(null);
const salvando = ref(false);

const formEditar = ref({
  titulo: "",
  descricao: ""
});

function abrirEditar(foto) {
  fotoEditando.value = foto;

  formEditar.value.titulo = foto.titulo || "";
  formEditar.value.descricao = foto.descricao || "";

  dialogEditar.value = true;
}

async function salvarEdicao() {
  if (!fotoEditando.value) return;

  salvando.value = true;

  await atualizarFoto(
    fotoEditando.value.uuid,
    formEditar.value
  );

  const index = fotos.value.findIndex(
    f => f.uuid === fotoEditando.value.uuid
  );

  if (index !== -1) {
    fotos.value[index].titulo = formEditar.value.titulo;
    fotos.value[index].descricao = formEditar.value.descricao;
  }

  salvando.value = false;
  dialogEditar.value = false;
}

const fotoSelecionada = ref(null);

const sentinela = ref(null);
let observer = null;

onMounted(async () => {
  await carregarFotos();

  observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      carregarMais();
    }
  });

  if (sentinela.value) {
    observer.observe(sentinela.value);
  }
});

onBeforeUnmount(() => {
  if (observer) observer.disconnect();
});

async function carregarFotos() {
  if (carregando.value) return;

  carregando.value = true;

  const data = await listarFotos(pagina.value);

  fotos.value.push(...data.data);
  ultimaPagina.value = data.meta.last_page;

  carregando.value = false;
}

async function carregarMais() {
  if (pagina.value >= ultimaPagina.value) return;
  pagina.value++;
  await carregarFotos();
}

function ampliarFoto(url) {
  fotoSelecionada.value = url;
}

async function confirmarExclusao(foto) {
  if (!confirm("Deseja realmente excluir esta foto?")) return;

  await excluirFoto(foto.uuid);
  fotos.value = fotos.value.filter(f => f.uuid !== foto.uuid);
}
</script>
<style scoped>
.galeria-page {
  min-height: 100vh;
  background: url("/backgrounds/cadastrar-bg.svg") center/cover;
  padding-top: 120px;
  padding-bottom: 120px;
  text-align: center;
}

/* TÍTULO */
.titulo-pagina {
  font-size: 64px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 60px;
  text-shadow: 0 4px 20px rgba(0, 0, 0, .3);
}

/* CARD */
.card {
  width: 95%;
  max-width: 1600px;
  margin: 0 auto;
  background: #f3ecdb;
  padding: 60px 80px;
  border-radius: 32px;
  box-shadow: 0 40px 120px rgba(0, 0, 0, .25);
}

/* GRID 5 COLUNAS */
.grid-fotos {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 30px;
}

/* CARD FOTO */
.foto-card {
  position: relative;
  background: #e6dcc4;
  border-radius: 20px;
  padding: 18px;
  text-align: left;
  transition: transform .2s ease;
}

.foto-card:hover {
  transform: translateY(-6px);
}

.menu-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 5px 15px rgba(0, 0, 0, .15);
}

/* THUMB */
.thumb-wrapper {
  height: 90px;
  overflow: hidden;
  border-radius: 12px;
  cursor: pointer;
}

.thumb-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info {
  margin-top: 12px;
  font-weight: 600;
  font-size: 14px;
  color: #333;
}

.loader {
  margin-top: 40px;
  font-weight: 600;
  color: #666;
}

/* MODAL */
.viewer-card {
  background: rgba(0, 0, 0, .96);
  height: 100vh;
  width: 100vw;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}

.viewer-img {
  max-width: 92vw;
  max-height: 92vh;
  border-radius: 18px;
  box-shadow: 0 40px 100px rgba(0, 0, 0, .7);
  object-fit: contain;
}

.fechar-viewer {
  position: absolute;
  top: 30px;
  right: 40px;
  font-size: 36px;
  color: #fff;
  cursor: pointer;
}

.editar-card {
  background: #f3ecdb;
  border-radius: 28px;
  padding: 40px;
  box-shadow: 0 40px 120px rgba(0, 0, 0, .25);
}

.editar-header {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 30px;
  color: #333;
}

.editar-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.campo :deep(.v-field) {
  border-radius: 14px;
}

.editar-footer {
  display: flex;
  justify-content: flex-end;
  gap: 16px;
  margin-top: 30px;
}

.btn-cancelar {
  font-weight: 600;
  color: #666;
}

.btn-salvar {
  background: #c7a43a;
  color: #fff;
  font-weight: 700;
  border-radius: 12px;
  padding: 0 24px;
  transition: .2s ease;
}

.btn-salvar:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(199, 164, 58, .4);
}

/* ============================= */
/* RESPONSIVO */
/* ============================= */

@media (max-width: 1600px) {
  .grid-fotos {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 1200px) {
  .titulo-pagina {
    font-size: 48px;
  }

  .grid-fotos {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .titulo-pagina {
    font-size: 38px;
  }

  .grid-fotos {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .titulo-pagina {
    font-size: 30px;
  }

  .card {
    padding: 30px 20px;
  }

  .grid-fotos {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
}

@media (max-width: 400px) {
  .grid-fotos {
    grid-template-columns: 1fr;
  }
}
</style>