<template>
    <ActionSection :class="commercialDocumentThemeClasses">
        <template #title>
            Sessões do Navegador
        </template>

        <template #description>
            Gerencie e saia de suas sessões ativas em outros navegadores e dispositivos. 
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Se necessário, você pode sair de todas as outras sessões do navegador em todos os seus dispositivos. Algumas de suas sessões recentes estão listadas abaixo; no entanto, esta lista pode não ser exaustiva. Se você acha que sua conta foi comprometida, você também deve atualizar sua senha.
            </div>

            <!-- Other Browser Sessions -->
            <div v-if="sessions?.length > 0" class="mt-5 space-y-6">
                <div v-for="(session, i) in sessions" :key="i" class="flex items-center">
                    <div>
                        <svg v-if="session.agent.is_desktop" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                        </svg>

                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>

                    </div>

                    <div class="ml-3">
                        <div class="text-sm text-gray-600">
                            {{ session.agent.platform ? session.agent.platform : 'Desconhecido' }} - {{ session.agent.browser ? session.agent.browser : 'Desconhecido' }}
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">
                                {{ session.ip_address }},

                                <span v-if="session.is_current_device" class="text-green-500 font-semibold">Este dispositivo</span>
                                <span v-else>Ativo pela última vez {{ session.last_active }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center mt-5">
                <PrimaryButton @click="confirmLogout">
                    Sair de Outras Sessões do Navegador
                </PrimaryButton>

                <ActionMessage :on="form.recentlySuccessful" class="ml-3">
                    Actualizado
                </ActionMessage>
            </div>

            <!-- Log Out Other Devices Confirmation Modal -->
            <DialogModal :show="confirmingLogout" @close="closeModal">
                <template #title>
                    Sair de outras sessões do navegador
                </template>

                <template #content>
                    Digite sua senha para confirmar que deseja sair das outras sessões do navegador em todos os seus dispositivos.

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Senha"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal">
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="logoutOtherBrowserSessions"
                    >
                    Sair de outras sessões do navegador
                    </PrimaryButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from "../../../Components/action-section.vue";
import ActionMessage from "../../../Components/action-message.vue";
import InputError from "../../../Components/input-error.vue";
import PrimaryButton from "../../../Components/primary-button.vue";
import SecondaryButton from "../../../Components/secondary-button.vue";
import TextInput from "../../../Components/text-input.vue";
import DialogModal from "../../../Components/dialog-modal.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineProps({
    sessions: Array,
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmLogout = () => {
    confirmingLogout.value = true;
    setTimeout(() => passwordInput.value.focus(), 250);
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('other-browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    }); 
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.reset();
};

</script>

<style scoped>

</style>
