import api from "./api";

export async function buscarVideoPorUuid(uuid) {
  const response = await api.get(`/videos/${uuid}`, {
    responseType: "blob",
  });

  return URL.createObjectURL(response.data);
}
