<template>
  <!-- Povo da revolução: silhueta de corpo inteiro, de perfil à esquerda, peito aberto e queixo erguido.
       tipo: rapper | campones | operario · pose: 0..2 · mulher: bool. Decorativo (aria-hidden). -->
  <svg class="sil sil-povo" :class="`sil-${tipo}`" viewBox="0 0 200 440" aria-hidden="true" focusable="false">
    <g fill="currentColor" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <!-- ferramenta atrás do corpo -->
      <g v-if="cfg.ferrTras" class="sil-ferramenta" v-html="cfg.ferrTras" />
      <!-- braço de trás -->
      <path class="sil-braco-tras" :d="cfg.bracoB" fill="none" :stroke-width="larg" />
      <!-- pernas (ou saia) -->
      <path v-for="(d, i) in pernas" :key="'p' + i" stroke="none" :d="d" />
      <path v-if="saia" stroke="none" :d="saia" />
      <!-- capuz atrás do pescoço -->
      <path v-if="tipo === 'rapper'" stroke="none" d="M100 62 C128 58 150 76 148 100 L98 92 Z" />
      <!-- tronco e pescoço -->
      <path stroke="none" :d="torso" />
      <path stroke="none" d="M94 58 L110 58 L113 88 L90 88 Z" />
      <!-- cabeça de perfil -->
      <path stroke="none" d="M96 12 C112 10 123 22 123 38 C123 50 117 58 109 62 L103 64 L100 68 C95 69 91 66 89 61 L85 59 L86 55 L81 53 L83 48 L79 46 L83 42 L82 36 C82 22 87 13 96 12 Z" />
      <!-- cabelo (mulher) -->
      <path v-if="mulher" stroke="none" d="M120 24 C146 22 160 46 152 78 C150 64 142 52 126 46 Z" />
      <!-- adereço de cabeça -->
      <g v-if="tipo === 'rapper'" stroke="none">
        <path d="M83 36 C82 14 98 4 114 7 C128 10 132 26 130 38 Z" />
        <path d="M86 32 L46 31 C43 32 43 38 46 39 L86 41 Z" />
      </g>
      <g v-else-if="tipo === 'campones'" stroke="none">
        <path d="M84 32 C82 8 100 0 114 3 C128 6 130 20 128 32 Z" />
        <path d="M44 33 C66 22 112 18 150 28 C158 31 158 36 148 38 C112 44 66 44 46 38 C40 36 40 34 44 33 Z" />
      </g>
      <g v-else stroke="none">
        <path d="M82 36 C80 12 98 2 114 6 C130 10 134 26 132 38 Z" />
        <path d="M70 34 L138 34 C142 34 142 41 138 41 L72 42 C67 42 67 35 70 34 Z" />
      </g>
      <!-- braço da frente e punho -->
      <path class="sil-braco" :d="cfg.bracoF" fill="none" :stroke-width="larg" />
      <circle v-for="(c, i) in cfg.punhos" :key="'f' + i" :cx="c[0]" :cy="c[1]" :r="mulher ? 9.5 : 11" stroke="none" />
      <!-- ferramenta na frente -->
      <g v-if="cfg.ferr" class="sil-ferramenta" v-html="cfg.ferr" />
    </g>
    <!-- detalhes claros -->
    <g class="sil-realce" fill="none" stroke-width="2.4" stroke-linecap="round" v-html="cfg.realce" />
  </svg>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  tipo: { type: String, default: "operario" },
  pose: { type: Number, default: 0 },
  mulher: { type: Boolean, default: false },
});

// perna cônica: quadril h (y 210), joelho k (y 326), tornozelo a (y 416); larguras = meia-largura
function perna(h, k, a, [wt, wk, wa]) {
  const p = (x) => Math.round(x * 10) / 10;
  const sapato = `M${p(a + wa)} 416 L${p(a - wa)} 416 C${p(a - wa - 12)} 418 ${p(a - wa - 26)} 426 ${p(a - wa - 26)} 436 L${p(a + wa + 10)} 436 Z`;
  return (
    `M${p(h - wt)} 208 C${p(h - wt)} 250 ${p(k - wk)} 290 ${p(k - wk)} 326 C${p(k - wk)} 360 ${p(a - wa)} 390 ${p(a - wa)} 416 ` +
    `L${p(a + wa)} 416 C${p(a + wa)} 390 ${p(k + wk)} 360 ${p(k + wk)} 326 C${p(k + wk)} 290 ${p(h + wt)} 250 ${p(h + wt)} 208 Z ` +
    sapato
  );
}

// três posturas de pernas: passada larga, firme, avanço
const POSTURAS = [
  { f: [84, 66, 46], t: [116, 130, 152] },
  { f: [86, 80, 72], t: [114, 120, 128] },
  { f: [84, 60, 50], t: [116, 136, 158] },
];

const LARG = { rapper: [22, 19, 15], campones: [17, 13, 9], operario: [18, 14, 10] };
const LARG_M = { rapper: [18, 15, 12], campones: [14, 11, 8], operario: [15, 12, 9] };

const TORSO_H =
  "M90 82 L114 82 C134 84 146 94 146 112 L142 160 C140 186 134 204 134 222 L72 222 C72 200 72 178 72 154 L66 114 C66 94 78 84 90 82 Z";
const TORSO_M =
  "M92 84 L112 84 C128 86 138 94 138 110 L134 150 C132 170 124 182 128 202 L136 226 L68 226 L76 202 C80 182 72 170 70 150 L66 112 C66 94 80 86 92 84 Z";

// poses: braço de trás/da frente, punhos, ferramenta e realces por tipo
const POSES = {
  rapper: [
    {
      bracoF: "M78 106 L52 78 L46 42",
      bracoB: "M132 106 L152 74 L146 40",
      punhos: [[146, 30]],
      ferr: `<path d="M46 42 L44 26" fill="none" stroke-width="7"/><circle cx="43" cy="17" r="9" stroke="none"/>`,
      realce: `<path d="M92 92 Q102 140 118 94" stroke-dasharray="1 4.6"/><circle cx="104" cy="130" r="6" fill="var(--sil-realce)" stroke="none"/><path d="M84 34 C100 38 118 38 130 36"/>`,
    },
    {
      bracoF: "M78 106 L54 72 L58 32",
      bracoB: "M132 106 L154 72 L150 32",
      punhos: [[58, 22], [150, 22]],
      realce: `<path d="M92 92 Q102 140 118 94" stroke-dasharray="1 4.6"/><circle cx="104" cy="130" r="6" fill="var(--sil-realce)" stroke="none"/><path d="M84 34 C100 38 118 38 130 36"/>`,
    },
    {
      bracoF: "M78 108 L56 136 L68 84",
      bracoB: "M132 106 L160 82 L176 42",
      punhos: [[176, 32]],
      ferr: `<path d="M68 84 L70 68" fill="none" stroke-width="7"/><circle cx="71" cy="59" r="8.5" stroke="none"/>`,
      realce: `<path d="M92 92 Q102 140 118 94" stroke-dasharray="1 4.6"/><circle cx="104" cy="130" r="6" fill="var(--sil-realce)" stroke="none"/><path d="M84 34 C100 38 118 38 130 36"/>`,
    },
  ],
  campones: [
    {
      bracoF: "M78 106 L50 82 L38 46",
      bracoB: "M132 106 L156 148 L130 174",
      punhos: [[38, 40], [130, 174]],
      ferr:
        `<path d="M38 128 L38 8" fill="none" stroke-width="5"/><path d="M38 8 C60 0 84 10 96 36 C78 22 60 20 38 28 Z" stroke="none"/>`,
      realce: `<path d="M80 98 L126 196" stroke-width="5"/><path d="M86 36 C100 40 116 40 128 36"/>`,
    },
    {
      bracoF: "M78 106 L54 74 L58 34",
      bracoB: "M132 106 L150 116 L138 124",
      punhos: [[58, 24]],
      ferrTras:
        `<path d="M112 80 C136 56 174 68 176 104 C178 134 158 148 130 140 C112 130 106 98 112 80 Z" stroke="none"/><path d="M118 80 C128 66 150 62 164 70 L166 80 L120 90 Z" stroke="none"/>`,
      realce: `<path d="M120 78 C136 82 150 82 164 76" stroke-width="3"/><path d="M80 98 L126 196" stroke-width="5"/><path d="M86 36 C100 40 116 40 128 36"/>`,
    },
    {
      bracoF: "M78 106 L52 114 L70 84",
      bracoB: "M132 106 L128 152 L100 136",
      punhos: [[70, 82], [100, 134]],
      ferr:
        `<path d="M122 176 L38 24" fill="none" stroke-width="5"/><path d="M38 24 L12 26 L18 50 L46 42 Z" stroke="none"/>`,
      realce: `<path d="M80 98 L126 196" stroke-width="5"/><path d="M86 36 C100 40 116 40 128 36"/>`,
    },
  ],
  operario: [
    {
      bracoF: "M78 106 L52 78 L46 52",
      bracoB: "M132 106 L152 74 L146 38",
      punhos: [[46, 46], [146, 30]],
      ferr:
        `<path d="M46 126 L46 14" fill="none" stroke-width="6"/><rect x="22" y="2" width="48" height="24" rx="3" stroke="none"/>`,
      realce: `<path d="M90 92 L96 160 M116 92 L108 160"/><path d="M96 38 L118 38" stroke-width="3"/><path d="M82 196 L130 196"/>`,
    },
    {
      bracoF: "M78 106 L54 76 L50 46",
      bracoB: "M132 106 L158 144 L130 172",
      punhos: [[50, 42], [130, 172]],
      ferr:
        `<path d="M50 90 L46 28" fill="none" stroke-width="8"/><path d="M46 28 C32 26 28 8 40 3 L45 14 L53 14 L58 3 C70 8 64 26 51 28 Z" stroke="none"/>`,
      realce: `<path d="M90 92 L96 160 M116 92 L108 160"/><path d="M96 38 L118 38" stroke-width="3"/><path d="M82 196 L130 196"/>`,
    },
    {
      bracoF: "M78 106 L58 72 L62 34",
      bracoB: "M132 106 L156 124 L150 150",
      punhos: [[62, 24], [150, 150]],
      ferrTras:
        `<path d="M144 164 L172 54" fill="none" stroke-width="6"/><path d="M152 40 L196 50 L190 74 L146 64 Z" stroke="none"/>`,
      realce: `<path d="M90 92 L96 160 M116 92 L108 160"/><path d="M96 38 L118 38" stroke-width="3"/><path d="M82 196 L130 196"/>`,
    },
  ],
};

const cfg = computed(() => {
  const lista = POSES[props.tipo] || POSES.operario;
  return lista[((props.pose % lista.length) + lista.length) % lista.length];
});
const larg = computed(() => (props.mulher ? 15 : 19));
const torso = computed(() => (props.mulher ? TORSO_M : TORSO_H));
const saia = computed(() =>
  props.mulher && props.tipo === "campones" ? "M80 208 L124 208 C138 270 150 336 156 392 L48 392 C56 336 68 270 80 208 Z" : ""
);
const pernas = computed(() => {
  const post = POSTURAS[((props.pose % 3) + 3) % 3];
  const l = (props.mulher ? LARG_M : LARG)[props.tipo] || LARG.operario;
  const lm = saia.value ? [8, 7, 6] : l;
  return [perna(...post.t, lm), perna(...post.f, lm)];
});
</script>

<style scoped>
.sil {
  display: block;
  height: 100%;
  width: auto;
  overflow: visible;
  color: var(--sil-cor, #0d0d0d);
  --sil-realce: #e9dcb8;
}
.sil-realce {
  stroke: var(--sil-realce);
  opacity: var(--sil-realce-op, 1);
}
</style>
