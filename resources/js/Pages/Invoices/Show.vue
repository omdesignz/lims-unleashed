<template>
  <div class="pl-page" data-template="dossier">
    <Head :title="`Factura${invoice.inv_no ? ' · ' + invoice.inv_no : ''}`" />
    <PageHeader
      :trail="[{ title: 'Facturas', url: route('invoices.index') }, { title: invoice.inv_no || 'Factura' }]"
      :title="invoice.inv_no || $t('gestlab.general.labels.invoices.page_view_title')"
      :lede="`${invoice.customer || 'Cliente por identificar'} · emitida a ${formatDate(invoice.created_at)}`"
    >
      <template #badges>
        <StatusChip :tone="statusTone">{{ formatStatus(invoice.status) }}</StatusChip>
      </template>
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="downloadPDF">
          <DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.invoices.download_pdf') }}
        </button>
        <button type="button" class="ds-button ds-button-secondary" @click="sendEmail">
          <EnvelopeIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.invoices.send_email') }}
        </button>
        <button type="button" class="ds-button ds-button-quiet" @click="duplicateInvoice">
          <DocumentDuplicateIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.invoices.duplicate') }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells mb-10">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.invoices.total_amount') }} · AOA</dt>
        <dd class="pl-cell-value">{{ formatCurrency(invoice.total) }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.invoices.amount_paid') }} · AOA</dt>
        <dd class="pl-cell-value">{{ formatCurrency(paid) }}</dd>
      </div>
      <div class="pl-cell" :class="{ 'pl-cell-bad': invoice.status === 'overdue' }">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.invoices.amount_due') }} · AOA</dt>
        <dd class="pl-cell-value">{{ formatCurrency(invoice.amount_due) }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.invoices.payment_progress') }}</dt>
        <dd class="grid gap-3">
          <span class="pl-cell-value">{{ paidShare }}%</span>
          <span class="pl-bar" aria-hidden="true"><i :style="{ width: `${paidShare}%` }" /></span>
        </dd>
      </div>
    </dl>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-9">
        <section class="pl-panel" aria-labelledby="invoice-items">
          <header class="pl-panel-head">
            <h2 id="invoice-items" class="pl-k">{{ $t('gestlab.general.labels.invoices.items') }}</h2>
            <span class="pl-num text-[var(--pl-muted)]">{{ items.length }}</span>
          </header>

          <div v-if="!items.length" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
            <span class="pl-k">{{ $t('gestlab.general.labels.invoices.no_items') }}</span>
            <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.invoices.no_items_description') }}</p>
          </div>

          <div v-else class="overflow-x-auto">
            <DataTable>
              <thead>
                <tr>
                  <th scope="col">{{ $t('gestlab.general.labels.invoices.item_description') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.invoices.qty') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.invoices.unit_price') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.invoices.discount') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.invoices.tax') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.invoices.line_total') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in items" :key="index">
                  <td class="py-3">
                    <p class="font-medium">{{ item.item_description }}</p>
                    <p v-if="item.obs" class="mt-0.5 text-[13px] text-[var(--pl-muted)]">{{ item.obs }}</p>
                    <p v-if="item.exemption_code || item.unit" class="pl-k pl-faint mt-1.5">
                      <template v-if="item.unit">{{ item.unit.code }}</template>
                      <template v-if="item.exemption_code"><template v-if="item.unit"> · </template>{{ $t('gestlab.general.labels.invoices.exemption') }} {{ item.exemption_code }}</template>
                    </p>
                  </td>
                  <td class="pl-num text-right">{{ item.qty }}</td>
                  <td class="pl-num text-right">{{ formatCurrency(item.unit_price) }}</td>
                  <td class="pl-num text-right">
                    <template v-if="item.discount_amount > 0">−{{ formatCurrency(item.discount_amount) }}<span v-if="item.discount_percentage" class="text-[var(--pl-faint)]"> · {{ item.discount_percentage }}%</span></template>
                    <span v-else class="text-[var(--pl-faint)]">—</span>
                  </td>
                  <td class="pl-num text-right">
                    <template v-if="item.tax_amount > 0">{{ formatCurrency(item.tax_amount) }}<span class="text-[var(--pl-faint)]"> · {{ item.tax_percentage }}%</span></template>
                    <span v-else class="text-[var(--pl-faint)]">—</span>
                  </td>
                  <td class="pl-num text-right font-medium">{{ formatCurrency(item.total) }}</td>
                </tr>
              </tbody>
            </DataTable>
          </div>

          <dl v-if="items.length" class="pl-facts border-t border-[var(--pl-line-strong)] sm:ml-auto sm:max-w-md sm:border-l sm:border-l-[var(--pl-line)]">
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.subtotal') }}</dt><dd class="pl-num text-right">{{ formatCurrency(invoice.sub_total) }}</dd></div>
            <div v-if="invoice.discount > 0" class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.discount_total') }}</dt><dd class="pl-num text-right">−{{ formatCurrency(invoice.discount) }}</dd></div>
            <div v-if="invoice.tax > 0" class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.tax_total') }}</dt><dd class="pl-num text-right">{{ formatCurrency(invoice.tax) }}</dd></div>
            <div class="pl-fact"><dt class="text-[var(--pl-fg)]">{{ $t('gestlab.general.labels.invoices.total') }} · AOA</dt><dd class="pl-num text-right text-base font-semibold">{{ formatCurrency(invoice.total) }}</dd></div>
          </dl>
        </section>

        <section v-if="invoice.obs" class="pl-panel" aria-labelledby="invoice-notes">
          <header class="pl-panel-head"><h2 id="invoice-notes" class="pl-k">{{ $t('gestlab.general.labels.invoices.obs') }}</h2></header>
          <p class="whitespace-pre-line p-4 text-sm leading-relaxed">{{ invoice.obs }}</p>
        </section>
      </div>

      <aside class="grid gap-9" aria-label="Dados do documento">
        <section class="pl-panel" aria-labelledby="invoice-details">
          <header class="pl-panel-head"><h2 id="invoice-details" class="pl-k">{{ $t('gestlab.general.labels.invoices.invoice_details') }}</h2></header>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.inv_no') }}</dt><dd class="pl-num">{{ invoice.inv_no }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.type_id') }}</dt><dd>{{ invoice.invoice_category || '—' }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.created_at') }}</dt><dd>{{ formatDate(invoice.created_at) }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.internal_ref') }}</dt><dd>{{ invoice.internal_ref || $t('gestlab.general.labels.invoices.no_reference') }}</dd></div>
            <div v-if="invoice.lab_code" class="pl-fact">
              <dt>{{ $t('gestlab.general.labels.invoices.labcode_id') }}</dt>
              <dd><span class="pl-num">{{ invoice.lab_code.code }}</span><span v-if="invoice.assign_lab_code" class="mt-1 block text-[13px] text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.invoices.assigned_to_collection') }}</span></dd>
            </div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.invoices.pricing_mode') }}</dt><dd>{{ invoice.use_matrix_price ? $t('gestlab.general.labels.invoices.matrix_pricing') : $t('gestlab.general.labels.invoices.parameter_pricing') }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="invoice-customer">
          <header class="pl-panel-head"><h2 id="invoice-customer" class="pl-k">{{ $t('gestlab.general.labels.invoices.customer_info') }}</h2></header>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Cliente</dt><dd class="font-medium">{{ invoice.customer || '—' }}</dd></div>
            <div v-if="invoice.customer?.email" class="pl-fact"><dt>{{ $t('gestlab.general.labels.email') }}</dt><dd>{{ invoice.customer.email }}</dd></div>
            <div v-if="invoice.customer?.phone" class="pl-fact"><dt>{{ $t('gestlab.general.labels.phone') }}</dt><dd class="pl-num">{{ invoice.customer.phone }}</dd></div>
            <div v-if="invoice.customer?.vat_number" class="pl-fact"><dt>{{ $t('gestlab.general.labels.vat') }}</dt><dd class="pl-num">{{ invoice.customer.vat_number }}</dd></div>
            <div class="pl-fact">
              <dt>{{ $t('gestlab.general.labels.invoices.delivery_address') }}</dt>
              <dd>
                {{ invoice.warehouse || '—' }}
                <span v-if="invoice.warehouse?.city || invoice.warehouse?.postal_code || invoice.warehouse?.country" class="mt-1 block text-[13px] text-[var(--pl-muted)]">
                  {{ [invoice.warehouse.city, invoice.warehouse.postal_code, invoice.warehouse.country].filter(Boolean).join(', ') }}
                </span>
              </dd>
            </div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="invoice-history">
          <header class="pl-panel-head"><h2 id="invoice-history" class="pl-k">{{ $t('gestlab.general.labels.invoices.audit_trail') }}</h2></header>
          <ol>
            <li class="pl-row">
              <span><span class="block font-medium">{{ $t('gestlab.general.labels.invoices.created_by') }}</span><span class="text-[13px] text-[var(--pl-muted)]">{{ invoice.user || '—' }}</span></span>
              <time class="pl-k pl-faint">{{ formatDateTime(invoice.created_at) }}</time>
            </li>
            <li v-if="invoice.updated_at !== invoice.created_at" class="pl-row">
              <span class="font-medium">{{ $t('gestlab.general.labels.invoices.last_updated') }}</span>
              <time class="pl-k pl-faint">{{ formatDateTime(invoice.updated_at) }}</time>
            </li>
            <li v-for="payment in invoice.payments ?? []" :key="payment.id" class="pl-row">
              <span><span class="block font-medium">{{ $t('gestlab.general.labels.invoices.payment_received') }}</span><span class="pl-num text-[13px] text-[var(--pl-muted)]">AOA {{ formatCurrency(payment.amount) }} · {{ payment.method || '—' }}</span></span>
              <time class="pl-k pl-faint">{{ formatDateTime(payment.created_at) }}</time>
            </li>
          </ol>
        </section>
      </aside>
    </div>

    <NextStepBar>
      {{ nextStep }}
      <template #actions>
        <button v-if="canEdit" type="button" class="ds-button ds-button-secondary" @click="editInvoice">
          <PencilIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.edit') }}
        </button>
        <button v-if="invoice.amount_due > 0" type="button" class="ds-button ds-button-primary" @click="recordPayment">
          {{ $t('gestlab.general.labels.invoices.record_payment') }}
        </button>
      </template>
    </NextStepBar>

    <DocumentShareModal
      :open="shareOpen"
      document-type="invoice"
      :document-id="props.record.data?.id"
      document-label="Factura"
      :document-number="props.record.data?.inv_no"
      :default-recipients="defaultRecipients"
      @close="shareOpen = false"
    />
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import DocumentShareModal from '@/Components/documents/DocumentShareModal.vue';
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import StatusChip from "@/Components/plano/StatusChip.vue";
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import {
  Copy as DocumentDuplicateIcon,
  FileDown as DocumentArrowDownIcon,
  Mail as EnvelopeIcon,
  Pencil as PencilIcon,
} from "@lucide/vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object
});

/**
 * The invoice dossier (Plano): what was billed, what has been paid, and the one
 * thing left to do — record the payment still owed.
 */
const invoice = computed(() => props.record.data ?? {});
const items = computed(() => invoice.value.items ?? []);
const paid = computed(() => Number(invoice.value.total || 0) - Number(invoice.value.amount_due || 0));
const paidShare = computed(() => {
  const total = Number(invoice.value.total || 0);

  return total > 0 ? Math.min(100, Math.max(0, Math.round((paid.value / total) * 100))) : 0;
});
const statusTone = computed(() => ({
  paid: 'ok',
  pending: 'wait',
  overdue: 'bad',
  cancelled: 'done',
}[invoice.value.status] ?? 'neutral'));
const nextStep = computed(() => {
  if (Number(invoice.value.amount_due) > 0) {
    return `Faltam AOA ${formatCurrency(invoice.value.amount_due)} por liquidar${paid.value > 0 ? `; AOA ${formatCurrency(paid.value)} já recebidos` : ''}.`;
  }

  return invoice.value.status === 'cancelled' ? 'Documento cancelado. Não há acções pendentes.' : 'Factura liquidada. Não há acções pendentes.';
});

const shareOpen = ref(false);
const defaultRecipients = computed(() => [
  props.record.data?.warehouse_id?.invoicing_email,
  props.record.data?.warehouse_id?.email,
  props.record.data?.warehouse_id?.focal_point_email,
].filter(Boolean));

// Formatting functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatStatus = (status) => {
  const statusMap = {
    'draft': 'Rascunho',
    'pending': 'Pendente',
    'paid': 'Pago',
    'overdue': 'Vencido',
    'cancelled': 'Cancelado'
  };
  return statusMap[status] || status;
};

// Actions
const downloadPDF = () => {
  // window.open(`/invoices/${props.record.data?.id}/pdf`, '_blank');
  window.open(route('invoices.getPDF', { id: props.record.data?.id}), '_blank');
};

const sendEmail = () => {
  shareOpen.value = true;
};

const recordPayment = () => {
  router.visit(`/invoices/${props.record.data?.id}/payments/create`);
};

const duplicateInvoice = () => {
  router.visit(`/invoices/${props.record.data?.id}/duplicate`);
};

const editInvoice = () => {
  router.visit(`/invoices/${props.record.data?.id}/edit`);
};

// Check if user can edit (based on status)
const canEdit = computed(() => {
  return ['draft', 'pending'].includes(props.record.data?.status);
});
</script>
