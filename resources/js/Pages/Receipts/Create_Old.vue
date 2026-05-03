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
    payment_categories: {
      type: Array,
      default: () => []
    },
});

let customerWarehouses = reactive([]);

const form = useForm({
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
        invoice_id: '',
        receipt_id: '',
        user_id: '',
        paid_amount: 0,
        pending_amount: 0,
        invoice_pending_amount: 0,
        obs: '',
    });
}

const removeItem = (index) => {
    form.items.splice(index, 1);
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
            amount_due: result.amount_due,
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
  }))

});

const linePendingAmount = (item) => {
return ( parseFloat(lineInvoicePendingAmount(item)) - parseFloat(linePaidAmount(item))).toFixed(2);
}

const lineInvoicePendingAmount = (item) => {
  return item['invoice_id']?.amount_due ?? 0;
}

const linePaidAmount = (item) => {
  return item['paid_amount'];
}


let submit = () => {
    if(!form.id) {
      form.transform((data) => ({
    ...data,
    formatted_items: itemsWithPendingValues.value.map(item => ({
          invoice_id: item['item'].invoice_id['value'],
          obs: item['item'].obs,
          invoice_pending_amount: item.invoice_pending_amount,
          paid_amount: item['item'].paid_amount,
          payment_id: item['item'].payment_id['value'],
          pending_amount: item.pending_amount,
          user_id: item.user_id,
          receipt_id: item.receipt_id,
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
        onSuccess: () => {
            
          },
      });
    }
}



const onSelectedItem = (item) => {
      return item?.item_id?.price;
    }
  

</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.receipts.page_create_description') }} {{ form?.customer_id?.label }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-6">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">

          <div class="sm:col-span-2">
            <label for="customer_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.customer_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.customer_id" v-model="form.customer_id" :load-options="loadCustomers" @update:model-value=""/>
            </div>
            <p v-if="form.errors.customer_id" class="mt-2 text-xs text-red-600" id="customer_id-error">{{ form.errors.customer_id }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="warehouse_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.warehouse_id') }}</label>
            <div class="mt-2">
              <combobox :disableInput="!form.customer_id" :hasError="form.errors.warehouse_id" v-model="form.warehouse_id" :load-options="loadWarehouses"/>
            </div>
            <p v-if="form.errors.warehouse_id" class="mt-2 text-xs text-red-600" id="warehouse_id-error">{{ form.errors.warehouse_id }}</p>
          </div>

        </div>

      <div class="border-b border-gray-900/10 pb-6">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.items.length }} {{ $t('gestlab.general.labels.receipts.items') }}
          <button @click="addItem" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.receipts.items_tagline') }} {{ form.name }}</p>
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
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">{{ $t('gestlab.general.labels.receipts.invoice_id') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.receipts.paid_amount') }}</th>
                <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 sm:table-cell">{{ $t('gestlab.general.labels.receipts.payment_id') }}</th>
                <th scope="col" class="py-3.5 pl-3 pr-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">{{ $t('gestlab.general.labels.receipts.pending_amount') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in itemsWithPendingValues" :key="index" class="border-b border-gray-200" 
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
              >
                <td class="max-w-0 py-5 pl-4 pr-3 text-sm sm:pl-0 align-top">
                  <div class="text-gray-900">
                      <combobox v-model="item.item.invoice_id" :load-options="loadInvoices" @update:model-value="onSelectedItem(item)"/>
                  </div>
                  <div class="mt-2 truncate text-gray-500">
                          <textarea v-model="item.item.obs" :placeholder="$t('gestlab.general.labels.receipts.obs')" rows="2" :name="`obs-${index+1}`" :id="`obs-${index+1}`" class="block w-full border-0 border-b border-transparent p-0 pb-2 resize-none focus:ring-0 focus:border-indigo-600 sm:text-sm focus:border-ft-orange" />
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-right text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <input v-model="item.item.paid_amount" type="number" step=".01" :name="`paid_amount-${index+1}`" :id="`paid_amount-${index+1}`" class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-orange-800 sm:text-sm sm:leading-6 text-right" placeholder="0.00" />
                  </div>
                </td>
                <td class="hidden px-3 py-5 text-sm text-gray-500 sm:table-cell align-top">
                  <div class="relative rounded-md shadow-sm">
                    <div class="mt-0 text-gray-500 z-50">
                      <combobox
                        :options="props.payment_categories"
                        v-model="item.item.payment_id"
                      />
                    </div>
                  </div>
                </td>
                <td class="py-5 pl-3 pr-4 text-right text-sm text-gray-500 sm:pr-0 align-top">
                  <div class="relative text-gray-900">
                    {{ parseFloat(item.pending_amount).toFixed(2) }}
                    <div class="mt-2">
                      <button @click="removeItem(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
                        <TrashIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
            <!-- <tfoot>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-6 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.receipts.subtotal') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-6 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.receipts.subtotal') }}</th>
                <td class="pl-3 pr-6 pt-6 text-right text-sm text-gray-500 sm:pr-0">{{ subTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.receipts.discount_total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.receipts.discount_total') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ discountTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-normal text-gray-500 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.receipts.tax_total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-normal text-gray-500 sm:hidden">{{ $t('gestlab.general.labels.receipts.tax_total') }}</th>
                <td class="pl-3 pr-6 pt-4 text-right text-sm text-gray-500 sm:pr-0">{{ taxTotal }}</td>
              </tr>
              <tr>
                <th scope="row" colspan="4" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-semibold text-gray-900 sm:table-cell sm:pl-0">{{ $t('gestlab.general.labels.receipts.total') }}</th>
                <th scope="row" class="pl-6 pr-3 pt-4 text-left text-sm font-semibold text-gray-900 sm:hidden">{{ $t('gestlab.general.labels.receipts.total') }}</th>
                <td class="pl-3 pr-4 pt-4 text-right text-sm font-semibold text-gray-900 sm:pr-0">{{ noteTotal }}</td>
              </tr>
            </tfoot> -->
          </table>
        </div>
      </div>


      
      <p v-if="form.errors.items" class="mt-2 text-xs text-red-600">{{ form.errors.items }}</p>
    </div>

    <div class="sm:col-span-full">
      <label for="obs" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.obs') }}</label>
      <div class="mt-2">
        <textarea v-model="form.obs" type="text" name="obs" id="obs" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
      </div>
      <p v-if="form.errors.obs" class="mt-2 text-xs text-red-600" id="obs">{{ form.errors.obs }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <button @click="showDeleteConfirmation = true" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.customer_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.customer_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.warehouse_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.warehouse_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
              
              <div class="w-full pt-2">
                <div class="mx-auto w-full rounded-2xl bg-white">
                  <Disclosure v-slot="{ open }" v-for="(item, index) in itemsWithPendingValues" :key="index" v-if="itemsWithPendingValues.length">
                    <DisclosureButton
                      class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                    >
                      <span>{{ item.invoice_id?.label }}</span>
                      <ChevronUpIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-5 w-5 text-white"
                      />
                    </DisclosureButton>
                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                      <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.invoice_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ item.invoice_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.paid_amount') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ item.paid_amount }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.payment_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ item.payment_id?.label }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.pending_amount') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ item.pending_amount }}</dd>
                          </div>
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.obs') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ item.obs }}</dd>
                          </div>
                        </dl>
                      </div>
                    </DisclosurePanel>
                  </Disclosure>
                </div>
              </div>
            </div>

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.receipts.obs') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.obs }}</dd>
            </div>
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>