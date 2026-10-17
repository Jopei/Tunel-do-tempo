<template>
  <ul class="media-grid" :class="`tipo-${tipo}`">
    <li v-for="(item, i) in itens" :key="item.uuid" class="media-item" :class="{ marcado: selecionados.includes(item.uuid) }">
      <button
        type="button"
        class="thumb"
        :aria-label="selecao ? `Selecionar ${rotulo(item)}` : `Abrir ${tipo === 'foto' ? 'foto' : 'vídeo'} ${rotulo(item)}`"
        :aria-pressed="selecao ? selecionados.includes(item.uuid) : undefined"
        @click="selecao ? $emit('alternar', item.uuid) : $emit('abrir', i)"
      >
        <img
          v-if="tipo === 'foto' || item.thumbnail || item.poster_url"
          :src="tipo === 'foto' ? item.url : item.thumbnail || item.poster_url"
          alt=""
          loading="lazy"
          decoding="async"
          width="240"
          height="240"
        />
        <span v-if="tipo === 'video'" class="play" aria-hidden="true"><v-icon size="40" color="white">mdi-play-circle</v-icon></span>
        <span v-if="selecao" class="check" aria-hidden="true">
          <v-icon size="22">{{ selecionados.includes(item.uuid) ? "mdi-check-circle" : "mdi-circle-outline" }}</v-icon>
        </span>
      </button>

      <v-menu v-if="podeEditar && !selecao" location="bottom end">
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            icon
            size="small"
            variant="flat"
            color="surface"
            class="menu-btn"
            :aria-label="`Ações ${tipo === 'foto' ? 'da foto' : 'do vídeo'} ${rotulo(item)}`"
          >
            <v-icon>mdi-dots-vertical</v-icon>
          </v-btn>
        </template>
        <v-list density="compact">
          <v-list-item prepend-icon="mdi-pencil-outline" title="Editar" @click="$emit('editar', item)" />
          <v-list-item prepend-icon="mdi-delete-outline" title="Excluir" base-color="error" @click="$emit('excluir', item)" />
        </v-list>
      </v-menu>

      <p class="info">{{ item.titulo || "Sem título" }}</p>
    </li>
  </ul>
</template>

<script setup>
defineProps({
  itens: { type: Array, default: () => [] },
  tipo: { type: String, default: "foto" },
  podeEditar: { type: Boolean, default: false },
  selecao: { type: Boolean, default: false },
  selecionados: { type: Array, default: () => [] },
});
defineEmits(["abrir", "editar", "excluir", "alternar"]);

const rotulo = (item) => item.titulo || "sem título";
</script>

<style scoped>
.media-grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--tm-space-4);
}
.tipo-video {
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}
.media-item {
  position: relative;
}
.thumb {
  position: relative;
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
.tipo-video .thumb {
  aspect-ratio: 16 / 9;
  background: linear-gradient(180deg, rgba(199, 164, 58, 0.2), rgba(0, 0, 0, 0.4));
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
.play {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.6));
}
.check {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: 50%;
  display: flex;
}
.marcado .thumb {
  outline: 3px solid var(--tm-gold);
  outline-offset: -3px;
}
.menu-btn {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 44px;
  height: 44px;
  opacity: 0.95;
}
.info {
  margin: var(--tm-space-2) 0 0;
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
