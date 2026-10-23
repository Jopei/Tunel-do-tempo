<template>
  <v-dialog
    :model-value="modelValue"
    fullscreen
    :scrim="false"
    transition="fade-transition"
    aria-label="Visualizador de fotos"
    class="foto-lightbox-dialog"
    @update:model-value="(v) => !v && fechar()"
    @keydown="aoTecla"
  >
    <div
      class="lb fv-era tm-immersive"
      :class="{ 'lb-ocioso': ocioso, 'lb-com-painel': painel && !mobile, 'fv-filtro-on': filtroAntigo && !verOriginal }"
      role="dialog"
      aria-modal="true"
      :aria-label="`Foto ${indice + 1} de ${itens.length}`"
      @pointermove.passive="acordar"
      @focusin="acordar"
    >
      <header class="lb-topo">
        <v-btn icon variant="text" color="white" size="large" class="lb-voltar" aria-label="Voltar para as fotos" @click="fechar">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <div class="lb-titulo">
          <strong>{{ atual?.titulo || "Foto" }}</strong>
          <span>{{ rotuloDia(diaDaFoto(atual)) }}</span>
        </div>
        <div class="lb-acoes">
          <v-btn
            icon
            variant="text"
            color="white"
            class="lb-filtro"
            :aria-label="filtroAntigo ? 'Desligar filtro de foto antiga' : 'Aplicar filtro de foto antiga'"
            :aria-pressed="filtroAntigo"
            aria-keyshortcuts="f"
            title="Filtro antigo (F)"
            @click="filtroAntigo = !filtroAntigo"
          >
            <v-icon>{{ filtroAntigo ? "mdi-image-filter-vintage" : "mdi-image-filter-vintage-outline" }}</v-icon>
          </v-btn>
          <v-btn
            v-if="filtroAntigo"
            icon
            variant="text"
            color="white"
            class="lb-original"
            aria-label="Ver original"
            :aria-pressed="verOriginal"
            title="Ver original"
            @click="verOriginal = !verOriginal"
          >
            <v-icon>{{ verOriginal ? "mdi-eye" : "mdi-eye-outline" }}</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            color="white"
            class="lb-info"
            aria-label="Detalhes da foto"
            :aria-pressed="painel"
            aria-keyshortcuts="i"
            @click="alternarPainel"
          >
            <v-icon>mdi-information-outline</v-icon>
          </v-btn>
          <v-btn icon variant="text" color="white" aria-label="Copiar link" @click="$emit('compartilhar', atual)">
            <v-icon>mdi-link-variant</v-icon>
          </v-btn>
          <v-btn
            v-if="atual?.url"
            icon
            variant="text"
            color="white"
            aria-label="Baixar"
            :href="atual.url"
            target="_blank"
            rel="noopener noreferrer"
            download
          >
            <v-icon>mdi-download</v-icon>
          </v-btn>
          <v-btn v-if="logado" icon variant="text" color="white" aria-label="Adicionar ao álbum" @click="$emit('album', atual)">
            <v-icon>mdi-image-album</v-icon>
          </v-btn>
          <v-btn v-if="podeEditar" icon variant="text" color="white" aria-label="Excluir foto" aria-keyshortcuts="Delete" @click="$emit('excluir', atual)">
            <v-icon>mdi-delete-outline</v-icon>
          </v-btn>
          <v-btn icon variant="text" color="white" aria-label="Fechar" @click="fechar">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
      </header>

      <v-btn
        v-if="itens.length > 1"
        icon
        size="large"
        class="lb-seta esq"
        color="white"
        variant="tonal"
        aria-label="Foto anterior"
        :disabled="indice === 0"
        @click="ir(-1)"
      >
        <v-icon>mdi-chevron-left</v-icon>
      </v-btn>

      <div
        class="lb-palco"
        @touchstart.passive="tocarInicio"
        @touchend.passive="tocarFim"
        @pointerdown="comecarPan"
        @pointermove="moverPan"
        @pointerup="fimPan"
        @pointercancel="fimPan"
      >
        <!-- quadro: desloca a imagem pela metade do painel (transform, não layout) -->
        <div class="lb-quadro">
          <!-- cena: slide horizontal na troca de foto (GSAP, transform/opacity) -->
          <div ref="cenaRef" class="lb-cena">
            <img
              v-if="atual"
              :key="atual.uuid"
              ref="midiaRef"
              class="lb-midia fv-img"
              :class="{ ampliada: zoom > 1, arrastando: pan.ativo }"
              :style="estiloImagem"
              :src="atual.url"
              :alt="atual.titulo || `Foto de ${rotuloDia(diaDaFoto(atual))}`"
              draggable="false"
              @dblclick="duploClique"
            />
          </div>
        </div>
      </div>

      <v-btn
        v-if="itens.length > 1"
        icon
        size="large"
        class="lb-seta dir"
        color="white"
        variant="tonal"
        aria-label="Próxima foto"
        :disabled="indice === itens.length - 1"
        @click="ir(1)"
      >
        <v-icon>mdi-chevron-right</v-icon>
      </v-btn>

      <div class="lb-rodape" aria-live="polite">{{ indice + 1 }} / {{ itens.length }}</div>

      <aside
        v-if="atual"
        class="lb-painel"
        :class="{ 'lb-painel--aberto': painel }"
        :style="mobile ? { height: `${folhaVh}vh` } : null"
        :aria-hidden="!painel"
        aria-labelledby="lb-painel-titulo"
      >
        <div
          v-if="mobile"
          class="lb-alca"
          role="separator"
          aria-label="Arraste para ajustar a altura do painel"
          @pointerdown="comecarFolha"
          @pointermove="moverFolha"
          @pointerup="fimFolha"
          @pointercancel="fimFolha"
        ><span></span></div>
        <div class="lb-painel-corpo">
          <div class="lb-painel-cab">
            <h2 id="lb-painel-titulo" ref="tituloPainel" tabindex="-1">Sobre esta foto</h2>
            <v-btn icon variant="text" size="small" class="tm-touch" aria-label="Fechar detalhes" @click="alternarPainel">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </div>

          <!-- 1. Cabeçalho do post -->
          <div class="lb-post-item lb-autor" :style="{ '--i': 0 }">
            <template v-if="auth.logado && autor">
              <component
                :is="autor.slug || autor.uuid ? 'router-link' : 'span'"
                v-bind="autor.slug || autor.uuid ? { to: { name: 'membro', params: { slug: chaveMembro(autor) } } } : {}"
                class="lb-avatar-link"
                :aria-label="`Perfil de ${autor.nome}`"
                @click="fechar"
              >
                <v-avatar size="40" color="surface-variant">
                  <img v-if="autor.foto_url" :src="autor.foto_url" alt="" loading="lazy" />
                  <span v-else class="lb-iniciais" aria-hidden="true">{{ iniciais(autor.nome) }}</span>
                </v-avatar>
              </component>
            </template>
            <div class="lb-autor-texto">
              <strong v-if="auth.logado && autor">
                <router-link v-if="autor.slug || autor.uuid" :to="{ name: 'membro', params: { slug: chaveMembro(autor) } }" @click="fechar">{{ autor.nome }}</router-link>
                <template v-else>{{ autor.nome }}</template>
              </strong>
              <span class="lb-data-linha">
                <time :datetime="diaDaFoto(atual)" :title="dataCompleta(diaDaFoto(atual))">
                  {{ atual.data_foto ? "" : "enviada " }}{{ tempoRelativo(diaDaFoto(atual)) }} · {{ dataCompleta(diaDaFoto(atual)) }}
                </time>
                <small v-if="!atual.data_foto" class="lb-nota">(data de envio)</small>
              </span>
            </div>
            <v-btn v-if="podeEditar && !editandoData" size="small" variant="text" class="lb-editar-data" @click="editarData">Editar data</v-btn>
          </div>

          <form v-if="editandoData" class="lb-data lb-post-item" @submit.prevent="salvarData">
            <v-text-field v-model="form.data" type="date" label="Data da foto" density="comfortable" :max="hoje" hide-details :disabled="salvando" />
            <div class="lb-form-acoes">
              <v-btn variant="text" :disabled="salvando" @click="editandoData = false">Cancelar</v-btn>
              <v-btn v-if="atual.data_foto" variant="text" :disabled="salvando" @click="limparData">Limpar</v-btn>
              <v-btn variant="flat" color="primary" type="submit" :loading="salvando" :disabled="!form.data">Salvar</v-btn>
            </div>
          </form>

          <!-- 2. Legenda -->
          <form v-if="editandoTexto" class="lb-form lb-post-item" @submit.prevent="salvarTexto">
            <v-text-field v-model="form.titulo" label="Título" density="comfortable" hide-details autocomplete="off" :disabled="salvando" />
            <v-textarea v-model="form.descricao" label="Descrição" rows="3" auto-grow density="comfortable" hide-details :disabled="salvando" />
            <div class="lb-form-acoes">
              <v-btn variant="text" :disabled="salvando" @click="editandoTexto = false">Cancelar</v-btn>
              <v-btn variant="flat" color="primary" type="submit" :loading="salvando">Salvar</v-btn>
            </div>
          </form>
          <div v-else class="lb-post-item lb-legenda" :style="{ '--i': 1 }">
            <p class="lb-legenda-titulo">{{ atual.titulo || "Sem título" }}</p>
            <p v-if="atual.descricao" class="lb-legenda-desc">{{ atual.descricao }}</p>
            <v-btn v-if="podeEditar" icon size="small" variant="text" class="tm-touch" aria-label="Editar título e descrição" @click="editarTexto">
              <v-icon>mdi-pencil-outline</v-icon>
            </v-btn>
          </div>

          <!-- 3. Marcados -->
          <section v-if="auth.logado && (marcados.length || podeEditar)" class="lb-post-item lb-marcados" :style="{ '--i': 2 }" aria-labelledby="lb-marc-h">
            <h3 id="lb-marc-h" class="lb-sub">Estavam nesta foto</h3>
            <template v-if="!editandoMarcados">
              <ul v-if="marcados.length" class="lb-marc-lista">
                <li v-for="m in marcados" :key="m.uuid">
                  <router-link class="lb-marc" :to="{ name: 'membro', params: { slug: chaveMembro(m) } }" @click="fechar">
                    <v-avatar size="36" color="surface-variant" class="lb-marc-avatar">
                      <img v-if="m.foto_url" :src="m.foto_url" alt="" loading="lazy" />
                      <span v-else class="lb-iniciais" aria-hidden="true">{{ iniciais(m.nome) }}</span>
                    </v-avatar>
                    <span>{{ m.nome }}</span>
                  </router-link>
                </li>
              </ul>
              <p v-else class="lb-nota">Ninguém marcado ainda.</p>
              <v-btn v-if="podeEditar" size="small" variant="tonal" prepend-icon="mdi-account-multiple-plus-outline" class="mt-2" @click="editarMarcados">
                {{ marcados.length ? "Editar marcados" : "Marcar pessoas" }}
              </v-btn>
            </template>
            <form v-else class="lb-form" @submit.prevent="salvarMarcados">
              <v-autocomplete
                v-model="form.marcados"
                :items="membros"
                item-title="nome"
                item-value="uuid"
                label="Quem estava nesta foto?"
                multiple
                chips
                closable-chips
                density="comfortable"
                hide-details
                :loading="carregandoMembros"
                :disabled="salvando"
                no-data-text="Nenhum membro encontrado"
              />
              <div class="lb-form-acoes">
                <v-btn variant="text" :disabled="salvando" @click="editandoMarcados = false">Cancelar</v-btn>
                <v-btn variant="flat" color="primary" type="submit" :loading="salvando">Salvar</v-btn>
              </div>
            </form>
          </section>

          <!-- 4. Histórias vinculadas como posts + álbuns -->
          <section v-if="auth.logado && vinculos.historias.length" class="lb-post-item" :style="{ '--i': 3 }" aria-labelledby="lb-hist-h">
            <h3 id="lb-hist-h" class="lb-sub">Aparece em {{ vinculos.historias.length }} {{ vinculos.historias.length === 1 ? "história" : "histórias" }}</h3>
            <ul class="lb-hist-lista">
              <li v-for="h in vinculos.historias" :key="h.uuid">
                <router-link class="lb-hist hx-ano" :to="{ name: 'historias-show', params: { uuid: h.uuid } }" @click="fechar">
                  <span class="lb-hist-capa fv-moldura">
                    <img v-if="h.capa_url" :src="h.capa_url" alt="" loading="lazy" decoding="async" />
                    <v-icon v-else size="22" aria-hidden="true">mdi-book-open-page-variant-outline</v-icon>
                  </span>
                  <span class="lb-hist-texto">
                    <strong>{{ h.titulo }}</strong>
                    <small v-if="h.data_historia"><time :datetime="h.data_historia">{{ tempoRelativo(h.data_historia) }}</time></small>
                  </span>
                </router-link>
              </li>
            </ul>
          </section>
          <ul v-if="auth.logado && vinculos.albuns.length" class="lb-post-item lb-chips" :style="{ '--i': 4 }" aria-label="Álbuns desta foto">
            <li v-for="a in vinculos.albuns" :key="a.uuid">
              <router-link class="lb-chip" :to="{ name: 'fotos-album', params: { uuid: a.uuid } }" @click="fechar">
                <v-icon size="16" aria-hidden="true">mdi-image-multiple-outline</v-icon>{{ a.titulo }}
              </router-link>
            </li>
          </ul>

          <!-- 5. Ações -->
          <div class="lb-post-item lb-acoes-post" :style="{ '--i': 5 }" role="group" aria-label="Ações da foto">
            <v-btn variant="tonal" class="lb-acao-post" prepend-icon="mdi-share-variant-outline" @click="compartilhar">Compartilhar</v-btn>
            <v-btn v-if="atual.url" variant="tonal" class="lb-acao-post" prepend-icon="mdi-download" :href="atual.url" target="_blank" rel="noopener noreferrer" download>Baixar</v-btn>
            <v-btn v-if="logado" variant="tonal" class="lb-acao-post" prepend-icon="mdi-image-album" @click="$emit('album', atual)">Álbum</v-btn>
          </div>

          <!-- 6. Detalhes técnicos -->
          <details class="lb-post-item lb-detalhes" :style="{ '--i': 6 }">
            <summary>Detalhes</summary>
            <dl class="lb-dl">
              <div v-if="atual.largura && atual.altura" class="lb-campo">
                <dt>Dimensões</dt>
                <dd>{{ atual.largura }} × {{ atual.altura }} px</dd>
              </div>
              <div v-if="atual.created_at" class="lb-campo">
                <dt>Enviada em</dt>
                <dd>{{ formatarData(atual.created_at) }}</dd>
              </div>
              <div v-if="atual.tamanho_bytes" class="lb-campo">
                <dt>Tamanho</dt>
                <dd>{{ formatarTamanho(atual.tamanho_bytes) }}</dd>
              </div>
            </dl>
          </details>
        </div>
      </aside>
    </div>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onBeforeUnmount } from "vue";
import { useDisplay } from "vuetify";
import gsap from "gsap";
import { prefereMenosMovimento } from "@/composables/useTema";
import { atualizarFoto, buscarDetalhesFoto } from "@/services/foto.service";
import { diaDaFoto, rotuloDia } from "@/composables/useFotoDatas";
import { formatarData, iniciais } from "@/composables/useFormat";
import { formatarTamanho } from "@/composables/useArquivos";
import { filtroAntigo, tempoRelativo, dataCompleta } from "@/composables/useEraFotosVideos";
import { listarMembrosPublicos, chaveMembro } from "@/services/membro.service";
import { atualizarMarcadosFoto } from "@/services/foto.service";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useAuthStore } from "@/stores/auth";

const props = defineProps({
  modelValue: Boolean,
  itens: { type: Array, default: () => [] },
  inicio: { type: Number, default: 0 },
  podeEditar: Boolean,
  logado: Boolean,
});
const emit = defineEmits(["update:modelValue", "mudou", "compartilhar", "album", "excluir", "editado"]);

const { smAndDown } = useDisplay();
const mobile = computed(() => smAndDown.value);
const { tratar } = useApiError();
const notify = useNotify();

const indice = ref(0);
const painel = ref(false);
const ocioso = ref(false);
const zoom = ref(1);
const origem = reactive({ x: 50, y: 50 });
const pan = reactive({ ativo: false, x: 0, y: 0, ix: 0, iy: 0, px: 0, py: 0 });
const tituloPainel = ref(null);
const folhaVh = ref(45);
const hoje = new Date().toISOString().slice(0, 10);

const editandoTexto = ref(false);
const editandoData = ref(false);
const salvando = ref(false);
const form = reactive({ titulo: "", descricao: "", data: "", marcados: [] });
const verOriginal = ref(false);
const editandoMarcados = ref(false);
const membros = ref([]);
const carregandoMembros = ref(false);

const midiaRef = ref(null);
const cenaRef = ref(null);
let fechando = false;

let gatilho = null;
let timerOcioso = null;
let toque = null;

const auth = useAuthStore();
const atual = computed(() => props.itens[indice.value]);

/* Álbuns e histórias da foto: só vêm em GET /fotos/{uuid}/details; busca com o painel aberto. */
const vinculos = reactive({ albuns: [], historias: [], marcados: null, autor: null });
const autor = computed(() => atual.value?.cadastrado_por ?? vinculos.autor);
const marcados = computed(() => vinculos.marcados ?? atual.value?.marcados ?? []);
const cacheVinculos = new Map();
watch(
  () => [painel.value, atual.value?.uuid],
  async ([aberto, uuid]) => {
    vinculos.albuns = [];
    vinculos.historias = [];
    vinculos.marcados = null;
    vinculos.autor = null;
    if (!aberto || !uuid || !auth.logado) return; // /fotos/{uuid}/details exige login
    try {
      if (!cacheVinculos.has(uuid)) {
        const d = await buscarDetalhesFoto(uuid);
        cacheVinculos.set(uuid, { albuns: d.albuns ?? [], historias: d.historias ?? [], marcados: d.marcados ?? null, autor: d.cadastrado_por ?? null });
      }
    } catch {
      return; // vínculos são complementares: falha silenciosa
    }
    if (atual.value?.uuid !== uuid) return;
    const v = cacheVinculos.get(uuid);
    vinculos.albuns = v.albuns;
    vinculos.historias = v.historias;
    vinculos.marcados = v.marcados;
    vinculos.autor = v.autor;
  },
);
const estiloImagem = computed(() => ({
  transformOrigin: `${origem.x}% ${origem.y}%`,
  transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom.value})`,
}));

/* ---------- abrir / navegar ---------- */
watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      gatilho = document.activeElement;
      indice.value = Math.min(Math.max(props.inicio, 0), Math.max(props.itens.length - 1, 0));
      painel.value = false;
      resetarZoom();
      cancelarEdicao();
      acordar();
      emit("mudou", atual.value);
      precarregar();
      animarAbertura();
    } else {
      clearTimeout(timerOcioso);
      fechando = false;
      nextTick(() => gatilho?.focus?.());
    }
  },
);

// Itens removidos por fora (excluir): mantém o índice válido ou fecha.
watch(
  () => props.itens.length,
  (n) => {
    if (!props.modelValue) return;
    if (!n) return fechar();
    if (indice.value >= n) indice.value = n - 1;
    emit("mudou", atual.value);
  },
);

function ir(delta) {
  if (fechando) return;
  const novo = indice.value + delta;
  if (novo < 0 || novo >= props.itens.length) return;
  indice.value = novo;
  resetarZoom();
  cancelarEdicao();
  emit("mudou", atual.value);
  nextTick(() => {
    precarregar();
    animarTroca(delta);
  });
}

function precarregar() {
  [-1, 1].forEach((d) => {
    const it = props.itens[indice.value + d];
    if (it?.url) new Image().src = it.url;
  });
}

/* ---------- animação: abrir/fechar a partir da miniatura (FLIP) e troca com slide ---------- */
// Miniatura da grade para a foto atual (o grid continua montado sob o overlay).
function miniaturaDe(uuid) {
  if (!uuid) return null;
  return document.querySelector(`.foto-item[data-uuid="${CSS.escape(uuid)}"] .foto-img`);
}
function visivelNaTela(r) {
  return r.width > 0 && r.height > 0 && r.bottom > 0 && r.right > 0 && r.top < window.innerHeight && r.left < window.innerWidth;
}
function ajustesFlip(origem, alvo) {
  const s = Math.min(origem.width / alvo.width, origem.height / alvo.height);
  return {
    x: origem.left + origem.width / 2 - (alvo.left + alvo.width / 2),
    y: origem.top + origem.height / 2 - (alvo.top + alvo.height / 2),
    scale: s,
  };
}

async function animarAbertura() {
  if (prefereMenosMovimento()) return;
  await nextTick();
  // Limpa resto de uma animação anterior (a imagem pode ser reaproveitada) antes de medir.
  if (midiaRef.value) gsap.set(midiaRef.value, { clearProps: "transform,opacity" });
  await new Promise((r) => requestAnimationFrame(r));
  const img = midiaRef.value;
  if (!img || !props.modelValue) return;
  const origem = miniaturaDe(atual.value?.uuid)?.getBoundingClientRect();
  const alvo = img.getBoundingClientRect();
  const controles = ".lb-topo, .lb-rodape, .lb-seta";
  if (!origem || !visivelNaTela(origem) || !img.complete || !alvo.width) {
    gsap.fromTo(img, { opacity: 0 }, { opacity: 1, duration: 0.25, ease: "power2.out", clearProps: "opacity" });
    gsap.fromTo(controles, { opacity: 0 }, { opacity: 1, duration: 0.25, clearProps: "opacity" });
    return;
  }
  const { x, y, scale } = ajustesFlip(origem, alvo);
  gsap.fromTo(
    img,
    { x, y, scale, opacity: 0.85 },
    { x: 0, y: 0, scale: 1, opacity: 1, duration: 0.3, ease: "power3.out", clearProps: "transform,opacity" },
  );
  gsap.fromTo(controles, { opacity: 0 }, { opacity: 1, duration: 0.25, ease: "power1.out", clearProps: "opacity" });
}

function animarTroca(delta) {
  const cena = cenaRef.value;
  if (!cena || !delta || prefereMenosMovimento()) return;
  gsap.fromTo(
    cena,
    { x: delta * 48, opacity: 0 },
    { x: 0, opacity: 1, duration: 0.2, ease: "power2.out", clearProps: "transform,opacity" },
  );
}

function fechar() {
  if (fechando || !props.modelValue) return;
  const img = midiaRef.value;
  const origem = miniaturaDe(atual.value?.uuid)?.getBoundingClientRect();
  const alvo = img?.getBoundingClientRect();
  // Com zoom o retângulo não é o da foto inteira: fecha sem o voo.
  if (!img || !origem || !visivelNaTela(origem) || !alvo?.width || zoom.value > 1 || prefereMenosMovimento()) {
    emit("update:modelValue", false);
    return;
  }
  fechando = true;
  const { x, y, scale } = ajustesFlip(origem, alvo);
  gsap.to(".lb-topo, .lb-rodape, .lb-seta", { opacity: 0, duration: 0.15, ease: "power1.in" });
  gsap.to(img, {
    x,
    y,
    scale,
    opacity: 0.4,
    duration: 0.28,
    ease: "power2.in",
    onComplete: () => {
      fechando = false;
      emit("update:modelValue", false);
    },
  });
}

/* ---------- ociosidade (setas somem em 2 s) ---------- */
function acordar() {
  ocioso.value = false;
  clearTimeout(timerOcioso);
  timerOcioso = setTimeout(() => (ocioso.value = true), 2000);
}

/* ---------- teclado ---------- */
function aoTecla(e) {
  acordar();
  const alvo = e.target;
  const emCampo = alvo?.tagName === "INPUT" || alvo?.tagName === "TEXTAREA" || alvo?.isContentEditable;
  if (emCampo) return;
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  switch (e.key) {
    case "ArrowLeft":
      ir(-1);
      break;
    case "ArrowRight":
      ir(1);
      break;
    case "i":
    case "I":
      alternarPainel();
      break;
    case "f":
    case "F":
      filtroAntigo.value = !filtroAntigo.value;
      break;
    case "Delete":
      if (props.podeEditar) emit("excluir", atual.value);
      break;
    case "+":
    case "=":
      ampliar(0.5);
      break;
    case "-":
    case "_":
      ampliar(-0.5);
      break;
    default:
  }
}

/* ---------- zoom e arrasto ---------- */
function resetarZoom() {
  zoom.value = 1;
  pan.x = pan.y = 0;
  origem.x = origem.y = 50;
}
function ampliar(passo) {
  zoom.value = Math.min(4, Math.max(1, zoom.value + passo));
  if (zoom.value === 1) pan.x = pan.y = 0;
}
function duploClique(e) {
  if (zoom.value > 1) return resetarZoom();
  const r = e.currentTarget.getBoundingClientRect();
  origem.x = ((e.clientX - r.left) / r.width) * 100;
  origem.y = ((e.clientY - r.top) / r.height) * 100;
  zoom.value = 2;
}
function comecarPan(e) {
  if (zoom.value <= 1 || e.target.tagName !== "IMG") return;
  pan.ativo = true;
  pan.ix = e.clientX;
  pan.iy = e.clientY;
  pan.px = pan.x;
  pan.py = pan.y;
  e.currentTarget.setPointerCapture?.(e.pointerId);
}
function moverPan(e) {
  if (!pan.ativo) return;
  pan.x = pan.px + (e.clientX - pan.ix);
  pan.y = pan.py + (e.clientY - pan.iy);
}
function fimPan() {
  pan.ativo = false;
}

/* ---------- gestos de toque ---------- */
function tocarInicio(e) {
  toque = e.touches.length === 1 && zoom.value === 1 ? { x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
}
function tocarFim(e) {
  if (!toque) return;
  const dx = e.changedTouches[0].clientX - toque.x;
  const dy = e.changedTouches[0].clientY - toque.y;
  toque = null;
  if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) ir(dx < 0 ? 1 : -1);
  else if (dy > 110 && dy > Math.abs(dx)) fechar();
}

/* ---------- painel de detalhes ---------- */
function alternarPainel() {
  painel.value = !painel.value;
  if (painel.value) {
    folhaVh.value = 45;
    nextTick(() => setTimeout(() => tituloPainel.value?.focus(), 30));
  } else {
    cancelarEdicao();
  }
}

let folha = null;
function comecarFolha(e) {
  folha = true;
  e.currentTarget.setPointerCapture?.(e.pointerId);
}
function moverFolha(e) {
  if (!folha) return;
  folhaVh.value = Math.min(90, Math.max(10, ((window.innerHeight - e.clientY) / window.innerHeight) * 100));
}
function fimFolha() {
  if (!folha) return;
  folha = null;
  if (folhaVh.value < 28) painel.value = false;
  else folhaVh.value = folhaVh.value > 68 ? 90 : 45;
}

/* ---------- edição ---------- */
function cancelarEdicao() {
  editandoTexto.value = false;
  editandoData.value = false;
  editandoMarcados.value = false;
}

async function editarMarcados() {
  form.marcados = marcados.value.map((m) => m.uuid);
  editandoMarcados.value = true;
  if (!membros.value.length) {
    carregandoMembros.value = true;
    try {
      membros.value = await listarMembrosPublicos();
    } catch {
      notify.erro("Não foi possível carregar a lista de membros.");
    } finally {
      carregandoMembros.value = false;
    }
  }
}
async function salvarMarcados() {
  const foto = atual.value;
  salvando.value = true;
  try {
    await atualizarMarcadosFoto(foto.uuid, form.marcados);
    const novos = form.marcados
      .map((u) => membros.value.find((m) => m.uuid === u) ?? marcados.value.find((m) => m.uuid === u))
      .filter(Boolean)
      .map((m) => ({ uuid: m.uuid, nome: m.nome, slug: m.slug ?? null, foto_url: m.foto_url ?? null }));
    vinculos.marcados = novos;
    const c = cacheVinculos.get(foto.uuid);
    if (c) c.marcados = novos;
    emit("editado", foto, { marcados: novos });
    notify.sucesso("Marcações atualizadas.");
    editandoMarcados.value = false;
  } catch (e) {
    tratar(e);
  } finally {
    salvando.value = false;
  }
}

function compartilhar() {
  emit("compartilhar", atual.value);
}
function editarTexto() {
  form.titulo = atual.value.titulo ?? "";
  form.descricao = atual.value.descricao ?? "";
  editandoTexto.value = true;
}
function editarData() {
  form.data = atual.value.data_foto ?? diaDaFoto(atual.value);
  editandoData.value = true;
}

async function persistir(patch, mensagem) {
  const foto = atual.value;
  salvando.value = true;
  try {
    await atualizarFoto(foto.uuid, patch);
    emit("editado", foto, patch);
    notify.sucesso(mensagem);
    cancelarEdicao();
  } catch (e) {
    tratar(e);
  } finally {
    salvando.value = false;
  }
}
const salvarTexto = () => persistir({ titulo: form.titulo.trim() || null, descricao: form.descricao.trim() || null }, "Foto atualizada.");
const salvarData = () => persistir({ data_foto: form.data }, "Data atualizada.");
const limparData = () => persistir({ data_foto: null }, "Data removida; vale a data de envio.");

onBeforeUnmount(() => {
  clearTimeout(timerOcioso);
  gsap.killTweensOf([midiaRef.value, cenaRef.value, ".lb-topo", ".lb-rodape", ".lb-seta"]);
});
</script>

<style scoped>
.lb {
  position: relative;
  width: 100%;
  height: 100dvh;
  background: rgba(11, 9, 5, 0.96);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.lb-topo {
  position: absolute;
  inset: 0 0 auto 0;
  z-index: 4;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 8px;
  background: linear-gradient(rgba(0, 0, 0, 0.65), transparent);
}
.lb-com-painel .lb-topo {
  right: 360px;
}
.lb-titulo {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
.lb-titulo strong,
.lb-titulo span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.lb-titulo span {
  font-size: var(--tm-fs-sm);
  opacity: 0.8;
}
.lb-acoes {
  display: flex;
  align-items: center;
}
.lb-palco {
  position: absolute;
  inset: 0;
  padding: 64px 16px 48px;
  overflow: hidden;
  touch-action: pan-y;
}
.lb-quadro {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  /* Painel aberto (desktop): imagem recentra no espaço restante, deslizando. */
  transition: transform 250ms var(--tm-ease);
}
.lb-com-painel .lb-quadro {
  transform: translateX(-180px);
}
.lb-cena {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.lb-midia {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  user-select: none;
  transform-origin: 50% 50%;
}
.lb-midia.ampliada {
  cursor: grab;
}
.lb-midia.arrastando {
  cursor: grabbing;
}
.lb-seta {
  position: absolute;
  top: 50%;
  z-index: 3;
  width: 48px;
  height: 48px;
  margin-top: -24px;
  transition: opacity var(--tm-dur-base) var(--tm-ease);
}
.lb-seta.esq {
  left: 8px;
}
.lb-seta.dir {
  right: 8px;
}
.lb-com-painel .lb-seta.dir {
  right: 368px;
}
.lb-ocioso .lb-seta:not(:focus-visible) {
  opacity: 0;
}
.lb-rodape {
  position: absolute;
  bottom: 12px;
  left: 0;
  right: 0;
  text-align: center;
  font-size: var(--tm-fs-sm);
  pointer-events: none;
}
.lb-com-painel .lb-rodape {
  right: 360px;
}

/* Painel de detalhes: coluna direita (desktop) / folha inferior (mobile) */
.lb-painel {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 5;
  width: 360px;
  background: var(--tm-paper);
  color: var(--tm-ink);
  transform: translateX(100%);
  visibility: hidden;
  transition: transform var(--tm-dur-base) var(--tm-ease), visibility 0s linear var(--tm-dur-base);
  overflow-y: auto;
}
.lb-painel--aberto {
  transform: translateX(0);
  visibility: visible;
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.lb-painel-corpo {
  padding: 8px 20px 24px;
}
.lb-painel-cab {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 56px;
}
.lb-painel-cab h2 {
  margin: 0;
  font-size: var(--tm-fs-xl);
}
.lb-painel-cab h2:focus {
  outline: none;
}
.lb-dl {
  margin: 0;
}
.lb-campo {
  padding: 12px 0;
  border-bottom: 1px solid var(--tm-border);
}
.lb-campo dt {
  font-size: var(--tm-fs-xs);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--tm-ink-muted);
  margin-bottom: 2px;
}
.lb-campo dd {
  margin: 0;
  overflow-wrap: anywhere;
}
.lb-links {
  margin: 0;
  padding: 0;
  list-style: none;
}
.lb-links a {
  color: var(--tm-gold-text);
}
.lb-nota {
  color: var(--tm-ink-muted);
}
.lb-form,
.lb-data {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 8px;
}
.lb-form-acoes {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
.lb-alca {
  display: grid;
  place-items: center;
  height: 24px;
  cursor: ns-resize;
  touch-action: none;
}
.lb-alca span {
  width: 40px;
  height: 4px;
  border-radius: 2px;
  background: var(--tm-border);
}

/* "Foto postada": painel como post. Ganchos motion 8 (.lb-post-item com --i) e 9 (.lb-midia filter). */
.lb-midia {
  transition: filter 150ms var(--tm-ease);
}
.lb-post-item {
  margin-top: 14px;
}
.lb-autor {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 4px;
}
.lb-avatar-link {
  flex: none;
  border-radius: 50%;
  display: inline-flex;
}
.lb-avatar-link :deep(img),
.lb-marc-avatar :deep(img),
.lb-marc-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.lb-iniciais {
  font-weight: 700;
  font-size: 0.85rem;
}
.lb-autor-texto {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  line-height: 1.3;
}
.lb-autor-texto a {
  color: var(--tm-ink);
  text-decoration: none;
}
.lb-autor-texto a:hover {
  text-decoration: underline;
}
.lb-data-linha {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
}
.lb-editar-data {
  flex: none;
  min-height: 44px;
  color: var(--tm-gold-text);
}
.lb-legenda {
  position: relative;
  padding-right: 44px;
}
.lb-legenda > .v-btn {
  position: absolute;
  top: -6px;
  right: -6px;
}
.lb-legenda-titulo {
  margin: 0;
  font-family: var(--tm-font-title);
  font-weight: 600;
  font-size: var(--tm-fs-xl);
  overflow-wrap: anywhere;
}
.lb-legenda-desc {
  margin: 4px 0 0;
  overflow-wrap: anywhere;
  white-space: pre-line;
}
.lb-sub {
  margin: 0 0 8px;
  font-family: var(--tm-font-body);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--tm-ink-muted);
}
.lb-marc-lista,
.lb-hist-lista,
.lb-chips {
  list-style: none;
  margin: 0;
  padding: 0;
}
.lb-marc-lista {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 12px;
}
.lb-marc {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  color: var(--tm-ink);
  text-decoration: none;
}
.lb-marc:hover span {
  text-decoration: underline;
}
.lb-hist-lista {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.lb-hist {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 64px;
  color: var(--tm-ink);
  text-decoration: none;
}
.lb-hist-capa {
  flex: none;
  width: 64px;
  height: 64px;
  display: grid;
  place-items: center;
  border-width: 3px;
  border-radius: 8px;
  color: var(--hx-ano-cor);
}
.lb-hist-capa img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.lb-hist-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.3;
}
.lb-hist-texto strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.lb-hist-texto small {
  color: var(--tm-ink-muted);
}
.lb-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.lb-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 36px;
  padding: 0 12px;
  border-radius: var(--tm-radius-pill);
  background: var(--tm-paper-2);
  color: var(--tm-gold-text);
  text-decoration: none;
  font-size: var(--tm-fs-sm);
}
.lb-acoes-post {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: 1fr;
  gap: 8px;
}
.lb-acao-post {
  min-height: 44px;
  min-width: 0;
}
.lb-detalhes summary {
  min-height: 44px;
  display: flex;
  align-items: center;
  cursor: pointer;
  font-weight: 600;
  color: var(--tm-gold-text);
}
@media (max-width: 959px) {
  .lb-acao-post :deep(.v-btn__prepend) {
    margin-inline: 0 4px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .lb-midia {
    transition: none;
  }
}
html[data-reduzir="1"] .lb-midia {
  transition: none;
}

@media (max-width: 959px) {
  .lb-com-painel .lb-topo,
  .lb-com-painel .lb-rodape {
    right: 0;
  }
  .lb-com-painel .lb-seta.dir {
    right: 8px;
  }
  .lb-painel {
    top: auto;
    left: 0;
    width: 100%;
    border-radius: var(--tm-radius-md) var(--tm-radius-md) 0 0;
    transform: translateY(100%);
    box-shadow: var(--tm-shadow-lg);
  }
  .lb-painel--aberto {
    transform: translateY(0);
  }
  .lb-seta {
    width: 44px;
    height: 44px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .lb-painel,
  .lb-painel--aberto,
  .lb-quadro,
  .lb-seta {
    transition: none;
  }
}
html[data-reduzir="1"] .lb-quadro {
  transition: none;
}
</style>
