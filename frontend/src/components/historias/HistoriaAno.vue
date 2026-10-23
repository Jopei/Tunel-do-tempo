<template>
  <section :id="capitulo.id" ref="raizRef" class="hx-ano" :class="{ 'hx-ano--lista': lista, 'is-visivel': marcoVisto }" :style="{ '--hx-ano-h': capitulo.hue }" :aria-labelledby="`${capitulo.id}-t`">
    <header class="hx-ano__marco">
      <span class="hx-ano__selo hx-selo hx-grao" aria-hidden="true">{{ capitulo.ano ?? seloExtra }}</span>
      <div class="hx-ano__titulos">
        <h2 :id="`${capitulo.id}-t`" ref="tituloRef" class="hx-ano__titulo tm-title" tabindex="-1">
          {{ capitulo.rotulo }}<span v-if="capitulo.sub" class="hx-ano__sub-extra"> ({{ capitulo.sub }})</span>
        </h2>
        <p class="hx-ano__resumo">{{ resumo }}</p>
        <span v-if="presentes.length" class="hx-ano__presentes" :aria-label="`Mais presentes: ${presentes.map((u) => u.nome).join(', ')}`">
          <AvatarUsuario v-for="u in presentes" :key="u.uuid" :nome="u.nome" :src="u.foto_url" :size="28" class="hx-ano__presente" />
        </span>
      </div>
    </header>

    <ul class="hx-ano__posts">
      <li v-for="(h, i) in capitulo.itens" :key="h.uuid" class="hx-ano__post">
        <slot :historia="h" :destaque="i === destaqueIdx && h.uuid === destaqueUuid" />
      </li>
    </ul>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import { revelarAoRolar } from "@/composables/useMovimentoEsquina";

const props = defineProps({
  capitulo: { type: Object, required: true },
  /** modo Lista: linhas compactas, sem fio de timeline */
  lista: { type: Boolean, default: false },
});

const tituloRef = ref(null);
defineExpose({ focar: () => tituloRef.value?.focus({ preventScroll: true }) });

// Movimento: o marco pina e o fio desenha quando entra na tela; cada post entra uma vez (cascata).
const raizRef = ref(null);
const marcoVisto = ref(false);
const revelarMarco = revelarAoRolar(() => (marcoVisto.value = true));
const revelarPosts = revelarAoRolar((el, i) => {
  el.style.setProperty("--hx-atraso", `${i * 60}ms`);
  el.classList.add("is-visivel");
});
function observarPosts() {
  if (!raizRef.value) return;
  revelarMarco.observar([...raizRef.value.querySelectorAll(".hx-ano__marco")]);
  revelarPosts.observar([...raizRef.value.querySelectorAll(".hx-ano__post")]);
}
onMounted(observarPosts);
watch(
  () => props.capitulo.itens.length,
  () => nextTick(observarPosts),
);
onBeforeUnmount(() => {
  revelarMarco.parar();
  revelarPosts.parar();
});

const seloExtra = computed(() => ({ antes: "‹", depois: "›", sem: "?" })[props.capitulo.chave] ?? "");
const fotos = computed(() => props.capitulo.itens.reduce((s, h) => s + (h.fotos_count || 0), 0));
const resumo = computed(() => {
  const n = props.capitulo.itens.length;
  const p = [`${n} ${n === 1 ? "história" : "histórias"}`];
  if (fotos.value) p.push(`${fotos.value} ${fotos.value === 1 ? "foto" : "fotos"}`);
  return p.join(" · ");
});

// Quem mais aparece no ano (até 4)
const presentes = computed(() => {
  const mapa = new Map();
  props.capitulo.itens.forEach((h) =>
    (h.usuarios ?? []).forEach((u) => {
      const e = mapa.get(u.uuid) ?? { u, n: 0 };
      e.n++;
      mapa.set(u.uuid, e);
    }),
  );
  return [...mapa.values()].sort((a, b) => b.n - a.n).slice(0, 4).map((e) => e.u);
});

// Marco do ano: primeiro post Principal (só capítulos com ano)
const destaqueIdx = computed(() => (props.capitulo.ano ? props.capitulo.itens.findIndex((h) => h.tipo_historia === "Principal") : -1));
const destaqueUuid = computed(() => props.capitulo.itens[destaqueIdx.value]?.uuid);
</script>

<style scoped>
.hx-ano {
  scroll-margin-top: calc(var(--tm-navbar-h) + 64px);
  margin-bottom: var(--tm-space-6);
}
.hx-ano__marco {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  background: var(--tm-paper);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3) var(--tm-space-4);
  margin-bottom: var(--tm-space-4);
  box-shadow: var(--tm-shadow-sm);
}
.hx-ano__titulos {
  min-width: 0;
}
.hx-ano__titulo {
  font-size: var(--tm-fs-2xl);
  margin: 0;
  color: var(--tm-ink);
}
.hx-ano__titulo:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: 3px;
}
.hx-ano__sub-extra {
  font-size: var(--tm-fs-md);
  font-weight: 400;
  color: var(--tm-ink-muted);
}
.hx-ano__resumo {
  margin: 0;
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.hx-ano__presentes {
  display: inline-flex;
  margin-top: 6px;
}
.hx-ano__presente + .hx-ano__presente {
  margin-left: -8px;
}
.hx-ano__posts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--hx-gap-post);
}
.hx-ano__post {
  position: relative;
}

@media (min-width: 960px) {
  .hx-ano {
    display: grid;
    grid-template-columns: 220px minmax(0, var(--hx-feed-w));
    gap: var(--tm-space-5);
    justify-content: center;
    align-items: start;
  }
  .hx-ano__marco {
    position: sticky;
    top: calc(var(--tm-navbar-h) + 72px);
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 0;
  }
  /* linha do tempo: fio vertical com um ponto dourado por post */
  .hx-ano__posts {
    position: relative;
    border-left: 3px solid var(--hx-ano-suave);
    padding-left: 0;
    margin-left: -14px;
    padding-inline-start: 0;
  }
  .hx-ano__post::before {
    content: "";
    position: absolute;
    left: -20px;
    top: 28px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--tm-gold);
    border: 3px solid var(--tm-paper);
    box-shadow: 0 0 0 1px var(--tm-border);
    z-index: 1;
  }
  .hx-ano__posts > li {
    margin-left: 14px;
  }
}
@media (min-width: 960px) {
  .hx-ano--lista {
    grid-template-columns: 220px minmax(0, 860px);
  }
  .hx-ano--lista .hx-ano__posts {
    border-left: 0;
    margin-left: 0;
  }
  .hx-ano--lista .hx-ano__post::before {
    display: none;
  }
  .hx-ano--lista .hx-ano__posts > li {
    margin-left: 0;
  }
}
.hx-ano--lista .hx-ano__posts {
  gap: var(--tm-space-2);
}
@media (max-width: 599px) {
  .hx-ano__marco {
    border-radius: 0;
    border-inline: 0;
  }
  .hx-ano__posts {
    gap: var(--hx-gap-post);
  }
}
</style>
