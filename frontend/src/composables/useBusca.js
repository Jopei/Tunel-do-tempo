import { ref } from "vue";

export const buscaAberta = ref(false);
export function abrirBusca() {
  buscaAberta.value = true;
}
