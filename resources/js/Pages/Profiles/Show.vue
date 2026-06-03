<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.profiles.page_show_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.profiles.page_show_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('profiles.edit', { profile: record.data?.id })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PencilIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.profiles.edit') }}
          </Link>
          <Link 
            :href="route('profiles.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.profiles.back') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- PROFILE DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.profile_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- NAME FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.name') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.name }}
                </div>
              </div>

              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.code') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.code }}
                </div>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.description') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.description || '-' }}
                </div>
              </div>

              <!-- PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.price') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ parseFloat(record.data?.price).toFixed(2) }} AOA
                </div>
              </div>

              <!-- CATEGORY FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.category_id_1') }}
                </label>
                <div class="mt-1">
                  <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
                    {{ record.data?.category || '-' }}
                  </span>
                </div>
              </div>

              <!-- OPTIMAL ANALYSIS TIME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.profiles.optimal_analysis_time') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.optimal_analysis_time || '-' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PARAMETERS SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.profiles.parameters') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ record.data?.parameters?.length || 0 }} {{ $t('gestlab.general.labels.profiles.items') }})
                </span>
              </h2>
              <span class="text-sm font-semibold text-blue-900">
                {{ calculateTotalPrice() }} AOA
              </span>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!record.data?.parameters || record.data?.parameters.length === 0" class="p-12 text-center">
            <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.messages.empty_state.parameters_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.messages.empty_state.parameters_description') }}
            </p>
          </div>

          <!-- PARAMETERS GRID -->
          <div v-else class="space-y-6 p-6">
            <!-- PARAMETER CARD -->
            <div 
              v-for="(parameter, index) in record.data?.parameters"
              :key="parameter.id"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
            >
              <!-- PARAMETER HEADER -->
              <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-blue-900 font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-white">
                        {{ parameter.name || $t('gestlab.general.labels.profiles.unnamed_parameter') }} - {{ parameter.active ? $t('gestlab.general.status.active') : $t('gestlab.general.status.inactive')  }}
                      </h3>
                      <p class="text-xs text-blue-100">
                        {{ parameter.code || 'N/A' }}
                        <span v-if="parameter.price" class="ml-2 font-medium">
                          {{ parseFloat(parameter.price).toFixed(2) }} AOA
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- PARAMETER CONTENT -->
              <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <!-- PARAMETER DETAILS -->
                  <div class="space-y-3 md:col-span-2 lg:col-span-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.unit_id') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.unit_label || '-' }}
                        </p>
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.protocol_id') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.protocol_label || '-' }}
                        </p>
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.standard_id') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.standard_label || '-' }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- REFERENCE VALUES -->
                  <div class="md:col-span-2 lg:col-span-3 pt-2 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.min_ref_value') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.min_ref_value || '-' }}
                        </p>
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.max_ref_value') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.max_ref_value || '-' }}
                        </p>
                      </div>
                      <div class="space-y-1">
                        <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.ref_val_origin') }}</span>
                        <p class="text-sm text-gray-900 font-medium">
                          {{ parameter.pivot.ref_val_origin || '-' }}
                        </p>
                      </div>
                    </div>

                    <!-- DILUTIONS / ANALYTES -->
                    <div v-if="parameter.pivot.dilutions" class="space-y-2">
                      <span class="text-xs text-gray-500">
                        {{ record.data?.analysis_category?.code === 2 
                          ? $t('gestlab.general.labels.profiles.dilutions') 
                          : 'Analytes' 
                        }}
                      </span>
                      <p class="text-sm text-gray-900 font-medium">
                        {{ parameter.pivot.dilutions }}
                      </p>
                    </div>
                  </div>

                  <!-- ADDITIONAL DETAILS -->
                  <div class="md:col-span-2 lg:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                      <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.nwp_id') }}</span>
                      <p class="text-sm text-gray-900 font-medium">
                        {{ parameter.pivot.nwp_label || '-' }}
                      </p>
                    </div>
                    <div class="space-y-1">
                      <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.category_id') }}</span>
                      <p class="text-sm text-gray-900 font-medium">
                        {{ parameter.pivot.category_label || '-' }}
                      </p>
                    </div>
                    <div class="space-y-1">
                      <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.optimal_analysis_time') }}</span>
                      <p class="text-sm text-gray-900 font-medium">
                        {{ parameter.pivot.optimal_analysis_time || '-' }}
                      </p>
                    </div>
                  </div>

                  <!-- EXTRA DATA -->
                  <div v-if="parameter.pivot.extra_data?.dilutions?.length" class="md:col-span-2 lg:col-span-3 pt-2">
                    <span class="text-xs text-gray-500">{{ $t('gestlab.general.labels.profiles.dilution_details') }}</span>
                    <div class="mt-2 space-y-2">
                      <div 
                        v-for="(dilution, dIndex) in parameter.pivot.extra_data.dilutions"
                        :key="dIndex"
                        class="flex items-center gap-3 bg-blue-50/50 p-2 rounded border border-blue-100"
                      >
                        <span class="text-sm text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.quantity') }}: {{ dilution.quantity || '-' }}
                        </span>
                        <span class="text-sm text-gray-700">
                          {{ $t('gestlab.general.labels.profiles.ratio') }}: {{ dilution.ratio || '-' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
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
          <div class="space-y-3">
            <Link 
              :href="route('profiles.edit', { profile: record.data?.id })"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PencilIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.edit') }}
            </Link>
            <Link 
              :href="route('profiles.create')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.create_new') }}
            </Link>
            <Link 
              :href="route('profiles.index')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200"
            >
              <ArrowLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.profiles.back_to_list') }}
            </Link>
          </div>
        </div>

        <!-- SUMMARY CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.summary') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.total_parameters') }}</span>
              <span class="font-semibold text-blue-900">{{ record.data?.parameters?.length || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.total_price') }}</span>
              <span class="font-semibold text-blue-900">{{ parseFloat(record.data?.price).toFixed(2) }} AOA</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.category') }}</span>
              <span class="text-sm text-gray-900 font-medium">{{ record.data?.analysis_category?.code || '-' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.created_at') }}</span>
              <span class="text-sm text-gray-500">{{ formatDate(record.data?.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.updated_at') }}</span>
              <span class="text-sm text-gray-500">{{ formatDate(record.data?.updated_at) }}</span>
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
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.active') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ record.data?.active ? $t('gestlab.general.status.active') : $t('gestlab.general.status.inactive') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.profiles.in_matrixes') }}</span>
              <span class="font-semibold text-blue-900">{{ record.data?.matrixes?.length || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, usePage } from "@inertiajs/vue3";
import {
  ClipboardDocumentCheckIcon,
  PencilIcon,
  ArrowLeftIcon,
  PlusCircleIcon,
  EyeIcon,
  Cog6ToothIcon
} from "@heroicons/vue/24/outline";
import { computed, onMounted } from "vue";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: Object
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
    
})

const calculateTotalPrice = () => {
  if (!props.record.data?.parameters) return '0.00';
  return props.record.data?.parameters.reduce((acc, param) => {
    return acc + (parseFloat(param.price) || 0);
  }, 0).toFixed(2);
};
</script>
