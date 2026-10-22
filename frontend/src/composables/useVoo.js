import gsap from "gsap";
import { prefereMenosMovimento } from "@/composables/useTema";

/**
 * Voo de um fantasma (clone fixo do elemento) entre dois pontos da tela.
 * Só transform (translate + scale) e opacity; o elemento real não é tocado.
 *
 * - el: elemento a clonar (o clone é descartado ao final).
 * - de: retângulo inicial (DOMRect ou objeto com left/top/width/height). Padrão: rect atual de el.
 * - para: elemento vivo, retângulo, ou função que devolve o retângulo de destino a cada quadro
 *   (útil quando o alvo ainda se move, ex.: esfera girando na órbita).
 *
 * Devolve { tween, fantasma }. Quem chama decide o que fazer no fim (onFim).
 * Se prefereMenosMovimento() for true, não faz nada e chama onFim imediatamente.
 */
export function voarFantasma(el, { de, para, duracao = 0.6, ease = "power3.out", onFim, classe = "", fade = false } = {}) {
  const alvoRect = typeof para === "function" ? para : () => (para instanceof Element ? para.getBoundingClientRect() : para);
  const origem = de ?? el.getBoundingClientRect();

  if (prefereMenosMovimento() || !origem?.width || !alvoRect()) {
    onFim?.();
    return { tween: null, fantasma: null };
  }

  const fantasma = el.cloneNode(true);
  fantasma.setAttribute("aria-hidden", "true");
  fantasma.setAttribute("tabindex", "-1");
  fantasma.removeAttribute("aria-label");
  fantasma.removeAttribute("id");
  fantasma.inert = true;
  if (classe) fantasma.classList.add(classe);
  Object.assign(fantasma.style, {
    position: "fixed",
    left: `${origem.left}px`,
    top: `${origem.top}px`,
    width: `${origem.width}px`,
    height: `${origem.height}px`,
    margin: "0",
    transform: "none",
    transition: "none",
    animation: "none",
    pointerEvents: "none",
    zIndex: "2500",
    transformOrigin: "50% 50%",
  });
  document.body.appendChild(fantasma);

  const cx0 = origem.left + origem.width / 2;
  const cy0 = origem.top + origem.height / 2;
  const t = { p: 0 };
  let ultimo = alvoRect();

  const tween = gsap.to(t, {
    p: 1,
    duration: duracao,
    ease,
    onUpdate() {
      ultimo = alvoRect() ?? ultimo;
      const cx = cx0 + (ultimo.left + ultimo.width / 2 - cx0) * t.p;
      const cy = cy0 + (ultimo.top + ultimo.height / 2 - cy0) * t.p;
      const s = 1 + (ultimo.width / origem.width - 1) * t.p;
      fantasma.style.transform = `translate3d(${cx - cx0}px, ${cy - cy0}px, 0) scale(${s})`;
      if (fade) fantasma.style.opacity = String(1 - 0.85 * t.p);
    },
    onComplete() {
      fantasma.remove();
      onFim?.();
    },
  });

  return { tween, fantasma };
}
