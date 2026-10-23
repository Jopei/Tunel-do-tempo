<template>
  <section class="player-fila" :class="{ embutida }" aria-label="Fila de reprodução">
    <header class="fila-cab">
      <h2 class="fila-titulo">Fila · {{ player.fila.length }} {{ player.fila.length === 1 ? "faixa" : "faixas" }}</h2>
      <v-btn variant="text" size="small" @click="player.limparFila()">Limpar</v-btn>
      <v-btn v-if="!embutida" icon variant="text" size="small" aria-label="Fechar fila" @click="player.filaAberta = false">
        <v-icon>mdi-close</v-icon>
      </v-btn>
    </header>
    <ol ref="listaRef" class="fila-lista">
      <template v-for="{ f, i, chave } in itens" :key="chave">
        <li v-if="i === 0 || i === player.indice || i === player.indice + 1" class="fila-secao" role="presentation">
          {{ i === player.indice ? "Tocando agora" : i < player.indice ? "Tocadas" : "A seguir" }}
        </li>
        <li
          class="fila-item"
          :class="{ atual: i === player.indice, 'arrastando-sobre': sobre === i }"
          :data-chave="chave"
          :aria-current="i === player.indice ? 'true' : undefined"
          draggable="true"
          @dragstart="(e) => comecarArrasto(e, i)"
          @dragover.prevent="sobre = i"
          @dragleave="sobre = -1"
          @drop.prevent="soltar(i)"
          @dragend="sobre = -1"
        >
          <button type="button" class="fila-tocar" :aria-label="`Tocar ${f.titulo}`" @click="player.irPara(i)">
            <MusicaCapa :src="f.capa_url" :tamanho="40" />
            <span class="fila-texto">
              <strong>{{ f.titulo }}</strong>
              <small>{{ f.artista || "Artista desconhecido" }}</small>
            </span>
            <span class="fila-dur">{{ f.duracao_segundos ? formatarDuracao(f.duracao_segundos) : "" }}</span>
          </button>
          <v-menu location="bottom end">
            <template #activator="{ props }">
              <v-btn v-bind="props" icon variant="text" size="small" class="tm-touch" :aria-label="`Opções de ${f.titulo} na fila`">
                <v-icon>mdi-dots-vertical</v-icon>
              </v-btn>
            </template>
            <v-list density="comfortable">
              <v-list-item prepend-icon="mdi-arrow-up" title="Mover para cima" :disabled="i === 0" @click="mover(i, -1)" />
              <v-list-item
                prepend-icon="mdi-arrow-down"
                title="Mover para baixo"
                :disabled="i === player.fila.length - 1"
                @click="mover(i, 1)"
              />
              <v-list-item prepend-icon="mdi-close" title="Remover da fila" @click="remover(i)" />
            </v-list>
          </v-menu>
        </li>
      </template>
    </ol>
  </section>
</template>

<script setup>
import { ref, computed, onBeforeUpdate, onUpdated } from "vue";
import gsap from "gsap";
import { usePlayerStore } from "@/stores/player";
import { formatarDuracao } from "@/composables/useFormat";
import { prefereMenosMovimento } from "@/composables/useTema";
import MusicaCapa from "./MusicaCapa.vue";

defineProps({ embutida: Boolean });
const player = usePlayerStore();

const sobre = ref(-1);
const listaRef = ref(null);
let origem = -1;

// Chave estável por faixa (uuid + ocorrência): ao reordenar, o elemento é reaproveitado e pode deslizar.
const itens = computed(() => {
  const cont = new Map();
  return player.fila.map((f, i) => {
    const n = (cont.get(f.uuid) ?? -1) + 1;
    cont.set(f.uuid, n);
    return { f, i, chave: `${f.uuid}:${n}` };
  });
});

/* ---------- reordenar/remover com deslizamento (FLIP por transform) ---------- */
let reordenando = false;
let antes = null;
onBeforeUpdate(() => {
  if (!reordenando || !listaRef.value) return;
  antes = new Map();
  listaRef.value.querySelectorAll(".fila-item[data-chave]").forEach((el) => antes.set(el.dataset.chave, el.getBoundingClientRect().top));
});
onUpdated(() => {
  if (!antes) return;
  const posicoes = antes;
  antes = null;
  reordenando = false;
  if (prefereMenosMovimento() || !listaRef.value) return;
  listaRef.value.querySelectorAll(".fila-item[data-chave]").forEach((el) => {
    const topo = posicoes.get(el.dataset.chave);
    if (topo == null) return;
    const dy = topo - el.getBoundingClientRect().top;
    if (Math.abs(dy) > 1) gsap.fromTo(el, { y: dy }, { y: 0, duration: 0.25, ease: "power2.out", clearProps: "transform" });
  });
});
function mover(i, delta) {
  reordenando = true;
  player.moverNaFila(i, delta);
}
function remover(i) {
  reordenando = true;
  player.remover(i);
}

function comecarArrasto(e, i) {
  origem = i;
  e.dataTransfer.effectAllowed = "move";
  e.dataTransfer.setData("text/plain", String(i));
}
function soltar(i) {
  sobre.value = -1;
  if (origem >= 0 && origem !== i) mover(origem, i - origem);
  origem = -1;
}
</script>

<style scoped>
.player-fila {
  position: fixed;
  right: 0;
  top: var(--tm-navbar-h);
  bottom: var(--tm-player-h, 72px);
  width: 360px;
  max-width: 100vw;
  z-index: 1850;
  display: flex;
  flex-direction: column;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border-left: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-md);
}
.player-fila.embutida {
  position: static;
  width: 100%;
  box-shadow: none;
  border: 0;
  flex: 1;
  min-height: 0;
}
.fila-cab {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 8px 8px 16px;
  border-bottom: 1px solid var(--tm-border);
}
.fila-titulo {
  flex: 1;
  margin: 0;
  font-size: var(--tm-fs-lg);
}
.fila-lista {
  list-style: none;
  margin: 0;
  padding: 0 8px 16px;
  overflow-y: auto;
  flex: 1;
}
.fila-secao {
  padding: 12px 8px 4px;
  font-size: var(--tm-fs-xs);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--tm-ink-muted);
}
.fila-item {
  display: flex;
  align-items: center;
  border-radius: var(--tm-radius-xs);
}
.fila-item.atual {
  background: color-mix(in srgb, var(--tm-gold) 16%, transparent);
}
.fila-item.arrastando-sobre {
  box-shadow: 0 -2px 0 var(--tm-gold);
}
.fila-tocar {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 8px;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.fila-texto {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  line-height: 1.3;
}
.fila-texto strong,
.fila-texto small {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.fila-texto small {
  color: var(--tm-ink-muted);
}
.fila-dur {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
  font-variant-numeric: tabular-nums;
}
</style>
