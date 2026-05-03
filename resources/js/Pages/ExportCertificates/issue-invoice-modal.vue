<template>
  <Modal
    :title="modalTitle"
    maxWidth="4xl"
    @close="closeModal"
  >
    <div class="space-y-6">
      <!-- Modal Header -->
      <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-900 to-blue-800">
          <CheckBadgeIcon class="h-6 w-6 text-white" />
        </div>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">
          {{ modalTitle }}
        </h3>
        <p class="mt-2 text-sm text-gray-600">
          {{ modalDescription }}
        </p>
      </div>

      <form
        class="space-y-6"
        @submit.prevent="submit"
      >
        <!-- Invoice Type -->
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-6">

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Invoice Type -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $t('gestlab.general.labels.invoices.type_id') }}
                        </label>
                        <comboboxEnhanced 
                            :hasError="form.errors.type_id" 
                            v-model="form.type_id" 
                            :load-options="loadInvoiceCategories"
                            placeholder="Seleccione o tipo..."
                        />
                        <p v-if="form.errors.type_id" class="text-xs text-red-600">
                            {{ form.errors.type_id }}
                        </p>
                    </div>

                    <!-- Customer -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                            <UserIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.invoices.customer_id') }}
                        </label>
                        <comboboxEnhanced 
                            :hasError="form.errors.customer_id" 
                            v-model="form.customer_id" 
                            :load-options="loadCustomers"
                            placeholder="Seleccione o cliente..."
                            :disableInput="true"
                        />
                        <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                            {{ form.errors.customer_id }}
                        </p>
                    </div>

                    <!-- Warehouse -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                            <BuildingOfficeIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.invoices.warehouse_id') }}
                        </label>
                        <comboboxEnhanced 
                            :loading="loadingWarehouses"
                            :hasError="form.errors.warehouse_id" 
                            v-model="form.warehouse_id" 
                            :load-options="loadWarehouses"
                            placeholder="Seleccione o armazém..."
                            :disableInput="true"
                        />
                        <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                            {{ form.errors.warehouse_id }}
                        </p>
                    </div>

                    <!-- Internal Reference -->
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $t('gestlab.general.labels.invoices.internal_ref') }}
                        </label>
                        <input 
                            v-model="form.internal_ref" 
                            type="text" 
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
                            placeholder="Introduza a referência..."
                        />
                        <p v-if="form.errors.internal_ref" class="text-xs text-red-600">
                            {{ form.errors.internal_ref }}
                        </p>
                    </div>
                </div>

          
            <!-- Items Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-2">

            <div v-if="form.items.length === 0" class="p-12 text-center">
                <CreditCardIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.buttons.no_items') }}
                </h3>
                <p class="mt-2 text-sm text-gray-500">
                    {{ $t('gestlab.general.buttons.add_first_item') }}
                </p>
                <button 
                    @click="addItem" 
                    type="button"
                    class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
                >
                    <PlusCircleIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.buttons.add_first_item') }}
                </button>
            </div>

            <!-- Invoice Items Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.invoices.item_id') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.invoices.qty') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.invoices.unit_price') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.invoices.discount') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.invoices.total') }}
                            </th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-6">
                                <span class="sr-only">{{ $t('gestlab.general.labels.actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr 
                            v-for="(item, index) in itemsWithSubTotal" 
                            :key="index"
                            v-motion
                            :initial="{ opacity: 0, y: 10 }"
                            :enter="{ opacity: 1, y: 0 }"
                            :delay="index * 50"
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <!-- Item Selection -->
                            <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm">
                                <div class="space-y-2">

                                    <comboboxEnhanced  
                                        v-model="item.item.item_id" 
                                        :load-options="loadPaidServices" 
                                        @update:model-value="onSelectedItem(item)"
                                        placeholder="Seleccione o serviço..."
                                        class="min-w-[250px]"
                                    />
                                    
                                    <textarea 
                                        v-model="item.item.obs" 
                                        :placeholder="$t('gestlab.general.labels.invoices.obs')" 
                                        rows="1"
                                        class="block w-full border-0 border-b border-transparent p-0 pb-1 resize-none focus:ring-0 focus:border-blue-900 text-sm placeholder-gray-400"
                                    />
                                </div>
                            </td>

                            <!-- Quantity & Unit -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <div class="space-y-2">
                                    <input 
                                        v-model="item.item.qty" 
                                        type="number" 
                                        step="1"
                                        min="0"
                                        class="w-20 rounded-md border border-gray-300 px-3 py-1.5 text-center text-sm focus:border-blue-900 focus:ring-blue-900"
                                    />
                                    <comboboxEnhanced 
                                        v-model="item.item.unit_id" 
                                        :load-options="loadUnits"
                                        placeholder="Unidade"
                                        class="w-32"
                                    />
                                </div>
                            </td>

                            <!-- Unit Price -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                    <input 
                                        v-model="item.unit_price" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-32 rounded-md border border-gray-300 px-3 py-1.5 text-right text-sm focus:border-blue-900 focus:ring-blue-900"
                                    />
                                </div>
                            </td>

                            <!-- Discount -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <input 
                                        v-model="item.item.discount_amount" 
                                        type="number"
                                        min="0"
                                        class="w-24 rounded-md border border-gray-300 px-3 py-1.5 text-right text-sm focus:border-blue-900 focus:ring-blue-900"
                                    />
                                    <select 
                                        v-model="item.item.discount_id" 
                                        class="rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                                    >
                                        <option 
                                            v-for="(type, typeIndex) in props.discount_categories" 
                                            :key="typeIndex" 
                                            :value="type.value"
                                        >
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                            </td>

                            <!-- Total -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                    {{ parseFloat(item.total).toFixed(2) }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                <button 
                                    @click="removeItem(index)"
                                    type="button"
                                    class="text-red-600 hover:text-red-900 transition-colors duration-200 p-1 rounded-md hover:bg-red-50"
                                    :title="$t('gestlab.general.buttons.remove_item')"
                                >
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Invoice Summary -->
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.invoices.subtotal') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                    {{ subTotal }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.invoices.discount_total') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-red-600 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-4 w-4" />
                                    -{{ discountTotal }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.invoices.tax_total') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                    {{ taxTotal }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                        <tr class="bg-blue-50">
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-lg font-bold text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.invoices.total') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <CurrencyEuroIcon class="h-5 w-5" />
                                    {{ invoiceTotal }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Observations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                    <InformationCircleIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.invoices.obs') }}
                </label>
                <textarea 
                    v-model="form.obs" 
                    rows="3"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                    :placeholder="$t('gestlab.general.labels.invoices.placeholders.observations')"
                />
                <p v-if="form.errors.obs" class="text-xs text-red-600">
                    {{ form.errors.obs }}
                </p>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="flex items-center justify-between pt-6">
            <div class="text-sm text-gray-500">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        <span>{{ form.items.length }} {{ $t('gestlab.general.labels.invoices.items') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                        <span class="font-semibold">{{ invoiceTotal }} {{ $t('gestlab.general.labels.invoices.total') }}</span>
                    </div>
                </div>
            </div>
            
        </div>
            

        </div>



        <!-- Modal Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
          <div class="text-sm text-gray-500">
            {{ $t('gestlab.general.labels.quality_certificates.action_irreversible') }}
          </div>
          
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="closeModal"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              {{ $t('gestlab.general.buttons.cancel') }}
            </button>
            
            <button
              type="submit"
              :disabled="form.processing || !form.type_id"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                form.processing || !form.type_id
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              <template v-if="form.processing">
                <ArrowPathIcon class="h-4 w-4 animate-spin" />
                {{ $t('gestlab.general.buttons.processing') }}
              </template>
              <template v-else>
                <CheckBadgeIcon class="h-4 w-4" />
                {{ actionButtonText }}
              </template>
            </button>
          </div>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { watch, computed, onMounted } from 'vue'
import Modal from "@/Components/modal.vue";
import comboboxEnhanced from "@/Components/combobox-enhanced.vue";
import {
  CheckBadgeIcon,
  ArrowPathIcon,
  CreditCardIcon,
  CurrencyEuroIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  record: Object,
  action: String,
  title: String,
  url: String,
  discount_categories: {
      type: Array,
      default: () => []
  }
})

const emit = defineEmits(['close'])

const form = useForm({
  certificate_id: props.record.data?.id,
  type_id: null,
  customer_id: {
    value: props.record.data?.exporter_id,
    label: props.record.data?.exporter
  },
  warehouse_id: {
    value: props.record.data?.exporter_warehouse_id,
    label: props.record.data?.exporter_warehouse
  },
  internal_ref: '',
  obs: 'Certificado Nº: ' + props.record.data?.cert_no,
  use_matrix_price: false,
  assign_lab_code: false,
  is_service: true,
  items: []
})

const addItem = () => {
    form.items.push({
        invoice_id: '',
        itemable_type: 'export_certificate',
        itemable_id: props.record.data?.id,
        item_id: '',
        item_description: '',
        unit_id: '',
        exemption_id: '',
        exemption_code: '',
        qty: 1,
        unit_price: 0,
        total: 0,
        discount_id: 1,
        discount_amount: 0,
        discount_percentage: 0,
        tax_percentage: 0,
        tax_amount: 0,
        tax_id: null,
        obs: 'Certificado Nº: ' + props.record.data?.cert_no,
        charge_tax: true,
    });
}

// Computed properties
const modalTitle = computed(() => {
  return 'Emitir Factura';
});

const modalDescription = computed(() => {
  return 'Por favor, seleccione o tipo de factura para a devida emissão.';
});

const actionButtonText = computed(() => {
  return 'Emitir Factura';
});

// Watch for record changes
watch(
  () => props.record,
  (record) => {
    if (record) {
      form.certificate_id = record.data.id;
    }
  },
  { immediate: true },
)

// Methods
const closeModal = () => {
  emit('close');
}

const updateRecord = () => {
  const payload = {
    quote_id: form.id,
    type_id: form.type_id,
  };

  form.post(props.url, payload, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      emit('close');
    },
  });
}

let submit = () => {
        form.transform((data) => ({
            ...data,
            tax: taxTotal?.value,
            total: invoiceTotal?.value,
            sub_total: subTotal?.value,
            discount: discountTotal?.value,
            amount_due: data.type_id != 2 ? invoiceTotal?.value : 0,
            formatted_items: itemsWithSubTotal.value.map(item => ({
                invoice_id: item.invoice_id,
                item_id: item.item_id,
                itemable_id: item.itemable_id,
                itemable_type: item.itemable_type,
                item_description: item.description,
                discount_id: item.item.discount_id,
                discount_percentage: item.item.discount_id == 1 ? item.discount_amount / item.product_price * 100 : 0,
                qty: item.qty,
                obs: item.obs,
                exemption_id: item.exemption_id,
                exemption_code: item.exemption_code,
                tax_id: item.tax_id,
                tax_percentage: item.tax,
                charge_tax: item.item.charge_tax,
                unit_price: item.unit_price,
                unit_id: item.unit_id,
                product_price: item.product_price,
                total: item.total,
                discount_amount: item.discount_amount,
                tax_amount: item.tax_amount,
            }))
        }))
        .post(route('exportcertificates.issueInvoice', { certificate_id: props.record.data?.id}), {
            preserveScroll: true,
            onSuccess: () => {
                // form.reset();
            },
        });
  } 

onMounted(() => {
    addItem();
});

function loadInvoiceCategories(query, setOptions) {
    fetch('/invoicecategories/getInvoiceCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.code,
        }))
        );
    });
}

function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.code,
        }))
        );
    });
}

function loadPaidServices(query, setOptions) {
    fetch('/paid-services/getPaidService?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
            results.map(result => ({
                value: result.id,
                label: result.name,
                price: result.fixed_price,
                tax_id: result.tax_id,
                charge_tax: result.charge_tax,
                tax_percentage: result.tax_percentage,
                exemption_id: result.exemption_id,
                exemption_code: result.exemption_code,
            }))
        );
    });
}

const itemsWithSubTotal = computed(() => {
    return form.items.map(item => ({
        item,
        tax: lineTaxPercentage(item),
        qty: item.qty,
        invoice_id: item.invoice_id,
        itemable_type: item.itemable_type,
        itemable_id: item.itemable_id,
        obs: item.obs,
        item_id: item.item_id,
        tax_id: item?.item_id?.tax_id,
        item_description: item?.item_id?.label ?? item.item_description,
        tax_percentage: item.tax_percentage,
        unit_id: item.unit_id,
        exemption_id: item?.item_id?.exemption_id,
        exemption_code: item?.item_id?.exemption_code,
        charge_tax: lineChargeTax(item),
        unit_price: lineUnitPrice(item),
        product_price: onSelectedItem(item),
        total: lineSubTotalAmount(item) ? lineSubTotalAmount(item) : parseFloat(0),
        discount_amount: lineDiscountAmount(item),
        tax_amount: lineTaxAmount(item),
    }));
});

const lineUnitPrice = (item) => {
    return parseFloat(onSelectedItem(item) - parseFloat(lineDiscountAmount(item)));
}

const lineChargeTax = (item) => {
    return item.item_id?.charge_tax ?? false;
}

const lineTaxPercentage = (item) => {
    return item.item_id?.tax_percentage ?? 0;
}

const lineSubTotalAmount = (item) => {
    return ( (parseFloat(lineUnitPrice(item)) * parseFloat(item.qty)) );
}

const lineDiscountAmount = (item) => {
    if(item.discount_id == 2) {
        return parseFloat((item.discount_amount <= onSelectedItem(item) ? item.discount_amount : 0));
    } else if(item.discount_id == 1) {
        return parseFloat((item.discount_amount <= 100 ? item.discount_amount * onSelectedItem(item) / 100 : 0));
    } else {
        return parseFloat(0);
    }
}

const lineTaxAmount = (item) => {
    return ((parseFloat(lineSubTotalAmount(item)) * lineTaxPercentage(item) / 100));
}

const subTotal = computed(() => {
    return parseFloat(itemsWithSubTotal.value.map(item => item.total).reduce((prev, curr) => prev + curr, 0)).toFixed(2);
})

const taxTotal = computed(() => {
    return parseFloat(itemsWithSubTotal.value.map(item => (item.tax_amount ? item.tax_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2);
})

const discountTotal = computed(() => {
    return parseFloat(itemsWithSubTotal.value.map(item => (item.discount_amount ? item.discount_amount : 0)).reduce((prev, curr) => prev + curr, 0)).toFixed(2);
})

const invoiceTotal = computed(() => {
    return (parseFloat(subTotal.value) + parseFloat(taxTotal.value)).toFixed(2);
})

const onSelectedItem = (item) => {
    return item?.item_id?.price;
}
</script>

<style scoped>
/* Custom animations */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slideIn {
  animation: slideIn 0.3s ease-out;
}

/* Signature validation states */
.border-green-200 {
  border-color: #d1fae5;
}

.bg-green-50 {
  background-color: #f0fdf4;
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

/* Disabled state styling */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Smooth transitions */
button, div, input {
  transition: all 0.2s ease-in-out;
}

/* Loading spinner animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
