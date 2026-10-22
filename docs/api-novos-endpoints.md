# API: endpoints novos e alterados (spec 4.B)

Base: `/api`. Headers comuns: `Accept: application/json`, `X-API-KEY: <chave>` (obrigatório em tudo que está dentro do grupo `api.key.auth`, exceto onde indicado "público"), `Authorization: Bearer <token>` quando "login".
Papéis: ADM = `Membro ADM`, RUA = `Membro Rua`, REVISOR = `Revisor`. Erros padrão: 401 sem login/chave, 403 sem permissão, 404 recurso inexistente, 422 validação (`{message, errors:{campo:[...]}}`), 429 rate limit.
Paginação (onde indicado): resposta Laravel `{data:[...], links:{first,last,prev,next}, meta:{current_page,last_page,per_page,total,...}}`.

Nenhum formato existente foi quebrado: só foram adicionados campos, e os parâmetros novos são opcionais (detalhes por endpoint).

---
## B1. `GET /historias/resumo` (alterado) - login
Query opcionais: `q` (título/descrição curta), `tipo` (`Pessoal|Principal|Paralela`), `usuario` (uuid), `page`, `per_page` (1-100, padrão 15).
- Sem `page` e sem `per_page`: devolve a **lista completa (array)**, como antes.
- Com `page` ou `per_page`: devolve paginado `{data, links, meta}`.
Item (campos novos marcados com *):
```json
{ "uuid":"...", "titulo":"...", "descricao_curta":"...", "tipo_historia":"Principal",
  "data_historia":"2015-06-01",  "fotos_count":3, "videos_count":1, "musicas_count":0,
  "usuarios":[{"uuid":"...","nome":"..."}] }
```
`data_historia`, `fotos_count`, `videos_count`, `musicas_count` são novos (também em `GET /historias/{uuid}/resumo`, `PUT /historias/{uuid}` e nos itens de `/me/favoritos`). Contagens ignoram mídia excluída. Ordenação: `data_historia` desc.

## B2. `GET /historias/{uuid}` (alterado) - login
Mantidos: `fotos`, `videos`, `musicas` (arrays de uuid). Novos (com URL pronta, elimina o N+1):
```json
"fotos_midia":  [{"uuid":"..","url":"https://api/.../api/fotos/{uuid}","titulo":"..","descricao":".."}],
"videos_midia": [{"uuid":"..","url":"https://api/.../api/videos/{uuid}","poster_url":"https://.../storage/x.jpg|null","titulo":".."}],
"musicas_midia":[{"uuid":"..","url":"https://api/.../api/musicas/{uuid}","titulo":".."}],
"lembrancas_count": 4,
"favoritado": false
```
Notas: mídia excluída (soft delete) deixou de aparecer também nos arrays de uuid. `url` de foto e vídeo é pública (o vídeo pode redirecionar para link externo). `url` de música continua exigindo login + `X-API-KEY` (rota existente `GET /musicas/{uuid}`): `<audio src>` não envia headers, então baixe como blob ou peça para tornarmos pública. `poster_url` vem do thumbnail atual; `duracao` depende do B4 (fora de escopo, não incluída).

## B3. Filtros nas galerias (alterado)
`GET /fotos` (público, sem API key como hoje) e `GET /videos` (**login**, mantido). Query opcionais:
`q` (título/descrição), `ano` (ano de `created_at`, 1900-2100), `usuario_uuid` (uuid de pessoa vinculada), `ordem` (`recentes` padrão | `antigas` | `titulo`), `page`, `per_page` (1-100, padrão 15; antes sem teto).
Resposta: a mesma coleção paginada de antes. `ordem` ou `per_page` inválidos retornam 422.

## B5. `GET /me` (novo) - login (qualquer papel)
Resposta (mesmo `UsuarioAuthResource` do login, agora com campos extras):
```json
{ "uuid":"..","nome":"..","email":"..","telefone":"11999999999|null","aniversario":"1990-01-01T00:00:00.000000Z","descricao":"..|null",
  "tipo_usuario":"Membro Rua","foto_perfil":"usuarios/fotos/x.jpg|null" }
```
`telefone`, `aniversario`, `descricao` também passam a vir na resposta de `POST /login`.

## B6. Papéis no servidor (alterado)
- `POST /notificacoes`: só ADM (403 para os demais).
- `PUT /historias/{uuid}` e `DELETE /historias/{uuid}`: ADM e RUA, ou qualquer usuário vinculado à história (campo `usuarios`, tratado como autor); demais 403. Antes, inexistente retornava 500; agora 404.
- `DELETE /lembrancas/{uuid}`: autor ou ADM.
- `GET /videos`: continua exigindo login.
- Atualizações (`/atualizacoes`): sem mudança (PUT já limita ao autor).

## B7. Esqueci / redefinir senha - público (exige `X-API-KEY`)
`POST /esqueci-senha` body `{ "email": "a@b.com" }` -> **200 sempre** (mesmo se o e-mail não existe):
`{"message":"Se o e-mail estiver cadastrado, enviaremos as instruções para redefinir a senha."}`. 422 só para e-mail malformado.
E-mail enviado com link `${FRONTEND_URL}/redefinir-senha?token=...&email=...`. Em local, `MAIL_MAILER=log` grava o e-mail em `storage/logs/laravel.log`.
`POST /redefinir-senha` body `{ "token":"...", "email":"...", "senha":"min 8" }` -> 200 `{"message":"Senha redefinida com sucesso."}`; 422 `{"message":"Link inválido ou expirado..."}` se token errado, expirado (60 min) ou já usado. Ao redefinir, todos os tokens de sessão do usuário são revogados.
Rate limit (429): 5 req/min por IP e 10/hora por e-mail, compartilhado entre as duas rotas. Token guardado como hash (sha256), uso único.

## B8. `GET /public/membros` (novo) - público, sem API key
`[{ "uuid":"..","nome":"..","foto_url":"https://.../api/fotos/{uuid}|null" }]` - apenas ADM e RUA, ordenados por nome; sem e-mail/telefone. `foto_url` só considera fotos do tipo "Perfil".

## B9. Central de notificações
- `GET /notificacoes` (alterado, login). Sem `status`/`page`/`per_page`: formato original (array das não lidas, objetos do model). Com algum deles: paginado, `status=todas|nao_lidas` (padrão `todas`), `per_page` 1-50. Item: `{id,uuid,titulo,descricao,tema,link,created_at,lida:boolean}`.
- `GET /notificacoes/contagem` (novo, login) -> `{"nao_lidas": 3}`.
- `DELETE /notificacoes/{id}` (novo, **só ADM**): exclui a notificação para todos (soft delete) -> 200 `{message}`. Não existe exclusão "só para mim" (exigiria nova coluna/tabela; ver pendências).
- `GET /admin/notificacoes` (novo, **só ADM**): enviadas, paginado (`per_page` 1-50). Item: `{id,uuid,titulo,descricao,tema,link,created_at,autor:{uuid,nome}|null,lidas_count}`.
- Inalterados: `POST /notificacoes/{id}/marcar`, `POST /notificacoes/marcar-todas`.

## B10. Lembranças - login (qualquer papel)
- `GET /historias/{uuid}/lembrancas?page=&per_page=` (per_page 1-50, padrão 15) - paginado, mais recentes primeiro.
- `POST /historias/{uuid}/lembrancas` body `{"texto":"2-2000 caracteres"}` -> 201 com o item. Texto é salvo como texto puro (tags removidas). Limite 20 req/min.
- `GET /homenagem/lembrancas` e `POST /homenagem/lembrancas` - mesmo contrato, para a página da homenagem (contexto `homenagem`, sem história).
- `DELETE /lembrancas/{uuid}` -> 204; 403 se não for autor nem ADM.
Item: `{ "uuid","texto","contexto":"historia|homenagem","created_at","usuario":{"uuid","nome"}, "pode_excluir": true|false }`.

## B11. Favoritos - login
- `POST /historias/{uuid}/favorito` -> 200 `{"favoritado":true}` (idempotente).
- `DELETE /historias/{uuid}/favorito` -> 200 `{"favoritado":false}` (idempotente).
- `GET /me/favoritos?page=&per_page=` -> paginado com itens no formato de resumo da história (B1), mais recentes primeiro por data da história.
- `GET /historias/{uuid}` traz `favoritado` (ver B2).

## B13. `GET /busca?q=` (novo) - login, 60 req/min
`q` 2-100 caracteres (422 se menor). Máximo 3 itens por grupo:
```json
{ "historias":[{"uuid","titulo","descricao_curta","tipo_historia","data_historia"}],
  "fotos":[{"uuid","titulo","url"}],
  "videos":[{"uuid","titulo","url","thumbnail"}],
  "membros":[{"uuid","nome","foto_url"}] }
```

## B14. Sanitização de HTML (comportamento)
Aplicada no servidor na gravação (perfil `conteudo` em `config/purifier.php`: p, br, hr, div, span, b/strong, i/em, u, s, blockquote, h1-h6, ul/ol/li, a[href|title|target], img[src|alt|width|height]; esquemas http/https/mailto/tel):
- `conteudo` de história (`POST /cadastrar/historias`), `descricao` de notificação, `conteudo_markdown` de atualização (script/handlers removidos; `>` de citações preservado).
- Lembrança: texto puro.
O frontend deve continuar usando DOMPurify. Conteúdo já gravado não é migrado.

---
## Variáveis de ambiente novas (`backend/.env.example`)
`FRONTEND_URL`, `MAIL_MAILER` (`log` em local), `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

---
# Rodada 2

## `GET /usuarios` (alterado) - login
Itens agora: `{uuid, nome, aniversario, descricao, foto_url|null}`; `email` e `telefone` só aparecem se o usuário logado for ADM. `foto_url` = foto de perfil (tipo "Perfil") ou null.

## `GET /public/historias/recentes?limite=3` (novo) - público, sem API key
`limite` 1-10 (padrão 3). Ordenado por `data_historia` desc.
`[{ "uuid","titulo","descricao_curta" (texto puro, máx. 160 chars),"data_historia":"YYYY-MM-DD","capa_url": "…/api/fotos/{uuid}"|null }]`
O `uuid` é exposto de propósito: permite redirecionar para `/historias/{uuid}` após o login (não é adivinhável; o conteúdo continua protegido). `capa_url` = primeira foto não excluída da história.

## `GET /fotos` e `GET /videos` (alterado)
`meta.total` já vem da paginação padrão. Fotos: novo `thumbnail_url` (sempre `null` por ora, miniaturas dependem do B4; use `url`). Vídeos: novo `poster_url` (igual a `thumbnail`, ou null).

## `GET /musicas/{uuid}` (sem mudança) - login + API key
Mantida protegida. Serve o arquivo via `response()->file` (BinaryFileResponse): `Content-Type` pela extensão (ex. `audio/mpeg`) e suporte a `Range`/`206`. Não testado em execução (sem driver de banco no host).

---
# Rodada 3 (correções da revisão)
- Sanitização: listas do Quill 2 (`<li data-list>`) viram `<ul>`/`<ol>` reais (listas mistas são divididas em blocos); `data-list` removido. Classes preservadas: `ql-align-center|right|justify` e `ql-indent-1..8`; demais classes são removidas.
- `POST /esqueci-senha`: consulta, token e e-mail rodam após a resposta (mesma resposta/tempo para e-mail existente ou não).
- `PUT /usuarios/{uuid}`: `email` agora é único (422 se outro usuário, inclusive excluído, já usa). O próprio e-mail do usuário é aceito.
- `GET /usuarios/{uuid}`: `email` e `telefone` só vêm para o ADM ou o próprio usuário.
- `POST /notificacoes`: `link` aceita só `/rota-interna` (não `//`) ou URL `http(s)://`; senão 422.
- `GET /public/historias/recentes`: exclui histórias do tipo "Pessoal".

---
# Rodada 4 (reestruturação: fotos, álbuns, vídeos/pastas, músicas, membros)
Papéis: LOGIN = qualquer usuário logado (X-API-KEY + Bearer); M = ADM e RUA (REVISOR recebe 403). 404 = recurso inexistente/excluído; 422 = validação.
Tudo que existia continua igual: só campos novos e parâmetros opcionais.

## Fotos
- `GET /fotos` (público, como antes). Novos parâmetros opcionais: `agrupar=data` (ordena pela data de referência `COALESCE(data_foto, DATE(created_at))`, respeitando `ordem=recentes|antigas`; o agrupamento visual é do frontend), `album_uuid`, `de`/`ate` (`YYYY-MM-DD`, sobre a data de referência; `ate >= de`). Item (e `GET /fotos/{uuid}/details`) ganha `data_foto` (`YYYY-MM-DD|null`), `largura`, `altura` (`int|null`) e `thumbnail_url` (agora real: miniatura JPEG de até 480 px, ou `null` se não gerada; use `thumbnail_url ?? url`).
- `GET /fotos/linha-do-tempo` (público, igual ao `GET /fotos`): `[{"data":"2024-05","total":12}]`, mais recente primeiro, por mês da data de referência. Aceita os mesmos filtros (`q`, `ano`, `usuario_uuid`, `album_uuid`, `de`, `ate`); ignora `page/per_page/ordem`.
- `PUT /fotos/{uuid}` (M, 404 se não existe): além de `titulo`/`descricao`, aceita `data_foto` (`YYYY-MM-DD`; enviar `null` limpa; omitir não altera).
- `POST /fotos` (M): aceita `data_foto` opcional. Sem ela, usa a data EXIF (DateTimeOriginal) quando houver. Preenche `largura`/`altura` e gera a miniatura (esta exige a extensão PHP GD no servidor).
- `POST /fotos/lote-excluir` (M) body `{"uuids":["..."]}` (1-100, uuids): `200 {"message","excluidas":2,"nao_encontradas":["uuid"]}`. Mesma regra do `DELETE /fotos/{uuid}` (apaga arquivo e miniatura; registro em soft delete).
- Correção: `PUT /fotos/{uuid}` existia duplicado e só vale no grupo M. Rota `POST /usuarios/foto-perfil` passou a se chamar `usuarios.foto.perfil.atualizar` (nome de rota, sem efeito na URL).

## Álbuns (GET = LOGIN; escrita = M)
Item: `{uuid,titulo,descricao,fotos_count,capa:{uuid,url,thumbnail_url}|null,created_at,updated_at}`. `capa` = foto de capa escolhida ou, na falta, a primeira do álbum. Fotos excluídas não contam.
- `GET /albuns?q=&page=&per_page=` (per_page 1-100, padrão 24): paginado `{data,links,meta}`, mais recentes primeiro.
- `GET /albuns/{uuid}` -> `{data:{...item}}`. As fotos do álbum vêm de `GET /fotos?album_uuid=`.
- `POST /albuns` `{titulo*, descricao, capa_foto_uuid, fotos:[uuid]}` -> 201 `{data}`.
- `PUT /albuns/{uuid}` `{titulo, descricao, capa_foto_uuid}` (omitido = não altera; `capa_foto_uuid:null` remove a capa) -> `{data}`.
- `DELETE /albuns/{uuid}` -> 200 `{message}` (as fotos não são afetadas).
- `POST /albuns/{uuid}/fotos` e `DELETE /albuns/{uuid}/fotos` body `{"fotos":["uuid"]}` (1-500) -> `{data}` atualizado. Idempotentes (adicionar já existente / remover ausente não dá erro). Adicionadas ficam no fim (`ordem` crescente). Remover a foto de capa zera a capa. Uuid de foto inexistente -> 422.

## Vídeos e pastas
- `GET /videos` (LOGIN, como antes): novos opcionais `pasta_uuid` (uuid da pasta, ou o literal `raiz` = vídeos sem pasta) e `todas=1` (ignora `pasta_uuid`). **Sem nenhum dos dois devolve todos os vídeos, como antes** (divergência do plano: o filtro de raiz é `pasta_uuid=raiz`). Pasta inexistente -> 422. Item ganha `pasta_uuid|null`, `duracao_segundos|null`, `tamanho_bytes|null`.
- `GET /video-pastas?pai_uuid=` (LOGIN; sem `pai_uuid` = raiz; pai inexistente 404): `{"pasta_atual":{uuid,nome}|null,"caminho":[{uuid,nome}],"pastas":[{uuid,nome,pai_uuid,videos_count}]}`. `caminho` vai da raiz até a pasta atual (inclusive), vazio na raiz. `pastas` = subpastas diretas, por nome; `videos_count` = vídeos diretamente na pasta.
- `POST /video-pastas` (M) `{nome*, pai_uuid}` -> 201 `{data:{uuid,nome,pai_uuid,videos_count}}`. 422 se já existe pasta com o mesmo nome no mesmo local (inclusive na raiz).
- `PUT /video-pastas/{uuid}` (M) `{nome, pai_uuid}` (`pai_uuid:null` move para a raiz) -> `{data}`. 422: nome duplicado no destino; mover para si mesma ou para uma subpasta (ciclo, erro em `pai_uuid`).
- `DELETE /video-pastas/{uuid}` (M): 200; **409** `{message}` se tiver vídeos (não excluídos) ou subpastas.
- `PATCH /videos/{uuid}/pasta` (M) `{"pasta_uuid": "uuid"|null}` (chave obrigatória; null = raiz) -> `{message, pasta_uuid}`. Vídeo inexistente 404; pasta inexistente 422.

## Músicas
- `GET /musicas?q=&ordem=&page=&per_page=` (LOGIN; `ordem` = `recentes` padrão|`antigas`|`titulo`|`artista`; per_page 1-100, padrão 24): paginado, item `{uuid,titulo,artista,album,ano,duracao_segundos,capa_url,url,url_expira_em}`. `url` é assinada e temporária (2 h; `url_expira_em` em ISO 8601): vá direto em `<audio src>`, **sem X-API-KEY nem login**. Cada listagem gera URLs novas; ao expirar (403) recarregue a música/lista. Suporta `Range` (206), então seek funciona. `capa_url` = `/api/fotos/{uuid}` da capa ou null.
- Stream: `GET /musicas/{uuid}/stream?expires=..&signature=..` (só via URL assinada; assinatura inválida/expirada -> 403; música ou arquivo inexistente -> 404). `GET /musicas/{uuid}` (LOGIN + API key) continua como antes.
- `PUT /musicas/{uuid}` (M) `{titulo, artista, album, ano(1900-2100), duracao_segundos, capa_foto_uuid}` (omitido = não altera; `capa_foto_uuid:null` remove) -> `{data:{...item}}`.
- `DELETE /musicas/{uuid}` (M) -> 200 `{message}` (soft delete; arquivo mantido).
- `POST /cadastrar/musicas` (M, multipart) aceita também `artista`, `album`, `ano`, `duracao_segundos`, `capa` (imagem jpg/png até 4 MB; vira uma foto do tipo "Tumb" e é usada como capa) ou `capa_foto_uuid` (foto existente). Corrigido bug anterior: o cadastro de música falhava por `path` não gravado.

## Membros
- `GET /public/membros` (público): item ganha `slug` (`string|null`).
- `GET /membros/{slug}` (LOGIN; slug inexistente ou de quem não é ADM/RUA -> 404):
```json
{ "uuid":"..","slug":"ana","nome":"Ana","foto_url":"https://.../api/fotos/{uuid}|null","descricao":"..|null",
  "historias":[ {uuid,titulo,descricao_curta,tipo_historia,data_historia,fotos_count,videos_count,musicas_count,usuarios:[{uuid,nome}]} ],
  "historia_pessoal": { ...mesmo resumo..., "conteudo":"<p>html sanitizado</p>" } | null }
```
Fallback: se o membro ainda não tem slug, o `{slug}` pode ser o **uuid** dele (com slug definido, só o slug funciona).
`historias` = histórias em que o membro aparece, **exceto as do tipo Pessoal** (por data desc); `historia_pessoal` = a Historia tipo Pessoal vinculada ao membro (a mais recente, se houver mais de uma) ou `null`.

## Rodada 5 (adições retrocompatíveis)
- Item de **foto** e de **vídeo** (listagens e detalhes) ganha `cadastrado_por:{uuid,nome}|null`; vídeo ganha também `created_at` (ISO 8601; foto já tinha).
- `GET /fotos/{uuid}/details` (LOGIN desde a rodada 6) ganha `albuns:[{uuid,titulo}]` e `historias:[{uuid,titulo}]` (excluídos não entram). Só no detalhe, não na listagem.
- `GET /videos/{uuid}/detalhes` (LOGIN, 404 se não existe) -> `{data:{...item de GET /videos, incl. url, poster_url}}`. `GET /videos/{uuid}` continua devolvendo o arquivo/redirect.
- `GET /musicas/{uuid}/detalhes` (LOGIN, 404) -> `{data:{...item de GET /musicas}}` com `url` assinada nova e `url_expira_em` (use para renovar URL expirada). `GET /musicas/{uuid}` inalterado.
- `GET /musicas?ordem=album` (album, depois título).
- Confirmado por teste: `PUT /video-pastas/{uuid}` aceita só `{pai_uuid}` (nome preservado) e `PUT /videos/{uuid}` aceita só `{titulo}` (descrição preservada).

### Rodada 6 — correções (segurança e acesso)
**Mudanças de acesso (podem gerar 401/403 no frontend):**
- `POST /usuarios/foto-perfil`: só o próprio usuário (`usuario_uuid` = o logado) ou MEMBRO_ADM; outro caso -> **403**.
- `PUT/DELETE /videos/{uuid}` e `PATCH /fotos/{uuid}/usuarios`: agora só MEMBRO_ADM e MEMBRO_RUA (Revisor -> **403**).
- `GET /fotos/{uuid}/details`: agora exige LOGIN (anônimo -> **401**). Para anônimo use `GET /fotos` / `GET /fotos/{uuid}` (arquivo).
**Contrato de resposta:**
- `usuario_cadastrou_id` removido de todo item de foto. `cadastrado_por` só aparece quando há login (token Bearer enviado); para anônimo em `GET /fotos` a chave não existe (trate como ausente).
- `historias` em `GET /fotos/{uuid}/details` não inclui histórias do tipo Pessoal (nem excluídas).
- `GET /videos?ordem=` também aceita `titulo_desc`, `duracao`, `duracao_desc`, `tamanho`, `tamanho_desc` (nulos sempre por último; desempate por mais recente). Valor fora da lista -> 422.
- `GET /albuns`: `capa` sem N+1; capa excluída (soft delete) vira fallback para a primeira foto do álbum (ou `null` se vazio).
**Validações novas (422):** imagens (`imagem` de foto/perfil, `capa` de música, `thumbnail` de vídeo) até 12000 px por lado; `thumbnail` de vídeo até 4 MB; `external_link` de vídeo só `http`/`https` (máx. 2048); `descricao` de foto até 2000 (perfil: 1000).
**Sem mudança de contrato:** originais JPEG são regravados sem EXIF/GPS (data e orientação extraídas antes; `data_foto`, `largura`, `altura` seguem preenchidos); imagem acima de ~64 MP não gera miniatura (`thumbnail_url` null); falha no upload não deixa arquivo órfão; `PUT /fotos/{uuid}` não devolve mais a mensagem da exceção em erros 500; novas músicas ficam em disco privado (`storage/app/private`) e o stream assinado (com Range/206) continua igual.
**Comandos (rodar após o deploy):** `php artisan musicas:mover-para-privado` (idempotente; move os arquivos antigos de `public` para o privado; até rodar, o stream ainda lê do `public` como fallback) e `php artisan fotos:gerar-miniaturas --limpar-exif` (regrava os JPEG existentes sem metadados; exige GD).
**Pendência (database-specialist):** `videos.path` é NOT NULL, então cadastrar vídeo só com `external_link` (sem arquivo) falha com 400; e `fotos.usuario_cadastrou_id` é NOT NULL (a foto de perfil agora grava o usuário logado).

### Rodada 7 — feed de histórias (adições retrocompatíveis)
- `GET /historias/resumo` (lista completa e paginada) e `GET /historias/{uuid}/resumo` (também favoritas e demais usos do resumo) ganham: `capa_url` (primeira foto vinculada não excluída, preferindo miniatura; `null` sem foto), `favoritos_count`, `lembrancas_count`, `favoritado` (bool do usuário logado) e, em `usuarios[]`, `slug` e `foto_url` (foto de perfil mais recente ou `null`). Sem N+1.
- `GET /historias/resumo?ordem=asc|desc` (padrão `desc`, igual ao anterior), por `data_historia`, desempate por id; outro valor -> 422.
- `GET /historias/{uuid}` ganha: `capa_url`, `favoritos_count`, `usuarios[]` com `slug` e `foto_url`, `fotos_midia[]` com `data_foto` (`YYYY-MM-DD`|null) e `thumbnail_url`|null, e `anterior`/`proxima`: `{uuid,titulo,data_historia}|null` na ordem cronológica asc (exclui tipo Pessoal e histórias excluídas; desempate por id).

### Rodada 8 — fotos e vídeos v2 (adições retrocompatíveis)
**Migration nova:** `videos.data_video` (date, nullable, índice). Rodar `php artisan migrate`.
- **Vídeos:** `POST /cadastrar/videos` e `PUT /videos/{uuid}` aceitam `data_video` (`YYYY-MM-DD`; no PUT `null` limpa; omitido não altera; formato inválido -> 422). Item (`GET /videos`, `/videos/{uuid}/detalhes`) ganha `data_video` (`YYYY-MM-DD|null`).
- `GET /videos`: `ano` agora filtra pelo ano de `data_video` (antes: ano de `created_at`; vídeos sem data não entram); novo `sem_data=1` (só vídeos sem `data_video`); `ordem=data_video|data_video_desc` (sem data sempre por último).
- `GET /videos/anos` (LOGIN) -> `[{"ano":2015,"total":3},{"ano":null,"total":2}]` (ano desc; `null` = sem data, por último).
- `GET /fotos/anos` (PÚBLICO, só agregados) -> `[{"ano":2014,"total":12,"capa":{"thumbnail_url":"...|null","url":"..."}}]` por `COALESCE(data_foto, created_at)`, ano desc, ignora fotos de perfil. `capa` (novo, aditivo) = foto mais recente não excluída do ano (sem perfil), `null` se não houver; sem dados de autor; calculada em lote (sem N+1).
- `GET /fotos?ano=` passa a filtrar pelo mesmo critério (`COALESCE(data_foto, created_at)`), coerente com `/fotos/anos` e `/fotos/linha-do-tempo` (antes: só `created_at`).
- `GET /fotos/neste-dia?limite=24` (LOGIN; `limite` 1-60) -> `[{"ano":2019,"fotos":[<item de foto>]}]`: fotos cujo dia/mês de `COALESCE(data_foto, created_at)` é o de hoje, em anos anteriores ao atual; ano desc; `limite` vale para o total de fotos. Vazio -> `[]`.
- **Autoria** (só logado; item de foto e de vídeo): `cadastrado_por:{uuid,nome,slug,foto_url}|null` (`slug`/`foto_url` podem ser `null`).
- **Marcados** (só logado; item de foto, inclusive em `/fotos`, `/fotos/neste-dia` e details): `marcados:[{uuid,nome,slug,foto_url}]`. Anônimo: a chave não existe. Edição pelo `PATCH /fotos/{uuid}/usuarios` (ADM/RUA) já existente.
- `GET /fotos/{uuid}/details`: `historias[]` ganha `capa_url` (primeira foto não excluída da história, miniatura preferida, `null` sem foto) e `data_historia` (`YYYY-MM-DD|null`).
- **Álbum** (`GET /albuns`, `/albuns/{uuid}` e respostas de escrita): `ano_inicio`/`ano_fim` (`int|null`, min/max de `data_foto` das fotos não excluídas; fotos sem `data_foto` não contam).
- Demo: `DemoRuaSeeder` passou a marcar membros demo nas fotos de galeria (idempotente). Não há vídeos demo.
- Correções posteriores: `GET /videos/anos` aceita os mesmos filtros de `GET /videos` (`pasta_uuid` incl. `raiz`, `todas=1`, `q`; inválido -> 422), sem parâmetros igual a antes. `GET /membros/{slug}` agora devolve `capa_url`, `favoritos_count`, `lembrancas_count` e `favoritado` reais em `historias[]` e `historia_pessoal` (antes zerados/null). `anterior` de uma história sem `data_historia` em `GET /historias/{uuid}` segue a ordem do feed asc (datadas, depois nulas por id); hoje `data_historia` é NOT NULL no banco, então é ramo defensivo (testado com a coluna nullable só no teste).
- Pendência (já listada na rodada 6): `videos.path` NOT NULL impede vídeo só com `external_link`.

## Comandos artisan (rodar após o deploy das migrations)
- `php artisan membros:gerar-slugs`: slug (`Str::slug(nome)`) para membros ADM/RUA sem slug; nunca sobrescreve; colisão vira `-2`, `-3`.
- `php artisan fotos:gerar-miniaturas [--force]`: miniatura, largura/altura e `data_foto` (EXIF, só se vazia) das fotos existentes; falha com mensagem se a extensão GD não existir.

## Pendências de infraestrutura
- A imagem do backend (`backend/dockerfile`) não tem a extensão **GD**: sem ela as miniaturas não são geradas (`thumbnail_url` fica `null`; largura/altura e data EXIF funcionam). Pedir ao devops: `apt-get install libpng-dev libjpeg-dev libwebp-dev && docker-php-ext-configure gd --with-jpeg --with-webp && docker-php-ext-install gd`.
