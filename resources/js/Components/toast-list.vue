<script setup>
import ToastListItem from "@/Components/toast-list-item.vue";
import {onUnmounted, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import toast from "@/Stores/toast";

const page = usePage();
const props = defineProps({
    duration: {
        type: Number,
        default: 5000,
    },
});

let removeFinishEventListener = router.on("finish", () => {
    if (page.props.toast) {
        toast.add({
            message: page.props.toast.message,
            title: page.props.toast?.title,
        });
    }
});

onUnmounted(() => removeFinishEventListener());

function remove(index) {
    toast.remove(index);
}
</script>
<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex items-start justify-end px-4 py-4 sm:px-6 sm:py-6">
        <div class="flex w-full max-w-md flex-col items-stretch gap-3 sm:items-end">
            <!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
            <TransitionGroup
                enter-from-class="translate-x-full opacity-0"
                enter-active-class="duration-500"
                leave-active-class="duration-500"
                leave-to-class="translate-x-full opacity-0"
            >
                <ToastListItem
                    v-for="(item, index) in toast.items"
                    :key="item.key"
                    :message="item.message"
                    :title="item.title"
                    :variant="item.variant"
                    :duration="item.duration || props.duration"
                    @remove="remove(index)"
                />
            </TransitionGroup>
        </div>
    </div>
</template>
