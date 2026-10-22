import api from "./api";

// params: { q, ordem: recentes|antigas|titulo|artista|album, per_page }
export async function listarMusicas(page = 1, params = {}) {
  const response = await api.get("/musicas", { params: { page, ...params } });
  return response.data;
}

export async function atualizarMusica(uuid, payload) {
  const response = await api.put(`/musicas/${uuid}`, payload);
  return response.data.data ?? response.data;
}

export async function excluirMusica(uuid) {
  await api.delete(`/musicas/${uuid}`);
}

export async function cadastrarMusica(formData, config = {}) {
  const response = await api.post("/cadastrar/musicas", formData, {
    headers: { "Content-Type": "multipart/form-data" },
    ...config,
  });
  return response.data;
}

/** GET /musicas/{uuid}/detalhes: item com url assinada nova e url_expira_em. null se não existe. */
export async function renovarUrlMusica(faixa) {
  try {
    const response = await api.get(`/musicas/${faixa.uuid}/detalhes`);
    return response.data.data ?? response.data;
  } catch (e) {
    if (e?.response?.status === 404) return null;
    throw e;
  }
}
