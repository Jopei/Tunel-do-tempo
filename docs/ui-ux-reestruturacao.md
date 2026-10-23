# Túnel do Tempo — Especificação de UI/UX da reestruturação

Autor: ui-ux-specialist. Destinatário: frontend-specialist (implementa), motion-specialist (seção 8).
Complementa `docs/ui-ux-spec.md` e `docs/plano-reestruturacao.md`. Contratos de API: ver plano.
Identidade mantida: papel/ouro/tinta, Fraunces (títulos) + Inter (corpo), tokens `--tm-*`, `useTema` (claro/escuro/auto), foco `--tm-focus`, alvos 44px, 4 estados por tela (carregando/vazio/erro/conteúdo) com `TmSkeletonGrid`, `TmEmptyState`, `TmErrorState`.

Breakpoints (alinhados ao Vuetify): xs <600, sm 600-959, md 960-1279, lg >=1280. Mobile-first. `100dvh` em telas cheias.

Tokens novos permitidos (somente escopados por área, nunca em `:root` global): ver 1 (`--yt-*`) e 6 (`--membro-*`). Todo o resto usa `--tm-*`.

---

## 1. Home: watch page YouTube ~2008-2010

Intenção: nostalgia fiel, mas é cenário, não clone enganoso. Tudo escopado em `.yt-watch` (CSS scoped, sem tocar `tokens.css`). A home mantém o hero atual (h1 "Túnel do Tempo", citação, CTAs) ACIMA da "página YouTube"? Decisão: NÃO. O hero some; a watch page é a home. O h1 da página é o título do vídeo ("Tudo começa aqui") e há `<h1 class="sr-only">Túnel do Tempo</h1>` antes. O CTA de volta ao site fica na própria moldura retrô (1.4).

### 1.1 Tokens `--yt-*` (escopo `.yt-watch`, valores fixos, independem do tema claro/escuro: é uma "foto de época")
- Página: fundo `#FFFFFF`, texto `#333`, link `#0033CC` (visitado `#551A8B`), cinza de bordas `#CCC`, cinza de caixas `#F1F1F1`/`#E8E8E8`, texto secundário `#666` (5,7:1 sobre branco, ok AA).
- Cabeçalho: gradiente `#FFFFFF -> #E5E5E5`, borda inferior `#BBB`; barra de navegação azul-acinzentada (`#CCCCCC -> #EEE`) com abas "Vídeos / Categorias / Canais" decorativas (`aria-hidden`, não focáveis).
- Botão cinza clássico: gradiente `#FDFDFD -> #E0E0E0`, borda `#CCC`, raio 3px, sombra interna 1px branca. Inscrever-se: vermelho `#CC181E` com texto branco (7:1, ok).
- Estrela: ouro de época `#F7C32E` cheia, `#CCC` vazia. Barra de controles do player: `#000` 85%, progresso vermelho `#CC181E`, buffer `#999`, trilho `#555`.
- Fonte: `Arial, Helvetica, sans-serif` 13px (era do site; é a única exceção à Inter, só dentro de `.yt-watch`). Título do vídeo 18px bold. Raios 0-3px (contraste intencional com os 12-24px do site).
- Fundo externo à "janela": papel `--tm-paper-2` (claro) / `#14110B` (escuro) para não desancorar do site; a janela retrô tem sombra `--tm-shadow-lg` e borda 1px `#999`, como um screenshot emoldurado.

### 1.2 Layout desktop (>=960px)
Janela retrô centralizada, `max-width: 1000px`, padding 0, margem 24px auto. Dentro:
1. **Cabeçalho (altura 56px):** logo "TúnelTube" (decisão: não usar "YouTube" nem o logotipo real; wordmark próprio em Arial Black 26px, "Túnel" preto + "Tube" em caixa vermelha `#CC181E` com texto branco e raio 4px, igual ao padrão da época; SVG inline com `role="img" aria-label="Túnel do Tempo"`), caixa de busca falsa (input real `readonly`, `aria-hidden` não: usar `<button>` "Buscar" que abre a BuscaGlobal existente; assim a busca é funcional e útil), à direita links "Entrar" (visitante) / avatar+nome (logado) e "Idioma: Português" decorativo.
2. **Faixa de navegação (28px):** abas decorativas.
3. **Corpo em 2 colunas:** principal 640px + lateral 300px, gap 20px (soma 960 + padding 20 = 1000).
   - **Coluna principal:**
     - Título `h1` 18px bold, "Tudo começa aqui".
     - **Player 640x385** (16:9 + barra de 25px; `aspect-ratio: 640/385`, `width:100%`). Estado inicial: poster (frame do vídeo, WebP) com botão play grande central (80px, círculo `#000` 60% com triângulo branco, é um `<button aria-label="Reproduzir: Tudo começa aqui">`). Ao clicar: `<video src="/videos/Abertura.mp4" controls preload="none">` nativo troca o poster (acessível e leve). A "barra de controles retrô" é visual no poster/pausado: barra fina inferior (play, tempo "0:00 / 2:14", trilho, volume, tela cheia) somente quando o vídeo NÃO está tocando, `aria-hidden`; ao tocar, entram os controles nativos (decisão: não reimplementar player; fidelidade onde dá, acessibilidade onde importa). Se o dono preferir controles 100% retrô, é custo alto e perde teclado/legendas (ver 9).
     - **Linha de metadados (abaixo do player):** à esquerda avatar 48x48 quadrado do Zé Paulo + "Zé Paulo" (link azul, vai para `/a-rua`) + botão "Inscrever-se" vermelho decorativo (ao clicar: toast "Você agora acompanha a rua." e vira "Inscrito" cinza, apenas visual, `aria-pressed`, guarda em localStorage); à direita "N visualizações" 20px bold (número fixo plausível definido pelo dono; não ligar a contador real) com mini gráfico decorativo omitido.
     - **Barra de ações** (botões cinza clássicos): "Gostei" (polegar, contador local), "Não gostei", "Compartilhar" (Web Share/copiar link), "Salvar em" (decorativo vira "Ver histórias" = CTA, ver 1.4), "Sinalizar" omitido. Estrelas: 5 estrelas 16px + "1.234 avaliações" (decorativas, `role="img" aria-label="Avaliação: 5 de 5 estrelas"`).
     - **Caixa de descrição** (`#F1F1F1`, borda `#CCC`, padding 10px): "Publicado em 12 de março de 2009 por Zé Paulo", texto curto do dono, e "Mostrar mais / Mostrar menos" (`<button aria-expanded>`), tags azuis (`memórias`, `rua`, `amigos`) que linkam para `/historias` (visitante: login).
     - **Comentários** ("Comentários e respostas", N): campo "Escreva um comentário..." para logado abre `LembrancasSecao` existente; lista de lembranças como comentários: avatar 40px, nome azul, data "há 2 anos", texto, ações decorativas "Responder". Visitante: caixa "Entre para deixar sua lembrança" + botão. Máx. 5 visíveis + "Ver todos os comentários" (cai em `/historias`). Estados: skeleton 3 linhas, vazio ("Seja o primeiro a deixar uma lembrança"), erro com retry.
   - **Coluna lateral "Relacionados"** (300px): título "Vídeos relacionados" 13px bold; lista de até 8 itens: miniatura 120x90 (4:3, objeto cover, lazy, `alt=""` pois o título está no link), título azul 13px (2 linhas, clamp), "por Zé Paulo"/nome do membro, "N visualizações" cinza, duração em selo preto no canto da miniatura (`#000` 80%, branco 11px). Fonte dos dados: histórias recentes. Cada item é UM `<a>` (miniatura+texto). Visitante clica -> login com redirect (cadeado discreto na miniatura + tooltip). Vazio/erro/loading: skeletons 8 linhas.
4. **Rodapé retrô (altura ~80px):** links cinza decorativos pequenos (Ajuda, Sobre, Termos) `aria-hidden`; "© 2009 TúnelTube" e, em destaque real, os links funcionais: "Voltar ao site" não (já dentro), "Em memória" -> `/aquele-que-sonhou`, "Atualizações".

### 1.3 Layout mobile (<960px)
Mimetiza o m.youtube da época sem simular celular de época: coluna única, largura total, sem borda/sombra de janela (a janela vira a página; padding lateral 12px).
Ordem: cabeçalho compacto 52px (hambúrguer do AppNavbar à esquerda como está, wordmark "TúnelTube" 22px, botão busca) -> player full-bleed 16:9 (sem padding lateral) -> título (16px, 2 linhas) -> linha avatar+nome+Inscrever-se (botão 44px alto) -> visualizações e estrelas em uma linha -> ações em linha com rolagem horizontal (`overflow-x:auto`, `scroll-snap-type:x proximity`, botões 44px, sem scrollbar visível) -> descrição colapsada 3 linhas ("Mostrar mais") -> CTA principal (1.4) em largura total -> relacionados (lista vertical, miniatura 120x68 16:9, 2 linhas de texto) -> comentários (acordeão fechado "Comentários (N)" mostra o primeiro comentário como prévia, estilo YouTube mobile) -> rodapé. Breakpoint sm-md (600-959): igual ao mobile, `max-width: 720px` centralizado.

### 1.4 Como volta ao site (CTA)
Dois níveis, sem quebrar a ilusão:
- **Primário (dentro do cenário):** o botão cinza clássico "Salvar em" vira **"Entrar na história"** com ícone de relógio/túnel, destacado (borda ouro 2px `--tm-gold`, texto `#222`, 44px alto no mobile) -> visitante: `/login?redirect=/historias`; logado: `/historias`. Mesma pegadinha no relacionado de topo: primeiro item "Continue assistindo: A Rua" -> `/a-rua`.
- **Ao fim do vídeo (evento `ended`):** overlay no player "Quer continuar?": 2 botões grandes "Entrar na história" (ouro, primário) e "Conhecer a rua" (outline branco), mais "Rever". Foco vai ao primário; Esc fecha. Fade 400ms (sem movimento além do fade).
- **Abaixo da janela (fora do cenário, fundo do site):** faixa de 1 linha em Fraunces 18px: "Isto não é o YouTube. É a nossa rua." + link "Entrar no Túnel do Tempo". Mantém a honestidade do cenário e dá um segundo caminho acessível para quem pulou o vídeo.
- Navbar global permanece (seção 7), por cima da janela retrô, com `variant` sobre fundo claro.

### 1.5 Acessibilidade e estados
- Elementos decorativos retrôs `aria-hidden="true"` e fora do tab order. Cada interativo real tem foco visível (`--tm-focus` 3px, em fundo branco da janela OK).
- Contraste: link `#0033CC` sobre branco 9,6:1; cinza `#666` 5,7:1; branco sobre `#CC181E` 5,7:1.
- Vídeo nunca autoplay; `prefers-reduced-motion`: sem fade/zoom do poster.
- Erro ao carregar vídeo: mensagem dentro do player com "Tentar de novo". Sem backend de views/estrelas: números são constantes configuráveis (`yt-config.js` do dono).

---

## 2. Fotos (estilo Google Fotos) — `/galeria-fotos`

Superfície: `--tm-paper`; cabeçalhos de data em Fraunces. Navegação lateral não é necessária (Álbuns são aba), para não competir com a navbar.

### 2.1 Estrutura da página
Barra de contexto sticky abaixo da navbar (altura 56px, `top: var(--tm-navbar-h)`, z-index 5): abas segmentadas **Fotos | Álbuns** (`role="tablist"`, rotas distintas `/galeria-fotos` e `/fotos/albuns`), busca (ícone no mobile), botão **Enviar** (primary, ícone `mdi-upload`; só ADM/RUA), menu de ordenação/zoom (densidade).
Em modo seleção, esta barra é substituída pela barra de ações (2.4).

### 2.2 Grade justificada por data
- Algoritmo: linhas de altura-alvo (desktop 220px, tablet 180px, mobile 120px); empacota fotos preservando `largura/altura` até a soma das larguras >= largura do contêiner, então escala a linha para preencher exato (última linha: altura-alvo, sem esticar). Gap 4px (2px no mobile). Raio 4px nas miniaturas (denso, estilo Google), `object-fit: cover`. Sem `largura/altura`: assumir 4:3.
- Recalcular com `ResizeObserver` (debounce 100ms). Cada item reserva espaço (`aspect-ratio`) antes de carregar: sem layout shift. Placeholder `--tm-paper-2` com leve shimmer (desligado com reduced-motion). `loading="lazy"`, usar `thumbnail_url` na grade, original só no lightbox.
- **Cabeçalho de grupo** sticky (`top` = navbar + barra de contexto = 116px): mostra a data "Qua., 12 de março de 2019" (Fraunces 18px) e, à direita, contagem "24" em cinza e um checkbox de grupo (aparece no hover/foco ou no modo seleção). Granularidade: se o grupo tem >30 itens, quebrar por dia; senão agrupar por dia mesmo assim (consistente com `agrupar`); meses sem data -> "Sem data" ao fim.
- Teclado: a grade é um `role="grid"` simplificado: Tab entra na primeira foto, setas navegam (roving tabindex), Enter abre, Espaço seleciona. `aria-label` por item: "Foto {titulo ou 'sem título'}, {data}".

### 2.3 Scrubber de linha do tempo
- Desktop (>=960px): trilho vertical fixo à direita, largura 56px, altura = viewport menos barras, com marcadores de ano (fonte 12px) e pontos por mês proporcionais ao `total` de `/fotos/linha-do-tempo`. Hover/arrasto mostra bolha "mar. 2019" ao lado do cursor (Fraunces 14px, `--tm-paper` com `--tm-shadow-md`). Posição atual acompanha o scroll (IntersectionObserver nos cabeçalhos). Arrastar/clicar rola até o mês (carrega os grupos intermediários sob demanda via `de`/`ate`; mostrar skeleton no destino).
- A11y: `<nav aria-label="Linha do tempo">` com lista de links "2019", "2018"... (focáveis; Enter salta). O arrasto é só mouse/toque.
- Mobile (<960px): trilho de 24px na borda direita, só aparece enquanto rola (some 1,5s depois) e ao tocar; a bolha de data fica à esquerda do dedo. Sem a linha fina: apenas a "pílula" arrastável 24x44 e bolha. Não conflita com swipe (zona de toque 24px na borda).

### 2.4 Seleção e barra de ações
- Estados do item: repouso; hover (overlay `rgba(0,0,0,.12)`, check circular 24px vazio no canto superior esquerdo, 44px de área de toque); selecionado (foto encolhe para 88% com raio 8px dentro de moldura `--tm-gold-text` 2px? Decisão: encolher 8px e fundo `--tm-paper-2`, check preenchido ouro `--tm-gold` com ícone `#2A2112`); foco (anel `--tm-focus`).
- Entrada: clicar no check, Espaço no item, long-press 400ms (mobile, com vibração `navigator.vibrate(10)` se existir), ou Shift+clique para intervalo e Ctrl/Cmd+clique para alternar. No modo seleção, clicar na foto alterna (não abre lightbox).
- **Barra de ações** (substitui a barra de contexto, sticky, altura 56px, fundo `--tm-gold` 14% sobre paper + borda inferior): `X` (limpar, `aria-label="Limpar seleção"`), "N selecionadas" (`aria-live="polite"`), ações: "Adicionar ao álbum" (abre popover de álbuns + "Novo álbum"), "Baixar" (zip só se houver backend; senão omitir), "Excluir" (só ADM/RUA; `ConfirmDialog` "Excluir N fotos? Essa ação não pode ser desfeita." foco em Cancelar; máx. 100: se >100, aviso "Selecione até 100 por vez"), no mobile ações excedentes vão a "mais" (`mdi-dots-vertical`). Esc limpa a seleção. Anunciar "3 fotos selecionadas" via live region.
- Mobile: a barra vira superior fixa (56px, substituindo navbar apenas no modo seleção) e os botões ficam em ícones 44px com rótulo no tooltip/aria-label.

### 2.5 Lightbox com painel de detalhes (estende `TmLightbox`)
- Tela cheia, fundo `#0B0905` 96%. Topo (64px, gradiente transparente): voltar `mdi-arrow-left` (48px), título/data no centro-esquerda, ações à direita: Info (`mdi-information-outline`, `aria-pressed`), Compartilhar/copiar link, Baixar, Adicionar ao álbum, Excluir (ADM/RUA), Fechar. Setas laterais 48px (somem 2s após inatividade, voltam em hover/foco), contador "3 / 24" na base. Esc fecha, setas navegam, `i` alterna o painel, `Delete` abre confirmação, `+`/`-` zoom, duplo clique/toque zoom 2x, swipe horizontal troca, swipe vertical para baixo fecha (mobile). Pré-carrega anterior/próxima. `?foto=<uuid>` na URL (deep link).
- **Painel de detalhes:** desktop: coluna direita 360px, empurra a imagem (a imagem recentra no espaço restante), `--tm-paper`/`--tm-ink`, entra por slide 250ms. Mobile: bottom sheet, altura inicial 45vh, arrastável até 90vh, handle 40x4px, fecha arrastando para baixo.
  Conteúdo: título (editável inline para ADM/RUA, ícone lápis), descrição, **Data** (valor `data_foto` com botão "Editar data" -> date picker; essencial para a grade), enviado por (avatar+nome), tamanho/dimensões (`largura x altura`), álbuns que contêm a foto (chips), histórias em que aparece (links). Cada campo é `dl/dt/dd`. Foco ao abrir painel vai ao título do painel.
- A11y: `role="dialog" aria-modal`, focus-trap, devolve foco à miniatura; `alt` = título ou "Foto de {data}"; zoom não prende gestos de leitor de tela.

### 2.6 Álbuns
- `/fotos/albuns`: grade de cartões (auto-fill `minmax(200px,1fr)`, gap 16px; mobile 2 colunas fixas, gap 12px). Cartão: capa quadrada 1:1 raio `--tm-radius-md`, título (600, 1 linha), "N itens · intervalo de datas" cinza. Primeiro cartão "Novo álbum" tracejado (`--tm-border`) com `mdi-plus`. Sem capa: colagem 2x2 das 4 primeiras fotos ou ícone `mdi-image-album` em `--tm-paper-2`.
- `/fotos/albuns/:uuid`: cabeçalho com migalhas "Álbuns / {titulo}", título Fraunces 2xl editável (ADM/RUA), descrição, botões "Adicionar fotos" (abre seletor em grade com seleção múltipla, mesma grade justificada) e "Compartilhar". Corpo: mesma grade justificada (sem cabeçalhos de data por padrão; opção "Agrupar por data"). Remover do álbum: ação na barra de seleção ("Remover do álbum", não exclui a foto; texto do confirm deixa claro).
- Criar/editar álbum: `TmFormDialog` (título 80 caracteres, descrição 300, contador).

### 2.7 Upload múltiplo
- Entrada: botão Enviar, soltar arquivos em qualquer lugar da página (overlay de drop de tela inteira: borda tracejada 3px `--tm-gold`, texto "Solte as fotos para enviar", `TmDropzone` estendido), ou colar. Aceita `image/*`, 10 MB cada (confirmar), máx. 50 por lote.
- **Painel de upload** (canto inferior direito desktop, 360px largura; mobile: folha inferior minimizável, 56px minimizada): cabeçalho "Enviando 3 de 12" + barra geral + minimizar/cancelar tudo; lista (altura máx. 280px, rolável) com miniatura 40px, nome (elipse), tamanho, barra por item 4px (`--tm-gold`), estado por item: na fila (relógio), enviando (%), concluído (check verde `success`), erro (ícone `mdi-alert-circle` + "Tentar de novo"), cancelado. Concorrência 3. Ao terminar tudo: resumo "12 fotos enviadas" + "Ver" (rola ao topo) e fecha em 6s (não fecha se houver erro). Ao fim, as novas fotos entram na grade com fade 250ms. `aria-live="polite"` anuncia progresso a cada 25% do lote, não por foto.
- Antes de enviar: se data não vier de EXIF, aviso discreto "Sem data: serão agrupadas pela data de envio" (editável depois). Opção "Adicionar ao álbum" no diálogo prévio (select).
- Fechar aba com upload em andamento: `beforeunload`.

### 2.8 Estados
- Carregando: grade de blocos fantasma com larguras pseudoaleatórias fixas (seed estável) em 3 grupos com cabeçalho esqueleto; `aria-busy`.
- Vazio (sem fotos): ilustração de moldura `mdi-image-outline` 64px, título "Nenhuma foto ainda", texto "As fotos da rua aparecerão aqui.", botão "Enviar fotos" (ADM/RUA). Vazio de álbum e de busca ("Nada encontrado para '{q}'" + "Limpar busca") próprios.
- Erro: `TmErrorState` com "Tentar de novo"; erro de rede em scroll infinito mostra faixa inferior inline "Não deu para carregar mais. Tentar de novo" sem perder a grade.
- Carregar mais: IntersectionObserver `rootMargin: 600px`, botão "Carregar mais" como fallback.

---

## 3. Vídeos (estilo Google Drive) — `/galeria-videos?pasta=<uuid>`

### 3.1 Estrutura
Topo da página: **breadcrumb** (`<nav aria-label="Caminho">`, `ol`): ícone casa "Vídeos" > pasta > subpasta; item atual `aria-current="page"` em 600; itens intermediários clicáveis e também alvos de drop; caminhos longos colapsam o meio em "..." (menu com os ocultos) abaixo de 600px, mostrando só "< Pasta pai" no xs. À direita: alternância **Grade | Lista** (`v-btn-toggle`, `aria-pressed`, persiste em localStorage), ordenar (Nome, Data, Duração), botão **Novo** (menu: Nova pasta, Enviar vídeo; só ADM/RUA).
Duas seções: "Pastas" (chips-cartão 56px altura) e "Vídeos".

### 3.2 Grade
- **Pasta:** cartão horizontal 56px alto, raio `--tm-radius-sm`, fundo `--tm-paper-2`, borda `--tm-border`, ícone `mdi-folder` 24px ouro `--tm-gold`, nome, "N vídeos" cinza, menu `⋮`. grid auto-fill `minmax(220px,1fr)`, gap 12px (mobile 1 coluna, 2 em >=400px).
- **Vídeo:** cartão vertical, miniatura 16:9 (raio `--tm-radius-md` no topo), duração em selo (canto inferior direito), título 2 linhas, metadados "Enviado por X · data". grid auto-fill `minmax(240px,1fr)`, gap 16px (mobile 1 coluna, 2 em >=480px). Miniatura: poster do backend; sem poster, fundo `--tm-paper-2` com `mdi-play-circle-outline` (nunca um `<video>` por cartão).
- Seleção por check no hover/long-press como em Fotos; barra de ações: Mover, Renomear (1 item), Excluir, Baixar.

### 3.3 Lista
Tabela semântica `role="table"`: colunas Nome (ícone 20px + nome), Enviado por, Data, Duração, Tamanho, Ações. Linha 48px (56px no mobile), zebra sutil `--tm-paper-2` 40% opcional, hover `--tm-paper-2`. Mobile (<600): só Nome + Duração + `⋮`, metadados em segunda linha cinza. Cabeçalhos de coluna ordenáveis (`aria-sort`, botão).

### 3.4 Menu de contexto
- Disparo: clique direito (desktop), long-press 500ms (touch), tecla Menu/Shift+F10 e botão `⋮` visível (sempre existe uma via sem botão direito). Posiciona junto ao cursor, ajusta para não sair da viewport. `role="menu"`, setas, Esc, devolve foco.
- Itens (vídeo): Abrir, Mover para..., Renomear, Copiar link, Baixar, divisor, Excluir (cor `error`). (pasta): Abrir, Renomear, Mover para..., Excluir. No mobile vira bottom sheet com itens de 48px. Excluir pasta não vazia: o backend responde 409; mostrar "Esvazie a pasta antes de excluir" com ação "Abrir pasta".
- Perfis: visitantes/revisores veem só Abrir/Copiar link.

### 3.5 Mover
- Diálogo "Mover para" (`TmFormDialog`): árvore de pastas (`v-treeview` ou lista indentada 20px/nível, expansível, `role="tree"`), pasta atual desabilitada, "Raiz" no topo, botão "Nova pasta aqui", botão primário "Mover aqui" desabilitado até escolher. Ciclos (mover pasta para dentro dela mesma) desabilitados com tooltip. Sucesso: toast "Movido para {pasta}" com "Desfazer" (re-PATCH). Erros 422 mostrados em linha.

### 3.6 Preview e player (`/videos/:uuid` e painel)
- Clique abre **preview em diálogo** (mobile: tela cheia) com player nativo 16:9 máx. 960px, `preload="metadata"`, `poster`, título, enviado por, data, pasta (link), ações (Mover, Copiar link, Baixar, Excluir) e "Abrir em página" (`/videos/:uuid`). Setas fora do player trocam para o vídeo anterior/próximo da pasta; parar vídeo ao trocar/fechar; foco devolvido ao cartão.
- Página `/videos/:uuid`: player 16:9 largura da coluna (`max-width: 1000px`), abaixo título (h1) + metadados + descrição; barra lateral "Mais desta pasta" (desktop 320px; mobile abaixo, lista horizontal). Migalhas com a pasta para voltar.

### 3.7 Drag-and-drop
- Arrastar cartões/linhas (selecionados em lote) para pastas, itens do breadcrumb e itens da árvore (alvo realçado com borda 2px `--tm-gold` e fundo ouro 12%); fantasma de arrasto = miniatura 64px + badge com contagem. Soltar arquivos do computador na página abre o upload para a pasta atual (overlay igual ao de Fotos, texto "Solte para enviar a {pasta}"). Também soltar sobre uma pasta específica envia para ela.
- Alternativa por teclado obrigatória: o item "Mover para..." (3.5). Anunciar via live region "Movido para {pasta}". Em touch, DnD só após long-press, e o menu "Mover" é o caminho principal. Upload de vídeo: mesmo painel de progresso de 2.7 (um item por vídeo, mostra MB enviados/total, cancelável).

### 3.8 Estados
Carregando: skeleton de 4 pastas + 6 cartões (ou 8 linhas na lista). Vazio de raiz: "Nenhum vídeo ainda" + "Enviar vídeo"; vazio de pasta: "Esta pasta está vazia" + "Arraste vídeos para cá" (desktop) / "Enviar" (mobile). Erro: `TmErrorState`; pasta inexistente/sem acesso: `TmEmptyState` "Pasta não encontrada" + "Ir para Vídeos". Visitante: redireciona ao login (rota protegida).

---

## 4. Músicas — `/musicas` (login)

### 4.1 Biblioteca
- Cabeçalho: título "Músicas", busca (`q`), ordenar (Título, Artista, Álbum, Recentes), botão "Enviar música" (ADM/RUA), botão primário "Tocar tudo" (play 48px ouro, `--tm-gold` + texto `--tm-ink`) e "Aleatório".
- **Desktop (>=960):** tabela de faixas: # (hover vira play 24px), capa 40x40 + Título/Artista (2 linhas), Álbum, Duração, `⋮`. Linha 56px, hover `--tm-paper-2`; faixa tocando: número vira equalizador de 3 barras (estático em reduced-motion), título em `--tm-gold-text`, `aria-current="true"`. Duplo clique toca; Enter/Espaço no foco da linha toca/pausa.
- **Mobile:** lista de cartões 64px: capa 48x48, título/artista, duração, `⋮`; tocar a linha toca. Álbum oculto.
- Capa sem imagem: bloco `--tm-paper-2` com `mdi-music-note` ouro. Menu `⋮`: Tocar a seguir, Adicionar à fila, Editar (ADM/RUA), Excluir (ADM/RUA, confirm).
- Estados: skeleton de 8 linhas; vazio "Nenhuma música ainda" (+ Enviar); busca sem resultado; erro com retry. Música cuja URL assinada expirou: ao falhar o `<audio>` (erro 403), buscar de novo `/musicas` para renovar a URL e retomar da posição; se falhar, toast "Não foi possível tocar esta faixa" e avança para a próxima.

### 4.2 Player persistente (`PlayerBarra.vue`, fora do router-view)
Só aparece quando há faixa na fila (entra com slide-up 250ms). Fica sobre o conteúdo, `position: fixed; bottom: 0`, z-index abaixo de diálogos e acima do conteúdo; o `<main>` ganha `padding-bottom` igual à altura da barra (variável `--tm-player-h` definida pelo player: 72px desktop, 64px mobile, 0 quando oculto).
- **Desktop (barra, 72px, `--tm-paper`, borda superior `--tm-border`, sombra para cima):** 3 zonas. Esquerda (30%): capa 48x48, título (600) + artista; link para a biblioteca. Centro (40%): anterior, play/pause 44px (ouro), próxima, aleatório, repetir (off/todas/uma, `aria-pressed`/rótulo muda), abaixo trilho de progresso 4px (6px no hover/foco) com tempos "1:12 / 3:40", é `input type=range` estilizado (teclado: setas 5s). Direita (30%): fila (`mdi-playlist-music`), volume (ícone + slider 96px, mute), expandir/fechar.
- **Mobile mini (64px):** capa 40, título/artista em 1 linha (marquee desligado: usar elipse), play/pause 44px, próxima, trilho de progresso 2px colado no topo da barra (não interativo na mini). Toque no corpo da barra expande.
- **Mobile expandido (folha cheia, 100dvh, sobe 300ms):** botão recolher (`mdi-chevron-down`, 48px) no topo e "Tocando agora"/"Fila"; capa grande (largura 80vw, máx. 340px, raio `--tm-radius-lg`, sombra `--tm-shadow-lg`); título Fraunces 22px + artista; trilho de progresso 44px de área de toque com tempos; controles: aleatório, anterior, play/pause 72px, próxima, repetir; fila (botão abre lista). Arrastar para baixo recolhe. Fundo `--tm-paper` com leve tinta da capa (usar apenas se cor extraída; senão `--tm-paper-2`).
- **Fila:** desktop: painel lateral direito 360px sobre o conteúdo (ou popover); mobile: dentro da folha expandida. Cabeçalho "Fila · N faixas", "Limpar". Itens: capa, título/artista, duração, alça de reordenar (DnD com alternativa "Mover para cima/baixo" no menu), remover. Faixa atual realçada; "A seguir" separada de "Tocadas". Persistir fila e posição em `localStorage` (restaura pausado, sem autoplay).
- Controles de mídia: integrar `navigator.mediaSession` (título, artista, capa, play/pause/prev/next), teclas multimídia. Atalho global: `Espaço` só quando foco no player; não capturar Espaço em formulários.
- Convivência: ao tocar um vídeo/Aquele que sonhou, o player pausa e mostra o botão "retomar"; o áudio da homenagem usa o mesmo store (um som por vez).
- A11y: `role="region" aria-label="Player de música"`, botões com rótulos, estado em `aria-live="polite"` somente em mudança de faixa ("Tocando: título, artista"); progresso com `aria-valuetext` "1 minuto e 12 segundos de 3 minutos e 40".

---

## 5. A Rua: órbita

### 5.1 Composição
Dados: 13 membros de `/public/membros`. 1 central (sorteado por visita; guardar em `sessionStorage` para não trocar ao voltar da página dele) + 12 satélites. Nome/símbolo vêm do registry (`simbolo`); sem símbolo: foto do membro, sem foto: iniciais em círculo ouro.
- **Esferas:** círculo com a foto do membro (`object-fit: cover`), borda 2px `--tm-gold`, anel externo suave `0 0 0 4px rgba(199,164,58,.25)`, sombra `--tm-shadow-md`. O símbolo do membro aparece como medalhão 28% do diâmetro, canto inferior direito da esfera (fundo `--tm-paper`, borda ouro).
- **Central:** diâmetro `clamp(150px, 26vw, 240px)` (240 no desktop). **Satélites:** `clamp(52px, 8vw, 76px)`; 3 anéis concêntricos: anel 1 (4 satélites) raio 1,0 R, anel 2 (4) 1,45 R, anel 3 (4) 1,9 R, onde R = (diâmetro central/2 + 56px) no desktop; em xs R = central/2 + 36px e raios 1,0 / 1,38 / 1,75 para caber em 100vw sem scroll horizontal (cálculo: raio externo + raio do satélite <= 46vw). Alternativa mais organizada: 12 = 3 anéis x 4, ângulos defasados 30° entre anéis para não alinhar. Desenho dos anéis: círculos tracejados 1px `rgba(255,255,255,.25)` (decorativo, `aria-hidden`).
- Rotação lenta: cada anel gira (anel 1: 90s, anel 2: 130s, anel 3: 180s; sentidos alternados); os satélites contra-giram para a foto ficar de pé. Pausa em hover/foco de qualquer esfera, no toque, com aba oculta e quando `prefers-reduced-motion`/`data-reduzir` (posições estáticas).
- Altura do palco: `min(88dvh, 100vw * 1.0)` no mobile (quadrado), centro vertical. Sem scroll horizontal.

### 5.2 Hover/foco/ativo
- Desktop hover e foco por teclado: esfera escala 1,12 (200ms `--tm-ease`), brilho do anel ouro, **rótulo** em "tooltip" persistente: cartão `--tm-paper` com `--tm-shadow-md`, raio `--tm-radius-sm`, padding 8px 12px, símbolo 20px + nome (Inter 600, 14px) posicionado acima (ou abaixo se faltar espaço), seta de 6px; z-index sobre os anéis. Anel pausa a rotação do anel ativo.
- Foco por teclado (ver 5.4) mostra o mesmo rótulo e `outline` `#F0C86A` 3px offset 3px (classe `.tm-immersive`).
- Central: rótulo fixo sempre visível embaixo da esfera (nome + símbolo), pois é o "membro do dia"; legenda "Hoje na rua" (12px caps? não: "Hoje na rua" em Inter 12px).
- Clique: `transicionarPara(router, '/membros/<slug>', img)`. Membro "Aquele que sonhou" também vai para o slug dele (redirect já previsto).

### 5.3 Mobile
- Toque único = abre o membro? Com satélites de ~52px o risco de toque errado é alto. Decisão: **primeiro toque seleciona (mostra rótulo + pausa rotação + esfera cresce 1,15), segundo toque (ou toque no rótulo "Abrir") navega**. Toque fora limpa. Hit area de cada satélite 48x48 mínimo (pseudo-elemento `inset:-4px` se menor). Satélites de anéis diferentes não se sobrepõem em hit area (garantido pelos raios acima).
- Rótulo mobile: cartão fixo na parte inferior do palco (altura 72px, largura min(92vw, 360px)) com foto 40px, nome, símbolo e botão "Abrir página" (44px); evita o rótulo cobrir esferas e ficar sob o dedo.
- Dica "Toque em uma esfera para conhecer" some após a primeira interação (localStorage).
- Orientação paisagem: reduzir palco para `min(86dvh, 60vw)`.

### 5.4 Acessibilidade
- Estrutura: `<section aria-labelledby>` com `h1` "A Rua"; a órbita é uma **lista** (`<ul>`, `<li>` por membro, ordem DOM = membro central primeiro, depois anel 1..3 por ordem alfabética), não posições visuais. Cada esfera `<button aria-label="{nome}, membro da rua. Abrir página">`; a central: "{nome}, membro em destaque hoje. Abrir página". Imagens `alt=""` (o botão já nomeia). Símbolo `aria-hidden`.
- Teclado: Tab percorre na ordem acima (roving tabindex dentro do `<ul>` com setas esquerda/direita para anterior/próximo, Home/End); Enter/Espaço abre; Esc remove o rótulo. Como a rotação mexe posições, a rotação pausa quando QUALQUER esfera tem foco.
- Leitor de tela: `aria-live="polite"` na região do rótulo só para foco/seleção no mobile; texto alternativo "13 membros na rua. {nome} está em destaque."
- Alternativa não visual: botão "Ver todos os membros em lista" (`/membros`) sempre visível sob o palco; assim a órbita é decorativa-funcional, nunca o único caminho.
- Contraste: rótulo `--tm-ink` sobre `--tm-paper` >12:1; anel ouro como indicador não depende de cor sozinha (escala + rótulo).
- Estados: carregando (esferas fantasma `--tm-paper-2` pulsando suave, central maior), erro (`TmErrorState` com retry, e fallback estático de 13 iniciais se houver cache), vazio (<13: distribui os existentes nos anéis na ordem; 0 membros -> estado vazio).

---

## 6. Página de membro: casca `MembroPagina`

Princípio: o ADM edita `frontend/src/membros/<slug>.vue` à mão, então a casca precisa ser (a) bonita por padrão, (b) previsível: regiões nomeadas, temas por CSS vars, qualquer região substituível sem quebrar o resto.

### 6.1 Contrato de props
`MembroPagina` recebe: `membro` (objeto de `GET /membros/{slug}`: uuid, slug, nome, foto_url, descricao, historias[], historia_pessoal), `tema` (objeto opcional que sobrepõe as vars de 6.3), `simbolo` (url SVG/PNG ou nome de ícone `mdi-*` ou texto/emoji; ver registry), `carregando`, `erro`. Emite nada; só slots. A casca resolve os estados (skeleton/erro/404) e renderiza slots dentro do conteúdo.

### 6.2 Regiões e slots (ordem vertical)
1. **`barra-topo`** (fixa pela casca): migalhas "A Rua / {nome}" (volta ao `/a-rua`, preserva a esfera de origem para a transição), alternador de tema do site. Slot `topo-extra` à direita.
2. **`hero`** (slot, padrão fornecido): altura `min(72dvh, 640px)` desktop / `min(60dvh, 520px)` mobile, `padding-top: var(--tm-navbar-h)`. Fundo `--membro-hero-fundo` (cor, gradiente ou imagem com overlay de contraste). Conteúdo centrado (ou dividido 2 colunas >=960: texto à esquerda, símbolo à direita): **símbolo** grande (`clamp(120px, 28vw, 260px)`, `slot simbolo`, padrão: o `simbolo` do registry dentro de círculo com borda `--membro-destaque` 3px e brilho), `h1` nome em Fraunces `--tm-fs-hero` cor `--membro-hero-texto`, frase/lema (`descricao`, `<p>` 18-22px, máx. 52ch), e a foto de perfil como medalhão de 96px sobreposto na borda inferior do hero (-48px, quebrando o fundo). Subslots: `hero-fundo`, `simbolo`, `hero-conteudo`, `hero-acoes`.
3. **`historia-pessoal`** (slot, padrão: renderiza `membro.historia_pessoal.conteudo` com `.tm-prose` e `useSanitize`): coluna de leitura 68ch, centralizada, título "A história de {primeiroNome}" em h2 (serifa), data/tempo de leitura; se não houver, a região some (sem placeholder vazio) e o hero "pula" direto à mídia. Subslots: `historia-antes`, `historia-depois`. Letra capitular opcional com `--membro-destaque` (classe `.tm-dropcap`).
4. **`midia`** (slot, padrão: seção "Momentos" com abas Fotos | Vídeos | Músicas, cada uma só aparece se houver itens): fotos em grade justificada pequena (altura 160px/120px mobile, máx. 12 + "Ver todas" -> galeria filtrada pelo membro, se existir filtro; senão lightbox), vídeos em cartões 16:9, músicas em lista com play no player persistente. O ADM pode substituir inteiro por um slot com sua própria composição (ex.: carrossel, colagem, linha do tempo). Subslots: `midia-fotos`, `midia-videos`, `midia-musicas`.
5. **`historias`** ("Histórias em que {primeiroNome} aparece", slot, padrão: lista/grade de `HistoriaCard` auto-fill `minmax(260px,1fr)`, até 6, "Ver todas" se mais). Vazio: "Ainda não há histórias com {primeiroNome}."; erro: retry. Sempre presente e não removível (é a ponte para o resto do site).
6. **`rodape-membro`** (slot): navegação "Anterior / Próximo membro" (ordem do registry, cíclica, setas), "Voltar à rua". Padrão presente.
Slot extra **`extra-<n>`** nada: slots livres são `depois-hero`, `depois-historia`, `depois-midia`, `depois-historias` (ficam entre as regiões; o ADM injeta qualquer bloco). Cada região é `<section aria-labelledby>`.

### 6.3 Variáveis de tema por membro (escopo `.membro-pagina`, definidas via `tema` ou `<style>` da página do membro)
| Variável | Padrão | Uso |
|---|---|---|
| `--membro-cor` | `var(--tm-gold)` | cor principal/assinatura |
| `--membro-cor-texto` | `var(--tm-gold-text)` | links/títulos de seção sobre papel (deve ter 4,5:1) |
| `--membro-destaque` | `var(--membro-cor)` | bordas do símbolo, marcadores, dropcap |
| `--membro-hero-fundo` | `linear-gradient(160deg, color-mix(in srgb, var(--membro-cor) 35%, #14110B), #14110B)` | fundo do hero |
| `--membro-hero-texto` | `#FBF6E6` | texto do hero (>=4,5:1 sobre o fundo) |
| `--membro-fundo` | `var(--tm-paper)` | fundo do corpo |
| `--membro-superficie` | `var(--tm-paper-2)` | cartões, citações |
| `--membro-texto` | `var(--tm-ink)` | texto de corpo |
| `--membro-fonte-titulo` | `var(--tm-font-title)` | fonte dos títulos (ADM pode importar outra) |
| `--membro-raio` | `var(--tm-radius-md)` | raio dos cartões |
| `--membro-sombra-simbolo` | `0 0 48px color-mix(in srgb, var(--membro-cor) 55%, transparent)` | brilho do símbolo |
Regras para o ADM: (1) trocar apenas variáveis, não seletores internos da casca; (2) checar contraste dos pares texto/fundo (a casca não corrige); (3) no tema escuro do site, `--membro-fundo`/`--membro-texto` continuam seguindo `--tm-*` a menos que o membro defina uma versão `[data-tema="escuro"] .membro-pagina { ... }`; (4) hero com imagem exige overlay `linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.65))`. `registry.js` fornece `cores: { cor, texto, hero }` que a casca converte nas vars acima.

### 6.4 Página padrão de fallback (`MembroPaginaPadrao`)
Usa a casca sem personalização: hero com a foto do membro como símbolo (círculo 200px; sem foto: iniciais Fraunces 72px sobre `--tm-gold`), gradiente padrão, nome, `descricao`; história pessoal (se houver); mídia (abas condicionais); histórias do membro; navegação anterior/próximo. Nenhum membro pode ficar sem página: o fallback é resposta a qualquer slug válido sem componente. Slug inexistente: `NotFoundView` embutida "Este membro não foi encontrado" + "Voltar à rua". Sem login: `/membros/:slug` exige login (API é LOGIN): redireciona ao login com `redirect`.

### 6.5 Responsividade e a11y
Mobile-first: hero em 1 coluna, símbolo acima do nome; mídia com abas rolagem horizontal; `max-width` do corpo 1100px (leitura 68ch). Um único `h1` (nome); regiões com `h2`. Símbolo decorativo `aria-hidden` se acompanhado do nome; se for a única identidade, `role="img"` + `aria-label="Símbolo de {nome}"`. Foco movido ao `h1` na entrada. Transição de entrada vinda da órbita: ver 8.

---

## 7. Navegação global (estende `AppNavbar`)

Itens atuais: Início, Histórias, A Rua, Fotos, Vídeos, Atualizações. Novo conjunto (desktop, barra 60px; ordem por frequência/narrativa):
**Início · Histórias · A Rua · Fotos · Vídeos · Músicas · Atualizações**
- 7 itens + marca cabem em >=1180px. Entre 960 e 1179px: "Atualizações" e "Músicas" vão para um item "Mais" (`v-menu`, `mdi-dots-horizontal`, rótulo "Mais"), mantendo Fotos/Vídeos visíveis. Abaixo de 960px: drawer (hambúrguer à esquerda, como está).
- **Músicas:** ícone `mdi-music-note-outline`, bloqueada para visitante (cadeado, como Histórias/Vídeos).
- **Fotos** ganha submenu leve (hover/foco): "Todas as fotos", "Álbuns" (`/fotos/albuns`); no drawer, são 2 sub-itens recuados. Também suficiente: as abas internas da página (2.1), então o submenu é opcional (P2); decisão: sem submenu no desktop, as abas bastam, evita menu pesado.
- **Vídeos:** sem submenu (pastas navegadas na página).
- **A Rua** mostra, no estado ativo, a página de membro como filha (`aria-current="page"` na A Rua quando a rota é `/membros/:slug`; a migalha da casca completa o contexto). "Membros" (lista) fica no menu do usuário/rodapé do drawer e no botão "Ver todos" da órbita.
- Drawer mobile (ordem): Início, Histórias, A Rua, Fotos, Vídeos, Músicas, Atualizações; divisor; Em memória; tema; (logado) Meu perfil, Sair; (visitante) Entrar.
- **Player persistente:** não faz parte da navbar; vive no fim da tela (4.2). Quando tocando, item "Músicas" mostra equalizador de 3 barras 14px ao lado do rótulo (estático em reduced-motion) para indicar áudio ativo.
- Home (watch page): navbar presente, tema claro sobre fundo papel; a janela retrô começa 16px abaixo da barra. Páginas imersivas (A Rua, membro) usam a variante sobre fundo escuro já existente.
- Menu "Criar" (ADM/RUA): História, Foto(s) (abre upload), Vídeo, Música, Atualização.
- Visitante: Fotos, A Rua, Atualizações e Início públicos; demais com cadeado e `?redirect`. Título de página e foco no h1 a cada rota (já previsto no spec anterior).
- Rótulos acentuados; `aria-current="page"` + sublinhado ouro 2px no ativo.

---

## 8. Momentos de animação (para o motion-specialist)

Princípios: um movimento "grande" por tela; tudo respeita `prefereMenosMovimento()` de `useTema` (também `data-reduzir="1"` e a media query): nesses casos a ação ocorre instantânea (duração 0, apenas troca de estado/opacidade 0->1 em <=80ms se preciso para foco). Easing padrão `--tm-ease`; duração dentro de `--tm-dur-*`. Usar só `transform`/`opacity` (GPU), nunca animar `width/height/top/left`. Pausar loops em aba oculta.

| # | Momento | Intenção | Duração/easing |
|---|---|---|---|
| 1 | Home: janela retrô entrando | Revelar a "memória" como se ligasse a TV antiga | fade + translateY 12px, 500ms, 1 vez; sem scanlines em loop |
| 2 | Home: poster -> player | Troca suave poster/vídeo, play cresce 1,0->1,08 no hover | 200ms |
| 3 | Home: fim do vídeo, overlay CTA | Convite sem cortar bruscamente | fade 400ms, botões em stagger 80ms |
| 4 | Home: "Inscrever-se" vira "Inscrito" e estrelas | Microfeedback divertido | botão 150ms, estrela pulsa 1,0->1,25->1,0 em 300ms |
| 5 | Navegação de página | Continuidade entre rotas | fade 150-250ms (out-in), barra de progresso de chunk |
| 6 | Órbita: entrada | Esferas surgem dos anéis para fora | escala 0.6->1 + opacity, stagger 60ms por esfera, 450ms cada, total <=1,2s |
| 7 | Órbita: rotação contínua | Ambiente vivo e lento | anel 1/2/3 = 90/130/180s linear infinito; satélites contra-giram; pausa em hover/foco/toque/aba oculta |
| 8 | Órbita: hover/seleção | Destacar membro | esfera 1,0->1,12, anel ouro acende, rótulo fade+4px 160ms |
| 9 | Órbita -> página do membro (transição existente) | Esfera "viaja" e vira o símbolo/medalhão do hero | reaproveitar `transicionarPara`; 600-800ms (`--tm-dur-slow`+); destino já mostra a foto como medalhão (FLIP) |
| 10 | Hero do membro | Símbolo "acende" ao chegar | brilho `--membro-sombra-simbolo` 0->1 em 800ms; título fade-up 500ms em stagger 100ms |
| 11 | Fotos: itens entrando na grade | Suavizar carregamento | opacity 0->1 + blur-up da miniatura, 250ms, só itens novos |
| 12 | Fotos: seleção | Item encolhe 100->88% e check pop | 150ms; check 1->1,15->1 em 200ms |
| 13 | Fotos: barra de ações | Troca de barra de contexto por ações | cross-fade + translateY 8px, 200ms |
| 14 | Lightbox | Abrir expande da miniatura (FLIP) e painel de detalhes desliza | abrir 300ms, painel 250ms, troca de foto slide 200ms (fade em reduzido) |
| 15 | Scrubber | Bolha de data segue o cursor, trilho aparece ao rolar (mobile) | bolha sem animação (segue direto); trilho fade 200ms |
| 16 | Upload: painel e progresso | Painel sobe; barra avança linear; item concluído vira check | sobe 250ms; check desenha em 300ms |
| 17 | Vídeos: mover/drag-and-drop | Alvo de soltura pulsa; item "voa" para a pasta | borda do alvo 120ms; voo 300ms (fade em reduzido) |
| 18 | Menu de contexto | Aparece no ponto do clique | scale .96->1 + fade, 120ms |
| 19 | Músicas: player entra/expande | Barra sobe; folha mobile expande a partir da barra | barra 250ms; folha 300ms; arrastar para baixo segue o dedo |
| 20 | Músicas: equalizador e capa | 3 barras ondulando enquanto toca | loop 900ms alternado, estático em reduzido |
| 21 | Aquele que sonhou: frase | Revelação do acróstico ITALO | já existente, 40ms por letra / 600ms por linha, tudo de uma vez em reduzido |
| 22 | Toasts | Entrada/saída discretas | slide 8px + fade, 200ms |

Não animar: scroll da grade (nativo), troca de tema (instantânea), skeleton shimmer só quando não reduzido.

---

## 9. Decisões a confirmar com o dono

1. Nome do wordmark da home ("TúnelTube") e se pode usar a estética de vermelho/branco do YouTube original (risco de marca é baixo por ser paródia/cenário, mas confirmar). Números de visualizações, avaliações e texto de descrição do vídeo (valores do dono).
2. Controles do player da home: nativos (recomendado, acessíveis) vs. 100% retrô (custa teclado/legendas).
3. Visitante: relacionados e comentários da home mostram apenas amostra e levam ao login? (assumido: sim).
4. Música: limite de tamanho, formatos e se a fila persiste entre sessões (assumido: persiste em localStorage).
5. Órbita: satélites em ordem alfabética estável (assumido) ou embaralhados a cada visita; tamanho do central muda a cada visita (assumido: só o membro).
6. Membro: formato dos símbolos (SVG recomendado, 1:1) e se a história pessoal aceita HTML rico (sanitizado).
7. Granularidade da seleção/Excluir em lote de Fotos para RUA vs ADM; envio de fotos/vídeos/músicas apenas ADM/RUA (assumido).

Observação de dependência para frontend: o registry (`frontend/src/membros/registry.js`) deve exportar `{ componente?, simbolo, cores }`; a casca lê `cores` e escreve as vars `--membro-*` (6.3). Nenhum token `--tm-*` global é alterado por esta especificação.
