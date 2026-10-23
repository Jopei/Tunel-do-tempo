import { REDE } from "@/config/esquina";

// Valores "de época" da home. Fixos de propósito: não ligar a contadores reais.
export const homeConfig = {
  videoSrc: "/videos/Abertura.mp4",
  poster: "/videos/Abertura-poster.webp",
  poster640: "/videos/Abertura-poster-640.webp",
  titulo: "Tudo começa aqui",
  canal: { nome: "Zé Paulo", rotulo: "Canal do Zé Paulo", avatar: "/avatar-ze-paulo.png" },
  visualizacoes: 12847,
  duracaoRotulo: "0:15",
  duracaoSegundos: 15.43, // medida do arquivo Abertura.mp4; usada até o metadado chegar
  anoVideo: 2011,
  publicadoEm: "2011-03-12",
  publicadoEmTexto: "12 de março de 2011",
  carimbo: "12 03 2011",
  descricao:
    "Antes de qualquer história, existia a rua. Este vídeo abre o túnel: tudo o que a gente viveu junto está guardado do outro lado.",
  // Chaves de sessionStorage e janela da intro (ms) em um lugar só, para o motion ajustar.
  introChave: "tm-home-intro",
  introCompletaMs: 6000,
  introCurtaMs: 800,
};

// Lembranças de época (conteúdo fixo, não interativo).
export const lembrancas = [
  { id: 1, nome: "Gabriel", data: "março de 2011", texto: "Eu lembro desse dia como se fosse ontem. A rua inteira estava lá." },
  { id: 2, nome: "Igor", data: "março de 2011", texto: "Quem diria que a gente ia guardar tudo isso. Valeu, Zé!" },
  { id: 3, nome: "Ruan", data: "abril de 2011", texto: "Só de ver esse vídeo já dá vontade de voltar pra esquina." },
];

// As quatro portas ("Continue assistindo"). `trava` = exige login.
export const portas = [
  { id: "historias", titulo: REDE.nome, sub: `${REDE.anoInicio}-${REDE.anoFim}`, icone: "mdi-book-open-page-variant", para: "/historias", trava: true },
  { id: "rua", titulo: "A Rua", sub: "Quem é quem", icone: "mdi-home-group", para: "/a-rua", trava: false },
  { id: "fotos", titulo: "Fotos da turma", sub: "Álbuns e galeria", icone: "mdi-image-multiple", para: "/fotos/albuns", trava: false },
  { id: "videos", titulo: "Mais vídeos do Zé Paulo", sub: "Galeria de vídeos", icone: "mdi-play-box-multiple", para: "/galeria-videos", trava: true },
];
