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

export async function atualizarUsuario(uuid, payload) {
  const response = await api.put(`/usuarios/${uuid}`, payload);
  return response.data;
}

export async function atualizarFotoPerfil(uuid, formData) {
  const response = await api.post(`/usuarios/${uuid}/foto-perfil`, formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  });
  return response.data;
}

export async function buscarUsuario(uuid) {
  const response = await api.get(`/usuarios/${uuid}`);
  return response.data;
}
