<script setup>
import Modal from "@/Components/Modal.vue";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
  ArrowLeftRight as ArrowsRightLeftIcon,
  FileSearch as DocumentMagnifyingGlassIcon,
  X as XMarkIcon,
} from "@lucide/vue";

const props = defineProps({
  show: Boolean,
  certificate: {
    type: Object,
    default: () => ({}),
  },
  revisionIds: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["close", "compared"]);

const canCompare = computed(() => props.revisionIds.length === 2);

function closeModal() {
  emit("close");
}

function openComparison() {
  if (!canCompare.value) {
    return;
  }

  emit("compared");
  router.visit(
    route("qualitycertificates.iso-revisions.compare-two", {
      certificate: props.certificate.id,
      revision_a: props.revisionIds[0],
      revision_b: props.revisionIds[1],
    }),
  );
}
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="closeModal">
    <div class="min-w-0">
      <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex min-w-0 items-start gap-3">
          <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
            <ArrowsRightLeftIcon class="h-5 w-5" />
          </div>
          <div>
            <p class="ds-kicker">Análise lado a lado</p>
            <h2 class="ds-heading mt-2 text-lg">Comparar revisões</h2>
            <p class="ds-copy mt-1 text-xs"> O resultado abre numa página dedicada com a matriz completa de diferenças. </p>
          </div>
        </div>
        <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </header>

      <div class="space-y-5 px-5 py-5 sm:px-6">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:items-center">
          <article class="ds-command-surface p-4">
            <p class="ds-kicker">Revisão A</p>
            <p class="ds-heading mt-2 font-mono text-lg">
              #{{ revisionIds[0] || "-" }}
            </p>
          </article>

          <ArrowsRightLeftIcon class="mx-auto h-5 w-5 text-[var(--ds-text-muted)]" />

          <article class="ds-command-surface p-4">
            <p class="ds-kicker">Revisão B</p>
            <p class="ds-heading mt-2 font-mono text-lg">
              #{{ revisionIds[1] || "-" }}
            </p>
          </article>
        </div>

        <div class="lims-status-strip p-4">
          <div class="flex items-start gap-3">
            <DocumentMagnifyingGlassIcon class="h-5 w-5 shrink-0 text-[var(--ds-text-muted)]" />
            <div>
              <h3 class="ds-heading text-sm">Conteúdo da comparação</h3>
              <p class="ds-copy mt-1 text-xs"> Inclui dados do certificado, relações laboratoriais, metadados da revisão e classificação ISO. </p>
            </div>
          </div>
        </div>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <button type="button" class="ds-button ds-button-secondary" @click="closeModal">
          Cancelar
        </button>
        <button
          type="button"
          class="ds-button ds-button-primary"
          :disabled="!canCompare"
          @click="openComparison"
        >
          <ArrowsRightLeftIcon class="h-4 w-4" />
          Abrir comparação
        </button>
      </footer>
    </div>
  </Modal>
</template>
