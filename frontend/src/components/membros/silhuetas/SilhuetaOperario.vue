<template>
  <SilhuetaPovo tipo="operario" :pose="pose" :mulher="mulher" />
</template>

<script setup>
import SilhuetaPovo from "./SilhuetaPovo.vue";

defineProps({
  pose: { type: Number, default: 0 },
  mulher: { type: Boolean, default: false },
});
</script>
