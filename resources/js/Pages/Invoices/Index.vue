<script setup>
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue';
import { useRecordArchive } from '@/Composables/useRecordArchive';
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import PaymentDialog from '@/Components/dialog-modal.vue';
import { ref, computed } from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import { usePermission } from '@/Composables/usePermissions'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { EyeIcon } from "@heroicons/vue/24/outline";
import ModuleHero from '@/Components/base/ModuleHero.vue'

const { hasPermission } = usePermission();

const invoiceFilterOptions = [
  {
    id: null,
    label: 'gestlab.general.labels.invoices.filters.all',
  },
  {
    id: 'unpaid',
    label: 'gestlab.general.labels.invoices.filters.unpaid',
  },
  {
    id: 'paid',
    label: 'gestlab.general.labels.invoices.filters.paid',
  },
  {
    id: 'trashed',
    label: 'gestlab.filter.excluded',
  },
];


const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

const form = useForm({
    id: null,
    payment_method: '',
});
const paymentBusy = ref(false);

const actionId = ref(null);


const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


const actions = computed(() => [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  ...(hasPermission('delete_invoices') ? [{ id: 'delete', label: 'gestlab.actions.delete' }] : []),
  ...(hasPermission('restore_invoices') ? [{ id: 'restore', label: 'gestlab.actions.restore' }] : []),
]);

const handleEdit = () => {
  router.get(route('invoices.create'));
}

function loadPaymentCategories(query, setOptions) {
    fetch('/paymentcategories/getPaymentCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
} 

let submit = () => {
  if (paymentBusy.value || form.processing) return;
  paymentBusy.value = true;

  try {
  form.post(route('invoices.changeStatusToPaid'), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showPaymentConfirmation.value = false;
        form.reset()
      },
      onFinish: () => { paymentBusy.value = false; },
      onNetworkError: () => form.setError('payment_method', 'Ligação interrompida. Actualize a lista antes de tentar novamente.'),
      onHttpException: () => form.setError('payment_method', 'Não foi possível concluir o pagamento. Actualize a lista antes de tentar novamente.'),
      onCancel: () => form.setError('payment_method', 'Pedido cancelado. Actualize a lista antes de tentar novamente.'),
  });
  } catch {
    paymentBusy.value = false;
    form.setError('payment_method', 'Não foi possível enviar o pedido. Tente novamente.');
  }
}


const showDeleteConfirmation = ref(false);
const showPaymentConfirmation = ref(false);


const pendingIDs = ref([]);
const archive = useRecordArchive({
  destroyUrl: () => route('invoices.destroy'),
  restoreUrl: () => route('invoices.restore'),
  onSuccess: () => {
    pendingIDs.value = [];
    actionId.value = null;
    props.record.data.forEach(record => { record.selected = false; });
  },
});

function requestArchive(operation) {
  if (archive.processing.value || !['delete', 'restore'].includes(operation)
    || !hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'invoices')) return;
  const ids = props.record.data.filter(record => record.selected).map(record => record.id);
  if (!ids.length) return;
  pendingIDs.value = [...ids];
  actionId.value = operation;
  showDeleteConfirmation.value = true;
}

function archiveRecord(operation, ids) {
  if (!hasPermission((operation === 'delete' ? 'delete_' : 'restore_') + 'invoices')) return;
  archive.submit(operation, ids);
}

function confirmAction() {
  if (archive.processing.value) return;
  showDeleteConfirmation.value = false;
  if (actionId.value === 'mark_as_paid') {
    showPaymentConfirmation.value = true;
    return;
  }
  archiveRecord(actionId.value, pendingIDs.value);
}
</script>
<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
<ModuleHero
  :eyebrow="$t('gestlab.general.labels.commercial_documents.commercial_area')"
  :title="$t('gestlab.general.labels.invoices.page_title')"
  :description="$t('gestlab.general.labels.invoices.index_description')"
>
  <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <p class="ds-kicker text-[0.64rem]">
        {{ $t('gestlab.general.labels.commercial_documents.records') }}
      </p>
      <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">
        {{ props.record?.total ?? props.record?.data?.length ?? 0 }}
      </p>
    </article>
    <article class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <p class="ds-kicker text-[0.64rem]">
        {{ $t('gestlab.general.labels.commercial_documents.flow') }}
      </p>
      <p class="mt-2 text-sm font-black text-[var(--ds-text)]">
        {{ $t('gestlab.general.labels.invoices.index_flow') }}
      </p>
    </article>
  </div>
</ModuleHero>

<ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
<records-table :action-processing="archive.processing.value" :archive-handler="archiveRecord" :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :filter-options="invoiceFilterOptions" :actions="actions" @execute-action="requestArchive" @create-record="handleEdit">
  <template v-slot:actions="id">
      <Link
                    :href="route('invoices.show', {invoice: id.id})"
                    class="ds-table-action"
                  >
                  <EyeIcon class="h-5 w-5" />
            </Link>
    
    <button type="button" :disabled="archive.processing.value || paymentBusy" @click="() => {form.id = id.id; actionId='mark_as_paid'; showDeleteConfirmation = true;}" class="ds-table-action" v-if="!id.data.deleted && hasPermission('add_receipts') && !id.data.status">
      <div class="flex">

      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
      </svg>

      </div>

    </button>
  </template>
</records-table>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
<PaymentDialog :show="showPaymentConfirmation" :closeable="!paymentBusy" @close="showPaymentConfirmation=false">
  <template #title>{{ confirmationDialogTitle }}</template>
  <template #content>
  <p>{{ confirmationDialogDescription }}</p>
  <div class="mt-4 space-y-4">
    <p class="ds-kicker">
      {{ $t('gestlab.general.labels.invoices.payment_type') }}
    </p>
    <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <label class="ds-field-label">
        {{ $t('gestlab.general.labels.invoices.payment_type') }}
      </label>
      <combobox-enhanced :hasError="form.errors.payment_method" :disableInput="paymentBusy" v-model="form.payment_method" :load-options="loadPaymentCategories"/>
    </div>
  </div>
  <p v-if="form.hasErrors" role="alert">{{ Object.values(form.errors).join(' ') }}</p>
  </template>
  <template #footer>
    <button type="button" class="ds-button ds-button-secondary" :disabled="paymentBusy" @click="showPaymentConfirmation=false">{{ trans('gestlab.general.buttons.cancel') }}</button>
    <button type="button" class="ds-button ds-button-primary" :disabled="paymentBusy || !form.payment_method?.value" @click="submit">{{ paymentBusy ? 'A registar…' : trans('gestlab.general.buttons.submit') }}</button>
  </template>
</PaymentDialog>
</div>
</template>
