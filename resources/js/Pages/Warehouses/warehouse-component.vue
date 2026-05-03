<template>
  <div class="relative bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm transition-all duration-200 hover:border-blue-900">
    <!-- WAREHOUSE HEADER -->
    <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-900">
            <BuildingOfficeIcon class="h-5 w-5 text-white" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900">
              {{ form.name || $t('gestlab.general.labels.warehouses.warehouse') }}
            </h3>
            <div class="flex items-center gap-2 mt-1">
              <span v-if="form.code" class="text-xs text-blue-900 font-medium bg-blue-50 px-2 py-0.5 rounded-full">
                {{ form.code }}
              </span>
              <span v-if="props.primary_warehouse == form.id" class="text-xs text-green-700 font-medium bg-green-50 px-2 py-0.5 rounded-full">
                {{ $t('gestlab.general.status.primary') }}
              </span>
              <span v-else class="text-xs text-gray-500 font-medium bg-gray-50 px-2 py-0.5 rounded-full">
                {{ $t('gestlab.general.status.secondary') }}
              </span>
            </div>
          </div>
        </div>
        <button 
          v-if="!props.record.id"
          @click="$emit('removed-from-array')"
          class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-1 rounded-lg hover:bg-red-50"
          :title="$t('gestlab.general.buttons.remove')"
        >
          <TrashIcon class="h-5 w-5" />
        </button>
        <button 
          v-else
          @click="deleteWarehouse(props.record.id)"
          class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-1 rounded-lg hover:bg-red-50"
          :title="$t('gestlab.general.buttons.delete')"
        >
          <TrashIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <!-- WAREHOUSE FORM -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-6">
        <!-- BASIC INFORMATION COLUMN -->
        <div class="space-y-6">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <UserCircleIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.warehouses.basic_info') }}
            </h4>
            <div class="space-y-4">
              <!-- NAME FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.name
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.placeholders.warehouse_name')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.code') }}
                </label>
                <input
                  v-model="form.code"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.code
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.code')"
                />
                <p v-if="form.errors.code" class="text-xs text-red-600">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.description') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.description
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.description')"
                />
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTACT INFORMATION COLUMN -->
        <div class="space-y-6">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <PhoneIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.warehouses.contact_info') }}
            </h4>
            <div class="space-y-4">
              <!-- EMAIL FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.email') }}
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.email
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  placeholder="warehouse@example.com"
                />
                <p v-if="form.errors.email" class="text-xs text-red-600">
                  {{ form.errors.email }}
                </p>
              </div>

              <!-- PRIMARY PHONE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.primary_phone') }}
                </label>
                <input
                  v-model="form.primary_phone"
                  type="tel"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.primary_phone
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  placeholder="+1234567890"
                />
                <p v-if="form.errors.primary_phone" class="text-xs text-red-600">
                  {{ form.errors.primary_phone }}
                </p>
              </div>

              <!-- ALTERNATIVE PHONE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.alternative_phone') }}
                </label>
                <input
                  v-model="form.alternative_phone"
                  type="tel"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.alternative_phone
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  placeholder="+1234567890"
                />
                <p v-if="form.errors.alternative_phone" class="text-xs text-red-600">
                  {{ form.errors.alternative_phone }}
                </p>
              </div>

              <!-- NIF FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.nif') }}
                </label>
                <input
                  v-model="form.nif"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.nif
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.nif')"
                />
                <p v-if="form.errors.nif" class="text-xs text-red-600">
                  {{ form.errors.nif }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- LOCATION & FOCAL POINT COLUMN -->
        <div class="space-y-6">
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <MapPinIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.warehouses.location') }}
            </h4>
            <div class="space-y-4">
              <!-- ADDRESS FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.address') }}
                </label>
                <input
                  v-model="form.address"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.address
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.address')"
                />
                <p v-if="form.errors.address" class="text-xs text-red-600">
                  {{ form.errors.address }}
                </p>
              </div>

              <!-- MUNICIPALITY FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.municipality') }}
                </label>
                <input
                  v-model="form.municipality"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.municipality
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.municipality')"
                />
                <p v-if="form.errors.municipality" class="text-xs text-red-600">
                  {{ form.errors.municipality }}
                </p>
              </div>

              <!-- PROVINCE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.province') }}
                </label>
                <input
                  v-model="form.province"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.province
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.province')"
                />
                <p v-if="form.errors.province" class="text-xs text-red-600">
                  {{ form.errors.province }}
                </p>
              </div>

              <!-- FOCAL POINT FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.focal_point') }}
                </label>
                <input
                  v-model="form.focal_point"
                  type="text"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.focal_point
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  :placeholder="$t('gestlab.general.labels.warehouses.focal_point')"
                />
                <p v-if="form.errors.focal_point" class="text-xs text-red-600">
                  {{ form.errors.focal_point }}
                </p>
              </div>

              <!-- FOCAL POINT CONTACT FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.focal_point_contact') }}
                </label>
                <input
                  v-model="form.focal_point_contact"
                  type="tel"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.focal_point_contact
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  placeholder="+1234567890"
                />
                <p v-if="form.errors.focal_point_contact" class="text-xs text-red-600">
                  {{ form.errors.focal_point_contact }}
                </p>
              </div>

              <!-- FOCAL POINT EMAIL FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.warehouses.focal_point_email') }}
                </label>
                <input
                  v-model="form.focal_point_email"
                  type="email"
                  :class="[
                    'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset placeholder:text-gray-400 focus:ring-2 focus:ring-inset sm:text-sm transition-colors duration-200',
                    form.errors.focal_point_email
                      ? 'ring-red-300 focus:ring-red-500 bg-red-50'
                      : 'ring-gray-300 focus:ring-blue-900'
                  ]"
                  placeholder="focal.point@example.com"
                />
                <p v-if="form.errors.focal_point_email" class="text-xs text-red-600">
                  {{ form.errors.focal_point_email }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- SAVE BUTTON -->
          <button 
            @click="submit"
            :disabled="form.processing || !form.isDirty"
            :class="[
              'sm:flex-1 inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
              form.processing || !form.isDirty
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
            ]"
          >
            <CheckIcon v-if="!form.processing" class="h-5 w-5" />
            <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
            {{
              form.processing 
                ? $t('gestlab.general.buttons.processing')
                : form.id 
                  ? $t('gestlab.general.buttons.update')
                  : $t('gestlab.general.buttons.submit')
            }}
          </button>

          <!-- MAKE PRIMARY BUTTON -->
          <button 
            @click="makePrimary"
            :disabled="props.primary_warehouse == form.id || !form.id"
            :class="[
              'sm:flex-1 inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
              props.primary_warehouse == form.id || !form.id
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                : props.primary_warehouse == form.id
                  ? 'bg-gradient-to-r from-green-600 to-green-500 text-white border border-green-600'
                  : 'bg-white text-blue-900 border border-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
            ]"
          >
            <StarIcon v-if="props.primary_warehouse == form.id" class="h-5 w-5" />
            <StarIcon v-else class="h-5 w-5" />
            {{
              props.primary_warehouse == form.id
                ? $t('gestlab.general.buttons.is_primary_warehouse')
                : $t('gestlab.general.buttons.make_primary_warehouse')
            }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import { throttle } from "lodash";
import { 
  TrashIcon, 
  CheckIcon,
  BuildingOfficeIcon,
  UserCircleIcon,
  PhoneIcon,
  MapPinIcon,
  StarIcon,
  ArrowPathIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
  record: Object,
  primary_warehouse: Number
});

const form = useForm({
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

const emit = defineEmits(['removed-from-array']);

let submit = () => {
  if (!form.id) {
    form.post(route('warehouses.store'), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset();
      },
    });
  } else {
    form.put(route('warehouses.update', { warehouse: form.id }), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        form.reset();
      },
    });
  }
};

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

let deleteWarehouse = (warehouse) => {
  router.get(route('warehouses.destroy'), {
    recordIds: [warehouse]
  }, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      // Success handling
    }
  });
};

let makePrimary = () => {
  router.put(route('customers.changePrimaryWarehouse', { customer: form.customer_id?.value }), {
    warehouse_id: form.id,
  }, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      // Success handling
    }
  });
};
</script>