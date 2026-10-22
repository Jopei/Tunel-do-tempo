<template>
  <section class="fotos-page fv-era tm-immersive" :class="{ 'fv-filtro-on': filtroAntigo }">
    <h1 class="sr-only">Fotos</h1>
    <FotosExplorador
      ref="explorador"
      :q="q"
      :ordem="ordem"
      :escala="escala"
      :destaques="!q"
      @limpar-busca="q = ''"
    >
      <template #contexto="{ capitulos, irCapitulo }">
        <FotosAbas
          v-model:q="buscaInput"
          v-model:ordem="ordem"
          v-model:densidade="densidade"
          ativa="fotos"
          :logado="auth.logado"
          :pode-enviar="auth.isMembro"
          :capitulos="capitulos"
          @enviar="explorador?.escolherArquivos()"
          @ir-ano="irCapitulo"
        />
      </template>
    </FotosExplorador>
  </section>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import FotosExplorador from "@/components/fotos/FotosExplorador.vue";
import FotosAbas from "@/components/fotos/FotosAbas.vue";
import { filtroAntigo, lerPref, gravarPref } from "@/composables/useEraFotosVideos";
import "@/assets/historias-era.css";
import "@/assets/fotos-videos-era.css";

const CHAVE_DENSIDADE = "tunel-fotos-densidade";
const ESCALAS = { compacta: 0.8, normal: 1, ampla: 1.3 };

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const explorador = ref(null);

const buscaInput = ref(String(route.query.q ?? ""));
const q = ref(buscaInput.value.trim());
const ordem = ref(route.query.ordem === "antigas" ? "antigas" : "recentes");
const densidade = ref(lerPref(CHAVE_DENSIDADE, "normal"));
const escala = computed(() => ESCALAS[densidade.value] ?? 1);

let t = null;
watch(buscaInput, (v) => {
  clearTimeout(t);
  t = setTimeout(() => (q.value = (v ?? "").trim()), 300);
});
watch(q, (v) => {
  if (buscaInput.value !== v) buscaInput.value = v;
});
watch(densidade, (v) => gravarPref(CHAVE_DENSIDADE, v));

watch([q, ordem], () => {
  const query = { ...route.query };
  if (q.value) query.q = q.value;
  else delete query.q;
  if (ordem.value !== "recentes") query.ordem = ordem.value;
  else delete query.ordem;
  router.replace({ query });
});
</script>

<style scoped>
.fotos-page {
  min-height: 100dvh;
  padding-top: var(--tm-navbar-h);
  background: var(--tm-paper);
  color: var(--tm-ink);
}
</style>
