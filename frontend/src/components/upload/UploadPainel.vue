<template>
  <Transition name="upload-entra" appear>
  <section
    v-if="up.aberto"
    class="upload-painel"
    :class="{ minimizado: up.minimizado, 'tem-erro': up.comErro > 0, 'terminou': up.terminou }"
    role="region"
    aria-label="Envios"
  >
    <header class="upload-cabeca">
      <div class="upload-titulo">
        <strong>{{ titulo }}</strong>
        <v-progress-linear
          v-if="!up.terminou"
          class="upload-barra-geral"
          :model-value="up.progressoGeral"
          color="primary"
          height="4"
          rounded
          :aria-label="`Progresso geral: ${up.progressoGeral}%`"
        />
      </div>
      <v-btn
        icon
        variant="text"
        size="small"
        class="tm-touch"
        :aria-label="up.minimizado ? 'Expandir envios' : 'Minimizar envios'"
        :aria-expanded="!up.minimizado"
        @click="up.minimizado = !up.minimizado"
      >
        <v-icon>{{ up.minimizado ? "mdi-chevron-up" : "mdi-chevron-down" }}</v-icon>
      </v-btn>
      <v-btn
        v-if="up.ativos"
        icon
        variant="text"
        size="small"
        class="tm-touch"
        aria-label="Cancelar todos os envios"
        @click="up.cancelarTudo()"
      >
        <v-icon>mdi-close-circle-outline</v-icon>
      </v-btn>
      <v-btn v-else icon variant="text" size="small" class="tm-touch" aria-label="Fechar painel de envios" @click="up.fechar()">
        <v-icon>mdi-close</v-icon>
      </v-btn>
    </header>

    <Transition name="upload-corpo">
    <div v-show="!up.minimizado" class="upload-corpo">
      <ul class="upload-lista">
        <li v-for="i in up.itens" :key="i.id" class="upload-item" :data-estado="i.estado" :data-tipo="i.tipo">
          <img v-if="i.preview" :src="i.preview" alt="" class="upload-mini" />
          <span v-else class="upload-mini upload-mini-icone" aria-hidden="true"><v-icon>mdi-video-outline</v-icon></span>
          <div class="upload-info">
            <span class="upload-nome" :title="i.nome">{{ i.nome }}</span>
            <span class="upload-meta">
              <template v-if="i.estado === 'enviando' && i.tipo === 'video'">
                {{ formatarTamanho(i.enviado) }} de {{ formatarTamanho(i.tamanho) }}
              </template>
              <template v-else-if="i.estado === 'erro'">{{ i.erro }}</template>
              <template v-else-if="i.estado === 'fila'">Na fila · {{ formatarTamanho(i.tamanho) }}</template>
              <template v-else-if="i.estado === 'cancelado'">Cancelado</template>
              <template v-else-if="i.estado === 'concluido'">Enviado</template>
              <template v-else>{{ i.progresso }}%</template>
            </span>
            <div v-if="i.estado === 'enviando'" class="upload-barra" aria-hidden="true">
              <span class="upload-barra-preenche" :style="{ transform: `scaleX(${i.progresso / 100})` }"></span>
            </div>
          </div>
          <span class="upload-estado">
            <v-icon v-if="i.estado === 'fila'" size="20" aria-label="Na fila">mdi-clock-outline</v-icon>
            <v-icon v-else-if="i.estado === 'concluido'" class="upload-check" size="22" color="success" aria-label="Concluído">mdi-check-circle</v-icon>
            <v-icon v-else-if="i.estado === 'erro'" size="22" color="error" aria-label="Erro">mdi-alert-circle</v-icon>
            <v-icon v-else-if="i.estado === 'cancelado'" size="20" aria-label="Cancelado">mdi-cancel</v-icon>
          </span>
          <v-btn
            v-if="i.estado === 'erro' || i.estado === 'cancelado'"
            size="small"
            variant="text"
            color="primary-text"
            @click="up.tentarDeNovo(i.id)"
          >
            Tentar de novo
          </v-btn>
          <v-btn
            v-else-if="i.estado === 'fila' || i.estado === 'enviando'"
            icon
            size="x-small"
            variant="text"
            class="tm-touch"
            :aria-label="`Cancelar envio de ${i.nome}`"
            @click="up.cancelar(i.id)"
          >
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </li>
      </ul>
      <footer v-if="up.terminou && up.concluidos" class="upload-resumo">
        <span>{{ resumo }}</span>
        <v-btn v-if="temFotos" size="small" variant="tonal" @click="verFotos">Ver</v-btn>
      </footer>
    </div>
    </Transition>

    <p class="sr-only" role="status" aria-live="polite">{{ up.anuncio }}</p>
  </section>
  </Transition>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useUploadStore } from "@/stores/upload";
import { formatarTamanho } from "@/composables/useArquivos";

const up = useUploadStore();
const router = useRouter();

const titulo = computed(() => {
  if (!up.terminou) return `Enviando ${Math.min(up.concluidos + up.enviando, up.total)} de ${up.total}`;
  if (up.comErro) return `${up.comErro} ${up.comErro === 1 ? "envio falhou" : "envios falharam"}`;
  return "Envio concluído";
});
const temFotos = computed(() => up.itens.some((i) => i.tipo === "foto" && i.estado === "concluido"));
const resumo = computed(() => {
  const n = up.concluidos;
  const fotos = up.itens.filter((i) => i.tipo === "foto" && i.estado === "concluido").length;
  if (fotos === n) return `${n} ${n === 1 ? "foto enviada" : "fotos enviadas"}`;
  return `${n} ${n === 1 ? "arquivo enviado" : "arquivos enviados"}`;
});

function verFotos() {
  if (router.currentRoute.value.name !== "fotos") router.push({ name: "fotos" });
  else window.scrollTo({ top: 0, behavior: "smooth" });
  up.fechar();
}

// Não deixa fechar a aba com envio em andamento.
function antesDeSair(e) {
  if (!up.ativos) return;
  e.preventDefault();
  e.returnValue = "";
}
onMounted(() => window.addEventListener("beforeunload", antesDeSair));
onBeforeUnmount(() => window.removeEventListener("beforeunload", antesDeSair));
</script>

<style scoped>
.upload-painel {
  position: fixed;
  right: 16px;
  bottom: calc(var(--tm-player-h, 0px) + 16px);
  z-index: 1900;
  width: 360px;
  max-width: calc(100vw - 32px);
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-sm);
  box-shadow: var(--tm-shadow-lg);
  overflow: hidden;
}
.upload-cabeca {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 8px 8px 16px;
  min-height: 56px;
}
.upload-titulo {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.upload-lista {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 280px;
  overflow-y: auto;
  border-top: 1px solid var(--tm-border);
}
.upload-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
}
.upload-mini {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  flex: none;
  background: var(--tm-paper-2);
}
.upload-mini-icone {
  display: grid;
  place-items: center;
}
.upload-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.upload-nome {
  font-size: var(--tm-fs-sm);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.upload-meta {
  font-size: var(--tm-fs-xs);
  color: var(--tm-ink-muted);
}
.upload-item[data-estado="erro"] .upload-meta {
  color: rgb(var(--v-theme-error));
}
.upload-barra {
  height: 4px;
  border-radius: 2px;
  background: var(--tm-paper-2);
  overflow: hidden;
}
.upload-barra-preenche {
  display: block;
  height: 100%;
  background: var(--tm-gold);
  transform-origin: left center;
  transition: transform 300ms var(--tm-ease);
}
.upload-estado {
  position: relative;
  display: inline-flex;
}
/* Item novo na lista: sobe e aparece (transform/opacity). */
.upload-item {
  animation: upload-item-entra 200ms var(--tm-ease) backwards;
}
@keyframes upload-item-entra {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
}
/* Concluído: anel que se expande uma vez e some (desenha o "ok"). */
.upload-item[data-estado="concluido"] .upload-estado::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid rgb(var(--v-theme-success));
  pointer-events: none;
  animation: upload-anel 400ms var(--tm-ease) 1 forwards;
}
@keyframes upload-anel {
  from {
    transform: scale(0.6);
    opacity: 0.8;
  }
  to {
    transform: scale(1.7);
    opacity: 0;
  }
}
/* Painel: entra subindo 8px (250ms) e sai esmaecendo. */
.upload-entra-enter-active {
  transition:
    opacity 250ms var(--tm-ease),
    transform 250ms var(--tm-ease);
}
.upload-entra-leave-active {
  transition: opacity 180ms var(--tm-ease);
}
.upload-entra-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.upload-entra-leave-to {
  opacity: 0;
}
/* Corpo: colapsar/expandir com fade + leve deslize (sem animar altura). */
.upload-corpo-enter-active,
.upload-corpo-leave-active {
  transition:
    opacity 180ms var(--tm-ease),
    transform 180ms var(--tm-ease);
}
.upload-corpo-enter-from,
.upload-corpo-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
.upload-resumo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 16px;
  border-top: 1px solid var(--tm-border);
  font-size: var(--tm-fs-sm);
  font-weight: 600;
}
@media (max-width: 600px) {
  .upload-painel {
    right: 0;
    left: 0;
    bottom: var(--tm-player-h, 0px);
    width: 100%;
    max-width: none;
    border-radius: var(--tm-radius-sm) var(--tm-radius-sm) 0 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .upload-barra-preenche,
  .upload-entra-enter-active,
  .upload-entra-leave-active,
  .upload-corpo-enter-active,
  .upload-corpo-leave-active {
    transition: none;
  }
  .upload-item,
  .upload-item[data-estado="concluido"] .upload-estado::after {
    animation: none;
  }
}
html[data-reduzir="1"] .upload-item,
html[data-reduzir="1"] .upload-item[data-estado="concluido"] .upload-estado::after {
  animation: none;
}

/* Concluído: o check surge com pop curto (transform/opacity). */
.upload-check {
  animation: upload-check-pop 300ms var(--tm-ease);
}
@keyframes upload-check-pop {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
}
@media (prefers-reduced-motion: reduce) {
  .upload-check {
    animation: none;
  }
}
html[data-reduzir="1"] .upload-check {
  animation: none;
}
</style>
