import api from "./api";

// params opcionais: { q, ano, usuario_uuid, ordem, per_page }
export async function listarFotos(page = 1, params = {}) {
  const response = await api.get("/fotos", {
    params: { page, ...params },
  });
  return response.data;
}

export async function buscarDetalhesFoto(uuid) {
  const response = await api.get(`/fotos/${uuid}/details`);
  return response.data.data ?? response.data;
}

export async function excluirFoto(uuid) {
  await api.delete(`/fotos/${uuid}`);
}

export async function atualizarFoto(uuid, payload) {
  await api.put(`/fotos/${uuid}`, payload);
}

export async function cadastrarFoto(payload, config = {}) {
  const response = await api.post("/fotos", payload, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
    ...config,
  });
  return response.data;
}

// Linha do tempo pública: [{ data: "YYYY-MM", total }], mais recente primeiro.
export async function listarLinhaDoTempo(params = {}) {
  const response = await api.get("/fotos/linha-do-tempo", { params });
  return response.data.data ?? response.data;
}

// Exclusão em lote (1-100). Devolve { excluidas, nao_encontradas }.
export async function excluirFotosLote(uuids) {
  const response = await api.post("/fotos/lote-excluir", { uuids });
  return response.data;
}

// Público: [{ ano, total, capa? }] por COALESCE(data_foto, created_at), ano desc.
export async function listarFotosAnos() {
  const response = await api.get("/fotos/anos");
  const d = response.data.data ?? response.data;
  return Array.isArray(d) ? d : [];
}

// Login: [{ ano, fotos: [item] }], ano desc.
export async function listarFotosNesteDia(limite = 24) {
  const response = await api.get("/fotos/neste-dia", { params: { limite } });
  const d = response.data.data ?? response.data;
  return Array.isArray(d) ? d : [];
}

// ADM/RUA: substitui a lista de pessoas marcadas.
export async function atualizarMarcadosFoto(uuid, usuariosUuid) {
  await api.patch(`/fotos/${uuid}/usuarios`, { usuarios_uuid: usuariosUuid });
}
