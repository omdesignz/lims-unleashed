<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, h, watch, onMounted } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { DatePicker } from 'v-calendar'
import { TrashIcon, PencilIcon, ArrowPathRoundedSquareIcon, EyeIcon, DocumentCheckIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import VapTable from '@/Components/vap-table/table.vue';
import { usePermission } from "@/Composables/usePermissions";
import combobox from '@/Components/combobox.vue';
// import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';
import comboboxMultiple from '@/Components/combobox-multiple.vue';
import Stats from '@/Components/stats.vue';


const { hasRole, hasPermission } = usePermission();
const props = defineProps({
    stats: Array,
    record: Object,
    parameters: Array,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    trashedFilter: { type: Boolean, default: false },
    trashedOptions: { type: Object, default: {} },
    initialFilters: { type: Object, default: {} },
    initialSortField: { type: String, default: '' },
    initialSortDirection: { type: String, default: 'asc' },
    initialIncludes: { type: Array, default: [] },
    initialGlobalFilter: { type: String, default: '' },
    entrypoint: { type: Object, default: () => ({}) },
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

const selectedParameters = ref([]);

const category = props.query.category || 'pending';

const page = usePage();

const scopeDashboard = computed(() => {
  const records = props.record?.data || [];

  const scopedRecords = records.filter((item) => (item.scope_control?.required_parameter_count || 0) > 0);
  const restrictedRecords = scopedRecords.filter((item) => item.scope_control?.conditioning_status === 'restricted');
  const pendingScopedRecords = scopedRecords.filter((item) => (item.pending_analysis || 0) > 0);

  return {
    scopedCount: scopedRecords.length,
    restrictedCount: restrictedRecords.length,
    requiredParameterTotal: scopedRecords.reduce((total, item) => total + (item.scope_control?.required_parameter_count || 0), 0),
    pendingScopedCount: pendingScopedRecords.length,
    attentionRecords: scopedRecords
      .filter((item) => item.scope_control?.conditioning_status === 'restricted' || (item.pending_analysis || 0) > 0)
      .sort((left, right) => {
        const leftWeight = (left.scope_control?.conditioning_status === 'restricted' ? 10 : 0) + (left.pending_analysis || 0);
        const rightWeight = (right.scope_control?.conditioning_status === 'restricted' ? 10 : 0) + (right.pending_analysis || 0);

        return rightWeight - leftWeight;
      })
      .slice(0, 5),
  };
});

const conditioningLabels = {
  accepted: 'Aceite',
  restricted: 'Aceite com restrições',
  rejected: 'Rejeitado',
};

onMounted(() => {
  changeCollectionCategory();
});

const columns = props.fields.map(field => {
    return {
      field: field.value,
      filter_field: field.filter_field,
      label: field.name,
      visible: true,
      filterable: field.filterable,
      type: field.type,
      format: field.format,
      filter: field.filter,
      options: field.options ? field.options : [],
      config: field.config ? field.config : {}
    };
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


const date = ref(null);

const actionType = ref(null);
const action = ref(null);
const record = ref(null);
const recordUrl = ref(null);
const selectedIDs = ref([]);

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + action.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + action.value);
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

const filters = [
  {
    id: null,
    label: trans('gestlab.filter.none'),
  },
  {
    id: "trashed",
    label: trans('gestlab.filter.excluded'),
  },
];

const showDeleteConfirmation = ref(false);


  const confirmAction = () => {

    if(actionType.value === 'bulk') {
      executeBulkAction(action.value);
    } else {
      executeSingleAction(action.value, record.value);
    }
  }

  const executeBulkAction = (action) => {
    
    if(!selectedIDs.value.length) return;

    switch (action) {
    case 'delete':
      router.get(route('directcollections.destroy'), {
          recordIds: selectedIDs.value
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            action = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(route('directcollections.restore'), {
          recordIds: selectedIDs.value
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                action = null;
            }
        });
        showDeleteConfirmation.value = false;
    }
  };
  
  const executeSingleAction = (action, record ) => {
    
    switch (action) {
      case "delete":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
        break;

      case "edit":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
        break;

      case "edit_slide":
        openSlideoverWithData(record)

        showDeleteConfirmation.value = false;
        break;

      case "restore":
        router.get(
          recordUrl.value,
          {
            recordIds: [record.id],
          },
          {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => {
              showDeleteConfirmation.value = false;
              action = null;
              record = null;
              recordUrl.value = null;
            },
          },
        );
        showDeleteConfirmation.value = false;
    }

  };


const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
  // month: "MMM",
  // input: 'YYYY-MM-DD',
});

const updateRange = (e) => {
  date.value = e;
};

function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
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

const handleCreateAction = () => {
  router.get(props.entrypoint?.create_sample_url || route('vap_samples.index', { collection_type: 'direct' }));
}

const exportSelectedAnalysisSheet = () => {
  if (!selectedIDs.value.length) return;

  const params = new URLSearchParams();
  selectedIDs.value.forEach((id) => params.append('recordIds[]', id));

  window.location.href = `${route('directcollections.exportParametersToAnalyzeSheet')}?${params.toString()}`;
}


</script>
<template>
<div class="space-y-8" :class="commercialDocumentThemeClasses">
<ModuleHero
    eyebrow="Sample collection"
    :title="$t('gestlab.general.labels.direct_collections.page_title')"
    description="Acompanhe colheitas diretas, escopo analítico, QR codes, parâmetros previstos e exportações operacionais para bancada."
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
        <ClipboardDocumentListIcon class="size-5" aria-hidden="true" /> Pendentes
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
        <DocumentCheckIcon class="size-5" aria-hidden="true" /> Processadas
      </button>
    </div>
  </template>
</ModuleHero>

<!-- Statistics -->
<Stats :data="props.stats" />

<ModuleCard
  :title="props.entrypoint?.label || 'A entrada canónica é Sample Entry'"
  :description="props.entrypoint?.description || 'Use a receção de amostra para iniciar novos fluxos; esta lista acompanha a etapa operacional depois da validação.'"
>
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <p class="text-sm font-semibold text-slate-900 dark:text-white">Novo fluxo recomendado</p>
      <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
        Produto, matriz, escopo, evidências, lab code e análise ficam ligados desde a receção.
      </p>
    </div>
    <button
      type="button"
      class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
      @click="handleCreateAction"
    >
      Criar via Sample Entry
    </button>
  </div>
</ModuleCard>

<ModuleCard title="Acompanhamento operacional" description="Seleccione colheitas para exportar a folha XLSX com os parâmetros a analisar.">
<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
  <div>
    <p class="text-sm text-slate-600 dark:text-slate-300">
      {{ selectedIDs.length ? `${selectedIDs.length} registo(s) seleccionados para exportação da folha de parâmetros.` : 'Seleccione uma ou mais colheitas para exportar a folha XLSX com os parâmetros a analisar.' }}
    </p>
  </div>

  <button
    type="button"
    class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-slate-300 dark:bg-primary-500 dark:hover:bg-primary-400"
    :disabled="!selectedIDs.length"
    @click="exportSelectedAnalysisSheet"
  >
    Exportar folha XLSX
  </button>
</div>
</ModuleCard>

<div v-if="scopeDashboard.scopedCount" class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,2fr),minmax(320px,1fr)]">
  <section class="rounded-3xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Escopo controlado</p>
        <h2 class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">Visão rápida da fila técnica</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
          Mostra quantas colheitas da lista já trazem parâmetros previstos e quais exigem maior atenção operacional.
        </p>
      </div>
      <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
        {{ scopeDashboard.scopedCount }} colheitas com escopo
      </div>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <article class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/45">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Colheitas com escopo</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeDashboard.scopedCount }}</p>
      </article>
      <article class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/45">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Com restrições ISO</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeDashboard.restrictedCount }}</p>
      </article>
      <article class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/45">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Parâmetros planeados</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeDashboard.requiredParameterTotal }}</p>
      </article>
      <article class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/45">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Ainda pendentes</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ scopeDashboard.pendingScopedCount }}</p>
      </article>
    </div>
  </section>

  <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
    <p class="text-sm font-semibold text-slate-900 dark:text-white">Colheitas que pedem atenção</p>
    <div v-if="scopeDashboard.attentionRecords.length" class="mt-4 space-y-3">
      <a
        v-for="row in scopeDashboard.attentionRecords"
        :key="row.id"
        :href="route('directcollections.show', { collection: row.id })"
        class="block rounded-2xl border border-slate-200 bg-slate-50 p-3 transition hover:border-primary-200 hover:bg-primary-50 dark:border-slate-800 dark:bg-slate-950/45 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ row.cl }} · {{ row.product }}</p>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ row.scope_control?.required_parameter_count || 0 }} parâmetros previstos</p>
          </div>
          <span
            :class="[
              'rounded-full px-2.5 py-1 text-[11px] font-semibold',
              row.scope_control?.conditioning_status === 'restricted'
                ? 'bg-amber-100 text-amber-800'
                : 'bg-slate-100 text-slate-700'
            ]"
          >
            {{ conditioningLabels[row.scope_control?.conditioning_status] || 'Sem avaliação' }}
          </span>
        </div>
        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
          {{ row.pending_analysis || 0 }} análises pendentes
        </p>
      </a>
    </div>
    <p v-else class="mt-4 text-sm text-slate-500 dark:text-slate-400">
      Nenhuma colheita da lista atual requer atenção prioritária.
    </p>
  </aside>
</div>

<ModuleCard title="Fila de colheitas diretas">
<vap-table
    hasQr
    :model="props.model" 
    :abilities="props.abilities" 
    :data="props.record.data" 
    :columns="columns" 
    :query="props.query" 
    :filters="filters" 
    :initialFilters="props.initialFilters"
    :initialSortField=props.initialSortField
    :initialSortDirection=props.initialSortDirection
    :initialIncludes="props.initialIncludes"
    :trashedFilter="props.trashedFilter"
    :trashedOptions="props.trashedOptions"
    @create-record="handleCreateAction" 
    :slideOverEdit="props.slideOverEdit" 
    :pagination="props.record.meta" 
    @update-selected-ids="selectedIDs = $event"
    :actions="actions" 
    @execute-bulk-action="($event) => {
      showDeleteConfirmation = true; 
      action = $event.action; 
      actionType = $event.actionType
      }"
    >
    <template #column-qr="{ row }">
      <img :src="row.qr" alt="QR Code" class="w-16 h-16" />
    </template>

        <template #column-actions="{ row }">
            <div class="inline-flex">
              <Link
                    :href="route('directcollections.show', {collection: row.id})"
                    v-if="
                      !row.deleted &&
                      hasPermission('view_' + props.model)
                    "
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                  >
                  <EyeIcon class="h-4 w-4" />
            </Link>


            <a
                    target="_blank"
                    :href="row.links.pdf_path"
                    v-if="
                      !row.deleted &&
                      hasPermission('view_' + props.model) &&
                      row?.links?.pdf_path
                    "
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="currentColor"
                      class="w-4 h-4"
                    >
                      <path
                        d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z"
                      />
                      <path
                        d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z"
                      />
                    </svg>
                  </a>
                  <a
                    target="_blank"
                    :href="row.links.pdf_collection_term"
                    v-if="
                      !row.deleted &&
                      hasPermission('view_' + props.model) && 
                      row?.links?.pdf_collection_term
                    "
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="1.5"
                      stroke="currentColor"
                      class="w-4 h-4"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                      />
                    </svg>
                  </a>
                  <a
                    target="_blank"
                    :href="row.links.pdf_collection_labels"
                    v-if="
                      !row.deleted &&
                      hasPermission('view_' + props.model) &&
                      row?.links?.pdf_collection_labels
                    "
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                  >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h4 w-4">
                    <path fill-rule="evenodd" d="M5.25 2.25a3 3 0 0 0-3 3v4.318a3 3 0 0 0 .879 2.121l9.58 9.581c.92.92 2.39 1.186 3.548.428a18.849 18.849 0 0 0 5.441-5.44c.758-1.16.492-2.629-.428-3.548l-9.58-9.581a3 3 0 0 0-2.122-.879H5.25ZM6.375 7.5a1.125 1.125 0 1 0 0-2.25 1.125 1.125 0 0 0 0 2.25Z" clip-rule="evenodd" />
                  </svg>

                  </a>

<button
          type="button"
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'restore';
              recordUrl = row.links.restore_path;
              showDeleteConfirmation = true;
            }
          "
          v-if="
            row.deleted && hasPermission('restore_' + props.model)
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <ArrowPathRoundedSquareIcon class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'edit';
              recordUrl = row.links.edit_path;
              showDeleteConfirmation = true;
            }
          "
          v-if="
            !row.deleted &&
            !!!props.slideOverEdit &&
            hasPermission('edit_' + props.model)
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <PencilIcon class="h-4 w-4" />

        </button>
      <button
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'edit_slide';
              showDeleteConfirmation = true;
            }
          "
          v-if="
            !row.deleted &&
            hasPermission('edit_' + props.model) &&
            !!props.slideOverEdit
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
        >
        <PencilIcon class="h-4 w-4" />

        </button>
        <button
          type="button"
          @click="
            () => {
              record = row;
              actionType = 'single';
              action = 'delete';
              recordUrl = row.links.delete_path;
              showDeleteConfirmation = true;
            }
          "
          class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
          v-if="
            !row.deleted && hasPermission('delete_' + props.model)
          "
        >
        <TrashIcon class="h-4 w-4" />

        </button>
    </div>
        </template>

        <template #specific-filters>
          
          <!-- Select Filter: Parameters -->

          <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-12">
            <div class="sm:col-span-full">
              

            </div>
          </div>
        </template>
</vap-table>
</ModuleCard>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

</div>
</template>
