import api from "./api";

export async function listarUsuarios() {
  const response = await api.get("/usuarios");
  return response.data;
}

export async function cadastrarUsuario(formData) {
  const response = await api.post("/cadastrar/usuarios", formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });

  return response.data;
}
