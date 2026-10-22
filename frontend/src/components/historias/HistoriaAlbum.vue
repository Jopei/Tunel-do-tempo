<template>
  <section v-if="fotos.length" class="hx-album" aria-labelledby="hx-album-t">
    <h2 id="hx-album-t" class="hx-album__titulo tm-title">Álbum: {{ fotos.length }} {{ fotos.length === 1 ? "foto" : "fotos" }}</h2>
    <ul class="hx-album__mosaico" :class="`hx-album__mosaico--${visiveis.length}`">
      <li v-for="(f, i) in visiveis" :key="f.uuid" class="hx-album__item" :class="{ 'hx-album__item--grande': i === 0 }" :style="{ '--hx-i': i }">
        <button
          type="button"
          class="hx-album__foto hx-foto"
          :title="f.data_foto ? dataCompleta(f.data_foto) : undefined"
          :aria-label="rotulo(f, i)"
          @click="$emit('abrir', i)"
        >
          <img
            :src="f.thumbnail_url || f.url"
            :alt="f.titulo || ''"
            class="hx-foto__img"
            loading="lazy"
            decoding="async"
          />
          <span v-if="i === visiveis.length - 1 && resto > 0" class="hx-album__mais" aria-hidden="true">+{{ resto }}</span>
        </button>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { computed } from "vue";
import { dataCompleta } from "@/composables/useFormat";

const props = defineProps({ fotos: { type: Array, default: () => [] } });
defineEmits(["abrir"]);

const MAX = 5;
const visiveis = computed(() => props.fotos.slice(0, MAX));
const resto = computed(() => props.fotos.length - MAX);
const rotulo = (f, i) => {
  const ultima = i === visiveis.value.length - 1 && resto.value > 0;
  const base = f.titulo || `Foto ${i + 1}`;
  return ultima ? `${base}. Abrir o álbum com mais ${resto.value} fotos` : `Abrir ${base}`;
};
</script>

<style scoped>
.hx-album {
  margin-block: var(--tm-space-6);
}
.hx-album__titulo {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.hx-album__mosaico {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 4px;
  grid-template-columns: repeat(2, 1fr);
  border-radius: var(--tm-radius-md);
  overflow: hidden;
}
.hx-album__item {
  min-width: 0;
  aspect-ratio: 1;
}
.hx-album__item--grande {
  grid-column: 1 / -1;
  aspect-ratio: 16 / 10;
}
.hx-album__foto {
  display: block;
  width: 100%;
  height: 100%;
  padding: 0;
  border: 0;
  cursor: pointer;
}
.hx-album__foto:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
  z-index: 1;
}
.hx-album__mais {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: grid;
  place-items: center;
  background: rgba(20, 15, 5, 0.6);
  color: #fff;
  font-size: var(--tm-fs-2xl);
  font-weight: 700;
}
/* 1 grande + 4 pequenas em desktop */
@media (min-width: 600px) {
  .hx-album__mosaico {
    grid-template-columns: repeat(4, 1fr);
  }
  .hx-album__item--grande {
    grid-column: 1 / 3;
    grid-row: 1 / 3;
    aspect-ratio: auto;
  }
  .hx-album__item:not(.hx-album__item--grande) {
    aspect-ratio: 1;
  }
  .hx-album__mosaico--1 .hx-album__item--grande,
  .hx-album__mosaico--2 .hx-album__item--grande {
    grid-column: 1 / -1;
    grid-row: auto;
    aspect-ratio: 16 / 9;
  }
  .hx-album__mosaico--2 .hx-album__item:not(.hx-album__item--grande) {
    grid-column: 1 / -1;
    aspect-ratio: 16 / 9;
  }
}
</style>
