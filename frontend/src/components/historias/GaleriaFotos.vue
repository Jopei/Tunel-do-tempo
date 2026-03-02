<template>
    <v-dialog v-model="dialog" max-width="1200">
        <v-card class="galeria-card">
            <v-card-title class="titulo">
                Galeria da História
            </v-card-title>

            <v-card-text>
                <div v-if="!fotosCarregadas.length" class="sem-fotos">
                    Nenhuma imagem disponível.
                </div>

                <div v-else class="grid-fotos">
                    <div v-for="foto in fotosCarregadas" :key="foto.uuid" class="foto-item"
                        @click="ampliarFoto(foto.url)">
                        <img :src="foto.url" />
                    </div>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer />
                <v-btn @click="dialog = false">
                    Fechar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- VISUALIZADOR CENTRAL -->
    <v-dialog v-model="fotoSelecionada" fullscreen transition="dialog-bottom-transition">
        <v-card class="viewer-card">
            <v-icon class="fechar-viewer" @click="fotoSelecionada = null">
                mdi-close
            </v-icon>

            <img :src="fotoSelecionada" class="viewer-img" />
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { buscarFotoPorUuid } from "@/services/foto.show.service";

const props = defineProps({
    fotos: {
        type: Array,
        default: () => []
    },
    modelValue: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(["update:modelValue"]);

const dialog = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val)
});

const fotosCarregadas = ref([]);
const fotoSelecionada = ref(null);

watch(
    () => dialog.value,
    async (aberto) => {
        if (aberto && props.fotos.length) {
            fotosCarregadas.value = [];

            for (const uuid of props.fotos) {
                try {
                    const url = await buscarFotoPorUuid(uuid);
                    fotosCarregadas.value.push({ uuid, url });
                } catch (e) {
                    console.error("Erro ao carregar foto:", uuid);
                }
            }
        }
    }
);

function ampliarFoto(url) {
    fotoSelecionada.value = url;
}
</script>

<style scoped>
/* CARD PADRÃO TÚNEL DO TEMPO */
.galeria-card {
    background: #fbf6e6;
    border-radius: 32px;
    padding: 28px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, .25);
}

.titulo {
    font-weight: 800;
    font-size: 22px;
    text-align: center;
    color: #333;
    margin-bottom: 10px;
}

/* SEM FOTO */
.sem-fotos {
    text-align: center;
    font-weight: 700;
    padding: 40px;
    color: #777;
}

/* GRID */
.grid-fotos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 28px;
}

/* FOTO ITEM */
.foto-item {
    background: rgba(199, 164, 58, 0.12);
    border: 2px solid rgba(199, 164, 58, 0.35);
    border-radius: 22px;
    padding: 14px;
    cursor: pointer;

    transition: all .25s ease;
}

.foto-item:hover {
    background: rgba(199, 164, 58, 0.22);
    border-color: #c7a43a;
    transform: translateY(-8px);
    box-shadow: 0 18px 40px rgba(0, 0, 0, .18);
}

.foto-item img {
    width: 100%;
    border-radius: 16px;
    object-fit: cover;
    transition: transform .3s ease;
}

.foto-item:hover img {
    transform: scale(1.04);
}

/* ========================= */
/* VISUALIZADOR CENTRAL */
/* ========================= */

.viewer-card {
    background: rgba(0, 0, 0, 0.96);
    height: 100vh;
    width: 100vw;

    display: flex;
    justify-content: center;
    align-items: center;

    position: relative;
}

/* IMAGEM GRANDE */
.viewer-img {
    max-width: 92vw;
    max-height: 92vh;

    border-radius: 18px;
    box-shadow: 0 40px 100px rgba(0, 0, 0, .7);

    object-fit: contain;
}

/* BOTÃO FECHAR */
.fechar-viewer {
    position: absolute;
    top: 30px;
    right: 40px;

    font-size: 34px;
    color: #fff;

    cursor: pointer;
    transition: transform .2s ease, opacity .2s ease;
}

.fechar-viewer:hover {
    transform: scale(1.2);
    opacity: .8;
}

/* RESPONSIVO */
@media (max-width: 600px) {

  /* CARD GALERIA */
  .galeria-card {
    padding: 20px;
    border-radius: 24px 24px 0 0;
  }

  .grid-fotos {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  /* FOTO ITEM */
  .foto-item {
    padding: 10px;
  }

  /* ========================= */
  /* VIEWER FULLSCREEN MOBILE */
  /* ========================= */

  .viewer-card {
    padding: 20px;
    align-items: center;
  }

  .viewer-img {
    max-width: 95vw;
    max-height: 75vh;
    border-radius: 14px;
  }

  .fechar-viewer {
    top: 18px;
    right: 18px;
    font-size: 28px;
  }

}
</style>