<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CubeIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.products.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.products.page_create_description') }}
            <span v-if="form.name" class="font-semibold text-blue-900">
              {{ form.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ $t('gestlab.general.labels.products.new_product') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- PRODUCT DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CubeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.products.product_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- NAME FIELD -->
              <div class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.products.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.name ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.products.name_placeholder')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.products.price') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.price"
                    type="number"
                    step="0.01"
                    :class="[
                      'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                      form.errors.price ? 'ring-red-300 focus:ring-red-500' : ''
                    ]"
                    placeholder="0.00"
                    min="0"
                  />
                  <span class="absolute right-3.5 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">
                    AOA
                  </span>
                </div>
                <p v-if="form.errors.price" class="text-xs text-red-600">
                  {{ form.errors.price }}
                </p>
              </div>

              <!-- FIXED PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.products.fixed_price') }}
                </label>
                <div class="relative">
                  <input
                    v-model="form.fixed_price"
                    type="number"
                    step="0.01"
                    :class="[
                      'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                      form.errors.fixed_price ? 'ring-red-300 focus:ring-red-500' : ''
                    ]"
                    placeholder="0.00"
                    min="0"
                  />
                  <span class="absolute right-3.5 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">
                    AOA
                  </span>
                </div>
                <p v-if="form.errors.fixed_price" class="text-xs text-red-600">
                  {{ form.errors.fixed_price }}
                </p>
              </div>

              <!-- MATRIX SELECTION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.products.matrix_id') }}
                </label>
                <comboboxEnhanced
                  :hasError="form.errors.matrix_id"
                  v-model="form.matrix_id"
                  :load-options="loadMatrices"
                  :placeholder="$t('gestlab.general.labels.products.select_matrix')"
                  @update:model-value="onMatrixSelect"
                />
                <p v-if="form.errors.matrix_id" class="text-xs text-red-600">
                  {{ form.errors.matrix_id }}
                </p>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2 md:col-span-3">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.products.description') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  :class="[
                    'block w-full rounded-lg border-0 py-2.5 px-3.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:leading-6',
                    form.errors.description ? 'ring-red-300 focus:ring-red-500' : ''
                  ]"
                  :placeholder="$t('gestlab.general.labels.products.description_placeholder')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- TAX SECTION -->
              <div class="space-y-4 md:col-span-3 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <!-- CHARGE TAX TOGGLE -->
                  <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <div class="relative">
                        <input
                          v-model="form.charge_tax"
                          type="checkbox"
                          class="sr-only"
                          :id="'charge_tax'"
                        />
                        <div :class="[
                          'h-6 w-11 rounded-full transition-colors duration-200',
                          form.charge_tax ? 'bg-blue-900' : 'bg-gray-300'
                        ]">
                          <div :class="[
                            'absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform duration-200',
                            form.charge_tax ? 'transform translate-x-5' : ''
                          ]"></div>
                        </div>
                      </div>
                      <span class="text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.products.charge_tax') }}
                      </span>
                    </label>
                    <p v-if="form.errors.charge_tax" class="text-xs text-red-600">
                      {{ form.errors.charge_tax }}
                    </p>
                  </div>

                  <!-- EXEMPTION OR TAX TYPE -->
                  <div class="space-y-2" v-if="!form.charge_tax">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.products.exemption_id') }}
                    </label>
                    <div class="flex gap-2">
                      <comboboxEnhanced
                        :hasError="form.errors.exemption_id"
                        v-model="form.exemption_id"
                        :load-options="loadExemptions"
                        :placeholder="$t('gestlab.general.labels.products.select_exemption')"
                        @update:model-value="onExemptionSelect"
                        class="flex-1"
                      />
                    </div>
                    <p v-if="form.errors.exemption_id" class="text-xs text-red-600">
                      {{ form.errors.exemption_id }}
                    </p>
                    <p v-if="form.errors.exemption_code" class="text-xs text-red-600">
                      {{ form.errors.exemption_code }}
                    </p>
                  </div>

                  <div class="space-y-2" v-else>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.products.tax_id') }}
                    </label>
                    <comboboxEnhanced
                      :hasError="form.errors.tax_id"
                      v-model="form.tax_id"
                      :load-options="loadTaxTypes"
                      :placeholder="$t('gestlab.general.labels.products.select_tax_type')"
                      @update:model-value="onTaxTypeSelect"
                    />
                    <p v-if="form.errors.tax_id" class="text-xs text-red-600">
                      {{ form.errors.tax_id }}
                    </p>
                  </div>

                  <!-- WITHHOLD TAX TOGGLE -->
                  <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <div class="relative">
                        <input
                          v-model="form.withhold_tax"
                          type="checkbox"
                          class="sr-only"
                          :id="'withhold_tax'"
                        />
                        <div :class="[
                          'h-6 w-11 rounded-full transition-colors duration-200',
                          form.withhold_tax ? 'bg-blue-900' : 'bg-gray-300'
                        ]">
                          <div :class="[
                            'absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform duration-200',
                            form.withhold_tax ? 'transform translate-x-5' : ''
                          ]"></div>
                        </div>
                      </div>
                      <span class="text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.products.withhold_tax') }}
                      </span>
                    </label>
                    <p v-if="form.errors.withhold_tax" class="text-xs text-red-600">
                      {{ form.errors.withhold_tax }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.actions') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="showConfirmation = true"
              :disabled="!form.isDirty || form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                !form.isDirty || form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CubeIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.products.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.products.price') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.price }} AOA</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.products.fixed_price') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.fixed_price }} AOA</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.products.tax_percentage') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.tax_percentage }}%</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.products.tax_status') }}</span>
                  <span :class="[
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    form.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ form.charge_tax ? $t('gestlab.general.labels.products.tax_charged') : $t('gestlab.general.labels.products.tax_exempt') }}
                  </span>
                </div>
                <div v-if="form.matrix_id" class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.products.matrix_id') }}</span>
                  <span class="font-semibold text-blue-900">{{ selectedMatrixLabel }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.products.form_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.isDirty ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
              ]">
                {{ form.isDirty ? $t('gestlab.general.status.unsaved') : $t('gestlab.general.status.saved') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.products.validation_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                hasErrors ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
              ]">
                {{ hasErrors ? $t('gestlab.general.status.invalid') : $t('gestlab.general.status.valid') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.products.required_fields') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                form.name && form.price ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ form.name && form.price ? $t('gestlab.general.status.complete') : $t('gestlab.general.status.incomplete') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CONFIRMATION DIALOG -->
  <confirm-dialog 
    size="sm:max-w-2xl" 
    alignment="sm:items-start" 
    @canceled="showConfirmation = false" 
    @close="showConfirmation = false" 
    @confirmed="submit" 
    v-if="showConfirmation" 
    :title="$t('gestlab.actions.confirmation_dialog_title.default')" 
    :description="$t('gestlab.actions.confirmation_dialog_description.default')" 
    confirm="Sim" 
    cancel="Não"
  >
    <div class="mt-4">
      <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-gradient-to-r from-blue-900 to-blue-800 mb-4">
        {{ $t('gestlab.general.labels.summary') }}
      </div>
      
      <div class="bg-gray-50 rounded-lg p-4">
        <dl class="space-y-3">
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.name') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.name || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.description') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.description || '-' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.price') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.price }} AOA</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.fixed_price') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.fixed_price }} AOA</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.tax_percentage') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ form.tax_percentage }}%</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.tax_status') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">
              <span :class="[
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                form.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ form.charge_tax 
                  ? $t('gestlab.general.labels.products.tax_charged') 
                  : $t('gestlab.general.labels.products.tax_exempt') 
                }}
              </span>
            </dd>
          </div>
          <div v-if="form.matrix_id" class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.matrix_id') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">{{ selectedMatrixLabel }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.products.withhold_tax') }}</dt>
            <dd class="text-sm text-gray-900 font-medium">
              {{ form.withhold_tax ? $t('gestlab.general.buttons.yes') : $t('gestlab.general.buttons.no') }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </confirm-dialog>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import { CubeIcon, Cog6ToothIcon } from "@heroicons/vue/24/outline";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
  // Add any props you need
});

const form = useForm({
  name: '',
  description: '',
  price: 0,
  fixed_price: 0,
  tax_percentage: 0,
  exemption_id: null,
  exemption_code: '',
  tax_id: null,
  matrix_id: null,
  charge_tax: true,
  withhold_tax: true,
});

const showConfirmation = ref(false);
const selectedMatrixLabel = ref('');

const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0;
});

function loadMatrices(query, setOptions) {
  fetch('/matrixes/getMatrix?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => {
          return {
            value: result.id,
            label: result.code,
            description: result.description,
            price: result.price,
            fixed_price: result.fixed_price,
            tax_id: result.tax_id,
            exemption_id: result.exemption_id,
            charge_tax: result.charge_tax,
            withhold_tax: result.withhold_tax,
          };
        })
      );
    });
}

function loadExemptions(query, setOptions) {
  fetch('/taxexemptions/getExemption?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => {
          return {
            value: result.id,
            label: result.code,
            description: result.description,
            code: result.code
          };
        })
      );
    });
}

function loadTaxTypes(query, setOptions) {
  fetch('/taxtypes/getTaxType?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => {
          return {
            value: result.id,
            label: result.name,
            percent: result.percent,
          };
        })
      );
    });
}

const onMatrixSelect = (selectedMatrix) => {
  if (selectedMatrix) {
    selectedMatrixLabel.value = selectedMatrix.label;
    // Optionally copy matrix tax settings to product
    form.tax_id = selectedMatrix.tax_id;
    form.exemption_id = selectedMatrix.exemption_id;
    form.charge_tax = selectedMatrix.charge_tax;
    form.withhold_tax = selectedMatrix.withhold_tax;
    form.price = selectedMatrix.fixed_price;
    // You might want to copy other matrix properties as needed
  } else {
    selectedMatrixLabel.value = '';
  }
};

const onTaxTypeSelect = (selectedTaxType) => {
  if (selectedTaxType) {
    form.tax_percentage = selectedTaxType.percent;
  }
};

const onExemptionSelect = (selectedExemption) => {
    if (selectedExemption) {
        form.exemption_code = selectedExemption.code;
    }
}

const submit = () => {
  form.post(route('products.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
    onError: () => {
        showConfirmation.value = false;
    }
  });
};
</script>