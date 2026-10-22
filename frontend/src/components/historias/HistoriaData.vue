<template>
  <time v-if="data" class="hx-data" :datetime="data" :title="completa">
    {{ relativo }}<span class="sr-only">, {{ completa }}</span>
  </time>
  <span v-else class="hx-data">Sem data</span>
</template>

<script setup>
import { computed } from "vue";
import { tempoRelativo, dataCompleta } from "@/composables/useFormat";

const props = defineProps({ data: { type: String, default: null } });
const relativo = computed(() => tempoRelativo(props.data));
const completa = computed(() => dataCompleta(props.data));
</script>
