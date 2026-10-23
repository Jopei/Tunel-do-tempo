<template>
  <Teleport to="body">
    <div v-if="aberto" class="vmenu-camada" @pointerdown.self="fechar" @contextmenu.self.prevent="fechar">
      <ul
        ref="menu"
        class="vmenu"
        :class="{ folha: xs }"
        role="menu"
        :aria-label="rotulo"
        :style="xs ? null : { left: `${pos.x}px`, top: `${pos.y}px` }"
        @keydown="aoTecla"
      >
        <template v-for="item in itens" :key="item.id">
          <li v-if="item.divisor" class="vmenu-divisor" role="separator"></li>
          <li v-else role="none">
            <button type="button" role="menuitem" class="vmenu-item" :class="{ perigo: item.perigo }" tabindex="-1" @click="escolher(item)">
              <v-icon size="20" aria-hidden="true">{{ item.icone }}</v-icon>
              <span>{{ item.titulo }}</span>
            </button>
          </li>
        </template>
      </ul>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from "vue";
import { useDisplay } from "vuetify";

const props = defineProps({
  aberto: Boolean,
  x: { type: Number, default: 0 },
  y: { type: Number, default: 0 },
  /** [{ id, titulo, icone, perigo?, divisor? }] */
  itens: { type: Array, default: () => [] },
  rotulo: { type: String, default: "Ações" },
  /** Elemento que recebe o foco ao fechar. */
  gatilho: { type: Object, default: null },
});
const emit = defineEmits(["escolher", "fechar"]);

const { xs } = useDisplay();
const menu = ref(null);
const pos = reactive({ x: 0, y: 0 });

watch(
  () => props.aberto,
  async (aberto) => {
    if (!aberto) return;
    pos.x = props.x;
    pos.y = props.y;
    await nextTick();
    const el = menu.value;
    if (!el) return;
    // Mantém dentro da viewport.
    const r = el.getBoundingClientRect();
    pos.x = Math.max(8, Math.min(props.x, window.innerWidth - r.width - 8));
    pos.y = Math.max(8, Math.min(props.y, window.innerHeight - r.height - 8));
    el.querySelector('[role="menuitem"]')?.focus();
  },
);

function fechar() {
  emit("fechar");
  nextTick(() => props.gatilho?.focus?.());
}

function escolher(item) {
  emit("escolher", item.id);
  emit("fechar");
}

function aoTecla(e) {
  const botoes = Array.from(menu.value?.querySelectorAll('[role="menuitem"]') ?? []);
  const i = botoes.indexOf(document.activeElement);
  if (e.key === "ArrowDown") botoes[(i + 1) % botoes.length]?.focus();
  else if (e.key === "ArrowUp") botoes[(i - 1 + botoes.length) % botoes.length]?.focus();
  else if (e.key === "Home") botoes[0]?.focus();
  else if (e.key === "End") botoes.at(-1)?.focus();
  else if (e.key === "Escape" || e.key === "Tab") {
    if (e.key === "Tab") e.preventDefault();
    fechar();
    return;
  } else return;
  e.preventDefault();
}
</script>

<style scoped>
.vmenu-camada {
  position: fixed;
  inset: 0;
  z-index: 2500;
}
.vmenu {
  position: fixed;
  min-width: 220px;
  margin: 0;
  padding: 6px;
  list-style: none;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-xs);
  box-shadow: var(--tm-shadow-md);
  transform-origin: top left;
}
.vmenu-item {
  width: 100%;
  min-height: 40px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 12px;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.vmenu-item:hover,
.vmenu-item:focus-visible {
  background: var(--tm-paper-2);
}
.vmenu-item.perigo {
  color: rgb(var(--v-theme-error));
}
.vmenu-divisor {
  height: 1px;
  margin: 6px 4px;
  background: var(--tm-border);
}
/* Mobile: folha inferior com itens de 48 px */
.vmenu.folha {
  left: 0;
  right: 0;
  bottom: 0;
  min-width: 0;
  border-radius: var(--tm-radius-md) var(--tm-radius-md) 0 0;
  padding: 8px 8px calc(8px + env(safe-area-inset-bottom));
}
.vmenu.folha .vmenu-item {
  min-height: 48px;
}

/* Menu de contexto nasce no ponto do clique: escala .96 -> 1 + fade. */
.vmenu {
  animation: vmenu-entra 120ms var(--tm-ease);
  transform-origin: top left;
}
@keyframes vmenu-entra {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
}
@media (prefers-reduced-motion: reduce) {
  .vmenu {
    animation: none;
  }
}
html[data-reduzir="1"] .vmenu {
  animation: none;
}
</style>
