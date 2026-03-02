import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";
import LoginView from "@/views/LoginView.vue";
import CadastrarHistoriaView from "@/views/CadastrarHistoriaView.vue";
import HistoriasView from "@/views/HistoriasView.vue";
import HistoriaShowView from "@/views/HistoriaShowView.vue";
import NaoTeEsquecemosAmigoView from "@/views/nao-te-esquecemos-amigoView.vue";
import CadastrarUsuarioView from "@/views/CadastrarUsuarioView.vue";
import ARuaView from "@/views/ARuaView.vue";
import FotosGaleriaView from "@/views/FotosGaleriaView.vue";
import VideosGaleriaView from "@/views/VideosGaleriaView.vue";
import AdminCriarNotificacaoView from "@/views/admin/AdminCriarNotificacaoView.vue";
import AtualizacoesView from "@/views/AtualizacoesView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: HomeView,
    },
    {
      path: "/login",
      name: "login",
      component: LoginView,
    },
    {
      path: "/historias",
      name: "historias-listar",
      component: HistoriasView,
      meta: { requiresAuth: true },
    },
    {
      path: "/historias/criar",
      name: "historias-criar",
      component: CadastrarHistoriaView,
      meta: { requiresAuth: true },
    },
    {
      path: "/historias/:uuid",
      name: "historias-show",
      component: HistoriaShowView,
      meta: { requiresAuth: true },
    },
    {
      path: "/aquele-que-sonhou",
      name: "aquele-que-sonhou",
      component: NaoTeEsquecemosAmigoView,
    },
    {
      path: "/cadastrar/usuarios",
      name: "cadastrar-usuarios",
      component: CadastrarUsuarioView,
    },
    {
      path: "/a-rua",
      name: "ARua",
      component: ARuaView,
    },
    {
      path: "/galeria-fotos",
      name: "fotos",
      component: FotosGaleriaView,
    },
    {
      path: "/galeria-videos",
      name: "videos",
      component: VideosGaleriaView,
    },
    {
      path: "/notificacoes",
      name: "notificacoes",
      component: AdminCriarNotificacaoView,
    },
    {
      path: "/atualizacoes",
      name: "atualizacoes",
      component: AtualizacoesView,
    },
    {
      path: "/:pathMatch(.*)*",
      name: "em-breve",
      component: () => import("@/views/EmBreveView.vue"),
    },
  ],
});

export default router;
