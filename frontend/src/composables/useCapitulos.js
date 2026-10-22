import { REDE, ANOS } from "@/config/esquina";
import { parseDataLocal } from "@/composables/useFormat";

const HUES = [38, 32, 26, 20, 12, 4, 355, 345, 335, 322, 308]; // esfriam de 2011 a 2021
const HUE_EXTRA = 45;

export const anoDe = (h) => parseDataLocal(h?.data_historia)?.getFullYear() ?? null;

export function hueDoAno(ano) {
  const i = ANOS.indexOf(ano);
  return i >= 0 ? HUES[i] : HUE_EXTRA;
}

/** chave do capítulo: "antes" | "2011".."2021" | "depois" | "sem" */
export function chaveCapitulo(h) {
  const a = anoDe(h);
  if (a == null) return "sem";
  if (a < REDE.anoInicio) return "antes";
  if (a > REDE.anoFim) return "depois";
  return String(a);
}

const META = {
  antes: { rotulo: "Antes da Rua", sub: `até ${REDE.anoInicio - 1}` },
  depois: { rotulo: "Depois da Rua", sub: `${REDE.anoFim + 1} em diante` },
  sem: { rotulo: "Sem data", sub: "ninguém lembra quando" },
};

/**
 * Agrupa em capítulos. `lista` já vem na ordem desejada dos posts.
 * Cronológica: Antes, 2011..2021, Depois, Sem data. Invertida: Depois, 2021..2011, Antes, Sem data.
 */
export function agruparCapitulos(lista, invertida = false) {
  const mapa = new Map();
  for (const h of lista) {
    const k = chaveCapitulo(h);
    if (!mapa.has(k)) mapa.set(k, []);
    mapa.get(k).push(h);
  }
  const anos = ANOS.map(String);
  const ordem = invertida ? ["depois", ...[...anos].reverse(), "antes", "sem"] : ["antes", ...anos, "depois", "sem"];
  return ordem
    .filter((k) => mapa.has(k))
    .map((k) => {
      const itens = mapa.get(k);
      const ano = /^\d{4}$/.test(k) ? Number(k) : null;
      return {
        chave: k,
        id: `ano-${k === "sem" ? "sem-data" : k}`,
        ano,
        rotulo: ano ? String(ano) : META[k].rotulo,
        sub: ano ? "" : META[k].sub,
        hue: hueDoAno(ano),
        itens,
      };
    });
}

/** Ordenação cronológica ascendente estável; sem data por último. */
export function ordenarAsc(lista) {
  return [...lista].sort((a, b) => {
    const da = parseDataLocal(a.data_historia)?.getTime() ?? Infinity;
    const db = parseDataLocal(b.data_historia)?.getTime() ?? Infinity;
    return da === db ? 0 : da - db;
  });
}
