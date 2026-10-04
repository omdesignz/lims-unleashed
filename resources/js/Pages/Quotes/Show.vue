<template>
  <div class="pl-page" data-template="dossier">
    <Head :title="`Proforma${props.record.data?.quote_no ? ' · ' + props.record.data.quote_no : ''}`" />
    <PageHeader
      :trail="[{ title: 'Proformas', url: route('quotes.index') }, { title: quote.quote_no || 'Proforma' }]"
      :title="quote.quote_no || $t('gestlab.general.labels.quotes.page_view_title')"
      :lede="`${quote.customer || 'Cliente por identificar'} · válida até ${formatDate(quote.due_date)}`"
    >
      <template #badges>
        <StatusChip :tone="isBilled ? 'ok' : 'neutral'">{{ quoteBillingLabel(props.record.data ?? {}) }}</StatusChip>
      </template>
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="downloadPDF">
          <DocumentArrowDownIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.quotes.download_pdf') }}
        </button>
        <button type="button" class="ds-button ds-button-secondary" @click="sendEmail">
          <EnvelopeIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.quotes.send_email') }}
        </button>
        <button type="button" class="ds-button ds-button-quiet" @click="duplicateQuote">
          <DocumentDuplicateIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.quotes.duplicate') }}
        </button>
      </template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-9">
        <section class="pl-panel" aria-labelledby="quote-items">
          <header class="pl-panel-head">
            <h2 id="quote-items" class="pl-k">{{ $t('gestlab.general.labels.quotes.items') }}</h2>
            <span class="pl-num text-[var(--pl-muted)]">{{ items.length }}</span>
          </header>

          <div v-if="!items.length" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
            <span class="pl-k">{{ $t('gestlab.general.labels.quotes.no_items') }}</span>
            <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.quotes.no_items_description') }}</p>
          </div>

          <div v-else class="overflow-x-auto">
            <DataTable>
              <thead>
                <tr>
                  <th scope="col">{{ $t('gestlab.general.labels.quotes.item_id') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.quotes.qty') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.quotes.unit_price') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.quotes.discount') }}</th>
                  <th scope="col" class="text-right">{{ $t('gestlab.general.labels.quotes.total') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in items" :key="index">
                  <td class="py-3">
                    <p class="font-medium">{{ item.item_description }}</p>
                    <p v-if="item.obs" class="mt-0.5 text-[13px] text-[var(--pl-muted)]">{{ item.obs }}</p>
                    <p v-if="item.exemption_code" class="pl-k pl-faint mt-1.5">Isenção {{ item.exemption_code }}</p>
                  </td>
                  <td class="pl-num text-right">{{ item.qty }}<span v-if="item.unit" class="text-[var(--pl-faint)]"> {{ item.unit.code }}</span></td>
                  <td class="pl-num text-right">{{ formatCurrency(item.extra_data?.agreed_unit_price ?? (Number(item.unit_price) + Number(item.discount_amount))) }}</td>
                  <td class="pl-num text-right">
                    <template v-if="item.discount_amount > 0">−{{ formatCurrency(item.discount_amount) }}<span v-if="item.discount_percentage" class="text-[var(--pl-faint)]"> · {{ item.discount_percentage }}%</span></template>
                    <span v-else class="text-[var(--pl-faint)]">—</span>
                  </td>
                  <td class="pl-num text-right font-medium">{{ formatCurrency(item.total) }}</td>
                </tr>
              </tbody>
            </DataTable>
          </div>

          <dl v-if="items.length" class="pl-facts border-t border-[var(--pl-line-strong)] sm:ml-auto sm:max-w-md sm:border-l sm:border-l-[var(--pl-line)]">
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.subtotal') }}</dt><dd class="pl-num text-right">{{ formatCurrency(Number(props.record.data?.sub_total || 0) + Number(props.record.data?.discount || 0)) }}</dd></div>
            <div v-if="quote.discount > 0" class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.discount_total') }}</dt><dd class="pl-num text-right">−{{ formatCurrency(quote.discount) }}</dd></div>
            <div v-if="quote.tax > 0" class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.tax_total') }}</dt><dd class="pl-num text-right">{{ formatCurrency(props.record.data?.tax) }}</dd></div>
            <div class="pl-fact"><dt class="text-[var(--pl-fg)]">{{ $t('gestlab.general.labels.quotes.total') }} · AOA</dt><dd class="pl-num text-right text-base font-semibold">{{ formatCurrency(quote.total) }}</dd></div>
          </dl>
        </section>

        <section v-if="quote.obs" class="pl-panel" aria-labelledby="quote-notes">
          <header class="pl-panel-head"><h2 id="quote-notes" class="pl-k">{{ $t('gestlab.general.labels.quotes.obs') }}</h2></header>
          <p class="whitespace-pre-line p-4 text-sm leading-relaxed">{{ quote.obs }}</p>
        </section>
      </div>

      <aside class="grid gap-9" aria-label="Dados do documento">
        <section class="pl-panel" aria-labelledby="quote-details">
          <header class="pl-panel-head"><h2 id="quote-details" class="pl-k">Documento</h2></header>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.quote_no') }}</dt><dd class="pl-num">{{ quote.quote_no }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.current_status') }}</dt><dd>{{ quoteBillingLabel(props.record.data ?? {}) }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.created_on') }}</dt><dd>{{ formatDate(quote.created_at) }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.due_date') }}</dt><dd>{{ formatDate(quote.due_date) }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.created_by') }}</dt><dd>{{ quote.user || '—' }}</dd></div>
            <div v-if="quote.updated_at !== quote.created_at" class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.last_updated') }}</dt><dd>{{ formatDate(quote.updated_at) }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.internal_ref') }}</dt><dd>{{ quote.internal_ref || $t('gestlab.general.labels.quotes.no_reference') }}</dd></div>
            <div v-if="quote.lab_code" class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.labcode_id') }}</dt><dd class="pl-num">{{ quote.lab_code.code }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.pricing_mode') }}</dt><dd>{{ quote.use_matrix_price ? $t('gestlab.general.labels.quotes.matrix_pricing') : $t('gestlab.general.labels.quotes.parameter_pricing') }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="quote-customer">
          <header class="pl-panel-head"><h2 id="quote-customer" class="pl-k">{{ $t('gestlab.general.labels.quotes.customer_info') }}</h2></header>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.quotes.customer_id') }}</dt><dd class="font-medium">{{ quote.customer || '—' }}</dd></div>
            <div v-if="quote.customer?.email" class="pl-fact"><dt>E-mail</dt><dd>{{ quote.customer.email }}</dd></div>
            <div v-if="quote.customer?.phone" class="pl-fact"><dt>Telefone</dt><dd class="pl-num">{{ quote.customer.phone }}</dd></div>
            <div class="pl-fact">
              <dt>{{ $t('gestlab.general.labels.quotes.warehouse_id') }}</dt>
              <dd>
                {{ quoteSiteLabel(props.record.data ?? {}) }}
                <span v-if="quote.warehouse?.city || quote.warehouse?.country" class="mt-1 block text-[13px] text-[var(--pl-muted)]">
                  {{ [quote.warehouse.city, quote.warehouse.postal_code, quote.warehouse.country].filter(Boolean).join(', ') }}
                </span>
              </dd>
            </div>
          </dl>
        </section>

        <section v-if="quote.status_history?.length" class="pl-panel" aria-labelledby="quote-history">
          <header class="pl-panel-head"><h2 id="quote-history" class="pl-k">{{ $t('gestlab.general.labels.quotes.timeline') }}</h2></header>
          <ol>
            <li v-for="(history, index) in quote.status_history" :key="index" class="pl-row">
              <span>
                <StatusChip :tone="history.status === 'approved' ? 'ok' : history.status === 'rejected' ? 'bad' : 'wait'">{{ formatStatus(history.status) }}</StatusChip>
                <span v-if="history.notes" class="mt-1.5 block text-[13px] text-[var(--pl-muted)]">{{ history.notes }}</span>
              </span>
              <time class="pl-k pl-faint">{{ formatDate(history.created_at) }}</time>
            </li>
          </ol>
        </section>
      </aside>
    </div>

    <NextStepBar>
      {{ isBilled ? 'Proforma facturada. A factura segue o seu próprio ciclo de cobrança.' : `Por facturar: AOA ${formatCurrency(quote.total)} em ${items.length} ${items.length === 1 ? 'linha' : 'linhas'}.` }}
      <template #actions>
        <Link v-if="canEdit" :href="route('quotes.edit', { quote: props.record.data?.id })" class="ds-button ds-button-secondary">
          <PencilIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.buttons.edit') }}
        </Link>
        <button v-if="!props.record.data?.converted_to_invoice" type="button" class="ds-button ds-button-primary" @click="convertToInvoice">
          {{ $t('gestlab.general.labels.quotes.convert_to_invoice') }}
        </button>
        <button v-else type="button" class="ds-button ds-button-primary" @click="viewInvoice">
          {{ $t('gestlab.general.labels.quotes.view_invoice') }}
        </button>
      </template>
    </NextStepBar>

    <DocumentShareModal
      :open="shareOpen"
      document-type="quote"
      :document-id="props.record.data?.id"
      document-label="Cotação"
      :document-number="props.record.data?.quote_no"
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
import { quoteBillingLabel, quoteSiteLabel } from '@/Composables/useQuoteAuthoring';
import { usePermission } from '@/Composables/usePermissions';
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
  Copy as DocumentDuplicateIcon,
  FileDown as DocumentArrowDownIcon,
  Pencil as PencilIcon,
  Mail as EnvelopeIcon,
} from "@lucide/vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object
});

/**
 * The proforma dossier (Plano): the agreed lines and totals, who it is for, and
 * the one next step — turning it into an invoice, or opening the invoice it became.
 */
const quote = computed(() => props.record.data ?? {});
const items = computed(() => quote.value.items ?? []);
const isBilled = computed(() => Boolean(quote.value.invoice_id || quote.value.converted_to_invoice));

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

const formatCurrency = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
};

const formatStatus = (status) => {
  const statusMap = {
    'pending': 'Pending',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'draft': 'Draft'
  };
  return statusMap[status] || status;
};

// Actions
const downloadPDF = () => {
  // Implement PDF download logic
  window.open(route('quotes.getPDF', { id: props.record.data?.id}), '_blank');
};

const sendEmail = () => {
  shareOpen.value = true;
};

const duplicateQuote = () => {
  // Implement duplicate logic
  router.visit(`/quotes/${props.record.data?.id}/duplicate`);
};

const convertToInvoice = () => {
  router.get(route('quotes.getConvertToInvoiceModal', { id: props.record.data?.id }));
}

const viewInvoice = () => {
  router.get(route('invoices.show', { id: props.record.data?.invoice_id }));
}

const { hasPermission } = usePermission();
const canEdit = computed(() => {
  return hasPermission('edit_quotes');
});
</script>
