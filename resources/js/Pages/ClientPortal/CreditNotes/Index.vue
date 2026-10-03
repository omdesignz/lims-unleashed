<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Receipt as ReceiptRefundIcon } from "@lucide/vue";

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
      { label: "Factura associada", value: note.invoice_id?.inv_no || note.invoice_id?.data?.inv_no },
      { label: "Referência interna", value: note.internal_ref },
    ],
  };
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Notas de crédito"
    kicker="Ajustes de facturação"
    description="Consulte anulacoes e retificacoes emitidas sobre documentos da sua conta."
    entity-label="nota de crédito"
    :icon="ReceiptRefundIcon"
    download-route="portal.creditnotes.getCreditNotePDF"
    support-type="billing_support"
    support-title="Apoio sobre nota de crédito"
    value-metric-label="Crédito nesta página"
    :map-record="mapCreditNote"
  />
</template>
