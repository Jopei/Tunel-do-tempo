<template>
  <section class="historia-show-page">
    <SideMenu />

    <div class="painel">
      <!-- TOPO -->
      <div class="topo">
        <!-- MEMBROS -->
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

        <!-- CENTRO -->
        <div class="centro">
          <h1 class="titulo">{{ historia.titulo }}</h1>
          <p class="descricao">{{ historia.descricao_curta }}</p>
        </div>

        <!-- INFO -->
        <div class="info">
          <div class="info-item">
            <v-icon size="16">mdi-calendar</v-icon>
            <span>{{formatarData( historia.data_historia)}}</span>
          </div>

          <div class="info-item">
            <v-icon size="16">mdi-tag</v-icon>
            <span class="tipo">{{ historia.tipo_historia }}</span>
          </div>
        </div>
      </div>

      <!-- CONTEÚDO -->
      <div class="conteudo-box">
        <div ref="viewerRef" class="markdown-viewer"></div>
      </div>

      <!-- AÇÃO FUTURA -->
      <div class="acoes">
        <v-icon class="folder-icon">
          mdi-folder-outline
        </v-icon>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import SideMenu from "@/components/layout/SideMenu.vue";
import api from "@/services/api";

import "@toast-ui/editor/dist/toastui-editor.css";
import Viewer from "@toast-ui/editor/dist/toastui-editor-viewer";

const route = useRoute();
const viewerRef = ref(null);

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
  const { data } = await api.get(`/historias/${route.params.uuid}`);
  console.log(data);
  historia.value = data;

  new Viewer({
    el: viewerRef.value,
    initialValue: data.conteudo || "",
  });
});

function formatarData(data) {
  if (!data) return "";
  const [ano, mes, dia] = data.split("-");
  return `${dia}/${mes}/${ano}`;
}
</script>

<style scoped>
/* ===== FUNDO ===== */
.historia-show-page {
  min-height: 100vh;
  background: url("/backgrounds/home-bg.svg") center/cover no-repeat;
  padding: 100px 32px 0;
  animation: pageFadeUp 0.9s ease-out forwards;
}

/* ===== PAINEL ===== */
.painel {
  background: #fbf6e6;
  border-radius: 48px;
  padding: 48px 56px 56px;
  min-height: calc(100vh - 128px);
  box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

/* ===== TOPO ===== */
.topo {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  align-items: center;
  margin-bottom: 36px;
}

.membros .label {
  font-weight: 700;
}

.membros .valor {
  display: block;
  margin-top: 6px;
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

.info {
  text-align: right;
  font-weight: 700;
}

.info .data {
  display: block;
  margin-bottom: 6px;
}

.info .tipo {
  text-transform: capitalize;
}

/* ===== CONTEÚDO ===== */
.conteudo-box {
  background: #fff8e1;
  border-radius: 24px;
  padding: 36px;
  min-height: 420px;
  box-shadow: inset 0 0 30px rgba(0, 0, 0, .08);
}

.markdown-viewer {
  font-size: 15px;
  line-height: 1.7;
}

/* ===== AÇÕES ===== */
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

.membros {
  font-weight: 700;
}

.lista-membros {
  display: flex;
  flex-direction: column;
  margin-top: 6px;
  gap: 4px;
}

.membro {
  font-weight: 600;
  font-size: 14px;
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

:deep(.toastui-editor-contents) {
  font-size: 15px;
  line-height: 1.9;
  color: #333;
}

:deep(.toastui-editor-contents p) {
  margin-bottom: 18px;
}

:deep(.toastui-editor-contents h1),
:deep(.toastui-editor-contents h2),
:deep(.toastui-editor-contents h3) {
  margin-top: 28px;
  margin-bottom: 16px;
}

/* ===== ANIMAÇÃO ===== */
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
</style>
