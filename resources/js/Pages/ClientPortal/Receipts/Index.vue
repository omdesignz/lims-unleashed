<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { BadgePercent as ReceiptPercentIcon } from "@lucide/vue";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapReceipt(receipt) {
  return {
    id: receipt.id,
    reference: receipt.rec_no,
    date: receipt.date,
    total: receipt.total,
    status: "Pagamento registado",
    tone: "success",
    description: receipt.description || receipt.obs,
    details: [
      { label: "Cliente", value: receipt.customer },
      { label: "Local", value: receipt.warehouse },
    ],
  };
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Recibos"
    kicker="Comprovativos de pagamento"
    description="Aceda aos pagamentos reconhecidos e descarregue o comprovativo fiscal correspondente."
    entity-label="recibo"
    :icon="ReceiptPercentIcon"
    download-route="portal.receipts.getReceiptPDF"
    support-type="billing_support"
    support-title="Apoio sobre recibos"
    value-metric-label="Recebido nesta página"
    :map-record="mapReceipt"
  />
</template>
