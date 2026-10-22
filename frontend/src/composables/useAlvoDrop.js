import { ref } from "vue";

export const MIME_VIDEOS = "application/x-tunel-videos";

/**
 * Transforma um elemento em alvo de soltura (vídeos arrastados ou arquivos do computador).
 * aoSoltar({ videos: string[] } | { arquivos: File[] }). Classe de destaque: `.alvo-drop`
 * (use :class="{ 'alvo-drop': sobre }").
 */
export function useAlvoDrop(aoSoltar, { ativo = () => true } = {}) {
  const sobre = ref(false);
  let nivel = 0;

  const aceita = (e) => {
    const t = Array.from(e.dataTransfer?.types ?? []);
    return t.includes(MIME_VIDEOS) || t.includes("Files");
  };

  const handlers = {
    onDragenter(e) {
      if (!ativo() || !aceita(e)) return;
      e.preventDefault();
      nivel++;
      sobre.value = true;
    },
    onDragover(e) {
      if (!ativo() || !aceita(e)) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = Array.from(e.dataTransfer.types).includes("Files") ? "copy" : "move";
    },
    onDragleave() {
      nivel = Math.max(0, nivel - 1);
      if (!nivel) sobre.value = false;
    },
    onDrop(e) {
      nivel = 0;
      sobre.value = false;
      if (!ativo() || !aceita(e)) return;
      e.preventDefault();
      e.tunelTratado = true; // o overlay global de upload ignora este drop
      const bruto = e.dataTransfer.getData(MIME_VIDEOS);
      if (bruto) {
        try {
          aoSoltar({ videos: JSON.parse(bruto) });
        } catch {
          /* dado inválido: ignora */
        }
      } else if (e.dataTransfer.files?.length) {
        aoSoltar({ arquivos: Array.from(e.dataTransfer.files) });
      }
    },
  };

  return { sobre, handlers };
}
