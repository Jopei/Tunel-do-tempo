<template>
  <div class="hx-hero hx-grao" :class="{ 'hx-hero--sem-capa': !capaOk }">
    <img
      v-if="capaOk"
      :src="capa"
      alt=""
      class="hx-hero__capa hx-foto__img"
      fetchpriority="high"
      decoding="async"
      @error="falhou = true"
    />
    <span v-else class="hx-hero__ano-gigante" aria-hidden="true">{{ ano ?? "" }}</span>
    <div class="hx-hero__scrim" aria-hidden="true"></div>

    <div class="hx-hero__conteudo">
      <nav class="hx-hero__migalhas no-print" aria-label="Você está em">
        <router-link to="/historias"><v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> Histórias</router-link>
        <template v-if="titulo">
          <span aria-hidden="true">/</span>
          <span aria-current="page" class="hx-hero__atual">{{ titulo }}</span>
        </template>
      </nav>

      <div v-if="ano" class="hx-hero__selos">
        <span class="hx-hero__selo-ano hx-selo" aria-hidden="true">{{ ano }}</span>
        <p class="hx-hero__quando">
          <strong class="hx-hero__relativo" aria-hidden="true">{{ relativo }}</strong>
          <span class="hx-hero__completa">{{ completa }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { tempoRelativo, dataCompleta } from "@/composables/useFormat";
import { anoDe, hueDoAno } from "@/composables/useCapitulos";

const props = defineProps({
  capa: { type: String, default: null },
  titulo: { type: String, default: "" },
  data: { type: String, default: null },
});

const falhou = ref(false);
watch(() => props.capa, () => (falhou.value = false));
const capaOk = computed(() => !!props.capa && !falhou.value);
const ano = computed(() => anoDe({ data_historia: props.data }));
const relativo = computed(() => tempoRelativo(props.data));
const completa = computed(() => dataCompleta(props.data));
defineExpose({ hue: computed(() => hueDoAno(ano.value)) });
</script>

<style scoped>
.hx-hero {
  position: relative;
  height: var(--hx-hero-h);
  background: linear-gradient(135deg, hsl(var(--hx-ano-h) 50% 28%), hsl(calc(var(--hx-ano-h) + 20) 40% 18%));
  overflow: hidden;
  color: #fff;
}
.hx-hero__capa {
  position: absolute;
  inset: 0;
}
.hx-hero::after {
  content: "";
  position: absolute;
  inset: 0;
  background: var(--hx-vinheta);
  pointer-events: none;
}
.hx-hero__ano-gigante {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-family: var(--tm-font-title);
  font-weight: 800;
  font-size: clamp(6rem, 24vw, 14rem);
  opacity: 0.18;
  font-variant-numeric: tabular-nums;
}
/* Scrim em cima (migalhas) e embaixo (selo/data): garante AA mesmo com capa branca */
.hx-hero__scrim {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.65) 0, rgba(0, 0, 0, 0) 38%, rgba(0, 0, 0, 0) 45%, rgba(0, 0, 0, 0.78) 100%);
}
.hx-hero__conteudo {
  position: relative;
  z-index: 1;
  height: 100%;
  max-width: 900px;
  margin: 0 auto;
  padding: var(--tm-space-4) var(--tm-space-4) 56px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.hx-hero__migalhas {
  display: flex;
  align-items: center;
  gap: var(--tm-space-2);
  font-size: var(--tm-fs-sm);
  min-width: 0;
  color: #fff;
}
.hx-hero__migalhas a {
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
}
.hx-hero__migalhas a:hover {
  text-decoration: underline;
}
.hx-hero__atual {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.hx-hero__selos {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
}
.hx-hero__selo-ano {
  width: 72px;
  height: 72px;
  font-size: 1.3rem;
  background: var(--hx-ano-selo);
  border: 2px solid rgba(255, 255, 255, 0.85);
}
.hx-hero__quando {
  margin: 0;
  display: flex;
  flex-direction: column;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.6);
}
.hx-hero__relativo {
  font-size: var(--tm-fs-xl);
}
.hx-hero__completa {
  font-size: var(--tm-fs-sm);
}
</style>
