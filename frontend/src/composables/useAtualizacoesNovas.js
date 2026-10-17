import { ref } from "vue";
import { listarAtualizacoes } from "@/services/atualizacao.service";

const CHAVE = "tunel-ult-atualizacao";
const temNova = ref(false);
let maisRecente = 0;

function ts(v) {
  const t = new Date(v).getTime();
  return Number.isNaN(t) ? 0 : t;
}

/** Verifica (uma vez por chamada) se há atualização mais recente que a última vista. */
export async function verificarAtualizacoesNovas() {
  try {
    const lista = await listarAtualizacoes();
    maisRecente = Math.max(0, ...(lista || []).map((a) => ts(a.created_at)));
    const visto = Number(localStorage.getItem(CHAVE) || 0);
    temNova.value = maisRecente > visto;
  } catch {
    temNova.value = false;
  }
}

export function marcarAtualizacoesVistas(lista) {
  const max = Math.max(0, ...(lista || []).map((a) => ts(a.created_at)));
  localStorage.setItem(CHAVE, String(max || Date.now()));
  temNova.value = false;
}

export function ultimaVistaTs() {
  return Number(localStorage.getItem(CHAVE) || 0);
}

export function useAtualizacoesNovas() {
  return { temNova, verificar: verificarAtualizacoesNovas };
}
