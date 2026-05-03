<template>
  <FormSection @submitted="updateProfileInformation">
    <template #title>
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Informação do Perfil
      </div>
    </template>

    <template #description>
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="text-gray-600">
            Atualize as informações de perfil e o endereço de e-mail da sua conta.
          </p>
          <p class="text-sm text-gray-500 mt-1">
            Mantenha as suas informações actualizadas para uma melhor experiência.
          </p>
        </div>
      </div>
    </template>

    <template #form>
      <div class="space-y-8">
        <!-- Profile Photo Section -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium text-gray-900">Fotografia do Perfil</h3>
              <p class="text-sm text-gray-500">Recomendado: 200x200px, JPG ou PNG</p>
            </div>
            <div v-if="props?.user?.user?.profile_photo_path" class="flex items-center gap-2">
              <button
                @click.prevent="deletePhoto"
                type="button"
                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors duration-200"
              >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Remover Foto
              </button>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
            <!-- Current/Preview Photo -->
            <div class="flex-shrink-0">
              <div v-show="!photoPreview" class="relative group">
                <img 
                  :src="props?.user?.user?.profile_photo_url" 
                  :alt="props?.user?.user?.name" 
                  class="h-24 w-24 rounded-full object-cover ring-4 ring-white shadow-sm"
                />
                <div class="absolute inset-0 rounded-full bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                  <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
              </div>
              
              <div v-show="photoPreview" class="relative">
                <div 
                  class="h-24 w-24 rounded-full bg-cover bg-center bg-no-repeat ring-4 ring-white shadow-sm"
                  :style="'background-image: url(\'' + photoPreview + '\');'"
                />
                <div class="absolute -top-1 -right-1 h-6 w-6 rounded-full bg-blue-900 text-white flex items-center justify-center text-xs">
                  Novo
                </div>
              </div>
            </div>

            <!-- Upload Controls -->
            <div class="space-y-3">
              <input
                ref="photoInput"
                type="file"
                class="hidden"
                accept="image/*"
                @change="updatePhotoPreview"
              >
              
              <button
                @click.prevent="selectNewPhoto"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Seleccionar Nova Foto
              </button>
              
              <div class="text-xs text-gray-500">
                Clique no botão acima para seleccionar uma nova fotografia do seu dispositivo.
              </div>
            </div>
          </div>
          
          <InputError :message="form.errors.photo" class="text-xs" />
        </div>

        <!-- Personal Information -->
        <div class="space-y-6">
          <h3 class="text-sm font-medium text-gray-900">Informação Pessoal</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <InputLabel for="name" value="Nome Completo" class="text-sm font-medium text-gray-700" />
                <span class="text-xs text-gray-500">Obrigatório</span>
              </div>
              <div class="relative">
                <TextInput
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                  :class="form.errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
                  autocomplete="name"
                  placeholder="Introduza o seu nome completo"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </div>
              <InputError :message="form.errors.name" class="text-xs" />
            </div>

            <!-- Email -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <InputLabel for="email" value="Email" class="text-sm font-medium text-gray-700" />
                <div v-if="props?.user?.user?.email_verified_at" class="flex items-center gap-1 text-xs text-green-600">
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Verificado
                </div>
                <div v-else class="flex items-center gap-1 text-xs text-yellow-600">
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  Não verificado
                </div>
              </div>
              <div class="relative">
                <TextInput
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
                  :class="form.errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
                  placeholder="seu.email@exemplo.com"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              
              <!-- Email Verification -->
              <div v-if="$page.props.hasEmailVerification && props?.user?.user?.email_verified_at === null" class="mt-3">
                <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                  <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                      <p class="text-xs font-medium text-yellow-800">
                        O seu endereço de email não foi verificado.
                      </p>
                      <button
                        @click.prevent="sendEmailVerification"
                        class="mt-1 inline-flex items-center gap-1 text-xs text-yellow-700 hover:text-yellow-900 transition-colors duration-200"
                      >
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Clique aqui para reenviar o email de verificação.
                      </button>
                    </div>
                  </div>
                </div>
                
                <div v-show="verificationLinkSent" class="mt-2 bg-green-50 rounded-lg p-3 border border-green-200">
                  <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-xs text-green-800">
                      Um novo link de verificação foi enviado para o seu endereço de email.
                    </p>
                  </div>
                </div>
              </div>
              
              <InputError :message="form.errors.email" class="text-xs" />
            </div>
          </div>
        </div>

        <!-- Signature Section -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-medium text-gray-900">Assinatura Digital</h3>
              <p class="text-sm text-gray-500">Utilizada para validação de documentos</p>
            </div>
            <div v-if="props.user?.user?.signature_url" class="flex items-center gap-2">
              <button
                @click="deleteSignature"
                type="button"
                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors duration-200"
              >
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Remover Assinatura
              </button>
            </div>
          </div>
          
          <div class="border border-gray-200 rounded-lg p-4">
            <SignaturePad 
              :current-signature="props.user?.user?.signature_url" 
              @save="saveSignature" 
              @delete="deleteSignature" 
            />
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-3">
          <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-sm font-medium text-green-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Perfil actualizado com sucesso
          </div>
          
          <div v-if="form.hasErrors" class="flex items-center gap-2 text-sm font-medium text-red-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Por favor, corrija os erros
          </div>
        </div>
        
        <button 
          @click.prevent="updateProfileInformation"
          :disabled="form.processing"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
            form.processing 
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
          ]"
        >
          <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ form.processing ? 'A actualizar...' : 'Actualizar Perfil' }}
        </button>
      </div>
    </template>
  </FormSection>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import ActionMessage from '@/Components/action-message.vue';
import InputError from '@/Components/input-error.vue'
import InputLabel from '@/Components/input-label.vue'
import TextInput from '@/Components/text-input.vue'
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
/* Smooth transitions */
img, button, input {
  transition: all 0.2s ease-in-out;
}

/* Profile photo hover effect */
.group:hover img {
  transform: scale(1.05);
}
</style>