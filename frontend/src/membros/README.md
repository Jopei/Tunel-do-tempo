# Páginas de membros (A Rua)

Rota `/membros/:slug` -> `views/MembroView.vue` busca `GET /membros/{slug}` e renderiza:
o componente do `registry.js` (se houver) ou `MembroPaginaPadrao.vue`.

Para criar a página de um membro:
1. Copie `paginas/_Exemplo.vue` para `paginas/<slug>.vue` e edite (slots e variáveis `--membro-*`).
2. Registre em `registry.js` com `componente` (lazy), `simbolo` e `cores`.
3. Assets (símbolo, fotos) em `frontend/public/membros/<slug>/`.

Sem entrada no registry o membro usa a página padrão (foto, nome, descrição, história pessoal, histórias).
`redirect: "/aquele-que-sonhou"` no registry faz o homenageado cair na página de homenagem.
O `slug` vem de `usuarios.slug` (backend: `php artisan membros:gerar-slugs`).

Contrato das props recebidas pelo componente: `membro`, `tema`, `simbolo`, `anterior`, `proximo`.
Detalhes de regiões/slots/tema: `docs/ui-ux-reestruturacao.md` seção 6.
