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
