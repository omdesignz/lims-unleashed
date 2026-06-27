<template>
  <Modal
    :show="true"
    maxWidth="2xl"
    @close="closeModal"
  >
    <div class="quality-certificate-validation space-y-6 p-6 sm:p-8" :class="commercialDocumentThemeClasses">
      <div class="ds-card p-6">
        <div class="text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb)/1)] text-white shadow-lg shadow-[rgb(var(--primary-900-rgb)/0.2)] dark:bg-[rgb(var(--primary-400-rgb)/1)] dark:text-[rgb(var(--primary-950-rgb)/1)]">
            <CheckBadgeIcon class="h-6 w-6" />
          </div>
          <h3 class="mt-4 text-xl font-extrabold text-[color:var(--ds-text)]">
            {{ modalTitle }}
          </h3>
          <p class="mt-2 text-sm text-[color:var(--ds-text-muted)]">
            {{ modalDescription }}
          </p>
        </div>
      </div>

      <form
        class="space-y-6"
        @submit.prevent="updateRecord"
      >
        <div>
          <DocumentValidationSignature
            :title="signatureTitle" 
            @save="form.signature = $event" 
          />
        </div>

        <div class="ds-panel p-5 sm:p-6">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-extrabold text-[color:var(--ds-text)]">
                {{ $t('gestlab.general.labels.quality_certificates.sign_on_behalf') }}
              </h4>
              <p class="mt-1 text-sm text-[color:var(--ds-text-muted)]">
                {{ $t('gestlab.general.labels.quality_certificates.sign_on_behalf_description') }}
              </p>
            </div>
            
            <button
              type="button"
              @click="toggleOnBehalfOf"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2',
                isOnBehalfOf 
                  ? 'bg-[rgb(var(--primary-800-rgb)/1)] dark:bg-[rgb(var(--primary-400-rgb)/1)]'
                  : 'bg-[color:var(--ds-border-strong)]'
              ]"
              :aria-label="isOnBehalfOf ? $t('gestlab.general.labels.quality_certificates.disable_on_behalf') : $t('gestlab.general.labels.quality_certificates.enable_on_behalf')"
            >
              <span 
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition duration-200',
                  isOnBehalfOf ? 'translate-x-6' : 'translate-x-1'
                ]"
              />
            </button>
          </div>

          <div 
            v-if="isOnBehalfOf"
            class="mt-4 animate-slideIn"
          >
            <label class="ds-field-label mb-2 block">
              {{ $t('gestlab.general.labels.quality_certificates.select_user') }}
              <span class="text-red-500 ml-0.5">*</span>
            </label>
            <combobox-enhanced
              name="user_id"
              :hasError="form.errors.user_id"
              v-model="form.user_id"
              :load-options="loadUsers"
              :placeholder="$t('gestlab.general.placeholders.select_user')"
              class="w-full"
            />
            <p v-if="form.errors.user_id" class="mt-1 text-xs text-red-600 dark:text-red-400">
              {{ form.errors.user_id }}
            </p>
            <p class="mt-2 text-xs text-[color:var(--ds-text-muted)]">
              {{ $t('gestlab.general.labels.quality_certificates.on_behalf_of_warning') }}
            </p>
          </div>
        </div>

        <div v-if="form.signature" class="rounded-[24px] border border-green-200 bg-green-50/90 p-4 shadow-sm dark:border-green-500/20 dark:bg-green-500/10">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/15">
              <CheckCircleIcon class="h-4 w-4 text-green-600" />
            </div>
            <div>
              <h4 class="text-sm font-semibold text-green-900 dark:text-green-200">
                {{ $t('gestlab.general.labels.quality_certificates.signature_ready') }}
              </h4>
              <p class="text-xs text-green-700 dark:text-green-300">
                {{ $t('gestlab.general.labels.quality_certificates.signature_ready_description') }}
              </p>
            </div>
          </div>
        </div>

        <div class="ds-panel sticky bottom-0 px-5 py-4">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-sm text-[color:var(--ds-text-muted)]">
            {{ $t('gestlab.general.labels.quality_certificates.action_irreversible') }}
          </div>
          
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                @click="closeModal"
                class="ds-button ds-button-secondary"
              >
                {{ $t('gestlab.general.buttons.cancel') }}
              </button>
              
              <button
                type="submit"
                :disabled="form.processing || !form.signature"
                :class="[
                  'ds-button ds-button-primary',
                  form.processing || !form.signature
                    ? 'cursor-not-allowed'
                    : ''
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
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { watch, computed } from 'vue'
import Modal from "@/Components/modal.vue";
import ComboboxEnhanced from "@/Components/combobox-enhanced.vue";
import DocumentValidationSignature from '@/Components/document-validation-signature.vue';
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import {
  CheckBadgeIcon,
  CheckCircleIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
  record: {
    type: Object,
    default: () => ({}),
  },
  action: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  url: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['close'])

const form = useForm({
  verified_on_behalf_of: props.record?.data?.verified_on_behalf_of || false,
  approve_on_behalf_of: props.record?.data?.approve_on_behalf_of || false,
  user_id: props.record?.data?.signed_by_user_id || null,
  signature: null,
  id: props.record?.data?.id || null,
})

// Computed properties
const isOnBehalfOf = computed(() => {
  return props.action === 'verify' 
    ? form.verified_on_behalf_of 
    : form.approve_on_behalf_of;
});

const modalTitle = computed(() => {
  return props.action === 'verify' 
    ? trans('gestlab.general.labels.quality_certificates.verify_certificate')
    : trans('gestlab.general.labels.quality_certificates.validate_certificate');
});

const modalDescription = computed(() => {
  return props.action === 'verify' 
    ? trans('gestlab.general.labels.quality_certificates.verify_description')
    : trans('gestlab.general.labels.quality_certificates.validate_description');
});

const signatureTitle = computed(() => {
  return props.action === 'verify' 
    ? trans('gestlab.general.labels.quality_certificates.verify_signature')
    : trans('gestlab.general.labels.quality_certificates.validate_signature');
});

const actionButtonText = computed(() => {
  return props.action === 'verify'
    ? trans('gestlab.general.labels.quality_certificates.verify_certificate')
    : trans('gestlab.general.labels.quality_certificates.validate_certificate');
});

// Watch for record changes
watch(
  () => props.record,
  (record) => {
    if (record) {
      form.verified_on_behalf_of = record.data?.verified_on_behalf_of || false;
      form.approve_on_behalf_of = record.data?.approve_on_behalf_of || false;
      form.user_id = record.data?.signed_by_user_id || null;
      form.id = record.data?.id || null;
    }
  },
  { immediate: true },
)

// Methods
const closeModal = () => {
  emit('close');
}

const toggleOnBehalfOf = () => {
  if (props.action === 'verify') {
    form.verified_on_behalf_of = !form.verified_on_behalf_of;
  } else {
    form.approve_on_behalf_of = !form.approve_on_behalf_of;
  }
  
  // Clear user selection when disabling on behalf of
  if (!isOnBehalfOf.value) {
    form.user_id = null;
  }
}

const updateRecord = () => {
  const payload = {
    certificate: form.id,
    signature: form.signature,
  };

  // Add on behalf of flag based on action
  if (props.action === 'verify') {
    payload.verified_on_behalf_of = form.verified_on_behalf_of;
    if (form.verified_on_behalf_of && form.user_id) {
      payload.signed_by_user_id = form.user_id;
    }
  } else {
    payload.approve_on_behalf_of = form.approve_on_behalf_of;
    if (form.approve_on_behalf_of && form.user_id) {
      payload.signed_by_user_id = form.user_id;
    }
  }

  form.transform(() => payload).post(props.url, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      closeModal();
    },
  });
}

const loadUsers = (query, setOptions) => {
  return loadSelectOptions('/users/getUser', query, setOptions, optionMappers.name);
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

/* Focus states for accessibility */
.quality-certificate-validation button:focus-visible {
  outline: 2px solid rgb(var(--primary-700-rgb));
  outline-offset: 2px;
}

/* Disabled state styling */
.quality-certificate-validation button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
