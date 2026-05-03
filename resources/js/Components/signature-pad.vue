<script setup>
import { ref, computed } from 'vue';
import { VPerfectSignature } from 'v-perfect-signature';
import { 
  XMarkIcon, 
  ArrowDownTrayIcon, 
  TrashIcon, 
  PencilIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';
import confirmDialog from "@/Components/confirm-dialog.vue";

const props = defineProps({
  currentSignature: String
});

const emit = defineEmits(['save', 'delete']);

// Refs
const signaturePad = ref();
const showDeleteConfirmation = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);

// Stroke options for signature pad
const strokeOptions = {
  size: 4,
  thinning: 0.7,
  smoothing: 0.5,
  streamline: 0.5,
  color: '#1e3a8a', // blue-900
};

// Computed
const isSignatureEmpty = computed(() => {
  return signaturePad.value?.isEmpty();
});

const hasExistingSignature = computed(() => {
  return props?.currentSignature?.length > 0;
});

const signatureStatus = computed(() => {
  if (hasExistingSignature.value) {
    return {
      label: 'signed',
      color: 'bg-green-100 text-green-800',
      icon: CheckCircleIcon
    };
  } else if (!isSignatureEmpty.value) {
    return {
      label: 'draft',
      color: 'bg-yellow-100 text-yellow-800',
      icon: PencilIcon
    };
  } else {
    return {
      label: 'empty',
      color: 'bg-gray-100 text-gray-800',
      icon: DocumentTextIcon
    };
  }
});

// Methods
const clearSignature = () => {
  if (signaturePad.value) {
    signaturePad.value.clear();
  }
};

const downloadSignature = () => {
  if (isSignatureEmpty.value) {
    alert('Please create a signature first!');
    return;
  }

  const link = document.createElement('a');
  link.download = 'signature.png';
  link.href = signaturePad.value.toDataURL();
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const saveSignature = () => {
  if (isSignatureEmpty.value) {
    alert('Please create a signature first!');
    return;
  }

  isSaving.value = true;
  const signatureData = signaturePad.value.toDataURL();
  
  emit('save', signatureData);
  
  // Simulate save delay
  setTimeout(() => {
    isSaving.value = false;
  }, 1000);
};

const deleteSignature = () => {
  showDeleteConfirmation.value = true;
};

const handleConfirmDelete = () => {
  isDeleting.value = true;
  
  emit('delete');
  
  // Simulate delete delay
  setTimeout(() => {
    isDeleting.value = false;
    showDeleteConfirmation.value = false;
  }, 1000);
};

const resetSignature = () => {
  if (signaturePad.value) {
    signaturePad.value.clear();
  }
};
</script>

<template>
  <!-- SIGNATURE CARD -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- CARD HEADER -->
    <div class="border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="rounded-lg bg-blue-100 p-2">
            <PencilIcon class="h-6 w-6 text-blue-900" />
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.signature.title') }}
            </h2>
            <p class="text-sm text-gray-500">
              {{ $t('gestlab.general.labels.signature.description') }}
            </p>
          </div>
        </div>
        <span :class="[
          'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
          signatureStatus.color
        ]">
          <component :is="signatureStatus.icon" class="h-3 w-3" />
          {{ $t(`gestlab.general.status.${signatureStatus.label}`) }}
        </span>
      </div>
    </div>

    <!-- CARD CONTENT -->
    <div class="p-6">
      <!-- EXISTING SIGNATURE VIEW -->
      <div v-if="hasExistingSignature" class="space-y-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.signature.current_signature') }}
          </h3>
          <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
            <img 
              :src="props.currentSignature" 
              alt="Signature"
              class="mx-auto h-32 object-contain"
            />
          </div>
          <div class="mt-4 text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
              <CheckCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.signature.signature_verified') }}
            </div>
          </div>
        </div>
      </div>

      <!-- SIGNATURE PAD -->
      <div v-else class="space-y-6">
        <div>
          <h3 class="text-sm font-semibold text-gray-900 mb-2">
            {{ $t('gestlab.general.labels.signature.create_signature') }}
          </h3>
          <p class="text-sm text-gray-500 mb-4">
            {{ $t('gestlab.general.labels.signature.signature_instructions') }}
          </p>
          
          <!-- SIGNATURE CANVAS -->
          <div class="relative border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50">
            <VPerfectSignature 
              :stroke-options="strokeOptions"
              ref="signaturePad"
              class="h-48 w-full cursor-crosshair bg-white"
            />
            
            <!-- EMPTY STATE OVERLAY -->
            <div 
              v-if="isSignatureEmpty"
              class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
              <div class="text-center">
                <div class="mx-auto h-12 w-12 text-gray-300 mb-2">
                  <PencilIcon class="h-12 w-12" />
                </div>
                <p class="text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.signature.draw_signature_here') }}
                </p>
              </div>
            </div>
          </div>

          <!-- SIGNATURE PREVIEW -->
          <div v-if="!isSignatureEmpty" class="mt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">
              {{ $t('gestlab.general.labels.signature.preview') }}
            </h4>
            <div class="flex items-center gap-4">
              <div class="flex-1 bg-gray-50 rounded-lg border border-gray-200 p-3">
                <img 
                  :src="signaturePad?.toDataURL()" 
                  alt="Signature preview"
                  class="h-12 object-contain"
                />
              </div>
              <div class="text-sm text-gray-500">
                {{ $t('gestlab.general.labels.signature.signature_ready') }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex flex-wrap gap-3 justify-between">
          <div class="flex flex-wrap gap-2">
            <!-- CLEAR BUTTON -->
            <button
              v-if="!hasExistingSignature && !isSignatureEmpty"
              @click="clearSignature"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <XMarkIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.clear') }}
            </button>

            <!-- DOWNLOAD BUTTON -->
            <button
              v-if="!hasExistingSignature && !isSignatureEmpty"
              @click="downloadSignature"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.download') }}
            </button>

            <!-- RESET BUTTON -->
            <button
              v-if="hasExistingSignature"
              @click="resetSignature"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-blue-900 px-4 py-2.5 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.new') }}
            </button>
          </div>

          <div class="flex flex-wrap gap-2">
            <!-- DELETE BUTTON -->
            <button
              v-if="hasExistingSignature"
              @click="deleteSignature"
              :disabled="isDeleting"
              type="button"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200',
                isDeleting
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
              ]"
            >
              <ArrowPathIcon v-if="isDeleting" class="h-4 w-4 animate-spin" />
              <TrashIcon v-else class="h-4 w-4" />
              {{ isDeleting ? $t('gestlab.general.buttons.deleting') : $t('gestlab.general.buttons.delete') }}
            </button>

            <!-- SAVE BUTTON -->
            <button
              v-if="!hasExistingSignature"
              @click="saveSignature"
              :disabled="isSignatureEmpty || isSaving"
              type="button"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200',
                isSignatureEmpty || isSaving
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <ArrowPathIcon v-if="isSaving" class="h-4 w-4 animate-spin" />
              <CheckCircleIcon v-else class="h-4 w-4" />
              {{ isSaving ? $t('gestlab.general.buttons.saving') : $t('gestlab.general.buttons.save') }}
            </button>
          </div>
        </div>
      </div>

      <!-- HELP TEXT -->
      <div class="mt-6">
        <div class="rounded-lg bg-blue-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-5 w-5 text-blue-900" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-900">
                {{ $t('gestlab.general.labels.signature.important_note') }}
              </h3>
              <div class="mt-2 text-sm text-blue-700">
                <p>{{ $t('gestlab.general.labels.signature.signature_legal_note') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION DIALOG -->
  <confirm-dialog
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="handleConfirmDelete"
    v-if="showDeleteConfirmation"
    :title="$t('gestlab.actions.confirmation_dialog_title.delete')"
    :description="$t('gestlab.actions.confirmation_dialog_description.delete')"
    :confirm="$t('gestlab.general.buttons.confirm')"
    :cancel="$t('gestlab.general.buttons.cancel')"
  >
    <div class="mt-4 space-y-4">
      <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-800">
        <ExclamationTriangleIcon class="h-4 w-4" />
        <p>{{ $t('gestlab.general.labels.signature.delete_warning') }}</p>
      </div>
      
      <div class="space-y-3">
        <div class="flex items-center gap-3 text-sm">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-500 flex-shrink-0" />
          <p class="text-gray-700">
            {{ $t('gestlab.general.labels.signature.delete_irreversible') }}
          </p>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <CheckCircleIcon class="h-5 w-5 text-blue-900 flex-shrink-0" />
          <p class="text-gray-700">
            {{ $t('gestlab.general.labels.signature.signature_can_be_created_again') }}
          </p>
        </div>
      </div>
    </div>
  </confirm-dialog>
</template>

<style scoped>
/* Custom cursor for signature pad */
.cursor-crosshair {
  cursor: crosshair;
}

/* Smooth transitions */
* {
  transition: all 0.2s ease;
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

/* Signature pad styling */
.v-perfect-signature {
  background: linear-gradient(to bottom, #f9fafb, #ffffff);
}

.v-perfect-signature:hover {
  background: linear-gradient(to bottom, #f3f4f6, #ffffff);
}

/* Focus styles */
:focus {
  outline: 2px solid #1e3a8a;
  outline-offset: 2px;
}

:focus:not(:focus-visible) {
  outline: none;
}
</style>