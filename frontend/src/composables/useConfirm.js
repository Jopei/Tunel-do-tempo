import { reactive } from "vue";

export const confirmState = reactive({
  aberto: false,
  titulo: "",
  texto: "",
  rotuloConfirmar: "Excluir",
  rotuloCancelar: "Cancelar",
  destrutivo: true,
  _resolve: null,
});

/** Abre o ConfirmDialog global e devolve uma promessa<boolean>. */
export function useConfirm() {
  function confirmar({
    titulo = "Tem certeza?",
    texto = "",
    rotuloConfirmar = "Excluir",
    rotuloCancelar = "Cancelar",
    destrutivo = true,
  } = {}) {
    return new Promise((resolve) => {
      Object.assign(confirmState, {
        aberto: true,
        titulo,
        texto,
        rotuloConfirmar,
        rotuloCancelar,
        destrutivo,
        _resolve: resolve,
      });
    });
  }
  return { confirmar };
}

export function resolverConfirm(valor) {
  confirmState.aberto = false;
  confirmState._resolve?.(valor);
  confirmState._resolve = null;
}
