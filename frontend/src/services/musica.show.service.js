import api from "./api";

export async function buscarMusicaPorUuid(uuid) {
  const response = await api.get(`/musicas/${uuid}`, {
    responseType: "blob",
  });

  return URL.createObjectURL(response.data);
}
