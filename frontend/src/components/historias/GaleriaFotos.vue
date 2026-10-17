<template>
  <TmFormDialog :model-value="modelValue" titulo="Fotos da história" max-width="1200" @update:model-value="(v) => emit('update:modelValue', v)">
    <p v-if="!itens.length" class="vazio">Nenhuma foto nesta história.</p>
    <ul v-else class="grid-fotos">
      <li v-for="(foto, i) in itens" :key="foto.uuid">
        <button type="button" class="thumb" :aria-label="`Abrir ${foto.titulo}`" @click="abrir(i)">
          <img :src="foto.url" :alt="foto.titulo" loading="lazy" decoding="async" width="250" height="250" />
        </button>
      </li>
    </ul>
  </TmFormDialog>

  <TmLightbox v-model="lightbox" :itens="itens" :inicio="inicio" tipo="foto" compartilhavel @compartilhar="copiar" />
</template>

<script setup>
import { ref, computed } from "vue";
import { urlFoto } from "@/services/midia.url";
import { useNotify } from "@/composables/useNotify";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import TmLightbox from "@/components/ui/TmLightbox.vue";

const props = defineProps({
  fotos: { type: Array, default: () => [] },
  /** [{uuid,url,titulo,descricao}] quando a API traz; senão usa os uuids */
  midia: { type: Array, default: null },
  modelValue: { type: Boolean, default: false },
  tituloHistoria: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue"]);

const notify = useNotify();
const lightbox = ref(false);
const inicio = ref(0);

// Os uuids vêm da história; as URLs são diretas, então todas as fotos carregam em paralelo.
const itens = computed(() => {
  const base = props.midia?.length ? props.midia : props.fotos.map((uuid) => ({ uuid }));
  return base.map((f, i) => ({
    uuid: f.uuid,
    url: f.url || urlFoto(f.uuid),
    descricao: f.descricao || "",
    titulo: f.titulo || `Foto ${i + 1} de ${base.length}${props.tituloHistoria ? ` da história ${props.tituloHistoria}` : ""}`,
  }));
});

function abrir(i) {
  inicio.value = i;
  lightbox.value = true;
}

async function copiar(foto) {
  try {
    await navigator.clipboard.writeText(`${location.origin}/galeria-fotos?foto=${foto.uuid}`);
    notify.sucesso("Link copiado.");
  } catch {
    notify.erro("Não foi possível copiar o link.");
  }
}
</script>

<style scoped>
.vazio {
  text-align: center;
  padding: var(--tm-space-7);
  color: var(--tm-ink-muted);
}
.grid-fotos {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--tm-space-4);
}
.thumb {
  display: block;
  width: 100%;
  aspect-ratio: 1;
  padding: 0;
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  overflow: hidden;
  background: rgb(var(--v-theme-surface-variant));
  cursor: pointer;
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.thumb:hover {
  transform: translateY(-4px);
  box-shadow: var(--tm-shadow-md);
}
.thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>
