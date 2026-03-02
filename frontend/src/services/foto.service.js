import api from "./api";

export async function listarFotos(page = 1) {
  const response = await api.get("/fotos", {
    params: { page },
  });
  return response.data;
}

export async function excluirFoto(uuid) {
  await api.delete(`/fotos/${uuid}`);
}

export async function atualizarFoto(uuid, payload) {
  await api.put(`/fotos/${uuid}`, payload);
}

export async function cadastrarFoto(payload) {
  const response = await api.post("/fotos", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });
  return response.data;
}
