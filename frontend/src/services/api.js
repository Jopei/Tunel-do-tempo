import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: "application/json",
    "X-API-KEY": import.meta.env.VITE_API_KEY,
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

let avisoRedeEm = 0;

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;
    const url = error.config?.url ?? "";
    const ehLogin = url.includes("/login") || url.includes("/logout");

    // Imports dinâmicos evitam dependência circular (router -> views -> services).
    const [{ toast }, { useAuthStore }] = await Promise.all([
      import("vue3-toastify"),
      import("@/stores/auth"),
    ]);

    if (status === 401 && !ehLogin) {
      const auth = useAuthStore();
      if (auth.logado) {
        auth.limpar();
        toast.error("Sua sessão expirou. Entre novamente.", { autoClose: 7000 });
        const { default: router } = await import("@/router");
        const atual = router.currentRoute.value;
        if (atual.name !== "login") {
          router.replace({ name: "login", query: { redirect: atual.fullPath } });
        }
      }
    } else if (status === 403) {
      toast.error("Você não tem permissão para isso.", { autoClose: 7000 });
    } else if (!error.response && error.code !== "ERR_CANCELED") {
      // Evita empilhar o mesmo aviso de rede
      if (Date.now() - avisoRedeEm > 5000) {
        avisoRedeEm = Date.now();
        toast.error("Sem conexão. Verifique a internet.", { autoClose: 7000 });
      }
    }

    return Promise.reject(error);
  },
);

export default api;
