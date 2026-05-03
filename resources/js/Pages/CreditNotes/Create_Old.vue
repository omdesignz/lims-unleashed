<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, reactive, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";


defineOptions({
  layout: Layout
});

const props = defineProps({
    reasons: {
      type: Array,
      default: () => []
    },
    discount_categories: {
      type: Array,
      default: () => []
    },
    invoice_record: Object
});

let customerWarehouses = reactive([]);

const form = useForm({
    use_matrix_price: true,
    invoice_id: '',
    customer_id: '',
    warehouse_id: '',
    internal_ref: '',
    obs: '',
    reason: '',
    items: []
});

watch(() => [form.customer_id.value], (currentValue, oldValue) => {
        console.log(currentValue);

        fetch('/warehouses/getWarehouse?q=' + '&customer_id=' + form.customer_id?.value)
        .then(response => response.json())
        .then(results => {

            customerWarehouses = results.map(result => {
                return {
                value: result.id,
                label: result.address,
                }
            });

            form.warehouse_id = customerWarehouses[0];

        });

        }
      );

const showDeleteConfirmation = ref(false);

const invoice_id = ref('');

// watch(invoice_id, (newValue, oldValue) => {
//   // executed immediately, then again when `source` changes
//   console.log('New valye ' + JSON.stringify(newValue));

//   router.reload({
//     // method: 'get',
//     data: {
//       id: newValue?.value,
//     },
//     only: ['invoice_record']
// })
// }, { immediate: true })

function loadInvoiceBasedOnId(e) {
  fetch('/creditnotes/getInvoiceData?id=' + e.value)
    .then(response => response.json())
    .then(results => {
        form.items = results.items;
        form.customer_id = results.customer_id;
        form.warehouse_id = results.warehouse_id;
        form.internal_ref = results.internal_ref;
        form.obs = results.obs;
        form.status = results.status;
        form.use_matrix_price = results.use_matrix_price;
        form.is_original = results.is_original;
        form.exported_saft = results.exported_saft;
        console.log(results)
    });
  
  // console.log(form.items);
}

const getInvoiceData = (event) => {

router.get(route('creditnotes.create'), {
  id: event.value
}, {
  preserveState: true
});  

// router.reload({
//     // method: 'get',
//     data: {
//       id: event.value,
//     },
    
//     only: ['invoice_record']
// })
// form.items.value = props?.invoice_record[0]?.value ?? [];
console.log(props?.invoice_record)

    // router.reload({data: event.value})
  // router.reload({ only: ['invoice_record'] });
  //   router.reload({
  //     id: event.value
  // },{
  //   only: ['invoice_record']
  // })

  // form.items = props.invoice_record.items;

  // console.log(props.invoice_record.value)
}

const addItem = () => {
    form.items.push({
        use_matrix_price: false,
        note_id: '',
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
    form.items.splice(index, 1);
}

function loadUnits(query, setOptions) {
    fetch('/units/getUnit?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadInvoices(query, setOptions) {
    fetch('/invoices/getInvoice?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.inv_no,
            };
        })
        );
    });
}

function loadInvoiceCategories(query, setOptions) {
    fetch('/invoicecategories/getInvoiceCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
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
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

let loadWarehouses = (query, setOptions) => {
    fetch('/warehouses/getWarehouse?q=' + query + '&customer_id=' + form.customer_id?.value)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.address,
            };
        })
        )
    });
}

function loadParameters(query, setOptions) {
    fetch('/parameters/getParameter?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
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
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
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

function loadLabCodes(query, setOptions) {
    fetch('/labcodes/getCode?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadParametersBasedOnLabCode(code_id) {
  fetch('/labcodes/getCodeParameters?code_id=' + code_id)
    .then(response => response.json())
    .then(results => {
        form.items = results
    });
}


let submit = () => {

if(!form.id) {
  form.transform((data) => ({
    ...data,
    tax: taxTotal?.value,
    total: noteTotal?.value,
    sub_total: subTotal?.value,
    discount: discountTotal?.value,
    amount_due: data.type_id != 2 ? noteTotal?.value : 0,
    formatted_items: itemsWithSubTotal.value.map(item => ({
          note_id: item.note_id,
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
  .post(route('creditnotes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('creditnotes.update',{invoice: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
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
}))

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

const noteTotal = computed(() => {
  return (parseFloat(subTotal.value) + parseFloat(taxTotal.value)).toFixed(2);
})

const onSelectedItem = (item) => {
      return item?.item_id?.price;
    }
  

</script>

<template>
<div class="border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.credit_notes.page_create_description') }} {{ form?.customer_id?.label }}</p>
</div>

<div class="border-b border-gray-200 pb-5 flex items-center">
    <h3 class="text-sm font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.invoice_by_matrix') }}</h3>
    <!-- <p class="ml-2 max-w-4xl text-sm text-gray-500">A emitir factura para o cliente: {{ form?.customer_id?.label }}</p> -->
    <p class="ml-2 max-w-4xl text-sm text-gray-500">
        <button type="button" @click="form.use_matrix_price = !form.use_matrix_price" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="form.use_matrix_price" :class="{ 'bg-blue-900': form.use_matrix_price, 'bg-gray-200': !form.use_matrix_price }">
            <span class="sr-only">Invoice by Matrix</span>
            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': form.use_matrix_price, 'translate-x-0': !form.use_matrix_price }">
                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!form.use_matrix_price" :class="{ 'opacity-0 duration-100 ease-out': form.use_matrix_price, 'opacity-100 duration-200 ease-in': !form.use_matrix_price }">
                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </span>
                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="form.use_matrix_price" :class="{ 'opacity-100 duration-200 ease-in': form.use_matrix_price, 'opacity-0 duration-100 ease-out': !form.use_matrix_price }">
                <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                </svg>
                </span>
            </span>
        </button>
    </p>
</div>

<form @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="invoice_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.invoice_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.invoice_id" v-model="form.invoice_id" :load-options="loadInvoices" @update:model-value="(e) => loadInvoiceBasedOnId(e)"/>
            </div>
            <p v-if="form.errors.invoice_id" class="mt-2 text-xs text-red-600" id="invoice_id-error">{{ form.errors.invoice_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.customer_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value=""/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.warehouse_id') }}</label>
            <div class="mt-2">
              <combobox :disableInput="!form.customer_id" :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="internal_ref" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.internal_ref') }}</label>
            <div class="mt-2">
              <input v-model="form.internal_ref" type="text" name="`internal_ref" id="`internal_ref" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6" placeholder="" />
            </div>
            <p v-if="form.errors.internal_ref" class="mt-2 text-xs text-red-600" id="internal_ref-error">{{ form.errors.internal_ref }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="labcode_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.reason') }}</label>
            <div class="mt-2">
              <combobox
                :options="props.reasons"
                v-model="form.reason"
              />
            </div>
            <p v-if="form.errors.reason" class="mt-2 text-xs text-red-600" id="reason-error">{{ form.errors.reason }}</p>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.items.length }} {{ $t('gestlab.general.labels.credit_notes.items') }}
          <!-- <button v-if="labcode_id && !form.items.length" @click="loadParametersBasedOnLabCode(labcode_id?.value)" class="rounded bg-orange-800 ml-2 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-orange-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-800">{{ $t('gestlab.general.labels.credit_notes.assign_lab_code') }}</button> -->
          <button @click="addItem" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.credit_notes.items_tagline') }} {{ form.name }}</p>
      </div>





      <div class="">
        
        <div class="-mx-4 mt-8 flow-root sm:mx-0">
          <table class="min-w-full">
            <colgroup>
              <col class="w-full sm:w-1/2" />
              <col class="sm:w-1/6" />
              <col class="sm:w-1/6" />
              <col class="sm:w-1/6" />
            </colgroup>
            <thead class="border-b border-gray-300 text-gray-900">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('gestlab.general.labels.credit_notes.item_id') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-center text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.credit_notes.qty') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.credit_notes.unit_price') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.credit_notes.discount') }}</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('gestlab.general.labels.credit_notes.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in itemsWithSubTotal" :key="index" class="border-b border-gray-200" 
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
              >
                <td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0 align-top">
                  <div class="text-gray-900">
                    <combobox v-if="form?.use_matrix_price" v-model="item.item.item_id" :load-options="loadMatrixes" @update:model-value="onSelectedItem(item)"/>
                    <combobox v-else v-model="item.item.item_id" :load-options="loadParameters" @update:model-value="onSelectedItem(item)"/>
                  </div>
                  <div class="mt-2 truncate text-gray-500">
                          <textarea v-model="item.item.obs" :placeholder="$t('gestlab.general.labels.credit_notes.obs')" rows="2" :name="`obs-${index+1}`" :id="`obs-${index+1}`" class="block w-full border-0 border-b border-transparent p-0 pb-2 resize-none focus:ring-0 focus:border-indigo-600 sm:text-sm focus:border-ft-orange" />
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <input v-model="item.item.qty" type="number" step="1" :name="`qty-${index+1}`" :id="`qty-${index+1}`" class="w-full rounded-md border-0 bg-white py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6 text-center" placeholder="0.00" />
                    <div class="mt-2 text-gray-500 z-50">
                      <combobox v-model="item.item.unit_id" :load-options="loadUnits"/>
                    </div>
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <input v-model="item.unit_price" type="number" step=".01" :name="`unit_price-${index+1}`" :id="`unit_price-${index+1}`" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6 text-right" placeholder="0.00" />
                  </div>
                </td>
                <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0 align-top">
                  <div class="relative rounded-md shadow-sm">
                    <input v-model="item.item.discount_amount" type="number" :name="`discount_amount-${index+1}`" :id="`discount_amount-${index+1}`" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6" placeholder="0.00" />
                    <div class="absolute inset-y-0 right-0 flex items-center">
                      <select v-model="item.item.discount_id" :id="`discount_id-${index+1}`" :name="`discount_id-${index+1}`" class="focus:ring-ft-orange focus:border-ft-orange h-full py-0 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-md">
                        <option v-for="(type, index) in props.discount_categories" :key="index" :value="type.value" :selected="item.item.discount_id">{{ type.label }}</option>
                      </select>
                    </div>
                  </div>
                </td>
                <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0 align-top">
                  <div class="relative text-gray-900">
                    {{ parseFloat(item.total).toFixed(2) }}
                    <div class="mt-2">
                      <button @click="removeItem(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
                        <TrashIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-6 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.credit_notes.subtotal') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-6 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.credit_notes.subtotal') }}</th>
                <td class="pl-3 pr-6 pt-6 text-right text-sm text-gray-500 sm:pr-0">{{ subTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.credit_notes.discount') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.credit_notes.discount') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ discountTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.credit_notes.tax_total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.credit_notes.tax_total') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ taxTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-semibold text-gray-900 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.credit_notes.total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-semibold text-gray-900 sm:hidden">{{ $t('gestlab.general.labels.credit_notes.total') }}</th>
                <td class="pl-3 pr-4 pt-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">{{ noteTotal }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>


      
      <p v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</p>
    </div>

    <div class="sm:col-span-full">
      <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">Observações</label>
      <div class="mt-2">
        <textarea v-model="form.obs" type="text" name="obs" id="obs" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
      </div>
      <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" id="obs">{{ form.errors.obs }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click="showDeleteConfirmation = true" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Registrar</button>
    </div>
  </form>

  <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.invoice_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.invoice_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.internal_ref') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.internal_ref }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.reason') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.reason?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
              
              <div class="w-full pt-2">
                <div class="mx-auto w-full rounded-2xl bg-white">
                  <Disclosure v-slot="{ open }" v-for="(product, index) in itemsWithSubTotal" :key="index" v-if="itemsWithSubTotal.length">
                    <DisclosureButton
                      class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                    >
                      <span>{{ product.product_id?.label }}</span>
                      <ChevronUpIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-5 w-5 text-white"
                      />
                    </DisclosureButton>
                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                      <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.item_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.item_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.qty') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.qty }} {{ product.item.unit_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.unit_price') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.unit_price }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.discount') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ product.item.discount_amount }} {{ product.item.discount_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.total') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ parseFloat(product.total).toFixed(2) }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.obs') }}</dt>
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.subtotal') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ subTotal }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.discount') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ discountTotal }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.tax_total') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ taxTotal }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.credit_notes.total') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ noteTotal }}</dd>
            </div>
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>