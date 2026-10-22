// Helpers de movimento da Esquina (histórias). Só transform/opacity.
// Tudo respeita prefers-reduced-motion e data-reduzir: sem reduzir movimento, nada é animado por JS.
import { prefereMenosMovimento } from "@/composables/useTema";

export function reduzirMovimentoEsquina() {
  return prefereMenosMovimento();
}

/** Anima via Web Animations API (só transform/opacity). Devolve a animação ou null. */
export function animarEsquina(el, quadros, opcoes = {}) {
  if (!el || typeof el.animate !== "function" || reduzirMovimentoEsquina()) return null;
  return el.animate(quadros, { fill: "none", ...opcoes });
}

/**
 * Observa elementos e chama `aoEntrar(el, indice)` uma única vez por elemento quando entram na tela.
 * Lotes entram em cascata (índice 0–3, cada um cabe no atraso de 60ms). Sem IntersectionObserver ou com movimento reduzido: entra direto.
 * Devolve { observar(lista), parar() }.
 */
export function revelarAoRolar(aoEntrar) {
  const semIO = typeof IntersectionObserver === "undefined" || reduzirMovimentoEsquina();
  let io = null;
  if (!semIO) {
    io = new IntersectionObserver(
      (entradas) => {
        const dentro = entradas.filter((e) => e.isIntersecting).map((e) => e.target);
        dentro.forEach((el, i) => {
          io.unobserve(el);
          aoEntrar(el, Math.min(i, 3)); // stagger: no máximo 4 por lote
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.05 },
    );
  }
  return {
    observar(lista) {
      lista.forEach((el) => {
        if (el.dataset.revelado) return;
        el.dataset.revelado = "1";
        if (semIO) aoEntrar(el, 0);
        else io.observe(el);
      });
    },
    parar() {
      io?.disconnect();
      io = null;
    },
  };
}
