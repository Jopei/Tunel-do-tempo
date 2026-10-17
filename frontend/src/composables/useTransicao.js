import gsap from "gsap";
import { prefereMenosMovimento } from "@/composables/useTema";

function elementos() {
  return {
    overlay: document.getElementById("transition-overlay"),
    img: document.getElementById("transition-image"),
  };
}

/** Anima a imagem clicada até o centro da tela e então navega. Sem animação se reduzir movimento. */
export function transicionarPara(router, url, imgEl, { posicao = "centro" } = {}) {
  const { overlay, img } = elementos();

  if (!overlay || !img || !imgEl || prefereMenosMovimento()) {
    router.push(url);
    return;
  }

  const rect = imgEl.getBoundingClientRect();
  img.src = imgEl.currentSrc || imgEl.src;

  gsap.set(img, {
    position: "fixed",
    top: rect.top,
    left: rect.left,
    width: rect.width,
    height: rect.height,
    xPercent: 0,
    yPercent: 0,
    scale: 1,
  });
  gsap.set(overlay, { opacity: 1, display: "flex", backgroundColor: "rgba(0,0,0,0)" });

  gsap
    .timeline({ onComplete: () => router.push(url) })
    .to(overlay, { backgroundColor: "#000", duration: 0.4, ease: "power1.out" })
    .to(
      img,
      {
        top: posicao === "baixo" ? "80%" : "50%",
        left: "50%",
        xPercent: -50,
        yPercent: -50,
        scale: posicao === "baixo" ? 2 : 2.4,
        duration: 1.1,
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
    duration: 1.2,
    ease: "power2.out",
    onComplete: () => {
      overlay.style.display = "none";
      overlay.style.opacity = 1;
    },
  });
}
