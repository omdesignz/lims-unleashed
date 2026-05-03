<script setup>
import { computed, onMounted } from "vue";
import { CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon } from "@heroicons/vue/24/outline";
import { XMarkIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
    message: String,
    title: {
        type: String,
        default: ''
    },
    duration: {
        type: Number,
        default: 2000
    },
    variant: {
        type: String,
        default: 'success'
    },
});

onMounted(() => {
    setTimeout(() => emit("remove"), props.duration);
});

const emit = defineEmits(["remove"]);

const variantConfig = computed(() => {
    const variants = {
        success: {
            wrapper: 'border-emerald-200/80 bg-white/95 text-slate-900 shadow-emerald-100 ring-1 ring-emerald-100/80 dark:border-emerald-500/20 dark:bg-slate-900/95 dark:text-white dark:ring-emerald-500/10',
            iconClass: 'text-emerald-500 dark:text-emerald-400',
            titleClass: 'text-slate-900 dark:text-white',
            messageClass: 'text-slate-600 dark:text-slate-300',
            actionClass: 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-200',
            icon: CheckCircleIcon,
        },
        error: {
            wrapper: 'border-rose-200/80 bg-rose-50/95 text-rose-950 shadow-rose-100 ring-1 ring-rose-100/80 dark:border-rose-500/20 dark:bg-rose-950/60 dark:text-rose-100 dark:ring-rose-500/10',
            iconClass: 'text-rose-500 dark:text-rose-300',
            titleClass: 'text-rose-900 dark:text-rose-100',
            messageClass: 'text-rose-700 dark:text-rose-200',
            actionClass: 'text-rose-400 hover:text-rose-700 dark:text-rose-300 dark:hover:text-rose-100',
            icon: XCircleIcon,
        },
        warning: {
            wrapper: 'border-amber-200/80 bg-amber-50/95 text-amber-950 shadow-amber-100 ring-1 ring-amber-100/80 dark:border-amber-500/20 dark:bg-amber-950/55 dark:text-amber-100 dark:ring-amber-500/10',
            iconClass: 'text-amber-500 dark:text-amber-300',
            titleClass: 'text-amber-900 dark:text-amber-100',
            messageClass: 'text-amber-700 dark:text-amber-200',
            actionClass: 'text-amber-400 hover:text-amber-700 dark:text-amber-300 dark:hover:text-amber-100',
            icon: ExclamationTriangleIcon,
        },
        info: {
            wrapper: 'border-blue-200/80 bg-blue-50/95 text-blue-950 shadow-blue-100 ring-1 ring-blue-100/80 dark:border-blue-500/20 dark:bg-slate-900/95 dark:text-blue-100 dark:ring-blue-500/10',
            iconClass: 'text-blue-500 dark:text-blue-300',
            titleClass: 'text-blue-900 dark:text-blue-100',
            messageClass: 'text-blue-700 dark:text-slate-300',
            actionClass: 'text-blue-400 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-100',
            icon: InformationCircleIcon,
        },
    };

    return variants[props.variant] || variants.success;
});
</script>
<template>
    <div :class="variantConfig.wrapper" class="pointer-events-auto w-full max-w-md overflow-hidden rounded-[1.6rem] border shadow-[0_20px_60px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <component :is="variantConfig.icon" :class="variantConfig.iconClass" class="h-6 w-6" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p v-if="props.title" :class="variantConfig.titleClass" class="text-sm font-semibold">{{ props.title }}</p>
                    <p :class="[props.title ? 'mt-1' : '', variantConfig.messageClass]" class="text-sm leading-5">{{ props.message }}</p>
                </div>
                <div class="flex flex-shrink-0">
                    <button @click="emit('remove')" type="button" :class="variantConfig.actionClass" class="inline-flex rounded-lg p-1.5 transition-colors focus:outline-none">
                        <span class="sr-only">Fechar notificação</span>
                        <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>
