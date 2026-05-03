<template>
  <Modal
    :title="modalTitle"
    maxWidth="2xl"
    @close="closeModal"
  >
    <div class="space-y-6">
      <!-- Modal Header -->
      <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-900 to-blue-800">
          <CheckBadgeIcon class="h-6 w-6 text-white" />
        </div>
        <h3 class="mt-4 text-lg font-semibold text-gray-900">
          {{ modalTitle }}
        </h3>
        <p class="mt-2 text-sm text-gray-600">
          {{ modalDescription }}
        </p>
      </div>

      <form
        class="space-y-6"
        @submit.prevent="updateRecord"
      >
        <!-- Invoice Type -->
        <!-- <div class="rounded-xl border border-gray-200 bg-gray-50 p-6"> -->
          
            <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ $t('gestlab.general.labels.invoices.type_id') }}
                            </label>
                            <comboboxEnhanced 
                                :hasError="false" 
                                v-model="form.type_id" 
                                :load-options="loadInvoiceCategories"
                                placeholder="Select invoice type..."
                            />
                        </div>
                    </div>
                </div>
            
        <!-- </div> -->

        <!-- Modal Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
          <div class="text-sm text-gray-500">
            {{ $t('gestlab.general.labels.quality_certificates.action_irreversible') }}
          </div>
          
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="closeModal"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              {{ $t('gestlab.general.buttons.cancel') }}
            </button>
            
            <button
              type="submit"
              :disabled="form.processing || !form.type_id"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                form.processing || !form.type_id
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              <template v-if="form.processing">
                <ArrowPathIcon class="h-4 w-4 animate-spin" />
                {{ $t('gestlab.general.buttons.processing') }}
              </template>
              <template v-else>
                <CheckBadgeIcon class="h-4 w-4" />
                {{ actionButtonText }}
              </template>
            </button>
          </div>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { watch, computed, onMounted } from 'vue'
import Modal from "@/Components/modal.vue";
import comboboxEnhanced from "@/Components/combobox-enhanced.vue";
import {
  CheckBadgeIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  record: Object,
  action: String,
  title: String,
  url: String
})

const emit = defineEmits(['close'])

const form = useForm({
  quote_id: props.record.data.id,
  type_id: null
})

// Computed properties

const modalTitle = computed(() => {
  return 'Converter Proforma';
});

const modalDescription = computed(() => {
  return 'Por favor, seleccione o tipo de factura para a devida conversão.';
});

const actionButtonText = computed(() => {
  return 'Converter Proforma';
});

// Watch for record changes
watch(
  () => props.record,
  (record) => {
    if (record) {
      form.quote_id = record.data.id;
    }
  },
  { immediate: true },
)

// Methods
const closeModal = () => {
  emit('close');
}

const updateRecord = () => {
  const payload = {
    quote_id: form.id,
    type_id: form.type_id,
  };

  form.post(props.url, payload, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      emit('close');
    },
  });
}

onMounted(() => {
    console.log(usePage());
});

function loadInvoiceCategories(query, setOptions) {
    fetch('/invoicecategories/getInvoiceCategory?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => ({
            value: result.id,
            label: result.code,
        }))
        );
    });
}
</script>

<style scoped>
/* Custom animations */
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

.animate-slideIn {
  animation: slideIn 0.3s ease-out;
}

/* Signature validation states */
.border-green-200 {
  border-color: #d1fae5;
}

.bg-green-50 {
  background-color: #f0fdf4;
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

/* Disabled state styling */
button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Smooth transitions */
button, div, input {
  transition: all 0.2s ease-in-out;
}

/* Loading spinner animation */
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
</style>