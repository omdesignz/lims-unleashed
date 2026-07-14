<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { DocumentCheckIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapQuote(quote) {
  const expired = quote.due_date && new Date(quote.due_date) < new Date();
  const converted = Boolean(quote.converted_to_invoice || quote.invoice_id);

  return {
    id: quote.id,
    reference: quote.quote_no,
    date: quote.date,
    total: quote.total,
    status: converted ? "Convertida em factura" : expired ? "Expirada" : "Em vigor",
    tone: converted ? "success" : expired ? "danger" : "info",
    description: quote.description || quote.obs,
    details: [
      { label: "Válida até", value: formatDate(quote.due_date) },
      { label: "Referência interna", value: quote.internal_ref },
    ],
  };
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat("pt-PT", { dateStyle: "medium" }).format(new Date(value)) : null;
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Propostas comerciais"
    kicker="Âmbitos e preços"
    description="Reveja propostas emitidas, validade, conversao e o PDF integral de cada documento."
    entity-label="proposta"
    :icon="DocumentCheckIcon"
    download-route="portal.quotes.getQuotePDF"
    support-type="billing_support"
    support-title="Apoio sobre proposta comercial"
    value-metric-label="Proposto nesta página"
    :map-record="mapQuote"
  />
</template>
