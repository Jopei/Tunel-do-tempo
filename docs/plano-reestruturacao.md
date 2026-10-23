# Plano de reestruturação — Túnel do Tempo

Objetivo: reestruturar o site de memórias mantendo o design e a ideia atuais (tokens `--tm-*`, `useTema`, fontes Fraunces/Inter).

## Áreas
1. **Fotos (estilo Google Fotos)** — `/galeria-fotos` (nome `fotos`), `/fotos/albuns`, `/fotos/albuns/:uuid`. Grade justificada agrupada por data (`COALESCE(data_foto, DATE(created_at))`), linha do tempo lateral, seleção múltipla (shift-click) com ações em lote, álbuns, lightbox com painel de detalhes e atalhos, upload múltiplo com progresso, miniaturas geradas no upload.
2. **Vídeos (estilo Google Drive)** — `/galeria-videos?pasta=<uuid>` (nome `videos`), `/videos/:uuid` (player). Pastas em árvore, breadcrumb, grade/lista (preferência em localStorage), mover para pasta, preview.
3. **Músicas** — `/musicas` (login). Biblioteca + player persistente (`stores/player.js`, `PlayerBarra.vue` em `App.vue` fora do `router-view`). Áudio só para logados via **URL assinada temporária** (`URL::temporarySignedRoute`), já que `<audio>` não envia headers.
4. **Histórias** — mantém contrato; só ajuste visual/reuso.
5. **Home** — "watch page" do YouTube ~2008-2010: canal "Zé Paulo", vídeo `/videos/Abertura.mp4` "Tudo começa aqui", Inscrever-se decorativo, visualizações, estrelas, relacionados = histórias recentes (`/public/historias/recentes` ou histórias se logado), comentários = lembranças. CSS escopado, sem mexer em `tokens.css` global. Sem backend novo.
6. **A Rua (órbita) + páginas de membros** — `RuaOrbita.vue` recebe os 13 membros de `/public/membros`; central sorteada a cada visita; 12 satélites em anéis calculados por JS; clique → `transicionarPara(router, '/membros/<slug>', img)`.
   - **Páginas de membro são codadas à mão pelo ADM depois** (não há editor de perfil nem tema no banco). Estrutura: `frontend/src/membros/registry.js` mapeia `slug` → `{ componente (lazy), simbolo, cores }`; `MembroView.vue` resolve o slug, carrega `GET /membros/{slug}` e renderiza o componente do membro ou `MembroPaginaPadrao.vue` como fallback. Base reutilizável `MembroPagina.vue` (casca com tema via CSS vars, símbolo, história pessoal, mídia) que cada página personalizada estende/usa via slots.
   - O amigo homenageado ("Aquele que sonhou") é um dos 13; `/aquele-que-sonhou` passa a redirecionar para a página dele (ou a página dele reaproveita a homenagem atual).
   - Todos os 13 já têm conta.

## Dados (migrations aditivas, com `down()`)
- `usuarios.slug` string(60) nullable unique (definido por seeder/artisan; usado nas URLs `/membros/:slug` e no registry). Comando `php artisan membros:gerar-slugs` (slug do nome, sem sobrescrever existentes).
- `fotos`: `data_foto` date nullable (index), `largura`, `altura` uint nullable, `thumbnail_path` nullable. Comando artisan para gerar miniaturas das existentes.
- `albuns` (id, uuid, titulo, descricao, capa_foto_id, usuario_cadastrou_id, timestamps, softDeletes) + `album_fotos` (album_id, foto_id, ordem, unique).
- `video_pastas` (id, uuid, nome, pai_id self-FK, usuario_cadastrou_id, timestamps, softDeletes, unique(pai_id,nome)); `videos.pasta_id` nullable, `duracao_segundos`, `tamanho_bytes`.
- `musicas`: `artista`, `album`, `duracao_segundos`, `capa_foto_id`, `ano` nullable.

## API (base `/api`, X-API-KEY + Sanctum; LOGIN=qualquer logado, M=ADM+RUA)
Fotos: `GET /fotos` + `agrupar`, `album_uuid`, `de`, `ate`; item + `data_foto,largura,altura,thumbnail_url`. `GET /fotos/linha-do-tempo` → `[{data:"2024-05",total}]`. `PUT /fotos/{uuid}` aceita `data_foto`. `POST /fotos/lote-excluir` (M) `{uuids}` máx 100.
Álbuns: `GET/POST /albuns`, `GET/PUT/DELETE /albuns/{uuid}`, `POST|DELETE /albuns/{uuid}/fotos` `{fotos:[uuid]}`.
Vídeos: `GET /videos` + `pasta_uuid`, `todas=1`; item + `pasta_uuid,duracao_segundos`. `GET /video-pastas?pai_uuid=` → `{pasta_atual,caminho,pastas:[{uuid,nome,videos_count}]}`. `POST/PUT/DELETE /video-pastas[/{uuid}]` (409 se não vazia, 422 ciclo/nome duplicado). `PATCH /videos/{uuid}/pasta`.
Músicas: `GET /musicas` (q, ordem, page, per_page) item `{uuid,titulo,artista,album,duracao_segundos,capa_url,url}` onde `url` é assinada temporária; `PUT/DELETE /musicas/{uuid}` (M); `POST /cadastrar/musicas` aceita `artista,album,capa`.
Membros: `GET /public/membros` item + `slug`. `GET /membros/{slug}` (LOGIN) → `{uuid,slug,nome,foto_url,descricao,historias:[resumo],historia_pessoal:{...}|null}` (história pessoal = Historia tipo `Pessoal` vinculada ao membro).
Correções: `PUT /fotos/{uuid}` só no grupo M (remove duplicata do grupo login); renomear segundo `usuarios.foto.perfil.store` para `usuarios.foto.perfil.atualizar`. Remover `services/orbita.service.js` (morto).

## Regras
- Nenhuma resposta existente muda de forma incompatível.
- Animações respeitam `prefereMenosMovimento()` de `useTema`.
- Critério: `php artisan test`, `route:cache`, `npm run build`, `npm run lint` passam.
