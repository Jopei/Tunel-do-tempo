<template>
  <AuthLayout larga>
    <div v-if="concluido" class="boas-vindas">
      <v-icon size="56" color="success" aria-hidden="true">mdi-check-circle-outline</v-icon>
      <h1 class="tm-title">Cadastro concluído, {{ primeiroNome }}.</h1>
      <p>Agora você faz parte da rua.</p>
      <v-btn to="/login" variant="flat" color="primary" size="large">Entrar</v-btn>
    </div>

    <form v-else class="card" novalidate @submit.prevent="salvar">
      <h1 class="tm-title">Quero fazer parte</h1>

      <v-alert type="info" variant="tonal" icon="mdi-key-outline" class="mb-5">
        Este cadastro é para quem recebeu uma chave de convite da rua. Peça o nome e o código da chave a um membro.
      </v-alert>

      <AvatarUpload ref="avatarRef" :nome="nome" rotulo="Adicionar foto de perfil (obrigatória)" :disabled="loading" @arquivo="foto = $event" />
      <p class="legenda-foto" :class="{ erro: erros.imagem }">
        {{ erros.imagem || "Foto de perfil *" }}
      </p>

      <div class="grid">
        <v-text-field v-model="nome" label="Nome *" autocomplete="name" :error-messages="erros.nome" :disabled="loading" />
        <v-text-field
          v-model="email"
          label="E-mail *"
          type="email"
          autocomplete="email"
          inputmode="email"
          :error-messages="erros.email"
          :disabled="loading"
        />
      </div>

      <div class="grid">
        <div>
          <v-text-field
            v-model="senha"
            label="Senha *"
            :type="mostrarSenha ? 'text' : 'password'"
            autocomplete="new-password"
            hint="Mínimo de 8 caracteres"
            persistent-hint
            :error-messages="erros.senha"
            :disabled="loading"
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
          <v-progress-linear :model-value="forca * 25" :color="coresForca[forca]" height="4" rounded class="mt-1" aria-hidden="true" />
          <p class="forca" aria-live="polite">{{ rotulosForca[forca] }}</p>
        </div>

        <!-- Mantido como no fluxo atual (IDs fixos do backend). A definição do tipo pela chave é um ponto para o backend. -->
        <v-select
          v-model="tipoUsuarioId"
          :items="TIPOS_USUARIO"
          item-title="titulo"
          item-value="id"
          label="Tipo de usuário *"
          :error-messages="erros.tipo_usuario_id"
          :disabled="loading"
        />
      </div>

      <div class="grid">
        <v-text-field v-model="aniversario" label="Aniversário" type="date" autocomplete="bday" :max="hoje" :disabled="loading" />
        <v-text-field
          :model-value="telefone"
          label="Telefone"
          type="tel"
          inputmode="tel"
          autocomplete="tel"
          placeholder="(99) 99999-9999"
          :error-messages="erros.telefone"
          :disabled="loading"
          @update:model-value="telefone = mascaraTelefone($event)"
        />
      </div>

      <v-textarea v-model="descricao" label="Fale um pouco sobre você" rows="2" auto-grow :disabled="loading" />

      <fieldset class="chave">
        <legend>Chave de convite</legend>
        <div class="grid">
          <v-text-field v-model="chaveNome" label="Nome da chave *" :error-messages="erros.chave_nome" :disabled="loading" autocomplete="off" />
          <v-text-field v-model="chaveCodigo" label="Código da chave *" :error-messages="erros.chave_codigo" :disabled="loading" autocomplete="off" />
        </div>
      </fieldset>

      <div class="botao-container">
        <v-btn type="submit" variant="flat" color="primary" size="large" :loading="loading" :disabled="loading">Cadastrar</v-btn>
      </div>
    </form>
  </AuthLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { onBeforeRouteLeave } from "vue-router";
import { cadastrarUsuario } from "@/services/usuario.service";
import { useApiError } from "@/composables/useApiError";
import { useConfirm } from "@/composables/useConfirm";
import { mascaraTelefone, forcaSenha } from "@/composables/useFormat";
import AuthLayout from "@/components/auth/AuthLayout.vue";
import AvatarUpload from "@/components/ui/AvatarUpload.vue";

// IDs fixos dos tipos no backend (tipo_usuarios).
const TIPOS_USUARIO = [
  { id: 1, titulo: "Membro ADM" },
  { id: 2, titulo: "Membro Rua" },
  { id: 3, titulo: "Revisor" },
];

const { tratar, focarPrimeiroInvalido } = useApiError();
const { confirmar } = useConfirm();

const nome = ref("");
const email = ref("");
const senha = ref("");
const mostrarSenha = ref(false);
const tipoUsuarioId = ref(null);
const aniversario = ref("");
const telefone = ref("");
const descricao = ref("");
const chaveNome = ref("");
const chaveCodigo = ref("");
const foto = ref(null);
const avatarRef = ref(null);
const loading = ref(false);
const erros = ref({});
const concluido = ref(false);
const nomeConcluido = ref("");
const hoje = new Date().toISOString().slice(0, 10);

const forca = computed(() => forcaSenha(senha.value));
const rotulosForca = ["", "Senha fraca", "Senha razoável", "Senha boa", "Senha forte"];
const coresForca = ["grey", "error", "warning", "info", "success"];
const primeiroNome = computed(() => nomeConcluido.value.split(" ")[0]);
const sujo = computed(() => !concluido.value && !!(nome.value || email.value || senha.value || foto.value || chaveNome.value));

function validar() {
  const e = {};
  if (!foto.value) e.imagem = "Adicione uma foto de perfil.";
  if (!nome.value.trim()) e.nome = "Informe seu nome.";
  if (!/^\S+@\S+\.\S+$/.test(email.value)) e.email = "Informe um e-mail válido.";
  if (senha.value.length < 8) e.senha = "A senha precisa ter ao menos 8 caracteres.";
  if (!tipoUsuarioId.value) e.tipo_usuario_id = "Selecione o tipo de usuário.";
  if (!chaveNome.value.trim()) e.chave_nome = "Informe o nome da chave.";
  if (!chaveCodigo.value.trim()) e.chave_codigo = "Informe o código da chave.";
  erros.value = e;
  return !Object.keys(e).length;
}

async function salvar() {
  if (!validar()) {
    focarPrimeiroInvalido();
    return;
  }
  loading.value = true;

  const form = new FormData();
  form.append("nome", nome.value.trim());
  form.append("email", email.value.trim());
  form.append("senha", senha.value);
  form.append("tipo_usuario_id", tipoUsuarioId.value);
  if (aniversario.value) form.append("aniversario", aniversario.value);
  if (telefone.value) form.append("telefone", telefone.value);
  if (descricao.value) form.append("descricao", descricao.value);
  form.append("chave_nome", chaveNome.value.trim());
  form.append("chave_codigo", chaveCodigo.value.trim());
  form.append("imagem", foto.value);

  try {
    await cadastrarUsuario(form);
    nomeConcluido.value = nome.value.trim();
    concluido.value = true;
    avatarRef.value?.limparPreview();
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    loading.value = false;
  }
}

onBeforeRouteLeave(async () => {
  if (!sujo.value) return true;
  return confirmar({
    titulo: "Sair do cadastro?",
    texto: "Os dados preenchidos serão perdidos.",
    rotuloConfirmar: "Sair",
    rotuloCancelar: "Continuar cadastro",
    destrutivo: false,
  });
});
</script>

<style scoped>
.card {
  width: 100%;
}
.boas-vindas {
  text-align: center;
  max-width: 440px;
  margin: 0 auto;
}
h1 {
  text-align: center;
  font-size: var(--tm-fs-2xl);
  margin-bottom: var(--tm-space-4);
}
.legenda-foto {
  text-align: center;
  margin: var(--tm-space-2) 0 var(--tm-space-5);
  font-weight: 600;
}
.legenda-foto.erro {
  color: rgb(var(--v-theme-error));
}
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-4);
}
.forca {
  margin: 2px 0 0;
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
  min-height: 1.4em;
}
.chave {
  border: 1px solid var(--tm-gold);
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-4) var(--tm-space-4) 0;
  margin-top: var(--tm-space-3);
}
.chave legend {
  font-weight: 700;
  padding: 0 var(--tm-space-2);
}
/* Botão fixo no rodapé do painel (que rola): "Cadastrar" nunca fica fora da tela */
.botao-container {
  position: sticky;
  bottom: calc(-1 * var(--tm-space-4));
  margin-top: var(--tm-space-4);
  padding: var(--tm-space-3) 0 var(--tm-space-4);
  text-align: center;
  background: linear-gradient(to top, rgb(var(--v-theme-surface)) 75%, transparent);
  z-index: 1;
}
@media (max-width: 600px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
