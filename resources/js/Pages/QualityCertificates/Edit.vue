<script setup>
import { reactive, ref } from 'vue';
import Layout from "@/Shared/Layouts/Layout.vue";
import DatePicker from "@/Components/date-picker.vue";
import UserCard from '@/Pages/partials/user-card.vue'
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineOptions({
    layout: Layout
});
const props = defineProps({
    record: Object
});

const updateRange = (e) => {
  form.dob = e;
}

const form = reactive(useForm(props.record.data));

const editUserInfo = ref(false);

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

let submit = () => {
    form.put(route('users.update', {id: form.id}), {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
      },
  });
  }

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  input: 'YYYY-MM-DD',
  model: 'YYYY-MM-DD',
});

</script>
<template>
    <user-card :auth="props.record.data" greeting="A modificar o usuário" />

    <div class="bg-white">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="mt-8 max-w-3xl mx-auto grid grid-cols-1 gap-6 sm:px-6 lg:max-w-7xl lg:grid-flow-col-dense lg:grid-cols-1">
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
                        Dados relacionados a conta do {{ props.record.data.name }}
                      </p>
                    </div>
                    <div class="ml-4 mt-4 flex-shrink-0">
                      <button v-if="!form.isDirty" @click="editUserInfo = !editUserInfo" type="button" class="relative inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-orange-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-800">
                        {{ editUserInfo ? 'Cancelar Edição' : 'Editar Informação' }}
                      </button>
                      <button v-if="form.isDirty" @click="submit" class="relative inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-orange-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-800">
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
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.username }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.username" id="username" name="username" type="text" autocomplete="username" :class="[form.errors.username ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.username" class="mt-2 text-sm text-red-600" id="username-error">{{ form.errors.username }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Nome
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.name }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.name" id="name" name="name" type="text" autocomplete="name" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.name }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Gênero
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.gender }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <select v-model="form.gender" id="gender" name="gender" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm rounded-md" :class="[form.errors.gender ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']">
                        <option :value="gender.value" v-for="(gender, index) in genderOptions" :key="index">{{ gender.label }}</option>
                      </select>
                      <p v-if="form.errors.gender" class="mt-2 text-sm text-red-600" id="gender-error">{{ form.errors.gender }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Endereço de Email
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.email }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.email" id="email" name="email" type="text" autocomplete="email" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.email }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Nº B.I (Bilhete de Identidade)
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.id_number }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.id_number" id="id_number" name="id_number" type="text" autocomplete="id_number" :class="[form.errors.id_number ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.id_number" class="mt-2 text-sm text-red-600" id="id_number-error">{{ form.errors.id_number }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Contacto
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.primary_phone }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.primary_phone" id="phone" name="phone" type="text" autocomplete="phone" :class="[form.errors.primary_phone ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.primary_phone" class="mt-2 text-sm text-red-600" id="phone-error">{{ form.errors.primary_phone }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Contacto Alternativo
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.secondary_phone }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <input v-model="form.secondary_phone" id="secondary_phone" name="secondary_phone" type="text" autocomplete="secondary_phone" :class="[form.errors.secondary_phone ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="form.errors.secondary_phone" class="mt-2 text-sm text-red-600" id="secondary_phone-error">{{ form.errors.secondary_phone }}</p>
                      </div>
                    </dd>
                  </div>
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      Data de Nascimento
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      {{ form.dob }}
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                      <date-picker v-model.string="form.dob" name="dob" id="dob" mode="date" locale="pt" :masks="masks" color="orange" @update:model-value="updateRange" />
                      <p v-if="form.errors.dob" class="mt-2 text-sm text-red-600" id="dob-error">{{ form.errors.dob }}</p>
                      </div>
                    </dd>
                  </div>
                  
                </dl>
              </div>
              
            </div>
          </section>

          
        </div>
      </div>
      </form>
    </div>

</template>