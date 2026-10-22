<!--
  Página de João Pedro — tema "A Rua é Nóis" (cartaz construtivista: vermelho / preto / creme).

  Ganchos para o motion (todos classes estáveis; o fallback aqui é só fade/CSS simples):
    .jp-abertura / .jp-abertura-nome   abertura do nome (overlay fixo, aria-hidden; o h1 real é o da casca)
    .jp-raios / .jp-raios--creme       feixes de sol (girar devagar)
    .jp-forma (.jp-diag .jp-bloco .jp-circulo .jp-cunha .jp-estrela)  formas do cartaz; --i = ordem
    .jp-particulas                     canvas das partículas (useParticulas)
    .jp-multidao > .jp-camada[data-camada=fundo|meio|frente] > .jp-pessoa[--i]  subida em camadas
      .jp-pessoa--rapper | --campones | --operario
    .jp-ceu > .jp-carcara (.sil-asa-esq/.sil-asa-dir/.sil-cauda)   voo do carcará (cruzar a cena + bater asas)
    .jp-indigena > .sil-arco / .sil-corda / .sil-flecha / .sil-cocar   arco tensionado, disparo e rastro
    .jp-bloco-entra                    blocos de conteúdo (entrada ao rolar); .jp-musica-botao
  Estado de reduced-motion: classe .jp--reduzido na raiz (e html[data-reduzir]).
-->
<template>
  <MembroPagina
    ref="raizRef"
    v-bind="$attrs"
    class="jp"
    :class="{ 'jp--reduzido': reduzido, 'jp--pausado': pausado, 'jp--revelar': revelar }"
    :membro="membro"
    :tema="temaMembro"
    :simbolo="simbolo"
    :anterior="anterior"
    :proximo="proximo"
  >
    <template #topo-extra>
      <button
        type="button"
        class="jp-som"
        :aria-pressed="somLigado ? 'true' : 'false'"
        :aria-label="somLigado ? 'Desligar efeitos sonoros' : 'Ligar efeitos sonoros'"
        :title="somLigado ? 'Desligar efeitos sonoros' : 'Ligar efeitos sonoros'"
        @click="alternarSomPagina"
      >
        <v-icon size="18" aria-hidden="true">{{ somLigado ? "mdi-volume-high" : "mdi-volume-off" }}</v-icon>
      </button>
      <div
        v-if="abertura"
        class="jp-abertura"
        aria-hidden="true"
        @animationstart="aoIniciarAbertura"
      >
        <span class="jp-abertura-nome"><span v-for="(l, i) in letrasAbertura" :key="i" class="jp-letra" :style="{ '--i': i }">{{ l }}</span></span>
      </div>
    </template>

    <template #hero-fundo>
      <div ref="cenaRef" class="jp-cena">
        <div class="jp-raios" />
        <div class="jp-raios jp-raios--creme" />
        <svg class="jp-formas" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" focusable="false">
          <!-- cunha vermelha cortando o círculo creme (homenagem ao "Red Wedge" de Lissitzky) -->
          <g class="jp-forma jp-circulo" style="--i: 0">
            <circle cx="300" cy="260" r="190" fill="#efe2c0" />
            <circle cx="300" cy="260" r="190" fill="none" stroke="#0d0d0d" stroke-width="14" />
            <polygon class="jp-cunha" points="120,330 470,190 330,120" fill="#0d0d0d" />
            <polygon points="170,300 400,205 330,150" fill="#c8101a" />
          </g>
          <polygon class="jp-forma jp-diag" style="--i: 1" points="-40,900 420,900 1700,-40 1240,-40" fill="#0d0d0d" opacity=".9" />
          <polygon class="jp-forma jp-diag" style="--i: 2" points="700,900 790,900 1700,160 1700,60" fill="#efe2c0" opacity=".9" />
          <g transform="rotate(-14 1180 560)">
            <rect class="jp-forma jp-bloco" style="--i: 3" x="1180" y="560" width="520" height="90" fill="#0d0d0d" />
          </g>
          <g transform="rotate(-14 -20 470)">
            <rect class="jp-forma jp-bloco" style="--i: 4" x="-20" y="470" width="640" height="46" fill="#0d0d0d" />
          </g>
          <g class="jp-forma jp-circulo" style="--i: 5">
            <circle cx="1330" cy="250" r="120" fill="#0d0d0d" />
            <circle cx="1330" cy="250" r="86" fill="#c8101a" />
            <circle cx="1330" cy="250" r="44" fill="#efe2c0" />
          </g>
          <g class="jp-forma jp-estrela" style="--i: 6" fill="#efe2c0">
            <polygon points="800,70 818,118 870,120 829,152 843,202 800,172 757,202 771,152 730,120 782,118" />
          </g>
          <g class="jp-forma jp-estrela" style="--i: 7" fill="#0d0d0d">
            <polygon points="1010,380 1020,408 1050,410 1026,428 1034,458 1010,440 986,458 994,428 970,410 1000,408" />
            <polygon points="560,330 568,352 592,354 573,368 580,392 560,378 540,392 547,368 528,354 552,352" />
          </g>
          <g class="jp-forma" style="--i: 8" fill="none" stroke="#0d0d0d" stroke-width="5">
            <line x1="0" y1="640" x2="1600" y2="400" />
            <line x1="0" y1="670" x2="1600" y2="430" />
            <line x1="860" y1="0" x2="1160" y2="900" />
          </g>
          <g class="jp-forma" style="--i: 9" fill="#efe2c0">
            <rect x="1380" y="470" width="30" height="30" />
            <rect x="1425" y="470" width="30" height="30" />
            <rect x="1470" y="470" width="30" height="30" />
          </g>
        </svg>
        <div class="jp-meiotom" />
        <canvas ref="canvasRef" class="jp-particulas" />

        <!-- céu: carcará em voo -->
        <div class="jp-ceu">
          <div class="jp-carcara"><div class="jp-carcara-corpo"><SilhuetaCarcara /></div></div>
        </div>

        <!-- fundo à esquerda: indígena atirando a flecha para o céu -->
        <div class="jp-indigena"><SilhuetaIndigena /></div>

        <!-- multidão subindo, três camadas de profundidade; todos olham para a esquerda -->
        <div class="jp-multidao">
          <div v-for="c in camadas" :key="c.nome" class="jp-camada" :data-camada="c.nome" :style="{ '--onda': c.onda + 's' }">
            <div
              v-for="(p, i) in c.pessoas"
              :key="c.nome + i"
              class="jp-pessoa"
              :class="[`jp-pessoa--${p.tipo}`, { 'jp-pessoa--vivo': p.vivo }]"
              :style="{ '--i': i + c.base, left: p.x + '%', '--esc': p.esc, '--atraso': p.atraso + 's' }"
            >
              <SilhuetaPovo :tipo="p.tipo" :pose="p.pose" :mulher="p.mulher" />
            </div>
          </div>
        </div>
        <div class="jp-textura" />
      </div>
    </template>

    <!-- foto em moldura de cartaz (duotone preto/vermelho) -->
    <template #simbolo="{ membro: m }">
      <div class="jp-moldura jp-bloco-entra">
        <img v-if="m.foto_url" class="jp-foto" :src="m.foto_url" alt="" />
        <span v-else class="jp-iniciais">{{ iniciaisNome(m.nome) }}</span>
        <svg class="jp-moldura-estrela" viewBox="0 0 100 100" aria-hidden="true">
          <polygon points="50,6 61,38 95,38 68,58 78,92 50,72 22,92 32,58 5,38 39,38" fill="#c8101a" stroke="#0d0d0d" stroke-width="5" />
        </svg>
      </div>
    </template>

    <template #hero-acoes>
      <div class="jp-trilha-controles">
        <button
          type="button"
          class="jp-musica-botao jp-musica-botao--mini"
          :disabled="estadoMusica === 'indisponivel'"
          aria-label="Faixa anterior"
          @click="faixaAnterior"
        >
          <v-icon size="22" aria-hidden="true">mdi-skip-previous</v-icon>
        </button>
        <button
          type="button"
          class="jp-musica-botao"
          :class="`jp-musica-botao--${estadoMusica}`"
          :disabled="estadoMusica === 'indisponivel'"
          :aria-pressed="estadoMusica === 'tocando'"
          @click="alternarMusica"
        >
          <v-icon size="22" aria-hidden="true">{{ estadoMusica === "tocando" ? "mdi-pause" : "mdi-play" }}</v-icon>
          <span v-if="estadoMusica === 'indisponivel'">Música indisponível no momento</span>
          <span v-else>{{ estadoMusica === "tocando" ? "Pausar" : "Tocar" }} {{ faixaAtual.titulo }} — {{ faixaAtual.artista }}</span>
        </button>
        <button
          type="button"
          class="jp-musica-botao jp-musica-botao--mini"
          :disabled="estadoMusica === 'indisponivel'"
          aria-label="Próxima faixa"
          @click="proxima(false)"
        >
          <v-icon size="22" aria-hidden="true">mdi-skip-next</v-icon>
        </button>
      </div>
    </template>

    <template #depois-hero>
      <div class="jp-faixa" aria-hidden="true">
        <div class="jp-faixa-trilho">
          <span v-for="n in 12" :key="n">A RUA É NÓIS <i>★</i></span>
        </div>
      </div>
    </template>

    <template #midia>
      <h2 id="mp-midia-t" class="mp-titulo">Trilha sonora</h2>
      <div class="jp-cartaz-musica jp-bloco-entra">
        <p class="jp-cartaz-titulo">{{ faixaAtual.titulo }}</p>
        <p class="jp-cartaz-artista">{{ faixaAtual.artista }}</p>
        <p class="jp-sr-only" aria-live="polite">
          {{ estadoMusica === "tocando" ? `Tocando: ${faixaAtual.titulo} — ${faixaAtual.artista}` : "" }}
        </p>
        <ol class="jp-tracklist">
          <li v-for="(f, i) in FAIXAS" :key="f.src">
            <button
              type="button"
              class="jp-faixa-item"
              :class="{
                'jp-faixa-item--ativa': i === indiceFaixa,
                'jp-faixa-item--indisponivel': indisponiveis[i],
              }"
              :disabled="indisponiveis[i]"
              :aria-pressed="i === indiceFaixa && tocando"
              @click="selecionarFaixa(i)"
            >
              <span class="jp-faixa-num" aria-hidden="true">{{ i + 1 }}</span>
              <span class="jp-faixa-nome">
                {{ f.titulo }} — {{ f.artista }}
                <small v-if="i === 0" class="jp-faixa-tag">Principal</small>
                <small v-if="indisponiveis[i]" class="jp-faixa-tag">Indisponível</small>
              </span>
              <v-icon size="22" aria-hidden="true">{{
                i === indiceFaixa && tocando ? "mdi-pause" : "mdi-play"
              }}</v-icon>
            </button>
          </li>
        </ol>
        <div class="jp-trilha-controles">
          <button
            type="button"
            class="jp-musica-botao jp-musica-botao--mini"
            :disabled="estadoMusica === 'indisponivel'"
            aria-label="Faixa anterior"
            @click="faixaAnterior"
          >
            <v-icon size="22" aria-hidden="true">mdi-skip-previous</v-icon>
          </button>
          <button
            type="button"
            class="jp-musica-botao"
            :class="`jp-musica-botao--${estadoMusica}`"
            :disabled="estadoMusica === 'indisponivel'"
            :aria-pressed="estadoMusica === 'tocando'"
            @click="alternarMusica"
          >
            <v-icon size="22" aria-hidden="true">{{ estadoMusica === "tocando" ? "mdi-pause" : "mdi-play" }}</v-icon>
            <span v-if="estadoMusica === 'indisponivel'">Música indisponível no momento</span>
            <span v-else>{{ estadoMusica === "tocando" ? "Pausar" : "Tocar" }} {{ faixaAtual.titulo }} — {{ faixaAtual.artista }}</span>
          </button>
          <button
            type="button"
            class="jp-musica-botao jp-musica-botao--mini"
            :disabled="estadoMusica === 'indisponivel'"
            aria-label="Próxima faixa"
            @click="proxima(false)"
          >
            <v-icon size="22" aria-hidden="true">mdi-skip-next</v-icon>
          </button>
        </div>
      </div>
    </template>
  </MembroPagina>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import MembroPagina from "../MembroPagina.vue";
import SilhuetaPovo from "@/components/membros/silhuetas/SilhuetaPovo.vue";
import SilhuetaCarcara from "@/components/membros/silhuetas/SilhuetaCarcara.vue";
import SilhuetaIndigena from "@/components/membros/silhuetas/SilhuetaIndigena.vue";
import { iniciarParticulas } from "@/composables/useParticulas";
import { prefereMenosMovimento } from "@/composables/useTema";
import { usePlayerStore } from "@/stores/player";
import { iniciais } from "@/composables/useFormat";
import { useEntradaSonora, useSomSite } from "@/composables/useEntradaSonora";

defineOptions({ inheritAttrs: false });
const props = defineProps({
  membro: { type: Object, default: null },
  tema: { type: Object, default: () => ({}) },
  simbolo: { type: String, default: "" },
  anterior: { type: String, default: null },
  proximo: { type: String, default: null },
});

// Large text em cima do vermelho; texto corrido sempre em preto sobre creme (AA).
const temaMembro = {
  cor: "#c8101a",
  texto: "#8e0a12",
  hero: "#c8101a",
  heroTexto: "#f6ecd0",
  fundo: "#efe2c0",
  superficie: "#f8f0da",
  textoCorpo: "#0d0d0d",
  fonteTitulo: '"Bebas Neue", "Oswald", Impact, Haettenschweiler, "Arial Narrow Bold", "Arial Narrow", sans-serif',
  raio: "0px",
};

const iniciaisNome = (n) => iniciais(n) || "?";

// ---- multidão: 3 camadas densas (fundo 16, meio 11, frente 8), todas olham para a esquerda ----
// Determinístico (sem Math.random): tipo, pose, gênero e escala variam por índice.
const TIPOS = ["operario", "rapper", "campones"];
const ESCALAS = [1, 0.92, 1.06, 0.97, 1.1, 0.9];
// onda: atraso (s) da entrada da camada; atraso: desencontro determinístico por pessoa
function montarCamada(nome, base, n, de, ate, semente, onda) {
  const passo = n > 1 ? (ate - de) / (n - 1) : 0;
  return {
    nome,
    base,
    onda,
    pessoas: Array.from({ length: n }, (_, i) => ({
      tipo: TIPOS[(i * 2 + semente) % 3],
      pose: (i + semente) % 3,
      mulher: (i * 3 + semente) % 5 < 2,
      x: +(de + passo * i + (((i * 7 + semente) % 3) - 1) * 0.8).toFixed(1),
      esc: ESCALAS[(i * 5 + semente) % ESCALAS.length],
      atraso: +((((i * 37 + semente * 11) % 9) * 0.024) + (((i * 13 + semente) % 4) * 0.03)).toFixed(3),
      // poucas figuras "vivas" (acenam/pulam de vez em quando); fundo nunca anima
      vivo: nome !== "fundo" && (i * 7 + semente) % 6 === 2,
    })),
  };
}
// o lado direito de meio/frente fica livre para o indígena (canto direito)
const camadas = [
  montarCamada("fundo", 0, 24, 2, 98, 0, 1.3),
  montarCamada("meio", 24, 16, 2, 72, 1, 1.75),
  montarCamada("frente", 40, 11, 3, 70, 2, 2.2),
];

// ---- abertura do nome, reduced-motion e estados de animação ----
const raizRef = ref(null);
const reduzido = ref(prefereMenosMovimento());
const abertura = ref(false);
const revelar = ref(false); // blocos de conteúdo começam ocultos até entrar na tela
const pausado = ref(false); // fora da viewport ou aba oculta: loops CSS param
let pausadoVista = false;
let pausadoAba = false;
let tAbertura = 0;
const letrasAbertura = computed(() => Array.from(props.membro?.nome || "João Pedro"));

// ---- efeito sonoro de entrada: toca no início real da animação de saída da abertura ----
const { somLigado, alternarSom } = useSomSite();
const somEntrada = useEntradaSonora({
  ocupado: () => estadoMusica.value === "tocando" || usePlayerStore().tocando,
});
function alternarSomPagina() {
  alternarSom();
  if (!somLigado.value) somEntrada.parar();
}
function aoIniciarAbertura(e) {
  // <style scoped> renomeia os keyframes (jp-abertura-sai-<hash>), então compara pelo prefixo.
  if (e.target === e.currentTarget && e.animationName.startsWith("jp-abertura-sai")) somEntrada.tocar();
}

// ---- partículas ----
const cenaRef = ref(null);
const canvasRef = ref(null);
let pararParticulas = () => {};
let pararCena = () => {};

function atualizarPausa() {
  pausado.value = pausadoVista || pausadoAba;
  if (!pausado.value) ligarBatida();
}

// ---- parallax por camada (desktop com ponteiro; transform via variável) ----
const FATOR_PARALLAX = { fundo: 0.12, meio: 0.22, frente: 0.34 };
const LIMITE_PARALLAX = 90; // px
let rafRolagem = 0;

function atualizarParallax() {
  rafRolagem = 0;
  const cena = cenaRef.value;
  if (!cena) return;
  const r = cena.getBoundingClientRect();
  const deslocamento = Math.max(0, -r.top); // quanto já rolamos
  cena.querySelectorAll(".jp-camada").forEach((el) => {
    const f = FATOR_PARALLAX[el.dataset.camada] ?? 0;
    el.style.setProperty("--py", Math.min(LIMITE_PARALLAX, deslocamento * f).toFixed(1) + "px");
  });
}

function aoRolar() {
  if (!rafRolagem) rafRolagem = requestAnimationFrame(atualizarParallax);
}

// ---- batida da música (AnalyserNode opcional; falha => sem reação ao ritmo) ----
let contextoAudio = null;
let analisador = null;
let dadosBatida = null;
let rafBatida = 0;
let batida = 0;

function definirBatida(v) {
  raizRef.value?.$el?.style?.setProperty("--jp-beat", v.toFixed(3));
}

function iniciarAnalise() {
  if (analisador || !audio || prefereMenosMovimento()) return;
  try {
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return;
    contextoAudio = new AC();
    const fonte = contextoAudio.createMediaElementSource(audio);
    analisador = contextoAudio.createAnalyser();
    analisador.fftSize = 256;
    dadosBatida = new Uint8Array(analisador.frequencyBinCount);
    fonte.connect(analisador);
    analisador.connect(contextoAudio.destination);
  } catch {
    analisador = null;
    contextoAudio?.close?.().catch?.(() => {});
    contextoAudio = null;
  }
}

function loopBatida() {
  rafBatida = 0;
  if (!analisador || estadoMusica.value !== "tocando" || pausado.value) {
    definirBatida(0);
    batida = 0;
    return;
  }
  if (contextoAudio?.state === "suspended") contextoAudio.resume?.();
  analisador.getByteFrequencyData(dadosBatida);
  let soma = 0;
  for (let i = 1; i < 8; i++) soma += dadosBatida[i];
  const alvo = Math.min(1, ((soma / 7) / 255) * 1.7);
  batida = batida * 0.6 + alvo * 0.4;
  definirBatida(batida);
  rafBatida = requestAnimationFrame(loopBatida);
}

function ligarBatida() {
  if (analisador && !rafBatida && estadoMusica.value === "tocando" && !pausado.value) {
    rafBatida = requestAnimationFrame(loopBatida);
  }
}

// ---- entrada dos blocos ao rolar e pausa fora da viewport ----
function configurarObservadores(cena) {
  const raiz = raizRef.value?.$el;
  if (!("IntersectionObserver" in window)) return () => {};
  const blocos = raiz?.querySelectorAll(".jp-bloco-entra") ?? [];
  const obsBlocos = new IntersectionObserver(
    (entradas) =>
      entradas.forEach((e) => {
        if (!e.isIntersecting) return;
        e.target.classList.add("jp-visivel");
        obsBlocos.unobserve(e.target);
      }),
    { threshold: 0.15 }
  );
  blocos.forEach((el) => obsBlocos.observe(el));

  const obsCena = new IntersectionObserver(([e]) => {
    pausadoVista = !e.isIntersecting;
    atualizarPausa();
  });
  obsCena.observe(cena);

  return () => {
    obsBlocos.disconnect();
    obsCena.disconnect();
  };
}

const mqDesktop = typeof window !== "undefined" ? window.matchMedia("(min-width: 900px) and (hover: hover)") : null;

function iniciarCena(cena, canvas) {
  pararParticulas();
  pararParticulas = iniciarParticulas(cena, canvas, {
    cores: ["#0d0d0d", "#0d0d0d", "#0d0d0d", "#ff4a3d", "#ff4a3d", "#6e060c"],
    densidade: 1,
    velocidade: 1.25,
    pausarForaDaTela: true,
  });
  const pararObs = configurarObservadores(cena);
  const temParallax = !reduzido.value && !!mqDesktop?.matches;
  if (temParallax) window.addEventListener("scroll", aoRolar, { passive: true });
  pararCena = () => {
    pararObs();
    if (temParallax) window.removeEventListener("scroll", aoRolar);
    cancelAnimationFrame(rafRolagem);
    rafRolagem = 0;
  };
}

// a cena só existe quando o membro carregou; monta os efeitos quando ela aparece
watch(
  cenaRef,
  (cena) => {
    if (!cena || reduzido.value) return;
    if (canvasRef.value) iniciarCena(cena, canvasRef.value);
  },
  { flush: "post" }
);

// ---- trilha sonora: sem autoplay; arquivos locais; cada faixa pode ficar indisponível ----
const FAIXAS = [
  { titulo: "Triunfo", artista: "Emicida", src: "/sons/Emicida%20-%20Triunfo.mp3" }, // principal
  { titulo: "Ponto de Vista", artista: "Djonga", src: "/sons/PontodeVista.mp3" },
];
const indiceFaixa = ref(0);
const tocando = ref(false);
const indisponiveis = ref(FAIXAS.map(() => false));
const faixaAtual = computed(() => FAIXAS[indiceFaixa.value]);
const estadoMusica = computed(() => {
  if (indisponiveis.value.every(Boolean)) return "indisponivel";
  return tocando.value ? "tocando" : "parada";
});
let audio = null;
let trocandoSrc = false;

function proximoDisponivel(de, passo) {
  const n = FAIXAS.length;
  for (let k = 1; k <= n; k++) {
    const i = (de + passo * k + n * k) % n;
    if (!indisponiveis.value[i]) return i;
  }
  return -1;
}

function criarAudio() {
  audio = new Audio(); // um único elemento/MediaElementSource: só o src muda
  audio.preload = "none";
  audio.src = faixaAtual.value.src;
  audio.volume = 0.5;
  audio.addEventListener("error", () => {
    if (trocandoSrc || !audio?.getAttribute("src")) return;
    const quisTocar = tocando.value;
    indisponiveis.value[indiceFaixa.value] = true;
    const prox = proximoDisponivel(indiceFaixa.value, 1);
    if (prox < 0) {
      tocando.value = false;
      return;
    }
    irParaFaixa(prox, quisTocar);
  });
  audio.addEventListener("pause", () => {
    if (!trocandoSrc && !audio.ended) tocando.value = false;
  });
  audio.addEventListener("ended", () => proxima(true));
}

function tocarAtual() {
  usePlayerStore().pausarPorMidia(); // um som por vez: pausa a música global
  tocando.value = true;
  audio
    .play()
    .then(() => {
      iniciarAnalise();
      ligarBatida();
    })
    .catch((e) => {
      if (e?.name === "AbortError") return; // troca de faixa interrompeu o play
      // NotAllowedError = gesto/permissão; o resto = arquivo ausente ou ilegível.
      if (e?.name === "NotAllowedError") tocando.value = false;
      else if (!audio.error) {
        tocando.value = false;
        indisponiveis.value[indiceFaixa.value] = true;
      }
    });
}

function irParaFaixa(i, tocar) {
  indiceFaixa.value = i;
  if (!audio) criarAudio();
  trocandoSrc = true;
  audio.src = FAIXAS[i].src;
  trocandoSrc = false;
  if (tocar) tocarAtual();
  else tocando.value = false;
}

function proxima(auto) {
  const i = proximoDisponivel(indiceFaixa.value, 1);
  if (i < 0) return;
  // fim da faixa: segue tocando (a trilha volta ao início em loop); botão: mantém o estado
  irParaFaixa(i, auto || tocando.value);
}

function faixaAnterior() {
  const i = proximoDisponivel(indiceFaixa.value, -1);
  if (i < 0) return;
  irParaFaixa(i, tocando.value);
}

function selecionarFaixa(i) {
  if (indisponiveis.value[i]) return;
  if (i === indiceFaixa.value) {
    alternarMusica();
    return;
  }
  irParaFaixa(i, true);
}

function alternarMusica() {
  if (estadoMusica.value === "indisponivel") return;
  if (indisponiveis.value[indiceFaixa.value]) {
    const i = proximoDisponivel(indiceFaixa.value, 1);
    if (i < 0) return;
    irParaFaixa(i, true);
    return;
  }
  if (!audio) criarAudio();
  if (tocando.value) {
    audio.pause();
    return;
  }
  tocarAtual();
}

async function verificarArquivos() {
  await Promise.all(
    FAIXAS.map(async (f, i) => {
      try {
        const r = await fetch(f.src, { method: "HEAD" });
        const tipo = r.headers.get("content-type") || "";
        if (!r.ok || tipo.includes("text/html")) indisponiveis.value[i] = true;
      } catch {
        /* offline: deixa o clique decidir */
      }
    })
  );
  // a principal sumiu: a faixa atual passa para a primeira disponível
  if (indisponiveis.value[indiceFaixa.value] && !audio) {
    const i = proximoDisponivel(indiceFaixa.value, 1);
    if (i >= 0) indiceFaixa.value = i;
  }
}

function aoMudarVisibilidade() {
  pausadoAba = document.visibilityState === "hidden";
  if (pausadoAba && estadoMusica.value === "tocando") audio?.pause();
  atualizarPausa();
}

onMounted(() => {
  if (!reduzido.value) {
    revelar.value = true;
    abertura.value = true;
    // o overlay sai com transform (~1,45 s); depois a página fica à vista
    tAbertura = window.setTimeout(() => (abertura.value = false), 1450);
  }
  verificarArquivos();
  document.addEventListener("visibilitychange", aoMudarVisibilidade);
});

onBeforeUnmount(() => {
  clearTimeout(tAbertura);
  somEntrada.parar();
  pararParticulas();
  pararCena();
  cancelAnimationFrame(rafBatida);
  rafBatida = 0;
  document.removeEventListener("visibilitychange", aoMudarVisibilidade);
  if (audio) {
    audio.pause();
    audio.removeAttribute("src");
    audio.load();
    audio = null;
  }
  if (contextoAudio) {
    contextoAudio.close?.().catch?.(() => {});
    contextoAudio = null;
  }
  analisador = null;
});
</script>

<style scoped>
.jp {
  --jp-vermelho: #c8101a;
  --jp-vermelho-esc: #6e060c;
  --jp-preto: #0d0d0d;
  --jp-creme: #efe2c0;
  --jp-h: clamp(150px, 28vh, 290px);
  --jp-ruido-leve: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='240'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 .14 0'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>");
  --jp-ruido: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='240' height='240'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/><feColorMatrix values='0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 .55 0'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>");
}
.jp.membro-pagina {
  background-color: var(--jp-creme);
  background-image: var(--jp-ruido-leve);
}

/* ---------- botão de som (canto, discreto) ---------- */
.jp-som {
  position: fixed;
  top: 12px;
  right: 12px;
  z-index: 1900;
  display: inline-grid;
  place-items: center;
  width: 40px;
  height: 40px;
  padding: 0;
  border: 0;
  background: var(--jp-preto);
  color: #f6ecd0;
  cursor: pointer;
  opacity: 0.85;
}
.jp-som:hover {
  opacity: 1;
}
.jp-som:focus-visible {
  outline: 3px solid #f6ecd0;
  outline-offset: 2px;
}

/* ---------- abertura do nome ---------- */
.jp-abertura {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: grid;
  place-items: center;
  overflow: hidden;
  background: var(--jp-vermelho);
  pointer-events: none;
  animation: jp-abertura-sai 1.45s cubic-bezier(0.7, 0, 0.2, 1) both;
}
/* faixa preta diagonal cortando o vermelho */
.jp-abertura::before {
  content: "";
  position: absolute;
  left: -20%;
  right: -20%;
  top: 38%;
  height: 26vh;
  background: var(--jp-preto);
  transform: rotate(-8deg) translate3d(-115%, 0, 0);
  animation: jp-abertura-faixa 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) 0.04s both;
}
/* flash de serigrafia */
.jp-abertura::after {
  content: "";
  position: absolute;
  inset: 0;
  background: #f6ecd0;
  opacity: 0;
  animation: jp-abertura-flash 0.32s ease-out 0.3s both;
}
.jp-abertura-nome {
  position: relative;
  z-index: 1;
  font-family: var(--membro-fonte-titulo);
  font-size: clamp(3.5rem, 17vw, 14rem);
  line-height: 0.9;
  text-transform: uppercase;
  text-align: center;
  color: #f6ecd0;
  padding: 0.1em 0.25em;
  background: var(--jp-preto);
  transform: rotate(-3deg);
  box-shadow: 0.08em 0.08em 0 #f6ecd0;
  animation: jp-abertura-carimbo 0.42s cubic-bezier(0.2, 1.4, 0.4, 1) 0.18s both;
}
/* cada letra cai como tipo móvel */
.jp-letra {
  display: inline-block;
  white-space: pre;
  animation: jp-letra-cai 0.34s cubic-bezier(0.2, 1.5, 0.4, 1) calc(0.22s + var(--i, 0) * 0.035s) both;
}
@keyframes jp-letra-cai {
  from {
    opacity: 0;
    transform: translate3d(0, -140%, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}
@keyframes jp-abertura-faixa {
  to {
    transform: rotate(-8deg) translate3d(0, 0, 0);
  }
}
@keyframes jp-abertura-flash {
  0% {
    opacity: 0;
  }
  35% {
    opacity: 0.55;
  }
  100% {
    opacity: 0;
  }
}
@keyframes jp-abertura-carimbo {
  0% {
    opacity: 0;
    transform: rotate(-3deg) scale(2.1);
  }
  60% {
    opacity: 1;
    transform: rotate(-3deg) scale(0.93);
  }
  100% {
    opacity: 1;
    transform: rotate(-3deg) scale(1);
  }
}
@keyframes jp-abertura-sai {
  0%,
  70% {
    transform: translate3d(0, 0, 0);
  }
  100% {
    transform: translate3d(0, -101%, 0);
  }
}

/* ---------- cena do hero ---------- */
.jp :deep(.mp-hero) {
  min-height: min(88dvh, 780px);
  padding-bottom: calc(var(--jp-h) * 0.95 + 24px);
  overflow: hidden;
}
.jp :deep(.mp-hero-fundo) {
  overflow: hidden;
}
.jp :deep(.mp-medalhao) {
  display: none; /* a foto já está na moldura do cartaz */
}
.jp-cena {
  position: absolute;
  inset: 0;
  background: var(--jp-vermelho);
  overflow: hidden;
}
.jp-raios {
  position: absolute;
  left: 72%;
  top: 28%;
  width: 260vmax;
  aspect-ratio: 1;
  margin: -130vmax 0 0 -130vmax;
  background: repeating-conic-gradient(from 0deg, transparent 0 8deg, rgba(13, 13, 13, 0.3) 8deg 12deg);
  -webkit-mask-image: radial-gradient(circle, #000 4%, transparent 55%);
  mask-image: radial-gradient(circle, #000 4%, transparent 55%);
  /* gira devagar; o ritmo da música só modula a opacidade (var --jp-beat) */
  opacity: calc(0.84 + var(--jp-beat, 0) * 0.16);
  animation: jp-gira 150s linear infinite;
  will-change: transform;
}
.jp-raios--creme {
  background: repeating-conic-gradient(from 6deg, transparent 0 14deg, rgba(246, 236, 208, 0.16) 14deg 17deg);
  animation-duration: 110s;
  animation-direction: reverse;
}
@keyframes jp-gira {
  to {
    transform: rotate(360deg);
  }
}
.jp-formas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
/* formas construtivistas: entram com diagonais, em ordem (--i) */
.jp-forma {
  --de-x: 0px;
  --de-y: 60px;
  --op: 1;
  animation: jp-forma-entra 0.75s cubic-bezier(0.2, 0.8, 0.2, 1) both;
  animation-delay: calc(0.35s + var(--i, 0) * 90ms);
}
.jp-diag {
  --de-x: -260px;
  --de-y: 0px;
  --op: 0.9;
}
.jp-bloco {
  --de-x: 0px;
  --de-y: -200px;
}
.jp-circulo {
  --de-x: 120px;
  --de-y: 0px;
}
.jp-cunha {
  --de-x: 0px;
  --de-y: 0px;
}
.jp-estrela {
  --de-x: 0px;
  --de-y: 0px;
}
@keyframes jp-forma-entra {
  from {
    opacity: 0;
    transform: translate3d(var(--de-x), var(--de-y), 0) scale(0.92);
  }
  to {
    opacity: var(--op);
    transform: translate3d(0, 0, 0) scale(1);
  }
}
/* estrelas: entram com giro curto (o pulso da música fica na estrela da moldura) */
.jp-estrela {
  transform-box: fill-box;
  transform-origin: 50% 50%;
  animation-name: jp-estrela-entra;
  animation-duration: 0.6s;
  animation-timing-function: cubic-bezier(0.2, 1.3, 0.4, 1);
}
@keyframes jp-estrela-entra {
  from {
    opacity: 0;
    transform: rotate(-120deg) scale(0.4);
  }
  to {
    opacity: 1;
    transform: rotate(0deg) scale(1);
  }
}
.jp-meiotom {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 40%, transparent 30%, rgba(60, 0, 4, 0.5) 100%);
}
.jp-particulas {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0.8;
}
.jp-textura {
  position: absolute;
  z-index: 10;
  pointer-events: none;
  inset: 0;
  background-image: var(--jp-ruido), radial-gradient(rgba(13, 13, 13, 0.35) 1px, transparent 1.6px);
  background-size: 240px 240px, 7px 7px;
  opacity: 0.45;
  mix-blend-mode: multiply;
}

/* carcará e indígena */
.jp-ceu {
  position: absolute;
  inset: 4% 0 auto 0;
  height: 30%;
}
.jp-carcara {
  position: absolute;
  left: 52%;
  top: 6%;
  width: clamp(130px, 24vw, 340px);
  --sil-cor: var(--jp-preto);
  opacity: 0;
  /* travessia da direita para a esquerda: ciclo de 30 s (~23 s no céu + pausa fora da tela) */
  animation: jp-carcara-cruza 30s linear 2.6s infinite;
  will-change: transform, opacity;
}
/* X e opacidade: velocidade quase constante, com uma pausa irregular (parada em 35%-40%) */
@keyframes jp-carcara-cruza {
  0% { opacity: 0; transform: translate3d(34vw, 0, 0); }
  5% { opacity: 1; transform: translate3d(26vw, 0, 0); }
  13.33% { transform: translate3d(14vw, 0, 0); }
  21.67% { transform: translate3d(0, 0, 0); }
  30% { transform: translate3d(-14vw, 0, 0); }
  35% { transform: translate3d(-20vw, 0, 0); }
  40% { transform: translate3d(-21vw, 0, 0); }
  50% { transform: translate3d(-48vw, 0, 0); }
  58.33% { transform: translate3d(-62vw, 0, 0); }
  66.67% { transform: translate3d(-80vw, 0, 0); }
  73.33% { opacity: 1; transform: translate3d(-94vw, 0, 0); }
  76.67% { opacity: 0; transform: translate3d(-104vw, 0, 0); }
  100% { opacity: 0; transform: translate3d(-104vw, 0, 0); }
}
/* Y, banco (rotação) e profundidade: ondas suaves; ao fundo fica menor */
.jp-carcara-corpo {
  transform-origin: 50% 50%;
  animation: jp-carcara-ondas 30s ease-in-out 2.6s infinite;
  will-change: transform;
}
@keyframes jp-carcara-ondas {
  0% { transform: translate3d(0, 6%, 0) rotate(0deg) scale(1.06); }
  5% { transform: translate3d(0, -2%, 0) rotate(-3deg) scale(1.04); }
  13.33% { transform: translate3d(0, -10%, 0) rotate(-4deg) scale(1.02); }
  21.67% { transform: translate3d(0, 4%, 0) rotate(4deg) scale(1); }
  30% { transform: translate3d(0, -13%, 0) rotate(-5deg) scale(0.96); }
  35% { transform: translate3d(0, -16%, 0) rotate(0deg) scale(0.95); }
  40% { transform: translate3d(0, -14%, 0) rotate(1deg) scale(0.94); }
  50% { transform: translate3d(0, -4%, 0) rotate(3deg) scale(0.9); }
  58.33% { transform: translate3d(0, -10%, 0) rotate(-3deg) scale(0.88); }
  66.67% { transform: translate3d(0, -2%, 0) rotate(3deg) scale(0.85); }
  73.33% { transform: translate3d(0, -8%, 0) rotate(0deg) scale(0.82); }
  76.67% { transform: translate3d(0, -10%, 0) rotate(0deg) scale(0.8); }
  100% { transform: translate3d(0, 6%, 0) rotate(0deg) scale(1.06); }
}
/* batidas (descida rápida, subida lenta) a cada 0,9 s; depois planeio de ~2 s com asas quase retas.
   Ciclo de 4,8 s. A asa direita é espelhada (sinal invertido). */
.jp-carcara :deep(.sil-asa-esq) {
  transform-box: view-box;
  animation: jp-asa-esq 4.8s linear infinite;
}
.jp-carcara :deep(.sil-asa-dir) {
  transform-box: view-box;
  animation: jp-asa-dir 4.8s linear infinite;
}
.jp-carcara :deep(.sil-cauda) {
  transform-box: fill-box;
  transform-origin: 50% 0;
  animation: jp-cauda 6s ease-in-out infinite alternate;
}
@keyframes jp-asa-esq {
  0% { transform: rotate(6deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  6.25% { transform: rotate(-18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  18.75% { transform: rotate(10deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  25% { transform: rotate(-18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  37.5% { transform: rotate(10deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  43.75% { transform: rotate(-18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  56.25% { transform: rotate(6deg); animation-timing-function: ease-in-out; }
  72% { transform: rotate(9deg); animation-timing-function: ease-in-out; }
  86% { transform: rotate(3deg); animation-timing-function: ease-in-out; }
  100% { transform: rotate(6deg); }
}
@keyframes jp-asa-dir {
  0% { transform: rotate(-6deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  6.25% { transform: rotate(18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  18.75% { transform: rotate(-10deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  25% { transform: rotate(18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  37.5% { transform: rotate(-10deg); animation-timing-function: cubic-bezier(0.55, 0, 0.9, 0.5); }
  43.75% { transform: rotate(18deg); animation-timing-function: cubic-bezier(0.4, 0, 0.6, 1); }
  56.25% { transform: rotate(-6deg); animation-timing-function: ease-in-out; }
  72% { transform: rotate(-9deg); animation-timing-function: ease-in-out; }
  86% { transform: rotate(-3deg); animation-timing-function: ease-in-out; }
  100% { transform: rotate(-6deg); }
}
@keyframes jp-cauda {
  from { transform: scale(0.86, 1); }
  to { transform: scale(1.14, 1.04); }
}

.jp-indigena {
  position: absolute;
  z-index: 2; /* entre a camada de fundo e a do meio: de corpo inteiro, sem ser coberto */
  right: 3%;
  bottom: 0;
  height: calc(var(--jp-h) * 1.4); /* cabeça logo abaixo do bloco do nome */
  --sil-cor: #0d0d0d;
  --sil-realce-op: 0.6;
  animation: jp-indigena-entra 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) 0.9s both;
}
@keyframes jp-indigena-entra {
  from {
    opacity: 0;
    transform: translate3d(30%, 20%, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}
/* ciclo de 6 s: repousa, puxa a corda pelo nock, solta (flecha sai do eixo, rastro atrás), repousa */
.jp-indigena :deep(.sil-corda-sup) {
  transform-box: view-box;
  transform-origin: 100px 28px;
  animation: jp-corda-sup 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-corda-inf) {
  transform-box: view-box;
  transform-origin: 100px 172px;
  animation: jp-corda-inf 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-braco-tras) {
  transform-box: view-box;
  transform-origin: 158px 116px;
  animation: jp-braco-tras 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-antebraco) {
  transform-box: view-box;
  transform-origin: 132px 141px;
  animation: jp-antebraco 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-flecha) {
  animation: jp-flecha 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-rastro) {
  transform-box: fill-box;
  transform-origin: 0% 50%;
  animation: jp-rastro 6s linear 1.8s infinite;
}
.jp-indigena :deep(.sil-cocar) {
  transform-box: view-box;
  transform-origin: 150px 60px;
  animation: jp-cocar 1.8s ease-in-out infinite alternate;
}
@keyframes jp-corda-sup {
  0%,
  56% {
    transform: none;
    animation-timing-function: ease-in-out;
  }
  62% {
    transform: rotate(-22.6deg) scaleY(1.0833);
    animation-timing-function: linear;
  }
  66% {
    transform: rotate(-22.6deg) scaleY(1.0833);
    animation-timing-function: ease-out;
  }
  67.5%,
  100% {
    transform: none;
  }
}
@keyframes jp-corda-inf {
  0%,
  56% {
    transform: none;
    animation-timing-function: ease-in-out;
  }
  62% {
    transform: rotate(22.6deg) scaleY(1.0833);
    animation-timing-function: linear;
  }
  66% {
    transform: rotate(22.6deg) scaleY(1.0833);
    animation-timing-function: ease-out;
  }
  67.5%,
  100% {
    transform: none;
  }
}
@keyframes jp-braco-tras {
  0%,
  56% {
    transform: none;
    animation-timing-function: ease-in-out;
  }
  62% {
    transform: rotate(-25.5deg);
    animation-timing-function: linear;
  }
  66% {
    transform: rotate(-25.5deg);
    animation-timing-function: ease-out;
  }
  67.5%,
  100% {
    transform: none;
  }
}
@keyframes jp-antebraco {
  0%,
  56% {
    transform: none;
    animation-timing-function: ease-in-out;
  }
  62% {
    transform: rotate(46.4deg);
    animation-timing-function: linear;
  }
  66% {
    transform: rotate(46.4deg);
    animation-timing-function: ease-out;
  }
  67.5%,
  100% {
    transform: none;
  }
}
@keyframes jp-flecha {
  0% {
    opacity: 0;
    transform: translate3d(0, 0, 0);
    animation-timing-function: ease-in-out;
  }
  6% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    animation-timing-function: ease-in-out;
  }
  56% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    animation-timing-function: ease-in-out;
  }
  62% {
    opacity: 1;
    transform: translate3d(30px, 0, 0);
    animation-timing-function: linear;
  }
  66% {
    opacity: 1;
    transform: translate3d(30px, 0, 0);
    animation-timing-function: linear;
  }
  67.5% {
    opacity: 1;
    transform: translate3d(0, 0, 0);
    animation-timing-function: ease-in;
  }
  74% {
    opacity: 1;
    transform: translate3d(-200px, 0, 0);
  }
  75%,
  100% {
    opacity: 0;
    transform: translate3d(-200px, 0, 0);
  }
}
@keyframes jp-rastro {
  0%,
  65% {
    opacity: 0;
    transform: scaleX(0);
  }
  66% {
    opacity: 0.75;
    transform: scaleX(0);
    animation-timing-function: linear;
  }
  67.5% {
    opacity: 0.75;
    transform: scaleX(0.1304);
    animation-timing-function: linear;
  }
  74% {
    opacity: 0.6;
    transform: scaleX(1);
  }
  75%,
  100% {
    opacity: 0;
    transform: scaleX(1);
  }
}
@keyframes jp-cocar {
  from {
    transform: rotate(-5deg);
  }
  to {
    transform: rotate(4deg);
  }
}

/* multidão */
.jp-multidao {
  position: absolute;
  inset: auto 0 0 0;
  height: var(--jp-h);
  pointer-events: none;
}
.jp-camada {
  position: absolute;
  inset: 0;
  z-index: 1; /* fundo 1, indígena 2, meio 3, frente 4 (multidão não cria contexto próprio) */
  /* parallax: --py é definido por JS no scroll (só desktop); fundo mais lento */
  transform: translate3d(0, var(--py, 0px), 0);
  will-change: transform;
}
.jp-pessoa {
  position: absolute;
  bottom: 0;
  height: calc(var(--jp-h) * var(--esc, 1));
  transform: translate3d(-50%, 0, 0);
  opacity: 1;
  /* subida em ondas: a ordem vem de --i (fundo -> meio -> frente) */
  animation: jp-sobe 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) both;
  animation-delay: calc(var(--onda, 0s) + var(--atraso, 0s));
}
@keyframes jp-sobe {
  from {
    opacity: 0;
    transform: translate3d(-50%, 110%, 0);
  }
  60% {
    opacity: 1;
    transform: translate3d(-50%, -4%, 0);
  }
  80% {
    transform: translate3d(-50%, 1.5%, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(-50%, 0, 0);
  }
}
/* poucas figuras 'vivas': braço erguendo de vez em quando (fase por --i) */
.jp-pessoa--vivo :deep(.sil-braco) {
  transform-box: fill-box;
  transform-origin: 50% 0;
  animation: jp-ergue 7s ease-in-out infinite;
  animation-delay: calc(var(--i, 0) * -1.3s);
}
@keyframes jp-ergue {
  0%, 70%, 94%, 100% { transform: none; }
  78%, 86% { transform: rotate(-22deg); }
}
/* respiração/balanço leve, fase diferente por pessoa */
.jp-pessoa > svg {
  transform-box: view-box;
  transform-origin: 50% 100%;
  animation: jp-respira 4.2s ease-in-out infinite;
  animation-delay: calc(var(--i, 0) * -0.37s);
}
@keyframes jp-respira {
  0%,
  100% {
    transform: rotate(-0.6deg);
  }
  50% {
    transform: rotate(0.6deg);
  }
}
.jp-camada[data-camada="meio"] {
  z-index: 3;
}
.jp-camada[data-camada="frente"] {
  z-index: 4;
}
.jp-camada[data-camada="fundo"] .jp-pessoa {
  --sil-cor: #8a0c14;
  --sil-realce-op: 0.35;
  height: calc(var(--jp-h) * 0.8 * var(--esc, 1));
}
.jp-camada[data-camada="meio"] .jp-pessoa {
  --sil-cor: #3a0508;
  --sil-realce-op: 0.7;
  height: calc(var(--jp-h) * 0.98 * var(--esc, 1));
}
.jp-camada[data-camada="frente"] .jp-pessoa {
  --sil-cor: #0d0d0d;
  height: calc(var(--jp-h) * 1.2 * var(--esc, 1));
}
@keyframes jp-fade {
  to {
    opacity: 1;
  }
}
@media (max-width: 599px) {
  .jp-camada[data-camada="fundo"] .jp-pessoa:nth-child(3n),
  .jp-camada[data-camada="meio"] .jp-pessoa:nth-child(3n),
  .jp-camada[data-camada="frente"] .jp-pessoa:nth-child(even) {
    display: none;
  }
  .jp-camada[data-camada="frente"] .jp-pessoa {
    height: calc(var(--jp-h) * 1.02 * var(--esc, 1));
  }
  .jp-camada[data-camada="meio"] .jp-pessoa {
    height: calc(var(--jp-h) * 0.84 * var(--esc, 1));
  }
  .jp-indigena {
    height: calc(var(--jp-h) * 1.3);
    right: -4%;
  }
}

/* ---------- conteúdo do hero ---------- */
.jp :deep(.mp-hero-conteudo) {
  z-index: 2;
}
.jp :deep(.mp-simbolo) {
  border: 0;
  border-radius: 0;
  box-shadow: none;
  overflow: visible;
  width: clamp(150px, 30vw, 280px);
}
.jp :deep(.mp-simbolo::after) {
  display: none;
}
.jp-moldura {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: var(--jp-vermelho);
  border: 8px solid var(--jp-preto);
  box-shadow: 0 0 0 5px #f6ecd0, 0 0 0 13px var(--jp-preto), 16px 16px 0 13px var(--jp-preto);
  overflow: visible;
  display: grid;
  place-items: center;
  animation: jp-fade 0.8s ease 2s backwards;
}
.jp-foto {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  filter: grayscale(1) contrast(1.25);
  mix-blend-mode: multiply;
}
.jp-iniciais {
  font-family: var(--membro-fonte-titulo);
  font-size: 5rem;
  color: var(--jp-preto);
}
.jp-moldura-estrela {
  position: absolute;
  right: -14%;
  top: -10%;
  width: 34%;
  transform: scale(calc(1 + var(--jp-beat, 0) * 0.12));
}
.jp :deep(.mp-nome) {
  font-size: clamp(3.4rem, 12vw, 8.5rem);
  line-height: 0.92;
  text-transform: uppercase;
  color: #f6ecd0;
  background: var(--jp-preto);
  display: inline-block;
  padding: 0.06em 0.18em 0.02em;
  transform: rotate(-2deg);
  box-shadow: 0.06em 0.06em 0 #f6ecd0;
}
.jp :deep(.mp-lema) {
  background: #f6ecd0;
  color: var(--jp-preto);
  font-weight: 700;
  padding: 10px 16px;
  border-left: 10px solid var(--jp-preto);
  margin-top: var(--tm-space-4);
  display: inline-block;
}
.jp :deep(.mp-topo) {
  color: #f6ecd0;
  text-shadow: 0 1px 0 var(--jp-preto);
}
.jp :deep(.mp-hero-acoes) {
  margin-top: var(--tm-space-4);
}

/* botão de música no estilo cartaz */
.jp-musica-botao {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-height: 48px;
  padding: 10px 18px;
  font-family: var(--membro-fonte-titulo);
  font-size: 1.2rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #f6ecd0;
  background: var(--jp-preto);
  border: 3px solid var(--jp-preto);
  box-shadow: 5px 5px 0 #f6ecd0;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.jp-musica-botao:hover:not(:disabled) {
  transform: translate(-2px, -2px);
  box-shadow: 7px 7px 0 #f6ecd0;
}
.jp-musica-botao:active:not(:disabled) {
  transform: translate(3px, 3px);
  box-shadow: 2px 2px 0 #f6ecd0;
}
.jp-musica-botao--tocando {
  background: #f6ecd0;
  color: var(--jp-preto);
  box-shadow: 5px 5px 0 var(--jp-preto);
}
/* microinteração: ao trocar play/pause o ícone "carimba" uma vez */
.jp-musica-botao :deep(.v-icon) {
  transition: transform 0.2s ease-out;
}
.jp-musica-botao:not(:disabled):hover :deep(.v-icon) {
  transform: scale(1.12);
}
.jp-musica-botao--tocando :deep(.v-icon) {
  animation: jp-icone-bate 0.32s cubic-bezier(0.2, 1.5, 0.4, 1) both;
}
@keyframes jp-icone-bate {
  from {
    transform: scale(0.6);
  }
  to {
    transform: scale(1);
  }
}
.jp-musica-botao--indisponivel {
  background: rgba(13, 13, 13, 0.88);
  color: #f6ecd0;
  border-style: dashed;
  box-shadow: none;
  font-size: 1rem;
  cursor: default;
}

.jp-trilha-controles {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}
.jp-musica-botao--mini {
  padding: 10px 12px;
}
.jp-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}
.jp-tracklist {
  list-style: none;
  margin: 0 0 var(--tm-space-3);
  padding: 0;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.jp-faixa-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 48px;
  padding: 8px 12px;
  text-align: left;
  font-family: var(--membro-fonte-titulo);
  font-size: 1.2rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--jp-preto);
  background: #f6ecd0;
  border: 3px solid var(--jp-preto);
  cursor: pointer;
  transition: transform 0.15s ease;
}
.jp-faixa-item:hover:not(:disabled) {
  transform: translate(-2px, -2px);
}
.jp-faixa-item--ativa {
  color: #f6ecd0;
  background: var(--jp-preto);
  border-color: #f6ecd0;
  outline: 3px solid var(--jp-preto);
}
.jp-faixa-item--indisponivel {
  border-style: dashed;
  opacity: 0.65;
  cursor: default;
}
.jp-faixa-num {
  font-size: 1.6rem;
  min-width: 1.2ch;
}
.jp-faixa-nome {
  flex: 1;
  min-width: 0;
  overflow-wrap: anywhere;
}
.jp-faixa-tag {
  display: inline-block;
  margin-left: 8px;
  padding: 0 6px;
  font-size: 0.75rem;
  color: #f6ecd0;
  background: var(--jp-vermelho);
  vertical-align: middle;
}
@media (max-width: 480px) {
  .jp-trilha-controles .jp-musica-botao:not(.jp-musica-botao--mini) {
    flex: 1 1 100%;
    order: -1;
    justify-content: center;
  }
}

/* faixa diagonal entre hero e corpo */
.jp-faixa {
  white-space: nowrap;
  overflow: hidden;
  background: var(--jp-preto);
  color: #f6ecd0;
  font-family: var(--membro-fonte-titulo);
  font-size: clamp(1.4rem, 3.5vw, 2.2rem);
  letter-spacing: 0.06em;
  padding: 6px 0;
  border-block: 4px solid var(--jp-vermelho);
  position: relative;
  z-index: 2;
}
/* trilho com 12 repetições: desloca metade (-50%) e recomeça sem salto */
.jp-faixa-trilho {
  display: flex;
  width: max-content;
  animation: jp-faixa-corre 26s linear infinite;
  will-change: transform;
}
@keyframes jp-faixa-corre {
  to {
    transform: translate3d(-50%, 0, 0);
  }
}
.jp-faixa-trilho > span {
  padding-right: 1.2em;
}
.jp-faixa i {
  color: var(--jp-vermelho);
  font-style: normal;
  margin-left: 0.6em;
}

/* ---------- corpo ---------- */
.jp :deep(.mp-titulo) {
  font-family: var(--membro-fonte-titulo);
  margin-bottom: var(--tm-space-4);
  display: inline-block;
  text-transform: uppercase;
  font-size: clamp(1.8rem, 5vw, 2.8rem);
  color: #f6ecd0;
  background: var(--jp-preto);
  padding: 4px 28px 2px 14px;
  clip-path: polygon(0 0, 100% 0, calc(100% - 14px) 100%, 0 100%);
  border-left: 12px solid var(--jp-vermelho);
}
.jp :deep(.mp-meta),
.jp :deep(.mp-vazio) {
  color: #3a3a3a;
}
.jp :deep(.tm-prose) {
  color: var(--jp-preto);
}
.jp :deep(.mp-hist-card) {
  background: #f8f0da;
  border: 3px solid var(--jp-preto);
  border-radius: 0;
  box-shadow: 6px 6px 0 var(--jp-vermelho);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.jp :deep(.mp-hist-card:hover) {
  transform: translate(-2px, -2px);
  box-shadow: 9px 9px 0 var(--jp-vermelho);
}
.jp :deep(.mp-hist-titulo) {
  text-transform: uppercase;
  font-size: 1.35rem;
}
.jp :deep(.mp-mais a),
.jp :deep(.mp-nav) {
  color: var(--jp-preto);
  text-transform: uppercase;
  font-family: var(--membro-fonte-titulo);
  font-size: 1.2rem;
  text-decoration: underline;
  text-decoration-color: var(--jp-vermelho);
  text-decoration-thickness: 4px;
}
.jp :deep(.mp-rodape) {
  border-top: 6px solid var(--jp-preto);
}
.jp-cartaz-musica {
  background: var(--jp-vermelho);
  border: 4px solid var(--jp-preto);
  box-shadow: 8px 8px 0 var(--jp-preto);
  padding: var(--tm-space-5);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  max-width: 560px;
  margin-top: var(--tm-space-4);
}
.jp-cartaz-titulo {
  margin: 0;
  font-family: var(--membro-fonte-titulo);
  font-size: clamp(2.4rem, 8vw, 4.2rem);
  line-height: 0.95;
  text-transform: uppercase;
  color: #f6ecd0;
  background: var(--jp-preto);
  padding: 2px 12px 0;
}
.jp-cartaz-artista {
  margin: 0 0 var(--tm-space-3);
  font-family: var(--membro-fonte-titulo);
  font-size: 1.6rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--jp-preto);
  background: #f6ecd0;
  padding: 0 10px;
}

/* ---------- blocos de conteúdo: entram ao rolar (só com JS e sem reduzir) ---------- */
.jp-bloco-entra {
  transition: opacity 0.45s ease-out, transform 0.45s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.jp--revelar .jp-bloco-entra:not(.jp-moldura):not(.jp-visivel) {
  opacity: 0;
  transform: translate3d(0, 28px, 0);
}

/* ---------- pausa fora da viewport / aba oculta: loops CSS param ---------- */
.jp--pausado :deep(*) {
  animation-play-state: paused !important;
}

/* ---------- mobile: menos camadas em movimento ---------- */
@media (max-width: 768px) {
  .jp-pessoa > svg,
  .jp-raios--creme {
    animation: none;
  }
  .jp-carcara :deep(.sil-cauda),
  .jp-pessoa--vivo :deep(.sil-braco) {
    animation: none;
  }
}

/* ---------- reduced motion: estado final estático ---------- */
.jp--reduzido :deep(*),
.jp--reduzido {
  animation: none !important;
  transition: none !important;
}
.jp--reduzido .jp-pessoa,
.jp--reduzido .jp-moldura {
  opacity: 1;
}
.jp--reduzido .jp-carcara {
  opacity: 1;
  animation: none;
}
@media (prefers-reduced-motion: reduce) {
  .jp-abertura {
    display: none;
  }
  .jp-pessoa,
  .jp-moldura {
    animation: none;
    opacity: 1;
  }
  .jp-carcara,
  .jp-carcara-corpo,
  .jp-indigena {
    opacity: 1;
    animation: none;
  }
  .jp-pessoa > svg,
  .jp-pessoa :deep(.sil-braco) {
    animation: none;
  }
  .jp-bloco-entra,
  .jp :deep(.mp-hist-card) {
    transition: none;
  }
  .jp--revelar .jp-bloco-entra:not(.jp-visivel) {
    opacity: 1;
    transform: none;
  }
}
html[data-reduzir="1"] .jp-abertura {
  display: none;
}
html[data-reduzir="1"] .jp-pessoa,
html[data-reduzir="1"] .jp-moldura {
  animation: none;
  opacity: 1;
}
html[data-reduzir="1"] .jp-carcara,
html[data-reduzir="1"] .jp-indigena {
  opacity: 1;
}
html[data-reduzir="1"] .jp *,
html[data-reduzir="1"] .jp :deep(*) {
  animation: none !important;
  transition: none !important;
}
</style>
