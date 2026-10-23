<template>
  <router-link
    :to="{ name: 'fotos-album', params: { uuid: album.uuid } }"
    class="album-card hx-ano"
    :class="{ 'album-card--pilha': (album.fotos_count ?? 0) > 1, 'album-card--sem-capa': !capaUrl }"
    :aria-label="rotulo"
  >
    <span class="album-pilha" aria-hidden="true"></span>
    <span class="album-capa fv-moldura fv-vinheta">
      <img v-if="capaUrl" class="fv-img" :src="capaUrl" alt="" loading="lazy" decoding="async" />
      <template v-else>
        <v-icon size="48" aria-hidden="true">mdi-image-multiple-outline</v-icon>
      </template>
      <span class="album-faixa" :class="{ 'album-faixa--solida': !capaUrl }">
        <strong class="album-titulo" :title="album.titulo">{{ album.titulo }}</strong>
        <span class="album-meta">
          {{ contagem }}<template v-if="anos"> · {{ anos }}</template>
        </span>
      </span>
    </span>
  </router-link>
</template>

<script setup>
import { computed } from "vue";
import { pluralFotos } from "@/composables/useEraFotosVideos";

const props = defineProps({ album: { type: Object, required: true } });
const capaUrl = computed(() => props.album.capa?.thumbnail_url || props.album.capa?.url || null);
const contagem = computed(() => pluralFotos(props.album.fotos_count ?? 0));
const anos = computed(() => {
  const { ano_inicio: i, ano_fim: f } = props.album;
  if (!i) return "";
  return !f || f === i ? String(i) : `${i}-${f}`;
});
const rotulo = computed(() => `Álbum ${props.album.titulo}, ${contagem.value}${anos.value ? `, ${anos.value}` : ""}`);
</script>

<style scoped>
.album-card {
  --hx-ano-h: 38;
  position: relative;
  display: block;
  color: var(--tm-ink);
  text-decoration: none;
  border-radius: 12px;
  padding: 4px 0 6px;
  /* gancho motion 5: .album-card:hover/:focus-visible eleva; .album-pilha se abre */
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.album-card:hover,
.album-card:focus-visible {
  transform: translateY(-2px);
}
.album-card:focus-visible {
  outline: 3px solid var(--tm-focus);
  outline-offset: 3px;
}
.album-capa {
  position: relative;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 100%;
  aspect-ratio: var(--fv-capa-album, 4 / 3);
  color: var(--hx-ano-cor);
}
.album-capa img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
/* Fotos empilhadas atrás (só borda/sombra, sem imagens): gira -2° e +1.5° */
.album-pilha {
  position: absolute;
  inset: 4px 0 6px 0;
  z-index: 0;
  pointer-events: none;
}
.album-card--pilha .album-pilha::before,
.album-card--pilha .album-pilha::after {
  content: "";
  position: absolute;
  inset: 0;
  border: 4px solid #fff;
  border-radius: 12px;
  background: var(--tm-paper-2);
  box-shadow: var(--tm-shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.25));
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.album-card--pilha .album-pilha::before {
  transform: rotate(-2deg);
}
.album-card--pilha .album-pilha::after {
  transform: rotate(1.5deg);
}
.album-card--pilha:hover .album-pilha::before,
.album-card--pilha:focus-visible .album-pilha::before {
  transform: rotate(-2deg) translate(-2px, 1px);
}
.album-card--pilha:hover .album-pilha::after,
.album-card--pilha:focus-visible .album-pilha::after {
  transform: rotate(1.5deg) translate(2px, 1px);
}
.album-faixa {
  position: absolute;
  inset: auto 0 0 0;
  z-index: 2;
  display: flex;
  flex-direction: column;
  padding: 32px 12px 10px;
  background: var(--fv-scrim);
  color: #fff;
  text-align: left;
}
.album-faixa--solida {
  background: transparent;
  inset: 0;
  justify-content: flex-end;
  color: var(--tm-ink);
}
.album-titulo {
  font-family: var(--tm-font-title);
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.album-meta {
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  opacity: 0.92;
}
@media (prefers-reduced-motion: reduce) {
  .album-card,
  .album-card--pilha .album-pilha::before,
  .album-card--pilha .album-pilha::after {
    transition: none;
  }
  .album-card:hover,
  .album-card:focus-visible {
    transform: none;
  }
}
html[data-reduzir="1"] .album-card,
html[data-reduzir="1"] .album-card--pilha .album-pilha::before,
html[data-reduzir="1"] .album-card--pilha .album-pilha::after {
  transition: none;
}
</style>
