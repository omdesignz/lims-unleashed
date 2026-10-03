<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Banknote as BanknotesIcon } from "@lucide/vue";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapInvoice(invoice) {
  const amountDue = Number(invoice.amount_due || 0);

  return {
    id: invoice.id,
    reference: invoice.inv_no,
    date: invoice.date,
    total: invoice.total,
    status: amountDue > 0 ? "Pagamento pendente" : "Liquidada",
    tone: amountDue > 0 ? "warning" : "success",
    description: invoice.description,
    details: [
      { label: "Em aberto", value: formatCurrency(amountDue) },
      { label: "Referência interna", value: invoice.internal_ref },
    ],
  };
}

function formatCurrency(value) {
  return new Intl.NumberFormat("pt-PT", { style: "currency", currency: "AOA" }).format(Number(value || 0));
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Facturas"
    kicker="Conta corrente"
    description="Consulte documentos emitidos, saldos pendentes e o PDF fiscal associado a cada factura."
    entity-label="factura"
    :icon="BanknotesIcon"
    download-route="portal.invoices.getInvoicePDF"
    support-type="billing_support"
    support-title="Apoio sobre facturação"
    value-metric-label="Facturado nesta página"
    :map-record="mapInvoice"
  />
</template>
