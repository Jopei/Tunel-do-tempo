import { defineStore } from "pinia";
import { cadastrarFoto } from "@/services/foto.service";
import { cadastrarVideo, moverVideo } from "@/services/video.service";
import { adicionarFotosAlbum } from "@/services/album.service";
import { comprimirImagem, formatarTamanho } from "@/composables/useArquivos";
import { fotoCriada, videoCriado } from "@/composables/useMidiaEventos";
import { interpretarErro } from "@/composables/useApiError";

const CONCORRENCIA = 3;
const TIPO_IMAGEM_GALERIA = 3; // "Galeria" no backend
const LIMITE_FOTO_MB = 4;
export const LIMITE_VIDEO_MB = 50;
export const MAX_FOTOS_LOTE = 50;

let proximoId = 1;
const controladores = new Map(); // id -> AbortController
let timerFechar = null;

/**
 * Fila global de envios (fotos e vídeos), concorrência 3. Continua ao trocar de rota.
 * Estados do item: fila | enviando | concluido | erro | cancelado.
 * Ganchos de animação: UploadPainel.vue (.upload-painel, .upload-item[data-estado], .upload-barra).
 */
export const useUploadStore = defineStore("upload", {
  state: () => ({
    itens: [],
    aberto: false,
    minimizado: false,
    anuncio: "",
    _marcoAnunciado: 0,
  }),

  getters: {
    total: (s) => s.itens.length,
    concluidos: (s) => s.itens.filter((i) => i.estado === "concluido").length,
    comErro: (s) => s.itens.filter((i) => i.estado === "erro").length,
    ativos: (s) => s.itens.filter((i) => i.estado === "fila" || i.estado === "enviando").length,
    enviando: (s) => s.itens.filter((i) => i.estado === "enviando").length,
    terminou() {
      return this.itens.length > 0 && this.ativos === 0;
    },
    progressoGeral(s) {
      if (!s.itens.length) return 0;
      const soma = s.itens.reduce((a, i) => a + (i.estado === "concluido" ? 100 : i.estado === "enviando" ? i.progresso : 0), 0);
      return Math.round(soma / s.itens.length);
    },
  },

  actions: {
    _criar(file, tipo, extra) {
      return {
        id: proximoId++,
        tipo,
        file,
        nome: file.name,
        tamanho: file.size,
        enviado: 0,
        progresso: 0,
        estado: "fila",
        erro: "",
        preview: tipo === "foto" ? URL.createObjectURL(file) : null,
        ...extra,
      };
    },

    /** Devolve mensagens de arquivos recusados (para toast). */
    adicionarFotos(arquivos, { albumUuid = null } = {}) {
      const imagens = arquivos.filter((f) => f.type.startsWith("image/"));
      const recusadas = [];
      if (imagens.length < arquivos.length) recusadas.push(`${arquivos.length - imagens.length} arquivo(s) não são imagens.`);
      const aceitas = imagens.slice(0, MAX_FOTOS_LOTE);
      if (imagens.length > MAX_FOTOS_LOTE) recusadas.push(`Máximo de ${MAX_FOTOS_LOTE} fotos por lote.`);
      this._comecarLote();
      aceitas.forEach((f) => this.itens.push(this._criar(f, "foto", { albumUuid })));
      this._abrir();
      return recusadas;
    },

    adicionarVideos(arquivos, { pastaUuid = null, pastaNome = "", dataVideo = null } = {}) {
      const ok = /\.(mp4|mov|avi)$/i;
      const validos = [];
      const recusadas = [];
      for (const f of arquivos) {
        if (!ok.test(f.name)) recusadas.push(`"${f.name}" não é MP4, MOV ou AVI.`);
        else if (f.size > LIMITE_VIDEO_MB * 1024 * 1024) recusadas.push(`"${f.name}" passa de ${LIMITE_VIDEO_MB} MB.`);
        else validos.push(f);
      }
      this._comecarLote();
      validos.forEach((f) => this.itens.push(this._criar(f, "video", { pastaUuid, pastaNome, dataVideo })));
      this._abrir();
      return recusadas;
    },

    _comecarLote() {
      // Novo lote depois de um terminado: recomeça a lista.
      if (this.terminou) this.limpar();
      clearTimeout(timerFechar);
    },

    _abrir() {
      if (!this.itens.length) return;
      this.aberto = true;
      this.minimizado = false;
      this._marcoAnunciado = 0;
      this._bombear();
    },

    _bombear() {
      while (this.enviando < CONCORRENCIA) {
        const proximo = this.itens.find((i) => i.estado === "fila");
        if (!proximo) break;
        this._enviar(proximo);
      }
      this._anunciar();
      if (this.terminou) this._aoTerminar();
    },

    async _enviar(item) {
      item.estado = "enviando";
      item.erro = "";
      item.progresso = 0;
      const controller = new AbortController();
      controladores.set(item.id, controller);
      const config = {
        signal: controller.signal,
        onUploadProgress: (e) => {
          if (!e.total) return;
          item.enviado = e.loaded;
          item.progresso = Math.min(99, Math.round((e.loaded / e.total) * 100));
        },
      };

      try {
        if (item.tipo === "foto") await this._enviarFoto(item, config);
        else await this._enviarVideo(item, config);
        item.estado = "concluido";
        item.progresso = 100;
      } catch (e) {
        if (e?.code === "ERR_CANCELED" || controller.signal.aborted) {
          item.estado = "cancelado";
        } else {
          item.estado = "erro";
          item.erro = e?.mensagemLocal || interpretarErro(e).mensagem;
        }
      } finally {
        controladores.delete(item.id);
        this._bombear();
      }
    },

    async _enviarFoto(item, config) {
      // Retry após falha no vínculo ao álbum: a foto já existe, só refaz o vínculo.
      if (item.fotoUuid) {
        if (item.albumUuid) await adicionarFotosAlbum(item.albumUuid, [item.fotoUuid]);
        return;
      }
      const arquivo = await comprimirImagem(item.file, { limiarMb: 0, max: 2048, qualidadeInicial: 0.85, alvoMb: LIMITE_FOTO_MB });
      if (arquivo.size > LIMITE_FOTO_MB * 1024 * 1024 || !["image/jpeg", "image/png"].includes(arquivo.type)) {
        const err = new Error("local");
        err.mensagemLocal = `Continua com ${formatarTamanho(arquivo.size)} (limite ${LIMITE_FOTO_MB} MB) ou formato não aceito.`;
        throw err;
      }
      const form = new FormData();
      form.append("tipo_imagem_id", TIPO_IMAGEM_GALERIA);
      form.append("imagem", arquivo);
      const r = await cadastrarFoto(form, config);
      item.fotoUuid = r?.uuid ?? null;
      if (item.albumUuid && r?.uuid) await adicionarFotosAlbum(item.albumUuid, [r.uuid]);
    },

    async _enviarVideo(item, config) {
      if (item.videoUuid) {
        if (item.pastaUuid) await moverVideo(item.videoUuid, item.pastaUuid);
        return;
      }
      const form = new FormData();
      form.append("titulo", item.nome.replace(/\.[^.]+$/, "").slice(0, 255));
      form.append("video", item.file);
      if (item.dataVideo) form.append("data_video", item.dataVideo);
      const r = await cadastrarVideo(form, config);
      item.videoUuid = r?.uuid ?? null;
      if (item.pastaUuid && r?.uuid) await moverVideo(r.uuid, item.pastaUuid);
    },

    cancelar(id) {
      const item = this.itens.find((i) => i.id === id);
      if (!item) return;
      if (item.estado === "enviando") controladores.get(id)?.abort();
      else if (item.estado === "fila") {
        item.estado = "cancelado";
        this._bombear();
      }
    },

    cancelarTudo() {
      this.itens.forEach((i) => {
        if (i.estado === "fila") i.estado = "cancelado";
      });
      controladores.forEach((c) => c.abort());
    },

    tentarDeNovo(id) {
      const item = this.itens.find((i) => i.id === id);
      if (!item || !["erro", "cancelado"].includes(item.estado)) return;
      item.estado = "fila";
      item.erro = "";
      clearTimeout(timerFechar);
      this._bombear();
    },

    _anunciar() {
      if (!this.total) return;
      const marco = Math.floor((this.concluidos / this.total) * 4); // a cada 25% do lote
      if (marco > this._marcoAnunciado && marco < 4) {
        this._marcoAnunciado = marco;
        this.anuncio = `${this.concluidos} de ${this.total} enviados.`;
      }
    },

    _aoTerminar() {
      const ok = this.concluidos;
      const fotos = this.itens.filter((i) => i.tipo === "foto" && i.estado === "concluido").length;
      const videos = this.itens.filter((i) => i.tipo === "video" && i.estado === "concluido").length;
      this.anuncio = ok ? `${ok} ${ok === 1 ? "arquivo enviado" : "arquivos enviados"}.` : "Envio finalizado.";
      if (fotos) fotoCriada.value++;
      if (videos) videoCriado.value++;
      clearTimeout(timerFechar);
      if (!this.comErro) timerFechar = setTimeout(() => this.fechar(), 6000);
    },

    fechar() {
      if (this.ativos) return;
      this.limpar();
    },

    limpar() {
      clearTimeout(timerFechar);
      this.itens.forEach((i) => i.preview && URL.revokeObjectURL(i.preview));
      this.itens = [];
      this.aberto = false;
      this._marcoAnunciado = 0;
    },
  },
});
