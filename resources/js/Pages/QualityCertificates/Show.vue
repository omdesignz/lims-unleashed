<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import DocumentShareModal from '@/Components/documents/DocumentShareModal.vue';
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { usePermission } from "@/Composables/usePermissions";
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathRoundedSquareIcon,
  CheckBadgeIcon,
  ClockIcon,
  DocumentIcon,
  DocumentMagnifyingGlassIcon,
  DocumentTextIcon,
  EyeIcon,
  EnvelopeIcon,
  PencilIcon,
  UserIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  record: {
    type: Object,
    default: () => ({ data: {} }),
  },
});

defineOptions({
  layout: Layout,
});

const { hasPermission } = usePermission();

const certificate = computed(() => props.record?.data ?? {});
const shareOpen = ref(false);

const laboratoryReference = computed(() => {
  if (certificate.value.lab_code || certificate.value.cl_code) {
    return certificate.value.lab_code || certificate.value.cl_code;
  }

  if (certificate.value.cl_id) {
    return `Registo #${certificate.value.cl_id}`;
  }

  return "Não associado";
});

const pdfUrl = computed(() => {
  if (!certificate.value.id) {
    return certificate.value.links?.pdf_path ?? "#";
  }

  return route("qualitycertificates.getPDF", { id: certificate.value.id });
});

const certificateMetrics = computed(() => [
  {
    label: "Estado de libertacao",
    value: certificate.value.validated_at ? "Validado" : "Pendente",
    note: certificate.value.validated_at
      ? formatDate(certificate.value.validated_at)
      : "aguarda assinatura",
  },
  {
    label: "Cliente",
    value: certificate.value.customer || "Não definido",
    note: certificate.value.warehouse || "local não definido",
  },
  {
    label: "Código laboratorial",
    value: laboratoryReference.value,
    note: "rastreabilidade da amostra",
  },
  {
    label: "Criado em",
    value: formatDate(certificate.value.created_at),
    note: certificate.value.user?.name || "utilizador não identificado",
  },
]);

const certificateDetails = computed(() => [
  {
    label: "Número do certificado",
    value: certificate.value.code || "-",
    monospaced: true,
  },
  {
    label: "Cliente",
    value: certificate.value.customer || "-",
  },
  {
    label: "Armazém / local",
    value: certificate.value.warehouse || "-",
  },
  {
    label: "Referência laboratorial",
    value: laboratoryReference.value,
    monospaced: true,
  },
  {
    label: "Responsável pelo registo",
    value: certificate.value.user?.name || "-",
  },
  {
    label: "Última atualizacao",
    value: formatDate(certificate.value.updated_at),
  },
  {
    label: "Observações",
    value: certificate.value.obs || "Sem observações registadas.",
    wide: true,
  },
]);

const releaseChecks = computed(() => [
  {
    label: "Cliente associado",
    ready: Boolean(certificate.value.customer_id || certificate.value.customer),
  },
  {
    label: "Local de destino",
    ready: Boolean(certificate.value.warehouse_id || certificate.value.warehouse),
  },
  {
    label: "Código laboratorial",
    ready: Boolean(
      certificate.value.cl_id ||
      certificate.value.lab_code ||
      certificate.value.cl_code,
    ),
  },
  {
    label: "Validação final",
    ready: Boolean(certificate.value.validated_at),
  },
]);

const completedReleaseChecks = computed(
  () => releaseChecks.value.filter((check) => check.ready).length,
);

const additionalDocuments = computed(() => {
  const documents = certificate.value.links?.additional_documents;

  return Array.isArray(documents) ? documents : [];
});

const activityHistory = computed(() => {
  const history = [];

  if (certificate.value.created_at) {
    history.push({
      type: "created",
      title: "Certificado criado",
      description: "Dossier de qualidade registado no sistema.",
      timestamp: certificate.value.created_at,
      user: certificate.value.user?.name,
    });
  }

  if (certificate.value.validated_at) {
    history.push({
      type: "validated",
      title: "Certificado validado",
      description: "Documento revisto e libertado para distribuição.",
      timestamp: certificate.value.validated_at,
      user: certificate.value.validated_by_user || "Sistema",
    });
  }

  if (
    certificate.value.updated_at &&
    certificate.value.updated_at !== certificate.value.created_at
  ) {
    history.push({
      type: "updated",
      title: "Dados actualizados",
      description: "O contexto comercial ou laboratorial foi revisto.",
      timestamp: certificate.value.updated_at,
      user: "Sistema",
    });
  }

  return history.sort(
    (left, right) => new Date(right.timestamp) - new Date(left.timestamp),
  );
});

function formatDate(dateString) {
  if (!dateString) {
    return "-";
  }

  return new Date(dateString).toLocaleDateString("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatRelativeTime(dateString) {
  if (!dateString) {
    return "-";
  }

  const elapsed = Math.max(0, Date.now() - new Date(dateString).getTime());
  const minutes = Math.floor(elapsed / 60000);
  const hours = Math.floor(elapsed / 3600000);
  const days = Math.floor(elapsed / 86400000);

  if (minutes < 60) {
    return `${minutes} min atras`;
  }

  if (hours < 24) {
    return `${hours} h atras`;
  }

  if (days === 1) {
    return "Ontem";
  }

  if (days < 7) {
    return `${days} dias atras`;
  }

  return formatDate(dateString);
}

function getActivityIcon(type) {
  const iconMap = {
    created: DocumentTextIcon,
    validated: CheckBadgeIcon,
    updated: PencilIcon,
    viewed: EyeIcon,
    verified: DocumentMagnifyingGlassIcon,
  };

  return iconMap[type] || ClockIcon;
}

function approve() {
  router.get(
    route("qualitycertificates.getApprove", { id: certificate.value.id }),
    {},
    {
      preserveScroll: true,
      preserveState: false,
    },
  );
}

function editCertificate() {
  router.get(
    route("qualitycertificates.edit", { certificate: certificate.value.id }),
    {},
    {
      preserveScroll: true,
      preserveState: true,
    },
  );
}

function openRevisionHistory() {
  router.get(
    route("qualitycertificates.iso-revisions.index", {
      certificate: certificate.value.id,
    }),
    {},
    {
      preserveScroll: true,
    },
  );
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('qualitycertificates.index')"
            class="ds-table-action -ml-2 mb-3"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao arquivo
          </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Dossier de libertacao</p>
            <span class="ds-chip">
              <span
                :class="[
                  'lims-status-dot',
                  certificate.validated_at
                    ? 'lims-status-dot-release'
                    : 'lims-status-dot-hold',
                ]"
              />
              {{ certificate.validated_at ? "Validado" : "Pendente" }}
            </span>
          </div>
          <h1 class="ds-heading mt-2 break-words text-2xl">
            Certificado {{ certificate.code ? `#${certificate.code}` : "sem código" }}
          </h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm"> Registo final para revisão, assinatura, controlo de versões e distribuição do certificado de qualidade. </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row lg:justify-end">
          <a
            :href="pdfUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="ds-button ds-button-secondary"
          >
            <DocumentMagnifyingGlassIcon class="h-4 w-4" />
            Ver PDF
          </a>
          <button type="button" class="ds-button ds-button-secondary" @click="shareOpen = true">
            <EnvelopeIcon class="h-4 w-4" />
            Enviar
          </button>
          <button
            v-if="!certificate.validated_at && hasPermission('validate_quality_certificates')"
            type="button"
            class="ds-button ds-button-primary"
            @click="approve"
          >
            <CheckBadgeIcon class="h-4 w-4" />
            {{ $t("gestlab.general.labels.quality_certificates.approve_certificate") }}
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4 xl:divide-x xl:divide-[var(--ds-border)]">
        <div
          v-for="metric in certificateMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0 xl:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="ds-heading mt-2 truncate text-sm" :title="metric.value">
            {{ metric.value }}
          </dd>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-soft)]" :title="metric.note">
            {{ metric.note }}
          </p>
        </div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start">
      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Informação controlada</p>
          <h2 class="ds-heading mt-2 text-lg">Identificação e contexto</h2>
          <p class="ds-copy mt-1 text-sm"> Dados que acompanham o documento ao longo da cadeia de emissão. </p>
        </div>

        <dl class="grid sm:grid-cols-2">
          <div
            v-for="detail in certificateDetails"
            :key="detail.label"
            :class="[
              'border-b border-[var(--ds-border)] px-5 py-4 sm:px-6',
              detail.wide ? 'sm:col-span-2' : '',
            ]"
          >
            <dt class="ds-table-heading">{{ detail.label }}</dt>
            <dd
              :class="[
                'mt-2 break-words text-sm font-semibold text-[var(--ds-text)]',
                detail.monospaced ? 'font-mono' : '',
              ]"
            >
              {{ detail.value }}
            </dd>
          </div>
        </dl>

        <div class="px-5 py-5 sm:px-6">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="ds-kicker">Documentos</p>
              <h2 class="ds-heading mt-2 text-base">Pacote de emissão</h2>
            </div>
            <span class="ds-chip">
              {{ additionalDocuments.length + 1 }} ficheiro(s)
            </span>
          </div>

          <div class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <article class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
                  <DocumentIcon class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                  <h3 class="ds-heading truncate text-sm">Certificado de qualidade</h3>
                  <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                    PDF controlado gerado pelo sistema
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <a
                  :href="pdfUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="ds-table-action"
                >
                  <DocumentMagnifyingGlassIcon class="h-4 w-4" />
                  Ver
                </a>
                <a
                  :href="certificate.links?.pdf_path || pdfUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  download
                  class="ds-table-action"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Transferir
                </a>
              </div>
            </article>

            <article
              v-for="(document, index) in additionalDocuments"
              :key="document.id || document.url || index"
              class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
                  <DocumentTextIcon class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                  <h3 class="ds-heading truncate text-sm">
                    {{ document.name || `Documento ${index + 2}` }}
                  </h3>
                  <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                    {{ document.description || "Documento complementar" }}
                  </p>
                </div>
              </div>
              <a
                :href="document.url"
                target="_blank"
                rel="noopener noreferrer"
                class="ds-table-action"
              >
                <EyeIcon class="h-4 w-4" />
                Abrir
              </a>
            </article>
          </div>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="ds-kicker">Gate de qualidade</p>
                <h2 class="ds-heading mt-2 text-base">Prontidao para emissão</h2>
              </div>
              <span class="font-mono text-sm font-bold text-[var(--ds-text)]">
                {{ completedReleaseChecks }}/{{ releaseChecks.length }}
              </span>
            </div>
          </div>
          <ul class="divide-y divide-[var(--ds-border)]">
            <li
              v-for="check in releaseChecks"
              :key="check.label"
              class="flex items-center justify-between gap-3 px-5 py-3"
            >
              <span class="text-sm font-semibold text-[var(--ds-text-muted)]">
                {{ check.label }}
              </span>
              <span class="inline-flex items-center gap-2 text-xs font-bold text-[var(--ds-text)]">
                <span
                  :class="[
                    'lims-status-dot',
                    check.ready ? 'lims-status-dot-release' : 'lims-status-dot-hold',
                  ]"
                />
                {{ check.ready ? "Concluído" : "Pendente" }}
              </span>
            </li>
          </ul>
        </section>

        <section class="ds-card p-5">
          <p class="ds-kicker">Comandos</p>
          <h2 class="ds-heading mt-2 text-base">Acções do dossier</h2>
          <div class="mt-4 grid gap-2">
            <button
              v-if="hasPermission('edit_qualitycertificate') && !certificate.validated_at"
              type="button"
              class="ds-button ds-button-secondary justify-start"
              @click="editCertificate"
            >
              <PencilIcon class="h-4 w-4" />
              {{ $t("gestlab.general.buttons.edit") }}
            </button>
            <button
              type="button"
              class="ds-button ds-button-secondary justify-start"
              @click="openRevisionHistory"
            >
              <ArrowPathRoundedSquareIcon class="h-4 w-4" />
              {{ $t("gestlab.general.labels.quality_certificates.generate_new_version") }}
            </button>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Rastreabilidade</p>
            <h2 class="ds-heading mt-2 text-base">
              {{ $t("gestlab.general.labels.quality_certificates.activity_history") }}
            </h2>
          </div>

          <ol v-if="activityHistory.length" class="px-5 py-5">
            <li
              v-for="(activity, index) in activityHistory"
              :key="`${activity.type}-${activity.timestamp}`"
              class="relative flex gap-3 pb-6 last:pb-0"
            >
              <div class="relative flex w-8 shrink-0 justify-center">
                <span
                  v-if="index !== activityHistory.length - 1"
                  class="absolute bottom-0 top-8 w-px bg-[var(--ds-border-strong)]"
                />
                <span class="grid h-8 w-8 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
                  <component :is="getActivityIcon(activity.type)" class="h-4 w-4" />
                </span>
              </div>
              <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                  <h3 class="ds-heading text-sm">{{ activity.title }}</h3>
                  <time class="text-xs font-semibold text-[var(--ds-text-soft)]">
                    {{ formatRelativeTime(activity.timestamp) }}
                  </time>
                </div>
                <p class="ds-copy mt-1 text-xs">{{ activity.description }}</p>
                <p v-if="activity.user" class="mt-2 flex items-center gap-1.5 text-xs font-semibold text-[var(--ds-text-muted)]">
                  <UserIcon class="h-3.5 w-3.5" />
                  {{ activity.user }}
                </p>
              </div>
            </li>
          </ol>

          <div v-else class="ds-empty-state m-5 p-5 text-center">
            <ClockIcon class="mx-auto h-5 w-5 text-[var(--ds-text-soft)]" />
            <p class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]">
              Sem eventos registados.
            </p>
          </div>
        </section>
      </aside>
    </div>

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
