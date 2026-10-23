<template>
  <div class="vlinhas-wrap">
    <table class="vlinhas">
      <caption class="sr-only">Vídeos da pasta</caption>
      <thead>
        <tr>
          <th scope="col" class="col-check"><span class="sr-only">Selecionar</span></th>
          <th scope="col" :aria-sort="ariaSort('nome')">
            <button type="button" class="vl-ordem" @click="$emit('ordenar', 'nome')">Nome<v-icon v-if="sort.campo === 'nome'" size="16">{{ icone }}</v-icon></button>
          </th>
          <th scope="col" class="col-por"><span class="vl-th">Enviado por</span></th>
          <th scope="col" class="col-data"><span class="vl-th">Data</span></th>
          <th scope="col" class="col-dur" :aria-sort="ariaSort('duracao')">
            <button type="button" class="vl-ordem" @click="$emit('ordenar', 'duracao')">Duração<v-icon v-if="sort.campo === 'duracao'" size="16">{{ icone }}</v-icon></button>
          </th>
          <th scope="col" class="col-tam" :aria-sort="ariaSort('tamanho')">
            <button type="button" class="vl-ordem" @click="$emit('ordenar', 'tamanho')">Tamanho<v-icon v-if="sort.campo === 'tamanho'" size="16">{{ icone }}</v-icon></button>
          </th>
          <th scope="col" class="col-acoes"><span class="sr-only">Ações</span></th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="v in itens"
          :key="v.uuid"
          class="vl-linha"
          :class="{ selecionado: selecionados.has(v.uuid) }"
          :data-uuid="v.uuid"
          :draggable="podeArrastar"
          :aria-selected="selecionados.has(v.uuid)"
          @dragstart="$emit('arrastar', $event, v)"
          @contextmenu.prevent="(e) => menuPonteiro(e, v)"
          @keydown="(e) => aoTecla(e, v)"
        >
          <td class="col-check">
            <button
              type="button"
              class="vl-check"
              role="checkbox"
              :aria-checked="selecionados.has(v.uuid)"
              :aria-label="selecionados.has(v.uuid) ? `Desmarcar ${v.titulo}` : `Selecionar ${v.titulo}`"
              @click="$emit('check', v, $event)"
            >
              <v-icon size="16" aria-hidden="true">mdi-check</v-icon>
            </button>
          </td>
          <td class="col-nome">
            <button type="button" class="vl-abrir" :aria-label="`Abrir vídeo ${v.titulo}`" @click="$emit('abrir', v, $event)">
              <img v-if="v.poster_url || v.thumbnail" :src="v.poster_url || v.thumbnail" alt="" class="vl-mini" loading="lazy" draggable="false" />
              <span v-else class="vl-mini vl-mini-vazio" aria-hidden="true"><v-icon size="20">mdi-play-circle-outline</v-icon></span>
              <span class="vl-nome">
                <span class="vl-titulo">{{ v.titulo }}</span>
                <small class="vl-sub">{{ subtitulo(v) }}</small>
              </span>
            </button>
          </td>
          <td class="col-por">{{ v.cadastrado_por?.nome ?? "—" }}</td>
          <td class="col-data">{{ v.created_at ? formatarData(v.created_at, { day: "2-digit", month: "short", year: "numeric" }) : "—" }}</td>
          <td class="col-dur">{{ v.duracao_segundos ? formatarDuracao(v.duracao_segundos) : "—" }}</td>
          <td class="col-tam">{{ v.tamanho_bytes ? formatarTamanho(v.tamanho_bytes) : "—" }}</td>
          <td class="col-acoes">
            <v-btn
              icon
              size="small"
              variant="text"
              class="tm-touch"
              aria-haspopup="menu"
              :aria-label="`Ações do vídeo ${v.titulo}`"
              @click="(e) => menuBotao(e, v)"
            >
              <v-icon>mdi-dots-vertical</v-icon>
            </v-btn>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { formatarDuracao, formatarData } from "@/composables/useFormat";
import { formatarTamanho } from "@/composables/useArquivos";

const props = defineProps({
  itens: { type: Array, default: () => [] },
  selecionados: { type: Set, default: () => new Set() },
  sort: { type: Object, default: () => ({ campo: "nome", dir: "asc" }) },
  podeArrastar: Boolean,
});
const emit = defineEmits(["abrir", "check", "menu", "arrastar", "ordenar"]);

const icone = computed(() => (props.sort.dir === "asc" ? "mdi-arrow-up" : "mdi-arrow-down"));
const ariaSort = (campo) => (props.sort.campo === campo ? (props.sort.dir === "asc" ? "ascending" : "descending") : "none");
const subtitulo = (v) => [v.cadastrado_por?.nome ? `Enviado por ${v.cadastrado_por.nome}` : null, v.duracao_segundos ? formatarDuracao(v.duracao_segundos) : null, v.tamanho_bytes ? formatarTamanho(v.tamanho_bytes) : null].filter(Boolean).join(" · ");

function menuBotao(e, v) {
  const el = e.currentTarget;
  const r = el.getBoundingClientRect();
  emit("menu", v, { x: r.left, y: r.bottom }, el);
}
function menuPonteiro(e, v) {
  emit("menu", v, { x: e.clientX, y: e.clientY }, e.currentTarget.querySelector(".vl-abrir"));
}
function aoTecla(e, v) {
  if (e.key === "ContextMenu" || (e.key === "F10" && e.shiftKey)) {
    e.preventDefault();
    const botao = e.currentTarget.querySelector(".vl-abrir");
    const r = botao.getBoundingClientRect();
    emit("menu", v, { x: r.left + 24, y: r.bottom }, botao);
  } else if (e.key === " " && e.target.classList?.contains("vl-abrir")) {
    e.preventDefault();
    emit("check", v, e);
  }
}
</script>

<style scoped>
.vlinhas-wrap {
  overflow-x: auto;
}
.vlinhas {
  width: 100%;
  border-collapse: collapse;
  color: var(--tm-ink);
}
.vlinhas th {
  text-align: left;
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  color: var(--tm-ink-muted);
  padding: 4px 8px;
  border-bottom: 1px solid var(--tm-border);
}
.vl-ordem {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 40px;
  padding: 0 4px;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
}
.vl-linha {
  height: 48px;
  border-bottom: 1px solid var(--tm-border);
}
.vl-linha:hover,
.vl-linha:focus-within {
  background: var(--tm-paper-2);
}
.vl-linha.selecionado {
  background: color-mix(in srgb, var(--tm-gold) 14%, var(--tm-paper));
}
.vl-linha td {
  padding: 4px 8px;
}
.vl-linha[draggable="true"] {
  cursor: grab;
}
.vl-th {
  padding: 0 4px;
}
.col-por,
.col-data {
  white-space: nowrap;
  color: var(--tm-ink-muted);
}
.col-por {
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.col-check {
  width: 44px;
}
.col-dur,
.col-tam {
  width: 96px;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.col-acoes {
  width: 52px;
  text-align: right;
}
.vl-check {
  width: 24px;
  height: 24px;
  padding: 0;
  display: grid;
  place-items: center;
  border: 2px solid var(--tm-ink-muted);
  border-radius: 50%;
  background: transparent;
  color: transparent;
  cursor: pointer;
}
.selecionado .vl-check {
  background: var(--tm-gold);
  border-color: var(--tm-gold);
  color: #2a2112;
}
.vl-abrir {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 44px;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.vl-mini {
  width: 56px;
  height: 32px;
  border-radius: 4px;
  object-fit: cover;
  background: var(--tm-paper-2);
  flex: none;
}
.vl-mini-vazio {
  display: grid;
  place-items: center;
  color: var(--tm-ink-muted);
}
.vl-nome {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.vl-titulo {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.vl-sub {
  display: none;
  color: var(--tm-ink-muted);
}
@media (max-width: 599px) {
  .vl-linha {
    height: 56px;
  }
  .col-tam,
  .col-por,
  .col-data,
  .col-dur {
    display: none;
  }
  .vl-sub {
    display: block;
  }
}
</style>
