<template>
  <section class="folha">
    <div class="folha-conteudo">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.folha {
  position: relative;
  width: 100%;
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  border-radius: var(--tm-radius-xl) var(--tm-radius-xl) 0 0;
  margin-top: var(--tm-space-6);
  padding: var(--tm-space-6) var(--tm-space-4) var(--tm-space-8);
  z-index: 2;
  flex: 1;
}
.folha-conteudo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-7);
  max-width: 1000px;
  margin: 0 auto;
}
@media (min-width: 960px) {
  .folha {
    border-radius: 48px 48px 0 0;
    padding-top: var(--tm-space-7);
  }
}
</style>
