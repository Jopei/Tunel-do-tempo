import { ref } from "vue";

/** Rascunho em localStorage com debounce. */
export function useRascunho(chave, atraso = 1000) {
  const storageKey = `tunel-rascunho:${chave}`;
  const salvoEm = ref(null);
  let timer = null;

  function ler() {
    try {
      const raw = localStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }

  function salvar(dados) {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const quando = new Date().toISOString();
      localStorage.setItem(storageKey, JSON.stringify({ dados, quando }));
      salvoEm.value = new Date(quando);
    }, atraso);
  }

  function limpar() {
    clearTimeout(timer);
    localStorage.removeItem(storageKey);
    salvoEm.value = null;
  }

  return { ler, salvar, limpar, salvoEm };
}
