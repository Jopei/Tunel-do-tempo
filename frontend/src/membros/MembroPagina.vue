<template>
  <article class="membro-pagina tm-immersive" :style="varsTema">
    <!-- 1. barra-topo (fixa pela casca) -->
    <div class="mp-topo">
      <nav class="mp-migalhas" aria-label="Você está em">
        <router-link to="/a-rua"><v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> A Rua</router-link>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ membro?.nome }}</span>
      </nav>
      <slot name="topo-extra" />
    </div>

    <div v-if="carregando" class="mp-corpo" aria-busy="true" aria-label="Carregando membro">
      <v-skeleton-loader type="avatar, heading, paragraph" />
    </div>
    <div v-else-if="erro" class="mp-corpo">
      <TmErrorState texto="Não conseguimos carregar esta página." @retry="$emit('retry')" />
    </div>

    <template v-else-if="membro">
      <!-- 2. hero -->
      <section class="mp-hero" aria-labelledby="mp-nome">
        <div class="mp-hero-fundo" aria-hidden="true"><slot name="hero-fundo" /></div>
        <div class="mp-hero-conteudo">
          <slot name="hero-conteudo" :membro="membro">
            <div class="mp-simbolo" ref="simboloRef" :aria-hidden="true">
              <slot name="simbolo" :membro="membro" :simbolo="simbolo">
                <MembroSimbolo :simbolo="simbolo" :nome="membro.nome" :foto="membro.foto_url" />
              </slot>
            </div>
            <div class="mp-texto">
              <h1 id="mp-nome" ref="nomeRef" tabindex="-1" class="mp-nome">{{ membro.nome }}</h1>
              <p v-if="membro.descricao" class="mp-lema">{{ membro.descricao }}</p>
              <div class="mp-hero-acoes"><slot name="hero-acoes" :membro="membro" /></div>
            </div>
          </slot>
        </div>
        <img
          v-if="membro.foto_url"
          class="mp-medalhao"
          :src="membro.foto_url"
          alt=""
          width="96"
          height="96"
          ref="medalhaoRef"
        />
      </section>
      <slot name="depois-hero" :membro="membro" />

      <div class="mp-corpo">
        <!-- 3. historia-pessoal -->
        <section v-if="membro.historia_pessoal" class="mp-secao mp-historia" aria-labelledby="mp-hist-t">
          <slot name="historia-antes" :membro="membro" />
          <slot name="historia-pessoal" :membro="membro" :historia="membro.historia_pessoal">
            <h2 id="mp-hist-t" class="mp-titulo">A história de {{ primeiroNome }}</h2>
            <p class="mp-meta">{{ tempoLeitura(membro.historia_pessoal.conteudo) }} min de leitura</p>
            <div class="tm-prose" v-html="htmlHistoria" />
          </slot>
          <slot name="historia-depois" :membro="membro" />
        </section>
        <slot name="depois-historia" :membro="membro" />

        <!-- 4. midia -->
        <section v-if="temSlot('midia') || temSlot('midia-fotos') || temSlot('midia-videos') || temSlot('midia-musicas')" class="mp-secao" aria-labelledby="mp-midia-t">
          <slot name="midia" :membro="membro">
            <h2 id="mp-midia-t" class="mp-titulo">Momentos</h2>
            <slot name="midia-fotos" :membro="membro" />
            <slot name="midia-videos" :membro="membro" />
            <slot name="midia-musicas" :membro="membro" />
          </slot>
        </section>
        <slot name="depois-midia" :membro="membro" />

        <!-- 5. historias (sempre presente) -->
        <section class="mp-secao" aria-labelledby="mp-hists-t">
          <slot name="historias" :membro="membro" :historias="historias">
            <h2 id="mp-hists-t" class="mp-titulo">Histórias em que {{ primeiroNome }} aparece</h2>
            <p v-if="!historias.length" class="mp-vazio">Ainda não há histórias com {{ primeiroNome }}.</p>
            <ul v-else class="mp-historias">
              <li v-for="h in historias.slice(0, 6)" :key="h.uuid">
                <router-link :to="{ name: 'historias-show', params: { uuid: h.uuid } }" class="mp-hist-card">
                  <strong class="mp-hist-titulo">{{ h.titulo }}</strong>
                  <span v-if="h.descricao_curta">{{ h.descricao_curta }}</span>
                </router-link>
              </li>
            </ul>
            <p v-if="historias.length > 6" class="mp-mais">
              <router-link to="/historias">Ver todas as histórias</router-link>
            </p>
          </slot>
        </section>
        <slot name="depois-historias" :membro="membro" />

        <!-- 6. rodape-membro -->
        <footer class="mp-rodape">
          <slot name="rodape-membro" :membro="membro">
            <router-link v-if="anterior" :to="`/membros/${anterior}`" class="mp-nav">
              <v-icon size="18" aria-hidden="true">mdi-arrow-left</v-icon> Anterior
            </router-link>
            <router-link to="/a-rua" class="mp-nav">Voltar à rua</router-link>
            <router-link v-if="proximo" :to="`/membros/${proximo}`" class="mp-nav">
              Próximo <v-icon size="18" aria-hidden="true">mdi-arrow-right</v-icon>
            </router-link>
          </slot>
        </footer>
      </div>
    </template>
  </article>
</template>

<script setup>
import { computed, ref, useSlots, watch } from "vue";
import { sanitizar } from "@/composables/useSanitize";
import { tempoLeitura } from "@/composables/useFormat";
import { flipDeTransicao } from "@/composables/useTransicao";
import TmErrorState from "@/components/ui/TmErrorState.vue";
import MembroSimbolo from "./MembroSimbolo.vue";

const props = defineProps({
  /** Resposta de GET /membros/{slug} */
  membro: { type: Object, default: null },
  /** Sobrepõe vars: { cor, texto, hero, fundo, superficie, textoCorpo, fonteTitulo, raio, sombraSimbolo, destaque } */
  tema: { type: Object, default: () => ({}) },
  /** url svg/png, "mdi-*" ou texto/emoji */
  simbolo: { type: String, default: "" },
  carregando: { type: Boolean, default: false },
  erro: { type: Boolean, default: false },
  /** slugs vizinhos para o rodapé (MembroView calcula) */
  anterior: { type: String, default: null },
  proximo: { type: String, default: null },
});
defineEmits(["retry"]);

const slots = useSlots();
const temSlot = (n) => !!slots[n];

// Ganchos para o motion: refs expostas e classes .mp-* estáveis.
const simboloRef = ref(null);
const nomeRef = ref(null);
const medalhaoRef = ref(null);
// FLIP: vindo da órbita, o medalhão nasce sobre a imagem que voou.
watch(medalhaoRef, (el) => el && flipDeTransicao(el));
defineExpose({ simboloRef, nomeRef, medalhaoRef, focarTitulo: () => nomeRef.value?.focus({ preventScroll: true }) });

const historias = computed(() => props.membro?.historias ?? []);
const primeiroNome = computed(() => props.membro?.nome?.split(" ")[0] ?? "");
const htmlHistoria = computed(() => sanitizar(props.membro?.historia_pessoal?.conteudo ?? ""));

const MAPA = {
  cor: "--membro-cor",
  texto: "--membro-cor-texto",
  destaque: "--membro-destaque",
  hero: "--membro-hero-fundo",
  heroTexto: "--membro-hero-texto",
  fundo: "--membro-fundo",
  superficie: "--membro-superficie",
  textoCorpo: "--membro-texto",
  fonteTitulo: "--membro-fonte-titulo",
  raio: "--membro-raio",
  sombraSimbolo: "--membro-sombra-simbolo",
};
const varsTema = computed(() => {
  const out = {};
  for (const [k, v] of Object.entries(props.tema ?? {})) if (v && MAPA[k]) out[MAPA[k]] = v;
  return out;
});
</script>

<style scoped>
.membro-pagina {
  /* Tokens do membro (spec 6.3). Só trocar variáveis, nunca os seletores .mp-*. */
  --membro-cor: var(--tm-gold);
  --membro-cor-texto: var(--tm-gold-text);
  --membro-destaque: var(--membro-cor);
  --membro-hero-fundo: linear-gradient(160deg, color-mix(in srgb, var(--membro-cor) 35%, #14110b), #14110b);
  --membro-hero-texto: #fbf6e6;
  --membro-fundo: var(--tm-paper);
  --membro-superficie: var(--tm-paper-2);
  --membro-texto: var(--tm-ink);
  --membro-fonte-titulo: var(--tm-font-title);
  --membro-raio: var(--tm-radius-md);
  --membro-sombra-simbolo: 0 0 48px color-mix(in srgb, var(--membro-cor) 55%, transparent);

  min-height: 100dvh;
  background: var(--membro-fundo);
  color: var(--membro-texto);
}

.mp-topo {
  position: absolute;
  top: var(--tm-navbar-h);
  left: 0;
  right: 0;
  z-index: 3;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px var(--tm-space-4);
  color: var(--membro-hero-texto);
}
.mp-migalhas {
  display: flex;
  align-items: center;
  gap: 8px;
}
.mp-migalhas a {
  color: inherit;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  min-height: 44px;
  font-weight: 600;
}

.mp-hero {
  position: relative;
  min-height: min(60dvh, 520px);
  padding: calc(var(--tm-navbar-h) + 64px) var(--tm-space-4) calc(var(--tm-space-7) + 48px);
  background: var(--membro-hero-fundo);
  color: var(--membro-hero-texto);
  display: flex;
  align-items: center;
  justify-content: center;
}
.mp-hero-fundo {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}
.mp-hero-conteudo {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--tm-space-5);
  text-align: center;
  max-width: 1100px;
  width: 100%;
}
.mp-simbolo {
  position: relative;
  width: clamp(120px, 28vw, 260px);
  aspect-ratio: 1;
  border-radius: 50%;
  border: 3px solid var(--membro-destaque);
  box-shadow: var(--membro-sombra-simbolo);
  display: grid;
  place-items: center;
  overflow: hidden;
}
/* Símbolo "acende": brilho interno 0 -> 1 em 800ms (opacity) */
.mp-simbolo::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 50%;
  box-shadow: inset 0 0 36px color-mix(in srgb, var(--membro-destaque) 45%, transparent);
  opacity: 0;
  pointer-events: none;
  animation: mp-acende 800ms var(--tm-ease) 150ms forwards;
}
/* Título e lema sobem em stagger de 100ms */
.mp-nome,
.mp-lema,
.mp-hero-acoes {
  animation: mp-sobe 500ms var(--tm-ease) backwards;
}
.mp-nome {
  animation-delay: 100ms;
}
.mp-lema {
  animation-delay: 200ms;
}
.mp-hero-acoes {
  animation-delay: 300ms;
}
.mp-medalhao {
  animation: mp-medalhao-entra 400ms var(--tm-ease) 500ms backwards;
}
@keyframes mp-acende {
  to {
    opacity: 1;
  }
}
@keyframes mp-sobe {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
}
@keyframes mp-medalhao-entra {
  from {
    opacity: 0;
    transform: translate(-50%, 12px);
  }
}
@media (prefers-reduced-motion: reduce) {
  .mp-simbolo::after {
    opacity: 1;
    animation: none;
  }
  .mp-nome,
  .mp-lema,
  .mp-hero-acoes,
  .mp-medalhao {
    animation: none;
  }
}
html[data-reduzir="1"] .mp-simbolo::after {
  opacity: 1;
  animation: none;
}
html[data-reduzir="1"] .mp-nome,
html[data-reduzir="1"] .mp-lema,
html[data-reduzir="1"] .mp-hero-acoes,
html[data-reduzir="1"] .mp-medalhao {
  animation: none;
}
.mp-nome {
  font-family: var(--membro-fonte-titulo);
  font-size: var(--tm-fs-hero);
  line-height: 1.1;
  margin: 0;
  outline: none;
}
.mp-lema {
  font-size: clamp(1.125rem, 2.4vw, 1.375rem);
  max-width: 52ch;
  margin: var(--tm-space-3) auto 0;
}
.mp-medalhao {
  position: absolute;
  left: 50%;
  bottom: -48px;
  transform: translateX(-50%);
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--membro-fundo);
  box-shadow: var(--tm-shadow-md);
  z-index: 2;
}

.mp-corpo {
  max-width: 1100px;
  margin: 0 auto;
  padding: calc(var(--tm-space-8) + 8px) var(--tm-space-4) var(--tm-space-8);
}
.mp-secao {
  margin-bottom: var(--tm-space-7);
}
.mp-historia {
  max-width: 68ch;
  margin-inline: auto;
}
.mp-titulo {
  font-family: var(--membro-fonte-titulo);
  font-size: var(--tm-fs-2xl);
  color: var(--membro-cor-texto);
  margin: 0 0 var(--tm-space-3);
}
.mp-meta,
.mp-vazio {
  color: var(--tm-ink-muted);
}
.mp-historias {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--tm-space-4);
}
.mp-hist-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
  height: 100%;
  padding: var(--tm-space-4);
  background: var(--membro-superficie);
  border: 1px solid var(--tm-border);
  border-radius: var(--membro-raio);
  color: inherit;
  text-decoration: none;
}
.mp-hist-card:hover {
  box-shadow: var(--tm-shadow-md);
}
.mp-hist-titulo {
  font-family: var(--membro-fonte-titulo);
}
.mp-mais a {
  color: var(--membro-cor-texto);
  font-weight: 600;
}
.mp-rodape {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: var(--tm-space-3);
  flex-wrap: wrap;
  border-top: 1px solid var(--tm-border);
  padding-top: var(--tm-space-4);
}
.mp-nav {
  color: var(--membro-cor-texto);
  font-weight: 600;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

@media (min-width: 960px) {
  .mp-hero {
    min-height: min(72dvh, 640px);
  }
  .mp-hero-conteudo {
    flex-direction: row-reverse;
    justify-content: space-between;
    text-align: left;
    padding-inline: var(--tm-space-5);
  }
  .mp-lema {
    margin-left: 0;
  }
}
</style>
