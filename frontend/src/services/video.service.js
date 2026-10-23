import api from "./api";

// params opcionais: { q, ano, usuario_uuid, ordem, per_page }
export async function listarVideos(page = 1, params = {}) {
  const response = await api.get("/videos", {
    params: { page, ...params },
  });
  return response.data;
}

export async function buscarDetalhesVideo(uuid) {
  const response = await api.get(`/videos/${uuid}/detalhes`);
  return response.data.data ?? response.data;
}

export async function excluirVideo(uuid) {
  await api.delete(`/videos/${uuid}`);
}

export async function atualizarVideo(uuid, payload) {
  await api.put(`/videos/${uuid}`, payload);
}

export async function cadastrarVideo(payload, config = {}) {
  const response = await api.post("/cadastrar/videos", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
    ...config,
  });

  return response.data;
}

// pastaUuid: null = tira da pasta (raiz).
export async function moverVideo(uuid, pastaUuid) {
  const response = await api.patch(`/videos/${uuid}/pasta`, { pasta_uuid: pastaUuid ?? null });
  return response.data;
}

// Login: [{ ano: 2015|null, total }], ano desc; null = sem data (por último).
// params opcionais: { pasta_uuid, todas, q }
export async function listarVideosAnos(params = {}) {
  const response = await api.get("/videos/anos", { params });
  const d = response.data.data ?? response.data;
  return Array.isArray(d) ? d : [];
}
