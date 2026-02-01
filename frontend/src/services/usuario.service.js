import api from "./api";

export async function listarUsuarios() {
  const response = await api.get("/api/usuarios");
  return response.data;
}
