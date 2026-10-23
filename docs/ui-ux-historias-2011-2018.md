# Histórias 2011-2018: especificação de UI/UX (v2)

Escopo: `HistoriasView.vue`, `HistoriaShowView.vue`, `HistoriaCard.vue` e novos componentes em `components/historias/`. Mantidos: rotas, filtros, URL-state (`?q&tipo&membro&ordem&visao`), galerias, lembranças (contrato), favoritos, tamanho de texto, impressão.
Implementação: frontend-specialist. Animação: motion-specialist (seção 6).
Decisões do dono incorporadas: direção "rede social da época"; ordem cronológica 2011→2018 como padrão; capítulos extras "Antes da Rua", "Depois da Rua", "Sem data".

## 1. Conceito: "Esquina", a rede social fictícia da Rua

Direção única: **rolar o feed de uma rede social de 2011-2018, só que da nossa turma**. Como a home virou o "TúnelTube", Histórias vira a **Esquina** (nome provisório, ver seção 8): uma rede própria onde cada história é um **post** publicado pelos membros que a viveram, com foto de perfil, data, curtida ("Guardar na memória"), comentários (lembranças), marcação de pessoas e "check-in" de lugar/ano.

O que evocamos (linguagem, não marca):
- Feed em coluna única de posts em cartões brancos/escuros com cabeçalho (avatar redondo, nome, "há 7 anos", ícone de visibilidade) e rodapé de ações (Curtir, Comentar, Compartilhar) com contadores.
- Perfil/capa estilo "linha do tempo" (a era do Facebook Timeline, 2012) com a **faixa de anos** à direita e marcos "Entrou na Rua", "Primeira história do ano".
- Fotos com **moldura quadrada de filtro antigo** (vinheta suave, leve dessaturação quente, borda branca fina), a cara dos primeiros filtros de 2012-2014, aplicado via CSS na capa (sem processar imagem).
- Datas amigáveis: "há 7 anos" + data completa no tooltip e em `<time>`: "14 de junho de 2014".
- Mural de comentários, "Fulano e mais 3 pessoas estavam com você" (marcação), distintivo "check-in" (`mdi-map-marker`) usando o ano/época como local simbólico.
- Cabeçalho da rede: barra superior fina com o nome da rede, busca e sino (já existem na navbar do site; não duplicar, apenas a faixa de capa).

O que NÃO fazemos: logos, azul/verde de marca, ícones oficiais, nomes (Facebook, Instagram, Orkut), tipografia proprietária. Mantemos paleta e fontes do site: papel quente, ouro como cor de ação (no lugar do "azul de rede social"), Fraunces nos títulos, Inter no corpo. A referência é de **estrutura e interação** (post, curtir, comentar), não visual de marca. Por isso não é pastiche.

Por quê: o grupo viveu essa era dentro dessas redes; "rolar o feed" é o gesto que todos reconhecem, e o conteúdo real já mapeia 1:1 (história = post, membros = autores/marcados, favorito = curtir, lembrança = comentário, `fotos_count` = álbum anexo, `data_historia` = data do post).

Regra de ouro: o retrô está na **estrutura e nos microdetalhes** (avatar, "há 7 anos", ações, filtro de foto); o site continua legível e moderno.

## 2. Lista de histórias: o feed da Esquina (`HistoriasView`)

### Estrutura (topo para base)
1. **Capa da rede (faixa de abertura)** 160px mobile / 240px desktop, sobre o fundo atual: mosaico/gradiente com a frase "Esquina · 2011 — 2018", subtítulo "A rede de quem viveu a Rua". Avatar grande circular central/esquerdo com o selo da Rua (usar `RuaOrbita`/logo atual se existir) e linha "13 membros · N histórias" (contador `aria-live` mantido). Botão "Contar uma história" (vira "Nova publicação" com ícone, mesmo destino) e FAB no mobile.
2. **Compositor falso (somente membros)**: caixa estilo "No que você está pensando?" que na verdade é um link para `/historias/criar`: avatar do usuário + "Conte uma história da Rua…". Não-membro: oculto. Baixa carga, comunica a metáfora.
3. **Régua de anos sticky** (`HistoriaRegua`), logo abaixo da navbar (`top: var(--tm-navbar-h)`):
   - Chips `Antes da Rua · 2011 · 2012 · … · 2018 · Depois da Rua · Sem data`. Os três extras só aparecem se houver histórias neles; os anos 2011-2018 aparecem sempre (desabilitados se vazios após filtro, `aria-disabled`), com contagem pequena.
   - Marca ativa por scroll-spy (IntersectionObserver); clique rola até o capítulo e foca o `h2`.
   - Desktop: horizontal, centralizada. Mobile: faixa rolável com scroll-snap, marca ativa centralizada, alvos de 44px.
   - Semântica: `<nav aria-label="Anos">` com links `#ano-2014`; ativo com `aria-current="true"`.
   - Leitura estilo "Pular para o ano" do Timeline, com visual de abas arredondadas.
4. **Filtros (mantidos)**: busca, membro, ordenar, chips de tipo, toggle de visão. Num cartão "barra de pesquisa" no topo do feed. Mobile: busca visível + botão "Filtros" abre bottom sheet. A visão "Cartões" (grade) permanece como alternativa compacta; **"Feed" (capítulos por ano) é o padrão** quando há datas. `?visao=cards` continua válido.
5. **Feed por capítulos**: ordem **2011→2018** (padrão). `ordem=recentes` inverte tudo (capítulos e posts, 2018→2011, e extras: "Depois" no topo, "Antes" no fim). `ordem=az` ignora capítulos e lista plana A-Z. Capítulos extras: "Antes da Rua" (< 2011) vem antes de 2011; "Depois da Rua" (> 2018) depois de 2018; "Sem data" por último em qualquer ordem.

### Capítulo (`HistoriaAno`)
- Marco de timeline: `h2` "2014" em Fraunces com selo circular de ano, subtítulo "7 histórias · 41 fotos", e uma linha vertical (a "linha do tempo" central da era 2012) ligando os posts com um ponto dourado por post.
- Extras com texto próprio: "Antes da Rua (até 2010)", "Depois da Rua (2019 em diante)", "Sem data (ninguém lembra quando)". Tom carinhoso, mesmo layout.
- Cor por ano: apenas acento decorativo (selo, linha, vinheta da foto), 8 tons que esfriam de 2011 a 2018; extras usam neutro ouro.
- Desktop ≥960px: feed em **coluna única de 680px** à direita da linha, com a **coluna lateral sticky à esquerda** (resumo do ano: número de posts, membros mais presentes, foto de perfil empilhadas). 1200px+ também mostra à direita "Aniversariantes do mês" (`AniversariantesMes` já existe) opcional. Mobile: coluna única 100%, sem lateral.
- Destaque do ano: o primeiro post `Principal` ganha capa 16:9 maior e selo "Marco do ano".

### Post (`HistoriaCard`, revisado) ← peça central
Estrutura de cima para baixo, tudo dentro de um cartão (`--tm-paper`, raio `--tm-radius-md`, borda `--tm-border`, sombra `--tm-shadow-sm`):
1. **Cabeçalho do post**: avatar do primeiro autor (40px, `foto_url` ou iniciais), nome do autor + " estava com " + até 2 nomes + "e mais N" (link para `/membros/:slug` em cada nome), embaixo `há 7 anos · 🌐` (ícone `mdi-earth` decorativo, `aria-hidden`) como `<time datetime>` com `title`/tooltip "14 de junho de 2014". Menu de ações (editar/apagar) à direita, 44px.
2. **Texto**: título em Fraunces (1 linha de destaque, `h3`; realce de busca `mark` mantido) e `descricao_curta` em 3 linhas com "Ver mais" que leva à página (o card inteiro é link).
3. **Mídia**: capa `aspect-ratio 1/1` (feed) ou 16/10, com **filtro de foto antigo**: `filter: sepia(.18) saturate(1.08) contrast(1.04)` + vinheta via `box-shadow: inset` / gradiente radial; borda branca interna 6px opcional. `loading="lazy"`, `decoding="async"`, `aspect-ratio` fixo. Sem capa: tile gradiente `--hx-ano` com ícone de livro (nunca vazio). Selo no canto: tipo (Principal/Paralela/Pessoal) e, se houver mais de 1 foto, contador "+N" tipo álbum.
4. **Barra de reações**: à esquerda, "❤ 12 guardaram" (derivado de contador, ver seção 7) e à direita "4 comentários · 9 fotos". Textos em Inter 14px.
5. **Ações**: três botões de largura igual com ícone + rótulo, separados por borda fina: **Guardar** (favorito, estado ativo dourado preenchido; chamada direta ao endpoint, estado otimista como na página), **Lembrar** (vai para o bloco de lembranças da página: `#lembrancas`), **Compartilhar** (Web Share/copiar link). O card inteiro continua sendo o link principal; os botões ficam fora do `router-link` (evitar link aninhado), usando overlay do link por pseudo-elemento.
6. Estados: hover eleva 2px; `focus-visible` com anel `#f0c86a` (regra `.tm-immersive`); botão ativo `aria-pressed`.
- Alvo ≥44px nas ações; altura mínima do card 96px (modo compacto sem mídia).

### Estados
- **Carregando**: 3 esqueletos de post (avatar círculo, 2 linhas, bloco quadrado, barra de ações), sob um cabeçalho de ano esqueleto; `aria-busy`; mesmas medidas (sem layout shift).
- **Vazio sem filtro**: "A Esquina ainda está quieta. Seja o primeiro a publicar." + CTA para membros.
- **Vazio com filtro**: `TmEmptyState` atual com "Limpar busca"; régua toda desabilitada.
- **Erro**: `TmErrorState` atual.
- Capítulo sem posts visíveis (filtro): omitido, nunca cabeçalho vazio.
- Carregamento incremental: renderizar o primeiro ano visível e os demais conforme scroll (virtualização leve por capítulo com IntersectionObserver) se a lista passar de ~60 posts.

### Responsivo
- ≥1200: coluna central 680px + lateral esquerda sticky (resumo do ano) + opcional direita.
- 960-1199: central 680px + lateral esquerda estreita.
- 600-959: coluna única 100% (máx. 680px, centralizada), régua rolável.
- <600: coluna única edge-to-edge (posts sem raio nas laterais, separados por faixa de 8px, como feed mobile), filtros em bottom sheet, FAB, sem lateral, selo de ano compacto.

## 3. Página da história: o post aberto (`HistoriaShowView`)

Hoje: painel único de 900px. Passa a ser **capa de perfil + post completo + comentários**, como abrir um post na rede.

### Hero (`HistoriaHero`) ← "capa de timeline"
- Altura 48vh (mín 300px, máx 480px) desktop; 36vh (mín 240px) mobile. Capa em largura total, `fetchpriority="high"`, filtro de foto antigo (mesmo do card) + scrim inferior para AA.
- Sobre a capa: migalha (Histórias / título), selo grande do ano ("2014") e, em tipografia de rede, **"há 7 anos"** com a data completa abaixo: "14 de junho de 2014".
- Sem capa: fundo `--hx-ano` com grão e o número do ano gigante.
- Mobile: título abaixo da capa (fora do scrim) para legibilidade; capa menor.

### Cabeçalho do post (sobre a folha)
Folha de leitura (`--tm-paper`) de 680px, sobrepondo a capa em -40px:
- Linha de autoria: avatares sobrepostos (40px, até 4) + "**Fulano** estava com **Ciclana** e mais 3" (links para `{ name:'membro', params:{ slug: chaveMembro(u) } }`), `<time>` "há 7 anos" + tooltip com data completa.
- Check-in: chip `mdi-map-marker` "na Rua · 2014" (decorativo/semântico, sem local real; se a API ganhar `local`, mostrar).
- Chips: tipo, tempo de leitura.
- `h1` título (Fraunces, `--tm-fs-3xl`), descrição curta.

### Membros presentes: "Quem estava lá"
- Seção com `h2`, grade de avatares 56px com nome embaixo; cada um é link para `/membros/:slug`, alvo 44px+, foco visível. Estilo "marcados na foto". Mobile: linha rolável. Máx. 8 + "+N" expansível. Texto "Marcados nesta história (7)".

### Barra de reações e ações (padrão rede social)
- Linha de contadores: "❤ 12 guardaram · 4 comentários · 9 fotos, 2 vídeos".
- Barra de 3 ações grandes: **Guardar** (favorito; "Guardada na memória" mantido como estado ativo), **Lembrar** (rola/foca o campo de comentário), **Compartilhar**. Secundárias em menu/ícones: A-/A+, imprimir, editar (membro), galerias.
- Sticky compacta (versão reduzida: coração, comentar, compartilhar, progresso de leitura) aparece após o cabeçalho sair da tela.

### Leitura
- Conteúdo em `.tm-prose`, 68ch, Inter com tamanho ajustável 16-22px (mantém A-/A+), títulos Fraunces. Índice "Nesta história" mantido (≥3 títulos): lateral sticky ≥1200px, recolhido no mobile. Imagens embutidas com moldura de foto (borda 6px clara + leve vinheta + `figcaption` se houver `alt`). Sem capitular nem texto decorativo que prejudique leitura.

### Álbum e mídia integrados
- Seção "Álbum: N fotos" logo após o texto: mosaico estilo álbum de rede (1 grande + 4 pequenas, "+N" na última) com miniaturas (`thumbnail_url ?? url`), filtro antigo, data da foto no tooltip. Clique abre `GaleriaFotos` (começando no item clicado se o componente aceitar; senão primeira).
- Vídeos: miniatura com `poster_url` e botão play, rótulo de duração se existir. Músicas: faixa tipo "tocando agora" (ícone, título), abre `GaleriaMusicas`.
- Sem mídia: seção não aparece. Lazy em tudo, máx. 5 miniaturas renderizadas no mosaico.

### Lembranças como comentários (`LembrancasSecao`, reestilizada)
- Título "Comentários" com contagem (`lembrancas_count`); cada item em balão (avatar 32px do autor, nome em negrito, texto, "há 3 dias · Excluir" quando `pode_excluir`). Campo de nova lembrança no topo fixo ao bloco: avatar do usuário + "Escreva uma lembrança…" + botão "Publicar". Contrato e lógica inalterados (rótulo "Lembrança" pode continuar em `aria-label` para coerência com o resto do site: confirmar).
- Paginação existente vira "Ver comentários anteriores" no padrão rede.
- Empty: "Ninguém lembrou de nada ainda. Seja o primeiro."

### Anterior/próxima na linha do tempo (correção)
- **Ordem cronológica crescente** (data asc; sem data por último, "Antes" e "Depois" nos extremos). Hoje o código usa a ordem da API (data desc), então "Anterior" aponta para a mais nova: corrigir ordenando a lista por `data_historia` asc.
- Rodapé "Continue rolando a Esquina": dois cartões de post mini (capa pequena, título, "há 8 anos") com "← Antes" e "Depois →". Se o vizinho for de outro ano, faixa "Virando o ano: 2014 → 2015".
- Mini-régua dos anos com o ano atual marcado, linkando `/historias#ano-AAAA`.
- Mobile: cartões empilhados, "Depois" primeiro. Sem atalho global de setas.

### Estados
- Carregando: hero esqueleto + cabeçalho de post esqueleto + parágrafos; altura reservada.
- 404 / 403 / erro: `TmEmptyState` / `TmErrorState` atuais dentro da folha, sem hero.
- Capa falha: fundo `--hx-ano` + grão (`@error`).

## 4. Tokens, tipografia, texturas, acessibilidade

### Tokens novos (escopados em `.historias-era` nas duas views; nada em `:root`)
- `--hx-feed-w: 680px`; `--hx-gap-post: 16px` (8px mobile).
- `--hx-filtro-foto: sepia(.18) saturate(1.08) contrast(1.04)`; vinheta `--hx-vinheta: radial-gradient(ellipse at center, transparent 55%, rgba(0,0,0,.28))`.
- `--hx-ano-h` por capítulo (hues 38, 30, 22, 12, 355, 340, 320, 300 para 2011-2018; extras neutros 45) e derivados `--hx-ano-cor`, `--hx-ano-suave`. Só acento (selo, linha, placeholder), nunca fundo de texto.
- `--hx-acao-ativa: var(--tm-gold)`; texto de ação usa `--tm-gold-text` (AA).
- `--hx-balao: var(--tm-paper-2)` para comentários.
- `--hx-hero-h: clamp(240px, 48vh, 480px)`.
- Reaproveitar `--tm-*` (espaço, raio, sombra, duração) sem alteração.

### Tipografia
Fraunces: `h1`, `h2` do ano, título do post. Inter: autoria, "há 7 anos", ações, comentários (14-16px, contadores 13px mínimo). Sem fonte nova. Números de ano no selo: Fraunces 800 tabular.

### Texturas (leves)
- Grão SVG `feTurbulence` inline (<1 KB) com opacity .06-.1 apenas no hero sem capa e no selo; não animado, nunca sobre texto de leitura.
- Vinheta e filtro de foto: CSS puro. Evitar `filter` pesado: aplicar só em capas visíveis (imagens pequenas, thumbnails); `will-change` não usar.
- `backdrop-filter` apenas na barra sticky compacta (1 elemento).

### Acessibilidade
- Contraste AA: texto sobre scrim do hero (pior caso capa branca), `--tm-gold-text` sobre papel, estados ativos das ações (não depender só de cor: ícone preenchido + `aria-pressed`).
- Foco visível `#f0c86a` em tudo imersivo; régua e cards sem recorte por `overflow`.
- Teclado: régua = links; ordem Tab: régua > filtros > posts (avatar/nomes > link do post > Guardar > Lembrar > Compartilhar > menu). Guardar com `aria-pressed`; Esc fecha bottom sheet.
- Leitores de tela: um `h1`; anos `h2`; posts `h3`; "há 7 anos" dentro de `<time datetime>` com data completa em texto oculto (`sr-only`), não só tooltip; emojis/ícones decorativos `aria-hidden`; contadores com rótulo completo ("12 pessoas guardaram"); lista de posts `ul/li` (ou `role="feed"` só se houver carga incremental, com `aria-busy`).
- `prefers-reduced-motion` e `html[data-reduzir="1"]`: sem parallax, sem revelação por scroll (conteúdo visível), scroll instantâneo na régua, sem pop do coração; filtro/vinheta estáticos permanecem.
- Alvos 44px; zoom 200% sem scroll horizontal (exceto régua e tiras de mídia, roláveis por teclado: `tabindex=0`, `role="region"` com rótulo).
- Impressão: sem hero, filtros, régua nem ações; só título, autores, data completa e texto (regras `.no-print` atuais).

## 5. Datas "há X anos"
- Regra: ≥1 ano "há N anos"; <1 ano "há N meses"/"há N dias"; hoje "hoje". Hoje (2026), 2018 = "há 8 anos", 2011 = "há 15 anos". O texto relativo muda sem recarregar, por isso **nunca é a única fonte**: `<time datetime="YYYY-MM-DD">` + data completa ("14 de junho de 2014") em tooltip e `sr-only`. Um helper em `useFormat` (`tempoRelativo`) para o frontend-specialist; usar `Intl.RelativeTimeFormat('pt-BR')`.
- Data só com ano/mês: mostrar o que existe ("junho de 2014").

## 6. Momentos de animação (motion-specialist)
Todos desligados com reduced-motion; apenas `transform`/`opacity`.

| # | Momento | Intenção | Duração |
|---|---------|----------|---------|
| 1 | Abertura da capa da rede e avatar | Entrada tipo "perfil carregando": avatar escala .9→1, texto sobe 12px | 500ms, stagger 80ms |
| 2 | Régua de anos: troca do ativo | Pílula de destaque desliza até o novo ano | 250ms |
| 3 | Clique na régua | Scroll suave + pulso no selo do ano | scroll nativo, pulso 400ms |
| 4 | Marco de ano entra no viewport | Selo "pina" (escala 1.1→1) e linha da timeline desenha de cima para baixo | 450ms |
| 5 | Posts entrando no scroll | Fade + subida 16px (como carregando mais do feed), stagger 60ms, máx. 4 por lote | 400ms |
| 6 | Hover/foco do post | Eleva 2px, sombra cresce | 200ms |
| 7 | Guardar (curtir) | Coração pop 1→1.3→1 + 6 partículas mínimas; contador sobe (roda o número) | 400ms |
| 8 | Comentar / publicar lembrança | Balão novo desliza e expande; campo limpa | 300ms |
| 9 | Hero da história | Capa com zoom lento 1.05→1 (uma vez); selo do ano e "há 7 anos" aparecem em fade | zoom 1200ms, textos 350ms |
| 10 | Folha sobe sobre o hero | Parallax leve da capa (0.5x), só desktop, CSS `animation-timeline` com fallback estático | contínuo |
| 11 | Mosaico do álbum | Miniaturas entram em cascata | 350ms, stagger 50ms |
| 12 | Virar o ano (anterior/próxima) | Faixa "2014 → 2015" desliza, transição de página existente | 500ms / 250ms |
| 13 | Skeleton | Pulso suave de brilho | 1400ms loop (parado em reduced-motion) |

## 7. Dados que faltam na API (apenas o essencial)
1. **Capa na listagem**: `capa_url` (+ `capa_thumbnail_url`) em `GET /historias/resumo`, igual ao `/public/historias/recentes`. Sem isso o post não tem imagem. Ideal futuro: `capa_foto_uuid` escolhível.
2. **Capa no detalhe**: `capa` em `GET /historias/{uuid}` (fallback no front: primeiro item de `fotos_midia`).
3. **Autores/marcados**: `usuarios[]` com `foto_url` e o campo que `chaveMembro` usa (slug), para avatares reais sem N+1. Se existir ordem de autoria (autor principal vs marcados), expor `papel: "autor"|"marcado"`; senão considerar o primeiro como autor.
4. **Contagens sociais no resumo**: `favoritos_count` e `lembrancas_count` em `/historias/resumo` (hoje só `lembrancas_count` no detalhe e `favoritado` no detalhe); e `favoritado` no resumo para o botão Guardar funcionar no feed sem abrir a página.
5. `data_foto` e `thumbnail_url` em `fotos_midia`.
6. Opcional: `local` (texto curto) na história para o check-in real.
Se (4) atrasar, o card oculta a linha de reações e mostra só mídia/data (degradação segura).

## 8. Confirmar antes de implementar
- Nome da rede: **Esquina** (provisório; alternativas "Rua Social", "Calçada"). Sem relação com marcas.
- "Curtir" vira "Guardar" (já existe como "Guardar na memória") e "Comentar" vira "Lembrar": manter os termos do site ou usar "Curtir/Comentar" literais da era? Recomendação: manter ícone de coração + rótulos do site.
- Histórias "Pessoal" aparecem no feed com selo próprio ou ficam só em filtro?
- Autoria: definir quem é o autor do post (primeiro de `usuarios` ou campo explícito).
- Itens (4) da API: contagens sociais no resumo.
- Aprovar hero menor (48vh) com folha de 680px, substituindo o painel de 900px.

## 9. Ordem sugerida
1. Backend: `capa_url`, `foto_url`/slug dos usuários, contagens e `favoritado` no resumo. 2. Tokens `.historias-era` + helper `tempoRelativo` + `HistoriaCard` (post). 3. Lista em capítulos com régua e extras. 4. Hero + cabeçalho de post + membros. 5. Álbum, comentários, navegação asc. 6. Motion (seção 6). 7. Revisão de contraste/teclado e teste em 360px.
