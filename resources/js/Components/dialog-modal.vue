<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-5">
            <div class="ds-heading text-lg">
                <slot name="title" />
            </div>

            <div class="ds-copy mt-4 text-sm">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-4 text-right">
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
