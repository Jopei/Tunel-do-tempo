export function formatarData(valor, opts = { day: "numeric", month: "long", year: "numeric" }) {
  if (!valor) return "";
  const d = new Date(valor);
  if (Number.isNaN(d.getTime())) return String(valor);
  return d.toLocaleDateString("pt-BR", opts);
}

export function dataRelativa(valor) {
  if (!valor) return "";
  const d = new Date(valor);
  if (Number.isNaN(d.getTime())) return "";
  const seg = Math.round((d.getTime() - Date.now()) / 1000);
  const rtf = new Intl.RelativeTimeFormat("pt-BR", { numeric: "auto" });
  const faixas = [
    ["year", 31536000],
    ["month", 2592000],
    ["day", 86400],
    ["hour", 3600],
    ["minute", 60],
  ];
  for (const [unid, s] of faixas) {
    if (Math.abs(seg) >= s) return rtf.format(Math.round(seg / s), unid);
  }
  return "agora há pouco";
}

export function iniciais(nome = "") {
  return nome
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? "")
    .join("");
}

export function tempoLeitura(html = "") {
  const texto = String(html).replace(/<[^>]*>/g, " ");
  const palavras = texto.split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.round(palavras / 200));
}

export function textoPuro(html = "") {
  return String(html).replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
}

/** Máscara (99) 99999-9999 */
export function mascaraTelefone(valor = "") {
  const d = String(valor).replace(/\D/g, "").slice(0, 11);
  if (d.length <= 2) return d ? `(${d}` : "";
  if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
  if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;
  return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

/** Força simples da senha: 0 (vazia) a 4. */
export function forcaSenha(s = "") {
  if (!s) return 0;
  let n = 0;
  if (s.length >= 8) n++;
  if (s.length >= 12) n++;
  if (/[A-Z]/.test(s) && /[a-z]/.test(s)) n++;
  if (/\d/.test(s) && /[^A-Za-z0-9]/.test(s)) n++;
  return Math.max(1, n);
}

/** 75 -> "1:15"; 3725 -> "1:02:05" */
export function formatarDuracao(segundos) {
  const s = Math.max(0, Math.round(Number(segundos) || 0));
  const h = Math.floor(s / 3600);
  const m = Math.floor((s % 3600) / 60);
  const r = String(s % 60).padStart(2, "0");
  return h ? `${h}:${String(m).padStart(2, "0")}:${r}` : `${m}:${r}`;
}

/** 75 -> "1 minuto e 15 segundos" (para aria-valuetext) */
export function duracaoPorExtenso(segundos) {
  const s = Math.max(0, Math.round(Number(segundos) || 0));
  const m = Math.floor(s / 60);
  const r = s % 60;
  const partes = [];
  if (m) partes.push(`${m} ${m === 1 ? "minuto" : "minutos"}`);
  if (r || !m) partes.push(`${r} ${r === 1 ? "segundo" : "segundos"}`);
  return partes.join(" e ");
}

/** Interpreta "YYYY-MM-DD" como data local (new Date() a trataria como UTC e recuaria um dia no Brasil). */
export function parseDataLocal(valor) {
  if (!valor) return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(valor));
  const d = m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : new Date(valor);
  return Number.isNaN(d.getTime()) ? null : d;
}

/** "14 de junho de 2014" (sem deslocamento de fuso). */
export function dataCompleta(valor) {
  const d = parseDataLocal(valor);
  return d ? d.toLocaleDateString("pt-BR", { day: "numeric", month: "long", year: "numeric" }) : "";
}

/** "há 7 anos", "há 3 meses", "há 5 dias", "hoje". Nunca é a única fonte: acompanhe de <time> + data completa. */
export function tempoRelativo(valor, agora = new Date()) {
  const d = parseDataLocal(valor);
  if (!d) return "";
  if (d > agora) return dataCompleta(valor);
  const meses = (agora.getFullYear() - d.getFullYear()) * 12 + (agora.getMonth() - d.getMonth()) - (agora.getDate() < d.getDate() ? 1 : 0);
  if (meses >= 12) {
    const a = Math.floor(meses / 12);
    return `há ${a} ${a === 1 ? "ano" : "anos"}`;
  }
  if (meses >= 1) return `há ${meses} ${meses === 1 ? "mês" : "meses"}`;
  const dias = Math.floor((new Date(agora.getFullYear(), agora.getMonth(), agora.getDate()) - d) / 86400000);
  if (dias <= 0) return "hoje";
  return `há ${dias} ${dias === 1 ? "dia" : "dias"}`;
}
