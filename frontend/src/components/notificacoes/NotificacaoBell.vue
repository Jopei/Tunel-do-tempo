<template>
  <div class="bell-wrapper" ref="bellRef">

    <div class="icon" @click.stop="toggle">
      <v-icon size="26" class="bell-icon">mdi-bell</v-icon>

      <span v-if="temNaoLidas" class="badge"></span>
    </div>

    <div v-if="aberto" class="dropdown">

      <div class="header">
        Notificações
      </div>

      <div v-if="loading" class="loading">
        Carregando...
      </div>

      <div v-else-if="!notificacoes.length" class="empty">
        Nenhuma notificação nova.
      </div>

      <div
        v-for="n in notificacoes"
        :key="n.id"
        class="item"
        @click="abrir(n)"
      >
        <strong>{{ n.titulo }}</strong>
        <small>{{ n.tema }}</small>
      </div>

      <div
        v-if="notificacoes.length"
        class="marcar"
        @click.stop="marcarTodas"
      >
        Marcar todas como lidas
      </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue";
import { listarNotificacoes, marcarTodasLidas, marcarLida } from "@/services/notificacao.service";

const notificacoes = ref([]);
const loading = ref(false);
const aberto = ref(false);

const temNaoLidas = computed(() => notificacoes.value.length > 0);
const bellRef = ref(null);

function handleClickOutside(event) {
  if (bellRef.value && !bellRef.value.contains(event.target)) {
    aberto.value = false;
  }
}


function toggle() {
  aberto.value = !aberto.value;
}

async function carregar() {
  loading.value = true;
  notificacoes.value = await listarNotificacoes();
  loading.value = false;
}

async function marcarTodas() {
  await marcarTodasLidas();
  notificacoes.value = [];
}

async function abrir(n) {
  await marcarLida(n.id);
  notificacoes.value = notificacoes.value.filter(i => i.id !== n.id);

  if (n.link) {
    window.location.href = n.link;
  }
}

onMounted(() => {
  carregar();
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.bell-wrapper {
  position: relative;
  cursor: pointer;
  margin-right: 14px;
}

.icon {
  position: relative;
}

.bell-wrapper:hover .bell-icon {
  transform: scale(1.1);
  transition: .2s ease;
}

.badge {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 10px;
  height: 10px;
  background: #c7a43a;
  border-radius: 50%;
  box-shadow: 0 0 0 2px #fff;
}

.dropdown {
  position: absolute;
  top: 40px;
  right: 0;
  width: 320px;
  background: #fbf6e6;
  border-radius: 18px;
  padding: 14px 0;
  box-shadow: 0 20px 60px rgba(0,0,0,.25);
  max-height: 420px;
  overflow-y: auto;
  z-index: 999;
}

.header {
  padding: 12px 18px;
  font-weight: 700;
  border-bottom: 1px solid rgba(0,0,0,.08);
}

.item {
  padding: 12px 18px;
  cursor: pointer;
  transition: .2s ease;
}

.item:hover {
  background: rgba(199,164,58,.18);
}

.empty {
  padding: 20px;
  text-align: center;
  color: #777;
}

.marcar {
  padding: 12px 18px;
  text-align: center;
  font-weight: 600;
  border-top: 1px solid rgba(0,0,0,.08);
}

.bell-icon {
  color: #8b6b1f; /* dourado do site */
}
</style>