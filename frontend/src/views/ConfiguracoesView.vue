<template>
  <section class="config-page tm-immersive">
    <div class="container">
      <h1 class="titulo tm-title">Meu perfil</h1>

      <div class="layout">
        <nav class="secoes d-none d-md-flex" aria-label="Seções do perfil">
          <a href="#perfil" @click.prevent="irPara('perfil')">Perfil</a>
          <a href="#contato" @click.prevent="irPara('contato')">Contato</a>
          <a href="#seguranca" @click.prevent="irPara('seguranca')">Segurança</a>
          <a href="#aparencia" @click.prevent="irPara('aparencia')">Aparência</a>
        </nav>

        <div class="conteudo">
          <div v-if="carregando" class="card" aria-busy="true" aria-label="Carregando perfil">
            <v-skeleton-loader type="avatar, heading, paragraph, paragraph" />
          </div>

          <form v-else novalidate @submit.prevent="salvar">
            <section id="perfil" class="card" aria-labelledby="t-perfil">
              <h2 id="t-perfil" class="tm-title">Perfil</h2>
              <div class="perfil-info">
                <AvatarUpload
                  ref="avatarRef"
                  :nome="form.nome"
                  :src="auth.fotoPerfilUrl"
                  :disabled="enviandoFoto"
                  rotulo="Mudar foto de perfil"
                  @arquivo="enviarFoto"
                />
                <div class="usuario-dados">
                  <strong>{{ form.nome }}</strong>
                  <span>{{ auth.papel }}</span>
                  <v-progress-linear v-if="enviandoFoto" indeterminate color="primary" class="mt-2" aria-label="Enviando foto" />
                </div>
              </div>

              <v-text-field v-model="form.nome" label="Nome" autocomplete="name" :error-messages="erros.nome" />
              <v-textarea
                v-model="form.descricao"
                label="Sobre você"
                rows="3"
                auto-grow
                counter="150"
                maxlength="150"
                :error-messages="erros.descricao"
              />
              <p class="sr-only" aria-live="polite">{{ avisoLimite }}</p>
            </section>

            <section id="contato" class="card" aria-labelledby="t-contato">
              <h2 id="t-contato" class="tm-title">Contato</h2>
              <v-text-field
                v-model="form.email"
                label="E-mail"
                type="email"
                autocomplete="email"
                inputmode="email"
                :error-messages="erros.email"
              />
              <div class="grid">
                <v-text-field
                  :model-value="form.telefone"
                  label="Telefone"
                  type="tel"
                  inputmode="tel"
                  autocomplete="tel"
                  placeholder="(99) 99999-9999"
                  :error-messages="erros.telefone"
                  @update:model-value="form.telefone = mascaraTelefone($event)"
                />
                <v-text-field
                  v-model="form.aniversario"
                  label="Aniversário"
                  type="date"
                  autocomplete="bday"
                  :max="hoje"
                  :error-messages="erros.aniversario"
                />
              </div>
            </section>

            <section id="seguranca" class="card" aria-labelledby="t-seg">
              <h2 id="t-seg" class="tm-title">Segurança</h2>
              <p class="dica">Deixe em branco para manter a senha atual.</p>
              <div class="grid">
                <v-text-field
                  v-model="form.senha"
                  label="Nova senha"
                  :type="mostrarSenha ? 'text' : 'password'"
                  autocomplete="new-password"
                  hint="Mínimo de 8 caracteres"
                  persistent-hint
                  :error-messages="erros.senha"
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
                <v-text-field
                  v-model="form.confirmar"
                  label="Confirmar nova senha"
                  :type="mostrarSenha ? 'text' : 'password'"
                  autocomplete="new-password"
                  :error-messages="erros.confirmar"
                />
              </div>
              <template v-if="form.senha">
                <v-progress-linear :model-value="forca * 25" :color="coresForca[forca]" height="4" rounded aria-hidden="true" />
                <p class="forca" aria-live="polite">{{ rotulosForca[forca] }}</p>
              </template>
            </section>

            <div class="rodape-form">
              <v-btn type="submit" variant="flat" color="primary" size="large" :loading="salvando" :disabled="!sujo || salvando">
                Salvar alterações
              </v-btn>
            </div>
          </form>

          <section id="aparencia" class="card" aria-labelledby="t-apar">
            <h2 id="t-apar" class="tm-title">Aparência</h2>
            <p id="rotulo-tema" class="dica">Tema</p>
            <v-btn-toggle
              :model-value="tema.preferencia.value"
              mandatory
              color="primary"
              density="comfortable"
              aria-labelledby="rotulo-tema"
              @update:model-value="tema.definir"
            >
              <v-btn value="claro" prepend-icon="mdi-weather-sunny">Claro</v-btn>
              <v-btn value="escuro" prepend-icon="mdi-weather-night">Escuro</v-btn>
              <v-btn value="auto" prepend-icon="mdi-theme-light-dark">Automático</v-btn>
            </v-btn-toggle>

            <v-switch
              :model-value="tema.reduzirManual.value"
              label="Reduzir animações"
              color="primary"
              hide-details
              class="mt-4"
              @update:model-value="tema.definirReduzir"
            />
          </section>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { onBeforeRouteLeave } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { atualizarUsuario, atualizarFotoPerfil } from "@/services/usuario.service";
import { useApiError } from "@/composables/useApiError";
import { useNotify } from "@/composables/useNotify";
import { useConfirm } from "@/composables/useConfirm";
import { useTema } from "@/composables/useTema";
import { mascaraTelefone, forcaSenha } from "@/composables/useFormat";
import AvatarUpload from "@/components/ui/AvatarUpload.vue";

const auth = useAuthStore();
const { tratar, focarPrimeiroInvalido } = useApiError();
const notify = useNotify();
const { confirmar } = useConfirm();
const tema = useTema();

const hoje = new Date().toISOString().slice(0, 10);
const carregando = ref(true);
const salvando = ref(false);
const enviandoFoto = ref(false);
const mostrarSenha = ref(false);
const erros = ref({});
const avatarRef = ref(null);
const inicial = ref("");

const form = ref({ nome: "", email: "", telefone: "", aniversario: "", senha: "", confirmar: "", descricao: "" });

const sujo = computed(() => JSON.stringify(form.value) !== inicial.value);
const forca = computed(() => forcaSenha(form.value.senha));
const rotulosForca = ["", "Senha fraca", "Senha razoável", "Senha boa", "Senha forte"];
const coresForca = ["grey", "error", "warning", "info", "success"];
const avisoLimite = ref("");

watch(
  () => form.value.descricao,
  (d) => {
    avisoLimite.value = d.length >= 140 ? `Limite próximo: ${d.length} de 150 caracteres.` : "";
  },
);

function preencher() {
  const u = auth.usuario || {};
  form.value = {
    nome: u.nome || "",
    email: u.email || "",
    telefone: mascaraTelefone(u.telefone || ""),
    aniversario: (u.aniversario || "").slice(0, 10),
    senha: "",
    confirmar: "",
    descricao: u.descricao || "",
  };
  inicial.value = JSON.stringify(form.value);
}

onMounted(async () => {
  window.addEventListener("beforeunload", avisarSaida);
  preencher();
  try {
    await auth.refreshUsuario();
    preencher();
  } catch {
    notify.erro("Não foi possível carregar todos os seus dados.");
  } finally {
    carregando.value = false;
  }
});
onBeforeUnmount(() => window.removeEventListener("beforeunload", avisarSaida));

function validar() {
  const e = {};
  if (!form.value.nome.trim()) e.nome = "Informe seu nome.";
  if (!/^\S+@\S+\.\S+$/.test(form.value.email)) e.email = "Informe um e-mail válido.";
  if (form.value.senha) {
    if (form.value.senha.length < 8) e.senha = "A senha precisa ter ao menos 8 caracteres.";
    if (form.value.senha !== form.value.confirmar) e.confirmar = "As senhas não são iguais.";
  }
  erros.value = e;
  return !Object.keys(e).length;
}

async function salvar() {
  if (!validar()) {
    focarPrimeiroInvalido();
    return;
  }
  salvando.value = true;
  const payload = {
    nome: form.value.nome.trim(),
    email: form.value.email.trim(),
    telefone: form.value.telefone || null,
    aniversario: form.value.aniversario || null,
    descricao: form.value.descricao || null,
  };
  // Só envia a senha se foi preenchida.
  if (form.value.senha) payload.senha = form.value.senha;

  try {
    await atualizarUsuario(auth.usuario.uuid, payload);
    await auth.refreshUsuario();
    preencher();
    notify.sucesso("Perfil atualizado.");
  } catch (e) {
    const info = tratar(e);
    erros.value = info.campos;
    if (Object.keys(info.campos).length) focarPrimeiroInvalido();
  } finally {
    salvando.value = false;
  }
}

async function enviarFoto(file) {
  enviandoFoto.value = true;
  const fd = new FormData();
  fd.append("foto", file);
  try {
    await atualizarFotoPerfil(auth.usuario.uuid, fd);
    await auth.refreshUsuario();
    avatarRef.value?.limparPreview();
    notify.sucesso("Foto atualizada.");
  } catch (e) {
    avatarRef.value?.limparPreview();
    tratar(e);
  } finally {
    enviandoFoto.value = false;
  }
}

function irPara(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function avisarSaida(e) {
  if (sujo.value) {
    e.preventDefault();
    e.returnValue = "";
  }
}

onBeforeRouteLeave(async () => {
  if (!sujo.value) return true;
  return confirmar({
    titulo: "Sair sem salvar?",
    texto: "As alterações no seu perfil serão perdidas.",
    rotuloConfirmar: "Sair",
    rotuloCancelar: "Continuar editando",
    destrutivo: false,
  });
});
</script>

<style scoped>
.config-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 32px) var(--tm-space-4) var(--tm-space-8);
}
.container {
  max-width: 1000px;
  margin: 0 auto;
}
.titulo {
  color: #fff;
  font-size: var(--tm-fs-hero);
  text-align: center;
  text-shadow: 0 1px 12px rgba(0, 0, 0, 0.45);
  margin-bottom: var(--tm-space-6);
}
.layout {
  display: grid;
  grid-template-columns: 180px 1fr;
  gap: var(--tm-space-5);
  align-items: start;
}
.secoes {
  position: sticky;
  top: calc(var(--tm-navbar-h) + 16px);
  flex-direction: column;
  gap: var(--tm-space-1);
  background: rgb(var(--v-theme-surface));
  border-radius: var(--tm-radius-md);
  padding: var(--tm-space-3);
}
.secoes a {
  color: rgb(var(--v-theme-on-surface));
  text-decoration: none;
  padding: 10px 12px;
  border-radius: var(--tm-radius-xs);
  font-weight: 600;
  min-height: 44px;
}
.secoes a:hover {
  background: rgb(var(--v-theme-surface-variant));
}
.card {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  margin-bottom: var(--tm-space-5);
  box-shadow: var(--tm-shadow-md);
  scroll-margin-top: calc(var(--tm-navbar-h) + 16px);
}
.card h2 {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-4);
}
.perfil-info {
  display: flex;
  align-items: center;
  gap: var(--tm-space-5);
  margin-bottom: var(--tm-space-5);
  flex-wrap: wrap;
}
.usuario-dados {
  display: flex;
  flex-direction: column;
  min-width: 160px;
}
.usuario-dados span {
  color: var(--tm-ink-muted);
}
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--tm-space-4);
}
.dica {
  margin: 0 0 var(--tm-space-3);
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
.forca {
  margin: 2px 0 0;
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
}
.rodape-form {
  display: flex;
  justify-content: flex-end;
  margin-bottom: var(--tm-space-5);
}
@media (max-width: 960px) {
  .layout {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 600px) {
  .card {
    padding: var(--tm-space-4);
  }
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
