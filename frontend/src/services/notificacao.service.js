import api from "@/services/api";

export async function listarNotificacoes() {
  const response = await api.get("/notificacoes");
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
