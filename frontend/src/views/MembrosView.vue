<template>
  <section class="membros-page tm-immersive">
    <div class="painel">
      <header class="cabeca">
        <h1 class="tm-title">Membros da rua</h1>
        <p class="sub">Quem faz parte desta história.</p>
        <v-text-field
          v-model="busca"
          label="Buscar membro"
          prepend-inner-icon="mdi-magnify"
          clearable
          hide-details
          autocomplete="off"
          class="busca"
        />
      </header>

      <AniversariantesMes class="mb-6" />

      <TmSkeletonGrid v-if="carregando" :quantidade="8" tipo="card" minimo="200px" />
      <TmErrorState v-else-if="erro" texto="Não conseguimos carregar os membros." @retry="carregar" />
      <TmEmptyState v-else-if="!filtrados.length" icone="mdi-account-search-outline" titulo="Nenhum membro encontrado" />

      <ul v-else class="grid">
        <li v-for="m in filtrados" :key="m.uuid">
          <router-link :to="{ name: 'membro', params: { slug: m.slug || m.uuid } }" class="membro-card">
            <AvatarUsuario :nome="m.nome" :src="fotoDe(m)" :size="72" />
            <strong>{{ m.nome }}</strong>
            <span v-if="m.descricao" class="bio">{{ m.descricao }}</span>
          </router-link>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { listarUsuarios } from "@/services/usuario.service";
import AniversariantesMes from "@/components/ui/AniversariantesMes.vue";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";
import TmSkeletonGrid from "@/components/ui/TmSkeletonGrid.vue";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import TmEmptyState from "@/components/ui/TmEmptyState.vue";
import { urlFoto } from "@/services/midia.url";

const membros = ref([]);
const carregando = ref(true);
const erro = ref(false);
const busca = ref("");

function normalizar(s) {
  return String(s ?? "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "");
}

const filtrados = computed(() => {
  const t = normalizar(busca.value);
  return membros.value
    .filter((m) => !t || normalizar(m.nome).includes(t))
    .sort((a, b) => a.nome.localeCompare(b.nome, "pt-BR"));
});

// A listagem pode (ou não) trazer foto; usa o que vier, senão mostra iniciais.
function fotoDe(m) {
  if (m.foto_url) return m.foto_url;
  if (m.foto?.url) return m.foto.url;
  if (m.foto?.uuid) return urlFoto(m.foto.uuid);
  return null;
}

async function carregar() {
  carregando.value = true;
  erro.value = false;
  try {
    const r = await listarUsuarios();
    membros.value = Array.isArray(r) ? r : (r?.data ?? []);
  } catch {
    erro.value = true;
  } finally {
    carregando.value = false;
  }
}

onMounted(carregar);
</script>

<style scoped>
.membros-page {
  min-height: 100dvh;
  background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url("/backgrounds/home-bg.svg") center/cover fixed;
  padding: calc(var(--tm-navbar-h) + 24px) var(--tm-space-4) var(--tm-space-8);
}
.painel {
  max-width: 1100px;
  margin: 0 auto;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-6);
  box-shadow: var(--tm-shadow-lg);
}
.cabeca h1 {
  font-size: var(--tm-fs-3xl);
}
.sub {
  color: var(--tm-ink-muted);
  margin: 0 0 var(--tm-space-4);
}
.busca {
  max-width: 420px;
  margin-bottom: var(--tm-space-5);
}
.grid {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: var(--tm-space-4);
}
.membro-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-2);
  text-align: center;
  height: 100%;
  padding: var(--tm-space-5) var(--tm-space-3);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  background: rgb(var(--v-theme-surface-variant));
  color: inherit;
  text-decoration: none;
  transition: transform var(--tm-dur-base) var(--tm-ease), box-shadow var(--tm-dur-base) var(--tm-ease);
}
.membro-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--tm-shadow-md);
}
.bio {
  color: var(--tm-ink-muted);
  font-size: var(--tm-fs-sm);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
@media (max-width: 600px) {
  .painel {
    padding: var(--tm-space-4);
  }
}
</style>
