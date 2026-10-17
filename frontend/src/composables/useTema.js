import { ref, computed } from "vue";

const CHAVE_TEMA = "tunel-tema";
const CHAVE_REDUZIR = "tunel-reduzir-animacoes";

// 'claro' | 'escuro' | 'auto'
const preferencia = ref(localStorage.getItem(CHAVE_TEMA) || "auto");
const reduzirManual = ref(localStorage.getItem(CHAVE_REDUZIR) === "1");

let vuetifyInstance = null;
let mql = null;

function temaEfetivo() {
  if (preferencia.value === "claro") return "claro";
  if (preferencia.value === "escuro") return "escuro";
  return mql?.matches ? "escuro" : "claro";
}

function aplicar() {
  const efetivo = temaEfetivo();
  document.documentElement.dataset.tema = efetivo;
  if (vuetifyInstance) {
    vuetifyInstance.theme.global.name.value =
      efetivo === "escuro" ? "tunelNoite" : "tunelClaro";
  }
  const meta = document.querySelector('meta[name="theme-color"]:not([media])');
  if (meta) meta.setAttribute("content", efetivo === "escuro" ? "#14110B" : "#FBF6E6");
}

function aplicarReduzir() {
  if (reduzirManual.value) document.documentElement.dataset.reduzir = "1";
  else delete document.documentElement.dataset.reduzir;
}

/** Chamar uma vez em main.js, antes de montar o app (evita flash de tema). */
export function iniciarTema(vuetify) {
  vuetifyInstance = vuetify;
  mql = window.matchMedia("(prefers-color-scheme: dark)");
  mql.addEventListener("change", () => {
    if (preferencia.value === "auto") aplicar();
  });
  aplicar();
  aplicarReduzir();
}

export function useTema() {
  const efetivo = computed(() => {
    if (preferencia.value === "auto") return mql?.matches ? "escuro" : "claro";
    return preferencia.value;
  });

  function definir(valor) {
    preferencia.value = valor;
    localStorage.setItem(CHAVE_TEMA, valor);
    aplicar();
  }

  function alternar() {
    definir(efetivo.value === "escuro" ? "claro" : "escuro");
  }

  function definirReduzir(valor) {
    reduzirManual.value = !!valor;
    localStorage.setItem(CHAVE_REDUZIR, valor ? "1" : "0");
    aplicarReduzir();
  }

  return { preferencia, efetivo, definir, alternar, reduzirManual, definirReduzir };
}

/** true se o usuário pediu menos movimento (sistema ou preferência do app). */
export function prefereMenosMovimento() {
  return (
    reduzirManual.value ||
    window.matchMedia("(prefers-reduced-motion: reduce)").matches
  );
}
