import { ref, computed } from "vue";

/**
 * Seleção múltipla por uuid, com intervalo (shift) em cima da ordem visível.
 * ordem: ref/computed com o array de uuids na ordem em que aparecem.
 */
export function useSelecao(ordem) {
  const marcados = ref(new Set());
  const ancora = ref(null);

  const quantidade = computed(() => marcados.value.size);
  const ativa = computed(() => marcados.value.size > 0);
  const lista = computed(() => Array.from(marcados.value));

  const tem = (uuid) => marcados.value.has(uuid);

  function definir(conjunto) {
    marcados.value = new Set(conjunto);
  }

  function alternar(uuid) {
    const n = new Set(marcados.value);
    if (n.has(uuid)) n.delete(uuid);
    else n.add(uuid);
    marcados.value = n;
    ancora.value = uuid;
  }

  function marcar(uuid) {
    if (marcados.value.has(uuid)) return;
    alternar(uuid);
  }

  /** Marca o intervalo entre a última âncora e o uuid dado. */
  function intervalo(uuid) {
    const todos = ordem.value;
    const a = todos.indexOf(ancora.value);
    const b = todos.indexOf(uuid);
    if (a < 0 || b < 0) {
      alternar(uuid);
      return;
    }
    const [de, ate] = a < b ? [a, b] : [b, a];
    const n = new Set(marcados.value);
    for (let i = de; i <= ate; i++) n.add(todos[i]);
    marcados.value = n;
  }

  /** Alterna um grupo inteiro: se todos marcados, desmarca; senão marca todos. */
  function alternarGrupo(uuids) {
    const n = new Set(marcados.value);
    const todos = uuids.every((u) => n.has(u));
    uuids.forEach((u) => (todos ? n.delete(u) : n.add(u)));
    marcados.value = n;
  }

  function remover(uuids) {
    const n = new Set(marcados.value);
    uuids.forEach((u) => n.delete(u));
    marcados.value = n;
  }

  function limpar() {
    marcados.value = new Set();
    ancora.value = null;
  }

  return { marcados, quantidade, ativa, lista, tem, definir, alternar, marcar, intervalo, alternarGrupo, remover, limpar };
}
