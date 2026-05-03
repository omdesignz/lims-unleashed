<script setup>
import { useForm } from '@inertiajs/vue3'
import { watch } from 'vue'
import { trans } from 'laravel-vue-i18n';
import combobox from "@/Components/combobox.vue";
// import { modal, close, reset, setModal } from '@/Composables/useModal'
import Modal from "@/Components/modal.vue";
import DocumentValidationSignature from '@/Components/document-validation-signature.vue';


const props = defineProps({
    record: Object,
    action: String,
    title: String,
    url: String
})

const emit = defineEmits(['close'])

const form = useForm({
    verified_on_behalf_of: false,
    signature: null,
    id: props.record.data.id,
})

watch(
    () => props.record,
    (record) => {
        if (record) {
            form.approved_on_behalf_of = record.data.approved_on_behalf_of
            form.signature = form.signature
            form.id = record.data.id
        }
    },
    { immediate: true },
)

function closeModal() {
    emit('close')
}

function updateRecord(e) {
    form.post(props.url, {certificate: form.id, signature: form.signature, approve_on_behalf_of: form.approve_on_behalf_of}), {
        onSuccess: closeModal,
    }
}

function loadUsers(query, setOptions) {
    fetch('/users/getUser?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}
</script>

<template>
    <Modal
        title="Editing Record"
        maxWidth="2xl"
        @close="closeModal"
    >
        <form
            class="space-y-6"
            @submit.prevent="updateRecord"
        >
            <h1>{{ props.title }}</h1>

                <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0 dark:sm:divide-gray-800">
                    <DocumentValidationSignature title="Verificação do Boletim de Resultados" @save="form.signature = $event" />
                </div>
                

                <div class="border-b border-gray-200 pb-5 flex justify-end space-x-3" v-if="props.action === 'verify'">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-400">Assinar por Outrem?</h3>
                    <p class="max-w-4xl text-sm text-gray-500 flex items-center">
                        <button type="button" @click="form.verified_on_behalf_of = !form.verified_on_behalf_of" class="bg-orange relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-orange focus:ring-offset-2" role="switch" :aria-checked="form.verified_on_behalf_of" :class="{ 'bg-orange': form.verified_on_behalf_of, 'bg-gray-200': !form.verified_on_behalf_of }">
                            <span class="sr-only">Edit Setting</span>
                            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': form.verified_on_behalf_of, 'translate-x-0': !form.verified_on_behalf_of }">
                                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!form.verified_on_behalf_of" :class="{ 'opacity-0 duration-100 ease-out': form.verified_on_behalf_of, 'opacity-100 duration-200 ease-in': !form.verified_on_behalf_of }">
                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                </span>
                                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="form.verified_on_behalf_of" :class="{ 'opacity-100 duration-200 ease-in': form.verified_on_behalf_of, 'opacity-0 duration-100 ease-out': !form.verified_on_behalf_of }">
                                <svg class="h-3 w-3 text-orange" fill="currentColor" viewBox="0 0 12 12">
                                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                </svg>
                                </span>
                            </span>
                        </button>
                    </p>
                </div>

                <div class="border-b border-gray-200 pb-5 flex justify-end space-x-3" v-else>
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-400">Assinar por Outrem?</h3>
                    <p class="max-w-4xl text-sm text-gray-500 flex items-center">
                        <button type="button" @click="form.approve_on_behalf_of = !form.approve_on_behalf_of" class="bg-orange relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden focus:ring-2 focus:ring-orange focus:ring-offset-2" role="switch" :aria-checked="form.approve_on_behalf_of" :class="{ 'bg-orange': form.approve_on_behalf_of, 'bg-gray-200': !form.approve_on_behalf_of }">
                            <span class="sr-only">Edit Setting</span>
                            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': form.approve_on_behalf_of, 'translate-x-0': !form.approve_on_behalf_of }">
                                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!form.approve_on_behalf_of" :class="{ 'opacity-0 duration-100 ease-out': form.approve_on_behalf_of, 'opacity-100 duration-200 ease-in': !form.approve_on_behalf_of }">
                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                </span>
                                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="form.approve_on_behalf_of" :class="{ 'opacity-100 duration-200 ease-in': form.approve_on_behalf_of, 'opacity-0 duration-100 ease-out': !form.approve_on_behalf_of }">
                                <svg class="h-3 w-3 text-orange" fill="currentColor" viewBox="0 0 12 12">
                                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                </svg>
                                </span>
                            </span>
                        </button>
                    </p>
                </div>

            <div class="flex justify-end space-x-3">
                <div class="flex justify-end space-x-3">
                    <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-400 dark:ring-gray-400 dark:hover:text-white" @click="closeModal">{{ $t('gestlab.general.buttons.cancel') }}</button>
                    <button v-if="form.isDirty" :disabled="form.processing || !form.signature" type="submit" class="inline-flex justify-center rounded-md bg-orange px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-orange-600 focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800">{{ props.action === 'verify' ? 'Verificar' : 'Validar' }}</button>
                </div>
            </div>
        </form>
    </Modal>
</template>