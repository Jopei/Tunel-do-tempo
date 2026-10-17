import { defineStore } from "pinia";
import api from "@/services/api";

const STORAGE_KEY = "tunel-auth";
const TOKEN_KEY = "auth_token";

export const PAPEIS_MEMBRO = ["Membro ADM", "Membro Rua"];
export const PAPEL_ADMIN = "Membro ADM";

function apiBase() {
  return (import.meta.env.VITE_API_BASE_URL || "").replace(/\/$/, "");
}

export const useAuthStore = defineStore("auth", {
  state: () => ({
    usuario: null,
    logado: false,
    fotoVersao: 0,
  }),

  getters: {
    papel: (state) => state.usuario?.tipo_usuario?.nome ?? state.usuario?.tipo_usuario ?? null,
    isAdmin() {
      return this.papel === PAPEL_ADMIN;
    },
    isMembro() {
      return PAPEIS_MEMBRO.includes(this.papel);
    },
    fotoPerfilUrl(state) {
      const u = state.usuario;
      if (!u) return null;
      const v = state.fotoVersao ? `?v=${state.fotoVersao}` : "";
      if (u.foto_url) return u.foto_url + v;
      if (u.foto?.uuid) return `${apiBase()}/fotos/${u.foto.uuid}${v}`;
      if (u.foto_perfil) {
        return apiBase().replace(/\/api$/, "") + "/storage/" + u.foto_perfil + v;
      }
      return null;
    },
  },

  actions: {
    temPapel(...papeis) {
      return papeis.includes(this.papel);
    },

    setUsuario(usuario) {
      this.usuario = usuario;
      this.logado = true;
      this.persistir();
    },

    persistir() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ usuario: this.usuario }));
    },

    carregarSessao() {
      const data = localStorage.getItem(STORAGE_KEY);
      if (!data) return;

      try {
        const parsed = JSON.parse(data);
        if (!parsed.usuario || !localStorage.getItem(TOKEN_KEY)) {
          this.limpar();
          return;
        }
        this.usuario = parsed.usuario;
        this.logado = true;
      } catch {
        this.limpar();
      }
    },

    /** Recarrega os dados completos do usuário logado (GET /usuarios/{uuid}). */
    async refreshUsuario() {
      if (!this.usuario?.uuid) return null;
      let atualizado;
      try {
        const { data } = await api.get("/me");
        const me = data.data ?? data;
        atualizado = { ...this.usuario, ...me, tipo_usuario: me.tipo_usuario?.nome ?? me.tipo_usuario ?? this.usuario.tipo_usuario };
        if (me.foto_perfil) atualizado.foto_url = null;
      } catch (e) {
        if (e.response?.status !== 404) throw e;
        // Fallback para backends sem /me
        const { data } = await api.get(`/usuarios/${this.usuario.uuid}`);
        const completo = data.data ?? data;
        atualizado = {
          ...this.usuario,
          nome: completo.nome,
          email: completo.email,
          telefone: completo.telefone,
          descricao: completo.descricao,
          aniversario: completo.aniversario,
          tipo_usuario: completo.tipo_usuario?.nome ?? this.usuario.tipo_usuario,
        };
        if (completo.foto?.uuid) {
          atualizado.foto_url = `${apiBase()}/fotos/${completo.foto.uuid}`;
          atualizado.foto_perfil = null;
        }
      }
      this.fotoVersao = Date.now();
      this.setUsuario(atualizado);
      return atualizado;
    },

    limpar() {
      this.usuario = null;
      this.logado = false;
      localStorage.removeItem(STORAGE_KEY);
      localStorage.removeItem(TOKEN_KEY);
    },

    logout() {
      // Avisa o servidor sem bloquear a UI.
      if (localStorage.getItem(TOKEN_KEY)) {
        api.post("/logout").catch(() => {});
      }
      this.limpar();
    },
  },
});
