import api from "./api";

export async function criarHistoria(payload, config = {}) {
  const response = await api.post("/cadastrar/historias", payload, config);
  return response.data;
}
