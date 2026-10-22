import gsap from "gsap";
import { homeConfig } from "./home-config";

// Entrada padrão: power3.out aproxima --tm-ease (cubic-bezier(.2,.7,.2,1)).
const ENTRADA = "power3.out";
const fmt = (n) => Math.round(n).toLocaleString("pt-BR");
const vivos = (...itens) => itens.flat().filter(Boolean);

/**
 * Coreografia da abertura da home (docs/ui-ux-home-v2.md, seção 7).
 * Só transform e opacity. Uma timeline única; `finalizar()` mata tudo e leva ao estado final limpo.
 * modo: 'completa' (~6 s, túnel 2026 -> 2011) | 'curta' (0,8 s, fade-up do palco).
 */
export function criarCoreografia(raiz, { modo, onPasso, onFim }) {
  return modo === "curta" ? curta(raiz, onFim) : completa(raiz, { onPasso, onFim });
}

function curta(raiz, onFim) {
  const palco = raiz.querySelector(".home-palco");
  const tl = gsap.timeline();
  if (palco) {
    tl.fromTo(palco, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.8, ease: ENTRADA, clearProps: "opacity,transform" }, 0);
  }
  tl.call(onFim, null, 0.8);
  return {
    finalizar() {
      tl.kill();
      if (palco) gsap.set(palco, { clearProps: "opacity,transform" });
    },
  };
}

function completa(raiz, { onPasso, onFim }) {
  const $ = (s) => raiz.querySelector(s);
  const $$ = (s) => Array.from(raiz.querySelectorAll(s));

  const hi = $(".hi");
  const ano = $(".hi-ano");
  if (!hi || !ano) {
    queueMicrotask(onFim);
    return { finalizar() {} };
  }

  const fundo = $(".hi-fundo");
  const calor = $(".hi-calor");
  const traco = $(".hi-traco");
  const aneis = $$(".hi-anel");
  const legHoje = $('[data-leg="hoje"]');
  const legVolta = $('[data-leg="voltando"]');
  const legChega = $('[data-leg="chegada"]');
  const player = $('[data-intro="player"]');
  const textos = $$('[data-intro="texto"]');
  const ctas = $('[data-intro="ctas"]');
  const portas = $('[data-intro="portas"]');
  const portasItens = $$('[data-intro="portas"] .porta');
  const lemb = $('[data-intro="lembrancas"]');
  const lembItens = $$('[data-intro="lembrancas"] .lemb-item');
  const rodape = $(".home-rodape");
  const carimbo = $(".hp-carimbo");
  const contador = $("[data-contador]");
  const valorFinal = Number(contador?.dataset.valor) || 0;

  const anoHoje = new Date().getFullYear();
  const anoVideo = homeConfig.anoVideo;
  const contagem = { v: anoHoje };
  const visualizacoes = { v: 0 };
  let ultimoAno = anoHoje;
  const ringTls = [];

  // Estado inicial (antes de qualquer frame): o overlay é opaco, então nada aparece por trás.
  ano.textContent = String(anoHoje);
  if (contador) contador.textContent = fmt(0);
  gsap.set([legVolta, legChega], { opacity: 0 });

  const tl = gsap.timeline();

  // 0,00 - 0,60  Hoje
  tl.call(onPasso, ["hoje"], 0)
    .fromTo(ano, { opacity: 0 }, { opacity: 1, duration: 0.6, ease: ENTRADA }, 0)
    .fromTo(legHoje, { opacity: 0, y: 24 }, { opacity: 1, y: 0, duration: 0.6, ease: ENTRADA }, 0);

  // 0,60 - 3,40  Descida: contagem de 2026 a 2011, anéis em profundidade, calor sobe
  tl.call(onPasso, ["descida"], 0.6)
    .to(legHoje, { opacity: 0, duration: 0.15, ease: "power1.out" }, 0.6)
    .to(legVolta, { opacity: 1, duration: 0.15, ease: "power1.out" }, 0.75)
    .fromTo(calor, { opacity: 0 }, { opacity: 0.35, duration: 2.8, ease: "power1.inOut" }, 0.6)
    .to(
      contagem,
      {
        v: anoVideo,
        duration: 2.8,
        ease: "power2.inOut",
        onUpdate() {
          const n = Math.round(contagem.v);
          if (n !== ultimoAno) {
            ultimoAno = n;
            ano.textContent = String(n);
          }
        },
      },
      0.6,
    );

  aneis.forEach((r, i) => {
    const ciclo = gsap
      .timeline({ repeat: 1 })
      .fromTo(r, { scale: 0.2, opacity: 0 }, { scale: 3.2, opacity: 0.5, duration: 1.1, ease: "power1.in" })
      .to(r, { opacity: 0, duration: 0.3, ease: "power1.out" });
    ringTls.push(ciclo);
    tl.add(ciclo, 0.6 + i * 0.28);
  });

  // 3,40 - 3,90  Chegada: assentamento em 2011, anéis se dissolvem
  tl.call(onPasso, ["chegada"], 3.4)
    .call(() => ringTls.forEach((t) => t.kill()), null, 3.4)
    .to(aneis, { opacity: 0, duration: 0.3, ease: "power1.out" }, 3.4)
    .to(legVolta, { opacity: 0, duration: 0.15, ease: "power1.out" }, 3.4)
    .fromTo(legChega, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: 0.3, ease: ENTRADA }, 3.5)
    .fromTo(ano, { scale: 1.06, y: -4 }, { scale: 1, y: 0, duration: 0.5, ease: "back.out(1.2)" }, 3.4);

  // 3,90 - 4,60  Projetor acende: traço vira o player, ano sobe e some, fundo se abre
  tl.call(onPasso, ["projetor"], 3.9)
    .fromTo(traco, { scaleX: 0, opacity: 1 }, { scaleX: 1, duration: 0.25, ease: "power2.out" }, 3.9)
    .to(traco, { opacity: 0, duration: 0.2, ease: "power1.out" }, 4.15)
    .fromTo(player, { scaleY: 0.02, opacity: 0 }, { scaleY: 1, opacity: 1, duration: 0.45, ease: "expo.out" }, 4.05)
    .to(ano, { scale: 0.35, y: -Math.round(window.innerHeight * 0.36), duration: 0.7, ease: "power2.inOut" }, 3.9)
    .to(ano, { opacity: 0, duration: 0.3, ease: "power1.out" }, 4.3)
    .to(fundo, { opacity: 0, duration: 0.7, ease: "power2.out" }, 3.9)
    .to(calor, { opacity: 0.6, duration: 0.15, ease: "power1.out" }, 4.05)
    .to(calor, { opacity: 0, duration: 0.55, ease: "power1.in" }, 4.2)
    .set(hi, { visibility: "hidden" }, 4.6);

  // 4,60 - 5,30  Texto em cascata + contador de visualizações
  tl.call(onPasso, ["texto"], 4.6)
    .fromTo(textos, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.4, ease: ENTRADA, stagger: 0.09 }, 4.6)
    .to(
      visualizacoes,
      {
        v: valorFinal,
        duration: 0.8,
        ease: "power2.out",
        onUpdate() {
          if (contador) contador.textContent = fmt(visualizacoes.v);
        },
      },
      4.6,
    );

  // 5,00 - 5,60  CTAs e carimbo de data
  tl.call(onPasso, ["ctas"], 5.0)
    .fromTo(ctas, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.4, ease: ENTRADA }, 5.0)
    .fromTo(carimbo, { opacity: 0 }, { opacity: 1, duration: 0.25, ease: ENTRADA, clearProps: "opacity" }, 5.0);

  // 5,30 - 6,00  Portas e lembranças em stagger
  tl.call(onPasso, ["portas"], 5.3)
    .fromTo(portas, { opacity: 0 }, { opacity: 1, duration: 0.3, ease: ENTRADA }, 5.3)
    .fromTo(portasItens, { opacity: 0, x: 16 }, { opacity: 1, x: 0, duration: 0.4, ease: ENTRADA, stagger: 0.1 }, 5.3)
    .fromTo(lemb, { opacity: 0 }, { opacity: 1, duration: 0.3, ease: ENTRADA }, 5.3)
    .fromTo(lembItens, { opacity: 0, y: 12 }, { opacity: 1, y: 0, duration: 0.4, ease: ENTRADA, stagger: 0.08 }, 5.3)
    .fromTo(rodape, { opacity: 0 }, { opacity: 1, duration: 0.4, ease: ENTRADA }, 5.5);

  // 6,00  Fim: limpa a camada e devolve o estado final
  tl.call(onFim, null, 6.0);

  const todos = vivos(hi, ano, fundo, calor, traco, aneis, legHoje, legVolta, legChega, player, textos, ctas, portas, portasItens, lemb, lembItens, rodape, carimbo);

  return {
    finalizar() {
      tl.kill();
      ringTls.forEach((t) => t.kill());
      if (todos.length) gsap.set(todos, { clearProps: "opacity,transform,visibility" });
      if (contador) contador.textContent = fmt(valorFinal);
    },
  };
}

/**
 * Transição de saída pelas portas (curta) e pelos CTAs (longa, "túnel").
 * Chama onDone quando deve navegar. `cancelar()` restaura a página (ex.: navegação falhou).
 */
export function animarSaida(raiz, { longa, onDone }) {
  const palco = raiz.querySelector(".home-palco");
  const aneis = Array.from(raiz.querySelectorAll(".home-saida-anel"));
  const tl = gsap.timeline();

  if (longa) {
    tl.to(palco, { scale: 1.12, opacity: 0, duration: 0.45, ease: "power2.in" }, 0)
      .fromTo(aneis, { scale: 0.4, opacity: 0 }, { scale: 4, opacity: 0.4, duration: 0.3, ease: "power1.out", stagger: 0.06 }, 0)
      .to(aneis, { scale: 4, opacity: 0, duration: 0.3, ease: "power1.in", stagger: 0.06 }, 0.3)
      .call(onDone, null, 0.4);
  } else {
    tl.to(palco, { opacity: 0, duration: 0.25, ease: "power1.in" }, 0).call(onDone, null, 0.25);
  }

  return {
    cancelar() {
      tl.kill();
      if (palco) gsap.set(palco, { clearProps: "opacity,transform" });
      if (aneis.length) gsap.set(aneis, { clearProps: "opacity,transform" });
    },
  };
}
