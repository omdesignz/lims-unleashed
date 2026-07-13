<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { ReceiptRefundIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapCreditNote(note) {
  const isCancellation = note.reason === "A";

  return {
    id: note.id,
    reference: note.note_no,
    date: note.date,
    total: note.total,
    status: isCancellation ? "Anulacao" : note.reason === "R" ? "Retificacao" : "Emitida",
    tone: isCancellation ? "warning" : "info",
    description: note.description || note.obs,
    details: [
      { label: "Fatura associada", value: note.invoice_id?.inv_no || note.invoice_id?.data?.inv_no },
      { label: "Referencia interna", value: note.internal_ref },
    ],
  };
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Notas de credito"
    kicker="Ajustes de faturacao"
    description="Consulte anulacoes e retificacoes emitidas sobre documentos da sua conta."
    entity-label="nota de credito"
    :icon="ReceiptRefundIcon"
    download-route="portal.creditnotes.getCreditNotePDF"
    support-type="billing_support"
    support-title="Apoio sobre nota de credito"
    value-metric-label="Credito nesta pagina"
    :map-record="mapCreditNote"
  />
</template>
