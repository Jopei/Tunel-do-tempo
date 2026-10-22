<template>
  <TmFormDialog v-model="dialog" :titulo="titulo" max-width="520" :bloqueado="salvando">
    <p v-if="erro" class="mover-erro" role="alert">{{ erro }}</p>

    <ul class="arvore" role="tree" aria-label="Pastas de vídeos" @keydown="aoTecla">
      <li
        v-for="(l, i) in linhas"
        :key="l.no.uuid ?? 'raiz'"
        role="treeitem"
        class="arvore-item"
        :aria-level="l.nivel + 1"
        :aria-expanded="l.no.filhos && !l.no.filhos.length ? undefined : l.no.aberto"
        :aria-selected="escolhido === l.no.uuid"
        :aria-disabled="!!l.motivo"
      >
        <div class="arvore-linha" :style="{ paddingLeft: `${l.nivel * 20}px` }" :class="{ escolhida: escolhido === l.no.uuid, desabilitada: !!l.motivo }">
          <button
            v-if="!l.no.filhos || l.no.filhos.length"
            type="button"
            class="arvore-seta"
            tabindex="-1"
            :aria-label="l.no.aberto ? `Recolher ${l.no.nome}` : `Expandir ${l.no.nome}`"
            @click="alternar(l.no)"
          >
            <v-progress-circular v-if="l.no.carregando" indeterminate size="16" width="2" />
            <v-icon v-else size="20">{{ l.no.aberto ? "mdi-chevron-down" : "mdi-chevron-right" }}</v-icon>
          </button>
          <span v-else class="arvore-seta" aria-hidden="true"></span>
          <button
            type="button"
            class="arvore-escolher"
            :data-linha="i"
            :tabindex="i === foco ? 0 : -1"
            :disabled="!!l.motivo"
            :title="l.motivo || undefined"
            @click="escolher(l.no)"
            @focus="foco = i"
          >
            <v-icon size="20" class="arvore-icone" aria-hidden="true">{{ l.no.uuid === null ? "mdi-home-outline" : "mdi-folder" }}</v-icon>
            <span class="arvore-nome">{{ l.no.nome }}</span>
            <small v-if="l.motivo" class="arvore-motivo">{{ l.motivo }}</small>
          </button>
        </div>
      </li>
    </ul>

    <v-btn variant="text" prepend-icon="mdi-folder-plus-outline" class="mt-2" :disabled="salvando" @click="novaAberta = true">
      Nova pasta {{ escolhidoSet && escolhido !== null ? "aqui" : "na raiz" }}
    </v-btn>

    <template #acoes>
      <v-btn variant="text" color="on-surface" :disabled="salvando" @click="dialog = false">Cancelar</v-btn>
      <v-btn variant="flat" color="primary" :disabled="!escolhidoSet" :loading="salvando" @click="confirmar">Mover aqui</v-btn>
    </template>

    <NomeDialog
      v-model="novaAberta"
      titulo="Nova pasta"
      rotulo="Nome da pasta"
      rotulo-acao="Criar"
      :salvando="criando"
      :erro="erroNova"
      @salvar="criarAqui"
    />
  </TmFormDialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from "vue";
import { listarPastas, criarPasta } from "@/services/video.pasta.service";
import { interpretarErro } from "@/composables/useApiError";
import TmFormDialog from "@/components/ui/TmFormDialog.vue";
import NomeDialog from "./NomeDialog.vue";

const props = defineProps({
  modelValue: Boolean,
  titulo: { type: String, default: "Mover para" },
  /** Pasta onde os itens estão hoje (desabilitada como destino). null = raiz. */
  origemUuid: { type: String, default: null },
  /** Pastas que estão sendo movidas (ela e suas subpastas não são destinos válidos). */
  pastasMovidas: { type: Array, default: () => [] },
  salvando: Boolean,
  erro: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue", "mover"]);

const dialog = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const raiz = reactive({ uuid: null, nome: "Raiz", filhos: null, aberto: true, carregando: false });
const escolhido = ref(null);
const escolhidoSet = ref(false);
const foco = ref(0);
const novaAberta = ref(false);
const criando = ref(false);
const erroNova = ref("");

const novoNo = (p) => reactive({ uuid: p.uuid, nome: p.nome, filhos: null, aberto: false, carregando: false });

async function carregarFilhos(no) {
  no.carregando = true;
  try {
    const r = await listarPastas(no.uuid);
    no.filhos = (r.pastas ?? []).map(novoNo);
  } catch {
    no.filhos = [];
  } finally {
    no.carregando = false;
  }
}

async function alternar(no) {
  no.aberto = !no.aberto;
  if (no.aberto && no.filhos === null) await carregarFilhos(no);
}

const linhas = computed(() => {
  const lista = [];
  const visitar = (no, nivel, bloqueadoPai) => {
    let motivo = "";
    if (no.uuid !== null && (bloqueadoPai || props.pastasMovidas.includes(no.uuid))) motivo = "Não pode mover para dentro dela mesma";
    else if (no.uuid === props.origemUuid) motivo = "Pasta atual";
    lista.push({ no, nivel, motivo });
    if (no.aberto && no.filhos) {
      const bloq = bloqueadoPai || (no.uuid !== null && props.pastasMovidas.includes(no.uuid));
      no.filhos.forEach((f) => visitar(f, nivel + 1, bloq));
    }
  };
  visitar(raiz, 0, false);
  return lista;
});

function escolher(no) {
  escolhido.value = no.uuid;
  escolhidoSet.value = true;
}

function confirmar() {
  const no = linhas.value.find((l) => l.no.uuid === escolhido.value)?.no;
  emit("mover", { uuid: escolhido.value, nome: no?.nome ?? "Raiz" });
}

function aoTecla(e) {
  const botoes = Array.from(e.currentTarget.querySelectorAll(".arvore-escolher"));
  const i = botoes.indexOf(document.activeElement);
  if (i < 0) return;
  const no = linhas.value[i]?.no;
  if (e.key === "ArrowDown") botoes[Math.min(i + 1, botoes.length - 1)].focus();
  else if (e.key === "ArrowUp") botoes[Math.max(i - 1, 0)].focus();
  else if (e.key === "ArrowRight" && no && !no.aberto) alternar(no);
  else if (e.key === "ArrowLeft" && no?.aberto && no.uuid !== null) no.aberto = false;
  else return;
  e.preventDefault();
}

async function criarAqui(nome) {
  criando.value = true;
  erroNova.value = "";
  try {
    const pai = escolhidoSet.value ? escolhido.value : null;
    await criarPasta(nome, pai);
    const no = linhas.value.find((l) => l.no.uuid === pai)?.no ?? raiz;
    await carregarFilhos(no);
    no.aberto = true;
    novaAberta.value = false;
  } catch (e) {
    erroNova.value = interpretarErro(e).mensagem;
  } finally {
    criando.value = false;
  }
}

watch(
  () => props.modelValue,
  async (aberto) => {
    if (!aberto) return;
    escolhido.value = null;
    escolhidoSet.value = false;
    raiz.filhos = null;
    raiz.aberto = true;
    foco.value = 0;
    await carregarFilhos(raiz);
    await nextTick();
  },
);
</script>

<style scoped>
.arvore {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 50dvh;
  overflow-y: auto;
}
.arvore-linha {
  display: flex;
  align-items: center;
  border-radius: var(--tm-radius-xs);
}
.arvore-linha.escolhida {
  background: color-mix(in srgb, var(--tm-gold) 18%, transparent);
  box-shadow: inset 0 0 0 2px var(--tm-gold);
}
.arvore-seta {
  width: 32px;
  height: 40px;
  flex: none;
  display: grid;
  place-items: center;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}
.arvore-escolher {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 8px;
  border: 0;
  border-radius: var(--tm-radius-xs);
  background: transparent;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.arvore-escolher:hover:not(:disabled) {
  background: var(--tm-paper-2);
}
.arvore-escolher:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}
.arvore-icone {
  color: var(--tm-gold);
}
.arvore-nome {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.arvore-motivo {
  margin-left: auto;
  color: var(--tm-ink-muted);
  white-space: nowrap;
}
.mover-erro {
  color: rgb(var(--v-theme-error));
  margin: 0 0 8px;
}
</style>
