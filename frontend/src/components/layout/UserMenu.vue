<template>
  <v-menu location="bottom end">
    <template #activator="{ props }">
      <v-btn v-bind="props" icon variant="text" class="avatar-btn" aria-label="Menu da conta">
        <v-badge v-if="temNova" dot color="error" floating>
          <AvatarUsuario :nome="auth.usuario?.nome" :src="auth.fotoPerfilUrl" :size="40" />
        </v-badge>
        <AvatarUsuario v-else :nome="auth.usuario?.nome" :src="auth.fotoPerfilUrl" :size="40" />
      </v-btn>
    </template>

    <v-list min-width="240" density="comfortable" aria-label="Conta">
      <v-list-item :title="auth.usuario?.nome" :subtitle="auth.papel" class="mb-1" />
      <v-divider />
      <v-list-item prepend-icon="mdi-account-outline" title="Meu perfil" to="/configuracoes" />
      <v-list-item prepend-icon="mdi-heart-outline" title="Minhas memórias" to="/minhas-memorias" />
      <v-list-item prepend-icon="mdi-bell-outline" title="Minhas notificações" to="/notificacoes/minhas" />
      <v-list-item prepend-icon="mdi-account-group-outline" title="Membros" to="/membros" />
      <v-list-item prepend-icon="mdi-update" title="Atualizações" to="/atualizacoes">
        <template v-if="temNova" #append>
          <v-chip size="x-small" color="error" label>Novo</v-chip>
        </template>
      </v-list-item>
      <v-list-item
        v-if="auth.isAdmin"
        prepend-icon="mdi-bell-plus-outline"
        title="Criar notificação"
        to="/admin/notificacoes/nova"
      />
      <v-list-item prepend-icon="mdi-candle" title="Em memória" to="/aquele-que-sonhou" />
      <v-divider />
      <v-list-item
        :prepend-icon="tema.efetivo.value === 'escuro' ? 'mdi-weather-sunny' : 'mdi-weather-night'"
        :title="tema.efetivo.value === 'escuro' ? 'Tema claro' : 'Tema escuro'"
        role="menuitemcheckbox"
        :aria-checked="tema.efetivo.value === 'escuro'"
        @click="tema.alternar()"
      />
      <v-list-item prepend-icon="mdi-logout" title="Sair" base-color="error" @click="sair" />
    </v-list>
  </v-menu>
</template>

<script setup>
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useTema } from "@/composables/useTema";
import { useAtualizacoesNovas } from "@/composables/useAtualizacoesNovas";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";

defineEmits(["abrir-atualizacao"]);

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const tema = useTema();
const { temNova } = useAtualizacoesNovas();

function sair() {
  auth.logout();
  if (route.meta.requiresAuth) router.push("/");
}
</script>

<style scoped>
.avatar-btn {
  width: 48px;
  height: 48px;
}
</style>
