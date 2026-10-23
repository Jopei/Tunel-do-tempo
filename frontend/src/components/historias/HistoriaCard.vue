<template>
  <article ref="cardRef" class="hx-post" :class="{ 'hx-post--destaque': destaque, 'hx-post--compacto': compacto }" :style="{ '--hx-ano-h': hue }">
    <header class="hx-post__cabeca">
      <HistoriaAutoria :usuarios="historia.usuarios ?? []" :data="historia.data_historia" />
      <v-menu v-if="podeEditar" location="bottom end">
        <template #activator="{ props }">
          <v-btn
            v-bind="props"
            icon
            variant="text"
            size="small"
            class="hx-post__menu"
            :aria-label="`Ações de ${historia.titulo}`"
          >
            <v-icon>mdi-dots-vertical</v-icon>
          </v-btn>
        </template>
        <v-list density="compact" :aria-label="`Ações de ${historia.titulo}`">
          <v-list-item prepend-icon="mdi-pencil-outline" title="Editar" @click="$emit('editar')" />
          <v-list-item prepend-icon="mdi-delete-outline" title="Apagar" base-color="error" @click="$emit('excluir')" />
        </v-list>
      </v-menu>
    </header>

    <div class="hx-post__texto">
      <h3 class="hx-post__titulo tm-title">
        <router-link :to="destino" class="hx-post__link">
          <template v-for="(p, i) in partesTitulo" :key="i">
            <mark v-if="p.marcado">{{ p.texto }}</mark>
            <template v-else>{{ p.texto }}</template>
          </template>
        </router-link>
      </h3>
      <p v-if="historia.descricao_curta" class="hx-post__descricao">{{ historia.descricao_curta }}</p>
    </div>

    <div class="hx-post__midia hx-foto" :class="{ 'hx-foto--sem': !capaOk, 'hx-grao': !capaOk }">
      <img
        v-if="capaOk"
        :src="historia.capa_url"
        alt=""
        class="hx-foto__img hx-post__capa"
        loading="lazy"
        decoding="async"
        @error="capaFalhou = true"
      />
      <span v-else class="hx-post__sem-capa" aria-hidden="true"><v-icon size="48">mdi-book-open-page-variant-outline</v-icon></span>
      <span class="hx-post__selo hx-post__selo--tipo">{{ historia.tipo_historia }}</span>
      <span v-if="historia.fotos_count > 1" class="hx-post__selo hx-post__selo--album" :aria-label="`${historia.fotos_count} fotos`">
        <v-icon size="14" aria-hidden="true">mdi-image-multiple-outline</v-icon> +{{ historia.fotos_count - 1 }}
      </span>
      <span v-if="destaque" class="hx-post__selo hx-post__selo--marco">Marco do ano</span>
    </div>

    <div v-if="temContadores" class="hx-post__contadores">
      <span v-if="historia.favoritos_count" class="hx-post__contador-curtidas">
        <v-icon size="16" color="error" aria-hidden="true">mdi-heart</v-icon>
        {{ historia.favoritos_count }} {{ historia.favoritos_count === 1 ? "curtida" : "curtidas" }}
      </span>
      <span class="hx-post__contador-resto">
        <template v-if="historia.lembrancas_count">{{ historia.lembrancas_count }} {{ historia.lembrancas_count === 1 ? "comentário" : "comentários" }}</template>
        <template v-if="historia.lembrancas_count && resumoMidia"> · </template>
        <template v-if="resumoMidia">{{ resumoMidia }}</template>
      </span>
    </div>

    <div class="hx-post__acoes" role="group" :aria-label="`Reações a ${historia.titulo}`">
      <button
        ref="curtirRef"
        type="button"
        class="hx-acao hx-acao--curtir"
        :class="{ 'hx-acao--ativa': historia.favoritado }"
        :aria-pressed="!!historia.favoritado"
        @click="$emit('curtir')"
      >
        <v-icon size="20" aria-hidden="true" class="hx-acao__icone">{{ historia.favoritado ? "mdi-heart" : "mdi-heart-outline" }}</v-icon>
        {{ historia.favoritado ? REDE.verbos.curtido : REDE.verbos.curtir }}
      </button>
      <router-link :to="{ ...destino, hash: '#lembrancas' }" class="hx-acao hx-acao--comentar">
        <v-icon size="20" aria-hidden="true" class="hx-acao__icone">mdi-comment-outline</v-icon>
        {{ REDE.verbos.comentar }}
      </router-link>
      <button type="button" class="hx-acao hx-acao--compartilhar" @click="$emit('compartilhar')">
        <v-icon size="20" aria-hidden="true" class="hx-acao__icone">mdi-share-variant-outline</v-icon>
        {{ REDE.verbos.compartilhar }}
      </button>
    </div>
  </article>
</template>

<script setup>
import { computed, nextTick, ref, watch } from "vue";
import { REDE } from "@/config/esquina";
import { anoDe, hueDoAno } from "@/composables/useCapitulos";
import HistoriaAutoria from "@/components/historias/HistoriaAutoria.vue";
import { animarEsquina, reduzirMovimentoEsquina } from "@/composables/useMovimentoEsquina";

const props = defineProps({
  historia: { type: Object, required: true },
  termo: { type: String, default: "" },
  podeEditar: { type: Boolean, default: false },
  /** primeiro post Principal do ano: capa maior */
  destaque: { type: Boolean, default: false },
  /** grade de cartões: sem barra de ações extensa */
  compacto: { type: Boolean, default: false },
});
defineEmits(["editar", "excluir", "curtir", "compartilhar"]);

const capaFalhou = ref(false);
const capaOk = computed(() => !!props.historia.capa_url && !capaFalhou.value);
const destino = computed(() => ({ name: "historias-show", params: { uuid: props.historia.uuid } }));
const hue = computed(() => hueDoAno(anoDe(props.historia)));

// Guardar (curtir): coração pop 1→1.3→1, 6 partículas mínimas e contador que sobe. Só ao curtir.
const cardRef = ref(null);
const curtirRef = ref(null);
watch(
  () => props.historia.favoritado,
  async (agora) => {
    if (!agora) return;
    const botao = curtirRef.value;
    animarEsquina(botao?.querySelector(".hx-acao__icone"), [{ transform: "scale(1)" }, { transform: "scale(1.3)" }, { transform: "scale(1)" }], {
      duration: 400,
      easing: "ease-out",
    });
    if (botao && !reduzirMovimentoEsquina()) {
      for (let i = 0; i < 6; i++) {
        const p = document.createElement("span");
        p.className = "hx-motion-particula";
        botao.appendChild(p);
        const a = (i / 6) * Math.PI * 2;
        const d = 18;
        const anim = animarEsquina(
          p,
          [
            { transform: "translate(0, 0) scale(1)", opacity: 1 },
            { transform: `translate(${Math.cos(a) * d}px, ${Math.sin(a) * d}px) scale(0.2)`, opacity: 0 },
          ],
          { duration: 400, easing: "ease-out" },
        );
        if (anim) anim.onfinish = () => p.remove();
        else p.remove();
      }
    }
    await nextTick();
    animarEsquina(cardRef.value?.querySelector(".hx-post__contador-curtidas"), [{ opacity: 0, transform: "translateY(40%)" }, { opacity: 1, transform: "translateY(0)" }], {
      duration: 300,
      easing: "ease-out",
    });
  },
);

const resumoMidia = computed(() => {
  const h = props.historia;
  const p = [];
  if (h.fotos_count) p.push(`${h.fotos_count} ${h.fotos_count === 1 ? "foto" : "fotos"}`);
  if (h.videos_count) p.push(`${h.videos_count} ${h.videos_count === 1 ? "vídeo" : "vídeos"}`);
  if (h.musicas_count) p.push(`${h.musicas_count} ${h.musicas_count === 1 ? "música" : "músicas"}`);
  return p.join(", ");
});
const temContadores = computed(() => !!(props.historia.favoritos_count || props.historia.lembrancas_count || resumoMidia.value));

const partesTitulo = computed(() => {
  const t = props.historia.titulo ?? "";
  const termo = props.termo.trim();
  if (!termo) return [{ texto: t, marcado: false }];
  const i = t.toLowerCase().indexOf(termo.toLowerCase());
  if (i < 0) return [{ texto: t, marcado: false }];
  return [
    { texto: t.slice(0, i), marcado: false },
    { texto: t.slice(i, i + termo.length), marcado: true },
    { texto: t.slice(i + termo.length), marcado: false },
  ].filter((p) => p.texto);
});
</script>

<style scoped>
.hx-post {
  position: relative; /* o link do título cobre o cartão via ::after */
  width: 100%;
  background: var(--tm-paper);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-md);
  box-shadow: var(--tm-shadow-sm);
  min-height: 96px;
  display: flex;
  flex-direction: column;
  transition: transform 200ms ease-out;
}
.hx-post:hover,
.hx-post:focus-within {
  transform: translateY(-2px);
  box-shadow: var(--tm-shadow-md);
}
.hx-post__cabeca {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--tm-space-2);
  padding: var(--tm-space-3) var(--tm-space-3) var(--tm-space-2) var(--tm-space-4);
}
.hx-post__menu {
  position: relative;
  z-index: 2;
  width: 44px;
  height: 44px;
  flex: none;
}
.hx-post__texto {
  padding: 0 var(--tm-space-4) var(--tm-space-3);
}
.hx-post__titulo {
  font-size: var(--tm-fs-xl);
  margin: 0 0 var(--tm-space-1);
}
.hx-post__link {
  color: inherit;
  text-decoration: none;
}
.hx-post__link::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: var(--tm-radius-md);
  z-index: 0;
}
.hx-post__link:focus-visible {
  outline: none;
}
.hx-post__link:focus-visible::after {
  outline: 3px solid #f0c86a;
  outline-offset: 2px;
}
.hx-post__link mark {
  background: rgba(199, 164, 58, 0.45);
  color: inherit;
  border-radius: 3px;
}
.hx-post__descricao {
  margin: 0;
  color: var(--tm-ink-muted);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.hx-post__midia {
  pointer-events: none; /* o clique cai no link do título (overlay) */
  aspect-ratio: 16 / 10;
  display: grid;
  place-items: center;
}
.hx-post--destaque .hx-post__midia {
  aspect-ratio: 16 / 9;
}
.hx-post__sem-capa {
  color: var(--hx-ano-selo);
  opacity: 0.7;
  position: relative;
}
.hx-post__selo {
  position: absolute;
  z-index: 1;
  padding: 2px 10px;
  border-radius: var(--tm-radius-pill);
  background: rgba(20, 15, 5, 0.78);
  color: #fff;
  font-size: var(--tm-fs-xs);
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.hx-post__selo--tipo {
  top: 12px;
  left: 12px;
}
.hx-post__selo--album {
  bottom: 12px;
  right: 12px;
}
.hx-post__selo--marco {
  top: 12px;
  right: 12px;
  background: var(--hx-ano-selo);
}
.hx-post__contadores {
  display: flex;
  justify-content: space-between;
  gap: var(--tm-space-3);
  flex-wrap: wrap;
  padding: var(--tm-space-3) var(--tm-space-4) var(--tm-space-2);
  font-size: var(--tm-fs-sm);
  color: var(--tm-ink-muted);
}
.hx-post__contador-curtidas {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.hx-post__acoes {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-top: 1px solid var(--tm-border);
  margin-top: auto;
  position: relative;
  z-index: 1;
}
.hx-acao {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 48px;
  padding: 0 var(--tm-space-2);
  background: transparent;
  border: 0;
  color: var(--tm-ink-muted);
  font: inherit;
  font-size: var(--tm-fs-sm);
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  border-radius: 0 0 var(--tm-radius-md) var(--tm-radius-md);
}
.hx-acao + .hx-acao {
  border-left: 1px solid var(--tm-border);
}
.hx-acao:hover {
  background: var(--hx-balao);
  color: var(--tm-ink);
}
.hx-acao--ativa {
  color: var(--hx-acao-texto);
}
.hx-acao--ativa .hx-acao__icone {
  color: var(--hx-acao-ativa);
}
.hx-acao:focus-visible {
  outline: 3px solid #f0c86a;
  outline-offset: -3px;
}
.hx-post--compacto .hx-post__descricao {
  -webkit-line-clamp: 2;
  line-clamp: 2;
}
@media (max-width: 599px) {
  .hx-post {
    border-radius: 0;
    border-inline: 0;
  }
  .hx-post__link::after,
  .hx-acao {
    border-radius: 0;
  }
}
</style>
