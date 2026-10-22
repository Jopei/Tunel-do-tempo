<template>
  <div class="abas-barra">
    <div class="abas" role="tablist" aria-label="Fotos e álbuns">
      <router-link v-slot="{ href, navigate }" custom :to="{ name: 'fotos' }">
        <a :href="href" role="tab" class="aba" :class="{ ativa: ativa === 'fotos' }" :aria-selected="ativa === 'fotos'" @click="navigate">Fotos</a>
      </router-link>
      <router-link v-if="logado" v-slot="{ href, navigate }" custom :to="{ name: 'fotos-albuns' }">
        <a :href="href" role="tab" class="aba" :class="{ ativa: ativa === 'albuns' }" :aria-selected="ativa === 'albuns'" @click="navigate">Álbuns</a>
      </router-link>
    </div>

    <v-spacer />

    <template v-if="busca">
      <v-text-field
        v-if="mdAndUp || buscaAberta"
        v-model="texto"
        class="abas-busca"
        density="compact"
        variant="outlined"
        :label="rotuloBusca"
        prepend-inner-icon="mdi-magnify"
        clearable
        hide-details
        autocomplete="off"
        @keydown.esc="buscaAberta = false"
      />
      <v-btn v-if="!mdAndUp && !buscaAberta" icon variant="text" class="tm-touch" :aria-label="rotuloBusca" @click="buscaAberta = true">
        <v-icon>mdi-magnify</v-icon>
      </v-btn>
    </template>

    <v-menu v-if="opcoes && capitulos.length && !mdAndUp" location="bottom end">
      <template #activator="{ props }">
        <v-btn v-bind="props" icon variant="text" class="tm-touch abas-ano" aria-label="Ir para o ano">
          <v-icon>mdi-calendar-search-outline</v-icon>
        </v-btn>
      </template>
      <v-card min-width="200" max-height="60dvh" class="overflow-y-auto">
        <v-list density="comfortable" aria-label="Ir para o ano">
          <v-list-item v-for="c in capitulos" :key="c.chave" :title="c.rotulo" :subtitle="c.sub || undefined" @click="$emit('ir-ano', c.chave)" />
        </v-list>
      </v-card>
    </v-menu>

    <v-switch
      v-if="opcoes && mdAndUp"
      v-model="filtroAntigo"
      class="abas-filtro"
      color="primary"
      density="compact"
      inset
      hide-details
      aria-label="Aplicar filtro de foto antiga"
    >
      <template #label><v-icon size="18" class="mr-1" aria-hidden="true">mdi-image-filter-vintage</v-icon>Filtro antigo</template>
    </v-switch>

    <v-menu v-if="opcoes" location="bottom end" :close-on-content-click="false">
      <template #activator="{ props }">
        <v-btn v-bind="props" icon variant="text" class="tm-touch" aria-label="Ordenar e densidade">
          <v-icon>mdi-tune-variant</v-icon>
        </v-btn>
      </template>
      <v-card min-width="220">
        <v-list density="comfortable" :aria-label="'Ordenação'" select-strategy="single-leaf">
          <v-list-subheader>Ordem</v-list-subheader>
          <v-list-item
            v-for="o in ORDENS"
            :key="o.valor"
            :title="o.titulo"
            :active="ordem === o.valor"
            role="menuitemradio"
            :aria-checked="ordem === o.valor"
            @click="$emit('update:ordem', o.valor)"
          />
          <template v-if="!mdAndUp">
            <v-list-subheader>Aparência</v-list-subheader>
            <v-list-item
              title="Filtro antigo"
              prepend-icon="mdi-image-filter-vintage"
              role="menuitemcheckbox"
              :aria-checked="filtroAntigo"
              @click="filtroAntigo = !filtroAntigo"
            >
              <template #append><v-switch :model-value="filtroAntigo" density="compact" hide-details inset readonly tabindex="-1" aria-hidden="true" /></template>
            </v-list-item>
          </template>
          <v-list-subheader>Tamanho das fotos</v-list-subheader>
          <v-list-item
            v-for="d in DENSIDADES"
            :key="d.valor"
            :title="d.titulo"
            :active="densidade === d.valor"
            role="menuitemradio"
            :aria-checked="densidade === d.valor"
            @click="$emit('update:densidade', d.valor)"
          />
        </v-list>
      </v-card>
    </v-menu>

    <v-btn
      v-if="podeEnviar"
      color="primary"
      variant="flat"
      prepend-icon="mdi-upload"
      class="abas-enviar"
      @click="$emit('enviar')"
    >
      <span class="d-none d-sm-inline">Enviar</span>
      <span class="d-sm-none sr-only">Enviar fotos</span>
    </v-btn>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { useDisplay } from "vuetify";
import { filtroAntigo } from "@/composables/useEraFotosVideos";

const props = defineProps({
  ativa: { type: String, default: "fotos" },
  logado: Boolean,
  podeEnviar: Boolean,
  busca: { type: Boolean, default: true },
  rotuloBusca: { type: String, default: "Buscar fotos" },
  opcoes: { type: Boolean, default: true },
  q: { type: String, default: "" },
  ordem: { type: String, default: "recentes" },
  densidade: { type: String, default: "normal" },
  /** [{ chave, rotulo, sub }] para "Ir para o ano" (mobile). */
  capitulos: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:q", "update:ordem", "update:densidade", "enviar", "ir-ano"]);

const ORDENS = [
  { titulo: "Mais recentes primeiro", valor: "recentes" },
  { titulo: "Mais antigas primeiro", valor: "antigas" },
];
const DENSIDADES = [
  { titulo: "Compacta", valor: "compacta" },
  { titulo: "Normal", valor: "normal" },
  { titulo: "Ampla", valor: "ampla" },
];

const { mdAndUp } = useDisplay();
const buscaAberta = ref(false);
const texto = computed({
  get: () => props.q,
  set: (v) => emit("update:q", v ?? ""),
});
</script>

<style scoped>
.abas-barra {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 56px;
  padding: 0 12px;
}
.abas {
  display: inline-flex;
  padding: 3px;
  border-radius: var(--tm-radius-pill);
  background: var(--tm-paper-2);
}
.aba {
  display: inline-flex;
  align-items: center;
  min-height: 40px;
  padding: 0 18px;
  border-radius: var(--tm-radius-pill);
  color: var(--tm-ink);
  text-decoration: none;
  font-weight: 600;
}
.aba.ativa {
  background: var(--tm-gold);
  color: #2a2112;
}
.abas-busca {
  max-width: 320px;
}
.abas-filtro {
  flex: none;
  min-width: 0;
}
.abas-enviar {
  min-height: 40px;
}
</style>
