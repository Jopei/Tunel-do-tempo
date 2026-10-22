<template>
  <section>
    <TmEmptyState
      v-if="status === 404"
      class="nao-achado"
      icone="mdi-account-off-outline" titulo="Este membro não foi encontrado">
      <v-btn to="/a-rua" variant="flat" color="primary">Voltar à rua</v-btn>
    </TmEmptyState>
    <component
      :is="componente"
      v-else
      :membro="membro"
      :tema="tema"
      :simbolo="simbolo"
      :carregando="carregando"
      :erro="erro"
      :anterior="vizinhos.anterior"
      :proximo="vizinhos.proximo"
      @retry="carregar"
    />
  </section>
</template>

<script setup>
import { ref, shallowRef, computed, watch, onMounted, defineAsyncComponent } from "vue";
import { useRoute, useRouter } from "vue-router";
import { buscarMembro, listarMembrosPublicos, chaveMembro } from "@/services/membro.service";
import { entradaDoMembro } from "@/membros/registry";
import { finalizarTransicao } from "@/composables/useTransicao";
import MembroPaginaPadrao from "@/membros/MembroPaginaPadrao.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";

const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

const route = useRoute();
const router = useRouter();
const membro = ref(null);
const carregando = ref(true);
const erro = ref(false);
const status = ref(null);
const lista = ref([]);
const componente = shallowRef(MembroPaginaPadrao);

const entrada = computed(() => entradaDoMembro(route.params.slug));
const simbolo = computed(() => entrada.value?.simbolo ?? "");
const tema = computed(() => entrada.value?.cores ?? {});

const vizinhos = computed(() => {
  const chaves = lista.value.map(chaveMembro);
  const i = chaves.indexOf(route.params.slug);
  if (i < 0 || chaves.length < 2) return { anterior: null, proximo: null };
  return {
    anterior: chaves[(i - 1 + chaves.length) % chaves.length],
    proximo: chaves[(i + 1) % chaves.length],
  };
});

function resolverComponente() {
  const e = entrada.value;
  componente.value = e?.componente ? defineAsyncComponent(e.componente) : MembroPaginaPadrao;
}

async function carregar() {
  const slug = route.params.slug;
  carregando.value = true;
  erro.value = false;
  status.value = null;

  try {
    lista.value = await listarMembrosPublicos();
  } catch {
    lista.value = [];
  }
  if (slug !== route.params.slug) return; // navegou para outro membro no meio do caminho

  // Links antigos (/membros/<uuid>): troca pelo slug quando conhecido.
  if (UUID.test(slug)) {
    const m = lista.value.find((x) => x.uuid === slug);
    if (m?.slug) {
      router.replace({ name: "membro", params: { slug: m.slug } });
      return;
    }
  }

  if (entrada.value?.redirect) {
    router.replace(entrada.value.redirect);
    return;
  }

  resolverComponente();
  try {
    const dados = await buscarMembro(slug);
    if (slug !== route.params.slug) return;
    membro.value = dados;
    document.title = `${membro.value.nome} · Túnel do Tempo`;
  } catch (e) {
    if (slug !== route.params.slug) return;
    membro.value = null;
    status.value = e.response?.status ?? null;
    erro.value = status.value !== 404;
  } finally {
    if (slug === route.params.slug) carregando.value = false;
  }
}

watch(
  () => route.params.slug,
  (n, o) => n && n !== o && route.name === "membro" && carregar(),
);
onMounted(async () => {
  await carregar();
  finalizarTransicao();
  document.getElementById("mp-nome")?.focus({ preventScroll: true });
});
</script>

<style scoped>
.nao-achado {
  padding-top: calc(var(--tm-navbar-h) + 48px);
}
</style>
