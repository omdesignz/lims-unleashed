<script setup>
import '../CommercialDocumentSurface.css';
import { optionRows } from '@/Composables/useCommercialDocumentOptions';
import { useCustomerSiteOptions } from '@/Composables/useCustomerSiteOptions';
import { prepareQuoteLine, selectQuoteCatalog, quoteLinePreview, saveQuoteForm } from '@/Composables/useQuoteAuthoring';
import FinancialObservationForm from '@/Components/documents/FinancialObservationForm.vue';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';
import {throttle} from "lodash";
import datePicker from '@/Components/date-picker.vue'
import { Trash2 as TrashIcon, CirclePlus as PlusCircleIcon, ClipboardCheck as ClipboardDocumentCheckIcon } from "@lucide/vue";
import { trans } from 'laravel-vue-i18n';


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object,
});


const updateDate = (e) => {
  form.due_date = e;
}

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
});

onMounted(() => {
  itemsWithSubTotal
});

const form = useForm({
    use_matrix_price: props.record.use_matrix_price,
    is_service: props.record.is_service,
    due_date: props.record.due_date,
    id: props.record.id,
    quote_no: props.record.quote_no,
    customer_id: props.record.customer_id,
    warehouse_id: props.record.warehouse_id,
    internal_ref: props.record.internal_ref,
    obs: props.record.obs,
    status: props.record.status,
    converted_to_invoice: props.record.converted_to_invoice,
    items: (props.record.items || []).map(prepareQuoteLine),
    total: props.record.total
});

const { loadWarehouses, loadingWarehouses } = useCustomerSiteOptions(form);

const addItem = () => {
    form.items.push(prepareQuoteLine({
        id: null,
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
    form.items.splice(index, 1);
}

function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        optionRows(results).map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadCustomers(query, setOptions) {
    fetch('/customers/getCustomer?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        optionRows(results).map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        optionRows(results).map(result => {
            return {
            value: result.id,
                catalog_type: 'parameter',
            label: result.name,
            price: result.price,
            tax_id: result.tax_id,
            charge_tax: result.charge_tax,
            tax_percentage: result.tax_percentage,
            exemption_id: result.exemption_id,
            exemption_code: result.exemption_code,
            };
        })
        );
    });
}

function loadMatrixes(query, setOptions) {
    fetch('/matrixes/getMatrix?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        optionRows(results).map(result => {
            return {
            value: result.id,
                catalog_type: 'matrix',
            label: result.description,
            price: result.fixed_price,
            tax_id: result.tax_id,
            charge_tax: result.charge_tax,
            tax_percentage: result.tax_percentage,
            exemption_id: result.exemption_id,
            exemption_code: result.exemption_code,
            };
        })
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

function loadUninvoiceProductsByWarehouse(warehouse_id)
{
    fetch('/labcodes/getWarehouseUninvoicedProducts?warehouse_id=' + warehouse_id + '&use_matrix_price=' + form.use_matrix_price)
    .then(response => response.json())
    .then(results => {
        form.items = results.map(prepareQuoteLine);
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
        optionRows(results).map(result => {
            return {
            value: result.collection_id,
            label: result.code,
            lab_code_id: result.id,
            };
        })
        );
    });
}

function selectLabCode(item, selection) {
    item.itemable_type = selection ? 'collectionproduct' : null;
}

const correctingItemId = ref(null);
const itemCorrectionErrors = ref({});

let submitItem = (item) => {
    if (correctingItemId.value !== null) {
      return;
    }
    const currentItem = item.item;
    const selectedCode = currentItem.itemable_id;
    correctingItemId.value = currentItem.id;
    itemCorrectionErrors.value = {};
    try {
      useForm({
        obs: currentItem.obs,
        unit_id: currentItem.unit_id,
        ...(selectedCode?.lab_code_id ? { lab_code_id: selectedCode.lab_code_id }
          : selectedCode === null ? { lab_code_id: null } : {}),
      })
      .put(route('quoteitems.update', {item: currentItem.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
          const record = page.props.record?.data || page.props.record;
          const savedItem = record?.items?.find((saved) => saved.id === currentItem.id);
          if (savedItem) {
            currentItem.itemable_id = savedItem.itemable_id;
            currentItem.itemable_type = savedItem.itemable_type;
          }
        },
        onError: (errors) => { itemCorrectionErrors.value = { id: currentItem.id, errors }; },
        onNetworkError: () => { itemCorrectionErrors.value = { id: currentItem.id, errors: { request: "Ligação interrompida. Tente novamente." } }; },
        onHttpException: () => { itemCorrectionErrors.value = { id: currentItem.id, errors: { request: "Não foi possível guardar a correcção." } }; },
        onFinish: () => { correctingItemId.value = null; },
      });
    } catch {
      correctingItemId.value = null;
      itemCorrectionErrors.value = { id: currentItem.id, errors: { request: "Não foi possível iniciar a correcção. Tente novamente." } };
    }
}


let submit = () => {
    if (correctingItemId.value !== null || props.record.invoice_id || props.record.converted_to_invoice) return;
    saveQuoteForm(form, route('quotes.update', { quote: form.id }), 'put', (page) => {
    const saved = page.props.record?.data || page.props.record;
    if (saved?.items) form.items = saved.items.map(prepareQuoteLine);
    form.defaults();
    });
};

const convertToInvoice = () => {

  useForm({
    id: props.record.id
  }).get(route('quotes.getConvertToInvoiceModal'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
}

const itemsWithSubTotal = computed(() => form.items.map(quoteLinePreview));
const subTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.total, 0).toFixed(2));
const taxTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.tax_amount, 0).toFixed(2));
const discountTotal = computed(() => itemsWithSubTotal.value.reduce((sum, line) => sum + line.discount_total, 0).toFixed(2));
const invoiceTotal = computed(() => (Number(subTotal.value) + Number(taxTotal.value)).toFixed(2));
const onSelectedItem = (wrapper) => selectQuoteCatalog(wrapper.item ?? wrapper);
</script>

<template>
<div>
<Head :title="`Editar proforma${record.quote_no ? ' · ' + record.quote_no : ''}`" />
<FinancialObservationForm v-if="record.invoice_id || record.converted_to_invoice" kind="quote" :record="record" />
<template v-else>
<div class="commercial-document-page border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
        <p v-if="Object.keys(form.errors).length" role="alert" class="text-sm text-red-600">{{ Object.values(form.errors).flat().join(' ') }}</p>
        <p v-if="form.items.some((line) => !line.catalog_type)" role="status" class="text-sm text-gray-600">Seleccione novamente os artigos sem tipo de catálogo antes de guardar.</p>
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.quotes.page_update_description') }} {{ form?.quote_no }}</p>
</div>

<form class="commercial-document-page" :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="type_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.due_date') }}</label>
            <div class="mt-2">
              <date-picker class="py-1.5" v-model.string="form.due_date" locale="pt" color="yellow" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
            </div>
            <p v-if="form.errors.due_date" class="mt-2 text-xs text-red-600" id="due_date-error">{{ form.errors.due_date }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.customer_id') }}</label>
            <div class="mt-2">
              <comboboxEnhanced :input-label="$t('gestlab.general.labels.quotes.customer_id')" :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers"/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.quotes.warehouse_id') }}</label>
            <div class="mt-2">
              <comboboxEnhanced :input-label="$t('gestlab.general.labels.quotes.warehouse_id')" :disableInput="!form.customer_id || loadingWarehouses" :loading="loadingWarehouses" :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="internal_ref" class="ds-field-label">{{ $t('gestlab.general.labels.quotes.internal_ref') }}</label>
            <div class="mt-2">
              <BaseInput v-model="form.internal_ref" type="text" name="internal_ref" id="internal_ref" class="ds-field" placeholder="" />
            </div>
            <p v-if="form.errors.internal_ref" class="mt-2 text-xs text-red-600" id="internal_ref-error">{{ form.errors.internal_ref }}</p>
          </div>

          <div class="sm:col-span-2">
            <div class="mt-8 flex items-center justify-end">
              <button v-if="!form.converted_to_invoice" @click="convertToInvoice" class="ds-button ds-button-primary px-4 py-2 text-sm">{{ $t('gestlab.general.labels.quotes.convert_to_invoice') }}</button>
            </div>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.items.length }} {{ $t('gestlab.general.labels.quotes.items') }}
          <button @click="addItem" class="ds-table-action ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.quotes.items_tagline') }} {{ form.quote_no }}</p>
      </div>





      <div class="">
        
        <div class="-mx-4 mt-8 flow-root sm:mx-0">
          <DataTable class="min-w-full">
            <colgroup>
              <col class="w-full sm:w-1/2" />
              <col class="sm:w-1/6" />
              <col class="sm:w-1/6" />
              <col class="sm:w-1/6" />
            </colgroup>
            <thead class="border-b border-gray-300 text-gray-900">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('gestlab.general.labels.quotes.item_id') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-center text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.quotes.qty') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.quotes.unit_price') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.quotes.discount') }}</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('gestlab.general.labels.quotes.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in itemsWithSubTotal" :key="index" class="border-b border-gray-200">
                <td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0 align-top">
                  <div class="text-gray-900">
                    <!-- <comboboxEnhanced v-if="form?.use_matrix_price" v-model="item.item.item_id" :load-options="loadMatrixes" @update:model-value="onSelectedItem(item)"/>
                    <comboboxEnhanced v-else v-model="item.item.item_id" :load-options="loadParameters" @update:model-value="onSelectedItem(item)"/> -->

                    <comboboxEnhanced
                        v-if="!form.is_service"
                        v-model="item.item.item_id" 
                        :load-options="loadProducts" 
                        @update:model-value="onSelectedItem(item)"
                        placeholder="Seleccione o produto..."
                        class="min-w-[250px]"
                    />
                    <comboboxEnhanced 
                        v-else 
                        v-model="item.item.item_id" 
                        :load-options="loadServices" 
                        @update:model-value="onSelectedItem(item)"
                        placeholder="Seleccione o serviço..."
                        class="min-w-[250px]"
                    />
                  </div>
                  <div class="mt-2 truncate text-gray-500">
                          <textarea v-model="item.item.obs" :placeholder="$t('gestlab.general.labels.quotes.obs')" rows="2" :name="`obs-${index+1}`" :id="`obs-${index+1}`" class="ds-field min-h-20 resize-none py-2 text-sm" />
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <BaseInput v-model="item.item.qty" type="number" step="0.01" min="0.01" :name="`qty-${index+1}`" :id="`qty-${index+1}`" class="ds-field text-center" placeholder="0.00" />
                    <div class="mt-2 text-gray-500 z-50">
                      <comboboxEnhanced v-model="item.item.unit_id" :load-options="loadUnits"/>
                    </div>
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <BaseInput v-model="item.item.agreed_unit_price" type="number" step=".01" :name="`unit_price-${index+1}`" :id="`unit_price-${index+1}`" class="ds-field text-right" placeholder="0.00" />
                  </div>
                    <div class="mt-2" v-if="!form.is_service">
                      <comboboxEnhanced v-model="item.item.itemable_id" :load-options="loadLabCodes" @update:model-value="selectLabCode(item.item, $event)" placeholder="CL"/>
                    </div>
                </td>
                <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0 align-top">
                  <div class="relative rounded-md shadow-sm">
                    <BaseInput v-model="item.item.discount_value" type="number" :name="`discount_amount-${index+1}`" :id="`discount_amount-${index+1}`" class="ds-field pr-28" placeholder="0.00" />
                    <div class="absolute inset-y-0 right-0 flex items-center">
                      <BaseSelect v-model="item.item.discount_mode" :id="`discount_id-${index+1}`" :name="`discount_id-${index+1}`" class="h-full rounded-xl border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 text-sm text-[var(--ds-text)] focus:outline-none focus:ring-2 focus:ring-[var(--ds-focus)]">
                        <option value="percentage">%</option><option value="fixed">Montante</option>
                      </BaseSelect>
                    </div>
                  </div>
                  <div class="mt-2 flex items-center justify-end">
                    <button type="button" :disabled="correctingItemId !== null" @click="submitItem(item)" class="ds-button ds-button-primary px-3 py-2 text-sm">{{ correctingItemId === item.id ? "A guardar..." : $t('gestlab.general.buttons.update') }}</button>
                  </div>
                  <p v-if="itemCorrectionErrors.id === item.id" role="alert" class="ds-field-error mt-2">{{ Object.values(itemCorrectionErrors.errors).flat().join(' ') }}</p>
                </td>
                <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0 align-top">
                  <div class="relative text-gray-900">
                      {{ parseFloat(item.total).toFixed(2) }}
                    <div class="mt-2">
                      <button @click="removeItem(index)" class="ds-table-action ml-auto">
                        <TrashIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-6 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.quotes.subtotal') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-6 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.quotes.subtotal') }}</th>
                <td class="pl-3 pr-6 pt-6 text-right text-sm text-gray-500 sm:pr-0">{{ (Number(subTotal) + Number(discountTotal)).toFixed(2) }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.quotes.discount_total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.quotes.discount_total') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ discountTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.quotes.tax_total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.quotes.tax_total') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ taxTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-semibold text-gray-900 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.quotes.total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-semibold text-gray-900 sm:hidden">{{ $t('gestlab.general.labels.quotes.total') }}</th>
                <td class="pl-3 pr-4 pt-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">
                  {{ invoiceTotal }}
                </td>
              </tr>
            </tfoot>
          </DataTable>
        </div>
      </div>


      
      <p v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</p>
    </div>

    <div class="sm:col-span-full">
      <label for="obs" class="ds-field-label">{{ $t('gestlab.general.labels.quotes.obs') }}</label>
      <div class="mt-2">
        <textarea v-model="form.obs" type="text" name="obs" id="obs" class="ds-field min-h-28 py-3" />
      </div>
      <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" id="obs">{{ form.errors.obs }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button type="button" :disabled="form.processing || correctingItemId !== null" @click="submit" class="ds-button ds-button-primary px-4 py-2 text-sm">{{ $t('gestlab.general.buttons.update') }}</button>
    </div>
  </form>
</template>
</div>
</template>
