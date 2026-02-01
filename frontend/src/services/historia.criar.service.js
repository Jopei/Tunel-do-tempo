import api from "./api";

export async function criarHistoria(payload) {
  const response = await api.post("/api/cadastrar/historias", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return response.data;
}
