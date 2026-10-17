<template>
  <v-dialog
    :model-value="confirmState.aberto"
    max-width="440"
    aria-labelledby="confirm-titulo"
    aria-describedby="confirm-texto"
    @update:model-value="(v) => !v && resolverConfirm(false)"
  >
    <v-card>
      <v-card-title id="confirm-titulo" class="tm-title text-wrap">{{ confirmState.titulo }}</v-card-title>
      <v-card-text v-if="confirmState.texto" id="confirm-texto">{{ confirmState.texto }}</v-card-text>
      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn ref="cancelar" variant="text" color="on-surface" autofocus @click="resolverConfirm(false)">
          {{ confirmState.rotuloCancelar }}
        </v-btn>
        <v-btn
          variant="flat"
          :color="confirmState.destrutivo ? 'error' : 'primary'"
          @click="resolverConfirm(true)"
        >
          {{ confirmState.rotuloConfirmar }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { confirmState, resolverConfirm } from "@/composables/useConfirm";
</script>
