<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            Actualizar Senha
        </template>

        <template #description>
            Certifique-se de que sua conta está usando uma senha longa e aleatória para permanecer segura.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="current_password" value="Senha Actual" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password" value="Nova Senha" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password_confirmation" value="Confirmar Senha" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                Actualizado
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Actualizar
            </PrimaryButton>
        </template>
    </FormSection>
</template>

<script setup>
import {ref} from 'vue'
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '../../../Components/action-message.vue'
import InputError from '../../../Components/input-error.vue'
import InputLabel from '../../../Components/input-label.vue'
import TextInput from '../../../Components/text-input.vue'
import PrimaryButton from '../../../Components/primary-button.vue'
import FormSection from '../../../Components/form-section.vue'

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};

</script>

<style scoped>

</style>
