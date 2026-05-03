<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { ClipboardDocumentListIcon, DocumentCheckIcon } from "@heroicons/vue/24/outline";


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

const category = props.query.category || 'pending';

const page = usePage();

onMounted(() => {
  changeCollectionCategory();
});

const changeCollectionCategory = (category='pending') => {
  category = category;

  router.get(page.url, {
    category: category
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

const range = ref({
  start: null,
  end: null,
});

const updateRange = (e) => {
  range.value = e;
}

const actionId = ref(null);

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  // input: 'YYYY-MM-DD',
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})

const selectedRecordIds = computed(() => props.record.data.filter((record) => record.selected).map((record) => record.id))


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
  router.get(route('programmedcollections.create'));
}

const exportSelectedAnalysisSheet = () => {
  if (!selectedRecordIds.value.length) return

  const params = new URLSearchParams()
  selectedRecordIds.value.forEach((id) => params.append('recordIds[]', id))

  window.location.href = `${route('programmedcollections.exportParametersToAnalyzeSheet')}?${params.toString()}`
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
      router.get(`/programmedcollections/destroy`, {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(`/programmedcollections/restore`, {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
  }
}  
</script>
<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.programmed_collections.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<div class="flex flex-col items-end mt-4">
  <span class="isolate inline-flex rounded-md shadow-sm">
    <button @click="changeCollectionCategory('pending')" type="button" class="relative inline-flex items-center rounded-l-md px-2 py-1.5 text-sm text-gray-900 border border-gray-300 hover:bg-gray-50 hover:text-blue-900 focus:z-10" :class="[props.query.category == 'pending' ? 'text-white bg-blue-900' : 'bg-white']">
      <span class="sr-only">Pendentes</span>
      <ClipboardDocumentListIcon class="size-5" aria-hidden="true" /> Pendentes
    </button>
    <button @click="changeCollectionCategory('archived')" type="button" class="relative -ml-px inline-flex items-center rounded-r-md px-2 py-1.5 text-sm text-gray-900 border border-gray-300 hover:bg-gray-50 hover:text-blue-900 focus:z-10" :class="[props.query.category == 'archived' ? 'text-white bg-blue-900' : 'bg-white']">
      <span class="sr-only">Processadas</span>
      <DocumentCheckIcon class="size-5" aria-hidden="true" /> Processadas
    </button>
  </span>
</div>

<div class="mt-4 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
  <div>
    <p class="text-sm font-semibold text-gray-900">Acompanhamento operacional</p>
    <p class="text-sm text-gray-500">
      {{ selectedRecordIds.length ? `${selectedRecordIds.length} registo(s) seleccionados para exportação da folha de parâmetros.` : 'Seleccione uma ou mais colheitas para exportar a folha XLSX com os parâmetros a analisar.' }}
    </p>
  </div>

  <button
    type="button"
    class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300"
    :disabled="!selectedRecordIds.length"
    @click="exportSelectedAnalysisSheet"
  >
    Exportar folha XLSX
  </button>
</div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="handleEdit"/> <br>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
</template>
