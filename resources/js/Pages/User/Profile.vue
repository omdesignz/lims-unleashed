<template>
  <div class="bg-white">
          <div class="px-4 sm:px-6 lg:max-w-6xl lg:mx-auto lg:px-8">
            <div class="py-6 md:flex md:items-center md:justify-between">
              <div class="flex-1 min-w-0">
                <!-- Profile -->
                <div class="flex items-center">
                  <img class="hidden h-16 w-16 rounded-full sm:block" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2.6&w=256&h=256&q=80" alt="" />
                  <div>
                    <div class="flex items-center">
                      <img class="h-16 w-16 rounded-full sm:hidden" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2.6&w=256&h=256&q=80" alt="" />
                      <h1 class="ml-3 text-2xl font-semibold leading-7 text-gray-900 sm:leading-9 sm:truncate">
                        Good morning, {{ name }}
                      </h1>
                    </div>
                    <dl class="mt-6 flex flex-col sm:ml-3 sm:mt-1 sm:flex-row sm:flex-wrap">
                      <dt class="sr-only">Company</dt>
                      <dd class="flex items-center text-sm text-gray-500 font-medium capitalize sm:mr-6">
                        <BuildingOfficeIcon class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" aria-hidden="true" />
                        Duke street studio
                      </dd>
                      <dt class="sr-only">Account status</dt>
                      <dd class="mt-3 flex items-center text-sm text-gray-500 font-medium sm:mr-6 sm:mt-0 capitalize">
                        <CheckCircleIcon class="flex-shrink-0 mr-1.5 h-5 w-5 text-green-400" aria-hidden="true" />
                        Verified account
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
              <div class="mt-6 flex space-x-3 md:mt-0 md:ml-4">
                <button @click="editPersonalInformation = !editPersonalInformation" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500">
                  Edit Profile
                </button>
                <Link class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500" :href="route('logout')" method="post" as="button">
                  Logout
                </Link>
              </div>
            </div>
          </div>

          <form @submit.prevent="PersonalDetailsForm.put('/user/profile-information', {
            errorBag: 'updateProfileInformation'
          })" class="space-y-6">
          <div class="mt-8 max-w-3xl mx-auto grid grid-cols-1 gap-6 sm:px-6 lg:max-w-7xl lg:grid-flow-col-dense lg:grid-cols-3">
        <div class="space-y-6 lg:col-start-1 lg:col-span-2">
          <!-- Description list-->
          <section aria-labelledby="applicant-information-title">
            <div class="bg-white shadow sm:rounded-lg">
              <!-- <div class="px-4 py-5 sm:px-6">
                <h2 id="applicant-information-title" class="text-lg leading-6 font-medium text-gray-900">
                  Informações Gerais <span>Update</span>
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                  Dados relacionados à sua conta
                </p>
              </div> -->

                <div class="bg-white px-4 py-5 sm:px-6">
                  <div class="-ml-4 -mt-4 flex justify-between items-center flex-wrap sm:flex-nowrap">
                    <div class="ml-4 mt-4">
                      <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Informações Gerais
                      </h3>
                      <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Dados relacionados à sua conta
                      </p>
                    </div>
                    <div class="ml-4 mt-4 flex-shrink-0">
                      <button v-if="!dataChanged" @click="editPersonalInformation = !editPersonalInformation" type="button" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                        {{ editPersonalInformation ? 'Cancelar Edição' : 'Editar Informação' }}
                      </button>
                      <button v-if="dataChanged" @click="submit" type="button" class="relative inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                        Actualizar 
                      </button>
                    </div>
                  </div>
                </div>

              <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Usuário
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ username }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.username" id="username" name="username" type="text" autocomplete="username" :class="[PersonalDetailsForm.errors.username ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.username" class="mt-2 text-sm text-red-600" id="username-error">{{ PersonalDetailsForm.errors.username }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Nome
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ name }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.name" id="name" name="name" type="text" autocomplete="name" :class="[PersonalDetailsForm.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ PersonalDetailsForm.errors.name }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Gênero Sexual
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ gender }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <combobox :hasError="PersonalDetailsForm.errors.gender" v-model="PersonalDetailsForm.gender" :options="genderOptions"/>

                      <!-- <combobox v-model="PersonalDetailsForm.gender" id="gender" name="gender" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm rounded-md" :class="[PersonalDetailsForm.errors.gender ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']">
                        <option :value="gender.value" v-for="(gender, index) in genderOptions" :key="index">{{ gender.label }}</option>
                      </combobox > -->
                      <p v-if="PersonalDetailsForm.errors.gender" class="mt-2 text-sm text-red-600" id="gender-error">{{ PersonalDetailsForm.errors.gender }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Endereço de Email
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ email }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.email" id="email" name="email" type="text" autocomplete="email" :class="[PersonalDetailsForm.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ PersonalDetailsForm.errors.email }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      NIF (Contribuinte)
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ id_number }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.id_number" id="id_number" name="id_number" type="text" autocomplete="id_number" :class="[PersonalDetailsForm.errors.id_number ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.id_number" class="mt-2 text-sm text-red-600" id="id_number-error">{{ PersonalDetailsForm.errors.id_number }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Contacto
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ phone }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.phone" id="phone" name="phone" type="text" autocomplete="phone" :class="[PersonalDetailsForm.errors.phone ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.phone" class="mt-2 text-sm text-red-600" id="phone-error">{{ PersonalDetailsForm.errors.phone }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Contacto Alternativo
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ alternate_phone }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <input v-model="PersonalDetailsForm.alternate_phone" id="alternate_phone" name="alternate_phone" type="text" autocomplete="alternate_phone" :class="[PersonalDetailsForm.errors.alternate_phone ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="PersonalDetailsForm.errors.alternate_phone" class="mt-2 text-sm text-red-600" id="alternate_phone-error">{{ PersonalDetailsForm.errors.alternate_phone }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Data de Nascimento
                    </dt>
                    <dd v-if="!editPersonalInformation" class="mt-1 text-sm text-gray-900">
                      {{ dob }}
                    </dd>
                    <dd v-if="editPersonalInformation">
                      <div class="mt-1">
                      <DateInput v-model="PersonalDetailsForm.dob" name="dob" id="dob" mode="date" locale="pt" :masks="{ title: 'MMM YYYY' }" color="orange" />
                      <p v-if="PersonalDetailsForm.errors.dob" class="mt-2 text-sm text-red-600" id="dob-error">{{ PersonalDetailsForm.errors.dob }}</p>
                      </div>
                    </dd>
                  </div>

                </dl>
              </div>

            </div>
          </section>


        </div>

        <section aria-labelledby="timeline-title" class="lg:col-start-3 lg:col-span-1">
          <div class="bg-white px-4 py-5 shadow sm:rounded-lg sm:px-6">
            <h2 id="timeline-title" class="text-lg font-medium text-gray-900">Outros Dados</h2>

            <!-- Activity Feed -->
            <div class="mt-6 flow-root">

            </div>
            <div class="mt-6 flex flex-col justify-stretch">
              <button type="button" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Outros Dados
              </button>
            </div>
          </div>
        </section>
      </div>
      </form>
    </div>

</template>

<script setup>
import { computed, ref } from 'vue'
import {
  CheckCircleIcon,
  BuildingOfficeIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import combobox from "@/Components/combobox.vue";
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

const props =  defineProps({
    name: String,
    email: String,
    username: String,
    phone: String,
    alternate_phone: String,
    id_number: String,
    dob: String,
    gender: String,
});

const editPersonalInformation = ref(false);

const genderOptions = ref([
  {
    value: 'O',
    label: 'Outro'
  },
  {
    value: 'M',
    label: 'Masculino'
  },
  {
    value: 'F',
    label: 'Feminino'
  }
]);

const PersonalDetailsForm = useForm({
            name: props.name,
            email: props.email,
            username: props.username,
            phone: props.phone,
            alternate_phone: props.alternate_phone,
            id_number: props.id_number,
            dob: props.dob,
            gender: props.gender,
        })

let dataChanged = computed(() => {
    return PersonalDetailsForm.isDirty;
});

let submit = () => {
  PersonalDetailsForm.put('/user/profile-information', { errorBag: 'updateProfileInformation'}, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        editPersonalInformation.value = false;
      }
  });
}

</script>

<script>

import Layout from "../../Shared/Layout.vue";

export default {
    layout: Layout
}
</script>
