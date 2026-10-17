<template>
  <v-dialog v-model="aberto" max-width="460">
    <v-card aria-labelledby="atalhos-titulo">
      <v-card-title id="atalhos-titulo" class="tm-title">Atalhos de teclado</v-card-title>
      <v-card-text>
        <dl class="lista">
          <template v-for="a in ATALHOS" :key="a.teclas">
            <dt><kbd v-for="t in a.teclas.split(' ')" :key="t">{{ t }}</kbd></dt>
            <dd>{{ a.descricao }}</dd>
          </template>
        </dl>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" color="on-surface" @click="aberto = false">Fechar</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";

const ATALHOS = [
  { teclas: "?", descricao: "Mostrar esta ajuda" },
  { teclas: "/", descricao: "Buscar (na lista de histórias)" },
  { teclas: "g h", descricao: "Ir para o início" },
  { teclas: "g c", descricao: "Ir para as histórias" },
  { teclas: "g f", descricao: "Ir para as fotos" },
  { teclas: "g v", descricao: "Ir para os vídeos" },
  { teclas: "Esc", descricao: "Fechar menus e janelas" },
];

const DESTINOS = { h: "/", c: "/historias", f: "/galeria-fotos", v: "/galeria-videos" };

const router = useRouter();
const aberto = ref(false);
let aguardandoG = false;
let timer = null;

function aoTecla(e) {
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  const alvo = e.target;
  if (alvo?.tagName === "INPUT" || alvo?.tagName === "TEXTAREA" || alvo?.tagName === "SELECT" || alvo?.isContentEditable) return;

  if (aguardandoG) {
    aguardandoG = false;
    clearTimeout(timer);
    const destino = DESTINOS[e.key.toLowerCase()];
    if (destino) router.push(destino);
    return;
  }
  if (e.key === "?") {
    aberto.value = true;
  } else if (e.key === "g") {
    aguardandoG = true;
    timer = setTimeout(() => (aguardandoG = false), 1200);
  }
}

onMounted(() => window.addEventListener("keydown", aoTecla));
onBeforeUnmount(() => window.removeEventListener("keydown", aoTecla));
</script>

<style scoped>
.lista {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: var(--tm-space-2) var(--tm-space-4);
  align-items: center;
}
dt {
  display: flex;
  gap: 4px;
}
dd {
  margin: 0;
}
kbd {
  min-width: 28px;
  text-align: center;
  padding: 2px 8px;
  border: 1px solid var(--tm-border);
  border-bottom-width: 2px;
  border-radius: var(--tm-radius-xs);
  background: rgb(var(--v-theme-surface-variant));
  font-family: var(--tm-font-body);
  font-size: var(--tm-fs-sm);
}
</style>
