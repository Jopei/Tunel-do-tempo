<template>
  <article class="historia-card">
    <router-link :to="{ name: 'historias-show', params: { uuid: historia.uuid } }" class="link-card">
      <v-chip size="x-small" label class="tipo" :color="corTipo">{{ historia.tipo_historia }}</v-chip>
      <h3 class="card-title tm-title">
        <template v-for="(p, i) in partesTitulo" :key="i">
          <mark v-if="p.marcado">{{ p.texto }}</mark>
          <template v-else>{{ p.texto }}</template>
        </template>
      </h3>
      <p v-if="historia.descricao_curta" class="descricao">{{ historia.descricao_curta }}</p>
      <div class="card-footer">
        <div class="membros" :aria-label="`Membros: ${nomes}`">
          <AvatarUsuario
            v-for="u in historia.usuarios.slice(0, 3)"
            :key="u.uuid"
            :nome="u.nome"
            :size="28"
            class="membro"
          />
          <span v-if="historia.usuarios.length > 3" class="mais">+{{ historia.usuarios.length - 3 }}</span>
        </div>
        <span v-if="temContagem" class="contagens" :aria-label="rotuloContagem">
          <span v-if="historia.fotos_count" aria-hidden="true"><v-icon size="14">mdi-image-outline</v-icon> {{ historia.fotos_count }}</span>
          <span v-if="historia.videos_count" aria-hidden="true"><v-icon size="14">mdi-video-outline</v-icon> {{ historia.videos_count }}</span>
          <span v-if="historia.musicas_count" aria-hidden="true"><v-icon size="14">mdi-music-note</v-icon> {{ historia.musicas_count }}</span>
        </span>
        <time v-if="historia.data_historia" :datetime="historia.data_historia" class="data">
          {{ formatarData(historia.data_historia, { month: "short", year: "numeric" }) }}
        </time>
      </div>
    </router-link>

    <v-menu v-if="podeEditar" location="bottom end">
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          icon
          variant="text"
          size="small"
          class="menu-btn"
          :aria-label="`Ações de ${historia.titulo}`"
        >
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
import { computed } from "vue";
import { formatarData } from "@/composables/useFormat";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";

const props = defineProps({
  historia: { type: Object, required: true },
  termo: { type: String, default: "" },
  podeEditar: { type: Boolean, default: false },
});
defineEmits(["editar", "excluir"]);

const temContagem = computed(() => !!(props.historia.fotos_count || props.historia.videos_count || props.historia.musicas_count));
const rotuloContagem = computed(
  () => `${props.historia.fotos_count || 0} fotos, ${props.historia.videos_count || 0} vídeos, ${props.historia.musicas_count || 0} músicas`,
);
const nomes = computed(() => props.historia.usuarios.map((u) => u.nome).join(", "));
const corTipo = computed(() => ({ Principal: "primary", Paralela: "info" })[props.historia.tipo_historia]);

const partesTitulo = computed(() => {
  const t = props.historia.titulo ?? "";
  const termo = props.termo.trim();
  if (!termo) return [{ texto: t, marcado: false }];
  const i = t.toLowerCase().indexOf(termo.toLowerCase());
  if (i < 0) return [{ texto: t, marcado: false }];
  return [
    { texto: t.slice(0, i), marcado: false },
    { texto: t.slice(i, i + termo.length), marcado: true },
    { texto: t.slice(i + termo.length), marcado: false },
  ].filter((p) => p.texto);
});
</script>

<style scoped>
.historia-card {
  position: relative;
  width: 100%;
  min-height: 96px;
  background: rgb(var(--v-theme-surface-variant));
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.historia-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--tm-shadow-md);
}
.link-card {
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-2);
  height: 100%;
  padding: var(--tm-space-4);
  padding-right: 52px;
  color: inherit;
  text-decoration: none;
  border-radius: var(--tm-radius-md);
}
.tipo {
  align-self: flex-start;
}
.card-title {
  font-size: var(--tm-fs-lg);
  margin: 0;
}
.card-title mark {
  background: rgba(199, 164, 58, 0.45);
  color: inherit;
  border-radius: 3px;
}
.descricao {
  margin: 0;
  color: var(--tm-ink-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-footer {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--tm-space-2);
}
.membros {
  display: flex;
  align-items: center;
}
.membro {
  margin-left: -8px;
}
.membro:first-child {
  margin-left: 0;
}
.mais {
  margin-left: 6px;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.contagens {
  display: inline-flex;
  gap: var(--tm-space-2);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.data {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.menu-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 44px;
  height: 44px;
}
</style>
