import api from "@/services/api";

export async function buscarFotosOrbita() {
  const response = await api.get("/orbita/fotos");
  return response.data;
}
