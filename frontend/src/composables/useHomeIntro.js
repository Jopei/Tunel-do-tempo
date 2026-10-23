import { ref, computed, nextTick, onMounted, onBeforeUnmount } from "vue";
import { prefereMenosMovimento } from "@/composables/useTema";
import { homeConfig } from "@/components/home/home-config";
import { criarCoreografia } from "@/components/home/home-coreografia";

/** Passos do storyboard (docs/ui-ux-home-v2.md, seção 7), na ordem. Os tempos ficam em home-coreografia.js. */
export const PASSOS = ["hoje", "descida", "chegada", "projetor", "texto", "ctas", "portas", "fim"].map((id) => ({ id }));

function lerSessao() {
  try {
    return sessionStorage.getItem(homeConfig.introChave) === "1";
  } catch {
    return false;
  }
}
function gravarSessao() {
  try {
    sessionStorage.setItem(homeConfig.introChave, "1");
  } catch {
    /* modo privado / storage bloqueado: segue sem persistir */
  }
}
function conexaoLenta() {
  const c = typeof navigator !== "undefined" ? navigator.connection : null;
  return !!c && (c.saveData || /(^|-)2g$/.test(c.effectiveType || ""));
}
function veioDoLogin() {
  try {
    return String(window.history.state?.back || "").startsWith("/login");
  } catch {
    return false;
  }
}

/**
 * Estado da abertura da home. A coreografia (GSAP) é criada sobre `raizRef`.
 *
 * modo:  'completa' (1ª visita da sessão, ~6 s) | 'curta' (0,8 s) | 'nenhuma' (reduced-motion)
 * passo: id do passo atual, ou null. Classes no raiz: home--intro, home--intro-<modo>, home--p-<passo> (cumulativas).
 * pular() / Esc / rever(): timeline morta e estado final limpo.
 */
export function useHomeIntro(raizRef) {
  const modo = ref("nenhuma");
  const ativa = ref(false);
  const passo = ref(null);
  const alcancados = ref([]);
  let coreo = null;
  let desmontado = false;
  let mq = null;

  function irPara(id) {
    passo.value = id;
    const i = PASSOS.findIndex((p) => p.id === id);
    alcancados.value = PASSOS.slice(0, i + 1).map((p) => p.id);
  }

  function finalizar() {
    coreo?.finalizar();
    coreo = null;
  }

  function focarPlaySeSobrou() {
    nextTick(() => {
      const el = document.activeElement;
      if (!el || el === document.body) {
        raizRef.value?.querySelector(".hp-play")?.focus({ preventScroll: true });
      }
    });
  }

  // Foco no body + palco inert: o Esc do <section> não recebe a tecla, então ouvimos no document.
  function aoEsc(e) {
    if (e.key === "Escape" && ativa.value) pular();
  }
  function ouvirEsc(ligar) {
    document[ligar ? "addEventListener" : "removeEventListener"]("keydown", aoEsc);
  }

  function concluir() {
    ouvirEsc(false);
    if (!ativa.value && passo.value === "fim") return;
    finalizar();
    irPara("fim");
    ativa.value = false;
    focarPlaySeSobrou();
  }

  function montar(focarPular) {
    if (desmontado || !ativa.value) return;
    finalizar();
    const el = raizRef.value;
    if (!el) return;
    coreo = criarCoreografia(el, { modo: modo.value, onPasso: irPara, onFim: concluir });
    if (focarPular) el.querySelector(".home-pular")?.focus({ preventScroll: true });
  }

  function iniciar(forcarCompleta = false) {
    finalizar();
    if (prefereMenosMovimento()) {
      modo.value = "nenhuma";
      ativa.value = false;
      irPara("fim");
      return;
    }
    const completa = forcarCompleta || !(lerSessao() || conexaoLenta() || veioDoLogin());
    modo.value = completa ? "completa" : "curta";
    ativa.value = true;
    ouvirEsc(true);
    if (completa) gravarSessao();
    alcancados.value = [];
    passo.value = null;
    if (!completa) irPara("fim");
    // Espera o overlay (v-if) entrar no DOM antes de montar a timeline; ainda antes do paint.
    nextTick(() => montar(forcarCompleta));
  }

  /** "Pular a viagem", Esc: vai direto ao estado final. */
  function pular() {
    if (ativa.value) concluir();
  }

  /** "Rever a viagem": reexecuta a completa (sem movimento com reduced-motion). */
  function rever() {
    if (prefereMenosMovimento()) return;
    // A viagem começa do topo da página, mesmo se o botão estiver no rodapé.
    window.scrollTo({ top: 0, behavior: "instant" });
    iniciar(true);
  }

  function aoMudarMovimento(e) {
    if (e.matches) {
      modo.value = "nenhuma";
      concluir();
    }
  }

  onMounted(() => {
    iniciar();
    mq = window.matchMedia("(prefers-reduced-motion: reduce)");
    mq.addEventListener("change", aoMudarMovimento);
  });
  onBeforeUnmount(() => {
    desmontado = true;
    ouvirEsc(false);
    finalizar();
    mq?.removeEventListener("change", aoMudarMovimento);
  });

  const classes = computed(() => ({
    "home--intro": ativa.value,
    [`home--intro-${modo.value}`]: ativa.value,
    ...Object.fromEntries(alcancados.value.map((id) => [`home--p-${id}`, true])),
  }));

  return { modo, ativa, passo, classes, pular, rever, irPara, concluir };
}
