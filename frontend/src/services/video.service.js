import api from "./api";

export async function listarVideos(page = 1) {
  const response = await api.get("/videos", {
    params: { page },
  });
  return response.data;
}

export async function excluirVideo(uuid) {
  await api.delete(`/videos/${uuid}`);
}

export async function atualizarVideo(uuid, payload) {
  await api.put(`/videos/${uuid}`, payload);
}

export async function cadastrarVideo(payload) {
  const response = await api.post("/cadastrar/videos", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return response.data;
}
