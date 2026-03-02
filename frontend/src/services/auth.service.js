import api from "./api";
import { useAuthStore } from "@/stores/auth";

export async function login(email, senha) {
  const response = await api.post("/login", {
    login: email,
    senha,
  });

  localStorage.setItem("auth_token", response.data.token);

  const authStore = useAuthStore();
  authStore.usuario = response.data.usuario;

  return response.data;
}
