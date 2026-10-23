import { defineStore } from "pinia";
import { ref, computed, watch } from "vue";
import { renovarUrlMusica } from "@/services/musica.service";
import { useNotify } from "@/composables/useNotify";

const CHAVE = "tunel-player";
const MARGEM_EXPIRACAO_MS = 60_000;

let audio = null;
const getAudio = () => {
  if (!audio) {
    audio = new Audio();
    audio.preload = "none";
  }
  return audio;
};

// Mídias locais (trilha de página, efeito de entrada): pausadas quando o player global começa a tocar.
const midiasLocais = new Set();

const clonar = (f) => ({
  uuid: f.uuid,
  titulo: f.titulo,
  artista: f.artista ?? null,
  album: f.album ?? null,
  duracao_segundos: f.duracao_segundos ?? null,
  capa_url: f.capa_url ?? null,
  url: f.url,
  url_expira_em: f.url_expira_em ?? null,
});

// Persistência sem segredos: nunca grava a url assinada (nem url_expira_em); ela é buscada ao tocar.
const semUrl = (f) => ({
  uuid: f.uuid,
  titulo: f.titulo,
  artista: f.artista ?? null,
  album: f.album ?? null,
  duracao_segundos: f.duracao_segundos ?? null,
  capa_url: f.capa_url ?? null,
});

function embaralhar(lista) {
  const a = [...lista];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

/**
 * Player de música global. Um único <audio> fora da árvore de componentes: continua tocando
 * ao trocar de rota. Fila e posição persistem em localStorage (restaura pausado, sem autoplay).
 * Ganchos: PlayerBarra.vue (.player-barra, .player-expandida, .player-capa, .player-fila).
 */
export const usePlayerStore = defineStore("player", () => {
  const notify = useNotify();

  const fila = ref([]);
  const indice = ref(-1);
  const tocando = ref(false);
  const carregando = ref(false);
  const tempo = ref(0);
  const duracao = ref(0);
  const volume = ref(1);
  const mudo = ref(false);
  const repetir = ref("off"); // off | todas | uma
  const aleatorio = ref(false);
  const original = ref(null); // ordem antes de embaralhar
  const expandido = ref(false);
  const filaAberta = ref(false);
  const pausadoPorOutro = ref(false);
  const anuncio = ref("");

  let srcCarregado = false;
  let buscaPendente = null;
  let tentativaRenovar = false;
  let iniciado = false;
  let ultimoSalvo = 0;
  let queriaTocar = false;

  const atual = computed(() => fila.value[indice.value] ?? null);
  const temFila = computed(() => fila.value.length > 0 && indice.value >= 0);
  const uuidAtual = computed(() => atual.value?.uuid ?? null);
  const duracaoTotal = computed(() => duracao.value || atual.value?.duracao_segundos || 0);

  /* ---------- persistência ---------- */
  function salvar() {
    try {
      if (!fila.value.length) {
        localStorage.removeItem(CHAVE);
        return;
      }
      localStorage.setItem(
        CHAVE,
        JSON.stringify({
          fila: fila.value.map(semUrl),
          indice: indice.value,
          tempo: tempo.value,
          volume: volume.value,
          mudo: mudo.value,
          repetir: repetir.value,
          aleatorio: aleatorio.value,
          original: original.value?.map(semUrl) ?? null,
        }),
      );
      ultimoSalvo = tempo.value;
    } catch {
      /* cota cheia ou modo privado: ignora */
    }
  }

  function restaurar() {
    try {
      const bruto = localStorage.getItem(CHAVE);
      if (!bruto) return;
      const d = JSON.parse(bruto);
      if (!Array.isArray(d.fila) || !d.fila.length) return;
      fila.value = d.fila.filter((f) => f?.uuid && f.titulo).map(semUrl);
      indice.value = Math.min(Math.max(Number(d.indice) || 0, 0), fila.value.length - 1);
      tempo.value = Number(d.tempo) || 0;
      volume.value = typeof d.volume === "number" ? Math.min(1, Math.max(0, d.volume)) : 1;
      mudo.value = !!d.mudo;
      repetir.value = ["off", "todas", "uma"].includes(d.repetir) ? d.repetir : "off";
      aleatorio.value = !!d.aleatorio;
      original.value = Array.isArray(d.original) ? d.original.filter((f) => f?.uuid).map(semUrl) : null;
      duracao.value = atual.value?.duracao_segundos ?? 0;
      const a = getAudio();
      a.volume = volume.value;
      a.muted = mudo.value;
    } catch {
      localStorage.removeItem(CHAVE);
    }
  }

  /* ---------- URL assinada ---------- */
  const expirando = (f) => !f.url || (f.url_expira_em && new Date(f.url_expira_em).getTime() - Date.now() < MARGEM_EXPIRACAO_MS);

  async function garantirUrl(faixa, forcar = false) {
    if (!forcar && !expirando(faixa)) return;
    const nova = await renovarUrlMusica(faixa);
    if (!nova?.url) throw new Error("sem-url");
    faixa.url = nova.url;
    faixa.url_expira_em = nova.url_expira_em ?? null;
  }

  /* ---------- reprodução ---------- */
  async function carregarFaixa(i, { autoplay = true, inicio = 0 } = {}) {
    if (i < 0 || i >= fila.value.length) return;
    const a = getAudio();
    indice.value = i;
    const faixa = fila.value[i];
    tempo.value = inicio;
    duracao.value = faixa.duracao_segundos ?? 0;
    tentativaRenovar = false;
    pausadoPorOutro.value = false;
    queriaTocar = autoplay;
    carregando.value = true;
    anuncio.value = `Tocando: ${faixa.titulo}${faixa.artista ? `, ${faixa.artista}` : ""}`;
    atualizarMediaSession();
    try {
      await garantirUrl(faixa);
    } catch {
      carregando.value = false;
      falhouFaixa();
      return;
    }
    if (indice.value !== i) return; // usuário trocou de faixa enquanto renovava
    srcCarregado = true;
    buscaPendente = inicio > 0 ? inicio : null;
    a.src = faixa.url;
    if (autoplay) {
      try {
        await a.play();
      } catch (e) {
        // Autoplay bloqueado pelo navegador: fica pausado, o usuário toca em play.
        if (e?.name === "NotAllowedError") carregando.value = false;
      }
    } else {
      carregando.value = false;
    }
    salvar();
  }

  function falhouFaixa() {
    notify.erro("Não foi possível tocar esta faixa.");
    if (indice.value < fila.value.length - 1) proxima({ auto: true });
    else {
      tocando.value = false;
      carregando.value = false;
    }
  }

  async function aoErroAudio() {
    const a = getAudio();
    if (!srcCarregado || !atual.value) return;
    // URL expirada (403) ou rede: renova uma vez e retoma da posição.
    if (tentativaRenovar) return falhouFaixa();
    tentativaRenovar = true;
    const retomar = a.currentTime || tempo.value || 0;
    const faixa = atual.value;
    try {
      await garantirUrl(faixa, true);
      if (atual.value?.uuid !== faixa.uuid) return;
      buscaPendente = retomar > 0 ? retomar : null;
      a.src = faixa.url;
      if (queriaTocar) await a.play().catch(() => {});
    } catch {
      falhouFaixa();
    }
  }

  function ligarEventos() {
    const a = getAudio();
    a.addEventListener("play", () => {
      midiasLocais.forEach((pausa) => pausa());
      tocando.value = true;
      queriaTocar = true;
      pausadoPorOutro.value = false;
    });
    a.addEventListener("pause", () => {
      tocando.value = false;
      if (!a.ended) queriaTocar = false;
    });
    a.addEventListener("playing", () => {
      carregando.value = false;
      tentativaRenovar = false;
    });
    a.addEventListener("waiting", () => (carregando.value = true));
    a.addEventListener("canplay", () => (carregando.value = false));
    a.addEventListener("loadedmetadata", () => {
      if (Number.isFinite(a.duration)) duracao.value = a.duration;
      if (buscaPendente != null) {
        a.currentTime = Math.min(buscaPendente, a.duration || buscaPendente);
        buscaPendente = null;
      }
    });
    a.addEventListener("timeupdate", () => {
      tempo.value = a.currentTime;
      if (Math.abs(tempo.value - ultimoSalvo) > 2) salvar();
      atualizarPosicao();
    });
    a.addEventListener("ended", () => {
      if (repetir.value === "uma") {
        a.currentTime = 0;
        a.play().catch(() => {});
      } else proxima({ auto: true });
    });
    a.addEventListener("error", aoErroAudio);
  }

  async function tocar() {
    if (!atual.value) return;
    pausadoPorOutro.value = false;
    if (!srcCarregado) return carregarFaixa(indice.value, { autoplay: true, inicio: tempo.value });
    try {
      await getAudio().play();
    } catch {
      /* bloqueado: continua pausado */
    }
  }
  const pausar = () => getAudio().pause();
  const alternar = () => (tocando.value ? pausar() : tocar());

  function proxima({ auto = false } = {}) {
    const n = fila.value.length;
    if (!n) return;
    let i = indice.value + 1;
    if (i >= n) {
      if (repetir.value === "todas") i = 0;
      else {
        if (auto) {
          // Fim da fila: para na última, pronta para recomeçar.
          getAudio().pause();
          tocando.value = false;
        }
        return;
      }
    }
    carregarFaixa(i, { autoplay: true });
  }

  function anterior() {
    if (!fila.value.length) return;
    if (tempo.value > 3) return buscar(0);
    let i = indice.value - 1;
    if (i < 0) i = repetir.value === "todas" ? fila.value.length - 1 : 0;
    carregarFaixa(i, { autoplay: true });
  }

  function buscar(segundos) {
    const max = duracaoTotal.value || Infinity;
    const s = Math.min(Math.max(0, segundos), max);
    tempo.value = s;
    if (srcCarregado) getAudio().currentTime = s;
    else buscaPendente = s;
    salvar();
  }

  function definirVolume(v) {
    volume.value = Math.min(1, Math.max(0, v));
    mudo.value = volume.value === 0;
    const a = getAudio();
    a.volume = volume.value;
    a.muted = mudo.value;
    salvar();
  }
  function alternarMudo() {
    mudo.value = !mudo.value;
    getAudio().muted = mudo.value;
    if (!mudo.value && volume.value === 0) definirVolume(0.5);
    salvar();
  }

  function ciclarRepetir() {
    repetir.value = repetir.value === "off" ? "todas" : repetir.value === "todas" ? "uma" : "off";
    salvar();
  }

  function aplicarEmbaralho() {
    original.value = fila.value.map(clonar);
    const cabeca = fila.value[indice.value];
    const resto = fila.value.filter((_, i) => i !== indice.value);
    fila.value = cabeca ? [cabeca, ...embaralhar(resto)] : embaralhar(resto);
    indice.value = cabeca ? 0 : -1;
  }
  function desfazerEmbaralho() {
    if (original.value) {
      const uuid = atual.value?.uuid;
      const porUuid = new Map(fila.value.map((f) => [f.uuid, f]));
      // mantém os objetos atuais (URLs renovadas) na ordem original
      fila.value = original.value.map((o) => porUuid.get(o.uuid) ?? o);
      indice.value = Math.max(0, fila.value.findIndex((f) => f.uuid === uuid));
    }
    original.value = null;
  }
  function alternarAleatorio() {
    aleatorio.value = !aleatorio.value;
    if (aleatorio.value) aplicarEmbaralho();
    else desfazerEmbaralho();
    salvar();
  }

  /* ---------- fila ---------- */
  /** Substitui a fila e toca. faixas: itens de GET /musicas. */
  function tocarLista(faixas, inicio = 0, { embaralhado } = {}) {
    if (!faixas.length) return;
    fila.value = faixas.map(clonar);
    indice.value = Math.min(Math.max(inicio, 0), fila.value.length - 1);
    original.value = null;
    if (embaralhado !== undefined) aleatorio.value = embaralhado;
    if (aleatorio.value) aplicarEmbaralho();
    srcCarregado = false;
    carregarFaixa(indice.value, { autoplay: true });
  }

  function adicionarAFila(faixa) {
    const c = clonar(faixa);
    fila.value.push(c);
    original.value?.push(clonar(faixa));
    if (indice.value < 0) {
      indice.value = 0;
      carregarFaixa(0, { autoplay: true });
    } else {
      notify.sucesso("Adicionada à fila.");
      salvar();
    }
  }

  function tocarAProxima(faixa) {
    if (indice.value < 0) return adicionarAFila(faixa);
    fila.value.splice(indice.value + 1, 0, clonar(faixa));
    original.value?.push(clonar(faixa));
    notify.sucesso("Toca a seguir.");
    salvar();
  }

  function irPara(i) {
    carregarFaixa(i, { autoplay: true });
  }

  function remover(i) {
    if (i < 0 || i >= fila.value.length) return;
    const eraAtual = i === indice.value;
    const uuid = fila.value[i].uuid;
    fila.value.splice(i, 1);
    if (original.value) original.value = original.value.filter((o) => o.uuid !== uuid);
    if (!fila.value.length) return fechar();
    if (i < indice.value) indice.value--;
    else if (eraAtual) {
      indice.value = Math.min(indice.value, fila.value.length - 1);
      carregarFaixa(indice.value, { autoplay: tocando.value });
    }
    salvar();
  }

  function moverNaFila(i, delta) {
    const j = i + delta;
    if (i < 0 || j < 0 || i >= fila.value.length || j >= fila.value.length) return;
    const [item] = fila.value.splice(i, 1);
    fila.value.splice(j, 0, item);
    if (indice.value === i) indice.value = j;
    else if (indice.value === j) indice.value = i;
    salvar();
  }

  /** Mantém só a faixa atual. */
  function limparFila() {
    if (!atual.value) return fechar();
    fila.value = [atual.value];
    indice.value = 0;
    original.value = null;
    salvar();
  }

  function fechar() {
    const a = getAudio();
    a.pause();
    a.removeAttribute("src");
    a.load();
    srcCarregado = false;
    buscaPendente = null;
    fila.value = [];
    indice.value = -1;
    original.value = null;
    tocando.value = false;
    carregando.value = false;
    tempo.value = 0;
    duracao.value = 0;
    expandido.value = false;
    filaAberta.value = false;
    pausadoPorOutro.value = false;
    atualizarMediaSession();
    salvar();
  }

  /** Chamado por players de vídeo/outros: um som por vez. */
  function pausarPorMidia() {
    if (!tocando.value) return;
    getAudio().pause();
    pausadoPorOutro.value = true;
  }

  /** Registra uma mídia local; pausa() é chamada quando o player global começa a tocar. Devolve o cancelamento. */
  function registrarMidiaLocal(pausa) {
    midiasLocais.add(pausa);
    return () => midiasLocais.delete(pausa);
  }

  /* ---------- Media Session ---------- */
  function atualizarMediaSession() {
    if (!("mediaSession" in navigator)) return;
    const f = atual.value;
    if (!f) {
      navigator.mediaSession.metadata = null;
      return;
    }
    navigator.mediaSession.metadata = new MediaMetadata({
      title: f.titulo,
      artist: f.artista ?? "",
      album: f.album ?? "",
      artwork: f.capa_url ? [{ src: f.capa_url, sizes: "512x512" }] : [],
    });
  }
  function atualizarPosicao() {
    if (!("mediaSession" in navigator) || !navigator.mediaSession.setPositionState) return;
    const d = duracaoTotal.value;
    if (!Number.isFinite(d) || d <= 0) return;
    try {
      navigator.mediaSession.setPositionState({ duration: d, position: Math.min(tempo.value, d), playbackRate: 1 });
    } catch {
      /* valores inconsistentes durante a troca de faixa */
    }
  }
  function ligarMediaSession() {
    if (!("mediaSession" in navigator)) return;
    const ms = navigator.mediaSession;
    const set = (acao, fn) => {
      try {
        ms.setActionHandler(acao, fn);
      } catch {
        /* ação não suportada */
      }
    };
    set("play", tocar);
    set("pause", pausar);
    set("previoustrack", anterior);
    set("nexttrack", () => proxima());
    set("seekto", (d) => d.seekTime != null && buscar(d.seekTime));
    set("seekbackward", (d) => buscar(tempo.value - (d.seekOffset || 10)));
    set("seekforward", (d) => buscar(tempo.value + (d.seekOffset || 10)));
  }

  watch(tocando, (v) => {
    if ("mediaSession" in navigator) navigator.mediaSession.playbackState = v ? "playing" : "paused";
  });

  /** Chamar uma vez (PlayerBarra): liga eventos, restaura a fila salva. */
  function iniciar() {
    if (iniciado) return;
    iniciado = true;
    restaurar();
    ligarEventos();
    ligarMediaSession();
    atualizarMediaSession();
  }

  return {
    fila, indice, tocando, carregando, tempo, duracao, volume, mudo, repetir, aleatorio, expandido, filaAberta,
    pausadoPorOutro, anuncio, atual, temFila, uuidAtual, duracaoTotal,
    iniciar, tocar, pausar, alternar, proxima, anterior, buscar, definirVolume, alternarMudo, ciclarRepetir,
    alternarAleatorio, tocarLista, adicionarAFila, tocarAProxima, irPara, remover, moverNaFila, limparFila, fechar,
    pausarPorMidia, registrarMidiaLocal,
  };
});
