<template>
<!-- Page header -->
<div class="bg-white">
  <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
    <div class="py-6 md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <!-- Profile -->
        <div class="flex items-center">
          <div>
            <div class="flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-ft-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>

              <span>
                <h2 class="ml-3 text-2xl font-semibold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                Segurança
              </h2>
              </span>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-6 flex space-x-3 md:mt-0 md:ml-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-ft-orange" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
      </div>
    </div>



   <div class="space-y-6 lg:col-start-1 lg:col-span-2">
       <form @submit.prevent="UpdatePasswordForm.put('/user/password', {
            errorBag: 'updatePassword'
          })" class="space-y-6">
          <!-- Description list-->
          <section aria-labelledby="applicant-information-title">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="bg-white px-4 py-5 sm:px-6">
                  <div class="-ml-4 -mt-4 flex justify-between items-center flex-wrap sm:flex-nowrap">
                    <div class="ml-4 mt-4">
                      <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Actualizar Senha
                      </h3>
                      <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Certifique-se de que sua conta está usando uma senha longa e aleatória para permanecer segura.
                      </p>
                    </div>
                    <div class="ml-4 mt-4 flex-shrink-0">
                      <button type="submit" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                        Actualizar
                      </button>
                    </div>
                  </div>

                  <div class="space-y-1 m-4">
                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                    Senha Actual
                    </label>
                    <div class="mt-1">
                    <input v-model="UpdatePasswordForm.current_password" id="current_password" name="current_password" type="password" autocomplete="current_password" :class="[UpdatePasswordForm.errors.current_password ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    <p v-if="UpdatePasswordForm.errors.current_password" class="mt-2 text-sm text-red-600" id="email-error">{{ UpdatePasswordForm.errors.current_password }}</p>
                    </div>
                </div>

                <div class="space-y-1 m-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                    Nova Senha
                    </label>
                    <div class="mt-1">
                    <input v-model="UpdatePasswordForm.password" id="password" name="password" type="password" autocomplete="password" :class="[UpdatePasswordForm.errors.password ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    <p v-if="UpdatePasswordForm.errors.password" class="mt-2 text-sm text-red-600" id="email-error">{{ UpdatePasswordForm.errors.password }}</p>
                    </div>
                </div>

                <div class="space-y-1 m-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                    Confirmar Senha
                    </label>
                    <div class="mt-1">
                    <input v-model="UpdatePasswordForm.password_confirmation" id="password_confirmation" name="password_confirmation" type="password" autocomplete="password_confirmation" :class="[UpdatePasswordForm.errors.password_confirmation ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    <p v-if="UpdatePasswordForm.errors.password_confirmation" class="mt-2 text-sm text-red-600" id="email-error">{{ UpdatePasswordForm.errors.password_confirmation }}</p>
                    </div>
                </div>

                </div>
            </div>
          </section>
          </form>
   </div>

   <br>


   <div class="space-y-6 lg:col-start-1 lg:col-span-2">

          <!-- Description list-->
          <section aria-labelledby="applicant-information-title">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="bg-white px-4 py-5 sm:px-6">
                  <div class="-ml-4 -mt-4 flex justify-between items-center flex-wrap sm:flex-nowrap">
                    <div class="ml-4 mt-4">
                      <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Autenticação de dois fatores
                      </h3>
                      <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Adicione segurança adicional à sua conta usando autenticação de dois fatores.
                      </p>
                    </div>
                    <div class="ml-4 mt-4 flex-shrink-0">
                      <div v-if="! twoFactorEnabled">
                          <confirm-password-modal @confirmed="enableTwoFactorAuthentication">
                            <button type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                              Activar
                            </button>
                          </confirm-password-modal>
                      </div>


                      <span v-else class="relative z-0 inline-flex shadow-sm rounded-md">
                        <confirm-password-modal @confirmed="showQrCode">
                        <button
                                              :class="{ 'opacity-25': disabling }"
                                              :disabled="disabling" type="button" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-l-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                        Mostrar código QR
                      </button>
                        </confirm-password-modal>
                        <confirm-password-modal @confirmed="disableTwoFactorAuthentication">
                        <button
                                              :class="{ 'opacity-25': disabling }"
                                              :disabled="disabling" type="button" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-r-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                        Desactivar
                      </button>
                        </confirm-password-modal>

                      </span>

                    </div>
                  </div>


                  <div v-if="twoFactorEnabled">
                      <div v-if="qrCode">
                          <div class="mt-4 max-w-xl text-sm text-gray-600">
                              <p class="font-semibold">
                                  A autenticação de dois fatores agora está habilitada. Leia o seguinte código QR usando o aplicativo autenticador do seu telefone.
                              </p>
                          </div>

                          <div class="mt-4" v-html="qrCode">
                          </div>
                      </div>

                      <div v-if="recoveryCodes.length > 0">
                          <div class="mt-4 max-w-xl text-sm text-gray-600">
                              <p class="font-semibold">
                                  Armazene esses códigos de recuperação em um gerenciador de senhas seguro. Eles podem ser usados para recuperar o acesso à sua conta se o seu dispositivo de autenticação de dois fatores for perdido.
                              </p>
                          </div>

                          <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-ft-orange bg-opacity-20 rounded-lg">
                            <ul role="list" class="divide-y divide-ft-gray divide-opacity-20">

                              <li v-for="code in recoveryCodes" :key="code" class="py-1 flex">

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>

                              <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ code }}</p>
                              </div>
                              </li>


                            </ul>

                          </div>

                          <confirm-password-modal @confirmed="regenerateRecoveryCodes">
                          <button class="relative inline-flex items-center px-4 py-2 mt-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                            Regenerar códigos de recuperação
                          </button>
                          </confirm-password-modal>
                      </div>

                      <div>

                          <confirm-password-modal @confirmed="showRecoveryCodes">
                              <button class="relative inline-flex items-center px-4 py-2 mt-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange" v-if="recoveryCodes.length === 0">
                              Mostrar códigos de recuperação
                          </button>

                          </confirm-password-modal>

                      </div>
                  </div>








                </div>
            </div>
          </section>

   </div>



  </div>
</div>
</template>
<script setup>
import { computed, ref } from 'vue'
import confirmPasswordModal from '../../Shared/confirm-password-modal.vue'
import { useForm, usePage, router } from '@inertiajs/vue3';
import {
  CheckCircleIcon,
  OfficeBuildingIcon,
  PlusIcon
} from '@heroicons/vue/solid'
import { trans } from 'laravel-vue-i18n';

defineProps({
    two_factor_secret: Boolean,
});

let enabling = ref(false);
let disabling = ref(false);
let qrCode = ref(null);
let recoveryCodes = ref([]);

let enableTwoFactorAuthentication = () => {
  enabling = true;
  router.post('/user/two-factor-authentication', {}, {
      preserveScroll: true,
      onSuccess: () => Promise.all([
          showQrCode,
          showRecoveryCodes,
      ]),
      onFinish: () => (enabling = false),
  })
}

let showQrCode = () => {
  return axios.get('/user/two-factor-qr-code')
              .then(response => {
                  qrCode.value = response.data.svg
              })
}

let showRecoveryCodes = () => {
  return axios.get('/user/two-factor-recovery-codes')
              .then(response => {
                  recoveryCodes.value = response.data
              })
}

let regenerateRecoveryCodes = () => {
  axios.post('/user/two-factor-recovery-codes')
        .then(response => {
            showRecoveryCodes
        })
}

let disableTwoFactorAuthentication = () => {
  disabling = true
  router.delete('/user/two-factor-authentication', {
      preserveScroll: true,
      onSuccess: () => (disabling = false),
  })
}

let twoFactorEnabled = computed(() => {
    return enabling && usePage().props.value.auth.user.two_factor_secret.value;
});

let UpdatePasswordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

let TwoFactorForm = useForm({

})

</script>
