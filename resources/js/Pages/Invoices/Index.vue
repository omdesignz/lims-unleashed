<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { router, useForm, Link } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import { usePermission } from '@/Composables/usePermissions'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { EyeIcon } from "@heroicons/vue/24/outline";
import ModuleHero from '@/Components/base/ModuleHero.vue'

const { hasPermission } = usePermission();


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

const actionId = ref(null);


const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})


let actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text'
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore'
  },
];

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

  form.get(route('invoices.changeStatusToPaid',{id: form.id, payment_method: form.payment_method?.label}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        showPaymentConfirmation.value = false;
        form.reset()
      },
  });
}


const showDeleteConfirmation = ref(false);
const showPaymentConfirmation = ref(false);


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(`/invoices/destroy`, {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId.value = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(`/invoices/restore`, {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId.value = null;
            }
        });
        showDeleteConfirmation.value = false;
  }
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

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="handleEdit">
  <template v-slot:actions="id">
      <Link
                    :href="route('invoices.show', {invoice: id})"
                    class="ds-table-action"
                  >
                  <EyeIcon class="h-5 w-5" />
            </Link>
    
    <button type="button" @click="() => {form.id = id.id; actionId='mark_as_paid'; showDeleteConfirmation = true;}" class="ds-table-action" v-if="!id.data.deleted && hasPermission('add_receipts') && !id.data.status">
      <div class="flex">

      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
      </svg>

      </div>

    </button>
  </template>
</records-table>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="showPaymentConfirmation = true, showDeleteConfirmation=false" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
<confirm-dialog @canceled="showPaymentConfirmation=false" @close="showPaymentConfirmation=false" @confirmed="submit" v-if="showPaymentConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.submit')" :cancel="trans('gestlab.general.buttons.cancel')">
  <div class="mt-4 space-y-4">
    <p class="ds-kicker">
      {{ $t('gestlab.general.labels.invoices.payment_type') }}
    </p>
    <div class="ds-card bg-[var(--ds-panel-raised)] p-4">
      <label class="ds-field-label">
        {{ $t('gestlab.general.labels.invoices.payment_type') }}
      </label>
      <combobox-enhanced :hasError="form.errors.payment_method" v-model="form.payment_method" :load-options="loadPaymentCategories"/>
    </div>
  </div>
</confirm-dialog>
</div>
</template>
