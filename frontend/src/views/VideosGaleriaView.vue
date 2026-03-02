<template>
    <section class="galeria-page">
        <UserMenu />

        <h1 class="titulo-pagina">Galeria de Vídeos</h1>

        <div class="card">

            <div v-if="!videos.length && !carregando" class="sem-fotos">
                Nenhum vídeo cadastrado.
            </div>

            <div v-else class="grid-fotos">

                <div v-for="video in videos" :key="video.uuid" class="foto-card">

                    <v-menu>
                        <template #activator="{ props }">
                            <v-btn icon v-bind="props" class="menu-btn">
                                <v-icon>mdi-dots-vertical</v-icon>
                            </v-btn>
                        </template>

                        <v-list>
                            <v-list-item @click="abrirEditar(video)">
                                <v-list-item-title>Editar</v-list-item-title>
                            </v-list-item>

                            <v-list-item @click="confirmarExclusao(video)">
                                <v-list-item-title class="text-red">
                                    Excluir
                                </v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>

                    <div class="thumb-wrapper" @click="abrirVideo(video.url)">
                        <video :src="video.url" muted preload="metadata" />
                        <div class="play-overlay">
                            <v-icon size="40">mdi-play-circle</v-icon>
                        </div>
                    </div>

                    <div class="info">
                        {{ video.titulo || "Sem título" }}
                    </div>

                </div>

            </div>

            <div v-if="carregando" class="loader">
                Carregando...
            </div>

            <div ref="sentinela"></div>

        </div>
    </section>


    <v-dialog v-model="videoSelecionado" fullscreen>
        <v-card class="viewer-card">
            <v-icon class="fechar-viewer" @click="videoSelecionado = null">
                mdi-close
            </v-icon>

            <video v-if="videoSelecionado" :src="videoSelecionado" controls autoplay class="viewer-video" />
        </v-card>
    </v-dialog>

    <!-- MODAL EDITAR -->
    <v-dialog v-model="dialogEditar" max-width="600">
        <v-card class="editar-card">

            <div class="editar-header">
                Editar Vídeo
            </div>

            <div class="editar-body">
                <v-text-field v-model="formEditar.titulo" label="Título" variant="outlined" />

                <v-textarea v-model="formEditar.descricao" label="Descrição" rows="3" variant="outlined" />
            </div>

            <div class="editar-footer">
                <v-btn variant="text" @click="dialogEditar = false">
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
import { listarVideos, excluirVideo, atualizarVideo } from "@/services/video.service";
import UserMenu from "@/components/layout/UserMenu.vue";

const videos = ref([]);
const pagina = ref(1);
const ultimaPagina = ref(1);
const carregando = ref(false);

const videoSelecionado = ref(null);
const dialogEditar = ref(false);
const videoEditando = ref(null);
const salvando = ref(false);

const formEditar = ref({
    titulo: "",
    descricao: ""
});

function abrirEditar(video) {
    videoEditando.value = video;

    formEditar.value.titulo = video.titulo || "";
    formEditar.value.descricao = video.descricao || "";

    dialogEditar.value = true;
}

async function salvarEdicao() {
    if (!videoEditando.value) return;

    salvando.value = true;

    await atualizarVideo(
        videoEditando.value.uuid,
        formEditar.value
    );

    const index = videos.value.findIndex(
        v => v.uuid === videoEditando.value.uuid
    );

    if (index !== -1) {
        videos.value[index].titulo = formEditar.value.titulo;
        videos.value[index].descricao = formEditar.value.descricao;
    }

    salvando.value = false;
    dialogEditar.value = false;
}

const sentinela = ref(null);
let observer = null;

onMounted(async () => {
    await carregarVideos();

    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) carregarMais();
    });

    if (sentinela.value) observer.observe(sentinela.value);
});

onBeforeUnmount(() => {
    if (observer) observer.disconnect();
});

async function carregarVideos() {
    if (carregando.value) return;

    carregando.value = true;

    const data = await listarVideos(pagina.value);

    videos.value.push(...data.data);
    ultimaPagina.value = data.meta.last_page;

    carregando.value = false;
}

async function carregarMais() {
    if (pagina.value >= ultimaPagina.value) return;
    pagina.value++;
    await carregarVideos();
}

function abrirVideo(url) {
    videoSelecionado.value = url;
}

async function confirmarExclusao(video) {
    if (!confirm("Deseja realmente excluir este vídeo?")) return;

    await excluirVideo(video.uuid);
    videos.value = videos.value.filter(v => v.uuid !== video.uuid);
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
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 32px;
}

/* CARD FOTO */
.foto-card {
    position: relative;
    background: #e6dcc4;
    border-radius: 20px;
    padding: 24px;
    text-align: left;
    transition: transform .2s ease;
}

.foto-card:hover {
    transform: translateY(-6px);
}

.menu-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, .15);
    width: 42px;
    height: 42px;
    z-index: 10;
}

/* THUMB */
.thumb-wrapper video {
    width: 100%;
    height: 100%;
    object-fit: cover;
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

.play-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    background: rgba(0, 0, 0, .35);
    opacity: 0;
    transition: .2s ease;
}

.thumb-wrapper {
    position: relative;
}

.thumb-wrapper:hover .play-overlay {
    opacity: 1;
}

.viewer-video {
    max-width: 92vw;
    max-height: 92vh;
    border-radius: 18px;
    box-shadow: 0 40px 100px rgba(0, 0, 0, .7);
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