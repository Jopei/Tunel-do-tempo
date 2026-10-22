import { ref, watch } from "vue";
import { REDE, ANOS } from "@/config/esquina";
import { hueDoAno } from "@/composables/useCapitulos";
import { tempoRelativo, dataCompleta } from "@/composables/useFormat";

/* ---------- preferências em localStorage (nunca quebram se o storage estiver bloqueado) ---------- */
export function lerPref(chave, padrao = null) {
  try {
    const v = localStorage.getItem(chave);
    return v == null ? padrao : v;
  } catch {
    return padrao;
  }
}
export function gravarPref(chave, valor) {
  try {
    localStorage.setItem(chave, String(valor));
  } catch {
    /* sem storage: a preferência vale só nesta sessão */
  }
}

/* ---------- filtro de foto antiga (compartilhado; padrão DESLIGADO) ---------- */
const CHAVE_FILTRO = "tunel-fv-filtro";
export const filtroAntigo = ref(lerPref(CHAVE_FILTRO, "0") === "1");
watch(filtroAntigo, (v) => gravarPref(CHAVE_FILTRO, v ? "1" : "0"));

/* ---------- capítulos por ano (Antes, ano a ano, Depois, Sem data) ---------- */
const EXTRAS = {
  antes: { rotulo: "Antes da Rua", sub: `até ${REDE.anoInicio - 1}` },
  depois: { rotulo: "Depois da Rua", sub: `${REDE.anoFim + 1} em diante` },
  sem: { rotulo: "Sem data", sub: "ninguém lembra quando" },
};

/** ano numérico (ou null) -> "antes" | "2011".."2021" | "depois" | "sem" */
export function chaveDoAno(ano) {
  if (ano == null || Number.isNaN(Number(ano))) return "sem";
  const a = Number(ano);
  if (a < REDE.anoInicio) return "antes";
  if (a > REDE.anoFim) return "depois";
  return String(a);
}
/** "YYYY-..." -> ano numérico ou null */
export function anoDoTexto(texto) {
  const m = /^(\d{4})/.exec(String(texto ?? ""));
  return m ? Number(m[1]) : null;
}

export function metaCapitulo(chave) {
  const ano = /^\d{4}$/.test(chave) ? Number(chave) : null;
  return {
    chave,
    id: `fv-cap-${chave}`,
    ano,
    rotulo: ano ? String(ano) : EXTRAS[chave].rotulo,
    sub: ano ? "" : EXTRAS[chave].sub,
    selo: ano ? String(ano) : chave === "antes" ? "Antes" : chave === "depois" ? "Depois" : "S/D",
    hue: hueDoAno(ano),
  };
}

/** Ordem dos capítulos conforme a ordenação: recentes = Depois, anos desc, Antes, Sem data. */
export function ordemCapitulos(ordem = "recentes") {
  const anos = ANOS.map(String);
  return ordem === "antigas" ? ["antes", ...anos, "depois", "sem"] : ["depois", ...[...anos].reverse(), "antes", "sem"];
}

/** "há 12 anos" a partir só do ano (referência 1º de julho, evita "hoje"/"há 0 anos"). */
export function haAnos(ano, agora = new Date()) {
  if (ano == null) return "";
  const n = agora.getFullYear() - Number(ano);
  if (n <= 0) return "este ano";
  return `há ${n} ${n === 1 ? "ano" : "anos"}`;
}

export const pluralFotos = (n) => `${n} ${n === 1 ? "foto" : "fotos"}`;
export const pluralVideos = (n) => `${n} ${n === 1 ? "vídeo" : "vídeos"}`;

export { tempoRelativo, dataCompleta };
