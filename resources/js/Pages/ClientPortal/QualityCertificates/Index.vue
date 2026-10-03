<script setup>
import PortalDocumentLibrary from "@/Components/portal/PortalDocumentLibrary.vue";
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { FlaskConical as BeakerIcon } from "@lucide/vue";

defineOptions({ layout: PortalLayout });

defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  query: { type: Object, default: () => ({}) },
});

function mapCertificate(certificate) {
  const released = Boolean(certificate.validated_at || certificate.validated_by_id || certificate.status === true || certificate.status === "released");

  return {
    id: certificate.id,
    reference: certificate.code || certificate.lab_code,
    date: certificate.validated_at || certificate.created_at,
    total: null,
    status: released ? "Validado" : "Em preparacao",
    tone: released ? "success" : "warning",
    description: certificate.product,
    details: [
      { label: "Código laboratorial", value: certificate.lab_code },
      { label: "Validado por", value: certificate.validated_by_user },
    ],
  };
}
</script>

<template>
  <PortalDocumentLibrary
    :record="record"
    :query="query"
    title="Certificados de qualidade"
    kicker="Resultados libertados"
    description="Aceda aos certificados associados às suas amostras e confirme o estado de validação."
    entity-label="certificado"
    :icon="BeakerIcon"
    download-route="portal.qualitycertificates.getQualityCertificatePDF"
    support-type="certificate_support"
    support-title="Apoio sobre certificado"
    :map-record="mapCertificate"
  />
</template>
