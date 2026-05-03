<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      {{ $t('gestlab.general.labels.vap_labels.print_settings') }}
    </h3>
    
    <div class="space-y-4">
      <!-- Include Cutouts -->
      <div class="flex items-center justify-between">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('gestlab.general.labels.vap_labels.include_cutouts') }}
          </label>
          <p class="text-xs text-gray-500">
            {{ $t('gestlab.general.labels.vap_labels.cutout_info') }}
          </p>
        </div>
        <div class="flex items-center">
          <input
            v-model="settings.include_cutouts"
            type="checkbox"
            class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
          />
        </div>
      </div>
      
      <!-- Labels Per Page -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          {{ $t('gestlab.general.labels.vap_labels.labels_per_page') }}
        </label>
        <input
          v-model="settings.labels_per_page"
          type="range"
          min="1"
          max="12"
          class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
        />
        <div class="flex justify-between text-xs text-gray-500">
          <span>1</span>
          <span class="font-medium">{{ settings.labels_per_page }}</span>
          <span>12</span>
        </div>
      </div>
      
      <!-- Margin -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          {{ $t('gestlab.general.labels.vap_labels.margin') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.margin"
            type="range"
            min="0"
            max="20"
            step="1"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
          />
          <span class="text-sm text-gray-600 w-12 text-right">
            {{ settings.margin }}mm
          </span>
        </div>
        <p class="text-xs text-yellow-600">
          {{ $t('gestlab.general.labels.vap_labels.warning_margin') }}
        </p>
      </div>
      
      <!-- Columns & Rows for Grid -->
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.columns') }}
          </label>
          <input
            v-model="settings.columns"
            type="number"
            min="1"
            max="10"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.rows') }}
          </label>
          <input
            v-model="settings.rows"
            type="number"
            min="1"
            max="20"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          />
        </div>
      </div>
      
      <!-- Spacing -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          {{ $t('gestlab.general.labels.vap_labels.spacing') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.spacing"
            type="range"
            min="0"
            max="20"
            step="1"
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
          />
          <span class="text-sm text-gray-600 w-12 text-right">
            {{ settings.spacing }}mm
          </span>
        </div>
      </div>
      
      <!-- Page Size -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          {{ $t('gestlab.general.labels.vap_labels.page_size') }}
        </label>
        <select
          v-model="settings.page_size"
          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
        >
          <option value="A4">A4</option>
          <option value="A3">A3</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
      </div>
      
      <!-- Orientation -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          {{ $t('gestlab.general.labels.vap_labels.orientation') }}
        </label>
        <div class="flex gap-2">
          <button
            @click="settings.orientation = 'portrait'"
            :class="[
              'flex-1 py-2 text-sm font-medium rounded-lg border',
              settings.orientation === 'portrait'
                ? 'border-blue-900 bg-blue-50 text-blue-900'
                : 'border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ $t('gestlab.general.labels.vap_labels.portrait') }}
          </button>
          <button
            @click="settings.orientation = 'landscape'"
            :class="[
              'flex-1 py-2 text-sm font-medium rounded-lg border',
              settings.orientation === 'landscape'
                ? 'border-blue-900 bg-blue-50 text-blue-900'
                : 'border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ $t('gestlab.general.labels.vap_labels.landscape') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      include_cutouts: true,
      labels_per_page: 1,
      margin: 5,
      columns: 2,
      rows: 4,
      spacing: 5,
      page_size: 'A4',
      orientation: 'portrait',
    })
  }
})

const emit = defineEmits(['update:modelValue'])

const settings = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})
</script>