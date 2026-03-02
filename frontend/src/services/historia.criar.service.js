import api from "./api";

export async function criarHistoria(payload) {
  const response = await api.post("/cadastrar/historias", payload);
  return response.data;
}
