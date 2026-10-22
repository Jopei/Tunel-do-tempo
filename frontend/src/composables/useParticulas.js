import { prefereMenosMovimento } from "@/composables/useTema";

// Pontos pretos à deriva, em canvas próprio (sem dependência externa).
// Dimensiona pelo tamanho real do contêiner (ResizeObserver), então funciona
// mesmo quando a página monta durante a transição de rota.
const VELOCIDADE = 0.6 * 30; // px/s, equivalente ao speed 0.6 do visual anterior
const RAIO_MAX = 5;

function criarParticula(w, h, cores, velocidade = 1) {
  return {
    cor: cores[Math.floor(Math.random() * cores.length)],
    x: Math.random() * w,
    y: Math.random() * h,
    r: Math.max(0.5, Math.random() * RAIO_MAX),
    a: 0.1 + Math.random() * 0.9,
    vx: (Math.random() * 2 - 1) * VELOCIDADE * velocidade,
    vy: (Math.random() * 2 - 1) * VELOCIDADE * velocidade,
  };
}

/**
 * Inicia as partículas no canvas. Retorna a função de limpeza (rAF, observers, listeners).
 * Com prefereMenosMovimento() não faz nada.
 * opcoes.cores: lista de cores (padrão: só preto). Repita uma cor para pesá-la.
 * opcoes.densidade e opcoes.velocidade (padrão 1): multiplicadores.
 * opcoes.pausarForaDaTela: para o rAF quando o canvas sai da viewport.
 */
export function iniciarParticulas(contenedor, canvas, opcoes = {}) {
  const cores = opcoes.cores?.length ? opcoes.cores : ["#000"];
  const densidade = opcoes.densidade ?? 1;
  const velocidade = opcoes.velocidade ?? 1;
  if (!contenedor || !canvas || prefereMenosMovimento()) return () => {};
  const ctx = canvas.getContext("2d");
  if (!ctx) return () => {};

  let w = 0;
  let h = 0;
  let particulas = [];
  let rafId = 0;
  let ultimo = 0;
  let rodando = false;

  function dimensionar() {
    const rect = contenedor.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    const base = window.matchMedia("(max-width: 768px)").matches ? 30 : 50;
    const alvo = Math.round(base * densidade);
    while (particulas.length < alvo) particulas.push(criarParticula(w, h, cores, velocidade));
    particulas = particulas.slice(0, alvo);
  }

  function quadro(agora) {
    const dt = Math.min((agora - ultimo) / 1000, 0.05);
    ultimo = agora;
    ctx.clearRect(0, 0, w, h);

    for (const p of particulas) {
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      // Sai por uma borda e reentra pela oposta.
      const m = p.r + 4;
      if (p.x < -m) { p.x = w + m; p.y = Math.random() * h; }
      else if (p.x > w + m) { p.x = -m; p.y = Math.random() * h; }
      if (p.y < -m) { p.y = h + m; p.x = Math.random() * w; }
      else if (p.y > h + m) { p.y = -m; p.x = Math.random() * w; }

      ctx.fillStyle = p.cor;
      ctx.globalAlpha = p.a;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.globalAlpha = 1;
    rafId = requestAnimationFrame(quadro);
  }

  function iniciar() {
    if (rodando || document.hidden) return;
    rodando = true;
    ultimo = performance.now();
    rafId = requestAnimationFrame(quadro);
  }

  function parar() {
    rodando = false;
    cancelAnimationFrame(rafId);
  }

  function aoVisibilidade() {
    if (document.hidden) parar();
    else iniciar();
  }

  const observador = new ResizeObserver(() => {
    dimensionar();
    iniciar();
  });
  observador.observe(contenedor);
  document.addEventListener("visibilitychange", aoVisibilidade);

  const vigia = opcoes.pausarForaDaTela && "IntersectionObserver" in window
    ? new IntersectionObserver(([e]) => (e.isIntersecting ? iniciar() : parar()))
    : null;
  vigia?.observe(canvas);

  return () => {
    parar();
    observador.disconnect();
    vigia?.disconnect();
    document.removeEventListener("visibilitychange", aoVisibilidade);
  };
}
