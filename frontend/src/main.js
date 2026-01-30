import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "@/stores/auth";

import vuetify from "./plugins/vuetify";

const app = createApp(App);

const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(vuetify);

const authStore = useAuthStore();
authStore.carregarSessao();

app.mount("#app");
