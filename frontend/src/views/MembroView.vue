<template>
  <section class="membro-page tm-immersive">
    <div class="painel">
      <nav class="migalhas" aria-label="Você está em">
        <router-link to="/membros"><v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> Membros</router-link>
      </nav>

      <div v-if="carregando" aria-busy="true" aria-label="Carregando membro">
        <v-skeleton-loader type="avatar, heading, paragraph" />
      </div>
      <TmEmptyState v-else-if="status === 404" icone="mdi-account-off-outline" titulo="Este membro não foi encontrado">
        <v-btn to="/membros" variant="flat" color="primary">Ver membros</v-btn>
      </TmEmptyState>
      <TmErrorState v-else-if="erro" texto="Não conseguimos carregar este perfil." @retry="carregar" />

      <template v-else-if="membro">
        <header class="perfil">
          <AvatarUsuario :nome="membro.nome" :src="membro.foto?.url ?? null" :size="112" />
          <div>
            <h1 class="tm-title">{{ membro.nome }}</h1>
            <p v-if="membro.tipo_usuario?.nome" class="papel">{{ membro.tipo_usuario.nome }}</p>
            <p v-if="membro.descricao" class="bio">{{ membro.descricao }}</p>
          </div>
        </header>

        <h2 class="tm-title sec">Histórias de {{ primeiroNome }}</h2>
        <p v-if="!historias.length && !carregandoHistorias" class="vazio">Ainda não há histórias com este membro.</p>
        <ul v-else class="lista">
          <li v-for="h in historias" :key="h.uuid">
            <router-link :to="{ name: 'historias-show', params: { uuid: h.uuid } }">
              <strong>{{ h.titulo }}</strong>
              <span>{{ h.descricao_curta }}</span>
            </router-link>
          </li>
        </ul>
      </template>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import { buscarUsuario } from "@/services/usuario.service";
import { buscarHistoriasResumo } from "@/services/historia.service";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";

const route = useRoute();
const membro = ref(null);
const historias = ref([]);
const carregando = ref(true);
const carregandoHistorias = ref(false);
const erro = ref(false);
const status = ref(null);

const primeiroNome = computed(() => membro.value?.nome?.split(" ")[0] ?? "");

async function carregar() {
  carregando.value = true;
  erro.value = false;
  status.value = null;
  try {
    const r = await buscarUsuario(route.params.uuid);
    membro.value = r.data ?? r;
    document.title = `${membro.value.nome} · Túnel do Tempo`;
  } catch (e) {
    membro.value = null;
    status.value = e.response?.status ?? null;
    erro.value = status.value !== 404;
  } finally {
    carregando.value = false;
  }
  if (!membro.value) return;
  carregandoHistorias.value = true;
  try {
    const r = await buscarHistoriasResumo();
    const lista = Array.isArray(r) ? r : (r?.data ?? []);
    historias.value = lista.filter((h) => h.usuarios?.some((u) => u.uuid === route.params.uuid));
  } catch {
    historias.value = [];
  } finally {
    carregandoHistorias.value = false;
  }
}

watch(() => route.params.uuid, (n, o) => n && n !== o && route.name === "membro" && carregar());
onMounted(carregar);
</script>

<style scoped>
.membro-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
}
.painel {
  max-width: 800px;
  margin: 0 auto;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
.migalhas a {
  color: var(--tm-gold-text);
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
}
.perfil {
  display: flex;
  align-items: center;
  gap: var(--tm-space-5);
  flex-wrap: wrap;
  margin: var(--tm-space-3) 0 var(--tm-space-6);
}
.perfil h1 {
  font-size: var(--tm-fs-3xl);
}
.papel {
  margin: 0;
  color: var(--tm-gold-text);
  font-weight: 600;
}
.bio {
  margin: var(--tm-space-2) 0 0;
  color: var(--tm-ink-muted);
  max-width: 55ch;
}
.sec {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.vazio {
  color: var(--tm-ink-muted);
}
.lista {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--tm-space-3);
}
.lista a {
  display: flex;
  flex-direction: column;
  padding: var(--tm-space-3) var(--tm-space-4);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  color: inherit;
  text-decoration: none;
}
.lista a:hover {
  background: rgb(var(--v-theme-surface-variant));
}
.lista span {
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
}
</style>
