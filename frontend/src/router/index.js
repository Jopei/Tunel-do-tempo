import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore, PAPEIS_MEMBRO, PAPEL_ADMIN } from "@/stores/auth";

const SUFIXO_TITULO = " · Túnel do Tempo";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    if (to.hash) return { el: to.hash, behavior: "smooth" };
    return { top: 0 };
  },
  routes: [
    {
      path: "/",
      name: "home",
      component: () => import("@/views/HomeView.vue"),
      meta: { title: "Início", immersive: true },
    },
    {
      path: "/login",
      name: "login",
      component: () => import("@/views/LoginView.vue"),
      meta: { title: "Entrar", hideNavbar: true, guestOnly: true },
    },
    {
      path: "/esqueci-senha",
      name: "esqueci-senha",
      component: () => import("@/views/EsqueciSenhaView.vue"),
      meta: { title: "Esqueci minha senha", guestOnly: true, hideNavbar: true },
    },
    {
      path: "/redefinir-senha",
      name: "redefinir-senha",
      component: () => import("@/views/RedefinirSenhaView.vue"),
      meta: { title: "Redefinir senha", hideNavbar: true },
    },
    {
      path: "/notificacoes/minhas",
      name: "minhas-notificacoes",
      component: () => import("@/views/MinhasNotificacoesView.vue"),
      meta: { title: "Minhas notificações", requiresAuth: true },
    },
    {
      path: "/minhas-memorias",
      name: "minhas-memorias",
      component: () => import("@/views/MinhasMemoriasView.vue"),
      meta: { title: "Minhas memórias", requiresAuth: true },
    },
    {
      path: "/historias",
      name: "historias-listar",
      component: () => import("@/views/HistoriasView.vue"),
      meta: { title: "Histórias", requiresAuth: true },
    },
    {
      path: "/historias/criar",
      name: "historias-criar",
      component: () => import("@/views/CadastrarHistoriaView.vue"),
      meta: { title: "Contar uma história", requiresAuth: true, roles: PAPEIS_MEMBRO },
    },
    {
      path: "/historias/:uuid",
      name: "historias-show",
      component: () => import("@/views/HistoriaShowView.vue"),
      meta: { title: "História", requiresAuth: true },
    },
    {
      path: "/aquele-que-sonhou",
      name: "aquele-que-sonhou",
      component: () => import("@/views/nao-te-esquecemos-amigoView.vue"),
      meta: { title: "Italo, aquele que sonhou", immersive: true },
    },
    {
      path: "/cadastrar/usuarios",
      name: "cadastrar-usuarios",
      component: () => import("@/views/CadastrarUsuarioView.vue"),
      meta: { title: "Quero fazer parte", hideNavbar: true },
    },
    {
      path: "/a-rua",
      name: "ARua",
      component: () => import("@/views/ARuaView.vue"),
      meta: { title: "A Rua", immersive: true },
    },
    {
      path: "/galeria-fotos",
      name: "fotos",
      component: () => import("@/views/FotosView.vue"),
      meta: { title: "Fotos" },
    },
    {
      path: "/galeria-videos",
      name: "videos",
      component: () => import("@/views/VideosView.vue"),
      meta: { title: "Vídeos", requiresAuth: true },
    },
    {
      path: "/videos/:uuid",
      name: "video",
      component: () => import("@/views/VideoPlayerView.vue"),
      meta: { title: "Vídeo", requiresAuth: true },
    },
    {
      path: "/admin/notificacoes/nova",
      name: "notificacoes",
      component: () => import("@/views/admin/AdminCriarNotificacaoView.vue"),
      meta: { title: "Nova notificação", requiresAuth: true, roles: [PAPEL_ADMIN] },
    },
    { path: "/notificacoes", redirect: { name: "notificacoes" } },
    {
      path: "/atualizacoes",
      name: "atualizacoes",
      component: () => import("@/views/AtualizacoesView.vue"),
      meta: { title: "Atualizações" },
    },
    {
      path: "/configuracoes",
      name: "configuracoes",
      component: () => import("@/views/ConfiguracoesView.vue"),
      meta: { title: "Meu perfil", requiresAuth: true },
    },
    {
      path: "/membros",
      name: "membros",
      component: () => import("@/views/MembrosView.vue"),
      meta: { title: "Membros", requiresAuth: true },
    },
    {
      path: "/membros/:slug",
      name: "membro",
      component: () => import("@/views/MembroView.vue"),
      meta: { title: "Membro", requiresAuth: true, immersive: true },
    },
    {
      path: "/musicas",
      name: "musicas",
      component: () => import("@/views/MusicasView.vue"),
      meta: { title: "Músicas", requiresAuth: true },
    },
    {
      path: "/fotos/albuns",
      name: "fotos-albuns",
      component: () => import("@/views/AlbunsView.vue"),
      meta: { title: "Álbuns", requiresAuth: true },
    },
    {
      path: "/fotos/albuns/:uuid",
      name: "fotos-album",
      component: () => import("@/views/AlbumView.vue"),
      meta: { title: "Álbum", requiresAuth: true },
    },
    {
      path: "/em-breve",
      name: "em-breve",
      component: () => import("@/views/EmBreveView.vue"),
      meta: { title: "Em breve", immersive: true },
    },
    {
      path: "/sem-permissao",
      name: "sem-permissao",
      component: () => import("@/views/SemPermissaoView.vue"),
      meta: { title: "Sem permissão", immersive: true },
    },
    {
      path: "/:pathMatch(.*)*",
      name: "nao-encontrado",
      component: () => import("@/views/NotFoundView.vue"),
      meta: { title: "Página não encontrada", immersive: true },
    },
  ],
});

router.beforeEach((to) => {
  const auth = useAuthStore();

  if (to.meta.requiresAuth && !auth.logado) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  if (to.meta.guestOnly && auth.logado) {
    return { name: "home" };
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.papel)) {
    return { name: "sem-permissao", query: { de: to.fullPath } };
  }
});

router.afterEach((to) => {
  const titulo = to.meta.title;
  document.title = titulo ? titulo + SUFIXO_TITULO : "O Túnel do Tempo";

  // Move o foco para o conteúdo após cada navegação (leitores de tela).
  requestAnimationFrame(() => {
    const alvo = document.querySelector("#conteudo h1") || document.getElementById("conteudo");
    if (alvo) {
      if (!alvo.hasAttribute("tabindex")) alvo.setAttribute("tabindex", "-1");
      alvo.focus({ preventScroll: true });
    }
  });
});

export default router;
