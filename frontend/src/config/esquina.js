// Configuração única da "rede social" fictícia das histórias. Trocar o nome aqui basta.
export const REDE = {
  nome: "Histórias da Rua R",
  subtitulo: "A rede de quem viveu a Rua",
  anoInicio: 2011,
  anoFim: 2021,
  verbos: { curtir: "Curtir", curtido: "Curtido", comentar: "Comentar", compartilhar: "Compartilhar" },
};

export const ANOS = Array.from({ length: REDE.anoFim - REDE.anoInicio + 1 }, (_, i) => REDE.anoInicio + i);
