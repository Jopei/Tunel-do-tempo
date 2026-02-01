<template>
    <section class="historias-page">
        <SideMenu />

        <div class="painel">
            <div class="search-bar">
                <input v-model="busca" type="text" placeholder="Buscar História..." />
                <v-icon class="search-icon">
                    mdi-magnify
                </v-icon>
            </div>

            <div class="cards-area">
                <div v-for="h in historiasFiltradas" :key="h.uuid" class="historia-card"
                    @click="irParaHistoria(h.uuid)">
                    <!-- MENU 3 PONTOS -->
                    <v-menu location="end top" offset="10">
                        <template #activator="{ props }">
                            <v-icon v-bind="props" class="menu-icon" @click.stop>
                                mdi-dots-vertical
                            </v-icon>
                        </template>

                        <v-card class="menu-card" elevation="6">
                            <v-list density="compact" class="menu-list">
                                <v-list-item>
                                    <v-list-item-title>Ver</v-list-item-title>
                                </v-list-item>

                                <v-list-item @click="editarHistoria(h)">
                                    <v-list-item-title>Editar</v-list-item-title>
                                </v-list-item>

                                <v-list-item @click="abrirDialogExcluir(h.uuid)">
                                    <v-list-item-title>Apagar</v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-card>
                    </v-menu>

                    <div class="card-title">{{ h.titulo }}</div>

                    <div class="card-footer">
                        <span class="tipo">{{ h.tipo_historia }}</span>
                        <span class="membros">👥 {{ h.usuarios.length }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <v-dialog v-model="dialogExcluir" max-width="420">
        <v-card style="background:#fbf6e6; border-radius:24px;">
            <v-card-title style="font-weight:700; text-align:center;">
                Excluir história
            </v-card-title>

            <v-card-text style="text-align:center;">
                Tem certeza que deseja excluir esta história?
            </v-card-text>

            <v-card-actions style="justify-content:center; gap:16px;">
                <v-btn variant="text" @click="dialogExcluir = false">
                    Cancelar
                </v-btn>

                <v-btn style="background:#b00020; color:#fff;" @click="confirmarExclusao">
                    Sim, excluir
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <EditarHistoriaModal v-model="dialogEditar" :historia="historiaSelecionada" @confirmar="recarregarHistorias" />


</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import SideMenu from "@/components/layout/SideMenu.vue";
import { buscarHistoriasResumo } from "@/services/historia.service";
import { deletarHistoria, atualizarHistoria } from "@/services/historia.service";
import EditarHistoriaModal from "@/components/historias/EditarHistoriaModal.vue";

const router = useRouter();
const historias = ref([]);
const busca = ref("");
const dialogExcluir = ref(false);
const historiaSelecionada = ref(null);
const dialogEditar = ref(false);

function editarHistoria(historia) {
    historiaSelecionada.value = historia;
    dialogEditar.value = true;
}

onMounted(async () => {
    historias.value = await buscarHistoriasResumo();
});

const historiasFiltradas = computed(() => {
    if (!busca.value) return historias.value;
    return historias.value.filter(h =>
        h.titulo.toLowerCase().includes(busca.value.toLowerCase())
    );
});

function abrirDialogExcluir(uuid) {
    historiaSelecionada.value = uuid;
    dialogExcluir.value = true;
}

async function confirmarExclusao() {
    await deletarHistoria(historiaSelecionada.value);

    historias.value = historias.value.filter(
        h => h.uuid !== historiaSelecionada.value
    );

    dialogExcluir.value = false;
    historiaSelecionada.value = null;
}

async function recarregarHistorias() {
  historias.value = await buscarHistoriasResumo();
}


function irParaHistoria(uuid) {
  router.push({
    name: "historias-show",
    params: { uuid },
  });
}

</script>
<style scoped>
.historias-page {
    min-height: 100vh;
    background: url("/backgrounds/cadastrar-bg.svg") center/cover no-repeat;
    padding: 100px 32px 0;
}

.painel {
    background: #fbf6e6;
    padding: 48px 56px;
    margin-top: 1.5%;
    border-radius: 48px 48px 0 0;
    min-height: calc(100vh - 128px);
}

/* BUSCA */
.search-bar {
    position: relative;
    width: 100%;
    margin-bottom: 56px;
}

.search-bar input {
    width: 100%;
    padding: 18px 56px 18px 28px;
    border-radius: 20px;
    border: 1.5px solid #000;
    font-weight: 600;
}

.search-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #555;
    pointer-events: none;
}

/* CARDS */
.cards-area {
    display: flex;
    gap: 32px;
}

.historia-card {
    position: relative;
    width: 260px;
    height: 150px;
    background: radial-gradient(circle at center, #efd56b, #c9a42f);
    border-radius: 22px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: inset 0 0 40px rgba(0, 0, 0, .15);
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
}

.historia-card:hover {
    transform: translateY(-4px);
}

/* ÍCONE MENU */
.menu-icon {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    z-index: 2;
}

/* MENU */
.menu-card {
    background: radial-gradient(circle at center, #efd56b, #c9a42f);
    border-radius: 14px;
    min-width: 130px;
}

.menu-card .v-list-item {
    font-weight: 600;
}

.menu-card .v-list-item:hover {
    background: rgba(0, 0, 0, .08);
}

.menu-card .danger {
    color: #8b0000;
}

/* TEXTO */
.card-title {
    text-align: center;
    font-weight: 700;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    font-weight: 700;
}

.tipo {
    text-transform: capitalize;
}

.menu-card {
    background: radial-gradient(circle at center, #efd56b, #c9a42f);
    border-radius: 14px;
    min-width: 130px;
}

.menu-list {
    background: transparent !important;
}

.menu-list .v-list-item:hover {
    background: rgba(0, 0, 0, 0.08);
}

.menu-list .danger {
    color: #8b0000;
}

/* ===== ANIMAÇÃO BASE ===== */
.reveal {
    opacity: 0;
    transform: translateY(24px);
    animation: fadeUp 0.8s ease-out forwards;
    animation-delay: var(--delay, 0s);
    will-change: transform, opacity;
}

/* ===== KEYFRAME ===== */
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.historias-page {
    animation: pageFadeUp 0.9s ease-out forwards;
}

/* ===== HOVER SUAVE (mantém identidade) ===== */
.historia-card {
    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.historia-card:hover {
    transform: translateY(-4px);
    box-shadow:
        inset 0 0 50px rgba(0, 0, 0, 0.25),
        0 18px 40px rgba(0, 0, 0, 0.35);
}

/* ===== MENU ICON FEEDBACK ===== */
.menu-icon {
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.menu-icon:hover {
    transform: scale(1.1);
    opacity: 0.85;
}

@media (max-width: 600px) {
  .historias-page {
    padding: 72px 12px 0;
  }

  .painel {
    padding: 24px 20px 32px;
    margin-top: 0;
    border-radius: 24px 24px 0 0;
    min-height: auto;
  }

  /* BUSCA */
  .search-bar {
    margin-bottom: 32px;
  }

  .search-bar input {
    padding: 16px 52px 16px 20px;
    border-radius: 18px;
  }

  /* CARDS EM COLUNA */
  .cards-area {
    flex-direction: column;
    gap: 20px;
  }

  .historia-card {
    width: 100%;
    height: 150px; /* mantém altura original */
  }

  /* MENU DE 3 PONTOS SEM QUEBRAR TOQUE */
  .menu-icon {
    top: 12px;
    right: 12px;
  }

  /* FOOTER DO CARD */
  .card-footer {
    font-size: 13px;
  }
}
</style>
