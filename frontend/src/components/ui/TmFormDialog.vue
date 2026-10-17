<template>
  <v-dialog
    :model-value="modelValue"
    :max-width="maxWidth"
    :fullscreen="mobile"
    :persistent="bloqueado"
    scrollable
    @update:model-value="pedirFechar"
  >
    <v-card class="tm-form-dialog" :aria-labelledby="idTitulo">
      <div class="topo">
        <h2 :id="idTitulo" class="tm-title">{{ titulo }}</h2>
        <v-btn
          icon
          variant="text"
          color="on-surface"
          aria-label="Fechar"
          :disabled="bloqueado"
          @click="pedirFechar(false)"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>
      <v-card-text class="corpo">
        <slot />
      </v-card-text>
      <v-card-actions v-if="$slots.acoes" class="rodape">
        <slot name="acoes" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { useDisplay } from "vuetify";
import { useConfirm } from "@/composables/useConfirm";

let contador = 0;
const idTitulo = `tm-dialog-titulo-${++contador}-${Math.random().toString(36).slice(2, 7)}`;

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  titulo: { type: String, required: true },
  maxWidth: { type: [String, Number], default: 720 },
  /** Bloqueia fechar (ex.: durante upload). */
  bloqueado: { type: Boolean, default: false },
  /** Há alterações não salvas: pede confirmação antes de fechar. */
  sujo: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "descartar"]);

const { mobile } = useDisplay();
const { confirmar } = useConfirm();

async function pedirFechar(valor) {
  if (valor) {
    emit("update:modelValue", true);
    return;
  }
  if (props.bloqueado) return;
  if (props.sujo) {
    const ok = await confirmar({
      titulo: "Descartar o que você escreveu?",
      texto: "As alterações ainda não foram salvas.",
      rotuloConfirmar: "Descartar",
      rotuloCancelar: "Continuar editando",
    });
    if (!ok) return;
    emit("descartar");
  }
  emit("update:modelValue", false);
}
</script>

<style scoped>
.tm-form-dialog {
  background: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
}
.topo {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--tm-space-3);
  padding: var(--tm-space-4) var(--tm-space-4) 0 var(--tm-space-5);
}
.topo h2 {
  font-size: var(--tm-fs-2xl);
  margin: 0;
}
.corpo {
  padding: var(--tm-space-4) var(--tm-space-5);
}
.rodape {
  padding: var(--tm-space-3) var(--tm-space-5) var(--tm-space-5);
  gap: var(--tm-space-2);
  flex-wrap: wrap;
  justify-content: flex-end;
}
</style>
