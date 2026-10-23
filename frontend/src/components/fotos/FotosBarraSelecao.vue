<template>
  <div class="barra-selecao" role="toolbar" aria-label="Ações para as fotos selecionadas">
    <v-btn icon variant="text" class="tm-touch" aria-label="Limpar seleção" @click="$emit('limpar')">
      <v-icon>mdi-close</v-icon>
    </v-btn>
    <span class="barra-contagem" role="status" aria-live="polite">
      {{ quantidade }} {{ quantidade === 1 ? "selecionada" : "selecionadas" }}
    </span>
    <v-spacer />

    <template v-if="!mobile">
      <v-btn v-if="logado" variant="text" prepend-icon="mdi-image-album" @click="$emit('album')">Adicionar ao álbum</v-btn>
      <v-btn v-if="podeEditar && emAlbum && quantidade === 1" variant="text" prepend-icon="mdi-image-check-outline" @click="$emit('capa')">
        Usar como capa
      </v-btn>
      <v-btn v-if="podeEditar && emAlbum" variant="text" prepend-icon="mdi-image-remove-outline" @click="$emit('remover')">
        Remover do álbum
      </v-btn>
      <v-btn v-if="podeEditar" variant="text" color="error" prepend-icon="mdi-delete-outline" @click="$emit('excluir')">Excluir</v-btn>
    </template>
    <template v-else>
      <v-btn v-if="logado" icon variant="text" class="tm-touch" aria-label="Adicionar ao álbum" @click="$emit('album')">
        <v-icon>mdi-image-album</v-icon>
      </v-btn>
      <v-btn v-if="podeEditar" icon variant="text" color="error" class="tm-touch" aria-label="Excluir" @click="$emit('excluir')">
        <v-icon>mdi-delete-outline</v-icon>
      </v-btn>
      <v-menu v-if="podeEditar && emAlbum" location="bottom end">
        <template #activator="{ props }">
          <v-btn v-bind="props" icon variant="text" class="tm-touch" aria-label="Mais ações">
            <v-icon>mdi-dots-vertical</v-icon>
          </v-btn>
        </template>
        <v-list density="comfortable">
          <v-list-item v-if="quantidade === 1" prepend-icon="mdi-image-check-outline" title="Usar como capa" @click="$emit('capa')" />
          <v-list-item prepend-icon="mdi-image-remove-outline" title="Remover do álbum" @click="$emit('remover')" />
        </v-list>
      </v-menu>
    </template>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useDisplay } from "vuetify";

defineProps({
  quantidade: { type: Number, default: 0 },
  podeEditar: Boolean,
  logado: Boolean,
  emAlbum: Boolean,
});
defineEmits(["limpar", "album", "excluir", "remover", "capa"]);

const { smAndDown } = useDisplay();
const mobile = computed(() => smAndDown.value);
</script>

<style scoped>
.barra-selecao {
  display: flex;
  align-items: center;
  gap: 4px;
  height: 56px;
  padding: 0 12px;
  background: color-mix(in srgb, var(--tm-gold) 14%, var(--tm-paper));
  border-bottom: 1px solid var(--tm-border);
  color: var(--tm-ink);
}
.barra-contagem {
  font-weight: 600;
  margin-left: 4px;
}
</style>
