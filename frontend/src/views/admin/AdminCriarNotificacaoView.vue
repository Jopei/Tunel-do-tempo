<template>
  <section class="notif-page tm-immersive">
    <div class="layout">
      <form class="card" novalidate @submit.prevent="salvar">
        <h1 class="tm-title">Criar notificação</h1>

        <v-text-field
          v-model="titulo"
          label="Título *"
          counter="120"
          maxlength="120"
          :error-messages="erros.titulo"
          :disabled="loading"
          autocomplete="off"
        />

        <div class="grid">
          <v-combobox
            v-model="tema"
            :items="TEMAS"
            label="Tema"
            :error-messages="erros.tema"
            :disabled="loading"
          />
          <v-select
            v-model="destino"
            :items="DESTINOS"
            item-title="titulo"
            item-value="valor"
            label="Destino ao clicar"
            :disabled="loading"
          />
        </div>

        <v-text-field
          v-if="destino === 'custom'"
          v-model="link"
          label="Link (interno começa com / ou URL https)"
          :error-messages="erros.link"
          :disabled="loading"
          placeholder="/galeria-fotos"
        />

        <div class="editor-bloco" :class="{ invalido: erros.descricao }">
          <label class="editor-label" for="notif-editor">Mensagem *</label>
          <div v-if="carregandoEditor" class="editor-carregando" role="status">Carregando editor...</div>
          <div id="notif-editor" ref="editorRef" class="editor"></div>
          <p v-if="erros.descricao" class="erro-campo" role="alert">{{ erros.descricao }}</p>
        </div>

        <div class="acoes">
          <v-btn type="submit" variant="flat" color="primary" size="large" :loading="loading" :disabled="loading">
            Criar notificação
          </v-btn>
        </div>
      </form>

      <aside class="previa" aria-label="Pré-visualização">
        <h2 class="tm-title">Como aparece no sino</h2>
        <div class="item-previa">
          <strong>{{ titulo || "Título da notificação" }}</strong>
          <div class="meta">
            <v-chip v-if="tema" size="x-small" label>{{ tema }}</v-chip>
            <span>agora há pouco</span>
          </div>
        </div>
      </aside>
    </div>

    <section class="enviadas" aria-labelledby="enviadas-titulo">
      <h2 id="enviadas-titulo" class="tm-title">Notificações enviadas</h2>
      <TmSkeletonGrid v-if="enviadas.carregando.value && !enviadas.itens.value.length" :quantidade="3" tipo="list-item-two-line" minimo="100%" />
      <TmErrorState v-else-if="enviadas.erro.value && !enviadas.itens.value.length" @retry="enviadas.recarregar" />
      <TmEmptyState v-else-if="enviadas.vazio.value" icone="mdi-bell-outline" titulo="Nenhuma notificação enviada ainda." />
      <ul v-else class="lista-env">
        <li v-for="n in enviadas.itens.value" :key="n.id" class="env">
          <div class="env-info">
            <strong>{{ n.titulo }}</strong>
            <span class="env-meta">
              <v-chip v-if="n.tema" size="x-small" label>{{ n.tema }}</v-chip>
              {{ dataRelativa(n.created_at) }}<template v-if="n.autor"> · por {{ n.autor.nome }}</template>
              · lida por {{ n.lidas_count ?? 0 }}
            </span>
          </div>
          <v-btn
            icon
            variant="text"
            color="error"
            :aria-label="`Excluir notificação ${n.titulo}`"
            :disabled="excluindo === n.id"
            @click="excluir(n)"
          >
            <v-icon>mdi-delete-outline</v-icon>
          </v-btn>
        </li>
      </ul>
      <div v-if="enviadas.temMais.value" class="text-center mt-4">
        <v-btn variant="tonal" :loading="enviadas.carregando.value" @click="enviadas.carregarProxima()">Carregar mais</v-btn>
      </div>
    </section>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { onBeforeRouteLeave } from "vue-router";
import { criarNotificacao, listarNotificacoesEnviadas, excluirNotificacao } from "@/services/notificacao.service";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { dataRelativa } from "@/composables/useFormat";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { normalizarListasQuill } from "@/composables/useSanitize";
import { useConfirm } from "@/composables/useConfirm";

const TEMAS = ["Aviso", "Novidade", "Aniversário", "Homenagem"];
const DESTINOS = [
  { titulo: "Nenhum", valor: "" },
  { titulo: "Vídeos", valor: "/galeria-videos" },
  { titulo: "Fotos", valor: "/galeria-fotos" },
  { titulo: "Histórias", valor: "/historias" },
  { titulo: "Atualizações", valor: "/atualizacoes" },
  { titulo: "Outro link", valor: "custom" },
];

const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();
const { confirmar } = useConfirm();

const titulo = ref("");
const tema = ref("");
const destino = ref("");
const link = ref("");
const loading = ref(false);
const carregandoEditor = ref(true);
const erros = ref({});
const editorRef = ref(null);
const editorTemTexto = ref(false);
let editor;

const enviadas = useListaPaginada((page) => listarNotificacoesEnviadas({ page, per_page: 15 }));
const excluindo = ref(null);

async function excluir(n) {
  const ok = await confirmar({
    titulo: `Excluir a notificação “${n.titulo}”?`,
    texto: "Ela some para todos os membros. Essa ação não pode ser desfeita.",
    rotuloConfirmar: "Excluir",
  });
  if (!ok) return;
  excluindo.value = n.id;
  try {
    await excluirNotificacao(n.id);
    enviadas.itens.value = enviadas.itens.value.filter((i) => i.id !== n.id);
    notify.sucesso("Notificação excluída.");
  } catch (e) {
    tratar(e);
  } finally {
    excluindo.value = null;
  }
}

const linkFinal = computed(() => (destino.value === "custom" ? link.value.trim() : destino.value));
const sujo = computed(() => !!(titulo.value || tema.value || link.value || editorTemTexto.value));

function linkValido(v) {
  if (!v) return true;
  if (v.startsWith("/") && !v.startsWith("//")) return true;
  try {
    return new URL(v).protocol === "https:";
  } catch {
    return false;
  }
}

onMounted(async () => {
  enviadas.carregarProxima();
  const [{ default: Quill }] = await Promise.all([import("quill"), import("quill/dist/quill.snow.css")]);
  carregandoEditor.value = false;
  editor = new Quill(editorRef.value, {
    theme: "snow",
    placeholder: "Escreva a notificação aqui...",
    modules: {
      toolbar: [[{ header: [1, 2, 3, false] }], ["bold", "italic", "underline"], [{ list: "ordered" }, { list: "bullet" }], ["link"], ["clean"]],
    },
  });
  editor.on("text-change", () => (editorTemTexto.value = editor.getText().trim().length > 0));
});

async function salvar() {
  erros.value = {};
  if (!titulo.value.trim()) erros.value.titulo = "Informe um título.";
  if (!editor || !editor.getText().trim()) erros.value.descricao = "Escreva a mensagem.";
  if (!linkValido(linkFinal.value)) erros.value.link = "Use um caminho interno (/...) ou uma URL https.";
  if (Object.keys(erros.value).length) {
    focarPrimeiroInvalido();
    return;
  }

  loading.value = true;
  try {
    await criarNotificacao({
      titulo: titulo.value.trim(),
      descricao: normalizarListasQuill(editor.root.innerHTML),
      tema: tema.value || "",
      link: linkFinal.value,
    });
    notify.sucesso("Notificação criada.");
    limpar();
    enviadas.recarregar();
    document.querySelector("#conteudo input")?.focus();
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    loading.value = false;
  }
}

function limpar() {
  titulo.value = "";
  tema.value = "";
  destino.value = "";
  link.value = "";
  editor?.setContents([]);
}

function avisarSaida(e) {
  if (sujo.value) e.preventDefault();
}
window.addEventListener("beforeunload", avisarSaida);
onBeforeUnmount(() => window.removeEventListener("beforeunload", avisarSaida));

onBeforeRouteLeave(async () => {
  if (!sujo.value) return true;
  return confirmar({
    titulo: "Sair sem criar a notificação?",
    texto: "O que você escreveu será perdido.",
    rotuloConfirmar: "Sair",
    rotuloCancelar: "Continuar editando",
  });
});
</script>

<style scoped>
.notif-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/cadastrar-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 32px) var(--tm-space-4) var(--tm-space-7);
}
.layout {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: var(--tm-space-5);
  align-items: start;
}
.enviadas {
  max-width: 1100px;
  margin: var(--tm-space-6) auto 0;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
}
.enviadas h2 {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-4);
}
.lista-env {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-2);
}
.env {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--tm-space-3);
  padding: var(--tm-space-3) var(--tm-space-4);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
}
.env-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.env-meta {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.card,
.previa {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  box-shadow: var(--tm-shadow-lg);
  padding: var(--tm-space-6);
}
h1 {
  font-size: var(--tm-fs-3xl);
  margin-bottom: var(--tm-space-5);
}
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-4);
}
.editor-bloco {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3);
}
.editor-bloco.invalido {
  border-color: rgb(var(--v-theme-error));
}
.editor-label {
  display: block;
  font-weight: 600;
  margin-bottom: var(--tm-space-2);
}
.editor-carregando {
  padding: var(--tm-space-5);
  text-align: center;
  color: var(--tm-ink-muted);
}
.erro-campo {
  margin: var(--tm-space-2) 0 0;
  color: rgb(var(--v-theme-error));
  font-size: var(--tm-fs-sm);
}
.acoes {
  margin-top: var(--tm-space-5);
  text-align: right;
}
.previa h2 {
  font-size: var(--tm-fs-lg);
  margin-bottom: var(--tm-space-3);
}
.item-previa {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-sm);
  padding: var(--tm-space-3);
}
.meta {
  display: flex;
  gap: var(--tm-space-2);
  align-items: center;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  margin-top: var(--tm-space-1);
}
:deep(.ql-toolbar) {
  border-radius: var(--tm-radius-xs) var(--tm-radius-xs) 0 0;
}
:deep(.ql-container) {
  min-height: 180px;
  border-radius: 0 0 var(--tm-radius-xs) var(--tm-radius-xs);
}
@media (max-width: 960px) {
  .layout {
    grid-template-columns: 1fr;
  }
  .card,
  .previa {
    padding: var(--tm-space-4);
  }
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
