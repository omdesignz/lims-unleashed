<script setup>
import { usePermission } from "@/Composables/usePermissions";
import DocumentShareModal from '@/Components/documents/DocumentShareModal.vue';
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  BanknotesIcon,
  BuildingOfficeIcon,
  CheckBadgeIcon,
  CubeIcon,
  DocumentCheckIcon,
  EnvelopeIcon,
  GlobeAltIcon,
  MapPinIcon,
  PaperClipIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  TruckIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref } from "vue";

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ["import", "export"].includes(value) },
  record: { type: Object, required: true },
});

const { hasPermission } = usePermission();
const certificate = computed(() => props.record?.data || props.record || {});
const shareOpen = ref(false);
const isImport = computed(() => props.kind === "import");
const config = computed(() => isImport.value
  ? {
      title: "Certificado de importação",
      kicker: "Controlo de entrada transfronteiriça",
      routePrefix: "importcertificates",
      permissionKey: "import_certificates",
      icon: ShieldCheckIcon,
      counterparty: certificate.value.importer,
      counterpartyLabel: "Importador",
    }
  : {
      title: "Certificado de exportação",
      kicker: "Controlo de saída transfronteiriça",
      routePrefix: "exportcertificates",
      permissionKey: "export_certificates",
      icon: GlobeAltIcon,
      counterparty: certificate.value.exporter,
      counterpartyLabel: "Exportador",
    });

const items = computed(() => certificate.value.items || []);
const totalQuantity = computed(() => items.value.reduce((total, item) => total + (Number.parseFloat(item.qty) || 0), 0));
const importCosts = computed(() => [
  { label: "Frete", value: certificate.value.cost_freight },
  { label: "Seguro", value: certificate.value.cost_insurance },
  { label: "Valor da mercadoria", value: certificate.value.cost_final },
  { label: "IVA", value: certificate.value.vat_cost },
]);
const totalCost = computed(() => importCosts.value.reduce((total, item) => total + (Number.parseFloat(item.value) || 0), 0));
const status = computed(() => {
  if (certificate.value.deleted) {
    return { label: "Arquivado", className: "ds-badge-warning" };
  }

  if (certificate.value.invoiced) {
    return { label: "Facturado", className: "ds-badge-success" };
  }

  return { label: "Controlado", className: "ds-badge-info" };
});
const canEdit = computed(() => !certificate.value.deleted
  && !certificate.value.invoiced
  && hasPermission(`edit_${config.value.permissionKey}`));
const routeFacts = computed(() => isImport.value
  ? [
      { label: "Importador", value: certificate.value.importer, icon: BuildingOfficeIcon },
      { label: "Armazém do importador", value: certificate.value.importer_warehouse, icon: MapPinIcon },
      { label: "Exportador", value: certificate.value.exporter, icon: BuildingOfficeIcon },
      { label: "Armazém do exportador", value: certificate.value.exporter_warehouse, icon: MapPinIcon },
      { label: "Transporte", value: certificate.value.trans_type, icon: TruckIcon },
      { label: "País de destino", value: certificate.value.destination_country, icon: GlobeAltIcon },
      { label: "Porto de saída", value: certificate.value.port_exit, icon: MapPinIcon },
      { label: "Porto de entrada", value: certificate.value.port_entry, icon: MapPinIcon },
    ]
  : [
      { label: "Exportador", value: certificate.value.exporter, icon: BuildingOfficeIcon },
      { label: "Armazém do exportador", value: certificate.value.exporter_warehouse, icon: MapPinIcon },
      { label: "Transporte", value: certificate.value.trans_type, icon: TruckIcon },
      { label: "País de origem", value: certificate.value.country_origin, icon: GlobeAltIcon },
      { label: "Cidade de origem", value: certificate.value.origin_city, icon: MapPinIcon },
      { label: "País de destino", value: certificate.value.country_destination, icon: GlobeAltIcon },
      { label: "Cidade de destino", value: certificate.value.destination_city, icon: MapPinIcon },
      { label: "Local de expedição", value: certificate.value.expedition_location, icon: MapPinIcon },
    ]);

function valueOrFallback(value) {
  return value || "Não registado";
}

function formatDate(value) {
  if (!value) {
    return "Não registada";
  }

  const date = new Date(value.length === 10 ? `${value}T00:00:00` : value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("pt-PT", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(date);
}

function formatQuantity(value) {
  return new Intl.NumberFormat("pt-PT", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.parseFloat(value) || 0);
}

function formatMoney(value) {
  return `${formatQuantity(value)} ${certificate.value.currency || "AOA"}`;
}

function fileName(path) {
  return path?.split("/").pop() || "Documento anexo";
}

function downloadPdf() {
  window.open(route(`${config.value.routePrefix}.getPDF`, { id: certificate.value.id }), "_blank", "noopener");
}

function downloadAttachment() {
  if (certificate.value.file) {
    window.open(certificate.value.file, "_blank", "noopener");
  }
}

function openInvoice() {
  if (certificate.value.invoice_id) {
    router.get(route("invoices.show", { id: certificate.value.invoice_id }));
    return;
  }

  router.get(route(`${config.value.routePrefix}.getIssueInvoiceModal`), { id: certificate.value.id });
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <Link :href="route(`${config.routePrefix}.index`)" class="ds-button ds-button-ghost px-0">
        <ArrowLeftIcon class="h-4 w-4" />
        Voltar ao registo
      </Link>

      <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="config.icon" class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">{{ config.kicker }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <h1 class="ds-heading text-2xl">{{ certificate.cert_no || config.title }}</h1>
              <span class="ds-badge" :class="status.className">
                <CheckBadgeIcon class="h-3.5 w-3.5" />
                {{ status.label }}
              </span>
            </div>
            <p class="ds-copy mt-1 max-w-3xl text-sm">
              {{ config.title }} · {{ config.counterpartyLabel }}: {{ config.counterparty || "não identificado" }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="downloadPdf">
            <ArrowDownTrayIcon class="h-4 w-4" />
            PDF
          </button>
          <button type="button" class="ds-button ds-button-secondary" @click="shareOpen = true">
            <EnvelopeIcon class="h-4 w-4" />
            Enviar
          </button>
          <Link v-if="canEdit" :href="route(`${config.routePrefix}.edit`, certificate.id)" class="ds-button ds-button-primary">
            <PencilSquareIcon class="h-4 w-4" />
            Editar
          </Link>
        </div>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-r xl:border-b-0">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Data do certificado</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(certificate.date) }}</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 xl:border-b-0 xl:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Produtos</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ items.length }} linhas</dd>
        </div>
        <div class="border-b border-[var(--ds-border)] px-4 py-3 sm:border-b-0 sm:border-r">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Quantidade total</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatQuantity(totalQuantity) }} un.</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Facturação</dt>
          <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ certificate.invoiced ? "Associada" : "Pendente" }}</dd>
        </div>
      </dl>
    </section>

    <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_19rem]">
      <main class="min-w-0 space-y-6">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
              <TruckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <div>
                <h2 class="text-sm font-bold text-[var(--ds-text)]">Partes e percurso</h2>
                <p class="ds-copy mt-1 text-xs">Identidade comercial e cadeia logística declarada.</p>
              </div>
            </div>
          </header>
          <dl class="grid sm:grid-cols-2">
            <div v-for="(fact, index) in routeFacts" :key="fact.label" class="flex min-w-0 gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6" :class="index % 2 === 0 ? 'sm:border-r' : ''">
              <component :is="fact.icon" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              <div class="min-w-0">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ fact.label }}</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-[var(--ds-text)]">{{ valueOrFallback(fact.value) }}</dd>
              </div>
            </div>
            <div v-if="!isImport" class="flex min-w-0 gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:border-r sm:px-6">
              <DocumentCheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              <div class="min-w-0">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Data de expedição</dt>
                <dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ formatDate(certificate.expedition_date) }}</dd>
              </div>
            </div>
          </dl>
        </section>

        <section class="ds-panel min-w-0 overflow-hidden">
          <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="flex items-center gap-3">
              <CubeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <div>
                <h2 class="text-sm font-bold text-[var(--ds-text)]">Produtos certificados</h2>
                <p class="ds-copy mt-1 text-xs">{{ items.length }} linhas · {{ formatQuantity(totalQuantity) }} unidades</p>
              </div>
            </div>
            <span class="ds-badge ds-badge-neutral">Registo controlado</span>
          </header>

          <div v-if="items.length" class="overflow-x-auto">
            <DataTable class="ds-table min-w-full">
              <thead class="ds-table-head">
                <tr>
                  <th class="ds-table-header px-5 py-3 text-left sm:px-6">Produto</th>
                  <th class="ds-table-header px-4 py-3 text-right">Quantidade</th>
                  <template v-if="isImport">
                    <th class="ds-table-header px-4 py-3 text-left">Origem</th>
                    <th class="ds-table-header px-4 py-3 text-left">Validade</th>
                    <th class="ds-table-header px-4 py-3 text-left">Lote</th>
                    <th class="ds-table-header px-5 py-3 text-left sm:px-6">BL</th>
                  </template>
                </tr>
              </thead>
              <tbody class="divide-y divide-[var(--ds-border)]">
                <tr v-for="(item, index) in items" :key="item.id || index" class="ds-table-row">
                  <td class="ds-table-cell px-5 py-3 font-semibold text-[var(--ds-text)] sm:px-6">{{ valueOrFallback(item.product) }}</td>
                  <td class="ds-table-cell whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ formatQuantity(item.qty) }}</td>
                  <template v-if="isImport">
                    <td class="ds-table-cell px-4 py-3">{{ valueOrFallback(item.origin) }}</td>
                    <td class="ds-table-cell whitespace-nowrap px-4 py-3">{{ formatDate(item.validity) }}</td>
                    <td class="ds-table-cell px-4 py-3">{{ valueOrFallback(item.lot) }}</td>
                    <td class="ds-table-cell px-5 py-3 sm:px-6">{{ valueOrFallback(item.bl_no) }}</td>
                  </template>
                </tr>
              </tbody>
              <tfoot class="border-t border-[var(--ds-border-strong)] bg-[var(--ds-panel-subtle)]">
                <tr>
                  <th class="px-5 py-3 text-left text-xs font-bold uppercase text-[var(--ds-text-soft)] sm:px-6">Total</th>
                  <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ formatQuantity(totalQuantity) }}</td>
                  <td v-if="isImport" colspan="4"></td>
                </tr>
              </tfoot>
            </DataTable>
          </div>
          <div v-else class="px-5 py-10 text-center sm:px-6">
            <CubeIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Sem produtos registados</p>
          </div>
        </section>

        <section v-if="isImport" class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
              <BanknotesIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <h2 class="text-sm font-bold text-[var(--ds-text)]">Composição do valor declarado</h2>
            </div>
          </header>
          <dl class="grid sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="cost in importCosts" :key="cost.label" class="border-b border-r border-[var(--ds-border)] px-5 py-4 last:border-r-0 sm:px-6 xl:border-b-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ cost.label }}</dt>
              <dd class="mt-2 text-sm font-bold tabular-nums text-[var(--ds-text)]">{{ formatMoney(cost.value) }}</dd>
            </div>
          </dl>
          <div class="flex items-center justify-between gap-4 border-t border-[var(--ds-border-strong)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <span class="text-sm font-bold text-[var(--ds-text)]">Total declarado</span>
            <span class="text-base font-bold tabular-nums text-[var(--ds-text)]">{{ formatMoney(totalCost) }}</span>
          </div>
        </section>

        <section v-if="certificate.obs || certificate.file" class="grid gap-6 lg:grid-cols-2">
          <article v-if="certificate.obs" class="ds-panel overflow-hidden p-5 sm:p-6">
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Observações</h2>
            <p class="ds-copy mt-3 whitespace-pre-line text-sm">{{ certificate.obs }}</p>
          </article>
          <article v-if="certificate.file" class="ds-panel overflow-hidden p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
              <div class="flex min-w-0 items-start gap-3">
                <PaperClipIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
                <div class="min-w-0">
                  <h2 class="text-sm font-bold text-[var(--ds-text)]">Evidência anexada</h2>
                  <p class="ds-copy mt-1 truncate text-sm">{{ fileName(certificate.file) }}</p>
                </div>
              </div>
              <button type="button" class="ds-icon-button shrink-0" title="Abrir documento" @click="downloadAttachment">
                <ArrowDownTrayIcon class="h-4 w-4" />
              </button>
            </div>
          </article>
        </section>
      </main>

      <aside class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4">
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Ciclo documental</h2>
          </header>
          <div class="space-y-3 p-5">
            <button type="button" class="ds-button ds-button-secondary w-full justify-start" @click="downloadPdf">
              <ArrowDownTrayIcon class="h-4 w-4" />
              Descarregar PDF
            </button>
            <button type="button" class="ds-button w-full justify-start" :class="certificate.invoice_id ? 'ds-button-secondary' : 'ds-button-primary'" @click="openInvoice">
              <BanknotesIcon class="h-4 w-4" />
              {{ certificate.invoice_id ? "Abrir factura" : "Emitir factura" }}
            </button>
            <button v-if="certificate.file" type="button" class="ds-button ds-button-secondary w-full justify-start" @click="downloadAttachment">
              <PaperClipIcon class="h-4 w-4" />
              Abrir anexo
            </button>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4">
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Responsabilidade</h2>
          </header>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div class="flex gap-3 px-5 py-4">
              <UserCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              <div class="min-w-0">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Registado por</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-[var(--ds-text)]">{{ valueOrFallback(certificate.user) }}</dd>
              </div>
            </div>
            <div class="flex gap-3 px-5 py-4">
              <CheckBadgeIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              <div class="min-w-0">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Responsável autorizado</dt>
                <dd class="mt-1 break-words text-sm font-semibold text-[var(--ds-text)]">{{ valueOrFallback(certificate.authorized_personnel) }}</dd>
              </div>
            </div>
            <div class="flex gap-3 px-5 py-4">
              <DocumentCheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
              <div class="min-w-0">
                <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Referência</dt>
                <dd class="mt-1 break-all font-mono text-sm font-semibold text-[var(--ds-text)]">{{ certificate.cert_no || `#${certificate.id}` }}</dd>
              </div>
            </div>
          </dl>
        </section>
      </aside>
    </div>

    <DocumentShareModal
      :open="shareOpen"
      :document-type="isImport ? 'import_certificate' : 'export_certificate'"
      :document-id="certificate.id"
      :document-label="config.title"
      :document-number="certificate.cert_no"
      :default-recipients="certificate.recipient_emails || []"
      @close="shareOpen = false"
    />
  </div>
</template>
