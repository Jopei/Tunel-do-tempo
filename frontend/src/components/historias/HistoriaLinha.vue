<template>
  <article class="hx-linha" :style="{ '--hx-ano-h': hue }">
    <div class="hx-linha__miniatura hx-foto" :class="{ 'hx-foto--sem': !capaOk }" aria-hidden="true">
      <img v-if="capaOk" :src="historia.capa_url" alt="" class="hx-foto__img" loading="lazy" decoding="async" @error="capaFalhou = true" />
      <v-icon v-else size="24" class="hx-linha__sem-capa">mdi-book-open-page-variant-outline</v-icon>
    </div>

    <div class="hx-linha__corpo">
      <h3 class="hx-linha__titulo tm-title">
        <router-link :to="destino" class="hx-linha__link" data-linha-link>
          <template v-for="(p, i) in partesTitulo" :key="i">
            <mark v-if="p.marcado">{{ p.texto }}</mark>
            <template v-else>{{ p.texto }}</template>
          </template>
        </router-link>
      </h3>
      <p class="hx-linha__meta">
        <HistoriaData :data="historia.data_historia" />
        <template v-if="completa"><span aria-hidden="true"> · </span><span class="hx-linha__completa" aria-hidden="true">{{ completa }}</span></template>
        <span class="hx-linha__tipo">{{ historia.tipo_historia }}</span>
      </p>
    </div>

    <div class="hx-linha__lado">
      <span v-if="usuarios.length" class="hx-linha__membros" :aria-label="`Membros: ${nomes}`">
        <AvatarUsuario v-for="u in usuarios.slice(0, 3)" :key="u.uuid" :nome="u.nome" :src="u.foto_url" :size="26" class="hx-linha__membro" />
        <span v-if="usuarios.length > 3" class="hx-linha__mais" aria-hidden="true">+{{ usuarios.length - 3 }}</span>
      </span>
      <ul class="hx-linha__contadores" :aria-label="rotulo">
        <li v-if="historia.fotos_count" aria-hidden="true"><v-icon size="14">mdi-image-outline</v-icon> {{ historia.fotos_count }}</li>
        <li v-if="historia.videos_count" aria-hidden="true"><v-icon size="14">mdi-video-outline</v-icon> {{ historia.videos_count }}</li>
        <li v-if="historia.favoritos_count" aria-hidden="true"><v-icon size="14">mdi-heart-outline</v-icon> {{ historia.favoritos_count }}</li>
        <li v-if="historia.lembrancas_count" aria-hidden="true"><v-icon size="14">mdi-comment-outline</v-icon> {{ historia.lembrancas_count }}</li>
      </ul>
    </div>

    <v-menu v-if="podeEditar" location="bottom end">
      <template #activator="{ props }">
        <v-btn v-bind="props" icon variant="text" size="small" class="hx-linha__menu" :aria-label="`Ações de ${historia.titulo}`">
          <v-icon>mdi-dots-vertical</v-icon>
        </v-btn>
      </template>
      <v-list density="compact" :aria-label="`Ações de ${historia.titulo}`">
        <v-list-item prepend-icon="mdi-pencil-outline" title="Editar" @click="$emit('editar')" />
        <v-list-item prepend-icon="mdi-delete-outline" title="Apagar" base-color="error" @click="$emit('excluir')" />
      </v-list>
    </v-menu>
  </article>
</template>

<script setup>
import { computed, ref } from "vue";
import { dataCompleta } from "@/composables/useFormat";
import { anoDe, hueDoAno } from "@/composables/useCapitulos";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import HistoriaData from "@/components/historias/HistoriaData.vue";

const props = defineProps({
  historia: { type: Object, required: true },
  termo: { type: String, default: "" },
  podeEditar: { type: Boolean, default: false },
});
defineEmits(["editar", "excluir"]);

const capaFalhou = ref(false);
const capaOk = computed(() => !!props.historia.capa_url && !capaFalhou.value);
const destino = computed(() => ({ name: "historias-show", params: { uuid: props.historia.uuid } }));
const hue = computed(() => hueDoAno(anoDe(props.historia)));
const completa = computed(() => dataCompleta(props.historia.data_historia));
const usuarios = computed(() => props.historia.usuarios ?? []);
const nomes = computed(() => usuarios.value.map((u) => u.nome).join(", "));

const rotulo = computed(() => {
  const h = props.historia;
  const p = [];
  if (h.fotos_count) p.push(`${h.fotos_count} fotos`);
  if (h.videos_count) p.push(`${h.videos_count} vídeos`);
  if (h.favoritos_count) p.push(`${h.favoritos_count} curtidas`);
  if (h.lembrancas_count) p.push(`${h.lembrancas_count} comentários`);
  return p.join(", ");
});

const partesTitulo = computed(() => {
  const t = props.historia.titulo ?? "";
  const termo = props.termo.trim();
  const i = termo ? t.toLowerCase().indexOf(termo.toLowerCase()) : -1;
  if (i < 0) return [{ texto: t, marcado: false }];
  return [
    { texto: t.slice(0, i), marcado: false },
    { texto: t.slice(i, i + termo.length), marcado: true },
    { texto: t.slice(i + termo.length), marcado: false },
  ].filter((p) => p.texto);
});
</script>

<style scoped>
.hx-linha {
  position: relative;
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr) auto 44px;
  align-items: center;
  gap: var(--tm-space-3);
  min-height: 72px;
  padding: var(--tm-space-2) var(--tm-space-2) var(--tm-space-2) var(--tm-space-3);
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-sm);
  transition: background var(--tm-dur-fast) var(--tm-ease), transform var(--tm-dur-fast) var(--tm-ease);
}
.hx-linha:hover,
.hx-linha:focus-within {
  background: var(--hx-balao);
}
.hx-linha__miniatura {
  width: 56px;
  height: 56px;
  border-radius: var(--tm-radius-xs);
  display: grid;
  place-items: center;
}
.hx-linha__miniatura::after {
  box-shadow: none;
}
.hx-linha__sem-capa {
  color: var(--hx-ano-selo);
}
.hx-linha__corpo {
  min-width: 0;
}
.hx-linha__titulo {
  font-size: var(--tm-fs-lg);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.hx-linha__link {
  color: inherit;
  text-decoration: none;
}
.hx-linha__link::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: var(--tm-radius-sm);
}
.hx-linha__link:focus-visible {
  outline: none;
}
.hx-linha__link:focus-visible::after {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
.hx-linha__link mark {
  background: rgba(199, 164, 58, 0.45);
  color: inherit;
  border-radius: 3px;
}
.hx-linha__meta {
  margin: 0;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  display: flex;
  flex-wrap: wrap;
  gap: 0 4px;
  align-items: center;
}
.hx-linha__tipo {
  margin-left: 6px;
  padding: 0 8px;
  border-radius: var(--tm-radius-pill);
  background: var(--hx-ano-suave);
  color: var(--tm-ink);
  font-size: var(--tm-fs-xs);
  font-weight: 600;
}
.hx-linha__lado {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
}
.hx-linha__membros {
  display: inline-flex;
  align-items: center;
}
.hx-linha__membro + .hx-linha__membro {
  margin-left: -8px;
}
.hx-linha__mais {
  margin-left: 4px;
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
}
.hx-linha__contadores {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  gap: var(--tm-space-2);
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
  min-width: 0;
}
.hx-linha__contadores li {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}
.hx-linha__menu {
  position: relative;
  z-index: 1;
  width: 44px;
  height: 44px;
}
@media (max-width: 759px) {
  .hx-linha {
    grid-template-columns: 48px minmax(0, 1fr) 44px;
  }
  .hx-linha__miniatura {
    width: 48px;
    height: 48px;
  }
  .hx-linha__lado {
    grid-column: 2 / 3;
    grid-row: 2;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--tm-space-2);
  }
  .hx-linha__completa {
    display: none;
  }
  .hx-linha__titulo {
    white-space: normal;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
  }
}
</style>
