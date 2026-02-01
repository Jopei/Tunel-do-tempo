import api from "./api";

export async function buscarHistoriasPorLimite(limite = 7) {
  const response = await api.get(`/api/historias/limite/${limite}`);
  return response.data;
}

export async function buscarHistoriasResumo() {
  const response = await api.get("/api/historias/resumo");
  return response.data;
}

export async function atualizarHistoria(uuid, payload) {
  await api.put(`/api/historias/${uuid}`, payload);
}

export async function deletarHistoria(uuid) {
  await api.delete(`/api/historias/${uuid}`);
}
