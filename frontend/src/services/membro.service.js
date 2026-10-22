import api from "./api";

let cachePublicos = null;

/** Público (sem login): membros da rua. Item: { uuid, slug?, nome, foto_url }. Cache por sessão da aba. */
export async function listarMembrosPublicos({ forcar = false } = {}) {
  if (cachePublicos && !forcar) return cachePublicos;
  const r = await api.get("/public/membros");
  const lista = Array.isArray(r.data) ? r.data : (r.data?.data ?? []);
  cachePublicos = lista;
  return lista;
}

/** Login: { uuid, slug, nome, foto_url, descricao, historias[], historia_pessoal|null }. */
export async function buscarMembro(slug) {
  const r = await api.get(`/membros/${encodeURIComponent(slug)}`);
  return r.data?.data ?? r.data;
}

/** Identificador usado nas URLs: slug, com uuid como fallback enquanto o backend não gera slugs. */
export const chaveMembro = (m) => m.slug || m.uuid;
