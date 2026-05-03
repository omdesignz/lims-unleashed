<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <TransitionRoot as="template" :show="confirmingPassword">
            <Dialog as="div" class="fixed z-10 inset-0 overflow-y-auto"  @close="closeModal">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <DialogOverlay class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                </TransitionChild>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <ExclamationCircleIcon class="h-6 w-6 text-red-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">

                            {{ title }}

                        </DialogTitle>
                        <div class="mt-2">

                            <p class="text-sm text-gray-500">
                                {{ content }}
                            </p>

                            <div class="mt-4">
                                <input ref="passwordInput"
                                       v-model="form.password"
                                       @keyup.enter="confirmPassword" id="password" name="password" type="password" autocomplete="current-password" :class="[form.error ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                                <p v-if="form.error" class="mt-2 text-sm text-red-600" id="email-error">{{ form.error }}</p>
                            </div>

                        </div>
                    </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:ml-10 sm:pl-4 sm:flex">

                            <button type="button" @click="closeModal" class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                            <button type="button" @click="confirmPassword" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ button }}
                            </button>

                    </div>
                </div>
                </TransitionChild>
            </div>
            </Dialog>
        </TransitionRoot>

    </span>
</template>

<script setup>
import { Dialog, DialogOverlay, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import {ref, reactive, nextTick} from "vue";

const props = defineProps({
    title: {
        default: 'Confirmar Senha',
    },
    content: {
        default: 'Para sua segurança, confirme sua senha para continuar.',
    },
    button: {
        default: 'Confirmar',
    }
});

const emit = defineEmits(['confirmed']);

const confirmingPassword = ref(false);

const passwordInput = ref(null);

const form = reactive({
    password: '',
    error: '',
    processing: false
});

const startConfirmingPassword = () => {
    axios.get(route('password.confirmation')).then(response => {
        if (response.data.confirmed) {
            emit('confirmed');
        } else {
            confirmingPassword.value = true;
            setTimeout(() => passwordInput.value.focus(), 250);
        }
    });
};

const confirmPassword = () => {
    form.processing = true;
    axios.post(route('password.confirm'), {
        password: form.password,
    }).then(() => {
        form.processing = false;
        closeModal();
        nextTick().then(() => emit('confirmed'));
    }).catch(error => {
        form.processing = false;
        form.error = error.response.data.errors.password[0];
        passwordInput.value.focus();
    });
};

const closeModal = () => {
    confirmingPassword.value = false;
    form.password = '';
    form.error = '';
};
</script>

<style scoped>

</style>
