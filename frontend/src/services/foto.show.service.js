import api from "./api";

export async function buscarFotoPorUuid(uuid) {
  const response = await api.get(`/fotos/${uuid}`, {
    responseType: "blob",
  });

  return URL.createObjectURL(response.data);
}
