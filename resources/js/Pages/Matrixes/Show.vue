<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.matrixes.page_show_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.matrixes.page_show_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('matrixes.edit', { matrix: record.data?.id })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PencilIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.matrixes.edit') }}
          </Link>
          <Link 
            :href="route('matrixes.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.matrixes.back') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- MATRIX DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.matrix_details') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.code') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.code }}
                </div>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.description') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.description || '-' }}
                </div>
              </div>

              <!-- PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.price') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ parseFloat(record.data?.price).toFixed(2) }} AOA
                </div>
              </div>

              <!-- FIXED PRICE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.fixed_price') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ parseFloat(record.data?.fixed_price).toFixed(2) }} AOA
                </div>
              </div>

              <!-- TAX STATUS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.tax_status') }}
                </label>
                <div class="mt-1">
                  <span :class="[
                    'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                    record.data?.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ record.data?.charge_tax 
                      ? $t('gestlab.general.labels.matrixes.tax_charged') 
                      : $t('gestlab.general.labels.matrixes.tax_exempt') 
                    }}
                  </span>
                </div>
              </div>

              <!-- EXEMPTION OR TAX -->
              <div class="space-y-2" v-if="!record.data?.charge_tax && record.data?.exemption">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.exemption_id') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.exemption?.code || '-' }}
                </div>
              </div>

              <div class="space-y-2" v-if="record.data?.charge_tax && record.data?.tax">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.tax_id') }}
                </label>
                <div class="mt-1 text-sm text-gray-900 font-medium bg-gray-50 rounded-lg p-3 border border-gray-200">
                  {{ record.data?.tax?.name || '-' }}
                </div>
              </div>

              <!-- WITHHOLD TAX -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.matrixes.withhold_tax') }}
                </label>
                <div class="mt-1">
                  <span :class="[
                    'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
                    record.data?.withhold_tax ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ record.data?.withhold_tax 
                      ? $t('gestlab.general.labels.matrixes.withholding_active') 
                      : $t('gestlab.general.labels.matrixes.withholding_inactive') 
                    }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PROFILES SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <ClipboardDocumentCheckIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.matrixes.profiles') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ record.data?.profiles?.length || 0 }} {{ $t('gestlab.general.labels.matrixes.items') }})
                </span>
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!record.data?.profiles || record.data?.profiles.length === 0" class="p-12 text-center">
            <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.messages.empty_state.profiles_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.messages.empty_state.profiles_description') }}
            </p>
          </div>

          <!-- PROFILES GRID -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
            <!-- PROFILE CARD -->
            <div 
              v-for="(profile, index) in record.data?.profiles"
              :key="profile.id"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm"
            >
              <!-- PROFILE HEADER -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                      {{ index + 1 }}
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ profile.name || $t('gestlab.general.labels.matrixes.unnamed_profile') }}
                      </h3>
                      <p class="text-xs text-gray-500">
                        {{ profile.code || 'N/A' }}
                        <span v-if="profile.price" class="ml-2 font-medium text-blue-900">
                          {{ parseFloat(profile.price).toFixed(2) }} AOA
                        </span>
                      </p>
                    </div>
                  </div>
                  <Link 
                    :href="route('profiles.show', { profile: profile.id })"
                    class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-blue-900 transition-all duration-200 p-1 rounded-full hover:bg-blue-50"
                    :title="$t('gestlab.general.labels.matrixes.view_details')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                </div>
              </div>
              
              <!-- PROFILE CONTENT -->
              <div class="p-4">
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.description') }}</span>
                    <span class="text-gray-900 font-medium">{{ profile.description || '-' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.category') }}</span>
                    <span class="text-gray-900 font-medium">{{ profile.category || '-' }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('gestlab.general.labels.profiles.parameters_count') }}</span>
                    <span class="font-semibold text-blue-900">{{ profile.parameters?.length || 0 }}</span>
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
              :href="route('matrixes.edit', { matrix: record.data?.id })"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PencilIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.edit') }}
            </Link>
            <Link 
              :href="route('matrixes.create')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.create_new') }}
            </Link>
            <Link 
              :href="route('matrixes.index')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200"
            >
              <ArrowLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.matrixes.back_to_list') }}
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
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.total_profiles') }}</span>
              <span class="font-semibold text-blue-900">{{ record.data?.profiles?.length || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.total_price') }}</span>
              <span class="font-semibold text-blue-900">{{ parseFloat(record.data?.price).toFixed(2) }} AOA</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.fixed_price') }}</span>
              <span class="font-semibold text-blue-900">{{ parseFloat(record.data?.fixed_price).toFixed(2) }} AOA</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.created_at') }}</span>
              <span class="text-sm text-gray-500">{{ formatDate(record.data?.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.updated_at') }}</span>
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
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.active') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ record.data?.active ? $t('gestlab.general.status.active') : $t('gestlab.general.status.inactive') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.matrixes.tax_status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.charge_tax ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ record.data?.charge_tax ? $t('gestlab.general.labels.matrixes.tax_charged') : $t('gestlab.general.labels.matrixes.tax_exempt') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link } from "@inertiajs/vue3";
import {
  ClipboardDocumentCheckIcon,
  PencilIcon,
  ArrowLeftIcon,
  PlusCircleIcon,
  EyeIcon,
  Cog6ToothIcon
} from "@heroicons/vue/24/outline";

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
</script>