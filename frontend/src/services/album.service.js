import api from "./api";

// Paginado: { data, links, meta }. params: { q, per_page }
export async function listarAlbuns(page = 1, params = {}) {
  const response = await api.get("/albuns", { params: { page, ...params } });
  return response.data;
}

export async function buscarAlbum(uuid) {
  const response = await api.get(`/albuns/${uuid}`);
  return response.data.data ?? response.data;
}

export async function criarAlbum(payload) {
  const response = await api.post("/albuns", payload);
  return response.data.data ?? response.data;
}

export async function atualizarAlbum(uuid, payload) {
  const response = await api.put(`/albuns/${uuid}`, payload);
  return response.data.data ?? response.data;
}

export async function excluirAlbum(uuid) {
  await api.delete(`/albuns/${uuid}`);
}

// Idempotentes (1-500 fotos).
export async function adicionarFotosAlbum(uuid, fotos) {
  const response = await api.post(`/albuns/${uuid}/fotos`, { fotos });
  return response.data.data ?? response.data;
}

export async function removerFotosAlbum(uuid, fotos) {
  const response = await api.delete(`/albuns/${uuid}/fotos`, { data: { fotos } });
  return response.data.data ?? response.data;
}
