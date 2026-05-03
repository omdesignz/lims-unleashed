<script setup>
import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { modal, close, reset, setModal } from '@/Composables/useModal.js'
import axios from 'axios'

const page = usePage()
let interceptorId = null
const resolvedModalComponent = computed(() => modal.value?.resolvedComponent?.default ?? null)

function addBaseUrlToRequest(config) {
    if (page.props._modal) {
        config.headers['X-Modal-Base-Url'] = page.props._modal.baseUrl
    }

    return config
}

watch(
    () => page.props._modal,
    (modalPayload) => {
        if (modalPayload?.component) {
            if (interceptorId === null) {
                interceptorId = axios.interceptors.request.use(addBaseUrlToRequest)
            }

            setModal({ ...modalPayload })
        } else {
            if (interceptorId !== null) {
                axios.interceptors.request.eject(interceptorId)
                interceptorId = null
            }

            close()
        }
    },
    { immediate: true },
)
</script>

<template>
    <component
        :is="resolvedModalComponent"
        v-if="resolvedModalComponent"
        :show="modal?.show"
        v-bind="modal?.props"
        @close="close"
        @after-leave="reset"
    />
</template>
