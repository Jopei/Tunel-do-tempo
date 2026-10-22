<template>
  <section id="lembrancas" ref="raizRef" class="lembrancas" :class="{ 'lembrancas--comentarios': comentarios }" aria-labelledby="lemb-titulo">
    <h2 id="lemb-titulo" class="tm-title">
      {{ comentarios ? "Comentários" : "Deixar uma lembrança" }}
      <span v-if="lista.total.value != null" class="contador">({{ lista.total.value }})</span>
    </h2>

    <form novalidate @submit.prevent="enviar">
      <v-textarea
        ref="campoRef"
        v-model="texto"
        :label="comentarios ? 'Escreva uma lembrança…' : 'Escreva uma lembrança'"
        rows="3"
        auto-grow
        counter="2000"
        maxlength="2000"
        :error-messages="erro"
        :disabled="enviando"
      />
      <div class="acoes">
        <v-btn type="submit" variant="flat" color="primary" :loading="enviando" :disabled="enviando">{{ comentarios ? "Publicar" : "Deixar lembrança" }}</v-btn>
      </div>
    </form>

    <TmSkeletonGrid v-if="lista.carregando.value && !lista.itens.value.length" :quantidade="2" tipo="list-item-two-line" minimo="100%" />
    <TmErrorState v-else-if="lista.erro.value && !lista.itens.value.length" texto="Não conseguimos carregar as lembranças." @retry="lista.recarregar" />
    <p v-else-if="lista.vazio.value" class="vazio">
      {{ comentarios ? "Ninguém lembrou de nada ainda. Seja o primeiro." : "Ainda não há lembranças. Seja o primeiro a deixar uma." }}
    </p>

    <ul v-else class="itens">
      <li v-for="l in lista.itens.value" :key="l.uuid" class="item">
        <div class="topo">
          <AvatarUsuario :nome="l.usuario?.nome" :src="l.usuario?.foto_url" :size="32" />
          <div class="quem">
            <strong>{{ l.usuario?.nome || "Alguém da rua" }}</strong>
            <time :datetime="l.created_at">{{ dataRelativa(l.created_at) }}</time>
          </div>
          <v-btn
            v-if="l.pode_excluir"
            icon
            size="small"
            variant="text"
            color="error"
            aria-label="Excluir lembrança"
            @click="excluir(l)"
          >
            <v-icon>mdi-delete-outline</v-icon>
          </v-btn>
        </div>
        <p class="texto">{{ l.texto }}</p>
      </li>
    </ul>

    <div v-if="lista.temMais.value" class="text-center mt-3">
      <v-btn variant="tonal" :loading="lista.carregando.value" @click="lista.carregarProxima()">{{ comentarios ? "Ver comentários anteriores" : "Ver mais lembranças" }}</v-btn>
    </div>
  </section>
</template>

<script setup>
import { ref, nextTick, onMounted } from "vue";
import { animarEsquina } from "@/composables/useMovimentoEsquina";
import { listarLembrancas, criarLembranca, excluirLembranca } from "@/services/lembranca.service";
import { useListaPaginada } from "@/composables/useListaPaginada";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useConfirm } from "@/composables/useConfirm";
import { dataRelativa } from "@/composables/useFormat";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";

const props = defineProps({
  /** uuid da história; vazio = página da homenagem */
  historiaUuid: { type: String, default: null },
  /** visual e rótulos de rede social (página da história) */
  comentarios: { type: Boolean, default: false },
});
const emit = defineEmits(["mudou"]);

const { tratar } = useApiError();
const notify = useNotify();
const { confirmar } = useConfirm();

const campoRef = ref(null);
const raizRef = ref(null);
const texto = ref("");
const erro = ref("");
const enviando = ref(false);

const lista = useListaPaginada((page) => listarLembrancas(props.historiaUuid, { page, per_page: 10 }));

async function enviar() {
  erro.value = "";
  const t = texto.value.trim();
  if (t.length < 2) {
    erro.value = "Escreva ao menos 2 caracteres.";
    return;
  }
  enviando.value = true;
  try {
    const nova = await criarLembranca(props.historiaUuid, t);
    lista.itens.value.unshift(nova);
    // balão novo desliza e expande (300ms); só transform/opacity
    nextTick(() =>
      animarEsquina(raizRef.value?.querySelector(".item"), [{ opacity: 0, transform: "translateY(-8px) scaleY(0.85)", transformOrigin: "top center" }, { opacity: 1, transform: "none", transformOrigin: "top center" }], { duration: 300, easing: "ease-out" }),
    );
    if (lista.total.value != null) lista.total.value++;
    texto.value = "";
    notify.sucesso("Lembrança guardada.");
    emit("mudou", lista.total.value);
  } catch (e) {
    const info = tratar(e);
    erro.value = info.campos.texto || (info.status === 429 ? "Calma, tente de novo em instantes." : "");
  } finally {
    enviando.value = false;
  }
}

async function excluir(l) {
  const ok = await confirmar({ titulo: "Excluir esta lembrança?", texto: "Essa ação não pode ser desfeita." });
  if (!ok) return;
  try {
    await excluirLembranca(l.uuid);
    lista.itens.value = lista.itens.value.filter((i) => i.uuid !== l.uuid);
    if (lista.total.value != null) lista.total.value--;
    emit("mudou", lista.total.value);
  } catch (e) {
    tratar(e);
  }
}

defineExpose({ focar: () => campoRef.value?.focus() });

onMounted(() => lista.carregarProxima());
</script>

<style scoped>
.lembrancas {
  margin-top: var(--tm-space-7);
  padding-top: var(--tm-space-5);
  border-top: 1px solid var(--tm-border);
}
.lembrancas h2 {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-4);
}
.contador {
  font-size: var(--tm-fs-md);
  color: var(--tm-ink-muted);
  font-weight: 400;
}
.acoes {
  text-align: right;
  margin-bottom: var(--tm-space-4);
}
.vazio {
  color: var(--tm-ink-muted);
}
.itens {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-3);
}
.item {
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3) var(--tm-space-4);
  background: rgb(var(--v-theme-surface-variant));
}
.topo {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
}
.quem {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}
.quem time {
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.lembrancas--comentarios {
  border-top: 0;
  margin-top: var(--tm-space-6);
  scroll-margin-top: calc(var(--tm-navbar-h) + 72px);
}
.lembrancas--comentarios .item {
  background: var(--hx-balao, rgb(var(--v-theme-surface-variant)));
  border: 0;
  border-radius: var(--tm-radius-md) var(--tm-radius-md) var(--tm-radius-md) var(--tm-radius-xs);
}
.texto {
  margin: var(--tm-space-2) 0 0;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
</style>
