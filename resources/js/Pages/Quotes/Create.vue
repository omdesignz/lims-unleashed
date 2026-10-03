<script setup>
import '../CommercialDocumentSurface.css';
import { prepareQuoteLine, selectQuoteCatalog, quoteLinePreview, saveQuoteForm } from '@/Composables/useQuoteAuthoring';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { optionRows } from "@/Composables/useCommercialDocumentOptions";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import {throttle} from "lodash";
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import {
  Trash2 as TrashIcon,
  CirclePlus as PlusCircleIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  ChevronUp as ChevronUpIcon,
  Euro as CurrencyEuroIcon,
  User as UserIcon,
  Building as BuildingOfficeIcon,
  FileText as DocumentTextIcon,
  Tag as TagIcon,
  FlaskConical as BeakerIcon,
  CreditCard as CreditCardIcon,
  Calculator as CalculatorIcon,
  Info as InformationCircleIcon,
  CircleAlert as ExclamationCircleIcon,
  Receipt as ReceiptRefundIcon,
  Copy as DocumentDuplicateIcon,
  Calendar as CalendarIcon,
  FileUp as DocumentArrowUpIcon,
} from "@lucide/vue";
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

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

const form = useForm({
    use_matrix_price: true,
    is_service: false,
    due_date: null,
    customer_id: '',
    warehouse_id: '',
    internal_ref: '',
    obs: '',
    items: []
});

const updateDate = (e) => {
  form.due_date = e;
}

let warehouseLookupVersion = 0;
watch(() => form.customer_id?.value, async (customerId) => {
    const version = ++warehouseLookupVersion;
    form.warehouse_id = null;
    customerWarehouses = [];
    if (!customerId) return;
    try {
        const response = await fetch('/warehouses/getWarehouse?customer_id=' + encodeURIComponent(customerId));
        if (!response.ok) throw new Error('Warehouse lookup failed');
        const results = await response.json();
        if (version !== warehouseLookupVersion) return;
        customerWarehouses = optionRows(results).map((result) => ({ value: result.id, label: result.address }));
        form.warehouse_id = customerWarehouses[0] ?? null;
    } catch {
        if (version === warehouseLookupVersion) form.setError('warehouse_id', 'Não foi possível carregar os locais. Tente seleccionar novamente.');
    }
});

const addItem = () => {
    form.items.push(prepareQuoteLine({
        quote_id: '',
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
    }));
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
            optionRows(results).map(result => ({
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
            optionRows(results).map(result => ({
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
            optionRows(results).map(result => ({
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
            optionRows(results).map(result => ({
                value: result.id,
                catalog_type: 'parameter',
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
            optionRows(results).map(result => ({
                value: result.id,
                catalog_type: 'matrix',
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
            optionRows(results).map(result => ({
                value: result.id,
                catalog_type: 'product',
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
            optionRows(results).map(result => ({
                value: result.id,
                catalog_type: 'paid_service',
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
            optionRows(results).map(result => ({
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
        form.items = results.map(prepareQuoteLine);
    });
}

function loadProductsBasedOnLabCode(code_id) {
    fetch('/labcodes/getCodeProducts?code_id=' + code_id + '&use_matrix_price=' + form.use_matrix_price)
    .then(response => response.json())
    .then(results => {
        form.items = results.map(prepareQuoteLine);
    });
}

function loadUninvoiceProductsByWarehouse(warehouse_id)
{
    fetch('/labcodes/getWarehouseUninvoicedProducts?warehouse_id=' + warehouse_id + '&use_matrix_price=' + form.use_matrix_price)
    .then(response => response.json())
    .then(results => {
        form.items = results.map(prepareQuoteLine);
    });
}

let submit = () => saveQuoteForm(form, route('quotes.store'), 'post', () => form.reset());

const itemsWithSubTotal = computed(() => form.items.map(quoteLinePreview));
const subTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.total, 0).toFixed(2));
const taxTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.tax_amount, 0).toFixed(2));
const discountTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.discount_total, 0).toFixed(2));
const quoteTotal = computed(() => (Number(subTotal.value) + Number(taxTotal.value)).toFixed(2));
const onSelectedItem = (wrapper) => selectQuoteCatalog(wrapper.item ?? wrapper);
</script>

<template>
    <div class="commercial-document-page commercial-document-create min-w-0 space-y-5 overflow-x-clip pb-10" :class="commercialDocumentThemeClasses">
        <p v-if="Object.keys(form.errors).length" role="alert" class="text-sm text-red-600">{{ Object.values(form.errors).flat().join(' ') }}</p>
        <p v-if="form.items.some((line) => !line.catalog_type)" role="status" class="text-sm text-gray-600">Seleccione novamente os artigos sem tipo de catálogo antes de guardar.</p>
        <!-- Header -->
        <header class="commercial-document-header px-0 pb-5 pt-1">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <DocumentArrowUpIcon class="h-7 w-7 text-blue-900" />
                        {{ $t('gestlab.general.labels.quotes.page_title') }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        {{ $t('gestlab.general.labels.quotes.page_create_description') }}
                        <span v-if="form.customer_id?.label" class="font-semibold text-blue-900">
                            {{ form.customer_id.label }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
                        {{ form.items.length }} {{ $t('gestlab.general.labels.quotes.items') }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Quote Settings Card -->
        <section class="ds-panel commercial-document-section overflow-hidden">
            <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-6 py-4">
                <h2 class="ds-heading flex items-center gap-2 text-lg">
                    <DocumentTextIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.quotes.quote_settings') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Due Date -->
                    <div class="space-y-2">
                        <label class="ds-field-label flex items-center gap-1">
                            <CalendarIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.quotes.due_date') }}
                        </label>
                        <date-picker-enhanced
                            v-model.string="form.due_date"
                            locale="pt"
                            color="blue"
                            mode="date"
                            :masks="masks"
                            class="w-full"
                            :popover-placement="'bottom-start'"
                        />
                        <p v-if="form.errors.due_date" class="text-xs text-red-600">
                            {{ form.errors.due_date }}
                        </p>
                    </div>

                    <!-- Customer -->
                    <div class="space-y-2">
                        <label class="ds-field-label flex items-center gap-1">
                            <UserIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.quotes.customer_id') }}
                        </label>
                        <comboboxEnhanced
                            :hasError="form.errors.customer_id"
                            v-model="form.customer_id"
                            :load-options="loadCustomers"
                            :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_customer')"
                        />
                        <p v-if="form.errors.customer_id" class="text-xs text-red-600">
                            {{ form.errors.customer_id }}
                        </p>
                    </div>

                    <!-- Warehouse -->
                    <div class="space-y-2">
                        <label class="ds-field-label flex items-center gap-1">
                            <BuildingOfficeIcon class="h-4 w-4" />
                            {{ $t('gestlab.general.labels.quotes.warehouse_id') }}
                        </label>
                        <comboboxEnhanced
                            :disableInput="!form.customer_id || loadingWarehouses"
                            :loading="loadingWarehouses"
                            :hasError="form.errors.warehouse_id"
                            v-model="form.warehouse_id"
                            :load-options="loadWarehouses"
                            :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_warehouse')"
                        />
                        <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
                            {{ form.errors.warehouse_id }}
                        </p>
                    </div>

                    <!-- Internal Reference -->
                    <div class="space-y-2">
                        <label class="ds-field-label">
                            {{ $t('gestlab.general.labels.quotes.internal_ref') }}
                        </label>
                        <BaseInput
                            v-model="form.internal_ref"
                            type="text"
                            class="ds-field"
                            :placeholder="$t('gestlab.general.labels.quotes.placeholders.enter_reference')"
                        />
                        <p v-if="form.errors.internal_ref" class="text-xs text-red-600">
                            {{ form.errors.internal_ref }}
                        </p>
                    </div>
                </div>

                <!-- Lab Code Section -->
                <!-- <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ $t('gestlab.general.labels.quotes.labcode_id') }}
                            </label>
                            <comboboxEnhanced
                                :hasError="form.errors.labcode_id"
                                v-model="labcode_id"
                                :load-options="loadLabCodes"
                                :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_lab_code')"
                            />
                            <p v-if="form.errors.labcode_id" class="text-xs text-red-600">
                                {{ form.errors.labcode_id }}
                            </p>
                        </div>

                        <div v-if="labcode_id && !form.items.length" class="md:col-span-2 flex items-end">
                            <button
                                @click="loadProductsBasedOnLabCode(labcode_id?.value)"
                                class="ds-button ds-button-secondary px-4 py-2 text-sm"
                            >
                                <BeakerIcon class="h-4 w-4" />
                                {{ $t('gestlab.general.labels.quotes.assign_lab_code') }}
                            </button>
                        </div>

                        <div class="md:colspan-2">
                            <button
                                    v-if="form.warehouse_id && !form.items.length"
                                    @click="loadUninvoiceProductsByWarehouse(form.warehouse_id?.value)"
                                    class="ds-button ds-button-secondary px-4 py-2 text-sm"
                                >
                                    <BeakerIcon class="h-4 w-4" />
                                    {{ $t('gestlab.general.labels.invoices.load_uninvoiced_products') }}
                            </button>
                        </div>

                    </div>
                </div> -->

                <!-- Item Mode Toggle -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <TagIcon class="h-5 w-5 text-gray-500" />
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $t('gestlab.general.labels.quotes.is_service') }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    {{ form.is_service ? 'A Facturar Um Serviço' : 'A Facturar Um Producto' }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="form.is_service = !form.is_service"
                            :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                                form.is_service ? 'bg-[rgb(var(--primary-700-rgb))]' : 'bg-[var(--ds-border)]'
                            ]"
                            :aria-checked="form.is_service"
                            role="switch"
                        >
                            <span class="sr-only">{{ $t('gestlab.general.labels.quotes.is_service') }}</span>
                            <span
                                :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                    form.is_service ? 'translate-x-5' : 'translate-x-0'
                                ]"
                            />
                        </button>
                    </div>
                </div>

                <!-- Pricing Mode Toggle -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <TagIcon class="h-5 w-5 text-gray-500" />
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $t('gestlab.general.labels.quotes.invoice_by_matrix') }}
                                </h3>
                                <p class="text-xs text-gray-500">
                                    {{ form.use_matrix_price ? 'A Usar o Preço da Matriz' : 'A Usar o Preço do Producto' }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            :disabled="form.is_service"
                            @click="form.use_matrix_price = !form.use_matrix_price"
                            :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]',
                                form.use_matrix_price ? 'bg-[rgb(var(--primary-700-rgb))]' : 'bg-[var(--ds-border)]'
                            ]"
                            :aria-checked="form.use_matrix_price"
                            role="switch"
                        >
                            <span class="sr-only">{{ $t('gestlab.general.labels.quotes.invoice_by_matrix') }}</span>
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
        </section>

        <!-- Items Section -->
        <section class="ds-panel commercial-document-section overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <CalculatorIcon class="h-5 w-5 text-blue-900" />
                        {{ $t('gestlab.general.labels.quotes.items') }}
                        <span class="text-sm font-normal text-gray-500 ml-2">
                            ({{ form.items.length }} {{ $t('gestlab.general.labels.quotes.items') }})
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
                    {{ $t('gestlab.general.labels.quotes.items_tagline') }}
                </p>
            </div>

            <div v-if="form.items.length === 0" class="p-12 text-center">
                <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-gray-300" />
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

            <!-- Quote Items Table -->
            <div v-else class="overflow-x-auto">
                <DataTable class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.quotes.item_id') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.quotes.qty') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.quotes.unit_price') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.quotes.discount') }}
                            </th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                                {{ $t('gestlab.general.labels.quotes.total') }}
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
                                        :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_matrix')"
                                        class="min-w-[250px]"
                                    /> -->
                                    <comboboxEnhanced
                                        v-if="!form.is_service"
                                        v-model="item.item.item_id"
                                        :load-options="loadProducts"
                                        @update:model-value="onSelectedItem(item)"
                                        :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_product')"
                                        class="min-w-[250px]"
                                    />
                                    <comboboxEnhanced
                                        v-else
                                        v-model="item.item.item_id"
                                        :load-options="loadServices"
                                        @update:model-value="onSelectedItem(item)"
                                        :placeholder="$t('gestlab.general.labels.quotes.placeholders.select_parameter')"
                                        class="min-w-[250px]"
                                    />
                                    <textarea
                                        v-model="item.item.obs"
                                        :placeholder="$t('gestlab.general.labels.quotes.obs')"
                                        rows="1"
                                        class="ds-field min-h-16 resize-none py-2 text-sm"
                                    />
                                </div>
                            </td>

                            <!-- Quantity & Unit -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                <div class="space-y-2">
                                    <BaseInput
                                        v-model="item.item.qty"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="ds-field w-20 text-center"
                                    />
                                    <comboboxEnhanced
                                        v-model="item.item.unit_id"
                                        :load-options="loadUnits"
                                        :placeholder="$t('gestlab.general.labels.quotes.placeholders.unit')"
                                        class="w-32"
                                    />
                                </div>
                            </td>

                            <!-- Unit Price -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                                    <BaseInput
                                        v-model="item.item.agreed_unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="ds-field w-32 text-right"
                                        :disabled="form.processing"
                                    />
                                </div>
                            </td>

                            <!-- Discount -->
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <BaseInput
                                        v-model="item.item.discount_value"
                                        type="number"
                                        min="0"
                                        class="ds-field w-24 text-right"
                                    />
                                    <BaseSelect
                                        v-model="item.item.discount_mode"
                                        class="ds-field px-2 py-1.5 text-sm"
                                    >
                                        <option
                                            v-for="(type, typeIndex) in props.discount_categories"
                                            :key="typeIndex"
                                            :value="type.value"
                                        >
                                            {{ type.label }}
                                        </option>
                                    </BaseSelect>
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

                    <!-- Quote Summary -->
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.quotes.subtotal') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                                    {{ (Number(subTotal) + Number(discountTotal)).toFixed(2) }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-gray-900 text-right">
                                {{ $t('gestlab.general.labels.quotes.discount_total') }}
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
                                {{ $t('gestlab.general.labels.quotes.tax_total') }}
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
                                {{ $t('gestlab.general.labels.quotes.total') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-lg font-bold text-blue-900 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- <CurrencyEuroIcon class="h-5 w-5" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                                    {{ quoteTotal }}
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </DataTable>
            </div>
        </section>

        <!-- Observations -->
        <section class="ds-panel commercial-document-section p-5 sm:p-6">
            <div class="space-y-2">
                <label class="ds-field-label flex items-center gap-2">
                    <InformationCircleIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.quotes.obs') }}
                </label>
                <textarea
                    v-model="form.obs"
                    rows="3"
                    class="ds-field min-h-28 py-3"
                    :placeholder="$t('gestlab.general.labels.quotes.placeholders.observations')"
                />
                <p v-if="form.errors.obs" class="text-xs text-red-600">
                    {{ form.errors.obs }}
                </p>
            </div>
        </section>

        <!-- Submit Section -->
        <div class="commercial-document-command flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        <span>{{ form.items.length }} {{ $t('gestlab.general.labels.quotes.items') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- <CurrencyEuroIcon class="h-4 w-4 text-gray-400" /> -->
                                    <p class="text-gray-400 mr-2">AOA</p>

                        <span class="font-semibold">{{ quoteTotal }} {{ $t('gestlab.general.labels.quotes.total') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    @click="showDeleteConfirmation = true"
                    :disabled="form.processing || form.items.length === 0"
                    :class="[
                        'ds-button px-6 py-3',
                        form.processing || form.items.length === 0
                            ? 'cursor-not-allowed bg-[var(--ds-border)] text-[var(--ds-muted)]'
                            : 'ds-button-primary'
                    ]"
                >
                    <DocumentArrowUpIcon class="h-5 w-5" />
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
                    <!-- <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div> -->
                    <div>
                        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
                        <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
                        </div>
                        <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.customer_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.warehouse_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.internal_ref') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.internal_ref }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">

                            <div class="w-full pt-2">
                                <div class="mx-auto w-full rounded-lg bg-[var(--ds-panel-raised)]">
                                <Disclosure v-slot="{ open }" v-for="(product, index) in itemsWithSubTotal" :key="index" v-if="itemsWithSubTotal.length">
                                    <DisclosureButton
                                    class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                                    >
                                    <span>{{ product.item_description }}</span>
                                    <ChevronUpIcon
                                        :class="open ? 'rotate-180 transform' : ''"
                                        class="h-5 w-5 text-white"
                                    />
                                    </DisclosureButton>
                                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                                    <div class="mt-6 border-t border-gray-100">
                                        <dl class="divide-y divide-gray-100">
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.item_id') }}</dt>
                                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.item_id?.label }}</dd>
                                        </div>
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.qty') }}</dt>
                                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.qty }} {{ product.item.unit_id?.label }}</dd>
                                        </div>
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.unit_price') }}</dt>
                                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.unit_price }}</dd>
                                        </div>
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.discount') }}</dt>
                                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.discount_value }} {{ product.item.discount_mode === 'percentage' ? '%' : 'Montante' }}</dd>
                                        </div>
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.total') }}</dt>
                                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parseFloat(product.total).toFixed(2) }}</dd>
                                        </div>
                                        <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.obs') }}</dt>
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
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.subtotal') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ (Number(subTotal) + Number(discountTotal)).toFixed(2) }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.discount') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ discountTotal }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.tax_total') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ taxTotal }}</dd>
                            </div>
                            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.total') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ quoteTotal }}</dd>
                            </div>
                        </dl>
                        </div>
                    </div>

                    </div>

        </div>
    </confirm-dialog>
</template>
