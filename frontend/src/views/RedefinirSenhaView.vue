<template>
  <PaginaCurta titulo="Criar nova senha" :texto="linkInvalido ? '' : 'Escolha uma senha com pelo menos 8 caracteres.'">
    <div v-if="linkInvalido || expirado" role="alert">
      <v-alert type="error" variant="tonal" class="mb-4">
        {{ expirado ? "Este link é inválido ou expirou." : "Link incompleto." }} Peça um novo link para continuar.
      </v-alert>
      <v-btn to="/esqueci-senha" variant="flat" color="primary" block>Pedir novo link</v-btn>
    </div>
    <div v-else-if="concluido" role="status">
      <v-alert type="success" variant="tonal" class="mb-4">Senha redefinida. Agora é só entrar.</v-alert>
      <v-btn to="/login" variant="flat" color="primary" block>Entrar</v-btn>
    </div>
    <form v-else novalidate @submit.prevent="enviar">
      <v-text-field :model-value="email" label="E-mail" type="email" autocomplete="email" readonly />
      <v-text-field
        v-model="senha"
        label="Nova senha"
        :type="mostrar ? 'text' : 'password'"
        autocomplete="new-password"
        :error-messages="erros.senha"
        :disabled="loading"
      >
        <template #append-inner>
          <v-btn
            type="button"
            icon
            variant="text"
            size="small"
            :aria-label="mostrar ? 'Ocultar senha' : 'Mostrar senha'"
            :aria-pressed="mostrar"
            @click="mostrar = !mostrar"
          >
            <v-icon>{{ mostrar ? "mdi-eye-off-outline" : "mdi-eye-outline" }}</v-icon>
          </v-btn>
        </template>
      </v-text-field>
      <v-text-field
        v-model="confirmar"
        label="Confirmar nova senha"
        :type="mostrar ? 'text' : 'password'"
        autocomplete="new-password"
        :error-messages="erros.confirmar"
        :disabled="loading"
      />
      <v-btn type="submit" variant="flat" color="primary" size="large" block :loading="loading" :disabled="loading">
        Redefinir senha
      </v-btn>
    </form>
  </PaginaCurta>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import { redefinirSenha } from "@/services/auth.service";
import { interpretarErro } from "@/composables/useApiError";
import { useAuthStore } from "@/stores/auth";
import PaginaCurta from "@/components/ui/PaginaCurta.vue";

const route = useRoute();
// Sessão ativa: encerra localmente para o link não ser redirecionado e o token não se perder.
const auth = useAuthStore();
if (auth.logado) auth.limpar();
const token = typeof route.query.token === "string" ? route.query.token : "";
const email = typeof route.query.email === "string" ? route.query.email : "";
const linkInvalido = computed(() => !token || !email);

const senha = ref("");
const confirmar = ref("");
const mostrar = ref(false);
const erros = ref({});
const loading = ref(false);
const concluido = ref(false);
const expirado = ref(false);

async function enviar() {
  const e = {};
  if (senha.value.length < 8) e.senha = "A senha precisa ter ao menos 8 caracteres.";
  if (senha.value !== confirmar.value) e.confirmar = "As senhas não são iguais.";
  erros.value = e;
  if (Object.keys(e).length) return;

  loading.value = true;
  try {
    await redefinirSenha({ token, email, senha: senha.value });
    concluido.value = true;
  } catch (err) {
    const info = interpretarErro(err);
    if (info.status === 422 && !info.campos.senha) expirado.value = true;
    else if (info.status === 422) erros.value = { senha: info.campos.senha };
    else erros.value = { senha: info.mensagem };
  } finally {
    loading.value = false;
  }
}
</script>
