<template>
  <div class="ctas" data-intro="ctas">
    <router-link ref="primarioRef" :to="primario.para" class="cta cta--ouro" data-passagem="primario">
      {{ primario.texto }}
    </router-link>
    <router-link :to="secundario.para" class="cta cta--contorno" data-passagem="secundario">
      {{ secundario.texto }}
    </router-link>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { REDE } from "@/config/esquina";

const auth = useAuthStore();
const primarioRef = ref(null);

// Visitante: conhecer a Rua / fazer parte. Logado: Histórias / A Rua.
const primario = computed(() =>
  auth.logado
    ? { texto: `Ir para ${REDE.nome}`, para: "/historias" }
    : { texto: "Conhecer a Rua", para: "/a-rua" },
);
const secundario = computed(() =>
  auth.logado
    ? { texto: "Ver A Rua", para: "/a-rua" }
    : { texto: "Quero fazer parte", para: "/cadastrar/usuarios" },
);

defineExpose({ focar: () => primarioRef.value?.$el?.focus?.() });
</script>

<style scoped>
.ctas {
  display: flex;
  flex-wrap: wrap;
  gap: var(--tm-space-3);
  align-items: center;
}
.cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 0 var(--tm-space-5);
  border-radius: var(--tm-radius-pill);
  font-weight: 600;
  text-decoration: none;
  border: 2px solid var(--tm-gold);
}
.cta--ouro {
  background: var(--tm-gold);
  color: #2a2112;
}
.cta--contorno {
  background: transparent;
  color: var(--tm-ink, #f1e8d2);
}
.cta:hover {
  filter: brightness(1.08);
}
@media (max-width: 599px) {
  .ctas {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
