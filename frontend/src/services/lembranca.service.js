import api from "@/services/api";

/** contexto: { historiaUuid } ou null para a página da homenagem */
function base(historiaUuid) {
  return historiaUuid ? `/historias/${historiaUuid}/lembrancas` : "/homenagem/lembrancas";
}

export async function listarLembrancas(historiaUuid, params = {}) {
  const r = await api.get(base(historiaUuid), { params });
  return r.data;
}

export async function criarLembranca(historiaUuid, texto) {
  const r = await api.post(base(historiaUuid), { texto });
  return r.data.data ?? r.data;
}

export async function excluirLembranca(uuid) {
  await api.delete(`/lembrancas/${uuid}`);
}
