import DOMPurify from "dompurify";

// Links externos sempre com rel seguro
DOMPurify.addHook("afterSanitizeAttributes", (node) => {
  if (node.tagName === "A" && node.getAttribute("target") === "_blank") {
    node.setAttribute("rel", "noopener noreferrer");
  }
  if (node.tagName === "IMG") {
    node.setAttribute("loading", "lazy");
  }
});

/**
 * Quill 2 emite listas como <ol><li data-list="bullet|ordered|checked">.
 * Converte para <ul>/<ol> reais e remove os marcadores internos (.ql-ui).
 */
export function normalizarListasQuill(html) {
  if (!html || !html.includes("data-list")) return html ?? "";
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, "text/html");
  doc.querySelectorAll(".ql-ui").forEach((n) => n.remove());
  doc.querySelectorAll("ol, ul").forEach((lista) => {
    const itens = Array.from(lista.children).filter((c) => c.tagName === "LI");
    if (!itens.length) return;
    const bullet = itens.every((li) => ["bullet", "checked", "unchecked"].includes(li.getAttribute("data-list")));
    if (bullet && lista.tagName === "OL") {
      const ul = doc.createElement("ul");
      ul.innerHTML = lista.innerHTML;
      lista.replaceWith(ul);
    }
  });
  return doc.body.firstElementChild.innerHTML;
}

export function sanitizar(html) {
  const limpo = DOMPurify.sanitize(html ?? "", {
    USE_PROFILES: { html: true },
    ADD_ATTR: ["target", "class", "data-list"],
  });
  return normalizarListasQuill(limpo);
}
