# Túnel do Tempo — Especificação de UI/UX

Autor: ui-ux-specialist. Destinatário: frontend-specialist (implementação) e, nos itens marcados [BACK], backend-specialist.
Base: auditoria de `frontend/src/**`, `router/index.js`, `plugins/vuetify.js`, `App.vue`, `assets/*.css` e `backend/routes/api.php` (2026-10-16).
Referências `arquivo:linha` são do código atual (sem prefixo = `frontend/src/`).

Princípios que guiam tudo abaixo:
1. Manter a identidade memorial: papel envelhecido (creme), ouro, texto em tinta quente, serifa nos títulos, animações lentas e suaves. Melhorar, não descaracterizar.
2. Um sistema só: tokens no tema Vuetify + um CSS base. Nenhuma cor hexadecimal solta em `.vue`.
3. Acessibilidade e mobile-first são requisito, não bônus.
4. Cada tela com 4 estados: carregando (skeleton), vazio, erro (com "Tentar de novo") e conteúdo.

Prioridades: P0 = bug/quebra/segurança/acessibilidade bloqueante; P1 = grande ganho de UX/consistência; P2 = polimento e extras.

---

## 1. Resumo dos principais problemas

Quebras e bugs (P0):
1. `ConfiguracoesView.vue:133,144` chama `authStore.refreshUsuario()`, que NÃO existe em `stores/auth.js` (TypeError no "Salvar" e no "Mudar foto"). Além disso `UsuarioAuthResource` (backend) não devolve `telefone/aniversario/descricao`, então o formulário abre vazio para esses campos.
2. Rotas sem guard: `/notificacoes` (formulário admin de criar notificação, `router/index.js:72-76`), `/configuracoes` (`:82-86`), `/galeria-videos` (API exige login, `api.php` `videos` dentro de `auth:sanctum`; visitante vê "Carregando..." para sempre porque `VideosGaleriaView.vue:168-179` não tem try/catch). Não existe `router.beforeEach`, nem redirect pós-login (`LoginView.vue:305` sempre vai para `/`).
3. 404 cai em "Em breve..." (`router/index.js:87-91`), que ainda contém uma TERCEIRA cópia do menu de usuário com rotas inexistentes (`EmBreveView.vue:17-28`: `/fotos/criar`, `/videos/criar`, `/galeria`). A órbita da A Rua aponta para `/rota-1..3` (`RuaOrbita.vue:96-105`), que também caem nisso.
4. Logout não limpa o token: `stores/auth.js:183-187` remove `tunel-auth` mas não `auth_token` (usado em `services/api.js:13`). Não há interceptor de 401 (sessão expirada deixa telas quebradas silenciosamente).
5. XSS: `HistoriaShowView.vue:42` (`v-html="historia.conteudo"`) e `AtualizacoesView.vue:33` (`v-html` de `marked.parse`) sem sanitização. Encaminhar também ao security-specialist.
6. Cores e peso: botão ouro `#c7a43a` com texto branco = contraste 2,4:1 (falha WCAG AA, mínimo 4,5:1) em ~50 usos; erro `color: red` (`LoginView.vue:442`) 4:1.
7. `EditarHistoriaModal.vue:61,72`: `block="isMobile"` (string literal, não bind) — sempre aplica `block`.
8. `HistoriasView.vue:94,98,114`: `historiaSelecionada` guarda ora objeto (editar) ora uuid (excluir): frágil. Item "Ver" do menu (`:27-29`) não faz nada.
9. Modais de criação (foto, vídeo, atualização, com toast-ui editor) são montados dentro de TODA instância de `UserMenu.vue:67-69` (13 telas): custo de bundle e DOM em todas as páginas.
10. `public/imgs/avatar-placeholder.png` (referenciado em `ConfiguracoesView.vue:114`) não existe (só `imgs/1.svg`).

Consistência:
- Zero tokens: `vuetify.js:8-14` sem tema; `assets/base.css` e `assets/main.css` NUNCA são importados (`main.js`) e ainda definem `#app{display:grid}`/links verdes de template Vue. Código morto. Fonte Inter/Roboto nunca é carregada (cai em fonte do sistema).
- Hex repetidos sem variável: `#c7a43a` x50, `#fbf6e6` x31, `#b89630` x13; cinco vermelhos diferentes (`#c0392b #b00020 #a93226 #8b0000 #ff0000`), dois verdes. Raios: 12/14/16/18/20/24/32/48px sem escala.
- 4 cabeçalhos/menus diferentes: `AppHeader.vue` (home), `RuaHeader.vue` (A Rua, quase cópia), `UserMenu.vue` (fixo), `UserMenuTm.vue` (relativo, usado por `TopActions`), mais `SideMenu.vue` (não usado, rotas `/videos` `/fotos` inexistentes) e a cópia em `EmBreveView.vue`. `UserMenu` x `UserMenuTm` diferem só em `position` (diff de 6 linhas).
- CSS duplicado: `FotosGaleriaView.vue` e `VideosGaleriaView.vue` (classes `foto-card`, `grid-fotos`, `sem-fotos`, `viewer-card`, `editar-*`), cards de formulário em `CadastrarHistoriaView/CadastrarUsuarioView/AdminCriarNotificacaoView/CriarFotoModal/CriarVideoModal/CriarAtualizacaoModal` (cada um reimplementa `.field`, `.card`, `button`), diálogo de exclusão com `style=""` inline (`HistoriasView.vue:54-68`).
- Bloco de tratamento de erro 422 copiado em 6 arquivos (CriarFoto `:113-135`, CriarAtualizacao `:101-123`, EditarHistoria `:157-179`, CadastrarHistoria `:202-218`, CadastrarUsuario `:150-172`, e variantes).
- Feedback misto: toast (vue3-toastify) + `alert()` (`HistoriaShowView.vue:150,160,170`, `ConfiguracoesView.vue:145`) + `confirm()` (`FotosGaleriaView.vue:198`, `VideosGaleriaView.vue:192`) + mensagem inline (`AdminCriarNotificacaoView.vue:9`, que ainda engole erros não-422 em `:93-97`). O toast fica em `top-right` (`main.js:141-145`) exatamente sobre o sino/avatar fixos (`TopActions` `position:fixed; top:28px; right:40px; z-index:9999`).

Navegação / IA:
- Visitante vê "Histórias" no header (`AppHeader.vue:16`) que exige login e não explica nada. "Atualizações" e "Aquele que sonhou" só existem dentro do dropdown do avatar. Rótulos sem acento ("Videos", "Historias").
- "Configurações" agrupa itens que não são configuração (Criar Notificação, Adicionar Atualização) e aparecem para qualquer logado, embora o backend restrinja criação de foto/vídeo/história a `MEMBRO_ADM`/`MEMBRO_RUA`. Rotas `PUT/DELETE /historias/{uuid}` e `POST /notificacoes` ficam só em `auth:sanctum` (qualquer logado, inclusive Revisor) — sinalizar ao backend/security.
- Sem breadcrumb/voltar, sem título de página (`document.title` fixo, `index.html lang=""`), sem scroll-restoration.

Acessibilidade:
- Controles feitos com `<span>`/`<div>`/`<v-icon>` + `@click` (menus, nav, avatar, sino, fechar de modais, ícone de pasta, thumbs): não focam por teclado nem têm papel semântico (`AppHeader.vue:15-18,55-58`, `UserMenu.vue:5,12-59`, `NotificacaoBell.vue:4`, `FotosGaleriaView.vue:37,63`, `HistoriaShowView.vue:47-52`, `RuaOrbita.vue:4`, `LoginView.vue:246,259`).
- Quase nenhum `:focus` (1 ocorrência de `outline` útil), 0 `prefers-reduced-motion` com animação pesada (GSAP em `RuaOrbita`, `EmBreve`, `nao-te-esquecemos`, particles, `fadeUp`, `reveal`).
- `<img>` sem `alt`: `FotosGaleriaView.vue:38,67`, `GaleriaFotos.vue:16,37`, `RuaOrbita.vue:5,11,22,33`, `UserMenu(Tm).vue:6`, `ConfiguracoesView.vue:14`, `RuaHeader.vue:56-59` (decorativas devem ser `alt=""`).
- Labels sem `for`/`id` em todos os formulários (`LoginView.vue:231-250` etc.); erros de campo só por borda vermelha (cor como único indicador), sem `aria-invalid`/`aria-describedby`; erro de login sem `role="alert"`.
- Diálogos próprios (lightbox, viewers) sem prev/next por teclado, sem foco devolvido; botão "☰" sem `aria-label` (`AppHeader.vue:22`); áudio da homenagem tenta autoplay (`nao-te-esquecemos-amigoView.vue:106-116`) e o botão não tem rótulo (`:41`).
- Tom: `LoginView.vue:223` "BEM VINDO MEU MANO!" em caixa alta — informal demais para um memorial; "Password" misturado com português (`:236`).

Performance percebida:
- `router/index.js:2-14` importa 13 views de forma síncrona (só `EmBreve` é lazy). Quill (3 telas), toast-ui editor (via UserMenu, todas as telas), GSAP e animejs (animejs não é importado em lugar nenhum — dependência morta), `particles.js` está no `package.json` mas é carregado por CDN (`nao-te-esquecemos-amigoView.vue:84`).
- `plugins/vuetify.js:5-6` registra TODOS os componentes e diretivas (bundle grande).
- Vídeos: `VideosGaleriaView.vue:38` coloca um `<video preload="metadata">` por card (N downloads). `GaleriaFotos.vue:73-80` resolve cada foto da história com `await` em série (N+1 sequencial).
- Loading é só a palavra "Carregando..." (`FotosGaleriaView.vue:50`, `NotificacaoBell.vue:16`); `HistoriasView.vue:102-104`, `HistoriaShowView.vue:139-146`, `AtualizacoesView.vue:70-80` sem loading nem erro (mostram tela vazia/título vazio). `HistoriaShowView` renderiza esqueleto vazio com título em branco.
- Assets pesados em `public/`: `videos/` 33 MB, `sons/` 21 MB, `imgs/1.svg` 1 MB, backgrounds SVG 2,7 MB, logins JPG 2,6 MB (sem `width/height`, sem `srcset`, sem `loading="lazy"` em `LoginView.vue:219`).

Código morto: `components/icons/*` (5 arquivos), `stores/counter.js`, `assets/base.css`, `assets/main.css`, `assets/logo.svg`, `components/layout/SideMenu.vue`, `UserMenu` OU `UserMenuTm` (unificar), `animejs` no package.json, imports não usados (`HomeView.vue:22` authStore; `ARuaView.vue` authStore usado; `EmBreveView` inteiro após 404 real; `LoginView` classes `.avatar` em `HomeView.vue:72-139` = CSS de menu antigo sem uso).

---

## 2. Design tokens e tema (centralizar em `plugins/vuetify.js` + `assets/tokens.css`)

Decisão: manter ouro + papel como assinatura, mas separar a cor "decorativa" (ouro `#C7A43A`, pode ser fundo/borda/ícone grande) da cor "funcional de texto/botão" (ouro escuro, passa em AA). Fonte serifa para títulos dá o tom de memorial; corpo continua sans.

### 2.1 Paleta (claro = `tunelClaro`, escuro = `tunelNoite`)

| Token (Vuetify `colors`) | Claro | Escuro | Uso / contraste |
|---|---|---|---|
| `background` | `#F3ECDB` | `#14110B` | fundo atrás dos painéis |
| `surface` | `#FBF6E6` | `#1E1A12` | painéis, cards, menus, modais (é o creme atual) |
| `surface-variant` | `#F3ECDB` | `#2A2418` | campos, hover, chips |
| `on-surface` (tinta) | `#2A2112` | `#F1E8D2` | texto principal; >12:1 sobre `surface` |
| `on-surface-muted` (custom) | `#5E5441` | `#B9AE93` | texto secundário; 6,9:1 / 7:1 |
| `primary` (botão) | `#C7A43A` | `#E0BE5A` | fundo de botão preenchido |
| `on-primary` | `#2A2112` | `#1B1505` | TEXTO ESCURO sobre ouro = 6,6:1 (hoje branco = 2,4:1, reprovado) |
| `primary-text` (custom) | `#7A5D0E` | `#E0BE5A` | links/texto ouro sobre creme = 5,7:1 |
| `secondary` | `#5E5441` | `#B9AE93` | ações neutras |
| `error` | `#B3261E` | `#FF8A80` | substitui os 5 vermelhos |
| `success` | `#1E7E34` | `#66D17A` | |
| `warning` | `#8A5A00` | `#F0C86A` | |
| `info` | `#1F5F8B` | `#8FC1E8` | |
| `focus` (custom) | `#1F5F8B` | `#E0BE5A` | anel de foco (3:1 mínimo contra fundo) |
| `border` (custom) | `rgba(42,33,18,.16)` | `rgba(241,232,210,.16)` | |
| `scrim` | `rgba(20,14,4,.55)` | `rgba(0,0,0,.7)` | overlay de modais/drawer |

Telas imersivas (home, A Rua, homenagem, login) mantêm seus backgrounds SVG; sobre eles texto branco precisa de `text-shadow: 0 1px 12px rgba(0,0,0,.45)` ou camada `linear-gradient(rgba(0,0,0,.35),transparent)` para garantir 4,5:1.

Regras: botão preenchido = ouro + tinta escura; link/ícone de destaque sobre creme = `primary-text`; destrutivo = `error` (nunca vermelho cru). Não usar cor como único indicador de erro/estado (ícone + texto).

### 2.2 Tipografia

- Títulos (h1-h3, nome do site): **Fraunces** (serifa variável, warm) — self-host via `@fontsource-variable/fraunces` (peso 400-700, subset latin + latin-ext). Corpo/UI: **Inter** via `@fontsource-variable/inter`. `font-display: swap`; pré-carregar só o peso do corpo. Sem CDN (privacidade e performance). Se o time preferir não adicionar dependência: `Georgia, 'Times New Roman', serif` nos títulos.
- Base do corpo 16px (hoje 15px, `base.css:82`), `line-height: 1.6`. Texto longo (história/atualização) 17-18px, `max-width: 68ch`, `line-height: 1.75`.
- Escala (`--tm-fs-*`): xs 12 / sm 14 / md 16 / lg 18 / xl 22 / 2xl 28 / 3xl `clamp(1.75rem, 4vw, 2.5rem)` / hero `clamp(2.25rem, 7vw, 4rem)`. Pesos: 400 corpo, 600 rótulos/botões, 700 títulos.
- Nunca MAIÚSCULAS em frases de UI (login). Rótulos em português com acento ("Vídeos", "Histórias").

### 2.3 Espaçamento, raios, sombras, movimento

- Espaço (base 4): `--tm-space-1..8` = 4, 8, 12, 16, 24, 32, 48, 64. Padding de painel: 16 (mobile) / 24 (tablet) / 32-40 (desktop). Gutter de grid: 12 / 16 / 24.
- Raios (reduzir os 8 valores atuais para 5): `xs 8` (chips), `sm 12` (campos, botões), `md 16` (cards), `lg 24` (painéis, modais), `xl 32 / 48` (somente a "folha" do `AppHeader` da home), `pill 999`, círculo 50%.
- Sombras: `sm: 0 1px 2px rgba(42,33,18,.12)`; `md: 0 8px 24px rgba(42,33,18,.18)`; `lg: 0 24px 64px rgba(42,33,18,.28)` (dropdown/modal). No escuro: sombra mais forte + borda 1px `border`.
- Foco: `outline: 3px solid var(--tm-focus); outline-offset: 2px;` em TODO elemento interativo (`:focus-visible`), inclusive `v-btn`/`v-list-item`/campos nativos. Nunca `outline:none` sem substituto (`CadastrarHistoriaView.vue:519`).
- Alvos de toque mínimos 44x44px (avatar 52 já ok; botões `menu-btn` das galerias, "×" de remover preview, ícone de pasta precisam ampliar).
- Movimento: `--tm-dur-fast 150ms`, `--tm-dur-base 250ms`, `--tm-dur-slow 600ms`, `--tm-ease cubic-bezier(.2,.7,.2,1)`. Máx. um movimento "grande" por tela. Regra global:

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important;
    transition-duration: .01ms !important; scroll-behavior: auto !important; }
}
```
  e no JS: `const reduzir = matchMedia('(prefers-reduced-motion: reduce)').matches` — se verdadeiro, GSAP faz `duration: 0` (transição da órbita vira troca imediata), particles desligadas (`particles.number.value = 0`), `.reveal` aparece sem animação, vídeo nunca autoplay.

### 2.4 Dark mode
- `theme: { defaultTheme: 'system' }` não existe no Vuetify 3 como string mágica; implementar com `useTheme()`: composable `useTema()` lê `localStorage['tunel-tema']` ('claro'|'escuro'|'auto'), default `auto` = `matchMedia('(prefers-color-scheme: dark)')`, aplica `theme.global.name.value = 'tunelNoite'|'tunelClaro'` e `document.documentElement.dataset.tema`. Toggle (ícone sol/lua, `aria-pressed`) no menu do usuário e, para visitante, no rodapé do drawer. Páginas imersivas já são escuras: nelas só os painéis internos mudam.
- Backgrounds SVG (`cadastrar-bg.svg`) no escuro: aplicar `filter: brightness(.45) saturate(.8)` na camada de fundo via pseudo-elemento (não no conteúdo).

### 2.5 Arquivos a criar (centralização)
- `assets/tokens.css`: variáveis `--tm-*` (espaço, raio, sombra, duração, fonte, tamanhos) + `:focus-visible` + `prefers-reduced-motion` + reset mínimo + `.tm-prose` (estilo de conteúdo rico para história/atualização, substitui `.rich-conteudo` e `.markdown`) + `.sr-only` + `.skip-link`. Importar em `main.js`. APAGAR `base.css`, `main.css`, `logo.svg` (confirmar uso), `components/icons/*`, `stores/counter.js`.
- `plugins/vuetify.js`: temas `tunelClaro`/`tunelNoite` (cores acima + `variables`), `defaults` globais: `VBtn { rounded: 'lg', class: 'text-none', color: 'primary' }`, `VTextField/VTextarea/VSelect { variant: 'outlined', density: 'comfortable', color: 'primary' }`, `VCard { rounded: 'lg' }`, `VDialog { scrim: true }`, `VTooltip`. Trocar `import * as components` por `vite-plugin-vuetify` (`autoImport: true`) ou imports nomeados (corta bundle).
- Componentes compartilhados (`components/ui/`): `PageShell.vue` (fundo, título h1, slot, `max-width`, padding por breakpoint, skip-link), `TmField.vue` (label com `for`, erro com `aria-describedby`, dica, contador), `TmEmptyState.vue` (ícone, título, texto, ação), `TmErrorState.vue` (mensagem + "Tentar novamente"), `TmSkeletonGrid.vue`, `ConfirmDialog.vue` + composable `useConfirm()` (promessa), `TmLightbox.vue`, `MediaCard.vue` (foto/vídeo), `TmImg.vue` (width/height, lazy, fallback). Composables: `useApiError(e)` (extrai 1ª mensagem 422, mapeia `errors` para campos, 401/403/5xx), `useNotify()` (wrapper dos toasts: success/erro/info com ícone), `useTema()`, `useListaPaginada()` (scroll infinito + loading/erro/empty), `useRascunho(chave)`.

---

## 3. Itens por tela / componente

### 3.0 Transversais (fazer primeiro)

| # | Prio | Problema | Comportamento esperado |
|---|---|---|---|
| T1 | P0 | `main.js` não importa CSS base; sem tema (`vuetify.js:8-14`). | Aplicar seção 2. Importar `tokens.css`. Remover CSS morto. |
| T2 | P0 | Sem guard (`router/index.js`). | `router.beforeEach`: se `meta.requiresAuth` e não logado -> `{ name:'login', query:{ redirect: to.fullPath } }`. `meta.roles: ['MEMBRO_ADM']` para `notificacoes` (valores exatos de `tipo_usuario` vêm de `UsuarioAuthResource`: confirmar enum com backend). Se logado e vai a `/login` -> `/`. Rotas com guard: `/historias*`, `/galeria-videos`, `/configuracoes`, `/notificacoes` (renomear para `/admin/notificacoes/nova`, manter redirect da antiga), `/historias/criar` (roles ADM/RUA). Logout em rota protegida -> `/`. |
| T3 | P0 | Imports síncronos (`:2-14`). | Todas as views `component: () => import(...)`, com `/* webpackChunkName */` opcional. Quill, toast-ui e GSAP só em chunks das telas que usam. `scrollBehavior` (voltar restaura posição; hash rola até âncora). `afterEach`: `document.title = meta.title + ' · Túnel do Tempo'` e mover foco para `<h1>` (ou `#conteudo`) para leitores de tela. |
| T4 | P0 | 404 = "Em breve" (`:87-91`). | Criar `NotFoundView.vue` (ver 3.13) para `/:pathMatch(.*)*`. Remover `EmBreveView.vue` OU manter só em `/em-breve` com layout simples, sem menu duplicado. |
| T5 | P0 | Sessão: `stores/auth.js:183-187`, `services/api.js`. | `logout()` remove também `auth_token` e chama `POST /logout` (existe) sem bloquear a UI. Interceptor de resposta: 401 fora de `/login` -> `authStore.logout()`, toast "Sua sessão expirou. Entre novamente." e `router.replace({name:'login', query:{redirect}})`; 403 -> toast "Você não tem permissão para isso."; erro de rede -> toast "Sem conexão. Verifique a internet." Criar `authStore.refreshUsuario()` (GET `/usuarios/{uuid}`) e `authStore.temPapel(...)`, `isMembro`, `isAdmin` (getters). Unificar `foto_perfil` (path) e `foto.uuid` (usado em `SideMenu`/`EmBreve`) numa getter `fotoPerfilUrl`. |
| T6 | P0 | `confirm()/alert()` nativos. | Substituir por `ConfirmDialog` (título, texto, botão destrutivo `error`, foco inicial em "Cancelar", Esc cancela, `aria-describedby`) e toasts. Texto padrão: "Excluir esta foto? Essa ação não pode ser desfeita." Após excluir, toast com "Desfazer" só se backend suportar soft-delete (senão sem desfazer). |
| T7 | P0 | Toast sobrepõe sino/avatar (`main.js:141-145`). | `position: 'bottom-center'` (mobile) / `'bottom-right'` (>=960px), `autoClose` 4000 (sucesso) e 7000 (erro, via `useNotify`), `pauseOnHover`, `role="status"`/`alert` nos de erro. Não usar emoji obrigatório no texto (mantém opcional: tom memorial). |
| T8 | P1 | Erro 422 duplicado em 6 lugares. | `useApiError()`; formulários mostram erro por campo (texto abaixo do campo + `aria-invalid`) E toast resumo apenas se houver erro geral. Foco vai ao primeiro campo inválido. |
| T9 | P1 | Menus fragmentados. | Ver 3.1 (navbar única). |
| T10 | P1 | Imagens/assets. | `TmImg` com `width/height` ou `aspect-ratio`, `loading="lazy"`, `decoding="async"`, `alt` obrigatório (prop). Login: escolher imagem aleatória mas com `fetchpriority="high"` e `width/height`; converter `public/login/*.jpg`, backgrounds e `imgs/1.svg` (1 MB, provável SVG com bitmap embutido) para WebP/AVIF/SVGO — pedir ao devops/frontend. |
| T11 | P1 | Dependências. | Remover `animejs` (não usado). Trocar particles via CDN pelo pacote `particles.js` já instalado (import dinâmico) ou eliminar a dependência com um canvas leve próprio (50 círculos). Escolher UM editor: manter **Quill** (já em 2 telas + sem dependência de markdown) para história/notificação e migrar `CriarAtualizacaoModal` para Quill, ou manter toast-ui só se o produto exigir preview Markdown (então migrar Quill->toast-ui? não recomendado, é mais pesado). Recomendação: Quill + `marked` só para renderizar atualizações antigas; salvar novas atualizações em HTML sanitizado (decisão a confirmar, ver seção 6). Sanitizar tudo que entra em `v-html` com `DOMPurify` (`.tm-prose`). |
| T12 | P2 | `index.html`. | `lang="pt-BR"`, `<meta name="description">`, `theme-color` (claro `#FBF6E6` / escuro `#14110B`), `og:title/description/image`, favicon SVG/PNG, `<noscript>`. |
| T13 | P2 | Mobile `100vh`. | Trocar por `min-height: 100dvh` em páginas e drawer (`AppHeader.vue:239`). |

### 3.1 Navegação: `AppHeader.vue`, `RuaHeader.vue`, `UserMenu.vue`, `UserMenuTm.vue`, `TopActions.vue`, `SideMenu.vue`, `NotificacaoBell.vue`

Decisão: **uma barra de navegação única** (`AppNavbar.vue`) em todas as telas "internas" e uma versão minimalista nas imersivas. Os cinco componentes atuais viram dois: `AppNavbar` (links + sino + menu de usuário) e `UserMenu` (v-menu). Excluir `UserMenuTm`, `TopActions`, `SideMenu`, `RuaHeader` (usa `AppNavbar` com `variant="sobre-fundo-escuro"`), e a cópia em `EmBreve`.

- P0 `AppHeader.vue:15-18,55-58`, `RuaHeader.vue:13-16`: itens `<span @click>`. Usar `<nav aria-label="Principal">` com `<router-link>`/`v-btn :to`. `aria-current="page"` na rota ativa + destaque visual (sublinhado ouro 2px, não só cor).
- P0 `AppHeader.vue:22`: botão "☰" -> `<v-btn icon aria-label="Abrir menu" :aria-expanded>`; drawer com `v-navigation-drawer` (focus-trap, Esc fecha, devolve foco ao botão, `aria-modal`), sem `setTimeout(250)` manual.
- Itens e ordem (desktop e drawer): Início, Histórias, A Rua, Fotos, Vídeos, Atualizações. "Aquele que sonhou" fica na A Rua (clicando no centro da órbita) e como link no rodapé do drawer ("Em memória"). Visitante: Histórias e Vídeos aparecem com ícone de cadeado discreto e levam a `/login?redirect=...` com texto de apoio "Entre para ver as histórias da rua." (em vez de esconder; evita surpresa).
- Lado direito: sino (só logado), botão "Criar" (só `MEMBRO_ADM`/`MEMBRO_RUA`: menu História / Foto / Vídeo / Atualização), avatar (menu) ou botão "Entrar". Menu do avatar (`v-menu` + `v-list` acessível): nome+tipo no topo; Meu perfil (`/configuracoes`); Tema (claro/escuro/auto); Atualizações; (admin) Criar notificação; Sair (com `ConfirmDialog` não necessário; sair é reversível).
- P0 `UserMenu.vue:67-69`: modais montados sempre. Carregar com `defineAsyncComponent` e `v-if="abrirModalFoto"` (monta só ao abrir). Isso tira toast-ui do bundle inicial.
- P1 mobile: avatar/sino saem do `position:fixed` flutuante (que cobre conteúdo, `TopActions.vue:14-20`) e passam a viver na barra do topo (altura 56px, sticky, `z-index` abaixo do drawer). Conteúdo ganha `padding-top` da barra. No desktop a barra é "pílula" translúcida (blur) sobre o fundo; na home, a "folha" creme atual (`AppHeader.vue:121-128`) continua como cenário, mas a navegação sai de dentro dela e vira a barra comum.
- P1 `NotificacaoBell.vue`: botão real (`aria-label="Notificações, N não lidas"`, `aria-expanded`), badge numérico (`9+`), `temNaoLidas` hoje = lista não vazia (`:55`, correto só enquanto a API devolve não lidas: manter). Painel com `role="dialog"`, itens como `<router-link>` (links internos) em vez de `window.location.href` (`:84-86` recarrega a página); links externos (`^https?://`) em nova aba com `rel="noopener noreferrer"`. `try/catch` em `carregar` (`:69-73`) com estado de erro e retry; skeleton de 3 linhas no loading; atualizar a cada 60s apenas com `document.visibilityState==='visible'`; `aria-live="polite"` no contador. Mostrar data relativa ("há 2 dias") e o tema como chip. Rodapé: "Marcar todas como lidas" (botão, não `div`) e "Ver todas" (P2, [BACK] ver 4.B).
- P1 `Esc` fecha menus; clique fora via `v-menu` (remover listeners globais `document.addEventListener` de `UserMenu.vue:154-160`, `NotificacaoBell.vue:89-92`, `EmBreveView.vue:62-70`).
- P2 skip-link "Pular para o conteúdo" como 1º elemento focável (`.skip-link` em tokens.css) e `<main id="conteudo">` em `App.vue` (hoje `v-main` sem landmark rotulado).
- Foto do usuário: `alt="Foto de {nome}"`; fallback com iniciais em círculo ouro (em vez do emoji 👤 `UserMenu.vue:7`).
- Emojis como ícones funcionais (🏠📝🎬🖼️👥🙈👁️🎵▶⏸) -> trocar por `mdi-*` com `aria-label` (renderização varia por SO e leitores leem "cabeça de macaco com olhos tapados").

### 3.2 `App.vue`
- P1 `:7-9`: overlay de transição fora do `<v-app>`, `<img id="transition-image">` sem `alt` (`alt=""` + `aria-hidden="true"`), `z-index:99999`. Encapsular em componente `TransicaoOverlay` com `provide('transicionar')` para as telas (hoje 3 views mexem em `document.getElementById`). Respeitar reduced-motion (sem animar, navegar direto).
- P1 envolver `<router-view>` em `<transition name="pagina" mode="out-in">` (fade 150-250ms; desligada com reduced-motion) e `<Suspense>` + barra de progresso fina no topo (ouro) para lazy chunks.
- P2 `:14-20` CSS global mover para tokens.css.

### 3.3 `HomeView.vue` + `FeaturedVideoCard.vue`
- P1 `HomeView.vue:6-18`: manter hero (título + frase). Frase com `<blockquote>`/`<q>` e atribuição opcional. Adicionar 2 CTAs sob a frase: "Ver histórias" (primário) e "Conhecer a rua" (secundário). Texto branco sobre background: garantir contraste (ver 2.1). `h1` com `clamp()`; `HomeView.vue:49` 64px fixo.
- P1 [FRONT] Abaixo do vídeo, seção "Últimas histórias" (3 cards) usando `GET /historias/limite/{n}` (rota pública com API key — `buscarHistoriasPorLimite` já existe em `services/historia.service.js:3-6` e NÃO é usada). Visitante vê título + descrição curta e CTA "Entre para ler". Logado clica e vai à história.
- P2 [FRONT] Bloco "Aniversariantes do mês" (membros logados) com `GET /usuarios` (`aniversario`, hoje só visível se `UsuarioListResource` expõe o campo: confirmar). Mensagem de afeto: "Hoje é dia de lembrar de {nome}". Só para logados.
- P0 `FeaturedVideoCard.vue:3`: `div @click` -> `<button>` "Reproduzir vídeo de abertura" (poster com `aria-hidden` no triângulo). Adicionar `poster` (frame do vídeo em WebP) para o `v-else` (`:16` hoje div vazio), `preload="none"`, nunca autoplay com som sem gesto (o clique é o gesto: ok), `<track kind="captions">` se houver legenda. `:40` botão "Conheça" sem ação -> leva a `/a-rua` ou remover. `:35` "13k de inscritos" e `:21` "TUDO COMEÇA AQUI 👆": conteúdo simulado de YouTube; confirmar com o dono se é intencional (identidade ou resíduo); se mantido, marcar `aria-hidden` o que é decorativo. `:11` `src` fixo 33 MB: servir via `<source>` MP4 + versão 720p/WebM menor (devops).

### 3.4 `LoginView.vue`
- P0 `:231-250` labels/ids/`autocomplete="email"`/`"current-password"`, `inputmode`, `required` nativo + mensagens de validação em português (hoje do navegador). Mostrar/ocultar senha: `<button type="button" aria-label="Mostrar senha" :aria-pressed>` com `mdi-eye` (não `span` emoji, `:246`).
- P0 `:262,441-445`: erro em `role="alert"` com ícone + texto, cor `error` (não `red`). Mensagens: 401 -> "E-mail ou senha incorretos."; 422/rede/5xx distintas. Foco volta ao campo e-mail. Limpar senha após falha.
- P0 `:305`: respeitar `route.query.redirect` (validar que começa com `/` e não `//`) e toast "Bem-vindo de volta, {nome}".
- P1 `:223-228` copy: "Que bom te ver por aqui." + "Entre com sua conta de membro ou continue como visitante." Remover "MEU MANO" e caixa alta. Botão "Entrar" (largura total; `:397` o seletor `button` global dentro do scoped atinge tudo, trocar por classe `.btn-primary` ou `v-btn`). "Continuar como visitante" vira botão de texto (hoje `span` clicável `:259`).
- P1 `:219`: `alt=""` (decorativa) + `width/height`, `fetchpriority`. Imagem aleatória depende de `onMounted` (`:318`) -> flash vazio: sorteio síncrono no `setup`.
- P1 mobile: card com `border-radius` completo e `min-height: 100dvh` (hoje cantos inferiores retos e `align-items:flex-start`, `:485-494`). Teclado virtual: não fixar altura (`min-height:720px` `:335`).
- P2 link "Esqueci minha senha" [BACK] (ver 4.B) e "Tem uma chave de convite? Cadastre-se" -> `/cadastrar/usuarios`. Botão `Enter` envia (já via form). Anunciar loading ("Entrando...") com `aria-busy`.

### 3.5 `HistoriasView.vue` (lista)
- P0 `:102-104`: sem loading/erro/vazio. Skeleton (6 cards), `TmErrorState` com retry, `TmEmptyState` ("Nenhuma história ainda." + botão "Contar uma história" para membros / "Nenhuma história encontrada para '{q}'" + "Limpar busca").
- P0 `:14-16`: card inteiro como `div @click`; usar `<router-link>` (ou `v-card :to`) para abrir; menu "⋮" dentro continua `@click.stop` mas com `aria-label="Ações de {titulo}"`, botão real 44x44. `:27-29` "Ver" sem ação -> navegar; remover (o clique do card já faz).
- P0 `:94,98,114`: separar `historiaParaEditar` (objeto) e `uuidParaExcluir`. Excluir via `ConfirmDialog` ("Excluir a história '{titulo}'? Fotos e vídeos vinculados continuam na galeria." — confirmar o comportamento real do backend antes de escrever isso) com `loading`, try/catch + toast; ações Editar/Apagar só para quem pode (`MEMBRO_ADM`/`MEMBRO_RUA`).
- P1 `:6` busca: `<v-text-field clearable prepend-inner-icon="mdi-magnify" label="Buscar histórias">` com `<label>` real, debounce 250ms, busca em título + descrição curta + nomes dos membros (campos já vêm em `HistoriaResumoResource`), realce do termo, sincroniza `?q=` na URL, atalho `/` foca a busca, contador "12 histórias".
- P1 [FRONT] filtros: chips de tipo (Todas / Principal / Paralela / Pessoal) com `aria-pressed`, filtro por membro (select), ordenação (Mais recentes / A-Z). Persistir em `?tipo=&membro=&ordem=`.
- P1 [FRONT+BACK-mínimo] alternância "Cards | Linha do tempo" (assina o produto): linha do tempo vertical agrupada por ano, com marcador ouro e linha fina; no mobile linha à esquerda. Precisa de `data_historia` em `HistoriaResumoResource` (adicionar um campo; ver 4.B-1).
- P1 card: tipo como chip colorido discreto (não só texto `.tipo`), avatares/iniciais empilhados dos membros (máx. 3 + "+2") em vez de "👥 N" (`:46`), data da história se disponível, trecho de `descricao_curta` (2 linhas, `line-clamp`).
- P1 mobile: lista de 1 coluna, card com altura mínima 96px, FAB "+" (membros) no canto inferior direito (hoje ações de criar só via menu).
- P2 paginação/scroll infinito quando a lista passar de ~50 itens [BACK] (`/historias/resumo` hoje devolve tudo).

### 3.6 `HistoriaShowView.vue` (leitura)
- P0 `:42` `v-html` -> `DOMPurify.sanitize` + classe `.tm-prose`; imagens do conteúdo `max-width:100%`, `loading="lazy"`.
- P0 `:139-146`: loading (skeleton de título + 6 linhas), 404 -> `NotFoundView` embutida ("Esta história não existe ou foi removida" + voltar), 403/erro -> `TmErrorState`. Hoje fica tela com título vazio e ícones. `document.title = historia.titulo`.
- P0 `:148-176`: `alert()` -> botões de mídia desabilitados com contagem ("Fotos (0)" desabilitado + tooltip "Sem fotos") em vez de abrir um modal para depois avisar.
- P1 acesso à mídia: ícone de pasta isolado (`:47-52`, `v-icon @click` sem foco) é obscuro. Trocar por barra de ações visível: "Fotos (n)", "Vídeos (n)", "Músicas (n)" como `v-btn` (ou abas), abrindo direto a galeria correspondente. Remover o modal intermediário (`:57-89`).
- P1 cabeçalho: botão/breadcrumb "← Histórias" (`Histórias / {titulo}`), metadados em linha (data por extenso "12 de março de 2019", tipo como chip, "Por: avatares dos membros" linkando para o perfil [FRONT, ver 4.A-9]), tempo de leitura ("5 min"). Layout `:7-38` em 3 colunas quebra em mobile: empilhar (título primeiro, membros e data depois).
- P1 [FRONT] ações: Compartilhar (Web Share API `navigator.share({title,text,url})`, fallback copiar link + toast "Link copiado"), Imprimir/PDF (`@media print` limpa navegação/fundo; folha branca, serifa 12pt), Editar (membros). Anterior/Próxima história (por ordem da lista em memória, se disponível).
- P2 [FRONT] modo leitura: controles A- / A+ (tamanho 16-22px), largura de coluna 68ch, salvos em `localStorage`; barra de progresso de leitura fina no topo.
- P2 [FRONT] índice automático a partir de h2/h3 do conteúdo se houver >=3.
- P2 [BACK] "Deixar uma lembrança" (comentários curtos por história) — emocional e alinhado ao memorial (ver 4.B).

### 3.7 `GaleriaFotos.vue`, `GaleriaVideos.vue`, `GaleriaMusicas.vue` (galerias de dentro da história)
- P0 `GaleriaFotos.vue:73-80`: `await` serial por foto. Carregar em paralelo (`Promise.allSettled`) com placeholders por item; idealmente [BACK] devolver `{uuid, url, titulo}` em `HistoriaShowResource` (4.B-2) e eliminar as N chamadas.
- P0 `:16,37` `alt` (título ou "Foto {i} de {n} da história {titulo}"), `loading="lazy"`; itens como `<button>` (foco, Enter/Espaço).
- P1 trocar os 2 `v-dialog` aninhados por `TmLightbox` compartilhado (ver 3.8) começando no índice clicado.
- P1 `GaleriaMusicas.vue`: título/nome da faixa por item, `aria-label` no `<audio>`, "tocar uma por vez" (pausar a anterior), próxima faixa automática (playlist) [FRONT].
- P1 Em mobile as galerias abrem em tela cheia (`fullscreen` via `useDisplay().mobile`), com botão fechar 44px no canto superior; `max-width:1200` só no desktop.
- Vídeos: `GaleriaVideos.vue` (254 linhas, verificar) usar o mesmo `MediaCard` e player com `preload="metadata"` somente ao abrir.

### 3.8 `FotosGaleriaView.vue` / `VideosGaleriaView.vue` + `TmLightbox`
- P0 `FotosGaleriaView.vue:174-185`, `VideosGaleriaView.vue:168-179`: sem try/catch -> `carregando` preso. try/finally + estado de erro com retry; scroll infinito: `IntersectionObserver` com `rootMargin:'400px'` (`:159-163` dispara só ao tocar o fim), botão "Carregar mais" como fallback acessível e quando reduced-motion/`IntersectionObserver` indisponível; anunciar "Carregadas N fotos" em `aria-live`.
- P0 `:128-149` e `:133`: `salvarEdicao` sem try/catch/toast; mostrar "Foto atualizada." e erros.
- P0 vídeos para visitante: ver T2; `/galeria-videos` com `requiresAuth`.
- P0 `:37-38`: thumb é `div @click` + `img` sem `alt` -> `<button class="thumb" aria-label="Abrir foto {titulo}">`; `menu-btn` com `aria-label="Ações da foto"`; item "Excluir" via `ConfirmDialog` (T6). Mostrar ações Editar/Excluir só a quem tem permissão (`MEMBRO_ADM`/`MEMBRO_RUA`; hoje o backend nega com 403 e a UI não trata).
- P1 `TmLightbox.vue`: tela cheia escura `#0B0905` 96%; setas esquerda/direita (teclado e botões 48px), Esc fecha, swipe horizontal no toque, pinch/duplo toque zoom (CSS transform simples), contador "3 / 24", legenda (título + descrição), botões: Compartilhar/copiar link, Baixar (se a imagem for do mesmo domínio), Tela cheia. Preload da imagem anterior/próxima. `role="dialog" aria-modal="true" aria-label="Visualizador de fotos"`, focus-trap, devolve foco ao thumb. Para vídeos: mesmo contêiner com `<video controls>`, setas trocam de vídeo, para o vídeo ao trocar/fechar (hoje `autoplay` `VideosGaleriaView.vue:68`).
- P1 grid: CSS `grid-template-columns: repeat(auto-fill, minmax(160px,1fr))` (substitui 6 breakpoints manuais `FotosGaleriaView.vue:236-415`); `aspect-ratio: 1` ou masonry (`column-count`) para fotos; hover mostra título; skeleton cards (aspect 1:1) enquanto carrega; `TmEmptyState` ("Nenhuma foto ainda" + "Adicionar foto" para membros).
- P1 [FRONT] barra de ferramentas: busca por título/descrição (sobre itens já carregados; com aviso "buscando entre as N já carregadas" — busca completa é [BACK] 4.B-3), ordenação (mais recentes/antigas), filtro por ano (derivado da data da foto se vier no payload), seleção múltipla (modo "Selecionar": excluir várias com um confirm) para membros.
- P1 [FRONT] deep link: `?foto={uuid}` abre direto o lightbox (usa `GET /fotos/{uuid}` ou `/details`, rotas públicas existentes) e permite "Copiar link".
- P1 vídeos: thumbnails — `video` por card baixa metadados de todos (`VideosGaleriaView.vue:38`). Curto prazo: `preload="none"` + poster neutro com ícone play; definitivo [BACK] 4.B-4 (poster gerado no upload). Mostrar duração quando houver.
- P2 duplicação: mover grid/card/viewer/editar para `MediaGrid.vue` + `MediaEditDialog.vue` reutilizados pelas duas views (corta ~350 linhas de CSS duplicado).
- P2 título da página "Galeria de Fotos" com contagem total (`meta.total` da API paginada) e subtítulo.

### 3.9 `AtualizacoesView.vue` + `CriarAtualizacaoModal.vue`
- P0 `:33` sanitizar HTML (`DOMPurify`). `:42-46` `target=_blank` -> `rel="noopener noreferrer"`; botão "Escutar música" com `mdi-music` e rótulo "Escutar música (abre em nova aba)".
- P0 `:70-80`: loading skeleton, erro com retry, vazio ("Ainda não há atualizações."); `try/catch`.
- P1 `:78` `isNova: index===0` marca sempre a mais recente como "Nova versão". Regra: "Novo" se `created_at` < 14 dias OU maior que `localStorage['tunel-ult-atualizacao']` (guardar ao visitar). Badge "Novo" no menu do avatar/ "Atualizações" quando há não lida [FRONT].
- P1 layout timeline vertical (data na lateral/linha ouro) com cartões `surface`; em mobile data acima do título. Agrupar por mês. Título como `h2`, âncora `#uuid` com botão de copiar link.
- P1 `CriarAtualizacaoModal.vue`: modal `fullscreen` em mobile; fechar com botão real (`:5` é `v-icon`), confirmar descarte se houver texto digitado; lazy (ver 3.1); `errors` (`:58`) é guardado mas nunca exibido por campo; fechar/limpar com `setTimeout` (`:96-99`) — fechar após sucesso imediato; título obrigatório, link de música `type="url"` com validação.
- P2 [FRONT] editar atualização (`PUT /atualizacoes/{uuid}` existe no backend, `api.php`) para quem criou/admin.

### 3.10 `ConfiguracoesView.vue` ("Editar perfil")
- P0 `:133,144` ver "Quebras" item 1 (criar `refreshUsuario`). `:139-151`: `salvar` sem toast de erro (só `console.error`), `alert` de sucesso -> toast + estado `loading` no botão (variável `loading` existe e nunca é usada no template).
- P0 carregar dados completos ao montar: `GET /usuarios/{uuid}` (`buscarUsuario` existe) e preencher o form; skeleton enquanto isso.
- P0 `:48,63,29-34,78`: `email` `type="email"`, `autocomplete`, `type="tel"` no telefone (máscara `(99) 99999-9999`), senha nova em `type=password` com mostrar/ocultar, `autocomplete="new-password"`, e SÓ enviar `senha` se preenchida (hoje envia string vazia); `confirmar nova senha` + medidor simples (mínimo 8). `input[type=file]` com `accept="image/*"` e limite (ex.: 5 MB) validado antes do envio; botões com `type="button"`.
- P1 foto: `alt="Foto de perfil de {nome}"`, preview imediato (`URL.createObjectURL`) enquanto envia, botão "Remover foto" [BACK opcional], fallback com iniciais (o `/imgs/avatar-placeholder.png` não existe). Recorte circular (P2).
- P1 formulário: `v-form` com rules, erros por campo (422), botão Salvar desabilitado enquanto não houver mudança (dirty), aviso ao sair com alterações (`onBeforeRouteLeave`), contador da bio (`:73-75`) com `aria-live` ao chegar em 140.
- P1 estrutura em seções: "Perfil", "Contato", "Segurança" (senha), "Aparência" (tema claro/escuro/auto + reduzir animações + som da homenagem), em cards; no desktop 2 colunas com nav lateral por âncoras; mobile 1 coluna.
- P2 "Sair de todos os dispositivos" [BACK].

### 3.11 `CadastrarHistoriaView.vue`
- P0 todos os `<label>` sem `for` (`:10-47`), erros só por borda (`:11,16,22,27`): `TmField` com mensagem. Campos obrigatórios marcados "*" e `aria-required`.
- P0 `:136` toolbar Quill com "image" insere base64 no HTML (payload gigante e quebra dos limites do backend). Remover o botão "image" do editor: imagens vão pelos uploads (fotos), e opcionalmente inserir "foto da galeria da história" no texto (P2). Validar conteúdo vazio (`editor.getText().trim()===''`) antes de enviar.
- P0 `:149-161` selecionar novos arquivos SUBSTITUI a seleção anterior; passar a ACRESCENTAR, evitar duplicatas (nome+tamanho), validar tipo/tamanho (foto <= 10 MB, vídeo <= limite do backend, confirmar), `URL.revokeObjectURL` ao remover/limpar/desmontar (`:151,156` vazam memória). Remover-por-índice (`:163-171`) deve revogar a URL.
- P1 uploads: área de soltar arquivos (drag-and-drop) + botões; grade de pré-visualização com nome/tamanho e "×" 44px com `aria-label="Remover {nome}"`; barra de progresso real (`onUploadProgress` do axios) com "Enviando 42% — não feche esta página"; estado "Salvar" desabilitado; cancelável (`AbortController`).
- P1 `:197-201` após salvar: em vez de limpar o form, oferecer "Ver história" (navegar a `/historias/{uuid}` se a API devolver o uuid; confirmar) e "Contar outra". Toast com ação.
- P1 [FRONT] rascunho automático (`useRascunho('historia-nova')`): salva título, descrição, data, tipo, membros e `editor.root.innerHTML` em `localStorage` a cada 1s de inatividade (debounce), badge "Rascunho salvo às 14:32"; ao abrir, se existir, banner "Você tem um rascunho de {data}. [Continuar] [Descartar]"; limpar ao salvar com sucesso. Arquivos de mídia não entram no rascunho (avisar). `onBeforeRouteLeave` + `beforeunload` se houver alterações não salvas.
- P1 [FRONT] pré-visualização: abas "Escrever | Pré-visualizar" renderizando com `.tm-prose` exatamente como a leitura.
- P1 membros: `usuarios-checkbox` (`:42-47`) vira `v-autocomplete multiple chips` (busca por nome, "Selecionar todos"), pré-selecionar o autor.
- P1 layout: uma coluna estreita no mobile (já há `@media`, mas `!important` em `:547,636` indica conflito); stepper opcional (1 Texto, 2 Mídia, 3 Membros) em mobile; botão Salvar fixo no rodapé (sticky) com estado.
- P2 `tipo`: explicar cada tipo com texto de apoio (Pessoal / Principal / Paralela) — hoje sem explicação.
- P2 atalho `Ctrl+S` salva rascunho.

### 3.12 `CadastrarUsuarioView.vue` ("Siga aqui")
- P0 `:142` `form.append("imagem", foto.value)` envia a string "null" quando não há foto; só anexar se existir (campo marcado `*` "Adicionar Foto *": decidir obrigatoriedade e validar no cliente).
- P0 `UserMenu` aparece na página de cadastro (pública): usar o navbar padrão; página de entrada de visitante deve explicar: "Este cadastro é para quem recebeu uma chave de convite da rua." com bloco explicativo acima de "Nome da Chave/Código da Chave" (`:73-83`) — hoje sem contexto.
- P0 labels `for/id`, erros por campo, `autocomplete` (`name`, `email`, `new-password`, `tel`, `bday`), `type="tel"` com máscara, senha com regra visível (mín. 8) e medidor, toggle de senha como `<button>` (`:38`).
- P1 `:46-50` tipo de usuário com IDs fixos (1,2,3): carregar do backend ou limitar; visitante comum NÃO deve poder se cadastrar como "Membro ADM" (UI e backend: o tipo deve ser definido pela chave; [BACK] confirmar). Na dúvida, esconder o campo para não logados.
- P1 foto: avatar circular com ícone de câmera e recorte; `alt`; preview com revoke.
- P1 sucesso (`:147-149`): em vez de limpar, tela de boas-vindas "Cadastro concluído, {nome}. Agora você faz parte da rua." + botão "Entrar" (`/login`). Evitar limpar sem destino.
- P2 stepper em 3 passos no mobile (Quem é você / Acesso / Chave).

### 3.13 Nova: `NotFoundView.vue` (substitui "Em breve" como 404)
- P0 Layout imersivo (fundo `home-bg.svg` + escuro), título em serifa: "Essa rua não leva a lugar nenhum." Subtexto: "O endereço que você tentou abrir não existe ou mudou de lugar." Botões: "Voltar ao início" (primário), "Ir para Histórias", `router.back()` ("Voltar"). `<h1>` com foco programático, `document.title="Página não encontrada"`, status noindex (`<meta name="robots" content="noindex">`).
- P1 variação `EmBreve` para recursos futuros reais (`/em-breve` com `meta`), para os `/rota-1..3` da órbita enquanto não existirem (ver 3.14): copy "Esta parte da rua ainda está sendo construída." com botão voltar. Sem menu duplicado.

### 3.14 `ARuaView.vue`, `RuaHeader.vue`, `RuaOrbita.vue`
- P0 `RuaOrbita.vue:4,11-37` imagens clicáveis sem `alt`/teclado: `<button class="orbita-item" aria-label="Abrir: {destino}">`, foco visível, `Enter/Espaço`. Rotas `/rota-1..3` (`:96-105`) levam a "em breve": enquanto não existirem, desabilitar visualmente (`aria-disabled`, tooltip "Em breve") ou mapear para páginas reais (ex.: fotos, vídeos, histórias). Decidir com o dono do produto (ver seção 6).
- P0 `:50-150` duas funções de transição quase idênticas e `getElementById` global: unificar numa função `transicionarPara(url, el, opts)` (ver App.vue); reduced-motion -> `router.push` direto. Fallback quando `overlay` é nulo (a função do centro `:50-93` não checa e quebra).
- P1 `ARuaView.vue:12-13` `<br>` como espaçamento e instrução "Clique em uma logo." -> `p.dica` com ícone e texto "Toque em um círculo para explorar" (mobile: "Toque"). Dica some após a 1ª interação (localStorage).
- P1 `:20` CTA "Siga aqui" é ambíguo -> "Quero fazer parte" (visitante) / "Ver membros" (logado, vai a `/membros` [FRONT 4.A-9]); só mostrar "Cadastro" a visitantes.
- P1 [FRONT/BACK] avatares reais na órbita: hoje fixos em `/login/7.jpg`, `3.jpg`, `4.jpg`. Com `GET /usuarios` (logado) usar fotos dos membros; para visitante precisaria endpoint público de membros (4.B-8).
- P1 mobile: órbita deve caber em 100vw sem scroll horizontal; hit area >= 48px; animação orbital pausa com `prefers-reduced-motion` e quando a aba está oculta (`visibilitychange`) para economizar bateria.
- P1 `RuaHeader.vue` é cópia de `AppHeader.vue`: remover (T9). Os 4 `<img dots>` (`:56-59`) -> `alt=""` `aria-hidden`, e o SVG `dots.svg` como background CSS.

### 3.15 `nao-te-esquecemos-amigoView.vue` ("Aquele que sonhou")
- P0 áudio: `:106-116` tenta autoplay (bloqueado na maioria dos navegadores, e invasivo). Começar silencioso: botão "Tocar música" visível (rótulo + ícone, `aria-pressed`, `:41` hoje só ▶ ⏸) e lembrar a escolha (`localStorage`). Se o usuário já interagiu (veio do clique na órbita, que é um gesto), pode iniciar com volume 0 -> fade-in até 0.3 em 2s. `prefers-reduced-motion`: sem fade, sem autoplay. Parar o áudio ao sair da rota (já faz em `onBeforeUnmount`, manter) e ao ocultar a aba. Arquivo `italoSong.mp3` (21 MB na pasta): servir com `preload="none"`, compactar (devops).
- P0 texto da frase `:10-36`: revelação linha a linha por `setTimeout` (`:146-151`) usa `document.querySelectorAll` global e não cancela timers ao sair; usar `ref` + CSS `animation-delay`, e em reduced-motion mostrar tudo de uma vez. Estrutura semântica: `<blockquote>` com o acróstico ITALO destacado em `<strong>` (leitores de tela leem as letras iniciais como parte da palavra: usar `aria-label` com a frase completa e `aria-hidden` nas letras soltas).
- P1 partículas (`:76-143`): script de CDN (`:84`) vs pacote npm instalado: usar import dinâmico do pacote; `color:#000000` sobre `italo-bg.svg`: verificar contraste do fundo; reduzir `number` para 30 em mobile; desligar em reduced-motion; pausar quando a aba está oculta.
- P1 contexto/legenda: `<h1 class="sr-only">Aquele que sonhou</h1>` e, abaixo da frase, bloco opcional (vídeo/fotos/depoimentos) — conteúdo do dono; sugerir "Deixar uma lembrança" (4.B). Botão "Voltar à rua" visível (hoje só o `UserMenu`).
- P1 mobile: texto central 22-28px, sem corte (já há media 768, validar a 360px).

### 3.16 `AdminCriarNotificacaoView.vue` (`/notificacoes`)
- P0 guard por papel (T2) e renomear a rota; backend deve restringir `POST /notificacoes` (hoje qualquer logado).
- P0 `:93-97`: erros não-422 são engolidos (sem feedback). Usar `useApiError` + toast. `:9` banner de sucesso inline -> toast; limpar e focar o título.
- P1 `:31` rótulo diz "Descrição (Markdown)" mas o editor é Quill e envia HTML (`:85`): corrigir o rótulo para "Mensagem". `:26` link: `type="text"` com placeholder `/galeria-videos`: validar (interno começa com `/`, ou URL https), com seletor rápido de destinos (select "Vídeos / Fotos / Histórias / Atualizações / Personalizado").
- P1 pré-visualização lado a lado ("Como aparece no sino": título + tema + data), contador de caracteres, "Tema" como `v-combobox` com sugestões (Aviso, Novidade, Aniversário, Homenagem).
- P2 histórico das notificações enviadas [BACK 4.B-6].

### 3.17 Modais de criação/edição: `CriarFotoModal`, `CriarVideoModal`, `EditarHistoriaModal`
- P0 todos: botão fechar `v-icon` sem foco (`CriarFotoModal.vue:5`) -> `v-btn icon` com `aria-label="Fechar"`; Esc fecha (padrão do `v-dialog`), mas com alterações pendentes pedir confirmação. `:persistent` durante upload.
- P0 `CriarFotoModal.vue:111` `setTimeout(() => fechar(), 800)` sem necessidade: fechar logo após sucesso e emitir `sucesso` para a galeria atualizar (hoje as galerias NÃO recarregam após criar foto/vídeo; incluir a nova foto no topo da lista quando a rota atual é a galeria).
- P0 `EditarHistoriaModal.vue:61,72` `block="isMobile"` -> `:block="isMobile"`. `:128` lista de usuários = só os atuais da história (`historia.usuarios`): impossível ADICIONAR membros. Carregar `listarUsuarios()` completo e marcar os atuais; permitir editar também data e conteúdo (hoje só título, descrição, tipo, membros) ou deixar explícito ("Para editar o texto, abra a história").
- P1 `CriarFotoModal.vue:19`: valida tipo/tamanho; área drag-and-drop; `tipoImagemId = 3` fixo (`:71`) -> constante nomeada/ocultar; data com `max=hoje`. Barra de progresso de upload. `fullscreen` em mobile (como o modal de editar já faz).
- P1 `CriarVideoModal`: aceitar também link (YouTube/Vimeo) se o produto quiser [BACK], aviso de tamanho máximo antes de enviar, progresso, cancelar.
- P1 campos Vuetify (`v-text-field` etc.) nos modais, no lugar dos `input` customizados, para herdar foco/erro/tema (hoje há dois estilos de campo convivendo: `.field input` em 6 arquivos e `v-text-field` em 4).

### 3.18 Código a remover/mesclar (P1, sem mudança visível)
`components/icons/*`, `stores/counter.js`, `assets/base.css`, `assets/main.css`, `assets/logo.svg` (se sem referência), `SideMenu.vue`, `UserMenuTm.vue` + `TopActions.vue` (ou mantê-lo como wrapper fino do navbar), `RuaHeader.vue`, `EmBreveView.vue` (após NotFound), dependência `animejs`, `HomeView.vue:64-139` (CSS de menu legado e `.avatar`, `.dropdown`, `.top-actions` duplicados), `HomeView.vue:22,27` (`authStore` não usado). Verificar que `vite-plugin-vue-devtools` fica fora do build de produção.

---

## 4. Features novas

### 4.A Somente frontend (API atual já atende)

| # | Prio | Feature | Detalhe |
|---|---|---|---|
| A1 | P0 | Guards + redirect pós-login + 404 real + sessão expirada | T2, T4, T5, 3.4, 3.13. |
| A2 | P0 | Estados padrão (skeleton/vazio/erro/retry) em todas as telas | `TmSkeletonGrid`, `TmEmptyState`, `TmErrorState`. |
| A3 | P0 | Confirmação de ações destrutivas + feedback unificado | `ConfirmDialog`, `useNotify`, fim de `alert/confirm`. |
| A4 | P1 | Busca e filtros nas histórias (título, descrição, membro, tipo, ordem) com estado na URL | 3.5. |
| A5 | P1 | Lightbox acessível (teclado, swipe, zoom, contador, compartilhar link) para fotos e vídeos | 3.8 `TmLightbox`; deep link `?foto=uuid` usa `GET /fotos/{uuid}`. |
| A6 | P1 | Scroll infinito robusto + "Carregar mais" + contagem | 3.8. |
| A7 | P1 | Rascunho automático da história + aviso de saída + pré-visualização | 3.11. |
| A8 | P1 | Navbar única, menu por papel, breadcrumbs/voltar, skip-link, títulos de página | 3.1, T3. |
| A9 | P1 | Notificações melhores no sino (badge numérico, polling leve, links internos, estados) | 3.1. |
| A10 | P1 | Compartilhar (Web Share/Copiar link) em história, foto, atualização | 3.6, 3.8. |
| A11 | P1 | Indicador de "Novo" em Atualizações (localStorage) | 3.9. |
| A12 | P1 | "Últimas histórias" na home para visitantes (usa `/historias/limite/{n}`, pública) | 3.3. |
| A13 | P1 | Upload com drag-and-drop, validação, progresso, cancelamento | 3.11, 3.17. |
| A14 | P1 | Dark mode (auto + manual) e preferência "reduzir animações" | 2.4, 2.3, 3.10. |
| A15 | P2 | Página "Membros" (`/membros` e `/membros/:uuid`): cards com foto, nome, bio (`GET /usuarios`, `GET /usuarios/{uuid}`) + histórias em que participa (filtro client-side de `/historias/resumo` por `usuarios[].uuid`). Alimenta o "Siga aqui/Ver membros" da A Rua. Só logados. |
| A16 | P2 | Aniversariantes do mês na home (logados) | 3.3 (confirmar se o resource lista `aniversario`). |
| A17 | P2 | Linha do tempo de histórias | 3.5 (requer um campo extra, ver B1: trivial). |
| A18 | P2 | Modo leitura (tamanho da fonte, largura), imprimir, índice, progresso de leitura | 3.6. |
| A19 | P2 | Seleção múltipla nas galerias (excluir/baixar vários) | 3.8. |
| A20 | P2 | Playlist de músicas da história com fila | 3.7. |
| A21 | P2 | Atalhos: `/` busca, `g h` home, `?` ajuda; `Esc` fecha | acessibilidade e produtividade. |
| A22 | P2 | PWA básico (manifest + ícones) para "instalar" no celular; sem offline agressivo (assets pesados) | |
| A23 | P2 | Compressão de imagem no cliente antes do upload (canvas, 2048px, qualidade 0.85) | reduz tempo de envio no celular. |

### 4.B Precisam de backend novo (endpoint sugerido)

| # | Prio | Feature | Endpoint / mudança |
|---|---|---|---|
| B1 | P1 | `data_historia` e `total` de mídia no resumo (linha do tempo, contagem nos cards) | `GET /historias/resumo`: acrescentar `data_historia` (ISO), `fotos_count`, `videos_count`, `musicas_count` em `HistoriaResumoResource`. Opcional: `?page=&per_page=&q=&tipo=&usuario=` com `meta` de paginação. |
| B2 | P1 | Mídia da história com URL pronta (elimina N+1 serial de `GaleriaFotos.vue:73-80`) | `GET /historias/{uuid}` devolver `fotos: [{uuid,url,titulo,descricao}]`, `videos: [{uuid,url,poster_url,titulo,duracao}]`, `musicas: [{uuid,url,titulo}]` (hoje arrays de uuid). |
| B3 | P1 | Busca/filtros nas galerias | `GET /fotos?q=&ano=&usuario_uuid=&ordem=&page=`; `GET /videos?...` idem. |
| B4 | P1 | Poster/miniatura e duração de vídeo | Gerar com ffmpeg no upload; campos `poster_url`, `duracao_segundos` em `VideoResource`. Opcional: miniaturas WebP de fotos (`thumb_url`, `largura`, `altura`) para grids rápidos. |
| B5 | P1 | `GET /me` (usuário autenticado completo) e `UsuarioAuthResource` com `telefone`, `aniversario`, `descricao` | corrige Configurações e dispensa `GET /usuarios/{uuid}`; também para sincronizar sessão ao abrir o app. |
| B6 | P1 | Papéis no servidor | `POST /notificacoes` restrito a `MEMBRO_ADM`; `PUT/DELETE /historias/{uuid}` a ADM/RUA (ou ao autor); `GET /videos` decidir se público (para o visitante) ou manter privado (a UI trata). Encaminhar ao backend + security. |
| B7 | P1 | Esqueci/redefinir senha | `POST /esqueci-senha {email}` (resposta genérica, rate limit), `POST /redefinir-senha {token,email,senha}`; telas `/esqueci-senha`, `/redefinir-senha`. |
| B8 | P2 | Membros públicos (órbita da A Rua para visitantes) | `GET /public/membros` -> `[{uuid,nome,foto_url}]` (sem e-mail/telefone). |
| B9 | P2 | Central de notificações | `GET /notificacoes?status=todas|nao_lidas&page=`, `GET /notificacoes/contagem` (só número, barato para polling), `DELETE /notificacoes/{id}`; página `/notificacoes/minhas` com histórico e filtro; admin: `GET /admin/notificacoes` (enviadas). |
| B10 | P2 | "Deixar uma lembrança" (comentários/homenagens) | `GET /historias/{uuid}/lembrancas?page=`, `POST /historias/{uuid}/lembrancas {texto}`, `DELETE /lembrancas/{uuid}` (autor/ADM). Também na página da homenagem. |
| B11 | P2 | Favoritos ("Guardar na memória") | `POST/DELETE /historias/{uuid}/favorito`, `GET /me/favoritos`. |
| B12 | P2 | Álbuns | `POST/GET /albuns`, associar fotos a álbum/história, capa do álbum. |
| B13 | P2 | Busca global | `GET /busca?q=` -> `{historias, fotos, videos, membros}` (3 itens por grupo) alimentando um `v-dialog` `Ctrl+K`. |
| B14 | P2 | Sanitização no servidor | HTMLPurifier em `conteudo` de história/atualização/notificação (defesa em profundidade além do DOMPurify do front). |
| B15 | P2 | Reordenar mídia / definir capa da história | `PATCH /historias/{uuid}/midia/ordem`, `PATCH /historias/{uuid}/capa`. |
| B16 | P2 | Preferências do usuário | `PUT /me/preferencias {tema, reduzir_animacao, som_homenagem}` (opcional; localStorage cobre o básico). |

---

## 5. Critérios de aceite (verificáveis)

Segurança/funcionalidade
- AC1: Sem login, abrir `/historias`, `/historias/criar`, `/galeria-videos`, `/configuracoes`, `/notificacoes` redireciona para `/login?redirect=<rota>`; após entrar, o usuário cai na rota original. `/login` logado redireciona para `/`.
- AC2: Logado com papel sem permissão em `/admin/notificacoes/nova` ou `/historias/criar` vê página "Sem permissão" (ou redirect + toast), nunca o formulário.
- AC3: Qualquer URL inexistente (`/xyz`, `/historias/a/b`) mostra `NotFoundView` com `<h1>`, links de volta e `document.title` "Página não encontrada".
- AC4: Logout remove `tunel-auth` E `auth_token` do `localStorage`; uma resposta 401 numa chamada protegida leva a `/login?redirect=...` com toast de sessão expirada.
- AC5: "Salvar alterações" e "Mudar foto" em `/configuracoes` funcionam sem erro de console, atualizam o avatar do menu sem recarregar e mostram toast; campos telefone/aniversário/bio vêm preenchidos.
- AC6: Nenhum `alert(`/`confirm(` no código (`grep` retorna 0). Toda exclusão passa por `ConfirmDialog` com foco inicial em "Cancelar".
- AC7: `v-html` só recebe conteúdo passado por `DOMPurify` (teste: história com `<img src=x onerror=alert(1)>` não executa).

Acessibilidade
- AC8: Toda a navegação, menus, galerias, lightbox, modais e formulários são operáveis só por teclado (Tab/Shift+Tab/Enter/Espaço/Esc/setas), com ordem lógica, anel de foco visível >=3:1 e foco devolvido ao gatilho ao fechar diálogos. Lighthouse Accessibility >= 95 em `/`, `/login`, `/historias`, `/galeria-fotos`.
- AC9: Contraste >= 4,5:1 (texto) e >= 3:1 (UI/ícones) nos temas claro e escuro, medido nos pares da seção 2.1; botão ouro usa texto escuro.
- AC10: Todo `<img>` informativo tem `alt` descritivo; decorativas `alt=""`. Todo controle ícone tem `aria-label`. Todo `<input>` tem `<label for>` ou `aria-label`; erros ligados por `aria-describedby` + `aria-invalid`; erro de login em `role="alert"`.
- AC11: Com `prefers-reduced-motion: reduce`: sem `.reveal`, sem transição GSAP da órbita (navegação imediata), sem partículas, sem autoplay de áudio/vídeo, animações <= 0,01ms.
- AC12: Existe skip-link, um `<main id="conteudo">`, um `<h1>` por página e `lang="pt-BR"`; o foco vai ao `<h1>` após cada navegação.

Design system/consistência
- AC13: `grep -rE "#[0-9a-fA-F]{3,8}" src --include=*.vue` retorna apenas exceções documentadas (SVG/ilustrações); cores vêm de `rgb(var(--v-theme-*))`/`var(--tm-*)`. `base.css`, `main.css`, `components/icons`, `counter.js`, `SideMenu`, `UserMenuTm`, `RuaHeader`, `EmBreveView` (se removida) não existem mais; `animejs` fora do `package.json`.
- AC14: Existe um único header/menu de usuário; todas as telas internas usam `AppNavbar`; nenhuma rota do menu aponta para URL inexistente (script/teste que percorre os links do menu e confirma resolução de rota diferente de 404).
- AC15: Raios usados pertencem à escala {8,12,16,24,32,48,999}; sombras às 3 definidas; duplicação de CSS entre `FotosGaleriaView` e `VideosGaleriaView` eliminada (ambas usam `MediaGrid`).

Estados e feedback
- AC16: Cada tela de dados exibe skeleton no carregamento, estado vazio com ação, e estado de erro com "Tentar novamente"; simular API offline em DevTools mostra erro (não "Carregando..." infinito) nas galerias, histórias, atualizações e sino.
- AC17: Toasts não cobrem sino/avatar (posição inferior), erros ficam >= 7s com `role="alert"`, sucesso 4s, com pausa no hover. Todos os erros de API passam por `useApiError` (nenhuma cópia do bloco 422).
- AC18: Formulários mostram erro por campo do 422 e levam o foco ao primeiro inválido. Sair de `/historias/criar` ou `/configuracoes` com alterações pede confirmação; rascunho da história é restaurado após recarregar a página e removido após salvar.

Performance
- AC19: Todas as rotas são lazy; o bundle inicial (`vite build`) não contém `quill`, `@toast-ui/editor`, `gsap` nem `marked` (verificar com `rollup-plugin-visualizer` ou saída de `vite build`: chunks separados). Vuetify sem `import * as components`. Meta: JS inicial gz <= 200 KB (aferir e registrar o valor de partida).
- AC20: LCP mobile (Lighthouse, 4G lento) <= 2,5s em `/login` e `/`; CLS <= 0,1 (imagens com dimensões). Imagens de grade `loading="lazy"`; nenhum `<video>` baixa mídia antes da interação (`preload="none"` ou poster).
- AC21: Galeria de história com 24 fotos carrega em paralelo (rede mostra requisições simultâneas) e exibe placeholders imediatamente.

Responsividade (testar a 360, 390, 768, 1024, 1440 px)
- AC22: Sem scroll horizontal em nenhuma tela; alvos de toque >= 44px; navbar vira barra + drawer < 960px; modais de formulário em tela cheia < 600px; lightbox permite swipe; `100dvh` em vez de `100vh`.

Funcionalidade nova
- AC23: Histórias: busca (debounce) filtra por título/descrição/membro; chips de tipo; ordenação; estado na URL sobrevive a F5 e ao botão voltar; vazio explica o que fazer.
- AC24: Lightbox: setas/Esc/swipe funcionam, contador correto, foto anterior/próxima pré-carregada, "Copiar link" copia URL com `?foto=` que ao abrir leva direto à foto.
- AC25: Dark mode: alternância persistida em `localStorage`, respeita `prefers-color-scheme` no 1º acesso, sem flash (aplicar tema antes da montagem do Vue).

---

## 6. Decisões a confirmar com o usuário (antes de implementar)

1. Tom e copy: sugestões de texto acima (login, 404, vazios) mudam o tom de "gíria" para "acolhedor/memorial". Aprovar ou ajustar. Manter emojis nos toasts ou não.
2. Fonte serifa (Fraunces) nos títulos: aceitar nova dependência `@fontsource-variable/*` (self-host) ou usar `Georgia` sem dependência.
3. Editor único: Quill para tudo (recomendado) vs manter toast-ui para atualizações em Markdown. Impacta formato salvo em `conteudo_markdown` (atualizações antigas continuam renderizadas com `marked`).
4. Destinos reais dos 3 avatares da órbita (`/rota-1..3`): o que são? Até definir, desabilitar com "Em breve".
5. `/galeria-videos` para visitantes: manter privada (login obrigatório; recomendação da UI atual) ou abrir o `GET /videos` no backend.
6. "Sobre a conta": cadastro público com "Tipo de usuário" selecionável (Membro ADM/Rua/Revisor) é risco; confirmar com backend que o tipo é derivado da chave. Valores exatos de `tipo_usuario` retornados em `UsuarioAuthResource` (para os guards por papel).
7. Conteúdo simulado em `FeaturedVideoCard` ("13k de inscritos", "Conheça", "há algumas tardes", "Quanto Mais Eu Como..."): é intencional (humor/identidade) ou resíduo?
8. Autoplay da música na homenagem: aprovar mudança para "começa silenciosa com botão visível" (padrão dos navegadores já bloqueia o autoplay).
9. Pasta `public/` (33 MB de vídeo, 21 MB de áudio, SVG de 1 MB): autorizar compressão/transcodificação (devops) e mover mídia pesada para storage/CDN.
10. Escopo de backend (seção 4.B): quais itens B1-B16 entram agora. Recomendado mínimo para a primeira entrega: B1, B2, B5, B6.

## 7. Ordem sugerida de implementação (para o orquestrador)

1. Fundação: T1 (tokens, tema, vuetify defaults), T3 (lazy routes, guard, títulos), T4/T5, componentes `ui/*`, composables (`useApiError`, `useNotify`, `useConfirm`), remoção de código morto. (frontend)
2. B5/B6 em paralelo (backend) e correção de `ConfiguracoesView` + papéis.
3. Navbar única + menu (3.1) + A11y global + reduced-motion.
4. Telas por valor: Login -> Histórias (lista + leitura) -> Galerias + Lightbox -> Cadastros (história, usuário) -> Configurações -> Atualizações/Notificações -> Home/A Rua/Homenagem.
5. Features P2 e backend B3/B4/B7-B16 conforme decisão da seção 6.
6. qa-specialist: testes e2e dos AC1-AC8, AC16-AC18, AC22-AC25 (Playwright); security-specialist: AC7/B6/B14; code-reviewer antes do PR.
