# Home v2: "A fita de 2011" (especificação de UI/UX)

Escopo: `views/HomeView.vue` e `components/yt/*` (sugestão: renomear a pasta para `components/home/`, ver seção 10). Sem código de componente neste documento.
Implementação: frontend-specialist. Animação: motion-specialist (seção 7, GSAP já está no projeto via `useTransicao`).
Referências do projeto: `assets/tokens.css` (`--tm-*`), `assets/historias-era.css` (`--hx-*`), `RuaOrbita.vue`, `ARuaView.vue`, `AuthLayout.vue`, `docs/ui-ux-historias-2011-2018.md`.

## 1. Diagnóstico (screenshots da home atual e de /a-rua, Edge headless 1440x900)

Por que destoa:
- A home é uma **caixa cinza-clara de 1000px com Arial 13px, links azuis e botão vermelho** flutuando num fundo quase preto. É o único lugar do site com outra paleta, outra fonte, outra densidade e outro raio (cantos retos). A navbar do site (escura, dourada, Fraunces) e a caixa parecem duas páginas coladas.
- O resto do site respira: A Rua tem um cartão escuro de 48px de raio, título Fraunces 4rem com "amigos." em dourado, esfera com anéis. A home tem 12 elementos de interface por centímetro quadrado (abas Vídeos/Categorias/Canais, busca, avaliações em estrelas, "Não gostei", rodapé "Ajuda Sobre Termos").
- Boa parte do retrô é **clone de interface genérica** (cabeçalho, busca, polegares), que não diz nada sobre a Rua. O que é nosso (Zé Paulo, "Tudo começa aqui", o avatar, o vídeo) fica pequeno no meio.
- A entrada não tem narrativa: abre direto numa página pronta, sem a sensação de "voltar no tempo" que o nome do projeto promete.
- A faixa "Isto não é o YouTube" confessa o desconforto: o clone precisa de desculpa.

O que funciona e fica: **o primeiro vídeo do canal do Zé Paulo como começo da história**, o contador de visualizações, a barra de progresso com tempo, os comentários de amigos, a lista "continue assistindo" como mapa do site, e o tom "é só o começo".

## 2. Conceito: o vídeo é uma fita guardada, não uma página do YouTube

Direção única: **a home é uma sala escura onde o primeiro vídeo do Zé Paulo é projetado**. Entra-se por um túnel (hoje, 2026, descendo até 2011), o vídeo acende no centro e, quando acaba (ou quando a pessoa decide), a sala vira porta para a Esquina, A Rua, Fotos e Vídeos.

O retrô passa a ser **sutil e de dentro do vídeo**, não da moldura:
- Fica dentro da imagem: grão, vinheta e dessaturação quente do filtro de foto antigo (`--hx-filtro-foto`, `--hx-vinheta`, `--hx-grao`, os mesmos da Esquina), mais um **carimbo de data de câmera** ("12 03 2011", dígitos âmbar, canto inferior direito, só antes do play e nos primeiros 3 s).
- Fica no texto: "12.847 visualizações", "Publicado em 12 de março de 2011", "há 15 anos", os 3 comentários curtos.
- Fica no controle: barra de progresso fina com marcador redondo e tempo `0:00 / 2:14`.
- **Todo o resto usa a linguagem do site**: fundo escuro quente, dourado como cor de ação, Fraunces nos títulos, Inter no corpo, raios `--tm-radius-md/lg`, espaçamento `--tm-space-*`, foco `#f0c86a` (`.tm-immersive`).

Regra de ouro: se o elemento não existiria no site sem o conceito, ele sai.

## 3. Mapa de decisões: manter, adaptar, remover

| Elemento atual | Decisão | Como fica |
|---|---|---|
| Cabeçalho TúnelTube (logo, busca, abas Vídeos/Categorias/Canais, "Entrar", idioma) | Remover | A navbar do site já cumpre o papel |
| Fundo cinza, Arial 13px, azul #0033CC, vermelho #CC181E | Remover | Tokens `--tm-*`, Fraunces/Inter |
| Moldura de 1000px com cantos retos | Remover | Palco em tela cheia (seção 4) |
| Título "Tudo começa aqui" | Manter, elevar | Fraunces `--tm-fs-3xl`, sobre o palco |
| Player com play central e avatar | Adaptar | Play dourado 88px, poster com filtro antigo, controles no estilo do site |
| Barra de progresso + tempo | Manter (retrô sutil) | Barra dourada fina, buffer em ouro 30%, marcador redondo |
| Avatar + nome do canal | Manter | Chip "Canal do Zé Paulo", avatar com borda dourada |
| Botão "Inscrever-se" vermelho | Remover | O CTA real é entrar na Rua (seção 5) |
| Contador "12.847 visualizações" | Manter (retrô) | Inter 600, `tabular-nums`, conta de 0 a 12.847 na entrada |
| Gostei (1198) / Não gostei / avaliações em estrelas | Remover | Ruído de YouTube. Opcional: "1.198 pessoas guardaram", texto estático |
| Compartilhar | Adaptar | Ícone no cabeçalho do vídeo (copiar link / Web Share) |
| Descrição em caixa cinza + "Mostrar mais" + tags | Adaptar | 2 linhas em Inter 16px, sem tags, sem "mostrar mais" (texto curto) |
| Comentários | Manter (retrô, lembrança) | 3 balões no estilo da Esquina (`--hx-balao`), link para as lembranças |
| Lista "Vídeos relacionados" | Transformar | "Continue assistindo": as 4 portas (seção 5) |
| Rodapé "Ajuda Sobre Termos", © 2009 | Remover | Rodapé do site se existir; links "Em memória" e "Atualizações" ficam como texto discreto |
| Faixa "Isto não é o YouTube" | Remover | Não há mais clone a desculpar |
| Data "12 de março de 2009" | **Corrigir para 12 de março de 2011** | O projeto é 2011-2018. 2009 quebra a coerência com a Esquina e com a narrativa |

## 4. Layout

### Princípios
- Seção única `tm-immersive`, `min-height: 100dvh` menos o player de música (`var(--tm-player-h, 0px)`), como `AuthLayout`.
- **Sala escura sempre**, mesmo com tema claro: escopo próprio (`.home-cinema`) fixando os valores do tema escuro (`--tm-paper #1e1a12`, `--tm-paper-2 #2a2418`, `--tm-ink #f1e8d2`, `--tm-ink-muted #b9ae93`, `--tm-gold-text #e0be5a`). Motivo: uma sala de projeção clara não faz sentido, e o contraste fica garantido.
- Fundo: `--tm-paper` com um brilho radial dourado (8-12% de opacidade) atrás do vídeo e o grão `--hx-grao` a 4% de opacidade. Sem imagem pesada.
- Largura máxima de conteúdo 1200px, centralizado, como `.rua-card`.

### Desktop (>= 1100px), referência 1440x900
```
[ navbar do site 60px ]
|  eyebrow:  CANAL DO ZÉ PAULO · DESDE 2011                                |
|  h1 Tudo começa aqui (Fraunces)                         |  CONTINUE        |
|  +----------------------------------------------+       |  ASSISTINDO      |
|  |                                              |       |  [Esquina    ]   |
|  |   PLAYER 16:9 (poster + filtro antigo)       |       |  [A Rua      ]   |
|  |   play dourado 88px, carimbo 12 03 2011      |       |  [Fotos      ]   |
|  |   barra de progresso  0:00 / 2:14  vol  FS   |       |  [Vídeos     ]   |
|  +----------------------------------------------+       |  (cards 300px)   |
|  (avatar) Zé Paulo   12.847 visualizações · há 15 anos  |                  |
|  descrição 2 linhas          [CTA primário] [CTA sec.]  |                  |
|  --- Lembranças: 3 balões de comentários ---------------|                  |
```
- Coluna principal: largura `min(880px, 100%)`; coluna lateral fixa 300px; vão `--tm-space-6`. Player limitado em altura: largura = `min(880px, (100dvh - 60px - 220px) * 16/9)` para que player + título + título do canal caibam sem rolar em 1440x900 (player ~800x450).
- Lateral "Continue assistindo" sticky (`top: calc(var(--tm-navbar-h) + 16px)`).
- Abaixo da dobra: comentários e rodapé discreto.

### Tablet (600-1099px)
- Coluna única de 720px centralizada. O player ocupa a largura toda da coluna. "Continue assistindo" vira **grade 2x2** abaixo da descrição e antes dos comentários.

### Mobile (< 600px), mobile-first
- Player edge-to-edge (sem raio nas laterais), 16:9, logo abaixo da navbar, para o vídeo ser o primeiro objeto da tela.
- Ordem: player, título (h1 `--tm-fs-2xl`), linha do canal (avatar 40px + "Zé Paulo" + "12.847 visualizações · há 15 anos"), descrição (2 linhas), **CTA primário largura total 48px**, CTA secundário em texto, "Continue assistindo" em **lista vertical de cards de 72px** (ícone/miniatura 72x72 + título + subtítulo), comentários (mostra 1, botão "Ver mais lembranças").
- Nenhum CTA fixo no rodapé (colidiria com o player de música `--tm-player-h`).
- Contador do túnel escala por `clamp`. Alvos de toque >= 44px.

### Hierarquia visual (do mais forte ao mais fraco)
1. Vídeo (play dourado, único elemento com brilho).
2. Título "Tudo começa aqui".
3. CTA primário dourado (único botão preenchido da tela).
4. Portas "Continue assistindo".
5. Metadados (views, data).
6. Comentários e rodapé.

### Componentes de estilo (medidas)
- Player: raio `--tm-radius-md` (16px) desktop, 0 no mobile; borda `1px solid var(--tm-border)`; sombra `--tm-shadow-lg`; fundo `#000`.
- Play central: círculo 88px (72px mobile), fundo `--tm-gold`, ícone `mdi-play` em `--tm-ink` escuro (#2a2112) para contraste >= 7:1; anel externo 1px dourado a 40%, pulsando devagar enquanto parado (seção 7).
- Controles: barra inferior com gradiente de 0 a 70% preto, altura 56px (64px toque); botões 44px; autoesconde 2,5 s após play com mouse parado, volta com foco/movimento/toque. Nunca esconder com foco de teclado dentro.
- Barra de progresso: 4px (8px em hover/foco), buffer `rgba(224,190,90,.3)`, progresso `--tm-gold`, marcador 14px.
- Carimbo de data: Inter 600, 14px mono-espaçado (`tabular-nums`), cor `#f2a33a`, `text-shadow` preto, `aria-hidden`.
- Card de porta: `--tm-paper-2`, raio `--tm-radius-md`, borda `--tm-border`, padding `--tm-space-3`; miniatura 96x54 (16:9) com ícone `mdi-*` e gradiente do `--hx-ano-cor`/ouro; título Fraunces 17px; subtítulo "por Zé Paulo" em `--tm-ink-muted` 13px (mantém a graça da lista de relacionados). Hover: borda dourada + `translateY(-2px)` 150ms.
- Balão de comentário: avatar 32px, nome Inter 600, texto 15px, balão `--hx-balao`, raio `--tm-radius-sm`, data "março de 2011".

## 5. Conteúdo, CTAs e conexão com as áreas

### Textos
- Eyebrow: "Canal do Zé Paulo · desde 2011". Título: "Tudo começa aqui".
- Metadados: "12.847 visualizações · 12 de março de 2011" (`<time datetime="2011-03-12">`, "há 15 anos" calculado).
- Descrição (existente): "Antes de qualquer história, existia a rua. Este vídeo abre o túnel: tudo o que a gente viveu junto está guardado do outro lado." (a segunda frase "Dê o play e entre." vira o rótulo do play).
- Fim do vídeo: "Isso foi só o começo."

### CTAs
| Estado | Primário (dourado preenchido) | Secundário (contorno) |
|---|---|---|
| Visitante | "Conhecer a Rua" -> `/a-rua` | "Quero fazer parte" -> `/cadastrar/usuarios` |
| Logado | "Ir para a Esquina" -> `/historias` | "Ver A Rua" -> `/a-rua` |

Só um botão preenchido por tela. "Entrar" já existe na navbar; não duplicar.

### "Continue assistindo": as quatro portas
| Porta | Título | Subtítulo | Destino | Visitante |
|---|---|---|---|---|
| Esquina | "Continue assistindo: a Esquina" | "Histórias 2011-2018" | `/historias` | cadeado, abre login com `redirect` |
| A Rua | "A Rua" | "Quem é quem" | `/a-rua` | aberto |
| Fotos | "Fotos da turma" | "Álbuns e galeria" | `/fotos/albuns` | aberto (como na navbar) |
| Vídeos | "Mais vídeos do Zé Paulo" | "Galeria de vídeos" | `/galeria-videos` | cadeado |

- Cadeado: `mdi-lock` 16px no canto da miniatura + texto sr-only "Exige entrar". Mesmo padrão visual dos itens da navbar. Sem sobrecarga de dados: nenhum contador real (decisão do projeto: valores "de época" fixos, `yt-config.js`).
- Opcional futuro (não bloqueia): selo "novo" se houver conteúdo novo para o membro logado.

### Comentários (lembranças de época)
Reaproveitar os textos já existentes em `YtComentarios.vue` (no máximo 3), sem campo para digitar, sem "responder". Rodapé: "Ver as lembranças de verdade" -> `/historias` (visitante: cadeado). Marcados como conteúdo fixo, não interativo.

## 6. Estados

| Estado | Comportamento |
|---|---|
| Carregando (poster) | Skeleton 16:9 em `--tm-paper-2` com o play já visível; sem layout shift (`aspect-ratio: 16/9`). Poster `fetchpriority="high"` |
| Parado (antes do play) | Poster com filtro antigo, play dourado pulsando, carimbo de data, controles visíveis mas esmaecidos (não ocultos) |
| Reproduzindo | Controles autoescondem; título e portas permanecem (a sala não apaga) |
| Em buffer | Spinner dourado 32px no centro, `aria-busy`, texto sr-only "Carregando vídeo" |
| Pausado | Controles visíveis, play central pequeno (56px) |
| Fim (`ended`) | Vídeo cai a 35% de brilho, **cartão final** centralizado: "Isso foi só o começo." + CTAs da seção 5 + "Rever" (ícone replay). Nunca navegar sozinho |
| Erro de vídeo | Mensagem "Não conseguimos carregar o vídeo agora." + botão "Tentar de novo". CTAs e portas continuam disponíveis |
| Poster ausente | Gradiente dourado/escuro com avatar do Zé Paulo 96px |
| Visitante | Cadeados nas portas, CTAs de visitante |
| Logado | Sem cadeados, saudação sutil no eyebrow: "Bem-vindo de volta, {nome}" se o nome existir; CTAs de membro |
| Sem JS / falha do script | Conteúdo estático visível (intro só entra quando JS adiciona a classe `home--intro`). `<noscript>` com os CTAs |
| Offline | Poster em cache (se houver), erro de vídeo padrão |

## 7. Entrada "túnel do tempo": storyboard (para o motion-specialist)

Princípios: só `transform` e `opacity` (nada de animar blur/filter/box-shadow), um `gsap.timeline` único, `will-change` aplicado só durante a timeline e removido no fim, 60 fps em celular médio. Easings: `--tm-ease = cubic-bezier(0.2, 0.7, 0.2, 1)` como padrão (GSAP: `CustomEase` ou equivalente, ou `power3.out`), `expo.out` para a "acendida" do vídeo, `power2.inOut` para a contagem. Duração total **6,0 s**, interativa a partir de 4,6 s. Sem som na intro (autoplay com som é bloqueado e hostil; o player de música do site não é tocado).

Elementos da camada de intro (todos `aria-hidden="true"`, `pointer-events: none`):
- `ano`: número gigante em Fraunces 600, `font-variant-numeric: tabular-nums`, `clamp(6rem, 22vw, 14rem)` (mobile 5rem), cor `--tm-gold-text`, centrado.
- `aneis`: 5 círculos SVG/div de 1px, `border: 1px solid var(--tm-gold)`, mesma linguagem dos anéis da órbita de A Rua. Diâmetros base 18vmin, escala animada.
- `calor`: camada de gradiente sépia (`rgba(199,164,58,.25)` a transparente) sobre o fundo, para o "esquentar" de hoje a 2011.
- `traço`: linha de luz 2px x 40vw, dourada, centro da tela.
- `moldura`: o próprio player (que já está no DOM, escondido).

| Tempo (s) | O que acontece | Anima | Duração e easing |
|---|---|---|---|
| 0,00 | Estado inicial: palco vazio escuro, navbar visível, botão "Pular" visível (foco inicial) | `ano` em 2026 (ano corrente), opacity 0 | - |
| 0,00-0,60 | **Hoje**: aparece o ano corrente com a legenda "Hoje" acima (Inter 600, caixa alta, 0,12em, `--tm-ink-muted`) | `ano`, legenda: opacity 0 a 1, y 24px a 0 | 600 ms, `--tm-ease` |
| 0,60-3,40 | **Descida**: o número desce de 2026 a 2011 | texto do `ano` por contagem inteira (ver nota); `aneis` nascem do centro (scale .2 a 3.2, opacity 0 a .5 a 0), stagger 0,28 s, repetição enquanto dura; `calor` opacity 0 a .35; legenda "Hoje" troca por "Voltando..." em fade 150 ms | 2,8 s, contagem `power2.inOut` (começa lenta, acelera no meio, freia perto de 2011); anéis 1,1 s cada, `power1.in` para dar ideia de aproximação |
| 3,40-3,90 | **Chegada**: contagem cai em 2011; leve assentamento; anéis se dissolvem; legenda vira "12 de março de 2011" | `ano`: scale 1.06 a 1 e y -4px a 0; anéis opacity a 0 | 500 ms, `back.out(1.2)` no assentamento, 300 ms `power1.out` nos anéis |
| 3,90-4,60 | **Acende o projetor**: o traço aparece no centro, abre na vertical e vira o player. O `ano` sobe e encolhe (scale 1 a .35, y para a posição do eyebrow) e some em fade | `traço`: scaleX 0 a 1 (250 ms, `power2.out`); player: scaleY .02 a 1, opacity 0 a 1 (450 ms, `expo.out`, transform-origin center); `ano`: opacity 1 a 0 (300 ms) | 700 ms total, sobrepostos |
| 4,60-5,30 | Texto entra: eyebrow, h1, linha do canal, contador de views conta 0 a 12.847 | stagger 90 ms: opacity 0 a 1, y 16px a 0; contador 800 ms `power2.out` (separador de milhar pt-BR) | 400 ms cada, `--tm-ease` |
| 5,00-5,60 | CTAs e carimbo de data "12 03 2011" | CTA: opacity/y, 400 ms; carimbo: opacity 0 a 1 em 250 ms, desliga sozinho aos 3 s do play ou aos 8 s parado | `--tm-ease` |
| 5,30-6,00 | Portas "Continue assistindo" (stagger 100 ms) e comentários (stagger 80 ms) | opacity 0 a 1, x 16px a 0 (portas), y 12px a 0 (comentários) | 400 ms, `--tm-ease` |
| 6,00 | Fim. Remover camada de intro e `will-change`. Foco vai para o botão Play (ou fica no "Pular", que é removido; mover foco para o Play) | - | - |

Nota da contagem: animar um objeto numérico (`{ano: 2026}`) e escrever `Math.round(ano)` no texto em `onUpdate`, limitado a atualizar só quando o inteiro muda. Reservar largura fixa (`ch` de 4 dígitos) para não oscilar. Nunca animar `font-variation-settings`.

### "Pular" e controle
- Botão "Pular a viagem" no canto superior direito da área de conteúdo (44px, contorno, `--tm-ink`), primeiro na ordem de tab. Clique, Esc ou Enter/Espaço levam a timeline ao fim em 250 ms (`timeline.timeScale(8)` ou `progress(1)` com fade curto). Some quando a timeline termina.
- **Frequência**: intro completa apenas na primeira visita à home por sessão (`sessionStorage`, chave sugerida `tm-home-intro`). Visitas seguintes na mesma sessão (voltar da Rua, clicar em "Início") usam a **versão curta de 0,8 s**: palco entra em fade-up simples (`tm-fade-up`), sem túnel. Link discreto "Rever a viagem" no rodapé da seção reexecuta a completa.
- Rota de entrada vinda de login/redirect: versão curta.
- A timeline é cancelada (kill) ao desmontar o componente e ao navegar.

### Interação com o vídeo (movimento do usuário)
- **Pulso do play**: anel externo scale 1 a 1.25 e opacity .5 a 0, 2,4 s, `ease-out`, repetindo a cada 3 s, só com o vídeo parado e visível (IntersectionObserver). Para ao primeiro play.
- **Play**: poster faz crossfade para o vídeo em 250 ms; carimbo de data some em 3 s; `Abertura.mp4` toca com som (gesto do usuário libera).
- **Passagem para a Rua/Esquina** (clique no CTA do cartão final ou da página, 650 ms): conteúdo escala 1 a 1.12 e opacity a 0 (450 ms, `power2.in`), `aneis` reaparecem expandindo para fora (scale .4 a 4, opacity 0 a .4 a 0, 600 ms) criando a sensação de atravessar o túnel; `router.push` aos 400 ms; destino recebe `finalizarTransicao()` e o `tm-fade-up` existente. Mesma assinatura visual de `useTransicao` (overlay `#transition-overlay`) para ter um só mecanismo.
- Clique em uma porta: versão curta (fade-out 250 ms, `power1.in`, e navegação).
- Cartão final (`ended`): vídeo opacity 1 a .35 em 400 ms; cartão fade-up 500 ms com `--tm-ease`; botão "Rever" volta o tempo a 0 e restaura em 300 ms.

### Versão `prefers-reduced-motion: reduce`
- Sem túnel, sem contagem, sem anéis, sem escala/translação, sem pulso do play. Estado final renderizado direto.
- Único movimento permitido: fade de opacity de 200 ms no conteúdo todo (ou nenhum). O contador de views aparece já em "12.847".
- Vídeo nunca toca sozinho (já vale para todos); sem autoesconder agressivo dos controles (controles ficam visíveis até o usuário interagir).
- Transição para outra rota: navegação imediata (mesma regra de `prefereMenosMovimento()` em `useTransicao`).
- Botão "Pular" não é renderizado nesse modo (não há o que pular).
- A preferência deve ser lida em tempo de execução (`prefereMenosMovimento()` já existe em `useTema`) e também reagir à mudança (media query listener).

## 8. Acessibilidade (WCAG 2.2 AA)

- Estrutura: um `h1` ("Túnel do Tempo", sr-only se o título visual for "Tudo começa aqui"; usar o h1 visual como `h1` e o nome do site fica na navbar, evitando dois h1), `h2` para "Continue assistindo" e "Lembranças". Landmarks: `main`, `aside aria-label="Continue assistindo"`.
- Camada de intro: `aria-hidden="true"`; durante ela, o restante do conteúdo fica `inert` (sem foco em elementos invisíveis), exceto o botão "Pular". Um aviso `role="status"` único e educado: "Voltando para 2011." (sem ler o contador).
- Player custom completo por teclado e leitor de tela:
  - Botões com rótulo ("Reproduzir", "Pausar", "Silenciar", "Tela cheia"), `aria-pressed` quando alternam.
  - Progresso como `role="slider"`, `aria-valuemin/max/now` e `aria-valuetext="0:42 de 2:14"`, setas ±5 s, PageUp/PageDown ±10 s, Home/End.
  - Atalhos: Espaço/K reproduz e pausa, M silencia, F tela cheia, apenas com o foco no player (nunca globais, para não brigar com rolagem).
  - Foco visível `3px #f0c86a` (já em `.tm-immersive`), nunca `outline: none`.
- **Legendas e transcrição**: `Abertura.mp4` precisa de faixa `.vtt` (`<track kind="captions" srclang="pt-BR">`) e link "Ler a transcrição". Dependência de conteúdo, ver seção 9.
- Contraste: texto `#f1e8d2` sobre `#1e1a12` ~13:1; muted `#b9ae93` ~7,5:1; dourado `#e0be5a` ~9:1; texto escuro sobre botão dourado ~8:1. Carimbo âmbar sobre vídeo tem sombra e é decorativo. Controles sobre vídeo têm gradiente de base para manter 4,5:1.
- Alvos >= 44px. Nenhuma informação só por cor (cadeado tem texto sr-only).
- Nada pisca mais de 3 vezes por segundo: o contador muda ~15 vezes em 2,8 s, mas por área pequena e sem flash de luminosidade; com `prefers-reduced-motion` desaparece. Recomenda-se não passar de 6 atualizações visíveis por segundo no pico.
- Autoplay: nenhum. Áudio só por gesto.
- Idioma `pt-BR`, datas em `<time datetime>`.

## 9. Performance e dependências de conteúdo

- `Abertura.mp4` tem **33 MB**. Usar `preload="none"` (jamais baixar sem play), poster obrigatório. Recomendado reencodar em H.264 720p ~1,2 Mbps com `faststart` (moov no início), alvo <= 18 MB; manter o original como fonte.
- **Poster novo** (a produzir): frame do vídeo em WebP/AVIF 1280x720, <= 100 KB, mais versão 640 para mobile (`srcset`). `width/height` explícitos.
- Faixa de legendas `.vtt` (seção 8).
- Fontes Fraunces e Inter já carregam por `@fontsource`; para o contador, garantir que Fraunces esteja pronta (`document.fonts.ready`) antes de iniciar a timeline, com limite de 1 s.
- `Save-Data` ou conexão `2g/slow-2g`: versão curta da intro e poster menor.
- Overlay de grão é uma imagem SVG já existente (`--hx-grao`), estática, sem animação.

## 10. Notas de implementação (para o frontend-specialist)

- Reaproveitar: `ytConfig` (atualizar `publicadoEm` para 2011, remover `avaliacoes`, `curtidas`, `chaveInscrito`, `tags`), avatar `/avatar-ze-paulo.png`, `useAuthStore`, `finalizarTransicao`, `prefereMenosMovimento`.
- Remover: `YtCabecalho.vue`, o estilo `.yt-watch`, `YtMetadados` (polegares/estrelas/inscrever), rodapé "Ajuda Sobre Termos", `.yt-faixa`.
- Adaptar: `YtPlayer` (mantém lógica de mídia, troca a casca visual e acessibilidade), `YtComentarios` (balões da Esquina), `YtRelacionados` (portas), `YtDescricao` (2 linhas, sem expansão).
- Sugestão de nomes: `components/home/` (`HomeCinema`, `HomePlayer`, `HomePortas`, `HomeLembrancas`, `HomeIntro`). Intro isolada em `HomeIntro` para o motion-specialist trabalhar sem tocar no player.
- Não alterar `RuaOrbita` nem a Esquina: a home só linka e imita a assinatura (anéis dourados, filtro antigo).
- Testar em: 1440x900, 1024x768, 390x844, tema claro e escuro, `prefers-reduced-motion`, visitante e logado, vídeo com erro, player de música aberto (`--tm-player-h`).

## 11. Critérios de aceite

1. Nenhum azul `#0033CC`, vermelho `#CC181E` ou Arial na home; fontes e cores só por tokens.
2. Em 1440x900 o player, o título e o CTA primário aparecem sem rolar depois da intro.
3. Intro completa em 6 s com botão "Pular"; segunda visita da sessão faz versão curta; reduced-motion mostra o estado final sem movimento.
4. Todo o player é operável por teclado e anuncia estado.
5. Fim do vídeo mostra o cartão "Isso foi só o começo." e nunca navega sozinho.
6. Visitante vê cadeados nas portas de Esquina e Vídeos; logado não.
7. Lighthouse acessibilidade >= 95 e nenhum download do MP4 antes do play.

## 12. Decisões para o dono

1. **Frequência da viagem**: intro completa só na primeira visita da sessão (recomendado) ou sempre que abrir a home?
2. **Nome TúnelTube**: remover de vez (recomendado, o site vira "Canal do Zé Paulo") ou manter como piscadela mínima no eyebrow ("TúnelTube · Canal do Zé Paulo")?

Defaults assumidos sem confirmação: data do vídeo 12 de março de **2011** (hoje 2009), sala escura mesmo no tema claro, produção de poster e legendas, reencode do MP4.
