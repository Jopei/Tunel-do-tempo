import { useNotify } from "@/composables/useNotify";

/**
 * Interpreta um erro do axios. Retorna { status, mensagem, campos }.
 * campos: { nomeDoCampo: "primeira mensagem" } vindo do 422 do Laravel.
 */
export function interpretarErro(e) {
  const status = e?.response?.status ?? null;
  const data = e?.response?.data;
  const campos = {};

  if (status === 422 && data?.errors) {
    for (const [campo, msgs] of Object.entries(data.errors)) {
      campos[campo] = Array.isArray(msgs) ? msgs[0] : String(msgs);
    }
  }

  let mensagem;
  if (!e?.response) mensagem = "Sem conexão. Verifique a internet e tente de novo.";
  else if (status === 422) mensagem = Object.values(campos)[0] || data?.message || "Confira os dados informados.";
  else if (status === 401) mensagem = "Sua sessão expirou. Entre novamente.";
  else if (status === 403) mensagem = "Você não tem permissão para isso.";
  else if (status === 404) mensagem = "Não encontramos o que você procurou.";
  else if (status >= 500) mensagem = "Algo deu errado do nosso lado. Tente novamente em instantes.";
  else mensagem = data?.message || "Não foi possível concluir. Tente novamente.";

  return { status, mensagem, campos };
}

export function useApiError() {
  const notify = useNotify();

  /** Mostra toast (exceto 401/403/rede, já tratados pelo interceptor) e devolve o erro interpretado. */
  function tratar(e, { silencioso = false } = {}) {
    const info = interpretarErro(e);
    if (!silencioso && info.status !== 401 && info.status !== 403 && e?.response) {
      notify.erro(info.mensagem);
    }
    return info;
  }

  /** Leva o foco ao primeiro campo com erro (procura [aria-invalid="true"]). */
  function focarPrimeiroInvalido(raiz = document) {
    requestAnimationFrame(() => {
      raiz
        .querySelector?.('[aria-invalid="true"], .v-input--error input, .v-input--error textarea, .tm-invalido')
        ?.focus?.();
    });
  }

  return { interpretarErro, tratar, focarPrimeiroInvalido };
}
