import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "@/stores/auth";

import vuetify from "./plugins/vuetify";
import { iniciarTema } from "@/composables/useTema";

import "./assets/tokens.css";

import Vue3Toastify from "vue3-toastify";
import "vue3-toastify/dist/index.css";
import "./assets/toast.css";

const app = createApp(App);

const pinia = createPinia();

app.use(pinia);

// Sessão e tema antes do router/montagem: guards precisam do usuário e o tema não pode piscar.
useAuthStore().carregarSessao();
iniciarTema(vuetify);

app.use(router);
app.use(vuetify);

// Toasts embaixo para não cobrir sino/avatar; centralizado no mobile.
app.use(Vue3Toastify, {
  autoClose: 4000,
  position: window.matchMedia("(min-width: 960px)").matches ? "bottom-right" : "bottom-center",
  theme: "colored",
  transition: "slide",
  pauseOnHover: true,
});

app.mount("#app");
