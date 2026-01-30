import { defineStore } from "pinia";

const STORAGE_KEY = "tunel-auth";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    usuario: null,
    logado: false,
  }),

  actions: {
    setUsuario(usuario) {
      this.usuario = usuario;
      this.logado = true;

      localStorage.setItem(STORAGE_KEY, JSON.stringify({ usuario }));
    },

    carregarSessao() {
      const data = localStorage.getItem(STORAGE_KEY);

      if (!data) return;

      try {
        const parsed = JSON.parse(data);
        this.usuario = parsed.usuario;
        this.logado = true;
      } catch {
        this.logout();
      }
    },

    logout() {
      this.usuario = null;
      this.logado = false;
      localStorage.removeItem(STORAGE_KEY);
    },
  },
});
