<template>
  <nav v-if="anterior || proxima" class="hx-viz no-print" aria-label="Continue rolando as Histórias da Rua R">
    <h2 class="hx-viz__titulo tm-title">Continue rolando as {{ REDE.nome }}</h2>
    <p v-if="virada" class="hx-viz__virada" :class="`hx-viz__virada--${virada.lado}`">Virando o ano: {{ virada.de }} → {{ virada.para }}</p>
    <div class="hx-viz__cartoes">
      <router-link
        v-if="anterior"
        :to="{ name: 'historias-show', params: { uuid: anterior.uuid } }"
        class="hx-viz__cartao hx-viz__cartao--antes"
        :style="{ '--hx-ano-h': hue(anterior) }"
      >
        <span class="hx-viz__dir"><v-icon size="16" aria-hidden="true">mdi-arrow-left</v-icon> Antes</span>
        <strong class="hx-viz__nome">{{ anterior.titulo }}</strong>
        <HistoriaData :data="anterior.data_historia" class="hx-viz__data" />
      </router-link>
      <router-link
        v-if="proxima"
        :to="{ name: 'historias-show', params: { uuid: proxima.uuid } }"
        class="hx-viz__cartao hx-viz__cartao--depois"
        :style="{ '--hx-ano-h': hue(proxima) }"
      >
        <span class="hx-viz__dir">Depois <v-icon size="16" aria-hidden="true">mdi-arrow-right</v-icon></span>
        <strong class="hx-viz__nome">{{ proxima.titulo }}</strong>
        <HistoriaData :data="proxima.data_historia" class="hx-viz__data" />
      </router-link>
    </div>

    <ul class="hx-viz__regua" aria-label="Ir para um ano">
      <li v-for="a in ANOS" :key="a">
        <router-link
          :to="{ name: 'historias-listar', hash: `#ano-${a}` }"
          class="hx-viz__ano"
          :class="{ 'hx-viz__ano--atual': a === anoAtual }"
          :aria-current="a === anoAtual ? 'true' : undefined"
        >
          {{ a }}
        </router-link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { REDE, ANOS } from "@/config/esquina";
import { anoDe, hueDoAno } from "@/composables/useCapitulos";
import HistoriaData from "@/components/historias/HistoriaData.vue";

const props = defineProps({
  anterior: { type: Object, default: null },
  proxima: { type: Object, default: null },
  /** ano da história atual */
  anoAtual: { type: Number, default: null },
});

const hue = (h) => hueDoAno(anoDe(h));
const virada = computed(() => {
  const a = props.anterior && anoDe(props.anterior);
  const p = props.proxima && anoDe(props.proxima);
  if (props.anoAtual && p && p !== props.anoAtual) return { lado: "depois", de: props.anoAtual, para: p };
  if (props.anoAtual && a && a !== props.anoAtual) return { lado: "antes", de: a, para: props.anoAtual };
  return null;
});
</script>

<style scoped>
.hx-viz {
  margin-top: var(--tm-space-7);
  padding-top: var(--tm-space-5);
  border-top: 1px solid var(--tm-border);
}
.hx-viz__titulo {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.hx-viz__virada {
  display: inline-block;
  margin: 0 0 var(--tm-space-3);
  padding: 4px 14px;
  border-radius: var(--tm-radius-pill);
  background: var(--hx-ano-selo, #5b4513);
  color: #fff;
  font-size: var(--tm-fs-sm);
  font-weight: 600;
}
.hx-viz__cartoes {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-3);
}
.hx-viz__cartao {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: var(--tm-space-3) var(--tm-space-4);
  border: 1px solid var(--tm-border);
  border-left: 4px solid var(--hx-ano-cor);
  border-radius: var(--tm-radius-md);
  color: var(--tm-ink);
  background: var(--tm-paper);
  text-decoration: none;
  min-height: 96px;
}
.hx-viz__cartao:hover {
  background: var(--hx-balao);
}
.hx-viz__cartao:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
.hx-viz__cartao--depois {
  grid-column: 2;
  text-align: right;
  border-left-width: 1px;
  border-right: 4px solid var(--hx-ano-cor);
}
.hx-viz__dir {
  color: var(--tm-gold-text);
  font-size: var(--tm-fs-sm);
  font-weight: 700;
}
.hx-viz__data {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.hx-viz__regua {
  list-style: none;
  margin: var(--tm-space-4) 0 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-1);
  justify-content: center;
}
.hx-viz__ano {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
  min-height: 44px;
  border-radius: var(--tm-radius-pill);
  color: var(--tm-ink);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  text-decoration: none;
}
.hx-viz__ano:hover {
  background: var(--hx-balao);
}
.hx-viz__ano--atual {
  background: var(--tm-ink);
  color: var(--tm-paper);
}
.hx-viz__ano:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
}
@media (max-width: 599px) {
  .hx-viz__cartoes {
    grid-template-columns: 1fr;
  }
  .hx-viz__cartao--depois {
    grid-column: 1;
    order: -1; /* mobile: "Depois" primeiro */
  }
}
</style>
