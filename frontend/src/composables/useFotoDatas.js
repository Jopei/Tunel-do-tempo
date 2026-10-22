// Data de referência da foto: data_foto (YYYY-MM-DD) ou, na falta, o dia de created_at.
const pad = (n) => String(n).padStart(2, "0");

export function diaDaFoto(foto) {
  if (foto?.data_foto) return String(foto.data_foto).slice(0, 10);
  if (!foto?.created_at) return "";
  const d = new Date(foto.created_at);
  if (Number.isNaN(d.getTime())) return String(foto.created_at).slice(0, 10);
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export const mesDaFoto = (foto) => diaDaFoto(foto).slice(0, 7);

function dataLocal(dia) {
  const [a, m, d] = dia.split("-").map(Number);
  return new Date(a, m - 1, d || 1);
}

export function rotuloDia(dia) {
  if (!dia) return "Sem data";
  const txt = dataLocal(dia).toLocaleDateString("pt-BR", { weekday: "short", day: "numeric", month: "long", year: "numeric" });
  return txt.charAt(0).toUpperCase() + txt.slice(1);
}

/** "2019-03" -> "mar. 2019" */
export function rotuloMes(mes) {
  if (!mes) return "";
  return dataLocal(`${mes}-01`).toLocaleDateString("pt-BR", { month: "short", year: "numeric" });
}

/** Agrupa (na ordem recebida) em [{ chave: dia, mes, rotulo, itens }]. */
export function agruparPorDia(fotos) {
  const grupos = [];
  let atual = null;
  for (const f of fotos) {
    const dia = diaDaFoto(f);
    if (!atual || atual.chave !== dia) {
      atual = { chave: dia || "sem-data", mes: dia.slice(0, 7), rotulo: rotuloDia(dia), itens: [] };
      grupos.push(atual);
    }
    atual.itens.push(f);
  }
  return grupos;
}

export const urlMiniatura = (foto) => foto?.thumbnail_url || foto?.url;
