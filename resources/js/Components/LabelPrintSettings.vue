<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
    <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-slate-100">
      {{ $t('gestlab.general.labels.vap_labels.print_settings') }}
    </h3>
    
    <div class="space-y-4">
      <!-- Include Cutouts -->
      <div class="flex items-center justify-between">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_labels.include_cutouts') }}
          </label>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ $t('gestlab.general.labels.vap_labels.cutout_info') }}
          </p>
        </div>
        <div class="flex items-center">
          <input
            v-model="settings.include_cutouts"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-primary-700 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-900"
          />
        </div>
      </div>
      
      <!-- Labels Per Page -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_labels.labels_per_page') }}
        </label>
        <input
          v-model="settings.labels_per_page"
          type="range"
          min="1"
          max="12"
          class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 accent-primary-700 dark:bg-slate-700"
        />
        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
          <span>1</span>
          <span class="font-medium">{{ settings.labels_per_page }}</span>
          <span>12</span>
        </div>
      </div>
      
      <!-- Margin -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_labels.margin') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.margin"
            type="range"
            min="0"
            max="20"
            step="1"
            class="h-2 flex-1 cursor-pointer appearance-none rounded-lg bg-slate-200 accent-primary-700 dark:bg-slate-700"
          />
          <span class="w-12 text-right text-sm text-slate-600 dark:text-slate-300">
            {{ settings.margin }}mm
          </span>
        </div>
        <p class="text-xs text-amber-600 dark:text-amber-300">
          {{ $t('gestlab.general.labels.vap_labels.warning_margin') }}
        </p>
      </div>
      
      <!-- Columns & Rows for Grid -->
      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_labels.columns') }}
          </label>
          <input
            v-model="settings.columns"
            type="number"
            min="1"
            max="10"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_labels.rows') }}
          </label>
          <input
            v-model="settings.rows"
            type="number"
            min="1"
            max="20"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          />
        </div>
      </div>
      
      <!-- Spacing -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_labels.spacing') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.spacing"
            type="range"
            min="0"
            max="20"
            step="1"
            class="h-2 flex-1 cursor-pointer appearance-none rounded-lg bg-slate-200 accent-primary-700 dark:bg-slate-700"
          />
          <span class="w-12 text-right text-sm text-slate-600 dark:text-slate-300">
            {{ settings.spacing }}mm
          </span>
        </div>
      </div>
      
      <!-- Page Size -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_labels.page_size') }}
        </label>
        <select
          v-model="settings.page_size"
          class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
        >
          <option value="A4">A4</option>
          <option value="A3">A3</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
      </div>
      
      <!-- Orientation -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_labels.orientation') }}
        </label>
        <div class="flex gap-2">
          <button
            type="button"
            @click="settings.orientation = 'portrait'"
            :class="[
              'flex-1 rounded-2xl border py-2 text-sm font-medium transition',
              settings.orientation === 'portrait'
                ? 'border-primary-700 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-500/15 dark:text-primary-100'
                : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
            ]"
          >
            {{ $t('gestlab.general.labels.vap_labels.portrait') }}
          </button>
          <button
            type="button"
            @click="settings.orientation = 'landscape'"
            :class="[
              'flex-1 rounded-2xl border py-2 text-sm font-medium transition',
              settings.orientation === 'landscape'
                ? 'border-primary-700 bg-primary-50 text-primary-900 dark:border-primary-500 dark:bg-primary-500/15 dark:text-primary-100'
                : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
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
