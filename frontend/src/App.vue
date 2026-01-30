<template>
  <v-app>
    <v-main>
      <router-view />
    </v-main>
  </v-app>
  <div id="transition-overlay">
  <img id="transition-image" />
</div>
</template>
<script setup>
</script>

<style>
html,
body,
#app {
  height: 100%;
  margin: 0;
}
#transition-overlay {
  position: fixed;
  inset: 0;
  background: #000;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}

#transition-image {
  width: 220px;
  height: 220px;
  border-radius: 50%;
  object-fit: cover;
}
</style>