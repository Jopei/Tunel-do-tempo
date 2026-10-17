<template>
  <div class="video-card">
    <div class="video-frame">
      <video
        v-if="play"
        ref="videoRef"
        class="video"
        src="/videos/Abertura.mp4"
        controls
        autoplay
        playsinline
        aria-label="Vídeo de abertura do Túnel do Tempo"
      />
      <button v-else type="button" class="play-btn" aria-label="Reproduzir vídeo de abertura" @click="play = true">
        <span class="play-circulo" aria-hidden="true">
          <v-icon size="48" color="white">mdi-play</v-icon>
        </span>
      </button>
    </div>

    <div class="video-footer">
      <h2 class="title tm-title">Tudo começa aqui</h2>
      <div class="author-inline">
        <img src="/avatar-ze-paulo.png" alt="" class="avatar-inline" width="40" height="40" loading="lazy" />
        <span class="author-name">Zé Paulo</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

// O clique do usuário é o gesto que autoriza o autoplay com som.
const play = ref(false);
</script>

<style scoped>
.video-card {
  width: 100%;
  max-width: 880px;
  background: #14110b;
  color: #fff;
  border-radius: var(--tm-radius-lg);
  padding: var(--tm-space-4);
  box-shadow: var(--tm-shadow-lg);
}

.video-frame {
  position: relative;
  aspect-ratio: 16 / 9;
  border-radius: var(--tm-radius-md);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(0, 0, 0, 0.6)), #1e1a12;
  overflow: hidden;
}

.video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-btn {
  position: absolute;
  inset: 0;
  width: 100%;
  border: 0;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.play-circulo {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(199, 164, 58, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform var(--tm-dur-base) var(--tm-ease);
}
.play-btn:hover .play-circulo {
  transform: scale(1.08);
}
.play-btn:focus-visible {
  outline-color: #f0c86a;
  outline-offset: -6px;
}

.video-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: var(--tm-space-4);
  gap: var(--tm-space-4);
  flex-wrap: wrap;
}

.title {
  font-size: var(--tm-fs-lg);
  margin: 0;
}

.author-inline {
  display: flex;
  align-items: center;
  gap: var(--tm-space-3);
}

.avatar-inline {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #f0c86a;
  object-fit: cover;
}

.author-name {
  font-weight: 600;
}
</style>
