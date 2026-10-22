<template>
  <div class="hx-autoria" :class="{ 'hx-autoria--empilhado': empilhar }">
    <span class="hx-autoria__avatares" aria-hidden="true">
      <AvatarUsuario
        v-for="u in avatares"
        :key="u.uuid"
        class="hx-autoria__avatar"
        :nome="u.nome"
        :src="u.foto_url"
        :size="tamanho"
      />
    </span>
    <div class="hx-autoria__texto">
      <p class="hx-autoria__linha">
        <template v-if="autor">
          <router-link class="hx-autoria__nome" :to="rota(autor)">{{ autor.nome }}</router-link>
          <template v-if="marcados.length">
            estava com
            <template v-for="(u, i) in marcadosVisiveis" :key="u.uuid">
              <router-link class="hx-autoria__nome" :to="rota(u)">{{ u.nome }}</router-link
              ><template v-if="i < marcadosVisiveis.length - 2">, </template
              ><template v-else-if="i === marcadosVisiveis.length - 2 && !resto"> e </template
              ><template v-else-if="i === marcadosVisiveis.length - 2">, </template>
            </template>
            <template v-if="resto"> e mais {{ resto }}</template>
          </template>
        </template>
        <template v-else>Alguém da Rua</template>
      </p>
      <p class="hx-autoria__quando">
        <HistoriaData :data="data" />
        <v-icon size="14" aria-hidden="true" class="hx-autoria__vis">mdi-earth</v-icon>
        <slot name="extra" />
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { chaveMembro } from "@/services/membro.service";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import HistoriaData from "@/components/historias/HistoriaData.vue";

const props = defineProps({
  usuarios: { type: Array, default: () => [] },
  data: { type: String, default: null },
  tamanho: { type: Number, default: 40 },
  /** mostra até 4 avatares sobrepostos (página da história) */
  empilhar: { type: Boolean, default: false },
});

const rota = (u) => ({ name: "membro", params: { slug: chaveMembro(u) } });
const autor = computed(() => props.usuarios[0] ?? null);
const marcados = computed(() => props.usuarios.slice(1));
const marcadosVisiveis = computed(() => marcados.value.slice(0, 2));
const resto = computed(() => Math.max(0, marcados.value.length - 2));
const avatares = computed(() => (props.empilhar ? props.usuarios.slice(0, 4) : autor.value ? [autor.value] : []));
</script>

<style scoped>
.hx-autoria {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  min-width: 0;
}
.hx-autoria__avatares {
  display: inline-flex;
  flex: none;
}
.hx-autoria--empilhado .hx-autoria__avatar + .hx-autoria__avatar {
  margin-left: -12px;
}
.hx-autoria__texto {
  min-width: 0;
}
.hx-autoria__linha,
.hx-autoria__quando {
  margin: 0;
}
.hx-autoria__linha {
  font-size: var(--tm-fs-md);
  line-height: 1.35;
  position: relative;
  z-index: 1;
}
.hx-autoria__nome {
  font-weight: 700;
  color: var(--tm-ink);
  text-decoration: none;
}
.hx-autoria__nome:hover {
  text-decoration: underline;
}
.hx-autoria__quando {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.hx-autoria__vis {
  color: var(--tm-ink-muted);
}
</style>
