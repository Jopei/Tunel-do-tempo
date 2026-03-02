import api from "@/services/api";

export async function listarAtualizacoes() {
  const response = await api.get("/atualizacoes");
  return response.data.data ?? response.data;
}

export async function criarAtualizacao(payload) {
  const response = await api.post("/atualizacoes", payload);
  return response.data;
}

export async function atualizarAtualizacao(uuid, payload) {
  const response = await api.put(`/atualizacoes/${uuid}`, payload);
  return response.data;
}
