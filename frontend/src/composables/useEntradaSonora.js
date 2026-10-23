import { ref } from "vue";
import { usePlayerStore } from "@/stores/player";

/*
 * Efeito sonoro de entrada (Web Audio sintetizado: sem arquivos, sem direitos autorais).
 *
 * Uso:
 *   const som = useEntradaSonora({ ocupado: () => musicaTocando() });
 *   // no elemento que anima (ex.: @animationstart, filtrando o nome da animação):
 *   som.tocar();
 *   // no onBeforeUnmount:
 *   som.parar();
 *
 * Linha do tempo (t = 0 é o início da animação de saída da abertura, ~1,45 s):
 *   0.00–0.42  rufar de timpano grave crescendo (pitch caindo + chiado de caixa)
 *   0.432      "carimbo": acorde de metais (D3/A3/D4, dente de serra, filtro abrindo)
 *              + bumbo + gongo com reverb curto
 *   0.63       cauda épica: stab de metais em G (quarta acima) + chimbal/chiado
 *   1.10–1.50  a cauda some junto com a saída da abertura
 *
 * Política: só toca se o documento já teve interação do usuário (ou se o AudioContext
 * liberar a execução); caso contrário desiste em silêncio. Nunca exibe erro.
 * Preferência de som do site: chave "tunel-som" em localStorage (padrão: ligado).
 */

const CHAVE_SOM = "tunel-som";
const VOLUME_MASTER = 0.35;
const T_CARIMBO = 0.432;

const somLigado = ref(lerPreferencia());

function lerPreferencia() {
  try {
    return localStorage.getItem(CHAVE_SOM) !== "0";
  } catch {
    return true;
  }
}

/** Preferência de som compartilhada entre páginas (botão 🔊/🔇). */
export function useSomSite() {
  function definirSom(valor) {
    somLigado.value = !!valor;
    try {
      localStorage.setItem(CHAVE_SOM, valor ? "1" : "0");
    } catch {
      /* storage bloqueado: a preferência vale só nesta sessão */
    }
  }
  return { somLigado, definirSom, alternarSom: () => definirSom(!somLigado.value) };
}

let ruido = null;
function bufferRuido(c) {
  if (ruido && ruido.sampleRate === c.sampleRate) return ruido;
  const n = c.sampleRate;
  ruido = c.createBuffer(1, n, c.sampleRate);
  const d = ruido.getChannelData(0);
  for (let i = 0; i < n; i++) d[i] = Math.random() * 2 - 1;
  return ruido;
}

function bufferReverb(c, segundos = 1.6, decaimento = 3.2) {
  const n = Math.floor(c.sampleRate * segundos);
  const buf = c.createBuffer(2, n, c.sampleRate);
  for (let ch = 0; ch < 2; ch++) {
    const d = buf.getChannelData(ch);
    for (let i = 0; i < n; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / n, decaimento);
  }
  return buf;
}

/** Monta o grafo sobre um AudioContext e agenda a sequência a partir de t0. */
function agendar(c, t0) {
  // master -> compressor (limiter suave: sem clipping) -> saída
  const master = c.createGain();
  master.gain.value = VOLUME_MASTER;
  const limitador = c.createDynamicsCompressor();
  limitador.threshold.value = -10;
  limitador.knee.value = 6;
  limitador.ratio.value = 12;
  limitador.attack.value = 0.003;
  limitador.release.value = 0.15;
  master.connect(limitador);
  limitador.connect(c.destination);

  // grupo da entrada: concentra o fade da cauda (some quando a abertura sai)
  const grupo = c.createGain();
  grupo.gain.setValueAtTime(1, t0 + 1.1);
  grupo.gain.linearRampToValueAtTime(0, t0 + 1.5);
  grupo.connect(master);

  // reverb curto (convolver com impulso gerado)
  const verb = c.createConvolver();
  verb.buffer = bufferReverb(c);
  const verbSaida = c.createGain();
  verbSaida.gain.value = 0.6;
  verb.connect(verbSaida);
  verbSaida.connect(grupo);

  // envia uma fonte ao grupo, com parcela opcional de reverb
  function conectar(no, wet = 0) {
    no.connect(grupo);
    if (wet > 0) {
      const envio = c.createGain();
      envio.gain.value = wet;
      no.connect(envio);
      envio.connect(verb);
    }
  }

  // rufar grave crescendo: toques cada vez mais próximos e mais fortes
  const toques = [0, 0.085, 0.16, 0.225, 0.283, 0.335, 0.38, 0.418];
  toques.forEach((dt, i) => {
    const t = t0 + dt;
    const pico = 0.18 + 0.5 * (i / (toques.length - 1));
    const o = c.createOscillator();
    o.type = "sine";
    o.frequency.setValueAtTime(150, t);
    o.frequency.exponentialRampToValueAtTime(55, t + 0.18);
    const g = c.createGain();
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(pico, t + 0.003);
    g.gain.exponentialRampToValueAtTime(0.0005, t + 0.26);
    o.connect(g);
    conectar(g, 0.12);
    o.start(t);
    o.stop(t + 0.28);
  });
  // chiado de caixa sob o rufar (ruído passa-faixa com crescendo)
  const chiado = c.createBufferSource();
  chiado.buffer = bufferRuido(c);
  const passaFaixa = c.createBiquadFilter();
  passaFaixa.type = "bandpass";
  passaFaixa.frequency.value = 2000;
  passaFaixa.Q.value = 1;
  const gChiado = c.createGain();
  gChiado.gain.setValueAtTime(0.0001, t0);
  gChiado.gain.exponentialRampToValueAtTime(0.1, t0 + 0.4);
  gChiado.gain.setValueAtTime(0.0001, t0 + 0.43);
  chiado.connect(passaFaixa);
  passaFaixa.connect(gChiado);
  conectar(gChiado);
  chiado.start(t0);
  chiado.stop(t0 + 0.45);

  // metal: dois dentes de serra desafinados, filtro passa-baixa que abre no ataque
  function metal(t, freq, dur, pico) {
    const filtro = c.createBiquadFilter();
    filtro.type = "lowpass";
    filtro.Q.value = 4;
    filtro.frequency.setValueAtTime(380, t);
    filtro.frequency.exponentialRampToValueAtTime(2600, t + 0.11);
    filtro.frequency.exponentialRampToValueAtTime(1800, t + 0.3);
    const g = c.createGain();
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(pico, t + 0.025);
    g.gain.setValueAtTime(pico * 0.8, t + dur - 0.12);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    filtro.connect(g);
    conectar(g, 0.22);
    for (const desafino of [-7, 7]) {
      const o = c.createOscillator();
      o.type = "sawtooth";
      o.frequency.value = freq;
      o.detune.value = desafino;
      o.connect(filtro);
      o.start(t);
      o.stop(t + dur + 0.02);
    }
  }

  // carimbo: acorde de metais (tônica, quinta, oitava) + bumbo + gongo
  const tC = t0 + T_CARIMBO;
  [146.83, 220.0, 293.66].forEach((f) => metal(tC, f, 0.62, 0.07));

  const bumbo = c.createOscillator();
  bumbo.type = "sine";
  bumbo.frequency.setValueAtTime(140, tC);
  bumbo.frequency.exponentialRampToValueAtTime(45, tC + 0.16);
  const gBumbo = c.createGain();
  gBumbo.gain.setValueAtTime(0.0001, tC);
  gBumbo.gain.exponentialRampToValueAtTime(0.8, tC + 0.002);
  gBumbo.gain.exponentialRampToValueAtTime(0.0005, tC + 0.42);
  bumbo.connect(gBumbo);
  conectar(gBumbo, 0.2);
  bumbo.start(tC);
  bumbo.stop(tC + 0.44);

  const gongo = c.createGain();
  gongo.gain.setValueAtTime(0.0001, tC);
  gongo.gain.exponentialRampToValueAtTime(0.22, tC + 0.006);
  gongo.gain.exponentialRampToValueAtTime(0.0005, tC + 1.9);
  conectar(gongo, 0.5);
  [
    [1, 0.35],
    [2.32, 0.22],
    [3.05, 0.15],
    [4.2, 0.08],
  ].forEach(([razao, nivel]) => {
    const o = c.createOscillator();
    o.type = "sine";
    o.frequency.value = 82 * razao;
    const g = c.createGain();
    g.gain.value = nivel;
    o.connect(g);
    g.connect(gongo);
    o.start(tC);
    o.stop(tC + 1.95);
  });

  // cauda épica: stab de metais em G (quarta acima) e chiado de chimbal que decai
  [196.0, 293.66, 392.0].forEach((f) => metal(t0 + 0.63, f, 0.4, 0.05));

  const chimbal = c.createBufferSource();
  chimbal.buffer = bufferRuido(c);
  const altaFaixa = c.createBiquadFilter();
  altaFaixa.type = "highpass";
  altaFaixa.frequency.value = 4000;
  const gChimbal = c.createGain();
  gChimbal.gain.setValueAtTime(0.0001, tC);
  gChimbal.gain.exponentialRampToValueAtTime(0.07, tC + 0.02);
  gChimbal.gain.exponentialRampToValueAtTime(0.0005, tC + 1.2);
  chimbal.connect(altaFaixa);
  altaFaixa.connect(gChimbal);
  conectar(gChimbal, 0.35);
  chimbal.start(tC);
  chimbal.stop(tC + 1.25);
}

/**
 * @param {{ ocupado?: () => boolean }} [opcoes] ocupado(): true se a música do dono ou o player global estiverem tocando.
 */
export function useEntradaSonora({ ocupado = () => false } = {}) {
  let ctx = null;
  let tocou = false;
  let fechado = false;
  let timerFechar = 0;

  function fechar() {
    clearTimeout(timerFechar);
    const c = ctx;
    ctx = null;
    c?.close?.().catch?.(() => {});
  }

  function fecharSilencioso() {
    fechar();
  }

  /** Dispara a entrada. Chamar no início da animação de saída da abertura. Só toca uma vez por montagem. */
  async function tocar() {
    if (tocou || fechado) return;
    if (!somLigado.value || ocupado()) return;
    tocou = true;
    // sem interação prévia o navegador nem libera o áudio: não cria nada
    if (typeof navigator !== "undefined" && navigator.userActivation && !navigator.userActivation.hasBeenActive) return;
    const AC = window.AudioContext || window.webkitAudioContext;
    if (!AC) return;
    const inicio = performance.now();
    try {
      ctx = new AC();
      if (ctx.state !== "running") {
        await Promise.race([
          ctx.resume().catch(() => {}),
          new Promise((r) => setTimeout(r, 150)),
        ]);
      }
    } catch {
      return fecharSilencioso();
    }
    if (!ctx || ctx.state !== "running" || fechado) return fecharSilencioso();
    // compensa o tempo gasto aguardando o resume, para manter o sincronismo com a animação
    const decorrido = (performance.now() - inicio) / 1000;
    try {
      agendar(ctx, ctx.currentTime + Math.max(0, 0.02 - decorrido));
    } catch {
      return fecharSilencioso();
    }
    timerFechar = setTimeout(fecharSilencioso, 2000);
  }

  /** Interrompe e libera o AudioContext (unmount ou som desligado). */
  function parar() {
    fechado = true;
    desregistrar();
    fechar();
  }

  // se o player global começar a tocar durante o efeito, corta o efeito
  const desregistrar = usePlayerStore().registrarMidiaLocal(fechar);

  return { tocar, parar };
}
