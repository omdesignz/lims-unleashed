<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-5">
            <div class="text-lg font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end gap-3 border-t border-[#ded3bf] bg-[#f7f1e7]/80 px-6 py-4 text-right dark:border-[#25443c] dark:bg-[#10231f]/70">
            <slot name="footer" />
        </div>
    </Modal>
</template>

<script setup>
import Modal from "./modal.vue";

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<style scoped>

</style>
