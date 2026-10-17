<template>
  <PaginaCurta titulo="Esqueceu a senha?" :texto="enviado ? '' : 'Informe seu e-mail e enviaremos as instruções para criar uma nova senha.'">
    <div v-if="enviado" role="status">
      <v-alert type="success" variant="tonal" class="mb-4">
        Se o e-mail estiver cadastrado, enviaremos as instruções. Confira sua caixa de entrada e o spam.
      </v-alert>
      <v-btn to="/login" variant="flat" color="primary" block>Voltar para entrar</v-btn>
    </div>
    <form v-else novalidate @submit.prevent="enviar">
      <v-text-field
        v-model="email"
        label="E-mail"
        type="email"
        autocomplete="email"
        inputmode="email"
        :error-messages="erro"
        :disabled="loading"
      />
      <v-btn type="submit" variant="flat" color="primary" size="large" block :loading="loading" :disabled="loading">
        Enviar instruções
      </v-btn>
      <div class="text-center mt-3"><router-link to="/login" class="voltar">Voltar para entrar</router-link></div>
    </form>
  </PaginaCurta>
</template>

<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";
import { esqueciSenha } from "@/services/auth.service";
import { interpretarErro } from "@/composables/useApiError";
import PaginaCurta from "@/components/ui/PaginaCurta.vue";

const route = useRoute();
const email = ref(typeof route.query.email === "string" ? route.query.email : "");
const erro = ref("");
const loading = ref(false);
const enviado = ref(false);

async function enviar() {
  erro.value = "";
  if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
    erro.value = "Digite um e-mail válido.";
    return;
  }
  loading.value = true;
  try {
    await esqueciSenha(email.value.trim());
    enviado.value = true;
  } catch (e) {
    const info = interpretarErro(e);
    erro.value = info.status === 429 ? "Muitas tentativas. Aguarde um pouco e tente de novo." : info.mensagem;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.voltar {
  color: var(--tm-gold-text);
  font-weight: 600;
  display: inline-block;
  padding: 10px 0;
}
</style>
