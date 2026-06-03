<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
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
    entrypoint: { type: Object, default: () => ({}) },
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
  router.get(props.entrypoint?.create_sample_url || route('vap_samples.index', { collection_type: 'programmed' }));
}

const exportSelectedAnalysisSheet = () => {
  if (!selectedRecordIds.value.length) return

  const params = new URLSearchParams()
  selectedRecordIds.value.forEach((id) => params.append('recordIds[]', id))

  window.location.href = `${route('programmedcollections.exportParametersToAnalyzeSheet')}?${params.toString()}`
}

const showDeleteConfirmation = ref(false);

const prepareBulkAction = (selectedActionId) => {
  actionId.value = selectedActionId;
  showDeleteConfirmation.value = true;
}


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (selectedActionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (selectedActionId) {
    case 'delete':
      router.get(`/programmedcollections/destroy`, {
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
        router.get(`/programmedcollections/restore`, {
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
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Planeamento de colheitas"
      :title="$t('gestlab.general.labels.programmed_collections.page_title')"
      description="Planeie colheitas programadas, acompanhe pendências e exporte folhas de parâmetros para execução laboratorial."
    >
      <template #actions>
        <div class="flex flex-wrap gap-3">
          <button
            @click="changeCollectionCategory('pending')"
            type="button"
            :class="[
              'inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition',
              props.query.category == 'pending'
                ? 'border-primary-600 bg-primary-600 text-white shadow-lg shadow-primary-600/20 dark:border-primary-500 dark:bg-primary-500'
                : 'border-slate-300 bg-white/80 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800'
            ]"
          >
            <ClipboardDocumentListIcon class="size-5" aria-hidden="true" />
            Pendentes
          </button>
          <button
            @click="changeCollectionCategory('archived')"
            type="button"
            :class="[
              'inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition',
              props.query.category == 'archived'
                ? 'border-primary-600 bg-primary-600 text-white shadow-lg shadow-primary-600/20 dark:border-primary-500 dark:bg-primary-500'
                : 'border-slate-300 bg-white/80 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-800'
            ]"
          >
            <DocumentCheckIcon class="size-5" aria-hidden="true" />
            Processadas
          </button>
        </div>
      </template>
    </ModuleHero>

    <ModuleCard
      :title="props.entrypoint?.label || 'A entrada canónica é Sample Entry'"
      :description="props.entrypoint?.description || 'Use a receção de amostra para iniciar novos fluxos; esta lista acompanha a etapa operacional depois da validação.'"
    >
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <p class="text-sm font-semibold text-slate-900 dark:text-white">Novo planeamento recomendado</p>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
            A Sample Entry passa a carregar produto, matriz, escopo analítico, local de colheita, equipa/viatura e lab code.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          @click="handleEdit"
        >
          Criar via Sample Entry
        </button>
      </div>
    </ModuleCard>

    <ModuleCard title="Acompanhamento operacional" description="Seleccione colheitas para exportar a folha XLSX com os parâmetros a analisar.">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          {{ selectedRecordIds.length ? `${selectedRecordIds.length} registo(s) seleccionados para exportação da folha de parâmetros.` : 'Seleccione uma ou mais colheitas para exportar a folha XLSX com os parâmetros a analisar.' }}
        </p>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-slate-300 dark:bg-primary-500 dark:hover:bg-primary-400"
          :disabled="!selectedRecordIds.length"
          @click="exportSelectedAnalysisSheet"
        >
          Exportar folha XLSX
        </button>
      </div>
    </ModuleCard>

    <ModuleCard title="Fila de colheitas programadas">
      <records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="prepareBulkAction" @create-record="handleEdit"/>
    </ModuleCard>

    <confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
  </div>
</template>
