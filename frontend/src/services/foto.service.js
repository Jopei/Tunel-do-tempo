import api from "./api";

// params opcionais: { q, ano, usuario_uuid, ordem, per_page }
export async function listarFotos(page = 1, params = {}) {
  const response = await api.get("/fotos", {
    params: { page, ...params },
  });
  return response.data;
}

export async function buscarDetalhesFoto(uuid) {
  const response = await api.get(`/fotos/${uuid}/details`);
  return response.data.data ?? response.data;
}

export async function excluirFoto(uuid) {
  await api.delete(`/fotos/${uuid}`);
}

export async function atualizarFoto(uuid, payload) {
  await api.put(`/fotos/${uuid}`, payload);
}

export async function cadastrarFoto(payload, config = {}) {
  const response = await api.post("/fotos", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
    ...config,
  });
  return response.data;
}
