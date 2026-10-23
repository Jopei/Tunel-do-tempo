import { ref, computed, onBeforeUnmount, nextTick } from "vue";

/**
 * Lista paginada com scroll infinito.
 * fetchPagina(pagina) deve devolver { data: [], meta: { last_page, total } }.
 * Uso: const lista = useListaPaginada(fn); <div :ref="lista.sentinela" />
 */
export function useListaPaginada(fetchPagina) {
  const itens = ref([]);
  const pagina = ref(0);
  const ultimaPagina = ref(1);
  const total = ref(null);
  const carregando = ref(false);
  const erro = ref(false);
  const anuncio = ref("");
  const sentinelaEl = ref(null);
  let observer = null;
  let geracao = 0;
  let emVoo = null;

  const temMais = computed(() => pagina.value < ultimaPagina.value);
  const vazio = computed(() => !carregando.value && !erro.value && pagina.value > 0 && itens.value.length === 0);

  function carregarProxima() {
    if (carregando.value && emVoo) return emVoo;
    const p = buscarProxima();
    emVoo = p;
    p.finally(() => {
      if (emVoo === p) emVoo = null;
      // Se a sentinela ainda está visível (1ª página não preenche a tela), re-observar dispara nova checagem.
      if (temMais.value && !erro.value) nextTick(observar);
    });
    return p;
  }

  /** Carrega páginas em sequência até haver pelo menos n itens (ou acabar / dar erro). */
  async function carregarAte(n) {
    while (itens.value.length < n && (pagina.value === 0 || temMais.value) && !erro.value) {
      const antes = pagina.value;
      await carregarProxima();
      if (pagina.value === antes) break;
    }
  }

  async function buscarProxima() {
    if (carregando.value || (pagina.value > 0 && !temMais.value)) return;
    const minha = geracao;
    carregando.value = true;
    erro.value = false;
    try {
      const proxima = pagina.value + 1;
      const r = await fetchPagina(proxima);
      if (minha !== geracao) return; // lista foi reiniciada no meio do caminho
      itens.value.push(...(r.data ?? []));
      pagina.value = proxima;
      ultimaPagina.value = r.meta?.last_page ?? proxima;
      total.value = r.meta?.total ?? itens.value.length;
      anuncio.value = `${itens.value.length} carregad${itens.value.length === 1 ? "o" : "os"}.`;
    } catch {
      if (minha === geracao) erro.value = true;
    } finally {
      if (minha === geracao) carregando.value = false;
    }
  }

  async function recarregar() {
    geracao++;
    itens.value = [];
    pagina.value = 0;
    ultimaPagina.value = 1;
    total.value = null;
    carregando.value = false;
    erro.value = false;
    await carregarProxima();
  }

  function observar() {
    observer?.disconnect();
    if (!("IntersectionObserver" in window) || !sentinelaEl.value) return;
    observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && !erro.value) carregarProxima();
      },
      { rootMargin: "400px" },
    );
    observer.observe(sentinelaEl.value);
  }

  // ref de função: <div :ref="lista.sentinela">
  function sentinela(el) {
    sentinelaEl.value = el;
    if (el) nextTick(observar);
  }

  onBeforeUnmount(() => {
    geracao++;
    observer?.disconnect();
  });

  return { itens, total, carregando, erro, temMais, vazio, anuncio, carregarProxima, carregarAte, recarregar, sentinela };
}
