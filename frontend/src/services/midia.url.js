// URLs diretas das mídias públicas (o navegador carrega em paralelo, com cache e Range).
// Músicas exigem login e continuam via services/musica.show.service.js (blob).
function base() {
  return (import.meta.env.VITE_API_BASE_URL || "").replace(/\/$/, "");
}

export const urlFoto = (uuid) => `${base()}/fotos/${uuid}`;
export const urlVideo = (uuid) => `${base()}/videos/${uuid}`;
