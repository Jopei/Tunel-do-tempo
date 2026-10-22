import api from "./api";

// -> { pasta_atual: {uuid,nome}|null, caminho: [{uuid,nome}], pastas: [{uuid,nome,pai_uuid,videos_count}] }
export async function listarPastas(paiUuid = null) {
  const response = await api.get("/video-pastas", { params: paiUuid ? { pai_uuid: paiUuid } : {} });
  return response.data;
}

export async function criarPasta(nome, paiUuid = null) {
  const response = await api.post("/video-pastas", { nome, pai_uuid: paiUuid });
  return response.data.data ?? response.data;
}

export async function renomearPasta(uuid, nome) {
  const response = await api.put(`/video-pastas/${uuid}`, { nome });
  return response.data.data ?? response.data;
}

export async function moverPasta(uuid, paiUuid = null) {
  const response = await api.put(`/video-pastas/${uuid}`, { pai_uuid: paiUuid });
  return response.data.data ?? response.data;
}

// 409 se tiver vídeos ou subpastas.
export async function excluirPasta(uuid) {
  await api.delete(`/video-pastas/${uuid}`);
}
