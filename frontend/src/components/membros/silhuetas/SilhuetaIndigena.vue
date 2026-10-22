<template>
  <!-- Indígena de corpo inteiro, de perfil à esquerda, atirando a flecha para a esquerda.
       Eixo do disparo (y = 100): nock da corda, centro do arco (empunhadura) e flecha alinhados. -->
  <svg class="sil sil-indigena" viewBox="0 0 300 420" aria-hidden="true" focusable="false">
    <g fill="currentColor" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
      <!-- pernas em passada larga (coxa e panturrilha cônicas), pés descalços -->
      <path stroke="none" d="M150 246 L178 246 C184 290 196 332 206 370 L212 408 L190 408 L176 372 C164 338 154 296 150 246 Z" />
      <path stroke="none" d="M124 246 L156 246 C152 290 140 322 130 352 L120 408 L92 408 L106 354 C116 322 124 292 124 246 Z" />
      <path stroke="none" d="M84 408 L122 408 L122 420 L78 420 Z M188 408 L216 408 L224 420 L188 420 Z" />
      <!-- tronco: peito aberto afinando até a cintura; tanga pendente -->
      <path stroke="none" d="M128 94 L168 94 C184 108 182 142 172 182 L168 254 L126 254 L128 182 C118 142 118 108 128 94 Z" />
      <path stroke="none" d="M124 244 L172 244 L176 298 L150 282 L122 304 Z" />
      <!-- cabeça de perfil -->
      <path stroke="none" d="M134 52 L127 66 L132 69 L131 75 L134 85 L144 93 L164 94 C174 82 174 62 168 52 Z" />
      <!-- cocar: penas em leque atrás e acima da cabeça -->
      <g class="sil-cocar" stroke="none">
        <path d="M168 54 L206 30 L196 62 Z" />
        <path d="M166 50 L204 6 L186 56 Z" />
        <path d="M160 46 L188 -4 L172 48 Z" />
        <path d="M152 44 L166 -8 L156 46 Z" />
        <path d="M144 44 L142 -4 L148 46 Z" />
        <path d="M136 46 L120 2 L140 48 Z" />
        <path d="M132 52 L106 18 L136 54 Z" />
        <path d="M130 50 L170 50 L168 58 L132 58 Z" />
      </g>
      <!-- braço da frente estendido, mão no centro do arco (empunhadura em x 68, y 100) -->
      <path d="M136 112 L104 120 L68 100" fill="none" stroke-width="14" />
      <!-- braço de trás puxando a corda pelo nock: ombro -> cotovelo -> antebraço (em dois níveis, para dobrar no cotovelo) -->
      <g class="sil-braco-tras">
        <path d="M158 116 L132 141" fill="none" stroke-width="13" />
        <g class="sil-antebraco">
          <path d="M132 141 L100 100" fill="none" stroke-width="12" />
        </g>
      </g>
      <!-- arco: tips em x 100 (corda em repouso), empunhadura no meio (x 68,5) -->
      <path class="sil-arco" d="M100 28 C58 56 58 144 100 172" fill="none" stroke-width="6" />
      <!-- rastro do disparo: do ponto de soltura até o nock, atrás da flecha -->
      <g transform="translate(130 100) rotate(180)">
        <path class="sil-rastro" d="M0 0 H230" fill="none" stroke-width="2.2" />
      </g>
      <!-- flecha: nock em (100, 100), apontando para a esquerda; o movimento é só translação -->
      <g class="sil-flecha">
        <g transform="translate(100 100) rotate(180)">
          <path d="M0 0 H74" fill="none" stroke-width="2.6" />
          <path stroke="none" d="M74 -4 L84 0 L74 4 Z" />
          <path stroke="none" d="M-2 0 L-14 -6 L-10 0 Z M-2 0 L-14 6 L-10 0 Z" />
        </g>
      </g>
    </g>
    <!-- corda do arco em duas metades, vértice (nock) no centro; faixas/pintura em cor clara -->
    <g class="sil-realce" fill="none" stroke-linecap="round">
      <path class="sil-corda-sup" d="M100 28 L100 100" stroke-width="1.8" />
      <path class="sil-corda-inf" d="M100 172 L100 100" stroke-width="1.8" />
      <path d="M126 256 L170 256 M126 246 L170 246" stroke-width="3" />
      <path d="M130 52 L168 50" stroke-width="2.4" />
    </g>
  </svg>
</template>

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
.sil-rastro {
  stroke: var(--sil-realce);
  opacity: 0;
}
</style>
