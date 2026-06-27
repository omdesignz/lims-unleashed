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
            wrapper: 'border-[rgb(var(--primary-200-rgb)/0.8)] bg-[var(--ds-panel-raised)] text-[var(--ds-text)] ring-1 ring-[rgb(var(--primary-100-rgb)/0.7)] dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:ring-[rgb(var(--primary-300-rgb)/0.1)]',
            iconClass: 'text-emerald-500 dark:text-emerald-400',
            titleClass: 'text-[var(--ds-text)]',
            messageClass: 'text-[var(--ds-text-muted)]',
            actionClass: 'text-[var(--ds-text-soft)] hover:text-[var(--ds-text)]',
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
            wrapper: 'border-[rgb(var(--accent-300-rgb)/0.55)] bg-[var(--ds-panel-raised)] text-[var(--ds-text)] ring-1 ring-[rgb(var(--accent-200-rgb)/0.5)] dark:border-[rgb(var(--accent-300-rgb)/0.2)] dark:ring-[rgb(var(--accent-300-rgb)/0.1)]',
            iconClass: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
            titleClass: 'text-[var(--ds-text)]',
            messageClass: 'text-[var(--ds-text-muted)]',
            actionClass: 'text-[var(--ds-text-soft)] hover:text-[var(--ds-text)]',
            icon: InformationCircleIcon,
        },
    };

    return variants[props.variant] || variants.success;
});
</script>
<template>
    <div :class="variantConfig.wrapper" class="pointer-events-auto w-full max-w-md overflow-hidden rounded-[1.6rem] border shadow-[var(--ds-shadow-card)] backdrop-blur">
        <div class="p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <component :is="variantConfig.icon" :class="variantConfig.iconClass" class="h-6 w-6" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p v-if="props.title" :class="variantConfig.titleClass" class="text-sm font-bold">{{ props.title }}</p>
                    <p :class="[props.title ? 'mt-1' : '', variantConfig.messageClass]" class="text-sm font-medium leading-5">{{ props.message }}</p>
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
