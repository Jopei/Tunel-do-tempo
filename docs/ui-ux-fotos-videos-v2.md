# Fotos e Vídeos v2: especificação de UI/UX (era 2011-2018)

Escopo: camada visual e de microinteração sobre `FotosView`, `AlbunsView`, `AlbumView`, `components/fotos/*`, `VideosView`, `VideoPlayerView`, `components/videos/*`. Implementação: frontend-specialist. Animação: motion-specialist (seção 7).
Base: `docs/ui-ux-reestruturacao.md` (fase 2) e `docs/ui-ux-historias-2011-2018.md` (Esquina). Contratos: `docs/api-novos-endpoints.md` (Rodadas 4-7).

## 0. Princípios e o que NÃO muda

- **Mecânica intocada**: grade justificada por data, scrubber, seleção (long-press/check/shift), lightbox (zoom, pan, setas, painel), upload, álbuns, drag-and-drop, pastas, breadcrumb, grade/lista, menu de contexto, mover, atalhos, URL-state (`?q&ordem`, `?pasta`), densidade e visão em `localStorage`. A v2 troca **cabeçalhos, molduras, cópia, estados vazios e blocos novos**, não o comportamento.
- **Regra de ouro (igual à Esquina)**: o retrô vive nas **molduras e microdetalhes** (ano em destaque, "há 12 anos", moldura de player, capa de álbum). A grade em si continua densa e neutra: foto é o protagonista, nunca competir com ela.
- Usabilidade Google Fotos/Drive é o piso: nada de esconder busca, ordem, seleção ou ações atrás de tema.
- Sem logos, ícones ou nomes de marcas. Referência de **estrutura** (álbum de rede social, canal/playlist), não de marca.

## 1. Tokens (reaproveitando `--hx-*`)

Nada em `:root`. Escopo novo `.fv-era` aplicado na raiz de `FotosView`, `AlbunsView`, `AlbumView`, `VideosView`, `VideoPlayerView`. Em `assets/historias-era.css`, estender o seletor de definição para `.historias-era, .fv-era` (assim `--hx-filtro-foto`, `--hx-vinheta`, `--hx-grao`, `--hx-ano-h`, `--hx-ano-cor/-suave/-selo`, `--hx-acao-*`, `--hx-balao` e os utilitários `.hx-foto`, `.hx-selo`, `.hx-grao` valem sem duplicar). Mesma regra de recálculo dos derivados `--hx-ano-*` em todo elemento que redefine `--hx-ano-h` (cabeçalho de período, cartão de ano, estante).

Tokens novos (arquivo `assets/fotos-videos-era.css`, só dentro de `.fv-era`):

| Token | Valor | Uso |
|---|---|---|
| `--fv-filtro-ativo` | `0` ou `1` via classe `.fv-filtro-on` na raiz | liga o filtro nas imagens (ver 2.4) |
| `--fv-moldura` | `6px solid #fff` (escuro: `rgba(255,255,255,.88)`) | borda "foto revelada" em capas de álbum e destaques; NÃO nas miniaturas da grade |
| `--fv-player-bg` | `#14110b` | moldura/fundo de miniaturas e página do player (escuro nos dois temas, é "tela") |
| `--fv-player-barra` | `rgba(0,0,0,.72)` | barra de controles falsa e selo de duração |
| `--fv-player-prog` | `var(--tm-gold)` | progresso falso e linha de hover |
| `--fv-cabeca-h` | `64px` desktop / `52px` mobile | altura do cabeçalho de período sticky |
| `--fv-capa-album` | `4 / 3` | proporção das capas de álbum |

Matiz por ano: reutilizar `--hx-ano-h` da Esquina (38, 30, 22, 12, 355, 340, 320, 300 para 2011-2018; neutro 45 para Antes/Depois/Sem data). Só acento (selo, filete, placeholder), nunca fundo de texto. Tipografia: Fraunces 800 tabular para anos e títulos de seção; Inter para o resto; **sem fonte nova**. Duração e contadores: Inter 600 tabular, mínimo 12px (selo de duração 12px sobre `--fv-player-barra` = AA).

## 2. Fotos

### 2.1 Estrutura da página (topo para base)

1. **Abas e controles atuais** (`FotosAbas`: Fotos / Álbuns, busca, ordem, densidade, enviar). Acrescentar à direita do controle de densidade o **toggle "Filtro antigo"** (2.4). Sem outras mudanças.
2. **Destaques** (novo, `FotosDestaques`), só na visão padrão sem busca/filtro ativo (`q` vazio, sem `de/ate`). Colapsável ("Ocultar destaques", lembra em `localStorage` `tunel-fotos-destaques`). Três blocos em tiras horizontais com scroll-snap, nesta ordem, cada um só aparece se tiver dados:
   - **"Neste dia, há X anos"**: cartões 4:5 com foto, selo no canto "há 9 anos" (`<time>`), legenda "14 de junho de 2017". Agrupa por ano; se houver só 1 ano, o título vira "Neste dia, há 9 anos". Clique abre o lightbox já posicionado (contexto = a tira).
   - **"Memórias por ano"**: um cartão por ano 2011-2018 (e "Antes da Rua"/"Depois da Rua" se houver): capa com moldura `--fv-moldura`, ano gigante Fraunces sobre scrim inferior, "132 fotos". Clique **rola a grade até o capítulo do ano** (usa o mesmo `scrollTo` do scrubber) e foca o `h2` do período. Matiz do ano só no filete inferior e na vinheta.
   - **"Álbuns recentes"**: 6 capas (componente de capa de álbum, 2.3) + cartão "Ver todos" para a aba Álbuns. Omitido para visitante deslogado (álbuns são LOGIN).
   Altura total fechada: 0 (não ocupa espaço). Aberta: tira de 168px mobile / 220px desktop por bloco com título `h2` 20px. Skeleton com mesma altura; nunca causa salto de layout.
3. **Grade por período** (existente), com **cabeçalhos novos** (2.2).
4. **Scrubber** (existente): marcas de ano ganham o mesmo matiz do capítulo (ponto de 6px) e o rótulo flutuante passa a mostrar "2014 · há 12 anos" ao arrastar. Mobile: continua oculto (já é) e a navegação por ano passa a vir dos cartões "Memórias por ano" e de um `v-select` "Ir para o ano" na barra de contexto (alvo 44px).

### 2.2 Cabeçalhos de período (capítulos)

Dois níveis, preservando o agrupamento por data existente:

- **Capítulo (ano)**: aparece quando muda o ano. Alinhado à Esquina: ordem de capítulos segue a ordenação escolhida (`recentes` = Depois, 2018 → 2011, Antes, Sem data; `antigas` = Antes, 2011 → 2018, Depois, Sem data; "Sem data" sempre por último). Layout: selo circular do ano (`.hx-selo` 56px, 44px mobile) + `h2` "2014" Fraunces `--tm-fs-2xl` + subtítulo "há 12 anos · 132 fotos" (`<time datetime="2014">` com `title` e `sr-only` "ano de 2014"). Filete de 3px `--hx-ano-cor` à esquerda no desktop; no mobile, filete embaixo. Extras com texto próprio e tom afetivo: "Antes da Rua (até 2010)", "Depois da Rua (2019 em diante)", "Sem data (ninguém lembra quando)".
- **Mês** (sub-cabeçalho, existente): mantém "junho de 2014" em Inter 600, sem selo. Sticky só em desktop, abaixo do capítulo? **Decisão**: sticky apenas o nível mais interno visível (mês) com o ano em prefixo pequeno ("2014 · junho"), para não gastar 120px de altura. Mobile: mesmo, `--fv-cabeca-h` 52px.
- Contagem: usa `GET /fotos/linha-do-tempo` (já agrega por mês; somar por ano no cliente). Sem endpoint novo para isso.
- Mês sem ano (sem `data_foto`, usa `created_at`): cai no capítulo do ano do envio e ganha tooltip "data de envio" (como já faz o lightbox). Fotos de fato sem data não existem na API atual; "Sem data" só se `data_foto` e `created_at` faltarem (defensivo).
- Fundo: `--tm-paper` com `backdrop-filter: blur(6px)` **apenas** no cabeçalho sticky (1 elemento); com fallback opaco.

### 2.3 Álbuns: capa de álbum de rede social

`AlbumCard` passa a ser um cartão "álbum" 4:3:
- Capa `capa.thumbnail_url ?? capa.url` com `--fv-moldura` (borda branca 6px, canto 12px) e vinheta suave; **tilt estático**: duas "fotos" empilhadas atrás (pseudo-elementos girados -2° e +1.5°, só sombra/borda, sem imagens extras) quando `fotos_count > 1`.
- Faixa inferior sobre scrim: título (Fraunces 600, 1 linha, `title` com texto completo), "42 fotos" e faixa de anos "2013-2015" (se a API informar, 5.3).
- Sem capa: tile `--hx-ano-suave` + ícone `mdi-image-multiple-outline` + título (nunca vazio).
- Selo "+N" no canto superior direito como contador de mídia quando `fotos_count > 99` não é necessário; mostrar `fotos_count` sempre no rodapé.
- Foco/hover: eleva 2px, empilhadas se abrem 2px. Todo o cartão é um link (uma tabulação por álbum); menu de ações separado, 44px.
- `AlbumView`: cabeçalho como "capa de álbum aberto": capa 4:3 à esquerda (desktop) ou topo (mobile), título `h1`, descrição, "42 fotos · 2013-2015", botões (Enviar, Editar, Excluir) conforme papel. A grade abaixo é a mesma, **sem cabeçalhos de capítulo** (ordem do álbum manual); com "ordenar por data" ativo, usa os capítulos.
- Grade de álbuns: `repeat(auto-fill, minmax(220px, 1fr))`, 2 colunas no mobile (<600), gap 16px/12px.

### 2.4 Filtro de foto antigo (opcional, toggle)

- Controle: `v-switch` compacto "Filtro antigo" (ícone `mdi-image-filter-vintage`, `aria-label="Aplicar filtro de foto antiga"`), na barra de contexto de Fotos e no topo do lightbox. **Padrão: desligado** (foto de memória deve ser fiel ao original; o filtro é um tempero escolhido). Estado em `localStorage` `tunel-fv-filtro` e mostrado como `aria-checked`.
- Quando ligado: classe `.fv-filtro-on` na raiz; `.hx-foto__img` usa `filter: var(--hx-filtro-foto)` e a vinheta `--hx-vinheta`. Aplicar em: destaques, capas de álbum, grade e lightbox (imagem principal). Não aplicar a vídeos, e **nunca** no download/compartilhar (arquivo original intacto).
- No lightbox, tecla/botão "Ver original" (olho) mantém pressionado = original temporário (a11y: botão com `aria-pressed`, alternativa por clique).
- Performance: `filter` só em elementos visíveis; na grade justificada, aplicar via classe no contêiner (uma regra CSS), sem `will-change`. Desligar automaticamente a vinheta (não o sépia) em densidade "compacta" para evitar custo de pintura em celulares fracos.
- Acessibilidade: o filtro mantém contraste de texto sobre foto porque texto sempre fica em scrim próprio, não depende do filtro.

### 2.5 Lightbox com cara de "foto postada"

Mecânica atual intacta (palco, zoom, pan, setas, ociosidade, atalhos). Muda o **painel** (`lb-painel`) e o cabeçalho:

Painel (320px desktop à direita; bottom sheet no mobile, já existente) vira o **post da foto**, de cima para baixo:
1. **Cabeçalho do post**: avatar do autor do envio (40px; `cadastrado_por.foto_url`, senão iniciais), nome (link para `/membros/:slug` se existir slug), linha "há 12 anos · 14 de junho de 2014" (`<time datetime>`, data da foto; se for data de envio, "enviada em …"). Deslogado: sem avatar/nome (já ocultamos `cadastrado_por`), só a data.
2. **Legenda**: `titulo` em Fraunces 600 e `descricao` em Inter; edição inline atual (membros) permanece (botão lápis).
3. **Marcados** ("Estavam nesta foto", 5.2): avatares 36px sobrepostos + nomes, cada um link; "+N". Omitido se vazio/indisponível.
4. **Histórias vinculadas como posts**: mini-cartões (`vinculos.historias`) de 64px: capa quadrada com moldura, título (1 linha), "há 11 anos". Link para a história, fecha o lightbox. Rótulo "Aparece em N história(s)". Álbuns vinculados como chips (`mdi-image-multiple-outline`) logo abaixo.
5. **Barra de ações** (3 botões iguais, ícone + rótulo, 44px): **Compartilhar** (link/Web Share), **Baixar**, **Álbum** (abre seletor). Sem "curtir" em foto (não existe contrato). Excluir/editar data continuam no menu `…` para membros.
6. **Detalhes técnicos** (dimensões, envio, tamanho) recolhidos num `details` "Detalhes" (fechado), para a primeira dobra ser só o post.
- Desktop ≥1200: painel abre por padrão se o usuário já abriu antes (preferência atual). Mobile: sheet com alça; primeira dobra = cabeçalho + legenda + marcados.
- Fita de miniaturas inferior (opcional, só desktop): 48px, para saltar entre fotos da mesma tira; fora de escopo se complicar (não obrigatória).
- Foco: ao abrir o painel, foco no `h2` "Sobre esta foto"; `Esc` fecha o painel e depois o lightbox (já é o comportamento).

### 2.6 Estados vazios e erro (cópia temática, mesmo `TmEmptyState`)

| Contexto | Ícone | Título | Texto | CTA |
|---|---|---|---|---|
| Sem fotos (geral, membro) | `mdi-image-outline` | "O álbum da Rua ainda está em branco" | "As fotos da turma vão aparecer aqui, do mais antigo ao mais novo." | Enviar fotos |
| Sem fotos (visitante/não membro) | idem | "Ainda não revelaram nenhuma foto" | "Quando alguém enviar, ela aparece aqui." | nenhum |
| Busca/filtro sem resultado | `mdi-image-search-outline` | "Nenhuma foto com '{q}'" | "Tente um nome, um ano (ex.: 2014) ou outra palavra." | Limpar busca |
| Sem álbuns | `mdi-image-multiple-outline` | "Nenhum álbum por aqui" | "Álbuns juntam fotos de um mesmo momento: uma viagem, um verão, uma festa." | Criar álbum (membro) |
| Álbum vazio | `mdi-image-plus-outline` | "Este álbum ainda está vazio" | "Adicione fotos da galeria ou envie novas." | Adicionar fotos |
| Destaques sem "neste dia" | bloco omitido | | | |
| Erro | `TmErrorState` atual | | | Tentar de novo |
Ilustração: opcional, ícone 64px sobre moldura `--fv-moldura` rotacionada -3° (estática). Sem imagens externas.

### 2.7 Mobile (<600) e tablet

- Destaques: tiras com cartões 132px de largura (peek de 24px do próximo para sugerir rolagem), `scroll-snap-type: x mandatory`; região rolável `tabindex="0"` com `aria-label`.
- Capítulo: selo 44px, texto uma linha, sem filete lateral. Mês sticky 52px.
- Grade: linha-alvo menor (já tem escala de densidade); sem scrubber. Toggle de filtro antigo dentro do menu "Opções" se não couber (alvo 44px).
- Lightbox: painel como bottom sheet (já), ações em 3 colunas iguais; gesto de arrastar para baixo fecha (existente).
- 600-959: destaques como tiras; álbuns em 3 colunas. ≥960: scrubber visível.

## 3. Vídeos

### 3.1 Organização (mantida) e o que muda

Drive intacto: pastas, breadcrumb, grade/lista, menu de contexto, mover, seleção, `?pasta`. **Novas partes**: moldura de player nas miniaturas, selo de duração, cabeçalho temático, **terceira visão "Por ano"** (estante), preview melhor, player com identidade, vazios.

Visão (`v-btn-toggle`) passa de 2 para 3: Grade / Lista / **Por ano** (`mdi-timeline-outline`, `aria-label="Agrupar por ano"`). Persistência: `tunel-videos-visao` aceita `grade|lista|ano`. Por ano ignora pastas (mostra todos os vídeos via `todas=1`, com aviso discreto "Mostrando todos os vídeos, de todas as pastas" e botão "Voltar às pastas"); se `?pasta` estiver ativo, permanece ele como filtro e chip "Pasta: X ×".

### 3.2 Miniatura com moldura de player antigo (`VideoCard`)

Proporção 16:9 fixa (sem layout shift), `border-radius: 8px`, fundo `--fv-player-bg` (letterbox) e:
- **Barra de controles falsa** (estática, `aria-hidden`) na base: 28px, `--fv-player-barra`, ícone `mdi-play` 14px, trilho de 3px com progresso `--fv-player-prog` em 0% (cresce até a posição "visto" no futuro; hoje 0), ícones de volume/tela cheia 14px. Aparece sempre no desktop; no mobile só a linha de progresso (4px) para não poluir 2 colunas.
- **Selo de duração**: canto inferior direito, acima da barra, no estilo da época: caixa preta semitransparente, raio 3px, texto Inter 12px 600 branco `tabular-nums` ("3:07", "1:02:14" — formato do `formatarDuracao` atual). Sem duração: não mostrar selo (nunca "0:00").
- **Play central**: círculo 44px `rgba(0,0,0,.55)` com triângulo branco, **visível só em hover/foco** no desktop e sempre em placeholders (sem `poster_url`). O cartão inteiro continua sendo o botão.
- **Sem poster**: tile `--hx-ano-suave` com ícone `mdi-play-circle-outline` 48px; a barra falsa permanece (identifica "vídeo").
- Sob a miniatura: título 2 linhas (Inter 600 15px), meta "Enviado por Ana · há 11 anos" (`<time>`; data = `data_video` se existir, senão envio com texto "enviado"). Tamanho em arquivo vira tooltip/lista.
- Marca de ano: pequeno selo no canto superior esquerdo com os 2 últimos dígitos do ano ("'14") em `--hx-ano-selo`, só na visão Por ano e Grade quando houver `data_video`. `aria-hidden` (a data está no texto).
- Selecionado/hover: mantém realce atual (anel dourado + check). Anel de seleção ≥3:1 contra a moldura escura.

### 3.3 Pastas (`VideoPastaCard`)

- Ícone de pasta continua (reconhecível), mas com **aba colorida pelo ano** quando a pasta tiver nome numérico de ano (2011-2018; heurística visual simples, sem dado novo) usando `--hx-ano-cor`. Outras pastas: aba dourada neutra.
- Texto: nome (1 linha), "7 vídeos". Alvo 44px+, mesmo menu de contexto.
- Breadcrumb: mantém; raiz recebe o rótulo "Vídeos" com ícone `mdi-filmstrip` (cópia), separadores `›`.

### 3.4 Visão alternativa "Por ano" (estante)

Substitui o conceito "estante de fitas" por algo mais fiel à era digital: **canais/estantes por ano**, como o catálogo de playlists de um canal:
- Uma **prateleira por capítulo** (mesma ordem/capítulos da Fotos: Antes, 2011-2018, Depois, Sem data), cada uma com cabeçalho (selo do ano + `h2` "2014" + "há 12 anos · 7 vídeos" + link "Ver todos") e uma **tira horizontal** de miniaturas 16:9 de 240px (mobile 180px), `scroll-snap-type: x proximity`, região rolável com rótulo e setas de rolagem (botões 44px, ocultos em touch). 
- Abaixo de ~8 vídeos na prateleira, mostra "+N" como último cartão que expande a prateleira em grade (`aria-expanded`).
- Lazy: prateleiras fora de viewport montam por `IntersectionObserver`; imagens `loading="lazy"`, `decoding="async"`.
- Prateleira vazia por filtro: omitida. Busca ativa: todas as prateleiras filtradas, resultados destacados no título.
- Seleção e menu de contexto funcionam igual (mesmos componentes de cartão).
- Mobile: prateleiras empilhadas; scroll horizontal por toque; cabeçalho do ano 48px.

### 3.5 Cabeçalho da página de vídeos

Linha única: título (Fraunces) **"Vídeos"** com subtítulo "Os vídeos da Rua, 2011-2018" (substitui apenas a cópia), contador `aria-live` ("42 vídeos · 6 pastas"), botões atuais (Nova pasta, Enviar) e toggle de visão. Sem hero grande: a ferramenta vem primeiro, tema fica no tipo e nas molduras.

### 3.6 Player (`VideoPlayerView`) com identidade

Dialoga com o TúnelTube da home (`components/yt/*`), reaproveitando **a linguagem visual** sem acoplar a `ytConfig` (valores fixos "de época" da home; nunca usar nem fingir contadores reais). Recomendação ao frontend: extrair de `YtMetadados/YtDescricao` apenas estilos/pequenos componentes apresentacionais (`props`: título, canal, data, descrição) em vez de importar `YtWatch`.
- **Página**: fundo `--fv-player-bg` em faixa superior atrás do player (largura total, 56.25vw máx. 70vh), resto da página no papel/tema do site. Mobile: player edge-to-edge, sticky no topo ao rolar a descrição (opcional) com `position: sticky; top: var(--tm-navbar-h)`.
- **Player**: elemento `<video controls>` nativo (a11y e controles do sistema); moldura: raio 12px desktop, 0 mobile, sombra `--tm-shadow-lg`. Poster com play central grande (mesmo componente de 3.2, 72px). Erro de carga: mensagem + "Tentar de novo" (copiar padrão do `YtPlayer`: `role="alert"`).
- **Abaixo**: `h1` título (Fraunces `--tm-fs-2xl`); linha do "canal": avatar do remetente (40px) + nome + "Publicado em 14 de junho de 2014 · há 12 anos"; botões à direita: Compartilhar, Baixar (se existir), Mover/Renomear (membro) no menu `…`.
- **Descrição** em caixa `--hx-balao` (raio 12px): meta "42 MB · 3:07", texto com "Mostrar mais" (`aria-expanded`) a partir de 3 linhas.
- **Lateral "Mais desta pasta"** (existente): miniaturas com a mesma moldura reduzida (sem barra falsa, só selo de duração), item atual marcado "Reproduzindo" (`aria-current="true"`). Se o vídeo tem `data_video`, abaixo da lista: chip-link "Mais de 2014" que vai para a visão Por ano rolada àquela prateleira.
- **Ao terminar**: cartão "Assistir a seguir" com o próximo da pasta, contagem de 5s **desligada por padrão** (opt-in, evita autoplay); botão Reproduzir/Cancelar. Opcional; marcar para o dono (seção 8).
- Mobile: lateral vira lista abaixo da descrição, título e canal em coluna.

### 3.7 Preview (`VideoPreviewDialog`) melhorado

- Poster 16:9 grande com a moldura de 3.2 (play central 64px), clique toca **no próprio diálogo** (troca poster por `<video autoplay controls>`, nunca autoplay ao abrir).
- Abaixo: título, "há 12 anos · 3:07 · 42 MB", caminho da pasta (`Vídeos › 2014 › Festas`), descrição curta.
- Ações em linha de 44px: **Abrir página** (primária), Mover, Renomear, Excluir (membro; destrutivo com confirmação existente).
- Navegação entre irmãos: setas `←`/`→` do teclado e botões laterais no desktop ("2 de 9"); troca com `crossfade` curto. Em lote: Esc fecha, foco volta ao cartão de origem.
- Mobile: bottom sheet com poster no topo e ações em largura total; não tocar vídeo no preview mobile (abre a página) para economizar dados, a menos que o usuário toque no play.

### 3.8 Estados vazios e erro

| Contexto | Ícone | Título | Texto | CTA |
|---|---|---|---|---|
| Sem vídeos (geral, membro) | `mdi-filmstrip-off` | "A estante da Rua está vazia" | "Os vídeos da turma ficam guardados aqui, em pastas, por ano ou na ordem que preferir." | Enviar vídeo |
| Sem vídeos (leitor) | idem | "Ainda não gravaram nada por aqui" | "Quando alguém enviar um vídeo, ele aparece aqui." | nenhum |
| Pasta vazia | `mdi-folder-open-outline` | "Esta pasta está vazia" | "Arraste vídeos para cá ou use Mover." | Enviar vídeo (membro) |
| Busca sem resultado | `mdi-video-off-outline` | "Nenhum vídeo com '{q}'" | "Tente o título, o nome de quem enviou ou um ano." | Limpar busca |
| Por ano, sem datas | `mdi-calendar-blank-outline` | "Nenhum vídeo com data ainda" | "Datar os vídeos ajuda a montar a estante por ano." | Editar data (membro) |
| Vídeo não encontrado | `mdi-video-off-outline` | "Vídeo não encontrado" | "Ele pode ter sido movido ou excluído." | Ir para Vídeos (já existe) |
| Erro | `TmErrorState` atual | | | Tentar de novo |

## 4. Hierarquia visual (resumo)

Fotos: 1) conteúdo (grade) 2) capítulo do ano (selo + h2) 3) destaques (colapsáveis, abaixo dos controles) 4) controles. Vídeos: 1) miniaturas/pastas 2) título e contador 3) prateleiras (visão Por ano). Cor temática só em selo, filete e vinheta; texto sempre `--tm-ink` sobre `--tm-paper`/`--tm-paper-2`. Um `h1` por página; anos `h2`; cartões de destaque `h3`.

## 5. Dados que faltam na API (somente o essencial)

1. **`data_video`** (`YYYY-MM-DD|null`) em vídeos: item de `GET /videos`, aceito em `PUT /videos/{uuid}` (como `data_foto`) e em `POST /cadastrar/videos` (opcional). Essencial para "Por ano" e "há X anos" corretos; sem isso a visão Por ano usa `created_at` (data de envio, rotulada "enviado") e fica de baixo valor. Aceitar `de/ate` em `GET /videos` é opcional.
2. **Fotos**: (a) `GET /fotos/neste-dia?limite=12` → fotos com mesmo dia/mês da data de referência e ano anterior ao atual, ordenadas por ano desc: itens iguais a `GET /fotos` + `ano`. (b) `GET /fotos/anos` → `[{ano, total, capa:{uuid,url,thumbnail_url}|null}]` (capa = primeira foto do ano) para "Memórias por ano" sem baixar páginas. Ambos públicos como `GET /fotos`. Fallback sem (b): cartões só com ano e contagem somada de `linha-do-tempo`, sem capa (tile `--hx-ano-suave`).
3. **Autoria no lightbox/vídeo**: `cadastrado_por` com `foto_url` e `slug` (hoje `nome`); no player de vídeo idem. Fallback: iniciais, sem link.
4. **Histórias vinculadas à foto** (já em `vinculos.historias`): acrescentar `capa_url` e `data_historia` ao item para o mini-post; fallback: ícone e título.
5. **Marcados na foto** (`usuarios:[{uuid,nome,foto_url,slug}]` em `GET /fotos/{uuid}/details`): opcional, depende de decisão do dono (8.3); sem isso, o bloco "Marcados" não aparece.
6. **Álbum**: `ano_inicio` e `ano_fim` (de `data_foto`) no item, opcional, para "2013-2015". Fallback: omite.
Tudo retrocompatível, campos novos opcionais. O front trata ausência como degradação silenciosa (bloco omitido, sem erro).

## 6. Acessibilidade (AA) e reduced-motion

- **Contraste**: texto sobre scrim com pior caso de foto branca ≥4.5:1 (scrim base `rgba(0,0,0,.6)` no terço inferior); selo de duração branco sobre `--fv-player-barra` ≥4.5:1; selo de ano branco sobre `--hx-ano-selo`; ações em `--tm-gold-text` sobre papel (AA nos dois temas); anel de foco `--tm-focus` / `#f0c86a` (regra `.tm-immersive`) ≥3:1 contra moldura escura e clara; estado ativo nunca só por cor (ícone preenchido + `aria-pressed`/`aria-current`).
- **Teclado**: toda tira é `role="region"` com `aria-label` e `tabindex="0"` (rolável por setas), com os cartões focáveis; toggle de visão é grupo com `aria-pressed`; Por ano: Tab percorre cabeçalho > cartões da prateleira; "Ver todos" é link. Lightbox: setas, Esc, `I` para o painel (existentes) + `F` alterna filtro antigo (opcional, anunciado em tooltip). Preview de vídeo: `←`/`→`, Esc, foco devolvido.
- **Leitores de tela**: "há 12 anos" dentro de `<time datetime>` com a data completa em texto `sr-only` e `title`; barra falsa e ícones decorativos `aria-hidden`; contadores com texto completo ("132 fotos de 2014"); região `aria-live="polite"` única para o resultado de busca/filtro; imagens de capa com `alt=""` quando o título já está no cartão, com `alt` descritivo apenas no lightbox (usa `titulo` ou "Foto de {data}").
- **Alvos** 44px; zoom 200% sem rolagem horizontal da página (só tiras).
- **Reduced-motion** (`prefers-reduced-motion` e `html[data-reduzir="1"]`): sem parallax, sem entrada em cascata, sem tilt/empilhamento animado, sem rolagem suave nas âncoras de ano (salto instantâneo), crossfade do preview vira corte; filtro antigo, moldura, vinheta e barra falsa são estáticos e permanecem. Toda informação visível sem animação.
- **Performance**: sem `backdrop-filter` além do cabeçalho sticky; `filter` só quando o toggle está ligado e em imagens visíveis; miniaturas `thumbnail_url ?? url` com `width/height` ou `aspect-ratio`; tiras montadas por `IntersectionObserver`; destaques só buscam dados na primeira visita à página e depois ficam em cache de sessão; nenhuma mídia pesada (vídeo/áudio) carregada antes do clique.
- **Impressão**: não se aplica (fora do escopo).

## 7. Momentos de animação (motion-specialist)

Apenas `transform`/`opacity`; todos desligados com reduced-motion. Preservar as animações existentes (barra de seleção, transição de visão, lightbox, menu, migalha, esqueleto).

| # | Momento | Intenção | Duração |
|---|---|---|---|
| 1 | Destaques entrando | Tiras surgem com fade + subida 12px, cartões em cascata (máx. 6) | 350ms, stagger 50ms |
| 2 | Cartão "Memórias por ano" ao clicar | Pulso do selo do ano e rolagem suave até o capítulo; o `h2` de destino faz pulso de 400ms | rolagem nativa, pulso 400ms |
| 3 | Capítulo de ano entra no viewport | Selo "pina" 1.1→1; filete cresce de 0 a 100% | 450ms |
| 4 | Scrubber: rótulo do ano | Rótulo desliza junto ao polegar, troca de ano com crossfade | 150ms |
| 5 | Capa de álbum hover/foco | Eleva 2px; fotos empilhadas se abrem 2px | 200ms |
| 6 | Álbuns entrando | Cascata de capas | 300ms, stagger 40ms |
| 7 | Toggle filtro antigo | Crossfade da cor/vinheta nas imagens visíveis (opacidade da camada de vinheta + filtro) | 250ms |
| 8 | Lightbox: abre painel "post" | Painel desliza (existente); itens do post entram em fade escalonado | 250ms, stagger 40ms |
| 9 | Lightbox: "Ver original" | Crossfade entre filtrada e original | 150ms |
| 10 | Miniatura de vídeo hover/foco | Play central escala .9→1 e barra falsa sobe 4px; linha de progresso dourada "varre" de 0 a 100% em hover (decorativa) | 200ms / 1200ms |
| 11 | Prateleira Por ano entra | Cartões deslizam 16px em fade | 350ms, stagger 50ms |
| 12 | Troca de visão (Grade/Lista/Por ano) | Transição `visao` existente estendida para a terceira visão | 250ms |
| 13 | Preview de vídeo: abrir e navegar | Poster escala .96→1 + fade; troca ←/→ com crossfade e deslocamento 24px | 250ms / 200ms |
| 14 | Player: entrada da página | Faixa escura expande da altura 0 e o player faz fade | 400ms |
| 15 | "Assistir a seguir" | Cartão sobe 12px; anel de contagem preenche (se o dono aprovar) | 300ms / 5000ms |
| 16 | Estados vazios | Moldura/ícone com leve entrada (fade + rotação -6° → -3°) | 400ms |
| 17 | Skeletons | Pulso de brilho (existente) | 1400ms loop |

## 8. Confirmar com o dono antes de implementar

1. **Filtro antigo**: padrão desligado (recomendado) ou ligado nas fotos com opção de desligar? E vale também para vídeos (não recomendado)?
2. **Datar vídeos**: aprovar o campo `data_video` (e o cadastro/edição dele) para viabilizar a visão "Por ano"; sem ele a visão fica adiada.
3. **Marcados em fotos**: criar marcação de pessoas por foto (novo contrato) ou manter só autor + histórias vinculadas?
Itens menores (decisão do front/UX, reversíveis): visão "Por ano" em vez de "fitas"; "Assistir a seguir" com contagem desligada; nomes de seção ("Neste dia", "Memórias por ano").

## 9. Ordem sugerida

1. Backend: `data_video`, `fotos/anos`, `fotos/neste-dia`, `cadastrado_por` enriquecido, extras de `vinculos.historias`. 2. Tokens `.fv-era` + helper `tempoRelativo` (já previsto na Esquina) + capítulo de ano e estados vazios. 3. Toggle de filtro antigo + capas de álbum + `AlbumView`. 4. Destaques (neste dia / por ano). 5. Moldura de player + selo de duração + visão Por ano. 6. Lightbox "post" e preview novo. 7. Player da página. 8. Motion (seção 7). 9. Revisão AA, teclado e teste em 360px.
