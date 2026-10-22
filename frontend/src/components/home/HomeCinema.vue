<template>
  <section
    ref="raiz"
    class="home-cinema tm-immersive"
    :class="classes"
    :data-passo="passo || undefined"
    @keydown.esc="pular"
    @click="aoClicarSaida"
  >
    <HomeIntro :ativa="ativa" :modo="modo" />
    <!-- Anéis da saída "túnel" (CTAs): animados por animarSaida() -->
    <div class="home-saida" aria-hidden="true">
      <span v-for="n in 3" :key="n" class="home-saida-anel" />
    </div>

    <p v-if="ativa" class="sr-only" role="status">Voltando para {{ homeConfig.anoVideo }}.</p>
    <button v-if="ativa && modo !== 'nenhuma'" type="button" class="home-pular" @click="pular">
      Pular a viagem
    </button>

    <!-- inert durante a abertura completa: nada focável invisível (o "Pular" fica fora) -->
    <div class="home-palco" :inert="ativa && modo === 'completa'">
      <div class="home-grade">
        <div class="home-principal">
          <header class="home-cab" data-intro="texto">
            <p class="home-eyebrow">
              {{ homeConfig.canal.rotulo }} · desde {{ homeConfig.anoVideo }}
              <span v-if="auth.logado && primeiroNome"> · Bem-vindo de volta, {{ primeiroNome }}</span>
            </p>
            <h1 class="home-titulo">{{ homeConfig.titulo }}</h1>
          </header>

          <HomePlayer />

          <div class="home-meta" data-intro="texto">
            <img :src="homeConfig.canal.avatar" alt="" width="40" height="40" class="home-avatar" />
            <div class="home-meta-texto">
              <span class="home-canal">{{ homeConfig.canal.nome }}</span>
              <span class="home-views">
                <span data-contador :data-valor="homeConfig.visualizacoes">{{ visualizacoes }}</span> visualizações ·
                <time :datetime="homeConfig.publicadoEm">{{ homeConfig.publicadoEmTexto }}</time>
                · há {{ anos }} anos
              </span>
            </div>
            <button type="button" class="home-compartilhar" @click="compartilhar">
              <v-icon size="20">mdi-share-variant</v-icon>
              <span class="sr-only">Compartilhar</span>
            </button>
          </div>

          <p class="home-descricao" data-intro="texto">{{ homeConfig.descricao }}</p>
          <HomeCtas />
        </div>

        <HomePortas class="home-lateral" />

        <HomeLembrancas class="home-lemb" />
      </div>

      <footer class="home-rodape">
        <router-link to="/aquele-que-sonhou">Em memória de Italo</router-link>
        <router-link to="/atualizacoes">Atualizações</router-link>
        <button v-if="modo !== 'nenhuma' || !reduzido" type="button" class="home-rever" @click="rever">Rever a viagem</button>
      </footer>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useNotify } from "@/composables/useNotify";
import { useHomeIntro } from "@/composables/useHomeIntro";
import { prefereMenosMovimento } from "@/composables/useTema";
import { homeConfig } from "./home-config";
import { animarSaida } from "./home-coreografia";
import HomeIntro from "./HomeIntro.vue";
import HomePlayer from "./HomePlayer.vue";
import HomeCtas from "./HomeCtas.vue";
import HomePortas from "./HomePortas.vue";
import HomeLembrancas from "./HomeLembrancas.vue";

const auth = useAuthStore();
const notify = useNotify();
const router = useRouter();
const raiz = ref(null);
const { modo, ativa, passo, classes, pular, rever } = useHomeIntro(raiz);

// Saída "túnel" pelas portas (curta) e CTAs (longa). Modificadores de clique seguem o comportamento nativo.
let saida = null;
function aoClicarSaida(e) {
  const a = e.target.closest?.("a[data-passagem], a[data-porta]");
  if (!a || saida || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
  const url = a.getAttribute("href");
  if (!url) return;
  e.preventDefault();
  if (prefereMenosMovimento()) {
    router.push(url);
    return;
  }
  const cancelar = () => {
    saida?.cancelar();
    saida = null;
  };
  saida = animarSaida(raiz.value, {
    longa: a.hasAttribute("data-passagem"),
    onDone: () => router.push(url).then((falha) => falha && cancelar()).catch(cancelar),
  });
}
onBeforeUnmount(() => saida?.cancelar());

const reduzido = computed(() => prefereMenosMovimento());
const primeiroNome = computed(() => (auth.usuario?.nome || "").trim().split(/\s+/)[0]);
const visualizacoes = homeConfig.visualizacoes.toLocaleString("pt-BR");
const anos = new Date().getFullYear() - homeConfig.anoVideo;

async function compartilhar() {
  const url = window.location.origin + "/";
  try {
    if (navigator.share) await navigator.share({ title: homeConfig.titulo, url });
    else {
      await navigator.clipboard.writeText(url);
      notify.sucesso("Link copiado.");
    }
  } catch {
    /* cancelado pelo usuário */
  }
}
</script>

<style scoped>
/* Sala escura sempre, mesmo no tema claro */
.home-cinema {
  --tm-paper: #1e1a12;
  --tm-paper-2: #2a2418;
  --tm-ink: #f1e8d2;
  --tm-ink-muted: #b9ae93;
  --tm-gold-text: #e0be5a;
  --tm-border: rgba(241, 232, 210, 0.16);
  --tm-focus: #f0c86a;
  position: relative;
  min-height: calc(100dvh - var(--tm-player-h, 0px));
  padding: var(--tm-navbar-h) var(--tm-space-4) var(--tm-space-7);
  background:
    radial-gradient(ellipse 50% 40% at 35% 40%, rgba(199, 164, 58, 0.1), transparent 70%),
    var(--tm-paper);
  color: var(--tm-ink);
  font-family: var(--tm-font-body);
}
.home-cinema::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: var(--hx-grao);
  opacity: 0.04;
  pointer-events: none;
}
.home-pular {
  position: fixed;
  top: calc(var(--tm-navbar-h) + var(--tm-space-3));
  right: var(--tm-space-4);
  z-index: 6;
  min-height: 44px;
  padding: 0 var(--tm-space-4);
  border: 1px solid var(--tm-ink);
  border-radius: var(--tm-radius-pill);
  background: rgba(30, 26, 18, 0.8);
  color: var(--tm-ink);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
.home-palco {
  position: relative;
  max-width: 1200px;
  margin: 0 auto;
}
.home-grade {
  display: grid;
  grid-template-columns: minmax(0, 880px) 300px;
  grid-template-areas:
    "principal lateral"
    "lemb lateral";
  gap: var(--tm-space-6);
  align-items: start;
  padding-top: var(--tm-space-4);
}
.home-principal {
  grid-area: principal;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-3);
  align-items: flex-start;
}
.home-lateral {
  grid-area: lateral;
  position: sticky;
  top: calc(var(--tm-navbar-h) + 16px);
}
.home-lemb {
  grid-area: lemb;
}
.home-eyebrow {
  margin: 0;
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--tm-ink-muted);
}
.home-titulo {
  margin: var(--tm-space-1) 0 0;
  font-family: var(--tm-font-title);
  font-size: var(--tm-fs-3xl);
  font-weight: 600;
  line-height: 1.15;
}
.home-meta {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  width: 100%;
}
.home-avatar {
  border-radius: 50%;
  border: 2px solid var(--tm-gold);
  object-fit: cover;
}
.home-meta-texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.home-canal {
  font-weight: 600;
}
.home-views {
  font-size: var(--tm-fs-sm);
  font-variant-numeric: tabular-nums;
  color: var(--tm-ink-muted);
}
.home-compartilhar {
  margin-left: auto;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: none;
  color: var(--tm-ink);
  cursor: pointer;
}
.home-descricao {
  margin: 0;
  max-width: 65ch;
  font-size: var(--tm-fs-md);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.home-rodape {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-4);
  margin-top: var(--tm-space-6);
  font-size: var(--tm-fs-sm);
}
.home-rodape a,
.home-rever {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  color: var(--tm-ink-muted);
  background: none;
  border: 0;
  font: inherit;
  cursor: pointer;
  text-decoration: underline;
}

/* Durante a abertura, a GSAP controla transform/opacity: CSS não pode competir com ela (transição dupla). */
.home--intro,
.home--intro :deep(*) {
  transition: none !important;
}

/* Anéis da saída "túnel" */
.home-saida {
  position: fixed;
  inset: 0;
  z-index: 7;
  display: grid;
  place-items: center;
  pointer-events: none;
}
.home-saida-anel {
  grid-area: 1 / 1;
  width: 18vmin;
  height: 18vmin;
  border: 1px solid var(--tm-gold);
  border-radius: 50%;
  opacity: 0;
  will-change: transform, opacity;
}

@media (min-width: 600px) and (max-width: 1099px) {
  .home-grade {
    grid-template-columns: minmax(0, 720px);
    grid-template-areas:
      "principal"
      "lateral"
      "lemb";
    justify-content: center;
  }
  .home-lateral {
    position: static;
  }
}
@media (max-width: 1099px) {
  .home-principal :deep(.hp) {
    width: 100%;
  }
}
@media (max-width: 599px) {
  .home-cinema {
    padding-inline: 0;
  }
  .home-grade {
    grid-template-columns: minmax(0, 1fr);
    grid-template-areas:
      "principal"
      "lateral"
      "lemb";
    gap: var(--tm-space-5);
    padding-top: 0;
  }
  .home-principal {
    width: 100%;
    gap: var(--tm-space-3);
  }
  .home-principal > :not(.hp) {
    margin-inline: var(--tm-space-4);
    width: auto;
  }
  .home-principal > :deep(.hp) {
    order: -1;
  }
  .home-grade > * {
    min-width: 0;
  }
  .home-lateral,
  .home-lemb,
  .home-rodape {
    padding-inline: var(--tm-space-4);
  }
  .home-titulo {
    font-size: var(--tm-fs-2xl);
  }
}
@media (prefers-reduced-motion: reduce) {
  .home--intro [data-intro],
  .home-palco {
    transition: none;
    animation: none;
  }
}
/* Destaque das portas depois do cartão final (convite a continuar) */
.home-cinema:has(.hp--fim) .home-lateral :deep(.porta-thumb) {
  animation: hp-convite 700ms var(--tm-ease) 2s both;
}
.home-cinema:has(.hp--fim) .home-lateral :deep(li:nth-child(2) .porta-thumb) { animation-delay: 2.12s; }
.home-cinema:has(.hp--fim) .home-lateral :deep(li:nth-child(3) .porta-thumb) { animation-delay: 2.24s; }
.home-cinema:has(.hp--fim) .home-lateral :deep(li:nth-child(4) .porta-thumb) { animation-delay: 2.36s; }
@keyframes hp-convite {
  0% { transform: scale(1); }
  40% { transform: scale(1.08); }
  100% { transform: scale(1); }
}
@media (prefers-reduced-motion: reduce) {
  .home-cinema :deep(.porta-thumb) { animation: none !important; }
}
:global(html[data-reduzir="1"]) .home-cinema :deep(.porta-thumb) {
  animation: none !important;
}
</style>
