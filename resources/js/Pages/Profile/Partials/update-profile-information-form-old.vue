<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Informação do Perfil
        </template>
        <template #description>
            Atualize as informações de perfil e o endereço de e-mail da sua conta.
        </template>
        <template #form>
            <!-- Profile Photo -->
            <div class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >
                <InputLabel for="photo" value="Fotografia" />
                <!-- Current Profile Photo -->
                <div v-show="! photoPreview" class="mt-2">
                    <img :src="props?.user?.user?.profile_photo_url" :alt="props?.user?.user?.name" class="rounded-full h-20 w-20 object-cover" />
                </div>
                <!-- New Profile Photo Preview -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>
                <SecondaryButton class="mt-2 mr-2" type="button" @click.prevent="selectNewPhoto">
                    Selecione Uma Nova Foto
                </SecondaryButton>
                <SecondaryButton
                    v-if="props?.user?.user?.profile_photo_path"
                    type="button"
                    class="mt-2"
                    @click.prevent="deletePhoto"
                >
                Remover Foto
                </SecondaryButton>
                <InputError :message="form.errors.photo" class="mt-2" />
            </div>
            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nome" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                />
                <InputError :message="form.errors.email" class="mt-2" />
                <div v-if="$page.props.hasEmailVerification && user?.email_verified_at === null">
                    <p class="text-sm mt-2">
                        Seu endereço de e-mail não foi verificado.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-gray-600 hover:text-gray-900"
                            @click.prevent="sendEmailVerification"
                        >
                        Clique aqui para reenviar o e-mail de verificação.
                        </Link>
                    </p>
                    <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-green-600">
                        Um novo link de verificação foi enviado para o seu endereço de e-mail.
                    </div>
                </div>
            </div>
            <!-- Signature -->
            <div class="col-span-full sm:col-span-full">
                <SignaturePad :current-signature="props.user?.user?.signature_url" @save="saveSignature" @delete="deleteSignature" />
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
import { ref } from 'vue';
// import { router } from '@inertiajs/vue3'
import { Link, useForm, router } from '@inertiajs/vue3';
import ActionMessage from '@/Components/action-message.vue';
import InputError from '@/Components/input-error.vue'
import InputLabel from '@/Components/input-label.vue'
import TextInput from '@/Components/text-input.vue'
import PrimaryButton from '@/Components/primary-button.vue'
import SecondaryButton from '@/Components/secondary-button.vue'
import FormSection from '@/Components/form-section.vue'
import SignaturePad from '@/Components/signature-pad.vue';


const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props?.user?.user?.name,
    id: props?.user?.user?.id,
    email: props?.user?.user?.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null); 

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }
    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];
    if (! photo) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

function saveSignature (e) {
  useForm({
    signature: e
  }).post(route('users.setsignature',{user: props?.user?.id}), {
          preserveScroll: true,
          onSuccess: () => {
            
          },
      });
}

function deleteSignature (e) {
  useForm({}).get(route('users.unsetsignature',{user: props?.user?.id}), {
          preserveScroll: true,
          onSuccess: () => {
            
          },
      });
}

</script>

<style scoped>

</style>
