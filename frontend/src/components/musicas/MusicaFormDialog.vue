<template>
  <TmFormDialog v-model="dialog" :titulo="musica ? 'Editar música' : 'Enviar música'" max-width="640" :bloqueado="salvando" :sujo="sujo" @descartar="limpar">
    <form id="form-musica" novalidate @submit.prevent="salvar">
      <template v-if="!musica">
        <div v-if="arquivo" class="arq">
          <v-icon aria-hidden="true">mdi-music-circle-outline</v-icon>
          <span class="arq-nome">{{ arquivo.name }} · {{ formatarTamanho(arquivo.size) }}</span>
          <v-btn icon size="small" variant="text" :aria-label="`Remover ${arquivo.name}`" :disabled="salvando" @click="removerArquivo">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </div>
        <TmDropzone
          v-else
          accept=".mp3,.wav,.ogg,audio/mpeg,audio/wav,audio/ogg"
          :max-mb="20"
          icone="mdi-music-note-plus"
          rotulo="Arraste a música para cá (MP3, WAV ou OGG)"
          :erro-externo="erros.musica || ''"
          @arquivos="escolherArquivo"
        />
      </template>

      <v-text-field v-model="titulo" label="Título" class="mt-4" :error-messages="erros.titulo" :disabled="salvando" autocomplete="off" />
      <v-text-field v-model="artista" label="Artista" :error-messages="erros.artista" :disabled="salvando" autocomplete="off" />
      <v-text-field v-model="album" label="Álbum" :error-messages="erros.album" :disabled="salvando" autocomplete="off" />
      <div class="linha">
        <v-text-field v-model="ano" label="Ano" type="number" min="1900" max="2100" :error-messages="erros.ano" :disabled="salvando" />
        <v-text-field
          v-model="duracao"
          label="Duração (segundos)"
          type="number"
          min="0"
          :error-messages="erros.duracao_segundos"
          :disabled="salvando"
        />
      </div>

      <div v-if="!musica" class="capa">
        <MusicaCapa :src="capaPreview" :tamanho="64" />
        <div>
          <v-btn variant="tonal" prepend-icon="mdi-image-outline" :disabled="salvando" @click="inputCapa?.click()">
            {{ capa ? "Trocar capa" : "Escolher capa (opcional)" }}
          </v-btn>
          <v-btn v-if="capa" variant="text" :disabled="salvando" @click="removerCapa">Remover</v-btn>
          <p v-if="erros.capa" class="erro" role="alert">{{ erros.capa }}</p>
        </div>
        <input ref="inputCapa" type="file" class="sr-only" tabindex="-1" accept="image/jpeg,image/png" aria-label="Escolher imagem de capa" @change="escolherCapa" />
      </div>

      <div v-if="salvando && !musica" class="progresso" role="status" aria-live="polite">
        <v-progress-linear :model-value="progresso" color="primary" height="8" rounded />
        <span>Enviando {{ progresso }}% — não feche esta página.</span>
      </div>
    </form>

    <template #acoes>
      <v-btn v-if="salvando && !musica" variant="text" color="on-surface" @click="cancelar">Cancelar envio</v-btn>
      <v-btn variant="flat" color="primary" type="submit" form="form-musica" :loading="salvando">
        {{ musica ? "Salvar" : "Enviar música" }}
      </v-btn>
    </template>
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { cadastrarMusica, atualizarMusica } from "@/services/musica.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { formatarTamanho, comprimirImagem } from "@/composables/useArquivos";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmDropzone from "@/components/ui/TmDropzone.vue";
import MusicaCapa from "./MusicaCapa.vue";

const props = defineProps({
  modelValue: Boolean,
  /** Música existente (edição); null = nova. */
  musica: { type: Object, default: null },
});
const emit = defineEmits(["update:modelValue", "salvo"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();

const titulo = ref("");
const artista = ref("");
const album = ref("");
const ano = ref("");
const duracao = ref("");
const arquivo = ref(null);
const capa = ref(null);
const capaPreview = ref(null);
const inputCapa = ref(null);
const salvando = ref(false);
const progresso = ref(0);
const erros = ref({});
let controller = null;

const sujo = computed(() => !props.musica && !!(arquivo.value || titulo.value || artista.value || album.value));

watch(
  () => props.modelValue,
  (aberto) => {
    if (!aberto) return;
    limpar();
    if (props.musica) {
      titulo.value = props.musica.titulo ?? "";
      artista.value = props.musica.artista ?? "";
      album.value = props.musica.album ?? "";
      ano.value = props.musica.ano ?? "";
      duracao.value = props.musica.duracao_segundos ?? "";
    }
  },
);

function escolherArquivo([f]) {
  arquivo.value = f;
  erros.value = {};
  if (!titulo.value) titulo.value = f.name.replace(/\.[^.]+$/, "");
  // Lê a duração pelos metadados do próprio arquivo.
  const url = URL.createObjectURL(f);
  const a = new Audio();
  a.preload = "metadata";
  a.onloadedmetadata = () => {
    if (Number.isFinite(a.duration) && !duracao.value) duracao.value = Math.round(a.duration);
    URL.revokeObjectURL(url);
  };
  a.onerror = () => URL.revokeObjectURL(url);
  a.src = url;
}
function removerArquivo() {
  arquivo.value = null;
}
function escolherCapa(e) {
  const f = e.target.files?.[0];
  e.target.value = "";
  if (!f) return;
  removerCapa();
  capa.value = f;
  capaPreview.value = URL.createObjectURL(f);
}
function removerCapa() {
  if (capaPreview.value) URL.revokeObjectURL(capaPreview.value);
  capa.value = null;
  capaPreview.value = null;
}
function cancelar() {
  controller?.abort();
}

function limpar() {
  titulo.value = artista.value = album.value = "";
  ano.value = duracao.value = "";
  arquivo.value = null;
  removerCapa();
  erros.value = {};
  progresso.value = 0;
}

async function salvar() {
  erros.value = {};
  if (!titulo.value.trim()) {
    erros.value = { titulo: "Informe o título." };
    focarPrimeiroInvalido();
    return;
  }
  if (!props.musica && !arquivo.value) {
    erros.value = { musica: "Selecione o arquivo de música." };
    return;
  }
  salvando.value = true;
  try {
    if (props.musica) {
      const salva = await atualizarMusica(props.musica.uuid, {
        titulo: titulo.value.trim(),
        artista: artista.value.trim() || null,
        album: album.value.trim() || null,
        ano: ano.value === "" ? null : Number(ano.value),
        duracao_segundos: duracao.value === "" ? null : Number(duracao.value),
      });
      notify.sucesso("Música atualizada.");
      emit("salvo", salva);
    } else {
      const form = new FormData();
      form.append("titulo", titulo.value.trim());
      form.append("musica", arquivo.value);
      if (artista.value.trim()) form.append("artista", artista.value.trim());
      if (album.value.trim()) form.append("album", album.value.trim());
      if (ano.value !== "") form.append("ano", ano.value);
      if (duracao.value !== "") form.append("duracao_segundos", duracao.value);
      if (capa.value) {
        const reduzida = await comprimirImagem(capa.value, { limiarMb: 0, max: 1200, alvoMb: 4 });
        if (reduzida.size > 4 * 1024 * 1024) {
          erros.value = { capa: "A capa passa de 4 MB mesmo reduzida. Escolha outra imagem." };
          return;
        }
        form.append("capa", reduzida);
      }
      controller = new AbortController();
      progresso.value = 0;
      await cadastrarMusica(form, {
        signal: controller.signal,
        onUploadProgress: (e) => {
          if (e.total) progresso.value = Math.round((e.loaded / e.total) * 100);
        },
      });
      notify.sucesso("Música enviada.");
      emit("salvo", null);
    }
    limpar();
    dialog.value = false;
  } catch (e) {
    if (e?.code === "ERR_CANCELED") notify.info("Envio cancelado.");
    else {
      const info = tratar(e);
      erros.value = info.campos;
      if (Object.keys(info.campos).length) focarPrimeiroInvalido();
    }
  } finally {
    salvando.value = false;
    controller = null;
  }
}

onBeforeUnmount(() => {
  controller?.abort();
  removerCapa();
});
</script>

<style scoped>
.arq {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: var(--tm-radius-xs);
  background: var(--tm-paper-2);
}
.arq-nome {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.linha {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.capa {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.erro {
  color: rgb(var(--v-theme-error));
  margin: 4px 0 0;
  font-size: var(--tm-fs-sm);
}
.progresso {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: var(--tm-fs-sm);
}
</style>
