<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { VPerfectSignature } from 'v-perfect-signature';
import { XMarkIcon, ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  currentSignature: String,
  title: {
    type: String,
    default: '',
  },

});

const emit = defineEmits(['save', 'delete']);

const signaturePad = ref();

const strokeOptions = {
    size: 16,
    thinning: 0.75,
    smoothing: 0.5,
    streamline: 0.5,
  };

function toDataURL() {
    const dataURL = signaturePad.value?.toDataURL();
    console.log(dataURL);
}

function clear() {
    signaturePad.value?.clear();
}

function download() {
    if (signaturePad.value?.isEmpty()) {
    alert('Empty signature pad!')
    return
}

  const link = document.createElement('a')
  link.download = 'signature.png'
  link.href = signaturePad.value?.toDataURL()
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

</script>
<template>
    <div class="mx-auto max-w-none">
        <div class="overflow-hidden bg-white sm:rounded-lg sm:shadow-sm">
    <div class="border-b border-gray-200 bg-white px-4 py-5 sm:px-6">
        <div class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap">
        <div class="ml-4 mt-4">
            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-400">{{ $t('gestlab.general.labels.signature.title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $t('gestlab.general.labels.signature.description') }}</p>
        </div>
        <div class="ml-4 mt-4 shrink-0">
            <span class="isolate inline-flex rounded-md shadow-xs">
                <button :disabled="props?.currentSignature?.length"  @click="clear" type="button" class="relative inline-flex items-center rounded-l-md bg-white px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 text-sm">
                <span class="sr-only">{{ $t('gestlab.general.buttons.clear') }}</span>
                <XMarkIcon class="h-5 w-5" aria-hidden="true" /> {{ $t('gestlab.general.buttons.clear') }}
                </button>
                <button v-if="!props?.currentSignature?.length"  @click="download" type="button" class="relative -ml-px inline-flex items-center bg-white px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 text-sm">
                <span class="sr-only">{{ $t('gestlab.general.buttons.download') }}</span>
                <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" /> {{ $t('gestlab.general.buttons.download') }}
                </button>
                
                <button :disabled="props?.currentSignature?.length || signaturePad?.isEmpty()"  @click="$emit('save', signaturePad.toDataURL())" type="button" class="relative -ml-px inline-flex items-center rounded-r-md bg-white px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-10 text-sm">
                <span class="sr-only">{{ $t('gestlab.general.buttons.submit') }}</span>
                Confirmar Assinatura
                </button>
            </span>
        </div>
        </div>
    </div>

    <img v-if="props?.currentSignature?.length" :src="props?.currentSignature" alt="">
    <VPerfectSignature v-else :stroke-options="strokeOptions" ref="signaturePad" />

</div>
</div>

</template>