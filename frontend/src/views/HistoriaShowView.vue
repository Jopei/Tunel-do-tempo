<template>
  <section class="historia-show-page">
    <UserMenu />

    <div class="painel">
      <!-- TOPO -->
      <div class="topo">
        <div class="membros">
          <div class="label-icon">
            <v-icon size="18">mdi-account-group</v-icon>
            <span>Membros</span>
          </div>

          <div class="lista-membros">
            <div v-for="u in historia.usuarios" :key="u.uuid" class="membro">
              <v-icon size="14">mdi-account</v-icon>
              <span>{{ u.nome }}</span>
            </div>
          </div>
        </div>

        <div class="centro">
          <h1 class="titulo">{{ historia.titulo }}</h1>
          <p class="descricao">{{ historia.descricao_curta }}</p>
        </div>

        <div class="info">
          <div class="info-item">
            <v-icon size="16">mdi-calendar</v-icon>
            <span>{{ formatarData(historia.data_historia) }}</span>
          </div>

          <div class="info-item">
            <v-icon size="16">mdi-tag</v-icon>
            <span class="tipo">{{ historia.tipo_historia }}</span>
          </div>
        </div>
      </div>

      <!-- CONTEÚDO -->
      <div class="conteudo-box">
        <div class="rich-conteudo" v-html="historia.conteudo"></div>
      </div>

      <!-- AÇÕES -->
      <div class="acoes">
        <v-icon
          class="folder-icon"
          @click="mostrarModalMidia = true"
        >
          mdi-folder-outline
        </v-icon>
      </div>
    </div>

    <!-- MODAL SELETOR DE MÍDIA -->
    <v-dialog v-model="mostrarModalMidia" max-width="700">
      <v-card class="modal-midia">
        <v-card-title class="titulo-modal">
          Galeria da História
        </v-card-title>

        <v-card-text>
          <div class="grid-midia">
            <div class="midia-card" @click="abrirFotos">
              <v-icon size="48">mdi-image-multiple</v-icon>
              <span>Fotos</span>
            </div>

            <div class="midia-card" @click="abrirVideos">
              <v-icon size="48">mdi-video</v-icon>
              <span>Vídeos</span>
            </div>

            <div class="midia-card" @click="abrirMusicas">
              <v-icon size="48">mdi-music</v-icon>
              <span>Músicas</span>
            </div>
          </div>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn @click="mostrarModalMidia = false">
            Fechar
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- GALERIAS -->
    <GaleriaFotos
      v-model="mostrarGaleriaFotos"
      :fotos="historia.fotos"
    />

    <GaleriaVideos
      v-model="mostrarGaleriaVideos"
      :videos="historia.videos"
    />

    <GaleriaMusicas
      v-model="mostrarGaleriaMusicas"
      :musicas="historia.musicas"
    />

  </section>
</template>


<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import GaleriaFotos from "@/components/historias/GaleriaFotos.vue";
import GaleriaVideos from "@/components/historias/GaleriaVideos.vue";
import GaleriaMusicas from "@/components/historias/GaleriaMusicas.vue";
import api from "@/services/api";
import UserMenu from "@/components/layout/UserMenu.vue";

const route = useRoute();

const mostrarModalMidia = ref(false);
const mostrarGaleriaFotos = ref(false);
const mostrarGaleriaVideos = ref(false);
const mostrarGaleriaMusicas = ref(false);

const historia = ref({
  titulo: "",
  descricao_curta: "",
  conteudo: "",
  data_historia: "",
  tipo_historia: "",
  usuarios: [],
  fotos: [],
  videos: [],
  musicas: [],
});

onMounted(async () => {
  try {
    const { data } = await api.get(`/historias/${route.params.uuid}`);
    historia.value = data;
  } catch (e) {
    console.error("Erro ao carregar história", e);
  }
});

function abrirFotos() {
  if (!historia.value.fotos.length) {
    alert("Esta história não possui fotos.");
    return;
  }

  mostrarModalMidia.value = false;
  mostrarGaleriaFotos.value = true;
}

function abrirVideos() {
  if (!historia.value.videos.length) {
    alert("Esta história não possui vídeos.");
    return;
  }

  mostrarModalMidia.value = false;
  mostrarGaleriaVideos.value = true;
}

function abrirMusicas() {
  if (!historia.value.musicas.length) {
    alert("Esta história não possui músicas.");
    return;
  }

  mostrarModalMidia.value = false;
  mostrarGaleriaMusicas.value = true;
}

function formatarData(data) {
  if (!data) return "";
  const [ano, mes, dia] = data.split("-");
  return `${dia}/${mes}/${ano}`;
}
</script>



<style scoped>
.historia-show-page {
  min-height: 100vh;
  background: url("/backgrounds/home-bg.svg") center/cover no-repeat;
  padding: 100px 32px 0;
  animation: pageFadeUp 0.9s ease-out forwards;
}

.painel {
  background: #fbf6e6;
  border-radius: 48px;
  padding: 48px 56px 56px;
  min-height: calc(100vh - 128px);
  box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

.topo {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  align-items: center;
  margin-bottom: 36px;
}

.centro {
  text-align: center;
}

.titulo {
  font-size: 36px;
  font-weight: 800;
  margin-bottom: 8px;
}

.descricao {
  font-size: 16px;
  font-weight: 600;
}

.membros {
  font-weight: 700;
}

.label-icon {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
}

.lista-membros {
  display: flex;
  flex-direction: column;
  margin-top: 8px;
  gap: 6px;
}

.membro {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
  font-weight: 600;
}

.info {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-end;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
}

.conteudo-box {
  background: #fff8e1;
  border-radius: 24px;
  padding: 36px;
  min-height: 420px;
  box-shadow: inset 0 0 30px rgba(0, 0, 0, .08);
}

.rich-conteudo {
  font-size: 15px;
  line-height: 1.9;
  color: #333;
}

.rich-conteudo p {
  margin-bottom: 18px;
}

.rich-conteudo h1 {
  font-size: 28px;
  margin-top: 28px;
  margin-bottom: 16px;
}

.rich-conteudo h2 {
  font-size: 22px;
  margin-top: 24px;
  margin-bottom: 14px;
}

.rich-conteudo h3 {
  font-size: 18px;
  margin-top: 20px;
  margin-bottom: 12px;
}

.rich-conteudo ul,
.rich-conteudo ol {
  padding-left: 22px;
  margin-bottom: 18px;
}

.rich-conteudo li {
  margin-bottom: 6px;
}

.rich-conteudo img {
  max-width: 100%;
  border-radius: 12px;
  margin: 16px 0;
}

.rich-conteudo a {
  color: #c7a43a;
  font-weight: 600;
  text-decoration: none;
}

.rich-conteudo a:hover {
  text-decoration: underline;
}

.acoes {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
}

.folder-icon {
  font-size: 32px;
  cursor: pointer;
  transition: transform .2s ease, opacity .2s ease;
}

.folder-icon:hover {
  transform: scale(1.15);
  opacity: .85;
}

/* MODAL MÍDIA – PADRÃO TÚNEL DO TEMPO */

.modal-midia {
  background: #fbf6e6;
  border-radius: 32px;
  padding: 32px;
  box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

.titulo-modal {
  font-weight: 800;
  text-align: center;
  font-size: 22px;
  color: #333;
  margin-bottom: 10px;
}

/* GRID */
.grid-midia {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  margin-top: 24px;
}

/* CARDS */
.midia-card {
  background: rgba(199, 164, 58, 0.12);
  border: 2px solid rgba(199, 164, 58, 0.35);
  border-radius: 22px;
  padding: 40px 10px;

  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;

  font-weight: 700;
  color: #333;

  cursor: pointer;

  transition: all .25s ease;
}

/* Ícone dourado */
.midia-card .v-icon {
  color: #c7a43a;
  transition: transform .25s ease;
}

/* Hover elegante */
.midia-card:hover {
  background: rgba(199, 164, 58, 0.22);
  border-color: #c7a43a;
  transform: translateY(-8px);
  box-shadow: 0 18px 40px rgba(0, 0, 0, .18);
}

.midia-card:hover .v-icon {
  transform: scale(1.15);
  color: #b89630;
}



@keyframes pageFadeUp {
  from {
    opacity: 0;
    transform: translateY(28px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 600px) {
  .historia-show-page {
    padding: 72px 12px 0;
  }

  .painel {
    padding: 24px 20px 28px;
    border-radius: 24px 24px 0 0;
    min-height: auto;
  }

  .topo {
    grid-template-columns: 1fr;
    gap: 24px;
    text-align: center;
  }

  .info {
    align-items: center;
  }

  .conteudo-box {
    padding: 20px;
    min-height: unset;
  }

  .rich-conteudo {
    font-size: 14px;
  }

  .acoes {
    justify-content: center;
  }
  .grid-midia {
    grid-template-columns: 1fr;
  }

  .modal-midia {
    padding: 24px;
    border-radius: 24px 24px 0 0;
  }
}
</style>