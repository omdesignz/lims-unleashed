<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, watch, reactive } from "vue";
import debounce from 'lodash/debounce'
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { MagnifyingGlassCircleIcon, PencilIcon, SquaresPlusIcon, TrashIcon } from "@heroicons/vue/24/outline";
import Pagination from '@/Components/pagination.vue'
import emptyState from '@/Components/empty-state.vue'
import { usePermission } from '@/Composables/usePermissions'
import { ColorPicker } from "vue3-colorpicker";
import IconPicker from "@/Components/icon-picker.vue";
import * as OutlinedIcons from '@heroicons/vue/24/outline'
import FormulaDisplay from "@/Components/formula-display.vue";
import StrengthCalibrationTable from '@/Components/Calibration/strength-calibration-table.vue';
import StrengthCalibrationTableMultiple from '@/Components/Calibration/strength-calibration-table-multiple.vue';
import ScaleCalibrationTable from '@/Components/Calibration/scale-calibration-table.vue';
import UncertaintyCalculations from '@/Components/Calibration/uncertainty-calculator.vue';
import eccentricityDiagram from '@/Components/Calibration/eccentricity-diagram.vue';


const { hasRole, hasPermission } = usePermission();

const openIconPicker = ref(false);

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

let form = useForm({
    name: '',
    description: '',
    bgcolor: '',
    iconcolor: '',
    icon: '',
});

const query = reactive({
  search: props.query?.search,
  filter: props.query?.filter,
  page: null
});

watch(query, debounce( function(value) {
  router.get(router.page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));

const actionId = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.title');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
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

const close = () => {
    openslideover.value = false;
    form.clearErrors();
    form.reset();
}

const showDeleteConfirmation = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
    form.id = data.id;
    form.name = data.name;
    form.description = data.description;
    form.bgcolor = data.bgcolor;
    form.iconcolor = data.iconcolor;
    form.icon = data.icon;
    
}

let submit = () => {

    if(!form.id) {
      form.post(route('boards.store'), {
          preserveScroll: true,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    } else {
      form.put(route('boards.update',{board: form.id}), {
          preserveScroll: true,
          onSuccess: () => {
            openslideover.value = false;
            form.reset()
          },
      });
    }
    
  }


  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(route('boards.destroy'), {
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
        router.get(route('boards.restore'), {
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
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.formulas.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">
      <!-- <Link as="button" href="/formulas/mb" @click="" class="group mt-1 -ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 hover:bg-blue-900 hover:text-white focus:outline-none">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5 text-gray-900 group-hover:text-white group-hover:animate-bounce" aria-hidden="true" />
          Playground de Microbiologia
      </Link> -->

      <Link as="button" :href="route('formulas.create')" class="group mt-1 -ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 hover:bg-blue-900 hover:text-white focus:outline-none">
          <SquaresPlusIcon class="-ml-1 mr-2 h-5 w-5 text-gray-900 group-hover:text-white group-hover:animate-bounce" aria-hidden="true" />
          Criar Fórmula
      </Link>
    </p>
</div>

<div class="flex-1 flex">
  <form class="w-full flex md:ml-0" action="#" method="GET">
    <label for="mobile-search-field" class="sr-only">{{ $t('gestlab.general.search_input_placeholder') }}</label>
    <label for="desktop-search-field" class="sr-only">{{ $t('gestlab.general.search_input_placeholder') }}</label>
    <div class="relative w-full text-gray-500 focus-within:text-blue-900">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
        <MagnifyingGlassCircleIcon class="flex-shrink-0 h-5 w-5 animate-bounce" aria-hidden="true" />
      </div>
      <input v-model="query.search" name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:hidden" :placeholder="$t('gestlab.general.search_input_placeholder')" type="search" />
      <input v-model="query.search" name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:block" :placeholder="$t('gestlab.general.search_input_placeholder')" type="search" />
    </div>
  </form>
</div>
<div>
    <h2 class="text-sm font-medium text-gray-500"></h2>
    <ul v-if="props.record.data.length" role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
      <li v-for="record in props.record.data" :key="record.id" class="col-span-1 flex rounded-md shadow-sm">
        
        <div class="flex flex-1 items-center justify-between truncate rounded-md border-b border-l border-r border-t border-gray-200 bg-white">
          <div class="flex-1 truncate px-4 py-2 text-sm">
            <Link :href="route('boards.show', {board: record.id})" class="font-medium text-gray-900 hover:text-gray-600">{{ record.name }}</Link>
            <FormulaDisplay :formula="record.expression" />

          </div>
          <div class="flex-shrink-0 pr-2">
            <Link :href="record.links.delete_path" as="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent bg-white text-gray-400 hover:text-red-600 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">Open options</span>
              <TrashIcon class="h-5 w-5" aria-hidden="true" />
            </Link>
            <Link :href="record.links.edit_path" as="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent bg-white text-gray-400 hover:text-gray-600 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">Open options</span>
              <PencilIcon class="h-5 w-5" aria-hidden="true" />
            </Link>
            <!-- <button @click="openSlideoverWithData(record)" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent bg-white text-gray-400 hover:text-gray-600 transform transition-all duration-200 hover:scale-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
              <span class="sr-only">Open options</span>
              <PencilIcon class="h-5 w-5" aria-hidden="true" />
            </button> -->
          </div>
        </div>
      </li>
    </ul>
    <empty-state class="pb-5" v-else @create-record="() => openslideover = true" name="Sem registros para apresentar." description="Comece por adicionar um novo clicando no botão abaixo." />
    <hr class="mt-4">
  <Pagination v-if="props.record.data.length" :links="props.record.meta.links" :from="props.record.meta.from" :to="props.record.meta.to" :total="props.record.meta.total" :current_page="props.record.meta.current_page" :last_page="props.record.meta.last_page" class="mt-2" />

  </div>

  <!-- <strength-calibration-table /> -->
  <!-- <strength-calibration-table-multiple /> -->
   <!-- <scale-calibration-table />

   <br>

   <eccentricity-diagram /> -->


    <!-- <uncertainty-calculations /> -->

<!-- <records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openslideover=true" @slideover-on="openSlideoverWithData"/> <br> -->

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">

              <!-- BG Color -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="bgcolor" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.formulas.bgcolor') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <color-picker v-model:pure-color="form.bgcolor" id="bgcolor" format="hex" shape="circle" lang="En"/>
                  <p v-if="form.errors.bgcolor" class="mt-2 text-sm text-red-600" id="bgcolor-error">{{ form.errors.bgcolor }}</p>
                </div>
              </div>

              <!-- Icon -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="icon" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.formulas.icon') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <icon-picker @picker-closed="openIconPicker=false" :show="openIconPicker" v-model="form.icon" @icon-selected="(e) => form.icon = e" />
                    <button v-if="!openIconPicker" @click="openIconPicker = true">
                      <component v-if="form.icon" :is="OutlinedIcons[form.icon]" class="h-6 w-6 flex-shrink-0 text-gray-900" aria-hidden="true" />
                      <component v-else :is="OutlinedIcons.LightBulbIcon" class="h-6 w-6 flex-shrink-0 text-gray-900" aria-hidden="true" />
                    </button>
                  <!-- <button v-if="!openIconPicker" @click="openIconPicker = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button> -->
                  <p v-if="form.errors.icon" class="mt-2 text-sm text-red-600" id="icon-error">{{ form.errors.icon }}</p>
                </div>
              </div>

              <!-- Icon Color -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="iconcolor" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.formulas.iconcolor') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <color-picker v-model:pure-color="form.iconcolor" id="iconcolor" format="hex" shape="circle" lang="En"/>
                  <p v-if="form.errors.iconcolor" class="mt-2 text-sm text-red-600" id="iconcolor-error">{{ form.errors.iconcolor }}</p>
                </div>
              </div>

              <!-- Name -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.formulas.name') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.name }}</p>
                </div>
              </div>

              <!-- Description -->
              <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                <div>
                  <label for="description" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.formulas.description') }}</label>
                </div>
                <div class="sm:col-span-2">
                  <textarea v-model="form.description" name="description" id="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                  <p v-if="form.errors.description" class="mt-2 text-sm text-red-600" id="description-error">{{ form.errors.description }}</p>
                </div>
              </div>

        </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <!-- <TransitionRoot
            :show="!form.isDirty"
            enter="transition-opacity duration-75"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150"
            leave-from="opacity-100"
            leave-to="opacity-0"
        >
            I will appear and disappear.
        </TransitionRoot> -->
        <button v-if="form.isDirty" @click="submit" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />
</template>