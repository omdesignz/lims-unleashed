<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { 
  MagnifyingGlassIcon,
  BuildingOfficeIcon,
  EnvelopeIcon,
  IdentificationIcon,
  PhoneIcon,
  MapPinIcon,
  UserIcon,
  DocumentMagnifyingGlassIcon,
  ArrowPathIcon,
  DocumentPlusIcon,
  QuestionMarkCircleIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  Cog6ToothIcon,
  CheckCircleIcon
} from "@heroicons/vue/24/outline";
import confirmDialog from "@/Components/confirm-dialog.vue";

const props = defineProps({});

defineOptions({
  layout: Layout
});

// Reactive state
const tax_number = ref(null);
const name = ref(null);
const email = ref(null);
const tax_regime = ref(null);
const address = ref(null);
const contact = ref(null);
const isLoading = ref(false);
const searchError = ref(null);

// Computed
const hasTaxData = computed(() => {
  return name.value || email.value || address.value || contact.value || tax_regime.value;
});

const searchStatus = computed(() => {
  if (isLoading.value) {
    return {
      label: 'searching',
      color: 'bg-blue-100 text-blue-800',
      icon: ArrowPathIcon
    };
  } else if (searchError.value) {
    return {
      label: 'error',
      color: 'bg-red-100 text-red-800',
      icon: ExclamationTriangleIcon
    };
  } else if (hasTaxData.value) {
    return {
      label: 'found',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else {
    return {
      label: 'idle',
      color: 'bg-gray-100 text-gray-800',
      icon: DocumentMagnifyingGlassIcon
    };
  }
});

const getTaxData = () => {
  if (!tax_number.value) {
    searchError.value = 'Please enter a tax number';
    return;
  }

  isLoading.value = true;
  searchError.value = null;
  
  // Clear previous data
  name.value = null;
  email.value = null;
  address.value = null;
  contact.value = null;
  tax_regime.value = null;

  axios.get('/customers/tax-data?tax_number=' + tax_number.value)
    .then((response) => {
      name.value = response.data.gsmc;
      address.value = response.data.addressDbb;
      email.value = response.data.email;
      contact.value = response.data.lxfs;
      tax_regime.value = response.data.regimeIva;
    })
    .catch((error) => {
      searchError.value = error.response?.data?.message || trans('gestlab.general.messages.search_failed');
    })
    .finally(() => {
      isLoading.value = false;
    });
};

const clearSearch = () => {
  tax_number.value = null;
  name.value = null;
  email.value = null;
  address.value = null;
  contact.value = null;
  tax_regime.value = null;
  searchError.value = null;
};
</script>

<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentMagnifyingGlassIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.tax_authority.page_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.tax_authority.page_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="[
            'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset ring-blue-700/10',
            searchStatus.color
          ]">
            <component :is="searchStatus.icon" class="h-4 w-4" />
            {{ $t(`gestlab.general.status.${searchStatus.label}`) }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- SEARCH SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <MagnifyingGlassIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.tax_authority.search_panel_title') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="space-y-6">
              <!-- SEARCH INPUT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <IdentificationIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.tax_authority.nif') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                  <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <IdentificationIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <input
                      v-model="tax_number"
                      type="text"
                      name="tax_number"
                      id="tax_number"
                      :placeholder="$t('gestlab.general.placeholders.enter_tax_number')"
                      class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                      @keyup.enter="getTaxData"
                    />
                  </div>
                  <button
                    @click="getTaxData"
                    :disabled="isLoading || !tax_number"
                    type="button"
                    :class="[
                      'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200',
                      isLoading || !tax_number
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                    ]"
                  >
                    <ArrowPathIcon v-if="isLoading" class="h-5 w-5 animate-spin" />
                    <MagnifyingGlassIcon v-else class="h-5 w-5" />
                    {{ isLoading ? $t('gestlab.general.buttons.searching') : $t('gestlab.general.buttons.search') }}
                  </button>
                  <button
                    v-if="hasTaxData"
                    @click="clearSearch"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                  >
                    {{ $t('gestlab.general.buttons.clear') }}
                  </button>
                </div>
                <p v-if="searchError" class="text-xs text-red-600">
                  {{ searchError }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.messages.press_enter_to_search') }}
                </p>
              </div>

              <!-- ERROR MESSAGE -->
              <div v-if="searchError && !isLoading" class="rounded-lg bg-red-50 p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                      {{ $t('gestlab.general.messages.search_failed') }}
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                      <p>{{ searchError }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RESULTS SECTION -->
        <div v-if="hasTaxData" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <BuildingOfficeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.tax_authority.search_results') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- COMPANY INFO -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <BuildingOfficeIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.tax_authority.company_info') }}
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-xs font-medium text-gray-500">
                        {{ $t('gestlab.general.labels.tax_authority.name') }}
                      </dt>
                      <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ name || $t('gestlab.general.messages.not_available') }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs font-medium text-gray-500">
                        {{ $t('gestlab.general.labels.tax_authority.tax_regime') }}
                      </dt>
                      <dd class="mt-1 text-sm font-medium" :class="tax_regime ? 'text-blue-900' : 'text-gray-500'">
                        {{ tax_regime || $t('gestlab.general.messages.not_available') }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs font-medium text-gray-500">
                        {{ $t('gestlab.general.labels.tax_authority.tax_number') }}
                      </dt>
                      <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ tax_number }}
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- CONTACT INFO -->
              <div class="space-y-6">
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.tax_authority.contact_info') }}
                  </h3>
                  <dl class="space-y-3">
                    <div>
                      <dt class="text-xs font-medium text-gray-500">
                        {{ $t('gestlab.general.labels.tax_authority.contact') }}
                      </dt>
                      <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ contact || $t('gestlab.general.messages.not_available') }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs font-medium text-gray-500">
                        {{ $t('gestlab.general.labels.tax_authority.email') }}
                      </dt>
                      <dd class="mt-1 text-sm font-medium" :class="email ? 'text-blue-900' : 'text-gray-500'">
                        {{ email || $t('gestlab.general.messages.not_available') }}
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- ADDRESS INFO (Full width) -->
              <div class="md:col-span-2">
                <div class="pt-6 border-t border-gray-200">
                  <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <MapPinIcon class="h-4 w-4 text-blue-900" />
                    {{ $t('gestlab.general.labels.tax_authority.address_info') }}
                  </h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-700">
                      {{ address || $t('gestlab.general.messages.address_not_available') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
              <div class="text-sm text-gray-500">
                {{ $t('gestlab.general.messages.tax_data_source') }}
              </div>
              <div class="flex items-center gap-3">
                <button
                  @click="getTaxData"
                  :disabled="isLoading"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                >
                  <ArrowPathIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.buttons.refresh_data') }}
                </button>
                <button
                  v-if="hasTaxData"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                >
                  {{ $t('gestlab.general.buttons.use_this_data') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="!hasTaxData && !isLoading && !searchError" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
          <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.general.messages.no_tax_data') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.general.messages.enter_tax_number_to_search') }}
          </p>
          <div class="mt-6 max-w-md mx-auto">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 text-left">
                {{ $t('gestlab.general.labels.tax_authority.nif') }}
              </label>
              <div class="flex gap-2">
                <input
                  v-model="tax_number"
                  type="text"
                  :placeholder="$t('gestlab.general.placeholders.enter_tax_number')"
                  class="flex-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 sm:text-sm"
                  @keyup.enter="getTaxData"
                />
                <button
                  @click="getTaxData"
                  :disabled="!tax_number"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <MagnifyingGlassIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.buttons.search') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.status') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.search_status') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                searchStatus.color
              ]">
                <component :is="searchStatus.icon" class="h-3 w-3" />
                {{ $t(`gestlab.general.status.${searchStatus.label}`) }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.tax_number_provided') }}</span>
              <span :class="tax_number ? 'text-green-600' : 'text-red-600'">
                {{ tax_number ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.data_available') }}</span>
              <span :class="hasTaxData ? 'text-green-600' : 'text-red-600'">
                {{ hasTaxData ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ $t('gestlab.general.labels.search_in_progress') }}</span>
              <span :class="isLoading ? 'text-blue-600' : 'text-gray-600'">
                {{ isLoading ? $t('gestlab.general.status.yes') : $t('gestlab.general.status.no') }}
              </span>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <button
              @click="getTaxData"
              :disabled="!tax_number || isLoading"
              type="button"
              class="w-full inline-flex items-center justify-between rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div class="flex items-center gap-2">
                <MagnifyingGlassIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.search_again') }}
              </div>
              <kbd class="inline-flex items-center rounded border border-gray-200 px-2 font-sans text-xs text-gray-400">
                ↵ Enter
              </kbd>
            </button>
            <button
              @click="clearSearch"
              :disabled="!hasTaxData"
              type="button"
              class="w-full inline-flex items-center justify-between rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div class="flex items-center gap-2">
                <TrashIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.clear_results') }}
              </div>
              <kbd class="inline-flex items-center rounded border border-gray-200 px-2 font-sans text-xs text-gray-400">
                Esc
              </kbd>
            </button>
            <button
              v-if="hasTaxData"
              type="button"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <DocumentPlusIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.tax_authority.create_customer') }}
            </button>
          </div>
        </div>

        <!-- HELP CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <QuestionMarkCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.help') }}
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="h-5 w-5 text-blue-900 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.messages.how_to_use') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.messages.enter_valid_nif') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-500 flex-shrink-0 mt-0.5" />
              <div>
                <p class="font-medium text-gray-900">{{ $t('gestlab.general.messages.data_source') }}</p>
                <p class="mt-1 text-gray-600">{{ $t('gestlab.general.messages.tax_authority_data') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Loading animation */
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

/* Focus styles */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}

/* Keyboard key styling */
kbd {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  background-color: #f9fafb;
  border-color: #e5e7eb;
}
</style>