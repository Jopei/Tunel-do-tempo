<template>
  <section v-if="temConteudo || carregando || erro" class="fv-dest" aria-labelledby="fv-dest-h">
    <div class="fv-dest-topo">
      <h2 id="fv-dest-h" class="sr-only">Destaques das fotos</h2>
      <v-btn
        variant="text"
        size="small"
        class="fv-dest-alternar"
        :prepend-icon="oculto ? 'mdi-chevron-down' : 'mdi-chevron-up'"
        :aria-expanded="!oculto"
        aria-controls="fv-dest-corpo"
        @click="oculto = !oculto"
      >
        {{ oculto ? "Mostrar destaques" : "Ocultar destaques" }}
      </v-btn>
    </div>

    <div v-show="!oculto" id="fv-dest-corpo" class="fv-dest-corpo">
      <div v-if="carregando" class="fv-dest-esq" aria-hidden="true">
        <span v-for="n in 6" :key="n" class="fv-dest-esq-item hx-skeleton"></span>
      </div>

      <template v-else>
        <p v-if="erro" class="fv-dest-erro" role="status">
          Não deu para carregar todos os destaques.
          <v-btn size="small" variant="text" @click="carregar">Tentar de novo</v-btn>
        </p>
        <!-- Neste dia -->
        <div v-if="nesteDia.length" class="fv-bloco fv-bloco--neste" data-destaque="neste-dia">
          <h2 class="fv-bloco-titulo">{{ tituloNesteDia }}</h2>
          <TmCarrossel class="fv-tira" role="region" tabindex="0" aria-label="Fotos deste dia em anos anteriores">
            <button
              v-for="f in nesteDia"
              :key="f.uuid"
              type="button"
              class="fv-card-foto fv-vinheta"
              :aria-label="`Foto de ${dataCompleta(diaDaFoto(f))}, ${tempoRelativo(diaDaFoto(f))}`"
              @click="$emit('abrir-foto', nesteDia, f.uuid)"
            >
              <img class="fv-img" :src="urlMiniatura(f)" alt="" loading="lazy" decoding="async" />
              <span class="fv-card-selo">
                <time :datetime="diaDaFoto(f)">{{ tempoRelativo(diaDaFoto(f)) }}</time>
              </span>
              <span class="fv-card-legenda">{{ dataCompleta(diaDaFoto(f)) }}</span>
            </button>
          </TmCarrossel>
        </div>

        <!-- Memórias por ano -->
        <div v-if="memorias.length" class="fv-bloco fv-bloco--anos" data-destaque="memorias">
          <h2 class="fv-bloco-titulo">Memórias por ano</h2>
          <TmCarrossel class="fv-tira" role="region" tabindex="0" aria-label="Memórias por ano">
            <button
              v-for="m in memorias"
              :key="m.chave"
              type="button"
              class="fv-card-ano hx-ano fv-vinheta"
              :style="{ '--hx-ano-h': m.hue }"
              :aria-label="`${m.rotulo}: ${pluralFotos(m.total)}. Ir para este capítulo`"
              @click="$emit('ir-capitulo', m.chave)"
            >
              <span class="fv-card-capa fv-moldura">
                <img v-if="m.capa" class="fv-img" :src="m.capa" alt="" loading="lazy" decoding="async" />
                <v-icon v-else size="40" class="fv-card-capa-vazia" aria-hidden="true">mdi-image-outline</v-icon>
              </span>
              <span class="fv-card-ano-texto">
                <strong class="fv-card-ano-num">{{ m.ano ?? m.rotulo }}</strong>
                <span>{{ pluralFotos(m.total) }}</span>
              </span>
              <span class="fv-card-filete" aria-hidden="true"></span>
            </button>
          </TmCarrossel>
        </div>

        <!-- Álbuns recentes -->
        <div v-if="logado && albuns.length" class="fv-bloco fv-bloco--albuns" data-destaque="albuns">
          <h2 class="fv-bloco-titulo">Álbuns recentes</h2>
          <TmCarrossel class="fv-tira" role="region" tabindex="0" aria-label="Álbuns recentes">
            <AlbumCard v-for="a in albuns" :key="a.uuid" :album="a" class="fv-album-mini" />
            <router-link :to="{ name: 'fotos-albuns' }" class="fv-ver-todos">
              <v-icon size="28" aria-hidden="true">mdi-image-multiple-outline</v-icon>
              <span>Ver todos os álbuns</span>
            </router-link>
          </TmCarrossel>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { listarFotosAnos, listarFotosNesteDia } from "@/services/foto.service";
import { listarAlbuns } from "@/services/album.service";
import { diaDaFoto, urlMiniatura } from "@/composables/useFotoDatas";
import { lerPref, gravarPref, chaveDoAno, metaCapitulo, ordemCapitulos, pluralFotos, tempoRelativo, dataCompleta, haAnos } from "@/composables/useEraFotosVideos";
import TmCarrossel from "@/components/ui/TmCarrossel.vue";
import AlbumCard from "./AlbumCard.vue";

const props = defineProps({
  logado: Boolean,
  ordem: { type: String, default: "recentes" },
});
defineEmits(["abrir-foto", "ir-capitulo"]);

const CHAVE = "tunel-fotos-destaques";
const oculto = ref(lerPref(CHAVE, "0") === "1");
watch(oculto, (v) => gravarPref(CHAVE, v ? "1" : "0"));

const carregando = ref(true);
const erro = ref(false);
const anos = ref([]);
const nesteDiaGrupos = ref([]);
const albuns = ref([]);

async function carregar() {
  carregando.value = true;
  erro.value = false;
  const [a, n, al] = await Promise.allSettled([
    listarFotosAnos(),
    props.logado ? listarFotosNesteDia(24) : [],
    props.logado ? listarAlbuns(1, { per_page: 6 }).then((r) => r.data ?? []) : [],
  ]);
  anos.value = a.status === "fulfilled" ? a.value : [];
  nesteDiaGrupos.value = n.status === "fulfilled" ? n.value : [];
  albuns.value = al.status === "fulfilled" ? al.value : [];
  erro.value = [a, n, al].some((r) => r.status === "rejected");
  carregando.value = false;
}
onMounted(carregar);

const nesteDia = computed(() => nesteDiaGrupos.value.flatMap((g) => (g.fotos ?? []).map((f) => ({ ...f, _ano: g.ano }))));
const tituloNesteDia = computed(() =>
  nesteDiaGrupos.value.length === 1 ? `Neste dia, ${haAnos(nesteDiaGrupos.value[0].ano)}` : "Neste dia, há alguns anos",
);

const memorias = computed(() => {
  const mapa = new Map();
  for (const a of anos.value) {
    if (a.ano == null) continue;
    const k = chaveDoAno(a.ano);
    const atual = mapa.get(k) ?? { ...metaCapitulo(k), total: 0, capa: null };
    atual.total += Number(a.total) || 0;
    atual.capa ??= a.capa?.thumbnail_url || a.capa?.url || null; // `capa` é opcional no contrato
    mapa.set(k, atual);
  }
  return ordemCapitulos(props.ordem)
    .filter((k) => mapa.has(k))
    .map((k) => mapa.get(k));
});

const temConteudo = computed(() => nesteDia.value.length > 0 || memorias.value.length > 0 || (props.logado && albuns.value.length > 0));
</script>

<style scoped>
.fv-dest {
  padding: 8px 12px 0;
  border-bottom: 1px solid var(--tm-border);
}
.fv-dest-topo {
  display: flex;
  justify-content: flex-end;
}
.fv-dest-alternar {
  min-height: 44px;
  color: var(--tm-gold-text);
}
.fv-dest-erro {
  margin: 0;
  padding: 0 4px;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.fv-dest-corpo {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-bottom: 8px;
}
.fv-bloco-titulo {
  margin: 0 0 4px 4px;
  font-family: var(--tm-font-title);
  font-size: 20px;
  font-weight: 600;
  color: var(--tm-ink);
}
.fv-dest-esq {
  display: flex;
  gap: 12px;
  overflow: hidden;
  height: 220px;
}
.fv-dest-esq-item {
  flex: none;
  width: 176px;
  height: 100%;
  border-radius: 12px;
}

/* Cartão "neste dia" 4:5 */
.fv-card-foto {
  position: relative;
  width: 176px;
  height: 220px;
  padding: 0;
  border: 0;
  border-radius: 12px;
  overflow: hidden;
  background: var(--tm-paper-2);
  cursor: pointer;
  display: block;
}
.fv-card-foto img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.fv-card-foto:focus-visible,
.fv-card-ano:focus-visible,
.fv-ver-todos:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: 2px;
}
.fv-card-selo {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 2;
  padding: 2px 8px;
  border-radius: var(--tm-radius-pill);
  background: var(--fv-player-barra);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
}
.fv-card-legenda {
  position: absolute;
  inset: auto 0 0 0;
  z-index: 2;
  padding: 28px 10px 8px;
  background: var(--fv-scrim);
  color: #fff;
  font-size: 13px;
  text-align: left;
}

/* Cartão "Memórias por ano" */
.fv-card-ano {
  position: relative;
  width: 176px;
  height: 220px;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
  display: block;
  text-align: left;
}
.fv-card-capa {
  position: absolute;
  inset: 0 0 6px 0;
  display: grid;
  place-items: center;
  color: var(--hx-ano-cor);
}
.fv-card-capa img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.fv-card-ano-texto {
  position: absolute;
  inset: auto 6px 12px 6px;
  z-index: 2;
  display: flex;
  flex-direction: column;
  padding: 24px 8px 4px;
  border-radius: 0 0 8px 8px;
  background: var(--fv-scrim);
  color: #fff;
  font-size: 13px;
}
.fv-card-ano-num {
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: 2rem;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}
.fv-card-filete {
  position: absolute;
  inset: auto 6px 0 6px;
  height: 4px;
  border-radius: 2px;
  background: var(--hx-ano-cor);
}
.fv-ver-todos {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 160px;
  min-height: 120px;
  border: 2px dashed var(--tm-border);
  border-radius: 12px;
  color: var(--tm-gold-text);
  font-weight: 600;
  text-align: center;
  text-decoration: none;
}
.fv-album-mini {
  width: 220px;
}

@media (max-width: 599px) {
  .fv-dest-esq,
  .fv-card-foto,
  .fv-card-ano {
    height: 168px;
  }
  .fv-card-foto,
  .fv-card-ano,
  .fv-dest-esq-item {
    width: 132px;
  }
  .fv-card-ano-num {
    font-size: 1.5rem;
  }
  .fv-album-mini {
    width: 160px;
  }
  .fv-ver-todos {
    width: 132px;
  }
}
</style>
