import api from "./api";
import { useAuthStore } from "@/stores/auth";

export async function login(email, senha) {
  const response = await api.post("/login", {
    login: email,
    senha,
  });

  localStorage.setItem("auth_token", response.data.token);

  const authStore = useAuthStore();
  authStore.setUsuario(response.data.usuario);

  return response.data;
}

export async function esqueciSenha(email) {
  const response = await api.post("/esqueci-senha", { email });
  return response.data;
}

export async function redefinirSenha({ token, email, senha }) {
  const response = await api.post("/redefinir-senha", { token, email, senha });
  return response.data;
}
