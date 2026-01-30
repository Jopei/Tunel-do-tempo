import api from "./api";

export async function login(email, senha) {
  const response = await api.post("/login", {
    login: email,
    senha,
  });

  localStorage.setItem("auth_token", response.data.token);

  return response.data;
}
