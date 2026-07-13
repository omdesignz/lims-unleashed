<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapGuide(guide) {
  return {
    id: guide.id,
    reference: guide.guide_no,
    date: guide.date,
    total: null,
    status: "Disponivel",
    tone: "success",
    description: guide.contact ? `Contacto: ${guide.contact}` : null,
    details: [
      { label: "DU", value: guide.du_no },
      { label: "BL", value: guide.bl },
    ],
  };
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Guias contratuais"
    kicker="Documentacao de processo"
    description="Consulte guias emitidas para os processos associados ao seu local operacional."
    entity-label="guia contratual"
    :icon="ClipboardDocumentCheckIcon"
    download-route="portal.contractguides.getContractGuidePDF"
    support-type="document_request"
    support-title="Pedido de guia contratual"
    :map-record="mapGuide"
  />
</template>
