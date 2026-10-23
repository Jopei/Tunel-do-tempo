<template>
  <TmFormDialog v-model="dialog" titulo="Adicionar ao álbum" max-width="520" :bloqueado="enviando">
    <v-text-field
      v-model="busca"
      label="Buscar álbum"
      prepend-inner-icon="mdi-magnify"
      clearable
      hide-details
      autocomplete="off"
      class="mb-3"
    />
    <v-btn block variant="tonal" prepend-icon="mdi-plus" class="mb-3" :disabled="enviando" @click="novoAberto = true">Novo álbum</v-btn>

    <p v-if="lista.carregando.value && !lista.itens.value.length" class="estado" role="status">Carregando álbuns...</p>
    <p v-else-if="lista.erro.value" class="estado" role="alert">
      Não foi possível carregar os álbuns.
      <v-btn size="small" variant="text" @click="lista.recarregar()">Tentar de novo</v-btn>
    </p>
    <p v-else-if="lista.vazio.value" class="estado">Nenhum álbum {{ q ? "encontrado" : "ainda" }}.</p>

    <ul class="albuns" aria-label="Álbuns">
      <li v-for="a in lista.itens.value" :key="a.uuid">
        <button type="button" class="album-op" :disabled="enviando" @click="escolher(a)">
          <img v-if="a.capa" :src="a.capa.thumbnail_url || a.capa.url" alt="" class="album-mini" />
          <span v-else class="album-mini vazio" aria-hidden="true"><v-icon>mdi-image-album</v-icon></span>
          <span class="album-texto">
            <strong>{{ a.titulo }}</strong>
            <small>{{ a.fotos_count }} {{ a.fotos_count === 1 ? "item" : "itens" }}</small>
          </span>
        </button>
      </li>
    </ul>
    <div v-if="lista.temMais.value" class="text-center mt-2">
      <v-btn variant="text" :loading="lista.carregando.value" @click="lista.carregarProxima()">Carregar mais</v-btn>
    </div>

    <AlbumFormDialog v-model="novoAberto" :fotos="uuids" @salvo="aoCriar" />
  </TmFormDialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { listarAlbuns, adicionarFotosAlbum } from "@/services/album.service";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import AlbumFormDialog from "./AlbumFormDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  /** uuids das fotos a adicionar. */
  uuids: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "adicionado"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const { tratar } = useApiError();
const notify = useNotify();

const busca = ref("");
const q = ref("");
const enviando = ref(false);
const novoAberto = ref(false);
const lista = useListaPaginada((p) => listarAlbuns(p, { per_page: 24, ...(q.value ? { q: q.value } : {}) }));

let t = null;
watch(busca, (v) => {
  clearTimeout(t);
  t = setTimeout(() => {
    q.value = (v ?? "").trim();
    lista.recarregar();
  }, 300);
});
watch(
  () => props.modelValue,
  (aberto) => {
    if (aberto) {
      busca.value = "";
      q.value = "";
      lista.recarregar();
    }
  },
);

async function escolher(album) {
  enviando.value = true;
  try {
    await adicionarFotosAlbum(album.uuid, props.uuids);
    const n = props.uuids.length;
    notify.sucesso(`${n} ${n === 1 ? "foto adicionada" : "fotos adicionadas"} a "${album.titulo}".`);
    emit("adicionado", album);
    dialog.value = false;
  } catch (e) {
    tratar(e);
  } finally {
    enviando.value = false;
  }
}

function aoCriar(album) {
  emit("adicionado", album);
  dialog.value = false;
}
</script>

<style scoped>
.albuns {
  list-style: none;
  margin: 0;
  padding: 0;
}
.album-op {
  width: 100%;
  min-height: 56px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 6px 8px;
  border: 0;
  background: transparent;
  border-radius: var(--tm-radius-xs);
  color: inherit;
  text-align: left;
  cursor: pointer;
}
.album-op:hover,
.album-op:focus-visible {
  background: var(--tm-paper-2);
}
.album-mini {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  object-fit: cover;
  flex: none;
  background: var(--tm-paper-2);
}
.album-mini.vazio {
  display: grid;
  place-items: center;
}
.album-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.album-texto small {
  color: var(--tm-ink-muted);
}
.estado {
  color: var(--tm-ink-muted);
  text-align: center;
  padding: 12px 0;
}
</style>
