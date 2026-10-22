import { ref, onMounted, onBeforeUnmount } from "vue";

const temArquivos = (e) => Array.from(e.dataTransfer?.types ?? []).includes("Files");

/**
 * Soltar arquivos em qualquer lugar da página (overlay de tela cheia).
 * Ignora arrastos internos (sem "Files"). aoSoltar(File[], evento).
 */
export function useDropArquivos(aoSoltar, { ativo = () => true } = {}) {
  const arrastando = ref(false);
  let nivel = 0;

  const entrar = (e) => {
    if (!ativo() || !temArquivos(e)) return;
    nivel++;
    arrastando.value = true;
  };
  const sair = (e) => {
    if (!temArquivos(e)) return;
    nivel = Math.max(0, nivel - 1);
    if (!nivel) arrastando.value = false;
  };
  const sobre = (e) => {
    if (!ativo() || !temArquivos(e)) return;
    e.preventDefault();
  };
  const soltar = (e) => {
    nivel = 0;
    arrastando.value = false;
    if (!temArquivos(e) || e.tunelTratado) return;
    e.preventDefault();
    if (!ativo()) return;
    const arquivos = Array.from(e.dataTransfer.files || []);
    if (arquivos.length) aoSoltar(arquivos, e);
  };

  onMounted(() => {
    window.addEventListener("dragenter", entrar);
    window.addEventListener("dragleave", sair);
    window.addEventListener("dragover", sobre);
    window.addEventListener("drop", soltar);
  });
  onBeforeUnmount(() => {
    window.removeEventListener("dragenter", entrar);
    window.removeEventListener("dragleave", sair);
    window.removeEventListener("dragover", sobre);
    window.removeEventListener("drop", soltar);
  });

  return { arrastando };
}
