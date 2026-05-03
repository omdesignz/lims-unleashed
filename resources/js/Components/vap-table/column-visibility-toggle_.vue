<template>
  <div class="relative">
    <!-- Column Visibility Toggle Button -->
    <button
      @click="toggleMenu"
      :class="[
        'inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
        isOpen 
          ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white border-blue-900' 
          : 'border-gray-300 bg-white text-gray-700 hover:border-blue-900 hover:text-blue-900 hover:bg-gray-50'
      ]"
    >
      <EyeIcon class="h-4 w-4" />
      {{ $t('gestlab.general.labels.columns') }}
      <ChevronDownIcon 
        class="h-4 w-4 transition-transform duration-200" 
        :class="isOpen ? 'rotate-180' : ''"
      />
    </button>

    <!-- Column Visibility Panel -->
    <div 
      v-if="isOpen"
      class="absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 overflow-hidden"
      v-click-outside="closeMenu"
    >
      <!-- Panel Header -->
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-3">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            <ViewColumnsIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.column_visibility') }}
          </h3>
          <button
            @click="closeMenu"
            class="text-blue-100 hover:text-white transition-colors duration-200 p-1 rounded-full hover:bg-white/10"
            :title="$t('gestlab.general.buttons.close')"
          >
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
        <p class="mt-1 text-xs text-blue-100">
          {{ $t('gestlab.general.messages.column_visibility_description') }}
        </p>
      </div>

      <!-- Column List -->
      <div class="max-h-96 overflow-y-auto p-4">
        <div class="space-y-3">
          <!-- Column Item -->
          <div 
            v-for="(column, index) in columns" 
            :key="column.field"
            class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors duration-150"
            :class="{
              'bg-blue-50': column.visible,
              'border-l-4 border-blue-900': column.visible
            }"
          >
            <!-- Column Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-2 w-2 rounded-full" :class="column.visible ? 'bg-blue-900' : 'bg-gray-300'"></span>
                <span class="text-sm font-medium text-gray-900 truncate">
                  {{ column.label }}
                </span>
              </div>
              <div v-if="column.description" class="mt-1 text-xs text-gray-500 truncate">
                {{ column.description }}
              </div>
            </div>

            <!-- Toggle Switch -->
            <button
              @click="toggleColumnVisibility(column.field)"
              type="button"
              :class="[
                'relative inline-flex h-5 w-10 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                column.visible 
                  ? 'bg-gradient-to-r from-blue-900 to-blue-800' 
                  : 'bg-gray-300 hover:bg-gray-400'
              ]"
              :title="column.visible ? $t('gestlab.actions.hide_column') : $t('gestlab.actions.show_column')"
              :aria-label="column.visible ? `Ocultar coluna ${column.label}` : `Mostrar coluna ${column.label}`"
            >
              <span 
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition duration-200',
                  column.visible ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <!-- Actions Footer -->
          <div class="pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between gap-2">
              <button
                @click="toggleAllColumnsVisibility(true)"
                class="text-xs font-medium text-blue-900 hover:text-blue-700 transition-colors duration-200"
              >
                {{ $t('gestlab.actions.show_all') }}
              </button>
              <button
                @click="toggleAllColumnsVisibility(false)"
                class="text-xs font-medium text-blue-900 hover:text-blue-700 transition-colors duration-200"
              >
                {{ $t('gestlab.actions.hide_all') }}
              </button>
              <button
                @click="resetToDefault"
                class="text-xs font-medium text-blue-900 hover:text-blue-700 transition-colors duration-200"
              >
                {{ $t('gestlab.actions.reset_defaults') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel Footer -->
      <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500">
            {{ visibleColumnsCount }} de {{ columns.length }} colunas visíveis
          </span>
          <button
            @click="saveVisibility"
            :disabled="!hasChanges"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
              hasChanges
                ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed'
            ]"
          >
            <CheckIcon class="h-3 w-3" />
            {{ $t('gestlab.general.buttons.apply') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import {
  EyeIcon,
  EyeSlashIcon,
  ChevronDownIcon,
  XMarkIcon,
  CheckIcon,
  ViewColumnsIcon
} from "@heroicons/vue/24/outline";

// Directives
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = (event) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
};

const props = defineProps({
  columns: Array,
});

const emit = defineEmits(['update-columns']);

const isOpen = ref(false);
const tempColumns = reactive([...props.columns]);

// Computed properties
const visibleColumnsCount = computed(() => 
  tempColumns.filter(column => column.visible).length
);

const hasChanges = computed(() => {
  return JSON.stringify(tempColumns) !== JSON.stringify(props.columns);
});

// Methods
const toggleMenu = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    // Reset temp columns when opening
    tempColumns.splice(0, tempColumns.length, ...props.columns.map(col => ({ ...col })));
  }
};

const closeMenu = () => {
  isOpen.value = false;
};

const toggleColumnVisibility = (field) => {
  const column = tempColumns.find(col => col.field === field);
  if (column) {
    column.visible = !column.visible;
  }
};

const toggleAllColumnsVisibility = (visible) => {
  tempColumns.forEach(column => {
    column.visible = visible;
  });
};

const resetToDefault = () => {
  // Reset to original visibility state
  tempColumns.splice(0, tempColumns.length, ...props.columns.map(col => ({ 
    ...col, 
    visible: col.defaultVisible !== undefined ? col.defaultVisible : true 
  })));
};

const saveVisibility = () => {
  if (hasChanges.value) {
    emit('update-columns', [...tempColumns]);
    closeMenu();
  }
};
</script>

<style scoped>
/* Custom scrollbar for panel */
.max-h-96::-webkit-scrollbar {
  width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Smooth transitions */
button, div {
  transition: all 0.2s ease-in-out;
}

/* Animation for panel entry */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.absolute {
  animation: slideIn 0.2s ease-out;
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

/* Custom styles for disabled states */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Hover effects for column items */
.hover\\:bg-gray-50:hover {
  background-color: #f9fafb;
}

/* Gradient border for selected columns */
.border-l-4 {
  border-left-style: solid;
  border-left-width: 4px;
}
</style>