<template>
  <nav aria-label="Caminho" class="vbc">
    <v-btn v-if="xs && caminho.length" variant="text" prepend-icon="mdi-chevron-left" class="vbc-voltar" @click="$emit('ir', paiUuid)">
      {{ paiNome }}
    </v-btn>
    <ol v-else class="vbc-lista">
      <li>
        <VideoMigalha raiz nome="Vídeos" :atual="!caminho.length" :pode-soltar="podeSoltar" @ir="$emit('ir', null)" @soltar="repassar" />
      </li>
      <template v-if="ocultos.length">
        <li class="vbc-sep" aria-hidden="true"><v-icon size="18">mdi-chevron-right</v-icon></li>
        <li>
          <v-menu location="bottom start">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon variant="text" size="small" class="tm-touch" aria-label="Pastas ocultas do caminho">
                <v-icon>mdi-dots-horizontal</v-icon>
              </v-btn>
            </template>
            <v-list density="comfortable">
              <v-list-item v-for="p in ocultos" :key="p.uuid" :title="p.nome" prepend-icon="mdi-folder-outline" @click="$emit('ir', p.uuid)" />
            </v-list>
          </v-menu>
        </li>
      </template>
      <template v-for="(p, i) in visiveis" :key="p.uuid">
        <li class="vbc-sep" aria-hidden="true"><v-icon size="18">mdi-chevron-right</v-icon></li>
        <li>
          <VideoMigalha
            :uuid="p.uuid"
            :nome="p.nome"
            :atual="i === visiveis.length - 1"
            :pode-soltar="podeSoltar"
            @ir="$emit('ir', p.uuid)"
            @soltar="repassar"
          />
        </li>
      </template>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { useDisplay } from "vuetify";
import VideoMigalha from "./VideoMigalha.vue";

const props = defineProps({
  /** Da raiz até a pasta atual: [{ uuid, nome }] */
  caminho: { type: Array, default: () => [] },
  podeSoltar: Boolean,
});
const emit = defineEmits(["ir", "soltar"]);

const { smAndDown, xs } = useDisplay();
const limite = computed(() => (smAndDown.value ? 2 : 4));
const ocultos = computed(() => (props.caminho.length > limite.value ? props.caminho.slice(0, props.caminho.length - limite.value) : []));
const visiveis = computed(() => props.caminho.slice(ocultos.value.length));
const paiUuid = computed(() => props.caminho.at(-2)?.uuid ?? null);
const paiNome = computed(() => props.caminho.at(-2)?.nome ?? "Vídeos");

const repassar = (...args) => emit("soltar", ...args);
</script>

<style scoped>
.vbc {
  min-width: 0;
}
.vbc-lista {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 2px;
}
.vbc-sep {
  display: inline-flex;
  color: var(--tm-ink-muted);
}
.vbc-voltar {
  max-width: 100%;
}
</style>
