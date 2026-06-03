<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import slideOver from '@/Components/slide-over.vue'
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { ArrowPathRoundedSquareIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import VapTable from '@/Components/vap-table/table.vue'
import { usePermission } from '@/Composables/usePermissions'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'


const { hasPermission } = usePermission();
const props = defineProps({
    record: Object,
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
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

let form = useForm({
    id: null,
    proposal_id: '',
    confidentiality: false,
    impartiality: false,
    nondisclosure: false,
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


const actionType = ref(null);
const action = ref(null);
const record = ref(null);
const recordUrl = ref(null);
const selectedIDs = ref([]);
const complianceRecords = computed(() => props.record?.data || []);
const signedCount = computed(() => complianceRecords.value.filter(item => item.acknowledged_at).length);
const completeCount = computed(() => complianceRecords.value.filter(item => item.confidentiality && item.impartiality && item.nondisclosure).length);
const pendingCount = computed(() => Math.max(complianceRecords.value.length - signedCount.value, 0));

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + action.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + action.value);
})


const openslideover = ref(false);

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

const close = () => {
    openslideover.value = false;
    record.value = null;
    action.value = null;
    form.clearErrors();
}

const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.proposal_id = {
        value: data.proposal_id,
        label: data.proposal?.proposal_no || data.proposal?.proposal_number || data.proposal || data.proposal_id,
    };
    form.confidentiality = data.confidentiality;
    form.impartiality = data.impartiality;
    form.nondisclosure = data.nondisclosure;
    
}

const transformPayload = (data) => ({
    ...data,
    proposal_id: data.proposal_id?.value || data.proposal_id,
});

let submit = () => {

    if(!form.id) {
      form.transform(transformPayload).post(route('proposalcomplianceagreements.store'), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.transform(transformPayload).put(route('proposalcomplianceagreements.update',{agreement: form.id}), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    }
    
  }


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
      router.get(route('proposalcomplianceagreements.destroy'), {
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
        router.get(route('proposalcomplianceagreements.restore'), {
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


const parseJsonResponse = async (response) => {
  const contentType = response.headers.get('content-type') || ''

  if (!response.ok || !contentType.includes('application/json')) {
    throw new Error(`Resposta inválida (${response.status})`)
  }

  return response.json()
}

function loadProposals(query, setOptions) {
  fetch(route('vap-proposals.options.proposals', { q: query || '' }))
    .then(parseJsonResponse)
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.proposal_no || result.proposal_number || `#${result.id}`,
          data: result,
        }))
      )
    })
    .catch(() => setOptions([]))
}


</script>
<template>
<div class="proposal-compliance-page space-y-6" :class="commercialDocumentThemeClasses">
<section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950">
  <div class="grid gap-6 px-6 py-7 lg:grid-cols-[minmax(0,1fr)_26rem] lg:items-end">
    <div>
      <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">ISO 17025</p>
      <h1 class="mt-3 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">
        {{ $t('gestlab.general.labels.proposal_compliance_agreements.page_title') }}
      </h1>
      <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-[#59665f] dark:text-slate-300">
        Monitorize a aceitação formal das propostas, os compromissos de confidencialidade, imparcialidade e não divulgação, mantendo evidência rastreável do consentimento do cliente.
      </p>
    </div>
    <div class="grid grid-cols-3 gap-3">
      <div class="rounded-[24px] border border-[#ded2bb] bg-white/80 p-4 text-center shadow-[0_18px_45px_-34px_rgba(20,61,55,0.45)] dark:border-white/10 dark:bg-white/5">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">Registos</p>
        <p class="mt-2 text-2xl font-black text-[#143d37] dark:text-emerald-100">{{ complianceRecords.length }}</p>
      </div>
      <div class="rounded-[24px] border border-emerald-200 bg-emerald-50 p-4 text-center shadow-[0_18px_45px_-34px_rgba(20,61,55,0.45)] dark:border-emerald-300/20 dark:bg-emerald-400/10">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-200">Assinados</p>
        <p class="mt-2 text-2xl font-black text-emerald-800 dark:text-emerald-100">{{ signedCount }}</p>
      </div>
      <div class="rounded-[24px] border border-amber-200 bg-amber-50 p-4 text-center shadow-[0_18px_45px_-34px_rgba(20,61,55,0.45)] dark:border-amber-300/20 dark:bg-amber-400/10">
        <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-200">Pendentes</p>
        <p class="mt-2 text-2xl font-black text-amber-800 dark:text-amber-100">{{ pendingCount }}</p>
      </div>
    </div>
  </div>
  <div class="border-t border-[#ded2bb] bg-white/55 px-6 py-4 dark:border-white/10 dark:bg-white/5">
    <div class="flex flex-wrap gap-3 text-xs font-black uppercase tracking-[0.18em] text-[#59665f] dark:text-slate-300">
      <span class="rounded-full bg-[#f7f1e6] px-3 py-1 dark:bg-white/10">Confidencialidade</span>
      <span class="rounded-full bg-[#f7f1e6] px-3 py-1 dark:bg-white/10">Imparcialidade</span>
      <span class="rounded-full bg-[#f7f1e6] px-3 py-1 dark:bg-white/10">Não divulgação</span>
      <span class="rounded-full bg-[#f7f1e6] px-3 py-1 dark:bg-white/10">{{ completeCount }} completos</span>
    </div>
  </div>
</section>

<section class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-4 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
  <vap-table
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
    @create-record="form.reset(), openslideover=true" @slideover-on="openSlideoverWithData" 
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
        <template #column-name="{ row }">
          <strong class="text-blue-900">{{ row.name }}</strong>
        </template>

        <template #column-actions="{ row }">
          <!-- <strong class="text-blue-900">Actions go here!</strong>
          <button @click="() => {
            showDeleteConfirmation = true;
            actionType = 'single';
            action = 'edit_slide';
            record = row;
          }">Edit Slide</button> -->


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
        </template>
  </vap-table>
</section>

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

            <!-- Proposal -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="proposal_id" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposal_compliance_agreements.proposal_id') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <combobox-enhanced :hasError="form.errors.proposal_id" v-model="form.proposal_id" :load-options="loadProposals" placeholder="Pesquisar proposta"/>
                  <p v-if="form.errors.proposal_id" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.proposal_id }}</p>
                </div>
              </div>

              <!-- Confidentiality -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="confidentiality" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposal_compliance_agreements.confidentiality') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.confidentiality" type="checkbox" name="confidentiality" id="confidentiality" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.confidentiality" class="mt-2 text-sm text-red-600" id="confidentiality-error">{{ form.errors.confidentiality }}</p>
                </div>
              </div>


                <!-- Impartiality -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="impartiality" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposal_compliance_agreements.impartiality') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.impartiality" type="checkbox" name="impartiality" id="impartiality" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.impartiality" class="mt-2 text-sm text-red-600" id="impartiality-error">{{ form.errors.impartiality }}</p>
                </div>
              </div>

            <!-- Non Disclosure -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="nondisclosure" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.proposal_compliance_agreements.nondisclosure') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.nondisclosure" type="checkbox" name="nondisclosure" id="nondisclosure" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
                  <p v-if="form.errors.nondisclosure" class="mt-2 text-sm text-red-600" id="nondisclosure-error">{{ form.errors.nondisclosure }}</p>
                </div>
              </div>
              
        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <button v-if="form.isDirty" @click="showDeleteConfirmationSlideover = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_compliance_agreements.proposal_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.proposal_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_compliance_agreements.confidentiality') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.confidentiality ? $t('gestlab.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_compliance_agreements.impartiality') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.impartiality ? $t('gestlab.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.proposal_compliance_agreements.nondisclosure') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.nondisclosure ? $t('gestlab.buttons.yes') : $t('gestlab.general.buttons.no') }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</div>
</template>

<style scoped>
.proposal-compliance-page :deep(.text-blue-900),
.proposal-compliance-page :deep(.hover\:text-blue-900:hover) {
  color: #143d37 !important;
}

.proposal-compliance-page :deep(.bg-blue-900) {
  background-color: #143d37 !important;
}

.proposal-compliance-page :deep(.hover\:bg-blue-800:hover) {
  background-color: #0f302b !important;
}

.proposal-compliance-page :deep(.focus\:ring-blue-900:focus),
.proposal-compliance-page :deep(.focus-visible\:outline-indigo-900:focus-visible) {
  --tw-ring-color: #c79a43 !important;
  outline-color: #c79a43 !important;
}

.proposal-compliance-page :deep(.border-gray-200),
.proposal-compliance-page :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])),
.proposal-compliance-page :deep(.border-gray-100),
.proposal-compliance-page :deep(.divide-gray-100 > :not([hidden]) ~ :not([hidden])) {
  border-color: #ded2bb !important;
}

.proposal-compliance-page :deep(.text-gray-900) {
  color: #10221d !important;
}

.proposal-compliance-page :deep(.text-gray-700),
.proposal-compliance-page :deep(.text-gray-600),
.proposal-compliance-page :deep(.text-ft-gray) {
  color: #59665f !important;
}

.proposal-compliance-page :deep(input:not([type='checkbox'])),
.proposal-compliance-page :deep(select),
.proposal-compliance-page :deep(textarea) {
  background: rgba(255, 255, 255, 0.98);
  color: #18231f;
  border-color: #d8cbb4;
  box-shadow: 0 14px 30px -28px rgba(20, 61, 55, 0.55);
}

.proposal-compliance-page :deep(input[type='checkbox']) {
  border-color: #d8cbb4;
  color: #143d37;
}

:global(.dark) .proposal-compliance-page :deep(.bg-white),
:global(.dark) .proposal-compliance-page :deep(.bg-gray-50) {
  background-color: rgba(15, 23, 42, 0.92) !important;
}

:global(.dark) .proposal-compliance-page :deep(.text-gray-900),
:global(.dark) .proposal-compliance-page :deep(.text-gray-700),
:global(.dark) .proposal-compliance-page :deep(.text-gray-600) {
  color: #f8fafc !important;
}

:global(.dark) .proposal-compliance-page :deep(.border-gray-200),
:global(.dark) .proposal-compliance-page :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])),
:global(.dark) .proposal-compliance-page :deep(.border-gray-100),
:global(.dark) .proposal-compliance-page :deep(.divide-gray-100 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgba(255, 255, 255, 0.12) !important;
}

:global(.dark) .proposal-compliance-page :deep(input:not([type='checkbox'])),
:global(.dark) .proposal-compliance-page :deep(select),
:global(.dark) .proposal-compliance-page :deep(textarea) {
  background: rgba(15, 23, 42, 0.92);
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.12);
}
</style>
