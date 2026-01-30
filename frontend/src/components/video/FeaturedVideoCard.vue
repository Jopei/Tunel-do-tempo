<template>
    <div class="video-card">
        <div class="video-frame" @click="play = true">
            <div v-if="!play" class="play-overlay">
                <div class="play-triangle"></div>
            </div>

            <video v-if="play" class="video" src="/videos/Abertura.mp4" controls autoplay />

            <div v-else class="video-placeholder" />
        </div>

        <div class="video-footer">

                <div class="author-info">
                    <div class="title">TUDO COMEÇA AQUI 👆</div>

                    <div class="author-inline">
                        <img src="/avatar-ze-paulo.png" alt="Zé Paulo" class="avatar-inline" />

                        <div class="author-text-inline">
                            <div class="author-name">
                                Zé Paulo
                                <span class="verified">✔</span>
                            </div>
                            <div class="subscribers">13k de inscritos</div>
                        </div>
                    </div>
                </div>

            <button class="know-btn">Conheça</button>
        </div>

        <div class="description-box">
            <div class="time">há algumas tardes</div>
            <div class="description">
                Quanto Mais Eu Como, Mais Fome Eu Sinto !
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
const play = ref(false)
</script>

<style scoped>
/* ===== CARD ===== */
.video-card {
    width: 100%;
    max-width: 880px;
    background: #0f0f0f;
    border-radius: 28px;
    padding: 16px;
    box-shadow: 0 24px 40px rgba(0, 0, 0, 0.35);
}

/* ===== VIDEO ===== */
.video-frame {
    position: relative;
    height: 420px;
    border-radius: 20px;
    background: #1a1a1a;
    overflow: hidden;
    cursor: pointer;
}

.video-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg,
            rgba(255, 255, 255, 0.08),
            rgba(0, 0, 0, 0.6));
}

.video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ===== PLAY ===== */
.play-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.play-triangle {
    width: 0;
    height: 0;
    border-left: 52px solid #ffffff;
    border-top: 30px solid transparent;
    border-bottom: 30px solid transparent;
    opacity: 0.9;
}

/* ===== FOOTER ===== */
.video-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 18px;
}

.author-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #f0c86a;
    object-fit: cover;
}

.author-info {
    display: flex;
    flex-direction: column;
}

/* >>> AJUSTES PRINCIPAIS AQUI <<< */
.title {
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 6px;
}

.author-inline {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar-inline {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #f0c86a;
  object-fit: cover;
}

.author-text-inline {
  display: flex;
  flex-direction: column;
}

.author-name {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 6px;
}

.verified {
  font-size: 14px;
  color: #3ea6ff;
}

.subscribers {
  font-size: 14px;
  color: #cfcfcf;
}


/* ===== DESCRIPTION ===== */
.description-box {
    margin-top: 12px;
    background: #2b2b2b;
    border-radius: 10px;
    padding: 8px 14px;
}

.time {
    font-size: 13px;
    color: #bdbdbd;
    margin-bottom: 4px;
}

.description {
    font-size: 14px;
    color: #ffffff;
    line-height: 1.4;
}

/* ===== BUTTON ===== */
.know-btn {
    background: #ff0000;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    cursor: pointer;
    font-weight: 600;
}
</style>
