<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleHero from '@/Components/base/ModuleHero.vue'


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
  router.get(route('receipts.create'));
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
      router.get(`/receipts/destroy`, {
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
        router.get(`/receipts/restore`, {
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
  :eyebrow="$t('gestlab.general.labels.commercial_documents.treasury_area')"
  :title="$t('gestlab.general.labels.receipts.page_title')"
  :description="$t('gestlab.general.labels.receipts.index_description')"
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
        {{ $t('gestlab.general.labels.receipts.index_flow') }}
      </p>
    </article>
  </div>
</ModuleHero>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="handleEdit"/>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" :confirm="trans('gestlab.general.buttons.yes')" :cancel="trans('gestlab.general.buttons.no')" />
</div>
</template>
