<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


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
  router.get(route('creditnotes.create'));
}

const showDeleteConfirmation = ref(false);


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(`/creditnotes/destroy`, {
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
        router.get(`/creditnotes/restore`, {
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
<div class="mb-6 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:ring-slate-800">
  <div class="relative isolate px-5 py-6 sm:px-7">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(var(--color-primary-500-rgb,59,130,246),0.18),transparent_32%),linear-gradient(135deg,rgba(248,250,252,0.96),rgba(255,255,255,0.9))] dark:bg-[radial-gradient(circle_at_top_left,rgba(var(--color-primary-400-rgb,96,165,250),0.20),transparent_34%),linear-gradient(135deg,rgba(15,23,42,0.98),rgba(2,6,23,0.96))]" />
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-700 dark:text-primary-300">Comercial</p>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ $t('gestlab.general.labels.credit_notes.page_title') }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
          Controle rectificações, créditos e documentos fiscais relacionados com facturas já emitidas.
        </p>
      </div>
      <div class="grid grid-cols-2 gap-3 sm:flex">
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/70">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Registos</p>
          <p class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ props.record?.total ?? props.record?.data?.length ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/70">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Fluxo</p>
          <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">Crédito</p>
        </div>
      </div>
    </div>
  </div>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="handleEdit"/> <br>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
</div>
</template>
