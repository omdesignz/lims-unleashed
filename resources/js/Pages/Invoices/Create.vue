<script setup>
import '../CommercialDocumentSurface.css';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import {throttle} from "lodash";
import { 
  TrashIcon, 
  PlusCircleIcon, 
  ClipboardDocumentCheckIcon,
  ChevronUpIcon,
  CurrencyEuroIcon,
  UserIcon,
  BuildingOfficeIcon,
  DocumentTextIcon,
  TagIcon,
  BeakerIcon,
  CreditCardIcon,
  CalculatorIcon,
  InformationCircleIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";

defineOptions({
  layout: Layout
});

const props = defineProps({
    parameters: {
      type: Array,
      default: () => []
    },
    discount_categories: {
      type: Array,
      default: () => []
    }
});

let customerWarehouses = reactive([]);
const showDeleteConfirmation = ref(false);
const labcode_id = ref('');
const loadingWarehouses = ref(false);

const form = useForm({
    use_matrix_price: true,
    is_service: false,
    type_id: '',
    customer_id: '',
    warehouse_id: '',
    internal_ref: '',
    obs: '',
    labcode_id: '',
    assign_lab_code: false,
    items: []
});

watch(() => [form.customer_id.value], (currentValue, oldValue) => {
    if (!form.customer_id?.value) return;
    
    loadingWarehouses.value = true;
    fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.customer_id?.value)
    .then(response => response.json())
    .then(results => {
        customerWarehouses = results.map(result => ({
            value: result.id,
            label: result.address,
        }));
        form.warehouse_id = customerWarehouses[0] || '';
        loadingWarehouses.value = false;
    })
    .catch(() => {
        loadingWarehouses.value = false;
    });
});

const addItem = () => {
    form.items.push({
        invoice_id: '',
        itemable_type: '',
        itemable_id: '',
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
        obs: '',
        charge_tax: true,
    });
}

const removeItem = (index) => {
    if (confirm(trans('gestlab.actions.confirm_delete_item'))) {
        form.items.splice(index, 1);
    }
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

function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
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

let loadWarehouses = (query, setOptions) => {
    fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.customer_id?.value)
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

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.name,
            price: result.price,
            tax_id: result.tax_id,
            charge_tax: result.charge_tax,
            tax_percentage: result.tax_percentage,
            exemption_id: result.exemption_id,
            exemption_code: result.exemption_code,
        }))
        );
    });
}

function loadMatrixes(query, setOptions) {
    fetch('/matrixes/getMatrix?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.description,
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

function loadProducts(query, setOptions) {
    fetch('/products/getProduct?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
            results.map(result => ({
                value: result.id,
                label: result.name,
                price: result.matrix_parameters_price,
                tax_id: result.tax_id,
                charge_tax: result.charge_tax,
                tax_percentage: result.tax_percentage,
                exemption_id: result.exemption_id,
                exemption_code: result.exemption_code,
            }))
        );
    });
}

function loadServices(query, setOptions) {
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

function loadLabCodes(query, setOptions) {
    fetch('/labcodes/getCode?q=' + query)
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

function loadParametersBasedOnLabCode(code_id) {
    fetch('/labcodes/getCodeParameters?code_id=' + code_id + '&use_matrix_price=' + form.use_matrix_price) 
    .then(response => response.json())
    .then(results => {
        form.items = results;
    });
}

function loadProductsBasedOnLabCode(code_id) {
    fetch('/labcodes/getCodeProducts?code_id=' + code_id + '&use_matrix_price=' + form.use_matrix_price)
    .then(response => response.json())
    .then(results => {
        form.items = results;
    });
}

function loadUninvoiceProductsByWarehouse(warehouse_id)
{
    fetch('/labcodes/getWarehouseUninvoicedProducts?warehouse_id=' + warehouse_id + '&use_matrix_price=' + form.use_matrix_price)
    .then(response => response.json())
    .then(results => {
        form.items = results;
    });
}

let submit = () => {
    if(!form.id) {
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
        .post(route('invoices.store'), {
            preserveScroll: true,
            onError: () => {
                showDeleteConfirmation.value = false
            },
            onSuccess: () => {
                form.reset();
            },
        });
    } else {
        form.put(route('invoices.update',{invoice: form.id}), {
            preserveScroll: true,
            onError: () => {
                showDeleteConfirmation.value = false
            },
            onSuccess: () => {
                form.reset();
            },
        });
    }
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

<template>
    <div class="commercial-document-page space-y-8" :class="commercialDocumentThemeClasses">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <CreditCardIcon class="h-7 w-7 text-blue-900" />
                        {{ $t('gestlab.general.labels.invoices.page_title') }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        {{ $t('gestlab.general.labels.invoices.page_create_description') }}
                        <span v-if="form.customer_id?.label" class="font-semibold text-blue-900">
                            {{ form.customer_id.label }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
                        {{ form.items.length }} {{ $t('gestlab.general.labels.invoices.items') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Invoice Settings Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-6 py-4">
                <h2 class="ds-heading flex items-center gap-2 text-lg">
                    <DocumentTextIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.invoices.invoice_settings') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Invoice Type -->
                    <div class="space-y-2">
                        <label class="ds-field-label">
                            {{ $t('gestlab.general.labels.invoices.type_id') }}
                        </label>
                        <comboboxEnhanced 
                            :hasError="form.errors.type_id" 
                            v-model="form.type_id" 
                            :load-options="loadInvoiceCategories"
                            :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_type')"
                        />
                        <p v-if="form.errors.type_id" class="text-xs text-red-600">
                            {{ form.errors.type_id }}
                        </p>
                    </div>

                    <!-- Customer -->
                    <div class="space-y-2">
                        <label class="ds-field-label flex items-center gap-1">
                            <UserIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.invoices.customer_id') }}
                        </label>
                        <comboboxEnhanced 
                            :hasError="form.errors.customer_id" 
                            v-model="form.customer_id" 
                            :load-options="loadCustomers"
                            :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_customer')"
                        />
                        <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                            {{ form.errors.customer_id }}
                        </p>
                    </div>

                    <!-- Warehouse -->
                    <div class="space-y-2">
                        <label class="ds-field-label flex items-center gap-1">
                            <BuildingOfficeIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.invoices.warehouse_id') }}
                        </label>
                        <comboboxEnhanced 
                            :disableInput="!form.customer_id || loadingWarehouses"
                            :loading="loadingWarehouses"
                            :hasError="form.errors.warehouse_id" 
                            v-model="form.warehouse_id" 
                            :load-options="loadWarehouses"
                            :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_warehouse')"
                        />
                        <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                            {{ form.errors.warehouse_id }}
                        </p>
                    </div>

                    <!-- Internal Reference -->
                    <div class="space-y-2">
                        <label class="ds-field-label">
                            {{ $t('gestlab.general.labels.invoices.internal_ref') }}
                        </label>
                        <input 
                            v-model="form.internal_ref" 
                            type="text" 
                            class="ds-field"
                            :placeholder="$t('gestlab.general.labels.invoices.placeholders.enter_reference')"
                        />
                        <p v-if="form.errors.internal_ref" class="text-xs text-red-600">
                            {{ form.errors.internal_ref }}
                        </p>
                    </div>
                </div>

                <!-- Lab Code Section -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="ds-field-label">
                                {{ $t('gestlab.general.labels.invoices.labcode_id') }}
                            </label>
                            <comboboxEnhanced 
                                :hasError="form.errors.labcode_id" 
                                v-model="labcode_id" 
                                :load-options="loadLabCodes" 
                                @update:model-value="(e) => form.labcode_id = e.value"
                                :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_lab_code')"
                            />
                            <p v-if="form.errors.labcode_id" class="text-xs text-red-600">
                                {{ form.errors.labcode_id }}
                            </p>
                        </div>

                        <div v-if="labcode_id" class="md:col-span-2">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $t('gestlab.general.labels.invoices.assign_to_collection') }}
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="form.assign_lab_code = !form.assign_lab_code"
                                        :class="[
                                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                                            form.assign_lab_code ? 'bg-[rgb(var(--primary-700-rgb))]' : 'bg-[var(--ds-border)]'
                                        ]"
                                        :aria-checked="form.assign_lab_code"
                                        role="switch"
                                    >
                                        <span class="sr-only">{{ $t('gestlab.general.labels.invoices.assign_to_collection') }}</span>
                                        <span 
                                            :class="[
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                form.assign_lab_code ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        />
                                    </button>
                                </div>
                                
                                <button 
                                    v-if="labcode_id && !form.items.length" 
                                    @click="loadProductsBasedOnLabCode(labcode_id?.value)"
                                    class="ds-button ds-button-primary px-4 py-2 text-sm"
                                >
                                    <BeakerIcon class="h-4 w-4" />
                                    {{ $t('gestlab.general.labels.invoices.assign_lab_code') }}
                                </button>
                                
                            </div>
                        </div>

                        <div class="md:colspan-2">
                            <button 
                                    v-if="form.warehouse_id && !form.items.length" 
                                    @click="loadUninvoiceProductsByWarehouse(form.warehouse_id?.value)"
                                    class="ds-button ds-button-primary px-4 py-2 text-sm"
                                >
                                    <BeakerIcon class="h-4 w-4" />
                                    {{ $t('gestlab.general.labels.invoices.load_uninvoiced_products') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pricing Mode Toggle -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <TagIcon class="h-5 w-5 text-gray-500" />
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $t('gestlab.general.labels.invoices.invoice_by_matrix') }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    {{ form.use_matrix_price ? 'Using matrix pricing' : 'Using parameter pricing' }}
                                </p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="form.use_matrix_price = !form.use_matrix_price"
                            :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                                form.use_matrix_price ? 'bg-[rgb(var(--primary-700-rgb))]' : 'bg-[var(--ds-border)]'
                            ]"
                            :aria-checked="form.use_matrix_price"
                            role="switch"
                        >
                            <span class="sr-only">{{ $t('gestlab.general.labels.invoices.invoice_by_matrix') }}</span>
                            <span 
                                :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    form.use_matrix_price ? 'translate-x-5' : 'translate-x-0'
                                ]"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <CalculatorIcon class="h-5 w-5 text-blue-900" />
                        {{ $t('gestlab.general.labels.invoices.items') }}
                        <span class="text-sm font-normal text-gray-500 ml-2">
                            ({{ form.items.length }} {{ $t('gestlab.general.labels.invoices.items') }})
                        </span>
                    </h2>
                    <button 
                        @click="addItem" 
                        type="button"
                        class="ds-button ds-button-primary px-4 py-2.5 text-sm"
                    >
                        <PlusCircleIcon class="h-5 w-5" />
                        {{ $t('gestlab.general.buttons.add_item') }}
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $t('gestlab.general.labels.invoices.items_tagline') }}
                </p>
            </div>

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
                    class="ds-button ds-button-primary mt-6 px-4 py-2.5 text-sm"
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
                            class="hover:bg-gray-50 transition-colors duration-150"
                        >
                            <!-- Item Selection -->
                            <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm">
                                <div class="space-y-2">
                                    <!-- <comboboxEnhanced 
                                        v-if="form?.use_matrix_price" 
                                        v-model="item.item.item_id" 
                                        :load-options="loadMatrixes" 
                                        @update:model-value="onSelectedItem(item)"
                                        :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_matrix')"
                                        class="min-w-[250px]"
                                    /> -->

                                    <comboboxEnhanced  
                                        v-model="item.item.item_id" 
                                        :load-options="loadProducts" 
                                        @update:model-value="onSelectedItem(item)"
                                        :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_product')"
                                        class="min-w-[250px]"
                                    />

                                    <!-- <comboboxEnhanced 
                                        v-else 
                                        v-model="item.item.item_id" 
                                        :load-options="loadParameters" 
                                        @update:model-value="onSelectedItem(item)"
                                        :placeholder="$t('gestlab.general.labels.invoices.placeholders.select_parameter')"
                                        class="min-w-[250px]"
                                    /> -->
                                    <textarea 
                                        v-model="item.item.obs" 
                                        :placeholder="$t('gestlab.general.labels.invoices.obs')" 
                                        rows="1"
                                        class="ds-field min-h-16 resize-none py-2 text-sm"
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
                                        class="ds-field w-20 text-center"
                                    />
                                    <comboboxEnhanced 
                                        v-model="item.item.unit_id" 
                                        :load-options="loadUnits"
                                        :placeholder="$t('gestlab.general.labels.invoices.placeholders.unit')"
                                        class="w-32"
                                    />
                                </div>
                            </td>

                            <!-- Unit Price -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                                    <input 
                                        v-model="item.unit_price" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="ds-field w-32 text-right"
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
                                        class="ds-field w-24 text-right"
                                    />
                                    <select 
                                        v-model="item.item.discount_id" 
                                        class="ds-field px-2 py-1.5 text-sm"
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
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                        <p class="text-gray-400 mr-2">AOA</p>
                                    {{ parseFloat(item.total).toFixed(2) }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium">
                                <button 
                                    @click="removeItem(index)"
                                    type="button"
                                    class="ds-table-action-danger"
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
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>
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
                                    <!-- <CurrencyEuroIcon class="h-4 w-4" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>
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
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>
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
                                    <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>
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
                <label class="ds-field-label flex items-center gap-2">
                    <InformationCircleIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.invoices.obs') }}
                </label>
                <textarea 
                    v-model="form.obs" 
                    rows="3"
                    class="ds-field min-h-28 py-3"
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
                        <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                            <p class="text-gray-400 mr-2">AOA</p>
                        <span class="font-semibold">{{ invoiceTotal }} {{ $t('gestlab.general.labels.invoices.total') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button 
                    type="button" 
                    @click="showDeleteConfirmation = true"
                    :disabled="form.processing || form.items.length === 0"
                    :class="[
                        'inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                        form.processing || form.items.length === 0
                            ? 'cursor-not-allowed bg-[var(--ds-border)] text-[var(--ds-muted)]'
                            : 'ds-button-primary'
                    ]"
                >
                    <CreditCardIcon class="h-5 w-5" />
                    {{ form.processing ? $t('gestlab.general.buttons.processing') : $t('gestlab.general.buttons.submit') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <confirm-dialog 
        size="sm:max-w-2xl" 
        alignment="sm:items-start" 
        @canceled="showDeleteConfirmation=false" 
        @close="showDeleteConfirmation=false" 
        @confirmed="submit" 
        v-if="showDeleteConfirmation" 
        :title="$t('gestlab.actions.confirmation_dialog_title.default')" 
        :description="$t('gestlab.actions.confirmation_dialog_description.default')" 
        :confirm="$t('gestlab.general.buttons.yes')"
        :cancel="$t('gestlab.general.buttons.no')"
    >
        <div class="mt-4">
            <div class="ds-chip mb-4 inline-flex items-center gap-2 px-3 py-1">
                <InformationCircleIcon class="h-3 w-3" />
                {{ $t('gestlab.general.labels.summary') }}
            </div>
            
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
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.customer_id') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.warehouse_id') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.internal_ref') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.internal_ref }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
                
                <div class="w-full pt-2">
                    <div class="mx-auto w-full rounded-2xl bg-white">
                    <Disclosure v-slot="{ open }" v-for="(product, index) in itemsWithSubTotal" :key="index" v-if="itemsWithSubTotal.length">
                        <DisclosureButton
                        class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                        >
                        <span>{{ product.item.item_id?.label }}</span>
                        <ChevronUpIcon
                            :class="open ? 'rotate-180 transform' : ''"
                            class="h-5 w-5 text-white"
                        />
                        </DisclosureButton>
                        <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                        <div class="mt-6 border-t border-gray-100">
                            <dl class="divide-y divide-gray-100">
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.item_id') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.item_id?.label }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.qty') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.qty }} {{ product.item.unit_id?.label }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.unit_price') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.unit_price }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.discount') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.discount_amount }} {{ product.item.discount_id?.label }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.total') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parseFloat(product.total).toFixed(2) }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.obs') }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.obs }}</dd>
                            </div>
                            </dl>
                        </div>
                        </DisclosurePanel>
                    </Disclosure>
                    </div>
                </div>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.subtotal') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ subTotal }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.discount') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ discountTotal }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.tax_total') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ taxTotal }}</dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.invoices.total') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ invoiceTotal }}</dd>
                </div>
            </dl>
            </div>
        </div>

        </div>
        </div>
    </confirm-dialog>
</template>
