import api from "@/services/api";

export async function listarNotificacoes() {
  const response = await api.get("/notificacoes");
  return response.data;
}

/** Central de notificações: { status: 'todas'|'nao_lidas', page, per_page } -> { data, meta } */
export async function listarNotificacoesPaginado(params = {}) {
  const response = await api.get("/notificacoes", { params: { status: "todas", page: 1, ...params } });
  return response.data;
}

export async function contagemNotificacoes() {
  const response = await api.get("/notificacoes/contagem");
  return response.data;
}

export async function marcarLida(id) {
  const response = await api.post(`/notificacoes/${id}/marcar`);
  return response.data;
}

export async function marcarTodasLidas() {
  const response = await api.post("/notificacoes/marcar-todas");
  return response.data;
}

export async function criarNotificacao(payload) {
  const response = await api.post("/notificacoes", payload);
  return response.data;
}

/** Só ADM */
export async function listarNotificacoesEnviadas(params = {}) {
  const response = await api.get("/admin/notificacoes", { params });
  return response.data;
}

/** Só ADM: exclui para todos */
export async function excluirNotificacao(id) {
  const response = await api.delete(`/notificacoes/${id}`);
  return response.data;
}
