import gsap from "gsap";
import { prefereMenosMovimento } from "@/composables/useTema";

// Último voo concluído: posição final da imagem no overlay, para o FLIP no destino.
let ultimoVoo = null;

// Navega e garante que o overlay não fique preso se houver redirect (ex.: /login), falha de chunk ou navegação cancelada.
function navegar(router, url) {
  const alvo = router.resolve(url).path;
  return router
    .push(url)
    .then((falha) => {
      if (falha || router.currentRoute.value.path !== alvo) finalizarTransicao();
    })
    .catch(() => finalizarTransicao());
}

function elementos() {
  return {
    overlay: document.getElementById("transition-overlay"),
    img: document.getElementById("transition-image"),
  };
}

/**
 * Anima a imagem clicada até o centro da tela e então navega (esfera -> página do membro).
 * Só usa transform (x/y/scale) e opacity. Sem animação se reduzir movimento.
 */
export function transicionarPara(router, url, imgEl, { posicao = "centro", duracao = 0.7 } = {}) {
  const { overlay, img } = elementos();

  if (!overlay || !img || !imgEl || prefereMenosMovimento()) {
    navegar(router, url);
    return;
  }

  const rect = imgEl.getBoundingClientRect();
  img.src = imgEl.currentSrc || imgEl.src;

  // Posição fixa no canto; o deslocamento até o centro é feito por transform.
  gsap.set(img, {
    position: "fixed",
    top: 0,
    left: 0,
    width: rect.width,
    height: rect.height,
    x: rect.left,
    y: rect.top,
    scale: 1,
    transformOrigin: "0 0",
  });
  gsap.set(overlay, { opacity: 0, display: "flex" });

  const alvoX = window.innerWidth / 2 - rect.width / 2;
  const alvoY = (posicao === "baixo" ? window.innerHeight * 0.8 : window.innerHeight / 2) - rect.height / 2;
  const escala = posicao === "baixo" ? 2 : 2.4;
  const srcFinal = imgEl.currentSrc || imgEl.src;

  gsap
    .timeline({
      onComplete: () => {
        ultimoVoo = {
          src: srcFinal,
          left: alvoX,
          top: alvoY,
          width: rect.width * escala,
          height: rect.height * escala,
        };
        navegar(router, url);
      },
    })
    .to(overlay, { opacity: 1, duration: 0.25, ease: "power1.out" })
    .to(
      img,
      {
        x: alvoX,
        y: alvoY,
        scale: escala,
        duration: duracao,
        ease: "power2.inOut",
      },
      "<",
    );
}

/** Chamado pela página de destino para dissolver o overlay. */
export function finalizarTransicao() {
  const { overlay } = elementos();
  if (!overlay || overlay.style.display === "none" || !overlay.style.display) return;

  if (prefereMenosMovimento()) {
    overlay.style.display = "none";
    return;
  }
  gsap.to(overlay, {
    opacity: 0,
    duration: 0.5,
    ease: "power2.out",
    onComplete: () => {
      overlay.style.display = "none";
      overlay.style.opacity = 1;
    },
  });
}

/**
 * FLIP do destino: o medalhão nasce exatamente sobre a imagem que voou (overlay)
 * e desliza/encolhe até o seu lugar. Só transform. Consome o voo (uma vez).
 */
export function flipDeTransicao(el) {
  if (!ultimoVoo || !el) return;
  const voo = ultimoVoo;
  ultimoVoo = null;
  if (prefereMenosMovimento() || (el.currentSrc || el.src) !== voo.src) return;

  // Mede sem a animação de entrada do CSS (ela sobrescreve o transform inline).
  el.style.animation = "none";
  const alvo = el.getBoundingClientRect();
  const escala = voo.width / alvo.width;
  const dx = voo.left + voo.width / 2 - (alvo.left + alvo.width / 2);
  const dy = voo.top + voo.height / 2 - (alvo.top + alvo.height / 2);

  gsap.fromTo(
    el,
    { x: dx, y: dy, scale: escala, transformOrigin: "50% 50%" },
    {
      x: 0,
      y: 0,
      scale: 1,
      duration: 0.6,
      ease: "power2.inOut",
      onComplete: () => {
        gsap.set(el, { clearProps: "transform" });
        el.style.animation = "";
      },
    },
  );
}
