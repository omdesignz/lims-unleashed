<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import StatusChip from "@/Components/plano/StatusChip.vue";
import DocumentShareModal from "@/Components/documents/DocumentShareModal.vue";
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { usePermission } from "@/Composables/usePermissions";
import { ArrowRight as ArrowRightIcon, Download as DocumentArrowDownIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

/**
 * The certificate dossier. It is generated from approved results, validated once with
 * the validator's signature and, after that, changed only through ISO revisions. The
 * release gate shown here is the same rule the validation enforces.
 */
const props = defineProps({
  record: { type: Object, default: () => ({ data: {} }) },
  release: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const shareOpen = ref(false);

const certificate = computed(() => props.record?.data ?? {});
const validated = computed(() => Boolean(certificate.value.validated_at));
const ready = computed(() => Boolean(props.release?.ready));
const pending = computed(() => Math.max(0, (props.release?.results ?? 0) - (props.release?.approved ?? 0)));
const pdfUrl = computed(() => (certificate.value.id ? route("qualitycertificates.getPDF", { id: certificate.value.id }) : "#"));
const title = computed(() => `Boletim ${certificate.value.code || `#${certificate.value.id}`}`);

const state = computed(() => {
  if (certificate.value.deleted) {
    return { tone: "neutral", label: "Arquivado" };
  }
  if (validated.value) {
    return { tone: "done", label: "Validado" };
  }

  return ready.value ? { tone: "ok", label: "Pronto a validar" } : { tone: "wait", label: "Aguarda aprovação" };
});

const identity = computed(() => [
  ["Cliente", certificate.value.customer],
  ["Local", certificate.value.warehouse],
  ["Produto", certificate.value.product],
  ["Código laboratorial", certificate.value.lab_code],
  ["Emitido em", formatDate(certificate.value.created_at)],
  ["Emitido por", certificate.value.user?.name],
]);

const resultStages = computed(() => [
  ["Resultados", props.release?.results ?? 0],
  ["Inseridos", props.release?.inserted ?? 0],
  ["Verificados", props.release?.verified ?? 0],
  ["Aprovados", props.release?.approved ?? 0],
]);

const validation = computed(() => {
  if (!validated.value) {
    return null;
  }

  return {
    by: certificate.value.validated_by_user || "—",
    onBehalfOf: certificate.value.validated_on_behalf_of_user,
    at: formatDate(certificate.value.validated_at),
  };
});

function formatDate(value) {
  if (!value) {
    return "—";
  }

  return new Date(value).toLocaleString("pt-PT", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });
}
</script>

<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Certificados', url: route('qualitycertificates.index') }, { title: certificate.code || `#${certificate.id}` }]"
      :title="title"
      :lede="[certificate.customer, certificate.product, certificate.lab_code].filter(Boolean).join(' · ') || 'Boletim de resultados'"
    >
      <template #badges><StatusChip :tone="state.tone">{{ state.label }}</StatusChip></template>
      <template #actions>
        <a :href="pdfUrl" target="_blank" rel="noopener" class="ds-button ds-button-secondary">PDF<span class="sr-only"> (abre noutra janela)</span></a>
        <button type="button" class="ds-button ds-button-secondary" @click="shareOpen = true">Enviar</button>
        <Link
          v-if="!validated && hasPermission('edit_quality_certificates')"
          :href="route('qualitycertificates.edit', { certificate: certificate.id })"
          class="ds-button ds-button-quiet"
        >Observação</Link>
      </template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-labelledby="release-title">
          <div class="pl-panel-head">
            <h2 id="release-title" class="pl-k">Resultados por detrás do boletim</h2>
            <StatusChip :tone="ready ? 'ok' : 'wait'">{{ ready ? "Todos aprovados" : `${pending} por aprovar` }}</StatusChip>
          </div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="[label, value] in resultStages" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd class="pl-num">{{ value }}</dd></div>
          </dl>
          <p class="border-t border-[var(--pl-line)] p-4 text-sm text-[var(--pl-muted)]">
            Cada resultado passa por três pessoas: quem insere, quem verifica e quem aprova. O boletim só é validado quando todos estiverem aprovados.
          </p>
        </section>

        <section class="pl-panel" aria-labelledby="identity-title">
          <div class="pl-panel-head"><h2 id="identity-title" class="pl-k">Identificação</h2><span class="pl-k pl-faint">Fixa desde a recepção</span></div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="[label, value] in identity" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value || "—" }}</dd></div>
          </dl>
          <div class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Observação</h3>
            <p class="text-sm">{{ certificate.obs || "Sem observação." }}</p>
          </div>
        </section>

        <section v-if="validation" class="pl-panel" aria-labelledby="validation-title">
          <div class="pl-panel-head"><h2 id="validation-title" class="pl-k">Validação</h2><StatusChip tone="done">Assinado</StatusChip></div>
          <dl class="pl-facts pl-facts-2">
            <div class="pl-fact"><dt>Validado por</dt><dd>{{ validation.by }}</dd></div>
            <div class="pl-fact"><dt>Em</dt><dd class="pl-num">{{ validation.at }}</dd></div>
            <div v-if="validation.onBehalfOf" class="pl-fact"><dt>Em nome de</dt><dd>{{ validation.onBehalfOf }}</dd></div>
          </dl>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Dossier</h2><span class="pl-k pl-faint">{{ certificate.lab_code || "Sem código" }}</span></div>
          <Link v-if="release.sample" :href="release.sample.url" class="pl-row"><span>Amostra <span class="pl-num">{{ release.sample.code }}</span></span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <Link v-if="release.proposal" :href="release.proposal.url" class="pl-row"><span>Proposta <span class="pl-num">{{ release.proposal.code }}</span></span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <Link :href="route('qualitycertificates.iso-revisions.index', { certificate: certificate.id })" class="pl-row">
            <span>Revisões ISO<span v-if="release.revision" class="pl-num pl-muted"> · v{{ release.revision }}</span></span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
          </Link>
          <a :href="pdfUrl" target="_blank" rel="noopener" class="pl-row"><span>PDF do boletim<span class="sr-only"> (abre noutra janela)</span></span><DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" /></a>
        </section>
      </aside>
    </div>

    <NextStepBar>
      <template v-if="validated">
        Validado por {{ validation.by }}<template v-if="validation.onBehalfOf"> em nome de {{ validation.onBehalfOf }}</template> · {{ validation.at }}. Correcções seguem a revisão ISO.
      </template>
      <template v-else-if="ready && hasPermission('validate_quality_certificates')">Todos os {{ release.results }} resultados estão aprovados. Reveja-os e assine o boletim.</template>
      <template v-else-if="ready">Todos os resultados estão aprovados. Aguarda um validador com permissão para assinar.</template>
      <template v-else>Faltam aprovar {{ pending }} de {{ release.results ?? 0 }} resultados antes da validação.</template>
      <template #actions>
        <Link
          v-if="validated"
          :href="route('qualitycertificates.iso-revisions.index', { certificate: certificate.id })"
          class="ds-button ds-button-secondary"
        >Revisões ISO</Link>
        <Link
          v-else-if="ready && hasPermission('validate_quality_certificates')"
          :href="route('qualitycertificates.getApprove', { id: certificate.id })"
          class="ds-button ds-button-primary"
          preserve-scroll
        >Validar boletim</Link>
        <Link v-else-if="release.sample" :href="release.sample.url" class="ds-button ds-button-secondary">Ver amostra</Link>
      </template>
    </NextStepBar>

    <DocumentShareModal
      :open="shareOpen"
      document-type="quality_certificate"
      :document-id="certificate.id"
      document-label="Boletim analítico"
      :document-number="certificate.code"
      :default-recipients="certificate.recipient_emails || []"
      @close="shareOpen = false"
    />
  </div>
</template>
