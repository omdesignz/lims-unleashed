<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';


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
  router.get(props.entrypoint?.analysis_url || route('analysis.index', { category: 'insert' }));
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
      router.get(`/counteranalysis/destroy`, {
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
        router.get(`/counteranalysis/restore`, {
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
  <div class="counter-analysis-page space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Decisão técnica"
      title="Contra-análises"
      description="Abra, acompanhe e conclua contra-análises quando um resultado exige confirmação técnica, repetição ou evidência adicional."
    >
      <template #actions>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          @click="handleEdit"
        >
          Solicitar a partir dos resultados
        </button>
      </template>
    </ModuleHero>

    <ModuleCard
      :title="props.entrypoint?.label || 'Contra-análises nascem de resultados existentes'"
      :description="props.entrypoint?.description || 'Solicite uma contra-análise a partir do ecrã de gestão de resultados para preservar rastreabilidade técnica.'"
    >
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          A contra-análise deve manter ligação ao resultado original, parâmetro, amostra e lab code. Por isso, o pedido inicia no fluxo de resultados.
        </p>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          @click="handleEdit"
        >
          Abrir gestão de resultados
        </button>
      </div>
    </ModuleCard>

    <ModuleCard title="Fila de contra-análises" description="Registos pendentes e históricos, com ações em lote e restauração quando aplicável.">
      <records-table
        :record="props.record"
        :model="props.model"
        :abilities="props.abilities"
        :fields="props.fields"
        :slideOverEdit="props.slideOverEdit"
        :query="props.query"
        :actions="actions"
        @execute-action="prepareBulkAction"
        @create-record="handleEdit"
      />
    </ModuleCard>

    <confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
  </div>
</template>
