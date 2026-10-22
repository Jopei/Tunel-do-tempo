<template>
  <!-- Carcará (Caracara plancus) planando, asas abertas com as "mãos" de primárias, cauda em leque,
       cabeça de perfil à esquerda com topete e bico curvo. Grupos: .sil-asa-esq/.sil-asa-dir/.sil-cauda. -->
  <svg class="sil sil-carcara" viewBox="0 0 400 220" aria-hidden="true" focusable="false">
    <g fill="currentColor" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round">
      <!-- asas (grupos externos recebem a animação; o espelho fica no path interno) -->
      <g class="sil-asa sil-asa-esq"><path :d="asa" /></g>
      <g class="sil-asa sil-asa-dir"><path :d="asa" transform="matrix(-1 0 0 1 400 0)" /></g>
      <!-- cauda em leque -->
      <path
        class="sil-cauda"
        d="M187 142 L156 204 Q166 216 178 208 Q186 220 197 211 Q204 221 212 211 Q224 220 232 208 Q244 214 246 204 L213 142 Z"
      />
      <!-- corpo: peito largo afinando até a cauda -->
      <path d="M186 98 C184 84 216 84 214 98 L222 138 C216 160 184 160 178 138 Z" />
      <!-- pescoço e cabeça (perfil à esquerda) -->
      <path class="sil-cabeca" d="M190 100 C184 86 182 74 186 64 C190 52 202 46 212 52 C219 57 219 68 216 80 L214 100 Z" />
      <!-- topete -->
      <path class="sil-topete" d="M206 50 C208 38 218 32 228 34 C222 38 220 44 220 54 Z" />
      <!-- bico curvo em gancho -->
      <path class="sil-bico" d="M188 60 C176 58 166 63 164 74 C164 82 168 90 174 94 C173 85 178 78 190 76 Z" />
    </g>
    <!-- olho (a barra clara das primárias ficava fora do grupo da asa e foi removida) -->
    <g class="sil-realce" fill="var(--sil-realce)">
      <circle cx="194" cy="66" r="2.6" />
    </g>
  </svg>
</template>

<script setup>
// asa esquerda: borda de ataque suave, 5 dedos de primárias e secundárias escalonadas
const asa =
  "M190 102 C162 78 126 64 88 62 C56 60 28 68 4 84 L34 86 L10 100 L42 100 L22 118 L54 112 L38 132 " +
  "L72 120 L62 140 C96 130 120 128 152 130 C168 130 180 126 190 124 Z";
</script>

<style scoped>
.sil {
  display: block;
  width: 100%;
  height: auto;
  overflow: visible;
  color: var(--sil-cor, #0d0d0d);
  --sil-realce: #e9dcb8;
}
/* ombro: origem das batidas (coordenadas do viewBox) */
.sil-asa-esq {
  transform-origin: 190px 108px;
  transform-box: view-box;
}
.sil-asa-dir {
  transform-origin: 210px 108px;
  transform-box: view-box;
}
</style>
