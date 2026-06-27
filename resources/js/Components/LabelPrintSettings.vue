<template>
  <div class="ds-panel p-5 sm:p-6">
    <h3 class="mb-5 text-lg font-extrabold text-[var(--ds-text)]">
      {{ $t('gestlab.general.labels.vap_labels.print_settings') }}
    </h3>
    
    <div class="space-y-4">
      <!-- Include Cutouts -->
      <div class="flex items-center justify-between">
        <div>
          <label class="ds-field-label mb-1 block">
            {{ $t('gestlab.general.labels.vap_labels.include_cutouts') }}
          </label>
          <p class="ds-field-hint">
            {{ $t('gestlab.general.labels.vap_labels.cutout_info') }}
          </p>
        </div>
        <div class="flex items-center">
          <input
            v-model="settings.include_cutouts"
            type="checkbox"
            class="ds-checkbox"
          />
        </div>
      </div>
      
      <!-- Labels Per Page -->
      <div class="space-y-2">
        <label class="ds-field-label block">
          {{ $t('gestlab.general.labels.vap_labels.labels_per_page') }}
        </label>
        <input
          v-model="settings.labels_per_page"
          type="range"
          min="1"
          max="12"
          class="h-2 w-full cursor-pointer appearance-none rounded-full bg-[var(--ds-panel-muted)] accent-[rgb(var(--primary-700-rgb))]"
        />
        <div class="flex justify-between text-xs text-[var(--ds-text-muted)]">
          <span>1</span>
          <span class="font-medium">{{ settings.labels_per_page }}</span>
          <span>12</span>
        </div>
      </div>
      
      <!-- Margin -->
      <div class="space-y-2">
        <label class="ds-field-label block">
          {{ $t('gestlab.general.labels.vap_labels.margin') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.margin"
            type="range"
            min="0"
            max="20"
            step="1"
            class="h-2 flex-1 cursor-pointer appearance-none rounded-full bg-[var(--ds-panel-muted)] accent-[rgb(var(--primary-700-rgb))]"
          />
          <span class="w-12 text-right text-sm font-bold text-[var(--ds-text-muted)]">
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
          <label class="ds-field-label block">
            {{ $t('gestlab.general.labels.vap_labels.columns') }}
          </label>
          <input
            v-model="settings.columns"
            type="number"
            min="1"
            max="10"
            class="ds-field"
          />
        </div>
        <div class="space-y-2">
          <label class="ds-field-label block">
            {{ $t('gestlab.general.labels.vap_labels.rows') }}
          </label>
          <input
            v-model="settings.rows"
            type="number"
            min="1"
            max="20"
            class="ds-field"
          />
        </div>
      </div>
      
      <!-- Spacing -->
      <div class="space-y-2">
        <label class="ds-field-label block">
          {{ $t('gestlab.general.labels.vap_labels.spacing') }}
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model="settings.spacing"
            type="range"
            min="0"
            max="20"
            step="1"
            class="h-2 flex-1 cursor-pointer appearance-none rounded-full bg-[var(--ds-panel-muted)] accent-[rgb(var(--primary-700-rgb))]"
          />
          <span class="w-12 text-right text-sm font-bold text-[var(--ds-text-muted)]">
            {{ settings.spacing }}mm
          </span>
        </div>
      </div>
      
      <!-- Page Size -->
      <div class="space-y-2">
        <label class="ds-field-label block">
          {{ $t('gestlab.general.labels.vap_labels.page_size') }}
        </label>
        <select
          v-model="settings.page_size"
          class="ds-field"
        >
          <option value="A4">A4</option>
          <option value="A3">A3</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
      </div>
      
      <!-- Orientation -->
      <div class="space-y-2">
        <label class="ds-field-label block">
          {{ $t('gestlab.general.labels.vap_labels.orientation') }}
        </label>
        <div class="flex gap-2">
          <button
            type="button"
            @click="settings.orientation = 'portrait'"
            :class="[
              'ds-button flex-1',
              settings.orientation === 'portrait'
                ? 'ds-button-primary'
                : 'ds-button-secondary'
            ]"
          >
            {{ $t('gestlab.general.labels.vap_labels.portrait') }}
          </button>
          <button
            type="button"
            @click="settings.orientation = 'landscape'"
            :class="[
              'ds-button flex-1',
              settings.orientation === 'landscape'
                ? 'ds-button-primary'
                : 'ds-button-secondary'
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
