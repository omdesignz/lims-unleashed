<template>
  <Modal
    :show="true"
    maxWidth="2xl"
    @close="closeModal"
  >
    <div class="quality-certificate-validation space-y-6 p-6 sm:p-8" :class="commercialDocumentThemeClasses">
      <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
        <div class="text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] shadow-lg shadow-[rgb(var(--primary-900-rgb)/0.2)] dark:from-[rgb(var(--primary-300-rgb))] dark:to-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]">
          <CheckBadgeIcon class="h-6 w-6 text-white" />
          </div>
          <h3 class="mt-4 text-xl font-semibold text-slate-900 dark:text-white">
            {{ modalTitle }}
          </h3>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            {{ modalDescription }}
          </p>
        </div>
      </div>

      <form
        class="space-y-6"
        @submit.prevent="updateRecord"
      >
        <div class="rounded-[26px] border border-slate-200 bg-slate-50/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <DocumentValidationSignature
            :record="record"
            :action="action"
            :url="url"
            :title="signatureTitle" 
            @save="form.signature = $event" 
          />
        </div>

        <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.18)] dark:border-slate-800 dark:bg-slate-950/85">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                {{ $t('gestlab.general.labels.quality_certificates.sign_on_behalf') }}
              </h4>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.quality_certificates.sign_on_behalf_description') }}
              </p>
            </div>
            
            <button
              type="button"
              @click="toggleOnBehalfOf"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:focus:ring-[rgb(var(--primary-300-rgb))]',
                isOnBehalfOf 
                  ? 'bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] dark:from-[rgb(var(--primary-300-rgb))] dark:to-[rgb(var(--primary-500-rgb))]'
                  : 'bg-slate-300 hover:bg-slate-400 dark:bg-slate-700 dark:hover:bg-slate-600'
              ]"
              :aria-label="isOnBehalfOf ? 'Desativar assinatura por outrem' : 'Ativar assinatura por outrem'"
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
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
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
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
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

        <div class="sticky bottom-0 rounded-[26px] border border-slate-200/80 bg-white/95 px-5 py-4 shadow-[0_-12px_40px_-28px_rgba(15,23,42,0.35)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-sm text-slate-500 dark:text-slate-400">
            {{ $t('gestlab.general.labels.quality_certificates.action_irreversible') }}
          </div>
          
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                @click="closeModal"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
              >
                {{ $t('gestlab.general.buttons.cancel') }}
              </button>
              
              <button
                type="submit"
                :disabled="form.processing || !form.signature"
                :class="[
                  'inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950',
                  form.processing || !form.signature
                    ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                    : 'bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] text-white hover:from-[rgb(var(--primary-800-rgb))] hover:to-[rgb(var(--primary-600-rgb))] dark:from-[rgb(var(--primary-300-rgb))] dark:to-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]'
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

const props = defineProps({
  record: Object,
  action: String,
  title: String,
  url: String
})

const emit = defineEmits(['close'])

const form = useForm({
  verified_on_behalf_of: props.record.data?.verified_on_behalf_of || false,
  approve_on_behalf_of: props.record.data?.approve_on_behalf_of || false,
  user_id: props.record.data?.signed_by_user_id || null,
  signature: null,
  id: props.record.data.id,
})

// Computed properties
const isOnBehalfOf = computed(() => {
  return props.action === 'verify' 
    ? form.verified_on_behalf_of 
    : form.approve_on_behalf_of;
});

const modalTitle = computed(() => {
  return props.action === 'verify' 
    ? 'Verificar Certificado' 
    : 'Validar Certificado';
});

const modalDescription = computed(() => {
  return props.action === 'verify' 
    ? 'Por favor, assine digitalmente para verificar este certificado de qualidade.'
    : 'Por favor, assine digitalmente para aprovar e validar este certificado de qualidade.';
});

const signatureTitle = computed(() => {
  return props.action === 'verify' 
    ? 'Assinatura para Verificação' 
    : 'Assinatura para Validação';
});

const actionButtonText = computed(() => {
  return props.action === 'verify' ? 'Verificar Certificado' : 'Validar Certificado';
});

// Watch for record changes
watch(
  () => props.record,
  (record) => {
    if (record) {
      form.verified_on_behalf_of = record.data?.verified_on_behalf_of || false;
      form.approve_on_behalf_of = record.data?.approve_on_behalf_of || false;
      form.user_id = record.data?.signed_by_user_id || null;
      form.id = record.data.id;
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

  form.post(props.url, payload, {
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
