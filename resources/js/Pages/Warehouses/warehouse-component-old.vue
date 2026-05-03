<script setup>
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
    record: Object,
    primary_warehouse: Number
});

let form = useForm({
    email: props.record?.email,
    primary_phone: props.record?.primary_phone,
    alternative_phone: props.record?.alternative_phone,
    nif: props.record?.nif,
    address: props.record?.address,
    municipality: props.record?.municipality,
    province: props.record?.province,
    description: props.record?.description,
    code: props.record?.code,
    name: props.record?.name,
    focal_point: props.record?.focal_point,
    focal_point_email: props.record?.focal_point_email,
    focal_point_contact: props.record?.focal_point_contact,
    customer_id: props.record?.customer_id,
    id: props.record?.id,
});

onMounted(() => {
});

const emit = defineEmits(['removed-from-array']);

let submit = (index) => {

if(!form.id) {
  form.post(route('warehouses.store'), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('warehouses.update',{warehouse: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset()
      },
  });
}

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

let deleteWarehouse = (warehouse) => {
  router.get(route('warehouses.destroy'), {
          recordIds: [warehouse]
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
        }
      });
}

let makePrimary = (warehouse) => {
  router.put(route('customers.changePrimaryWarehouse', {customer: form.customer_id?.value}), {
          warehouse_id: form.id,
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
        }
      });
}

</script>
<template>
    
        <li class="rounded-xl border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-3">
            <div class="text-sm font-medium leading-6 text-gray-900">

              <ClipboardDocumentCheckIcon class="h-5 w-5" />

              </div>
              Armazém | {{ form?.name }}

            <button v-if="!props.record.id" @click="$emit('removed-from-array')" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
            <button v-else @click="deleteWarehouse(props.record.id)" class="hover:text-red-500 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.name') }}</label>
                  <div class="mt-2">
                    <input v-model="form.name" type="text" name="name-error" id="name-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.name" class="mt-2 text-xs text-red-600" id="name-error">{{ form.errors.name }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="email" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.email') }}</label>
                  <div class="mt-2">
                    <input v-model="form.email" type="email" name="email-error" id="email-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.email" class="mt-2 text-xs text-red-600" id="email-error">{{ form.errors.email }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="primary_phone" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.primary_phone') }}</label>
                  <div class="mt-2">
                    <input v-model="form.primary_phone" type="number" name="primary_phone-error" id="primary_phone-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.primary_phone" class="mt-2 text-xs text-red-600" id="primary_phone-error">{{ form.errors.primary_phone }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="focal_point" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.focal_point') }}</label>
                  <div class="mt-2">
                    <input v-model="form.focal_point" type="text" name="focal_point-error" id="focal_point-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.focal_point" class="mt-2 text-xs text-red-600" id="focal_point-error">{{ form.errors.focal_point }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="focal_point_contact" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.focal_point_contact') }}</label>
                  <div class="mt-2">
                    <input v-model="form.focal_point_contact" type="number" name="focal_point_contact-error" id="focal_point_contact-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.focal_point_contact" class="mt-2 text-xs text-red-600" id="focal_point_contact-error">{{ form.errors.focal_point_contact }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="focal_point_email" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.focal_point_email') }}</label>
                  <div class="mt-2">
                    <input v-model="form.focal_point_email" type="email" name="focal_point_email-error" id="focal_point_email-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.focal_point_email" class="mt-2 text-xs text-red-600" id="focal_point_email-error">{{ form.errors.focal_point_email }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="alternative_phone" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.alternative_phone') }}</label>
                  <div class="mt-2">
                    <input v-model="form.alternative_phone" type="number" name="alternative_phone-error" id="alternative_phone-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.alternative_phone" class="mt-2 text-xs text-red-600" id="alternative_phone-error">{{ form.errors.alternative_phone }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="nif" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.nif') }}</label>
                  <div class="mt-2">
                    <input v-model="form.nif" type="text" name="nif-error" id="nif-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.nif" class="mt-2 text-xs text-red-600" id="nif-error">{{ form.errors.nif }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="address" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.address') }}</label>
                  <div class="mt-2">
                    <input v-model="form.address" type="text" name="address-error" id="address-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.address" class="mt-2 text-xs text-red-600" id="address-error">{{ form.errors.address }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="municipality" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.municipality') }}</label>
                  <div class="mt-2">
                    <input v-model="form.municipality" type="text" name="municipality-error" id="municipality-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.municipality" class="mt-2 text-xs text-red-600" id="municipality-error">{{ form.errors.municipality }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="province" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.province') }}</label>
                  <div class="mt-2">
                    <input v-model="form.province" type="text" name="province-error" id="province-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.province" class="mt-2 text-xs text-red-600" id="province-error">{{ form.errors.province }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="description" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.warehouses.description') }}</label>
                  <div class="mt-2">
                    <input v-model="form.description" type="text" name="description-error" id="description-error" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
                  </div>
                  <p v-if="form.errors.description" class="mt-2 text-xs text-red-600" id="description-error">{{ form.errors.description }}</p>
                </div>
              </dd>
            </div>

            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                    <button v-if="form.isDirty" @click="submit" :disabled="form.processing" type="button" class="block w-full inline-flex justify-center rounded-md bg-orange-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
                    <button @click="makePrimary" :disabled="props?.primary_warehouse == form.id" type="button" class="block w-full inline-flex justify-center rounded-md bg-orange-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ props?.primary_warehouse !== form.id ? $t('gestlab.general.buttons.make_primary_warehouse') : $t('gestlab.general.buttons.is_primary_warehouse') }}</button>
                </div>
              </dd>
            </div>

          </dl>
        </li>
      
    
</template>