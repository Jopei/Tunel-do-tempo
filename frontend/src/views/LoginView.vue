<template>
  <AuthLayout>
    <div class="login-form">
        <h1 tabindex="-1">Que bom te ver por aqui.</h1>
        <p>Entre com sua conta de membro ou continue como visitante.</p>
        <p v-if="veioDeRotaProtegida" class="aviso" role="note">
          Entre para ver as histórias da rua.
        </p>

        <form novalidate @submit.prevent="handleLogin">
          <v-text-field
            id="login-email"
            ref="emailRef"
            v-model="email"
            label="E-mail"
            type="email"
            autocomplete="email"
            inputmode="email"
            required
            :error-messages="erros.email"
            :aria-invalid="!!erros.email"
            prepend-inner-icon="mdi-email-outline"
          />

          <v-text-field
            id="login-senha"
            v-model="senha"
            label="Senha"
            :type="mostrarSenha ? 'text' : 'password'"
            autocomplete="current-password"
            required
            :error-messages="erros.senha"
            :aria-invalid="!!erros.senha"
            prepend-inner-icon="mdi-lock-outline"
          >
            <template #append-inner>
              <v-btn
                type="button"
                icon
                variant="text"
                size="small"
                :aria-label="mostrarSenha ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="mostrarSenha"
                @click="mostrarSenha = !mostrarSenha"
              >
                <v-icon>{{ mostrarSenha ? "mdi-eye-off-outline" : "mdi-eye-outline" }}</v-icon>
              </v-btn>
            </template>
          </v-text-field>

          <v-alert
            v-if="erro"
            type="error"
            variant="tonal"
            density="comfortable"
            class="mb-4 text-left"
            role="alert"
            icon="mdi-alert-circle-outline"
          >
            {{ erro }}
          </v-alert>

          <div class="esqueci">
            <router-link :to="{ name: 'esqueci-senha', query: email ? { email } : {} }">Esqueci minha senha</router-link>
          </div>

          <v-btn
            type="submit"
            color="primary"
            variant="flat"
            size="large"
            block
            :loading="loading"
            :aria-busy="loading"
            class="entrar"
          >
            {{ loading ? "Entrando..." : "Entrar" }}
          </v-btn>
        </form>

        <div class="outros">
          <v-btn variant="text" color="primary-text" @click="entrarComoVisitante">
            Continuar como visitante
          </v-btn>
          <router-link to="/cadastrar/usuarios" class="convite">
            Tem uma chave de convite? Cadastre-se
          </router-link>
        </div>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import AuthLayout from "@/components/auth/AuthLayout.vue";
import { login } from "@/services/auth.service";
import { useNotify } from "@/composables/useNotify";
import { interpretarErro } from "@/composables/useApiError";

const router = useRouter();
const route = useRoute();
const notify = useNotify();

const email = ref("");
const senha = ref("");
const erro = ref("");
const erros = ref({});
const loading = ref(false);
const mostrarSenha = ref(false);
const emailRef = ref(null);

const veioDeRotaProtegida = computed(() => !!route.query.redirect);

function destinoSeguro() {
  const r = route.query.redirect;
  return typeof r === "string" && r.startsWith("/") && !r.startsWith("//") ? r : "/";
}

function validar() {
  const e = {};
  if (!email.value.trim()) e.email = "Informe seu e-mail.";
  else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) e.email = "Digite um e-mail válido.";
  if (!senha.value) e.senha = "Informe sua senha.";
  erros.value = e;
  return Object.keys(e).length === 0;
}

async function handleLogin() {
  erro.value = "";
  if (!validar()) return;
  loading.value = true;

  try {
    const response = await login(email.value.trim(), senha.value);
    const primeiroNome = response.usuario?.nome?.split(" ")[0];
    notify.sucesso(primeiroNome ? `Bem-vindo de volta, ${primeiroNome}.` : "Bem-vindo de volta.");
    router.push(destinoSeguro());
  } catch (e) {
    const info = interpretarErro(e);
    if (info.status === 401) erro.value = "E-mail ou senha incorretos.";
    else if (info.status === 422) erro.value = "Confira o e-mail e a senha informados.";
    else erro.value = info.mensagem;
    senha.value = "";
    emailRef.value?.focus?.();
  } finally {
    loading.value = false;
  }
}

function entrarComoVisitante() {
  router.push("/");
}
</script>

<style scoped>
.login-form {
  text-align: center;
}
.login-form h1 {
  font-size: var(--tm-fs-2xl);
  margin-bottom: 8px;
}
.login-form p {
  color: var(--tm-ink-muted);
  margin-bottom: 20px;
}
.login-form .aviso {
  color: var(--tm-gold-text);
  font-weight: 600;
}
.entrar {
  color: rgb(var(--v-theme-on-primary)) !important;
  font-weight: 700;
}
.outros {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.convite {
  color: var(--tm-gold-text);
  font-size: var(--tm-fs-sm);
  padding: 8px;
}
.esqueci {
  text-align: right;
  margin: -8px 0 12px;
}
.esqueci a {
  color: var(--tm-gold-text);
  font-weight: 600;
  display: inline-block;
  padding: 10px 0;
}
</style>
