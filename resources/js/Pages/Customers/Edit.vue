<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BuildingStorefrontIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.customers.page_title') }}
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.customers.page_update_description') }}
            <span v-if="form.name" class="font-semibold text-blue-900 ml-1">
              {{ form.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.warehouses.length }} {{ $t('gestlab.general.labels.customers.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- CUSTOMER INFORMATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.customer_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <form @submit.prevent class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- NAME FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6 transition-colors duration-200',
                    form.errors.name
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.customers.placeholders.customer_name')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.description') }}
                </label>
                <input
                  v-model="form.description"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6 transition-colors duration-200',
                    form.errors.description
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.customers.placeholders.description')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- CATEGORY FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.category_id') }}
                </label>
                <div class="mt-1">
                  <combobox 
                    v-model="form.category_id" 
                    :load-options="loadCategories"
                    :has-error="form.errors.category_id"
                  />
                </div>
                <p v-if="form.errors.category_id" class="text-xs text-red-600">
                  {{ form.errors.category_id }}
                </p>
              </div>

              <!-- UPDATE BUTTON -->
              <div class="space-y-2 flex items-end">
                <button
                  @click="submit"
                  :disabled="form.processing || !form.isDirty"
                  :class="[
                    'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                    form.processing || !form.isDirty
                      ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                      : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                  ]"
                >
                  <CheckIcon v-if="!form.processing" class="h-5 w-5" />
                  <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                  {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.update') }}
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- WAREHOUSES SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <BuildingOfficeIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.customers.warehouses') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.warehouses.length }} {{ $t('gestlab.general.labels.customers.items') }})
                </span>
              </h2>
              <button 
                @click="addWarehouse"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.customers.add_warehouse') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.warehouses.length === 0" class="p-12 text-center">
            <BuildingOffice2Icon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.customers.no_warehouses') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.customers.add_first_warehouse') }}
            </p>
            <button 
              @click="addWarehouse"
              type="button"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.add_first_warehouse') }}
            </button>
          </div>

          <!-- WAREHOUSES GRID -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-1 gap-6 p-6">
            <div 
              v-for="(warehouse, index) in form.warehouses"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- WAREHOUSE HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ warehouse.code || $t('gestlab.general.labels.customers.unnamed_warehouse') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.customers.warehouse') }} #{{ index + 1 }}
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeWarehouse(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.buttons.remove')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- WAREHOUSE COMPONENT -->
              <div class="p-4">
                <warehouse-component 
                  :primary_warehouse="form.warehouse_id" 
                  :record="warehouse" 
                  @removed-from-array="removeWarehouse(index)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between py-2">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.last_updated') }}</span>
              <span class="text-sm font-medium text-gray-900">
                {{ new Date().toLocaleDateString() }}
              </span>
            </div>
            
            <div class="flex items-center justify-between py-2">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.warehouse_count') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                form.warehouses.length > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ form.warehouses.length }} {{ $t('gestlab.general.labels.customers.items') }}
              </span>
            </div>

            <div class="flex items-center justify-between py-2">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.form_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                form.isDirty ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ form.isDirty ? $t('gestlab.general.status.unsaved_changes') : $t('gestlab.general.status.saved') }}
              </span>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <button 
              @click="addWarehouse"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-2.5 text-sm font-medium text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.new_warehouse') }}
            </button>
            
            <button 
              @click="submit"
              :disabled="!form.isDirty"
              :class="[
                'w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors duration-200',
                form.isDirty
                  ? 'bg-blue-900 text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed'
              ]"
            >
              <CheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.save_changes') }}
            </button>

            <!-- DIVIDER -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.customers.customer_name') }}</span>
                  <span class="font-medium text-blue-900 truncate max-w-[150px]">{{ form.name || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.customers.total_warehouses') }}</span>
                  <span class="font-medium text-blue-900">{{ form.warehouses.length }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import warehouseComponent from '@/Pages/Warehouses/warehouse-component.vue';
import { throttle } from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  BuildingStorefrontIcon,
  UserCircleIcon,
  BuildingOfficeIcon,
  BuildingOffice2Icon,
  CheckIcon,
  Cog6ToothIcon,
  ArrowPathIcon
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object
});

const form = useForm({
  id: props.record?.id,
  name: props.record?.name,
  code: props.record?.code,
  description: props.record?.description,
  category_id: props.record?.category_id,
  warehouse_id: props.record?.warehouse_id,
  warehouses: props.record?.warehouses || []
});

onMounted(() => {
  // Initialization logic here
});

const addWarehouse = () => {
  form.warehouses.push({
    email: '',
    primary_phone: '',
    alternative_phone: '',
    nif: '',
    address: '',
    municipality: '',
    province: '',
    description: '',
    code: '',
    customer_id: {
      value: props.record?.id,
      label: props.record?.name || ''
    }
  });
};

const removeWarehouse = (index) => {
  form.warehouses.splice(index, 1);
};

function loadCategories(query, setOptions) {
  fetch('/customercategories/getCustomerCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.name,
        }))
      );
    });
}

let submit = () => {
  if (!form.id) {
    form.post(route('customers.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
      },
    });
  } else {
    form.put(route('customers.update', { customer: form.id }), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        // Success handling
      },
    });
  }
};
</script>
