<template>
    <ActionSection>
        <template #title>
            Autenticação de dois fatores
        </template>
        <template #description>
            Adicione segurança adicional à sua conta usando autenticação de dois fatores.
        </template>
        <template #content>
            <h3 v-if="twoFactorEnabled && ! confirming" class="text-lg font-medium text-gray-900">
                Você habilitou a autenticação de dois fatores.
            </h3>
            <h3 v-else-if="twoFactorEnabled && confirming" class="text-lg font-medium text-gray-900">
                Termine de habilitar a autenticação de dois fatores.
            </h3>
            <h3 v-else class="text-lg font-medium text-gray-900">
                Você não habilitou a autenticação de dois fatores.
            </h3>
            <div class="mt-3 max-w-xl text-sm text-gray-600">
                <p>
                    Quando a autenticação de dois fatores estiver habilitada, você será solicitado a fornecer um token aleatório seguro durante a autenticação. Você pode recuperar esse token do aplicativo Google Authenticator do seu telefone.
                </p>
            </div>
            <div v-if="twoFactorEnabled">
                <div v-if="qrCode">
                    <div class="mt-4 max-w-xl text-sm text-gray-600">
                        <p v-if="confirming" class="font-semibold">
                            Para concluir a activação da autenticação de dois fatores, digitalize o seguinte código QR usando o aplicativo autenticador do seu telefone ou digite a chave de configuração e forneça o código OTP gerado.
                        </p>
                        <p v-else>
                            A autenticação de dois fatores agora está habilitada. Leia o seguinte código QR usando o aplicativo autenticador do seu telefone.
                        </p>
                    </div>
                    <div class="mt-4" v-html="qrCode" />
                    <div class="mt-4 max-w-xl text-sm text-gray-600" v-if="setupKey">
                        <p class="font-semibold">
                            Chave de configuração: <span v-html="setupKey"></span>
                        </p>
                    </div>
                    <div v-if="confirming" class="mt-4">
                        <InputLabel for="code" value="Code" />
                        <TextInput
                            id="code"
                            v-model="confirmationForm.code"
                            type="text"
                            name="code"
                            class="block mt-1 w-1/2"
                            inputmode="numeric"
                            autofocus
                            autocomplete="one-time-code"
                            @keyup.enter="confirmTwoFactorAuthentication"
                        />
                        <InputError :message="confirmationForm.errors.code" class="mt-2" />
                    </div>
                </div>
                <div v-if="recoveryCodes.length > 0 && ! confirming">
                    <div class="mt-4 max-w-xl text-sm text-gray-600">
                        <p class="font-semibold">
                            Armazene esses códigos de recuperação em um gerenciador de senhas seguro. Eles podem ser usados para recuperar o acesso à sua conta se o seu dispositivo de autenticação de dois fatores for perdido.
                        </p>
                    </div>
                    <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                        <div v-for="code in recoveryCodes" :key="code">
                            {{ code }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <div v-if="! twoFactorEnabled">
                    <ConfirmsPassword @confirmed="enableTwoFactorAuthentication">
                        <PrimaryButton type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling">
                            Activar
                        </PrimaryButton>
                    </ConfirmsPassword>
                </div>
                <div v-else>
                    <ConfirmsPassword @confirmed="confirmTwoFactorAuthentication">
                        <PrimaryButton
                            v-if="confirming"
                            type="button"
                            class="mr-3"
                            :class="{ 'opacity-25': enabling }"
                            :disabled="enabling"
                        >
                            Confirmar
                        </PrimaryButton>
                    </ConfirmsPassword>
                    <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
                        <SecondaryButton
                            v-if="recoveryCodes.length > 0 && ! confirming"
                            class="mr-3"
                        >
                        Regenerar códigos de recuperação
                        </SecondaryButton>
                    </ConfirmsPassword>
                    <ConfirmsPassword @confirmed="showRecoveryCodes">
                        <SecondaryButton
                            v-if="recoveryCodes.length === 0 && ! confirming"
                            class="mr-3"
                        >
                        Mostrar códigos de recuperação
                        </SecondaryButton>
                    </ConfirmsPassword>
                    <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                        <SecondaryButton
                            v-if="confirming"
                            :class="{ 'opacity-25': disabling }"
                            :disabled="disabling"
                        >
                            Cancelar
                        </SecondaryButton>
                    </ConfirmsPassword>
                    <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                        <DangerButton
                            v-if="! confirming"
                            :class="{ 'opacity-25': disabling }"
                            :disabled="disabling"
                        >
                            Desactivar
                        </DangerButton>
                    </ConfirmsPassword>
                </div>
            </div>
        </template>
    </ActionSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import ActionSection from "../../../Components/action-section.vue";
import ConfirmsPassword from "../../../Components/confirms-password.vue";
import DangerButton from "../../../Components/danger-button.vue";
import InputError from "../../../Components/input-error.vue";
import InputLabel from "../../../Components/input-label.vue";
import PrimaryButton from "../../../Components/primary-button.vue";
import SecondaryButton from "../../../Components/secondary-button.vue";
import TextInput from "../../../Components/text-input.vue";

const props = defineProps({
    requiresConfirmation: Boolean,
});

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => ! enabling.value && usePage().props?.auth?.user?.two_factor_secret,
);

watch(twoFactorEnabled, () => {
    if (! twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;
    router.post('/user/two-factor-authentication', {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const showQrCode = () => {
    return axios.get('/user/two-factor-qr-code').then(response => {
        qrCode.value = response.data.svg;
    });
};
const showSetupKey = () => {
    return axios.get('/user/two-factor-secret-key').then(response => {
        setupKey.value = response.data.secretKey;
    });
}

const showRecoveryCodes = () => {
    return axios.get('/user/two-factor-recovery-codes').then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post('/user/confirmed-two-factor-authentication', {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post('/user/two-factor-recovery-codes')
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;
    router.delete('/user/two-factor-authentication', {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
        },
    });
};

</script>

<style scoped>

</style>
