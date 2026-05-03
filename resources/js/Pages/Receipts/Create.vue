<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import {throttle} from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  ClipboardDocumentCheckIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  CurrencyEuroIcon,
  DocumentTextIcon,
  UserIcon,
  BuildingOfficeIcon,
  Cog6ToothIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  QueueListIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
    payment_categories: {
      type: Array,
      default: () => []
    },
});

// Reactive state
let customerWarehouses = reactive([]);
const showDeleteConfirmation = ref(false);
const invoice_id = ref('');
const isLoading = ref(false);

// Form
const form = useForm({
    invoice_id: '',
    customer_id: '',
    warehouse_id: '',
    internal_ref: '',
    obs: '',
    reason: '',
    items: []
});

// Computed
const totalPaidAmount = computed(() => {
  return itemsWithPendingValues.value.reduce((sum, item) => {
    return sum + parseFloat(item.item.paid_amount || 0);
  }, 0);
});

const totalPendingAmount = computed(() => {
  return itemsWithPendingValues.value.reduce((sum, item) => {
    return sum + parseFloat(item.pending_amount || 0);
  }, 0);
});

const itemsWithPendingValues = computed(() => {
  return form.items.map(item => ({
      item,
      receipt_id: item.receipt_id,
      user_id: item.user_id,
      amount_due: item.amount_due,
      invoice_id: item.invoice_id,
      payment_id: item.payment_id,
      invoice_pending_amount: lineInvoicePendingAmount(item),
      pending_amount: linePendingAmount(item),
      paid_amount: linePaidAmount(item)
  }));
});

const linePendingAmount = (item) => {
  const invoiceAmount = parseFloat(lineInvoicePendingAmount(item)) || 0;
  const paidAmount = parseFloat(linePaidAmount(item)) || 0;
  return (invoiceAmount - paidAmount).toFixed(2);
};

const lineInvoicePendingAmount = (item) => {
  return item['invoice_id']?.amount_due || 0;
};

const linePaidAmount = (item) => {
  return parseFloat(item['paid_amount']) || 0;
};

const isFormValid = computed(() => {
  return form.customer_id && 
         form.warehouse_id && 
         form.items.length > 0 &&
         form.items.every(item => item.invoice_id && item.payment_id && item.paid_amount > 0);
});

const formStatus = computed(() => {
  if (isFormValid.value && totalPendingAmount.value === 0) {
    return {
      label: 'complete',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else if (isFormValid.value) {
    return {
      label: 'partial',
      color: 'bg-yellow-100 text-yellow-800',
      icon: ExclamationTriangleIcon
    };
  } else {
    return {
      label: 'incomplete',
      color: 'bg-red-100 text-red-800',
      icon: ExclamationTriangleIcon
    };
  }
});

// Watchers
watch(() => form.customer_id.value, (customerId) => {
  if (customerId) {
    isLoading.value = true;
    fetch(`/warehouses/getWarehouse?q=&customer_id=${customerId}`)
      .then(response => response.json())
      .then(results => {
        customerWarehouses = results.map(result => ({
          value: result.id,
          label: result.address,
        }));
        
        if (customerWarehouses.length > 0) {
          form.warehouse_id = customerWarehouses[0];
        }
      })
      .finally(() => {
        isLoading.value = false;
      });
  }
});

// Methods
function loadInvoiceBasedOnId(e) {
  if (!e?.value) return;
  
  isLoading.value = true;
  fetch(`/creditnotes/getInvoiceData?id=${e.value}`)
    .then(response => response.json())
    .then(results => {
      form.items = results.items || [];
      form.customer_id = results.customer_id;
      form.warehouse_id = results.warehouse_id;
      form.internal_ref = results.internal_ref;
      form.obs = results.obs;
      form.status = results.status;
      form.use_matrix_price = results.use_matrix_price;
      form.is_original = results.is_original;
      form.exported_saft = results.exported_saft;
    })
    .finally(() => {
      isLoading.value = false;
    });
}

function loadInvoices(query, setOptions) {
  if (!query) return;
  
  fetch(`/invoices/getInvoice?q=${query}`)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: `${result.inv_no} - AOA${parseFloat(result.amount_due || 0).toFixed(2)}`,
          amount_due: result.amount_due,
        }))
      );
    });
}

function loadCustomers(query, setOptions) {
  if (!query) return;
  
  fetch(`/customers/getCustomer?q=${query}`)
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

function loadWarehouses(query, setOptions) {
  if (!form.customer_id?.value) {
    setOptions([]);
    return;
  }
  
  fetch(`/warehouses/getWarehouse?q=${query}&customer_id=${form.customer_id.value}`)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: result.address,
        }))
      );
    });
}

const addItem = () => {
  form.items.push({
    invoice_id: '',
    receipt_id: '',
    user_id: '',
    paid_amount: 0,
    pending_amount: 0,
    invoice_pending_amount: 0,
    obs: '',
    payment_id: '',
  });
};

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
};

const submit = () => {
  if (!isFormValid.value) return;
  
  showDeleteConfirmation.value = true;
};

const handleConfirmSubmit = () => {
  if(!form.id) {
    form.transform((data) => ({
      ...data,
      formatted_items: itemsWithPendingValues.value.map(item => ({
        invoice_id: item.item.invoice_id?.value,
        obs: item.item.obs,
        invoice_pending_amount: item.invoice_pending_amount,
        paid_amount: item.item.paid_amount,
        payment_id: item.item.payment_id?.value,
        pending_amount: item.pending_amount,
        user_id: item.user_id,
        receipt_id: item.receipt_id,
      }))
    }))
    .post(route('receipts.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        showDeleteConfirmation.value = false;
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  } else {
    form.put(route('receipts.update', {receipt: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteConfirmation.value = false;
      },
      onError: () => {
        showDeleteConfirmation.value = false;
      }
    });
  }
};
</script>

<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.receipts.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.receipts.page_create_description') }}
            <span v-if="form.customer_id?.label" class="font-semibold text-blue-900">
              {{ form.customer_id.label }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ form.items.length }} {{ $t('gestlab.general.labels.receipts.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- CUSTOMER INFORMATION SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.receipts.customer_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <!-- GRID FORM LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CUSTOMER FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.receipts.customer_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :hasError="form.errors.customer_id"
                  v-model="form.customer_id"
                  :load-options="loadCustomers"
                  :placeholder="$t('gestlab.general.labels.receipts.placeholders.select_customer')"
                  class="w-full"
                />
                <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>

              <!-- WAREHOUSE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.receipts.warehouse_id') }}
                  <span class="text-red-500">*</span>
                </label>
                <comboboxEnhanced 
                  :disableInput="!form.customer_id"
                  :hasError="form.errors.warehouse_id"
                  v-model="form.warehouse_id"
                  :load-options="loadWarehouses"
                  :placeholder="form.customer_id ? $t('gestlab.general.labels.receipts.placeholders.select_warehouse') : $t('gestlab.general.labels.receipts.placeholders.select_customer_first')"
                  class="w-full"
                />
                <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                  {{ form.errors.warehouse_id }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- PAYMENT ITEMS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <QueueListIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.receipts.items') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ form.items.length }} {{ $t('gestlab.general.labels.receipts.items') }})
                </span>
              </h2>
              <button 
                @click="addItem"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                <PlusCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.buttons.add_item') }}
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="form.items.length === 0" class="p-12 text-center">
            <QueueListIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.receipts.no_payment_items') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.receipts.add_payment_items_description') }}
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

          <!-- ITEMS GRID -->
          <div v-else class="grid grid-cols-1 gap-6 p-6">
            <!-- ITEM CARD -->
            <div 
              v-for="(item, index) in itemsWithPendingValues"
              :key="index"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- ITEM HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ item.invoice_id?.label || $t('gestlab.general.labels.receipts.invoice_payment') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.receipts.item') }} #{{ index + 1 }}
                      </p>
                    </div>
                  </div>
                  <button 
                    @click="removeItem(index)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded-full hover:bg-red-50"
                    :title="$t('gestlab.general.buttons.remove')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
              
              <!-- ITEM CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                  <!-- Invoice Selection -->
                  <div class="lg:col-span-2 space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.receipts.invoice_id') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced
                      v-model="item.item.invoice_id"
                      :load-options="loadInvoices"
                      :placeholder="$t('gestlab.general.labels.receipts.placeholders.select_invoice')"
                      class="w-full"
                    />
                    <div v-if="item.invoice_pending_amount > 0" class="text-xs text-gray-500">
                      {{ $t('gestlab.general.labels.receipts.invoice_pending') }}: 
                      <span class="font-medium text-blue-900">
                        AOA{{ parseFloat(item.invoice_pending_amount).toFixed(2) }}
                      </span>
                    </div>
                  </div>

                  <!-- Payment Method -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.receipts.payment_id') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <comboboxEnhanced
                      :options="payment_categories"
                      v-model="item.item.payment_id"
                      :placeholder="$t('gestlab.general.labels.receipts.placeholders.select_payment')"
                      class="w-full"
                    />
                  </div>

                  <!-- Paid Amount -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.receipts.paid_amount') }}
                      <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                      </div>
                      <input
                        v-model="item.item.paid_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="item.invoice_pending_amount"
                        class="pl-9 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                        placeholder="0.00"
                      />
                    </div>
                  </div>

                  <!-- Observations -->
                  <div class="md:col-span-2 lg:col-span-4 space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.receipts.obs') }}
                    </label>
                    <textarea
                      v-model="item.item.obs"
                      rows="2"
                      class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      :placeholder="$t('gestlab.general.labels.receipts.placeholders.observations')"
                    />
                  </div>

                  <!-- Amount Summary -->
                  <div class="md:col-span-2 lg:col-span-4 pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="space-y-1">
                        <div class="text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.receipts.invoice_total') }}
                        </div>
                        <div class="text-sm font-semibold text-gray-900">
                          AOA{{ parseFloat(item.invoice_pending_amount || 0).toFixed(2) }}
                        </div>
                      </div>
                      <div class="space-y-1">
                        <div class="text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.receipts.paid') }}
                        </div>
                        <div class="text-sm font-semibold text-green-600">
                          AOA{{ parseFloat(item.item.paid_amount || 0).toFixed(2) }}
                        </div>
                      </div>
                      <div class="space-y-1">
                        <div class="text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.receipts.remaining') }}
                        </div>
                        <div class="text-sm font-semibold" :class="{
                          'text-red-600': item.pending_amount > 0,
                          'text-green-600': item.pending_amount <= 0
                        }">
                          AOA{{ parseFloat(item.pending_amount || 0).toFixed(2) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- OBSERVATIONS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.receipts.additional_info') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.receipts.obs') }}
              </label>
              <textarea
                v-model="form.obs"
                rows="4"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                :placeholder="$t('gestlab.general.labels.receipts.placeholders.observations')"
              />
              <p v-if="form.errors.obs" class="text-xs text-red-600">
                {{ form.errors.obs }}
              </p>
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
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.summary') }}
              </h4>
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_items') }}</span>
                  <span class="font-semibold text-blue-900">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_paid') }}</span>
                  <span class="font-semibold text-green-600">
                    AOA{{ totalPaidAmount.toFixed(2) }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_pending') }}</span>
                  <span class="font-semibold text-red-600">
                    AOA{{ totalPendingAmount.toFixed(2) }}
                  </span>
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
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.receipts.form_status') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                formStatus.color
              ]">
                <component :is="formStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${formStatus.label}`) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.customer_selected') }}</span>
              <span :class="form.customer_id ? 'text-green-600' : 'text-red-600'">
                {{ form.customer_id ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.warehouse_selected') }}</span>
              <span :class="form.warehouse_id ? 'text-green-600' : 'text-red-600'">
                {{ form.warehouse_id ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.payments_added') }}</span>
              <span :class="form.items.length > 0 ? 'text-green-600' : 'text-red-600'">
                {{ form.items.length > 0 ? form.items.length : $t('gestlab.general.status.none') }}
              </span>
            </div>
          </div>
        </div>

        <!-- CUSTOMER INFO CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <UserIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.receipts.customer_info') }}
          </h3>
          <div class="space-y-3">
            <div v-if="form.customer_id" class="space-y-1">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.receipts.customer_id') }}
              </div>
              <div class="text-sm font-medium text-gray-900">
                {{ form.customer_id.label }}
              </div>
            </div>
            <div v-if="form.warehouse_id" class="space-y-1">
              <div class="text-xs text-gray-500">
                {{ $t('gestlab.general.labels.receipts.warehouse_id') }}
              </div>
              <div class="text-sm font-medium text-gray-900">
                {{ form.warehouse_id.label }}
              </div>
            </div>
            <div v-if="!form.customer_id" class="text-sm text-gray-500 italic">
              {{ $t('gestlab.general.labels.receipts.select_customer_first') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.receipts.auto_save') }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="addItem"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <PlusCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.add_another_item') }}
        </button>
        <button 
          @click="submit"
          :disabled="!isFormValid"
          type="button"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200',
            !isFormValid
              ? 'bg-gray-400 cursor-not-allowed'
              : 'bg-blue-900 hover:bg-blue-800'
          ]"
        >
          <ClipboardDocumentCheckIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.finalize') }}
        </button>
      </div>
    </div>
  </div>

  <!-- Confirmation Dialog -->
  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="handleConfirmSubmit"
    v-if="showDeleteConfirmation"
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    :confirm="$t('gestlab.general.buttons.confirm')"
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900">
        <CheckCircleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.receipts.receipt_summary') }}</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.receipts.customer_info') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.customer_id') }}</span>
                <span class="font-medium">{{ form.customer_id?.label }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.warehouse_id') }}</span>
                <span class="font-medium">{{ form.warehouse_id?.label }}</span>
              </div>
            </div>
          </div>
          
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-2">
              {{ $t('gestlab.general.labels.receipts.totals') }}
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_items') }}</span>
                <span class="font-medium">{{ form.items.length }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_paid') }}</span>
                <span class="font-medium text-green-600">AOA{{ totalPaidAmount.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('gestlab.general.labels.receipts.total_pending') }}</span>
                <span class="font-medium" :class="totalPendingAmount > 0 ? 'text-red-600' : 'text-green-600'">
                  AOA{{ totalPendingAmount.toFixed(2) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.receipts.obs') }}
          </h4>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-sm text-gray-700" v-if="form.obs">{{ form.obs }}</p>
            <p class="text-sm text-gray-500 italic" v-else>
              {{ $t('gestlab.general.labels.receipts.no_observations') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </confirm-dialog>
</template>

<style scoped>
/* Smooth transitions for better UX */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar styling */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Focus styles for accessibility */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}
</style>