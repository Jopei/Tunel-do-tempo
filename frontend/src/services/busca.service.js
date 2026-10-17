import api from "@/services/api";

export async function buscar(q, config = {}) {
  const r = await api.get("/busca", { params: { q }, ...config });
  return r.data.data ?? r.data;
}
