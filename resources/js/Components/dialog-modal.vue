<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-[22px] py-[22px]">
            <div class="pl-d3">
                <slot name="title" />
            </div>

            <div class="ds-copy mt-3 text-sm">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2 border-t border-[var(--pl-line)] px-[22px] py-3.5 text-right">
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
