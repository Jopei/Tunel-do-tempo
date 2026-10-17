import { ref, onBeforeUnmount } from "vue";

const MB = 1024 * 1024;

export function formatarTamanho(bytes) {
  if (bytes >= MB) return `${(bytes / MB).toFixed(1).replace(".", ",")} MB`;
  return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function aceita(file, accept) {
  if (!accept) return true;
  return accept
    .split(",")
    .map((s) => s.trim())
    .some((a) => (a.endsWith("/*") ? file.type.startsWith(a.slice(0, -1)) : file.type === a || file.name.endsWith(a)));
}

/** Valida tipo e tamanho. Retorna { validos, mensagens }. */
export function validarArquivos(lista, { accept, maxMb }) {
  const validos = [];
  const mensagens = [];
  for (const f of Array.from(lista || [])) {
    if (!aceita(f, accept)) mensagens.push(`"${f.name}" não é um tipo de arquivo aceito.`);
    else if (maxMb && f.size > maxMb * MB) mensagens.push(`"${f.name}" passa de ${maxMb} MB.`);
    else validos.push(f);
  }
  return { validos, mensagens };
}

/** Gerencia lista de arquivos com previews (ACRESCENTA, sem duplicatas, revoga URLs). */
export function useListaArquivos({ previews = true } = {}) {
  const itens = ref([]); // { id, file, url }

  function chave(f) {
    return `${f.name}:${f.size}:${f.lastModified}`;
  }

  function adicionar(files) {
    for (const file of files) {
      const id = chave(file);
      if (itens.value.some((i) => i.id === id)) continue;
      itens.value.push({ id, file, url: previews ? URL.createObjectURL(file) : null });
    }
  }

  function remover(id) {
    const i = itens.value.findIndex((x) => x.id === id);
    if (i === -1) return;
    if (itens.value[i].url) URL.revokeObjectURL(itens.value[i].url);
    itens.value.splice(i, 1);
  }

  function limpar() {
    itens.value.forEach((i) => i.url && URL.revokeObjectURL(i.url));
    itens.value = [];
  }

  onBeforeUnmount(limpar);

  return { itens, adicionar, remover, limpar };
}

/**
 * Reduz fotos no cliente (lado maior <= max px, JPEG). Só processa se passar de alvoMb
 * (ou limiarMb), ou se não for JPEG/PNG. Baixa a qualidade/escala até caber em alvoMb.
 * Devolve o original se não valer a pena ou se algo falhar.
 */
export async function comprimirImagem(file, { max = 2048, qualidadeInicial = 0.85, limiarMb = 2, alvoMb = null } = {}) {
  if (!file.type.startsWith("image/")) return file;
  const tipoOk = file.type === "image/jpeg" || file.type === "image/png";
  const limite = (alvoMb ?? limiarMb) * MB;
  if (tipoOk && file.size <= Math.max(limite, limiarMb * MB)) return file;
  try {
    const bmp = await createImageBitmap(file);
    let melhor = null;
    for (const [lado, q] of [[max, qualidadeInicial], [max, 0.7], [1600, 0.7], [1280, 0.6], [1024, 0.5]]) {
      const escala = Math.min(1, lado / Math.max(bmp.width, bmp.height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.round(bmp.width * escala);
      canvas.height = Math.round(bmp.height * escala);
      canvas.getContext("2d").drawImage(bmp, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", q));
      if (!blob) break;
      melhor = blob;
      if (!alvoMb || blob.size <= alvoMb * MB) break;
    }
    bmp.close?.();
    if (!melhor) return file;
    if (tipoOk && melhor.size >= file.size) return file;
    const nome = file.name.replace(/\.[^.]+$/, "") + ".jpg";
    return new File([melhor], nome, { type: "image/jpeg", lastModified: file.lastModified });
  } catch {
    return file;
  }
}
