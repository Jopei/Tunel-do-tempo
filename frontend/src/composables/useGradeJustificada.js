import { ref, onMounted, onBeforeUnmount, watch } from "vue";

function aspecto(item) {
  const l = Number(item?.largura);
  const a = Number(item?.altura);
  const r = l > 0 && a > 0 ? l / a : 4 / 3;
  return Math.min(3.5, Math.max(0.4, r));
}

/**
 * Empacota itens em linhas de altura parecida preenchendo exatamente `largura`
 * (a última linha mantém a altura-alvo, sem esticar).
 * Devolve [{ altura, ultima, itens: [{ item, w, h }] }].
 */
export function justificar(itens, largura, alvo, gap) {
  if (!largura || largura < 40 || !itens.length) return [];
  const linhas = [];
  let atual = [];
  let soma = 0;

  const fechar = (altura, ultima) => {
    const util = largura - gap * (atual.length - 1);
    const h = Math.floor(altura);
    let usado = 0;
    const lista = atual.map((c, i) => {
      const eUltimo = i === atual.length - 1;
      const w = ultima ? Math.floor(h * c.asp) : eUltimo ? util - usado : Math.floor((c.asp / soma) * util);
      usado += w;
      return { item: c.item, w, h };
    });
    linhas.push({ altura: h, ultima, itens: lista });
    atual = [];
    soma = 0;
  };

  for (const item of itens) {
    const asp = aspecto(item);
    atual.push({ item, asp });
    soma += asp;
    if (soma * alvo + gap * (atual.length - 1) >= largura) {
      fechar((largura - gap * (atual.length - 1)) / soma, false);
    }
  }
  if (atual.length) fechar(alvo, true);
  return linhas;
}

/** Observa a largura de um elemento (debounce 100 ms, primeira medida imediata). */
export function useLargura(elRef) {
  const largura = ref(0);
  let observer = null;
  let timer = null;
  let primeira = true;

  const medir = () => {
    const el = elRef.value;
    if (el) largura.value = Math.floor(el.clientWidth);
  };

  function ligar() {
    observer?.disconnect();
    const el = elRef.value;
    if (!el) return;
    medir();
    if (typeof ResizeObserver === "undefined") return;
    observer = new ResizeObserver(() => {
      if (primeira) {
        primeira = false;
        medir();
        return;
      }
      clearTimeout(timer);
      timer = setTimeout(medir, 100);
    });
    observer.observe(el);
  }

  onMounted(ligar);
  watch(elRef, ligar);
  onBeforeUnmount(() => {
    observer?.disconnect();
    clearTimeout(timer);
  });

  return largura;
}
