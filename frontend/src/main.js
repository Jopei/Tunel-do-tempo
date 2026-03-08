import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "@/stores/auth";

import vuetify from "./plugins/vuetify";

import Vue3Toastify from "vue3-toastify";
import "vue3-toastify/dist/index.css";

const app = createApp(App);

const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(vuetify);

app.use(Vue3Toastify, {
  autoClose: 3000,
  position: "top-right",
  theme: "colored",
});

const authStore = useAuthStore();
authStore.carregarSessao();

app.mount("#app");
