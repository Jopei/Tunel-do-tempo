<template>
  <section class="cadastrar-historia-page tm-immersive">
    <div v-if="criada" class="card sucesso">
      <v-icon size="56" color="success" aria-hidden="true">mdi-check-circle-outline</v-icon>
      <h1 class="tm-title">História guardada.</h1>
      <p>Obrigado por manter a memória da rua viva.</p>
      <div class="acoes-sucesso">
        <v-btn :to="{ name: 'historias-show', params: { uuid: criada } }" variant="flat" color="primary" size="large">Ver história</v-btn>
        <v-btn variant="tonal" size="large" @click="criada = null">Contar outra</v-btn>
      </div>
    </div>

    <form v-else class="card" novalidate @submit.prevent="salvar">
      <h1 class="tm-title">Contar uma história</h1>

      <v-alert v-if="rascunhoPendente" type="info" variant="tonal" class="mb-4" role="status">
        <div class="alerta-rascunho">
          <span>Você tem um rascunho de {{ formatarDataHora(rascunhoPendente.quando) }}.</span>
          <span class="botoes">
            <v-btn size="small" variant="flat" color="primary" @click="restaurar">Continuar</v-btn>
            <v-btn size="small" variant="text" @click="descartarRascunho">Descartar</v-btn>
          </span>
        </div>
      </v-alert>

      <div class="grid">
        <v-text-field
          v-model="titulo"
          label="Título *"
          counter="255"
          maxlength="255"
          :error-messages="erros.titulo"
          :disabled="loading"
          autocomplete="off"
        />
        <v-text-field
          v-model="dataHistoria"
          label="Data da história *"
          type="date"
          :error-messages="erros.data_historia"
          :disabled="loading"
        />
      </div>

      <v-textarea
        v-model="descricaoCurta"
        label="Descrição curta *"
        rows="2"
        auto-grow
        counter="500"
        maxlength="500"
        :error-messages="erros.descricao_curta"
        :disabled="loading"
      />

      <v-select
        v-model="tipoHistoria"
        label="Tipo da história *"
        :items="TIPOS"
        item-title="titulo"
        item-value="valor"
        :error-messages="erros.tipo_historia"
        :disabled="loading"
      >
        <template #item="{ props: p, item }">
          <v-list-item v-bind="p" :subtitle="item.raw.ajuda" />
        </template>
      </v-select>
      <p class="ajuda-tipo">{{ ajudaTipo }}</p>

      <div class="editor-bloco" :class="{ invalido: erros.conteudo }">
        <v-tabs v-model="aba" density="comfortable" color="primary" aria-label="Escrever ou pré-visualizar">
          <v-tab value="escrever">Escrever</v-tab>
          <v-tab value="previa">Pré-visualizar</v-tab>
        </v-tabs>
        <div v-show="aba === 'escrever'" class="editor-wrap">
          <label class="sr-only" for="editor-historia">Conteúdo da história</label>
          <div v-if="carregandoEditor" class="editor-carregando" role="status">Carregando editor...</div>
          <div id="editor-historia" ref="editorRef" class="editor"></div>
        </div>
        <div v-if="aba === 'previa'" class="tm-prose previa" v-html="previaHtml"></div>
        <p v-if="erros.conteudo" class="erro-campo" role="alert">{{ erros.conteudo }}</p>
        <p v-if="salvoEm" class="rascunho-info" role="status">Rascunho salvo às {{ hora(salvoEm) }}</p>
      </div>

      <v-autocomplete
        v-model="membros"
        :items="usuarios"
        item-title="nome"
        item-value="uuid"
        label="Membros da história"
        multiple
        chips
        closable-chips
        class="mt-4"
        :loading="carregandoUsuarios"
        :error-messages="erros.usuarios"
        :disabled="loading"
        no-data-text="Nenhum membro encontrado"
      >
        <template #prepend-item>
          <v-list-item title="Selecionar todos" prepend-icon="mdi-check-all" @click="membros = usuarios.map((u) => u.uuid)" />
          <v-divider />
        </template>
      </v-autocomplete>

      <fieldset class="midia">
        <legend>Fotos, vídeos e músicas</legend>
        <p class="aviso-midia">Os arquivos não entram no rascunho: se você sair, precisará adicioná-los de novo.</p>

        <div class="drops">
          <div>
            <TmDropzone accept="image/jpeg,image/png" multiple :max-mb="40" rotulo="Fotos (JPG ou PNG)" @arquivos="fotos.adicionar" />
            <p v-if="erros['fotos.0'] || erros.fotos" class="erro-campo" role="alert">{{ erros['fotos.0'] || erros.fotos }}</p>
          </div>
          <div>
            <TmDropzone
              accept="video/mp4,video/quicktime,video/x-msvideo"
              multiple
              :max-mb="200"
              icone="mdi-video-outline"
              rotulo="Vídeos (MP4, MOV, AVI)"
              @arquivos="videos.adicionar"
            />
            <p v-if="erros['videos.0'] || erros.videos" class="erro-campo" role="alert">{{ erros['videos.0'] || erros.videos }}</p>
          </div>
          <div>
            <TmDropzone
              accept="audio/mpeg,audio/wav,audio/ogg"
              multiple
              :max-mb="30"
              icone="mdi-music-note-plus"
              rotulo="Músicas (MP3, WAV, OGG)"
              @arquivos="musicas.adicionar"
            />
            <p v-if="erros['musicas.0'] || erros.musicas" class="erro-campo" role="alert">{{ erros['musicas.0'] || erros.musicas }}</p>
          </div>
        </div>

        <ul v-if="fotos.itens.value.length || videos.itens.value.length || musicas.itens.value.length" class="preview">
          <li v-for="f in fotos.itens.value" :key="f.id" class="preview-item">
            <img :src="f.url" :alt="`Pré-visualização de ${f.file.name}`" />
            <span class="nome">{{ f.file.name }} · {{ formatarTamanho(f.file.size) }}</span>
            <v-btn icon size="small" variant="flat" color="surface" class="remove-btn" :aria-label="`Remover ${f.file.name}`" :disabled="loading" @click="fotos.remover(f.id)">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </li>
          <li v-for="v in videos.itens.value" :key="v.id" class="preview-item">
            <video :src="v.url" controls preload="metadata" :aria-label="`Pré-visualização de ${v.file.name}`" />
            <span class="nome">{{ v.file.name }} · {{ formatarTamanho(v.file.size) }}</span>
            <v-btn icon size="small" variant="flat" color="surface" class="remove-btn" :aria-label="`Remover ${v.file.name}`" :disabled="loading" @click="videos.remover(v.id)">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </li>
          <li v-for="m in musicas.itens.value" :key="m.id" class="preview-item musica">
            <v-icon aria-hidden="true">mdi-music</v-icon>
            <span class="nome">{{ m.file.name }} · {{ formatarTamanho(m.file.size) }}</span>
            <v-btn icon size="small" variant="flat" color="surface" class="remove-btn" :aria-label="`Remover ${m.file.name}`" :disabled="loading" @click="musicas.remover(m.id)">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </li>
        </ul>
      </fieldset>

      <div class="rodape-fixo">
        <div v-if="loading" class="progresso" role="status" aria-live="polite">
          <v-progress-linear :model-value="progresso" color="primary" height="8" rounded />
          <span>Enviando {{ progresso }}% — não feche esta página.</span>
        </div>
        <div class="rodape-botoes">
          <v-btn v-if="loading" variant="text" color="on-surface" @click="cancelar">Cancelar envio</v-btn>
          <v-btn type="submit" variant="flat" color="primary" size="large" :loading="loading" :disabled="loading">Salvar história</v-btn>
        </div>
      </div>
    </form>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { onBeforeRouteLeave } from "vue-router";
import { criarHistoria } from "@/services/historia.criar.service";
import { listarUsuarios } from "@/services/usuario.service";
import { useAuthStore } from "@/stores/auth";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useConfirm } from "@/composables/useConfirm";
import { useRascunho } from "@/composables/useRascunho";
import { useListaArquivos, formatarTamanho, comprimirImagem } from "@/composables/useArquivos";
import { sanitizar, normalizarListasQuill } from "@/composables/useSanitize";
import TmDropzone from "@/components/ui/TmDropzone.vue";

const TIPOS = [
  { valor: "Pessoal", titulo: "Pessoal", ajuda: "Uma lembrança sua, contada do seu jeito." },
  { valor: "Principal", titulo: "Principal", ajuda: "Um fato central da história da rua." },
  { valor: "Paralela", titulo: "Paralela", ajuda: "Algo que aconteceu ao lado, mas faz parte do todo." },
];

const auth = useAuthStore();
const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();
const { confirmar } = useConfirm();
const rascunho = useRascunho("historia-nova");
const { salvoEm } = rascunho;

const titulo = ref("");
const descricaoCurta = ref("");
const dataHistoria = ref("");
const tipoHistoria = ref("Pessoal");
const membros = ref([]);
const usuarios = ref([]);
const carregandoUsuarios = ref(false);

const fotos = useListaArquivos();
const videos = useListaArquivos();
const musicas = useListaArquivos({ previews: false });

const loading = ref(false);
const progresso = ref(0);
const erros = ref({});
const criada = ref(null);
const aba = ref("escrever");
const previaHtml = ref("");
const carregandoEditor = ref(true);
const editorTemTexto = ref(false);
const rascunhoPendente = ref(null);
const editorRef = ref(null);
let editor = null;
let controller = null;
let salvando = false;

const ajudaTipo = computed(() => TIPOS.find((t) => t.valor === tipoHistoria.value)?.ajuda ?? "");

const sujo = computed(
  () =>
    !criada.value &&
    !!(
      titulo.value ||
      descricaoCurta.value ||
      dataHistoria.value ||
      editorTemTexto.value ||
      fotos.itens.value.length ||
      videos.itens.value.length ||
      musicas.itens.value.length
    ),
);

function hora(d) {
  return d.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" });
}
function formatarDataHora(iso) {
  return new Date(iso).toLocaleString("pt-BR", { dateStyle: "long", timeStyle: "short" });
}

/* ---------- rascunho ---------- */
function snapshot() {
  return {
    titulo: titulo.value,
    descricaoCurta: descricaoCurta.value,
    dataHistoria: dataHistoria.value,
    tipoHistoria: tipoHistoria.value,
    membros: membros.value,
    conteudo: editor?.root.innerHTML ?? "",
  };
}
function agendarRascunho() {
  if (rascunhoPendente.value || salvando || !(titulo.value || descricaoCurta.value || editorTemTexto.value)) return;
  rascunho.salvar(snapshot());
}
watch([titulo, descricaoCurta, dataHistoria, tipoHistoria, membros], agendarRascunho, { deep: true });

function restaurar() {
  const d = rascunhoPendente.value?.dados;
  if (d) {
    titulo.value = d.titulo ?? "";
    descricaoCurta.value = d.descricaoCurta ?? "";
    dataHistoria.value = d.dataHistoria ?? "";
    tipoHistoria.value = d.tipoHistoria ?? "Pessoal";
    membros.value = d.membros ?? [];
    if (editor) editor.root.innerHTML = d.conteudo ?? "";
  }
  rascunhoPendente.value = null;
}
function descartarRascunho() {
  rascunho.limpar();
  rascunhoPendente.value = null;
}

/* ---------- editor (Quill carregado sob demanda) ---------- */
async function iniciarEditor() {
  const [{ default: Quill }] = await Promise.all([import("quill"), import("quill/dist/quill.snow.css")]);
  carregandoEditor.value = false;
  editor = new Quill(editorRef.value, {
    theme: "snow",
    placeholder: "Escreva a história aqui...",
    modules: {
      // Sem botão de imagem: imagens vão como fotos da história (evita base64 gigante no HTML).
      toolbar: [[{ header: [1, 2, 3, false] }], ["bold", "italic", "underline"], [{ list: "ordered" }, { list: "bullet" }], ["link"], ["clean"]],
    },
  });
  editor.on("text-change", () => {
    editorTemTexto.value = editor.getText().trim().length > 0;
    agendarRascunho();
  });
}

watch(aba, (v) => {
  if (v === "previa") previaHtml.value = sanitizar(editor?.root.innerHTML ?? "");
});

async function carregarUsuarios() {
  carregandoUsuarios.value = true;
  try {
    const r = await listarUsuarios();
    usuarios.value = Array.isArray(r) ? r : (r?.data ?? []);
  } catch {
    usuarios.value = [];
  } finally {
    carregandoUsuarios.value = false;
  }
}

/* ---------- envio ---------- */
function validar() {
  const e = {};
  if (!titulo.value.trim()) e.titulo = "Informe um título.";
  if (!dataHistoria.value) e.data_historia = "Informe a data da história.";
  if (!descricaoCurta.value.trim()) e.descricao_curta = "Escreva uma descrição curta.";
  if (!editor || !editor.getText().trim()) e.conteudo = "Escreva o conteúdo da história.";
  erros.value = e;
  return Object.keys(e).length === 0;
}

async function salvar() {
  if (!validar()) {
    aba.value = "escrever";
    focarPrimeiroInvalido();
    return;
  }

  loading.value = true;
  const fotosFinais = await Promise.all(fotos.itens.value.map((i) => comprimirImagem(i.file, { limiarMb: 0, alvoMb: 4 })));
  const grande = fotosFinais.find((f) => f.size > 4 * 1024 * 1024);
  if (grande) {
    erros.value = { fotos: `A foto “${grande.name}” continua acima de 4 MB depois de reduzida. Remova-a ou escolha outra.` };
    loading.value = false;
    return;
  }
  const form = new FormData();
  form.append("titulo", titulo.value.trim());
  form.append("descricao_curta", descricaoCurta.value.trim());
  form.append("conteudo", normalizarListasQuill(editor.root.innerHTML));
  form.append("data_historia", dataHistoria.value);
  form.append("tipo_historia", tipoHistoria.value);
  membros.value.forEach((u) => form.append("usuarios[]", u));
  fotosFinais.forEach((f) => form.append("fotos[]", f));
  videos.itens.value.forEach((i) => form.append("videos[]", i.file));
  musicas.itens.value.forEach((i) => form.append("musicas[]", i.file));

  progresso.value = 0;
  controller = new AbortController();
  salvando = true;

  try {
    const r = await criarHistoria(form, {
      signal: controller.signal,
      onUploadProgress: (e) => {
        if (e.total) progresso.value = Math.round((e.loaded / e.total) * 100);
      },
    });
    rascunho.limpar();
    limparFormulario();
    criada.value = r?.uuid ?? null;
    notify.sucesso("História guardada.");
  } catch (e) {
    salvando = false;
    if (e?.code === "ERR_CANCELED") {
      notify.info("Envio cancelado.");
    } else {
      const info = tratar(e);
      erros.value = info.campos;
      if (Object.keys(info.campos).length) focarPrimeiroInvalido();
    }
  } finally {
    loading.value = false;
    controller = null;
    salvando = false;
  }
}

function cancelar() {
  controller?.abort();
}

function limparFormulario() {
  titulo.value = "";
  descricaoCurta.value = "";
  dataHistoria.value = "";
  tipoHistoria.value = "Pessoal";
  membros.value = auth.usuario?.uuid ? [auth.usuario.uuid] : [];
  fotos.limpar();
  videos.limpar();
  musicas.limpar();
  editor?.setContents([]);
  editorTemTexto.value = false;
  erros.value = {};
}

/* ---------- saída com alterações ---------- */
function avisarSaida(e) {
  if (sujo.value) {
    e.preventDefault();
    e.returnValue = "";
  }
}
function atalho(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "s") {
    e.preventDefault();
    if (!rascunhoPendente.value) rascunho.salvar(snapshot());
    notify.info("Rascunho salvo neste aparelho.");
  }
}

onBeforeRouteLeave(async () => {
  if (!sujo.value) return true;
  return confirmar({
    titulo: "Sair sem salvar a história?",
    texto: "O texto fica guardado como rascunho neste aparelho, mas fotos, vídeos e músicas serão perdidos.",
    rotuloConfirmar: "Sair",
    rotuloCancelar: "Continuar editando",
    destrutivo: false,
  });
});

onMounted(async () => {
  window.addEventListener("beforeunload", avisarSaida);
  window.addEventListener("keydown", atalho);
  const existente = rascunho.ler();
  if (existente?.dados) rascunhoPendente.value = existente;
  membros.value = auth.usuario?.uuid ? [auth.usuario.uuid] : [];
  carregarUsuarios();
  await iniciarEditor();
});
onBeforeUnmount(() => {
  window.removeEventListener("beforeunload", avisarSaida);
  window.removeEventListener("keydown", atalho);
  controller?.abort();
});
</script>

<style scoped>
.cadastrar-historia-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/cadastrar-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
  display: flex;
  justify-content: center;
  align-items: flex-start;
}
.card {
  width: 100%;
  max-width: 1000px;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  padding: var(--tm-space-7);
  border-radius: var(--tm-radius-lg);
  box-shadow: var(--tm-shadow-lg);
}
.card.sucesso {
  text-align: center;
  max-width: 560px;
}
.acoes-sucesso {
  display: flex;
  gap: var(--tm-space-3);
  justify-content: center;
  flex-wrap: wrap;
  margin-top: var(--tm-space-5);
}
h1 {
  font-size: var(--tm-fs-3xl);
  margin-bottom: var(--tm-space-5);
}
.grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--tm-space-4);
}
.alerta-rascunho {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--tm-space-2);
}
.ajuda-tipo {
  margin: calc(-1 * var(--tm-space-2)) 0 var(--tm-space-4);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.editor-bloco {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3);
}
.editor-bloco.invalido {
  border-color: rgb(var(--v-theme-error));
}
.editor-wrap {
  margin-top: var(--tm-space-3);
}
.editor-carregando {
  padding: var(--tm-space-5);
  text-align: center;
  color: var(--tm-ink-muted);
}
.previa {
  min-height: 220px;
  margin-top: var(--tm-space-3);
}
.rascunho-info {
  margin: var(--tm-space-2) 0 0;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.erro-campo {
  margin: var(--tm-space-2) 0 0;
  color: rgb(var(--v-theme-error));
  font-size: var(--tm-fs-sm);
}
:deep(.ql-container) {
  min-height: 260px;
  font-size: var(--tm-fs-md);
}
.midia {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-4);
  margin-top: var(--tm-space-5);
}
.midia legend {
  font-weight: 700;
  padding: 0 var(--tm-space-2);
}
.aviso-midia {
  margin: 0 0 var(--tm-space-3);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.drops {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--tm-space-3);
}
.preview {
  list-style: none;
  padding: 0;
  margin: var(--tm-space-4) 0 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: var(--tm-space-3);
}
.preview-item {
  position: relative;
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-sm);
  overflow: hidden;
  background: rgb(var(--v-theme-surface-variant));
  display: flex;
  flex-direction: column;
}
.preview-item img,
.preview-item video {
  width: 100%;
  height: 110px;
  object-fit: cover;
}
.preview-item.musica {
  flex-direction: row;
  align-items: center;
  gap: var(--tm-space-2);
  padding: var(--tm-space-3);
  padding-right: 52px;
}
.nome {
  font-size: var(--tm-fs-xs);
  padding: var(--tm-space-1) var(--tm-space-2);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.remove-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 44px;
  height: 44px;
}
.rodape-fixo {
  position: sticky;
  bottom: 0;
  margin: var(--tm-space-5) calc(-1 * var(--tm-space-7)) calc(-1 * var(--tm-space-7));
  padding: var(--tm-space-3) var(--tm-space-7);
  background: rgb(var(--v-theme-surface));
  border-top: 1px solid var(--tm-border);
  border-radius: 0 0 var(--tm-radius-lg) var(--tm-radius-lg);
  z-index: 5;
}
.rodape-botoes {
  display: flex;
  justify-content: flex-end;
  gap: var(--tm-space-2);
}
.progresso {
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-1);
  font-size: var(--tm-fs-sm);
  margin-bottom: var(--tm-space-2);
}
@media (max-width: 960px) {
  .drops {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 600px) {
  .card {
    padding: var(--tm-space-4);
  }
  .grid {
    grid-template-columns: 1fr;
  }
  .rodape-fixo {
    margin: var(--tm-space-5) calc(-1 * var(--tm-space-4)) calc(-1 * var(--tm-space-4));
    padding: var(--tm-space-3) var(--tm-space-4);
  }
}
</style>
