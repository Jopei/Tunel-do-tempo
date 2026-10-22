/**
 * Registro das páginas de membros (a Rua).
 *
 * Chave = slug do membro (usuarios.slug). Todo membro SEM entrada aqui usa
 * MembroPaginaPadrao automaticamente, então só cadastre quem tiver página própria
 * ou símbolo/cores. Veja README.md nesta pasta.
 *
 * Campos (todos opcionais):
 *  - componente: () => import("./paginas/MinhaPagina.vue")   (lazy; recebe a prop `membro`)
 *  - simbolo:    url de SVG/PNG ("/membros/ze/simbolo.svg"), "mdi-star" ou texto/emoji curto
 *  - cores:      { cor, texto, hero } -> viram --membro-cor, --membro-cor-texto, --membro-hero-fundo
 *  - esfera:     imagem própria da esfera na órbita (tem prioridade sobre a foto de perfil)
 *  - redirect:   caminho interno; MembroView redireciona (ex.: o homenageado -> "/aquele-que-sonhou")
 */
export const registry = {
  // Italo, o homenageado: a página dele é a homenagem "Aquele que sonhou".
  italo: { redirect: "/aquele-que-sonhou", esfera: "/imgs/1.svg" },
  "joao-pedro": {
    componente: () => import("./paginas/JoaoPedro.vue"),
    simbolo: "/membros/joao-pedro/simbolo.svg",
    cores: { cor: "#C8101A", texto: "#8E0A12", hero: "linear-gradient(160deg, #C8101A, #6E060C)" },
  },
  // "ze-paulo": {
  //   componente: () => import("./paginas/ZePaulo.vue"),
  //   simbolo: "mdi-hammer-wrench",
  //   cores: { cor: "#C7A43A", texto: "#7A5D0E", hero: "linear-gradient(160deg, #3a2b0b, #14110B)" },
  // },
};

export function entradaDoMembro(slug) {
  return registry[slug] ?? null;
}

/** Ordem cíclica para "Anterior / Próximo": slugs do registry primeiro; o resto vem da lista pública. */
export function slugsRegistrados() {
  return Object.keys(registry);
}
