<template>
  <v-menu location="bottom end">
    <template #activator="{ props }">
      <v-btn v-bind="props" icon variant="text" class="tm-touch" :aria-label="`Ações de ${musica.titulo}`">
        <v-icon>mdi-dots-vertical</v-icon>
      </v-btn>
    </template>
    <v-list density="comfortable">
      <v-list-item prepend-icon="mdi-playlist-play" title="Tocar a seguir" @click="$emit('acao', 'proxima')" />
      <v-list-item prepend-icon="mdi-playlist-plus" title="Adicionar à fila" @click="$emit('acao', 'fila')" />
      <template v-if="podeEditar">
        <v-list-item prepend-icon="mdi-pencil-outline" title="Editar" @click="$emit('acao', 'editar')" />
        <v-list-item prepend-icon="mdi-delete-outline" title="Excluir" base-color="error" @click="$emit('acao', 'excluir')" />
      </template>
    </v-list>
  </v-menu>
</template>

<script setup>
defineProps({
  musica: { type: Object, required: true },
  podeEditar: Boolean,
});
defineEmits(["acao"]);
</script>
