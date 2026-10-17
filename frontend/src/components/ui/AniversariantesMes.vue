<template>
  <section v-if="aniversariantes.length" class="aniv" aria-labelledby="aniv-titulo">
    <h2 id="aniv-titulo" class="tm-title">Aniversariantes do mês</h2>
    <ul class="lista">
      <li v-for="m in aniversariantes" :key="m.uuid">
        <router-link :to="{ name: 'membro', params: { uuid: m.uuid } }" class="pessoa">
          <AvatarUsuario :nome="m.nome" :src="m.foto_url || null" :size="48" />
          <span>
            <strong>{{ m.nome }}</strong>
            <small>{{ m.dia === hoje ? `Hoje é dia de lembrar de ${primeiro(m.nome)}` : `Dia ${m.dia}` }}</small>
          </span>
        </router-link>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { listarUsuarios } from "@/services/usuario.service";
import AvatarUsuario from "@/components/ui/AvatarUsuario.vue";

// Só aparece se GET /usuarios trouxer `aniversario`; sem o campo (ou em erro) não renderiza nada.
const aniversariantes = ref([]);
const agora = new Date();
const hoje = agora.getDate();
const primeiro = (n) => String(n).split(" ")[0];

onMounted(async () => {
  try {
    const r = await listarUsuarios();
    const lista = Array.isArray(r) ? r : (r?.data ?? []);
    aniversariantes.value = lista
      .map((u) => {
        const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(u.aniversario || "");
        return m ? { ...u, mes: Number(m[2]), dia: Number(m[3]) } : null;
      })
      .filter((u) => u && u.mes === agora.getMonth() + 1)
      .sort((a, b) => a.dia - b.dia);
  } catch {
    aniversariantes.value = [];
  }
});
</script>

<style scoped>
.aniv {
  width: 100%;
}
.aniv h2 {
  font-size: var(--tm-fs-xl);
  margin-bottom: var(--tm-space-3);
}
.lista {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: var(--tm-space-3);
}
.pessoa {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
  padding: var(--tm-space-3);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  color: inherit;
  text-decoration: none;
  background: rgb(var(--v-theme-surface-variant));
}
.pessoa span {
  display: flex;
  flex-direction: column;
}
small {
  color: var(--tm-ink-muted);
}
</style>
