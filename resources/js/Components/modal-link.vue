<script setup>
import { open } from '@/Composables/useModal'

defineProps({
    href: {
        type: String,
        required: true,
    },
})
</script>

<template>
    <button
        type="button"
        @click="open(href)"
    >
        <slot />
    </button>
</template>