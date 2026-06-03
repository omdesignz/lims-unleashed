<script setup>
import '../CommercialDocumentSurface.css';
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';


defineOptions({
  layout: Layout
});

const props = defineProps({
    record: Object,
    discount_categories: {
      type: Array,
      default: () => []
    }
});

onMounted(() => {
  itemsWithSubTotal
});

const form = useForm({
    type_id: props.record.type_id,
    id: props.record.id,
    inv_no: props.record.inv_no,
    customer_id: props.record.customer_id,
    warehouse_id: props.record.warehouse_id,
    internal_ref: props.record.internal_ref,
    obs: props.record.obs,
    items: props.record.items,
    total: props.record.total
});

const addItem = () => {
    form.items.push({
        id: null,
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

function loadWarehouses(query, setOptions) {
    fetch('/warehouses/getWarehouse?q=' + query + (form.customer_id ? '&customer_id=' + form.customer_id.value : ''))
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.address,
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

let submitItem = (item) => {
    useForm({
      id: item.id,
      obs: item.obs,
      itemable_id: item.itemable_id,
      itemable_type: item.itemable_type,
      unit_id: item.unit_id,
    })
    .put(route('invoiceitems.update', {item: item.id}), {
      preserveScroll: true,
      preserveState: false,
        onSuccess: () => {
          form.reset()
        },
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
  .post(route('receipts.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('receipts.update',{receipt: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset()
      },
  });
}

}

const itemsWithSubTotal = computed(() => {

return form.items.map(item => ({
    item,
    id: item.id,
    tax: lineTaxPercentage(item),
    qty: item.qty,
    invoice_id: item.invoice_id,
    itemable_type: item.itemable_type,
    itemable_id: item.itemable_id,
    obs: item.obs,
    item_id: item.item_id,
    tax_id: item?.tax_id,
    item_description: item.item_description,
    tax_percentage: item.tax_percentage,
    unit_id: item.unit_id,
    exemption_id: item?.exemption_id,
    exemption_code: item?.exemption_code,
    charge_tax: lineChargeTax(item),
    unit_price: lineUnitPrice(item),
    product_price: onSelectedItem(item),
    total: lineSubTotalAmount(item) ? lineSubTotalAmount(item) : parseFloat(0),
    discount_amount: lineDiscountAmount(item),
    tax_amount: lineTaxAmount(item),
}))

});

const lineUnitPrice = (item) => {
  return parseFloat(onSelectedItem(item) - parseFloat(lineDiscountAmount(item))).toFixed(2);
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
    return parseFloat(subTotal.value + taxTotal.value).toFixed(2);
})

const onSelectedItem = (item) => {
      return item?.item_id?.price;
    }
  

</script>

<template>
<div class="commercial-document-page border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Facturas</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">A editar a factura : {{ form?.inv_no }}</p>
</div>

<form class="commercial-document-page" :class="commercialDocumentThemeClasses" @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2 sm:col-start-1">
            <label for="type_id" class="block text-sm font-medium leading-6 text-gray-900">Categoria</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.type_id" v-model="form.type_id" :load-options="loadInvoiceCategories" @update:model-value=""/>
            </div>
            <p v-if="form.errors.type_id" class="mt-2 text-xs text-red-600" id="type_id-error">{{ form.errors.type_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">Cliente</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value=""/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">Armazém</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="internal_ref" class="block text-sm font-medium leading-6 text-gray-900">Referência</label>
            <div class="mt-2">
              <input v-model="form.internal_ref" type="text" name="`internal_ref" id="`internal_ref" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6" placeholder="" />
            </div>
            <p v-if="form.errors.internal_ref" class="mt-2 text-xs text-red-600" id="internal_ref-error">{{ form.errors.internal_ref }}</p>
          </div>

          <div class="sm:col-span-2">
            <div class="mt-8 flex items-center justify-end">
              <button @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Registrar</button>
            </div>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.items.length }} Iten(s)
          <button @click="addItem" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">Itens associados à factura: {{ form.name }}</p>
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
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Item</th>
                <th scope="col" class="hidden px-3 py-3.5 text-center text-sm font-semibold text-gray-900 sm:table-cell">QTD</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">Preço UN</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">Desconto</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">Total</th>
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
                      <combobox v-model="item.item.item_id" :load-options="loadParameters" @update:model-value="onSelectedItem(item)"/>
                  </div>
                  <div class="mt-2 truncate text-gray-500">
                          <textarea v-model="item.item.obs" placeholder="observações sobre o artigo..." rows="2" :name="`obs-${index+1}`" :id="`obs-${index+1}`" class="block w-full border-0 border-b border-transparent p-0 pb-2 resize-none focus:ring-0 focus:border-indigo-600 sm:text-sm focus:border-ft-orange" />
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
                    <div class="mt-2">
                      <combobox v-model="item.item.itemable_id" :load-options="loadLabCodes" placeholder="CL"/> 
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
                  <div class="mt-2 flex items-center justify-end">
                    <button @click="submitItem(item)" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Registrar Item</button>
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
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-6 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">Subtotal</th>
                <th scope="row" class="pl-6 pr-3 pt-6 text-left text-sm font-normal text-gray-500 sm:hidden">Subtotal</th>
                <td class="pl-3 pr-6 pt-6 text-right text-sm text-gray-500 sm:pr-0">{{ subTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">Descontos</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">Descontos</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ discountTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">Taxas</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">Taxas</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ taxTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-semibold text-gray-900 sm:table-cell sm:pl-0">Total</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-semibold text-gray-900 sm:hidden">Total</th>
                <td class="pl-3 pr-4 pt-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">
                  {{ invoiceTotal }}
                </td>
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

    <!-- <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click="submit" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">Registrar</button>
    </div> -->
  </form>

</template>
