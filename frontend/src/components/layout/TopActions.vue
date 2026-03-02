<template>
  <div class="top-actions">
    <NotificacaoBell />
    <UserMenuTm />
  </div>
</template>

<script setup>
import NotificacaoBell from "@/components/notificacoes/NotificacaoBell.vue";
import UserMenuTm from "@/components/layout/UserMenuTm.vue";
</script>

<style scoped>
.top-actions {
  position: fixed;
  top: 28px;
  right: 40px;
  display: flex;
  align-items: center;
  gap: 16px;
  z-index: 9999;
}

@media (max-width: 600px) {
  .top-actions {
    top: 18px;
    right: 18px;
    gap: 12px;
  }
}
</style>