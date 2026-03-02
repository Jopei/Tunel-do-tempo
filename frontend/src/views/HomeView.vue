<template>
  <section class="home-view">

    <TopActions />

    <div class="hero-content">
      <h1 class="reveal" style="--delay: 0s">
        Túnel do Tempo
      </h1>

      <p class="subtitle reveal" style="--delay: 0.15s">
        O tempo não apaga o que a luz tocou, apenas o transforma em memória.
      </p>

      <AppHeader class="reveal" style="--delay: 0.3s">
        <FeaturedVideoCard class="reveal" style="--delay: 0.45s" />
      </AppHeader>
    </div>
  </section>
</template>
<script setup>
import { useAuthStore } from "@/stores/auth";
import AppHeader from "@/components/layout/AppHeader.vue";
import FeaturedVideoCard from "@/components/video/FeaturedVideoCard.vue";
import TopActions from "@/components/layout/TopActions.vue";

const authStore = useAuthStore();
</script>
<style scoped>
.home-view {
  min-height: 100vh;
  background-image: url("/backgrounds/home-bg.svg");
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: center;
}

.hero-content {
  width: 100%;
  max-width: 1200px;
  padding: 60px 24px 0;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

h1 {
  font-size: 64px;
  font-weight: 700;
  color: #fff;
  text-align: center;
}

.subtitle {
  text-align: center;
  color: #fff;
  font-style: italic;
  margin-top: 8px;
  margin-bottom: 48px;
  opacity: 0.9;
}

.user-menu {
  position: fixed;
  top: 24px;
  right: 32px;
  z-index: 10;
}

/* CÍRCULO */
.avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #c7a43a;
  cursor: pointer;
  background: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-icon {
  font-size: 26px;
  color: #c7a43a;
}

.avatar.active {
  transform: scale(0.96);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

/* DROPDOWN */
.dropdown {
  position: absolute;
  top: 58px;
  right: 0;
  transform: translateX(-10px);
  width: 230px;
  background: #fbf6e6;
  border-radius: 16px;
  padding: 12px 0;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
  animation: dropdownIn 0.25s ease forwards;
}

/* ITENS */
.dropdown span {
  display: block;
  padding: 10px 18px;
  cursor: pointer;
  font-weight: 600;
  color: #333;
  transition: background 0.2s ease;
}

.top-actions {
  position: fixed;
  top: 28px;
  right: 40px;
  display: flex;
  align-items: center;
  gap: 14px;
  z-index: 9999;
}

.dropdown span:hover {
  background: rgba(199, 164, 58, 0.18);
}

.dropdown .sair {
  color: #b00020;
}

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes dropdownIn {
  from {
    opacity: 0;
    transform: translateX(8px) translateY(-6px);
  }

  to {
    opacity: 1;
    transform: translateX(-10px) translateY(0);
  }
}

.reveal {
  opacity: 0;
  animation: fadeUp 0.8s ease-out forwards;
  animation-delay: var(--delay, 0s);
  will-change: transform, opacity;
}

@media (max-width: 600px) {
  .home-view {
    align-items: flex-start;
  }

  .hero-content {
    padding: 48px 16px 0;
    min-height: 100vh;
  }

  h1 {
    font-size: 38px;
    line-height: 1.2;
  }

  .subtitle {
    font-size: 14px;
    margin-bottom: 32px;
    padding: 0 6px;
  }

  .user-menu {
    top: 16px;
    right: 16px;
  }

  .dropdown {
    transform: translateX(0);
  }

  @media (max-width: 600px) {
    .top-actions {
      top: 18px;
      right: 18px;
    }

    .dropdown {
      width: 90vw;
      right: -40px;
    }
  }
}
</style>
