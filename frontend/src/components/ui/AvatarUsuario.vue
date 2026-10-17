<template>
  <v-avatar :size="size" class="avatar-usuario" :color="src && !falhou ? undefined : 'primary'">
    <img v-if="src && !falhou" :src="src" :alt="`Foto de ${nome || 'usuário'}`" @error="falhou = true" />
    <span v-else class="iniciais" role="img" :aria-label="`Iniciais de ${nome || 'usuário'}`">
      {{ iniciais(nome) || "?" }}
    </span>
  </v-avatar>
</template>

<script setup>
import { ref, watch } from "vue";
import { iniciais } from "@/composables/useFormat";

const props = defineProps({
  nome: { type: String, default: "" },
  src: { type: String, default: null },
  size: { type: [Number, String], default: 40 },
});

const falhou = ref(false);
watch(() => props.src, () => (falhou.value = false));
</script>

<style scoped>
.avatar-usuario {
  border: 2px solid var(--tm-gold);
}
.avatar-usuario img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.iniciais {
  font-weight: 700;
  font-size: 0.85em;
  color: rgb(var(--v-theme-on-primary));
}
</style>
