import api from "@/services/api";

export async function favoritarHistoria(uuid) {
  const r = await api.post(`/historias/${uuid}/favorito`);
  return r.data;
}

export async function desfavoritarHistoria(uuid) {
  const r = await api.delete(`/historias/${uuid}/favorito`);
  return r.data;
}

export async function listarFavoritos(params = {}) {
  const r = await api.get("/me/favoritos", { params });
  return r.data;
}
