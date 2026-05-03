<script setup>
import { reactive, ref, computed } from 'vue';
import Layout from "@/Shared/Layouts/Layout.vue";
import DatePicker from "@/Components/date-picker.vue";
import UserCard from '@/Pages/partials/user-card.vue';
import ComboboxMultiple from '@/Components/combobox-multiple.vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import SignaturePad from '@/Components/signature-pad.vue';
import { usePermission } from '@/Composables/usePermissions';
import Fuse from 'fuse.js';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const { hasRole, hasPermission } = usePermission();

defineOptions({
    layout: Layout
});
const props = defineProps({
    record: Object,
    permissions: Array,
    roles: Array
});

const fuse = new Fuse(props?.permissions, {
      keys: ['label'],
      isCaseSensitive: false
    });

const permInput = ref('');

const results = computed(() => {
  return permInput.value ? fuse.search(permInput.value) : props?.permissions?.map(item => ({
    item: Object.assign(item, {}),
    matches: [],
    score: 1
  }));
});

const updateRange = (e) => {
  form.dob = e;
}

function saveSignature (e) {
  useForm({
    signature: e
  }).post(route('users.setsignature',{user: form.id}), {
          preserveScroll: true,
          onSuccess: () => {
            
          },
      });
}

function deleteSignature (e) {
  useForm({}).get(route('users.unsetsignature',{user: form.id}), {
          preserveScroll: true,
          onSuccess: () => {
            
          },
      });
}

const form = reactive(useForm(props.record));

const signature = useForm({
  data: null
});

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
        form.reset()
      },
  });
}

const updatePass = useForm({
  id: null,
  password: null,
  password_confirmation: null,
});

let submitUpdatePass = () => {
  updatePass.put(route('users.setpass',{user: form.id}), {
          preserveScroll: true,
          onSuccess: () => {
            updatePass.reset()
          },
      });
}

const masks = ref({
  modelValue: 'YYYY-MM-DD',
  data: 'YYYY-MM-DD',
  input: 'YYYY-MM-DD',
  model: 'YYYY-MM-DD',
});

function loadDepartments(query, setOptions) {
    fetch('/departments/getDepartment?q=' + query)
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

function loadRoles(query, setOptions) {
    fetch('/roles/getRole?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.label,
            };
        })
        );
    });
}

</script>
<template>
    <user-card :auth="props.record" greeting="A modificar o usuário" />

    <div class="bg-white">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="mt-8 max-w-3xl mx-auto grid grid-cols-1 gap-6 sm:px-6 lg:max-w-7xl lg:grid-flow-col-dense lg:grid-cols-1">
        <div class="space-y-6 lg:col-start-1 lg:col-span-2">
          <!-- Description list-->
          <section aria-labelledby="applicant-information-title">
            <div class="bg-white shadow sm:rounded-lg">
              
                <div class="bg-white px-4 py-5 sm:px-6">
                  <div class="-ml-4 -mt-4 flex justify-between items-center flex-wrap sm:flex-nowrap">
                    <div class="ml-4 mt-4">
                      <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ $t('gestlab.general.labels.users.general_info_title') }}
                      </h3>
                      <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        {{ $t('gestlab.general.labels.users.general_info_description') }} {{ props.record.name }}
                      </p>
                    </div>
                    <div class="ml-4 mt-4 flex-shrink-0">
                      <button v-if="updatePass.isDirty" @click="submitUpdatePass" type="button" class="relative inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-blue-900 hover:text-white focus:outline-none mr-2">
                        {{ $t('gestlab.general.buttons.update_password') }}
                      </button>
                      <button v-if="!form.isDirty" @click="editUserInfo = !editUserInfo" type="button" class="relative inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-blue-900 hover:text-white focus:outline-none">
                        {{ editUserInfo ? $t('gestlab.general.buttons.cancel') : $t('gestlab.general.buttons.edit') }}
                      </button>
                      <button v-if="form.isDirty" @click="submit" type="button" class="relative inline-flex items-center px-4 py-2 shadow-sm text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-blue-900 hover:text-white focus:outline-none">
                        {{ $t('gestlab.general.buttons.update') }}
                      </button>
                    </div>
                  </div>
                </div>
              
              <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.username') }}
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
                      {{ $t('gestlab.general.labels.users.name') }}
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
                      {{ $t('gestlab.general.labels.users.gender') }}
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
                      {{ $t('gestlab.general.labels.users.email') }}
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
                      {{ $t('gestlab.general.labels.users.id_number') }}
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
                      {{ $t('gestlab.general.labels.users.primary_phone') }}
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
                      {{ $t('gestlab.general.labels.users.secondary_phone') }}
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
                      {{ $t('gestlab.general.labels.users.dob') }}
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

                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.departments') }}
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      <span v-for="(department, departmentIdx) in form.departments" :key="department.id" class="rounded-full bg-blue-900 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 mr-2">{{ department.label }}</span>
                    </dd>
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                        <ComboboxMultiple v-model="form.departments" :load-options="loadDepartments" multiple/>
                      <p v-if="form.errors.departments" class="mt-2 text-sm text-red-600" id="departments-error">{{ form.errors.departments }}</p>
                      </div>
                    </dd>
                  </div>
                  <hr class="sm:col-span-full">
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.roles') }}
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      <span v-for="(role, roleIdx) in form.roles" :key="role.id" class="rounded-full bg-blue-900 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 mr-2">{{ role.label }}</span>
                    </dd>
                    
                    <dd v-if="editUserInfo">
                      <div class="mt-1">
                        <ComboboxMultiple v-model="form.roles" :load-options="loadRoles" multiple/>
                      <p v-if="form.errors.roles" class="mt-2 text-sm text-red-600" id="roles-error">{{ form.errors.roles }}</p>
                      </div>
                    </dd>
                  </div>
                  <hr class="sm:col-span-full">

                  <div class="sm:col-span-full">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.permissions') }}
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      <span v-for="(permission, permissionIdx) in form.permissions" :key="permission.id" class="rounded-full bg-blue-900 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900 mr-2">{{ permission.label }}</span>
                    </dd>
                    <dd v-if="editUserInfo && hasPermission('edit_permissions')">

                      <div class="relative w-full text-gray-500 focus-within:text-blue-900 mb-2">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                          <MagnifyingGlassIcon class="flex-shrink-0 h-5 w-5 animate-bounce" aria-hidden="true" />
                        </div>
                        <input v-model="permInput" name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-transparent py-2 pl-8 pr-3 text-base text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:hidden" placeholder="Pesquisar por uma permissão" type="search" />
                        <input v-model="permInput" name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-transparent py-2 pl-8 pr-3 text-sm text-gray-500 placeholder-gray-500 focus:outline-none focus:ring-0 focus:border-transparent focus:text-red-800 focus:placeholder-gray-400 sm:block" placeholder="Pesquisar por uma permissão" type="search" />
                      </div>

                      <div class="mt-1">
                        <ul permission="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                          <li v-for="(permission, index) in results" :key="permission.value" class=""
                                  v-motion
                                  :initial="{ opacity: 0, y: 100 }"
                                  :enter="{ opacity: 1, y: 0, scale: 1 }"
                                  :variants="{ custom: { scale: 2 } }"
                                  :delay="100"
                          >
                          <fieldset>
                            <legend class="sr-only">{{ $t('gestlab.general.labels.users.permissions') }}</legend>
                            <div class="space-y-5">
                              <div class="relative flex items-start">
                                <div class="flex h-6 items-center">
                                  <input :value="permission.item" v-model="form.permissions" :id="`permission-${index+1}`" :aria-describedby="`permission-${index+1}`" :name="`permission-${index+1}`" type="checkbox" class="h-4 w-4 rounded-full border-blue-900 text-blue-900 focus:ring-blue-900" />
                                </div>
                                <div class="ml-3 text-sm leading-6">
                                  <label :for="`permission-${index+1}`" class="font-medium text-gray-900">{{ permission.item.label }}</label>
                                  <p :id="`permission-${index+1}`" class="text-gray-500"></p>
                                </div>
                              </div>
                              
                            </div>
                          </fieldset>

                        </li>
                        </ul>
                      </div>
                    </dd>
                  </div>
                  <hr class="sm:col-span-full">
                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.password') }}
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      ---
                    </dd>
                    <dd v-if="editUserInfo && hasPermission('reset-password_users') || editUserInfo && form.id === $props?.auth?.user?.id">
                      <div class="mt-1">
                      <input v-model="updatePass.password" id="password" name="password" type="password" autocomplete="password" :class="[updatePass.errors.password ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="updatePass.errors.password" class="mt-2 text-sm text-red-600" id="password-error">{{ updatePass.errors.password }}</p>
                      </div>
                    </dd>
                  </div>

                  <div class="sm:col-span-1">
                    <dt class="text-sm font-medium text-gray-500">
                      {{ $t('gestlab.general.labels.users.password_confirmation') }}
                    </dt>
                    <dd v-if="!editUserInfo" class="mt-1 text-sm text-gray-900">
                      ---
                    </dd>
                    <dd v-if="editUserInfo && hasPermission('reset-password_users') || editUserInfo && form.id === $props?.auth?.user?.id">
                      <div class="mt-1">
                      <input v-model="updatePass.password_confirmation" id="password_confirmation" name="password_confirmation" type="password" autocomplete="password_confirmation" :class="[updatePass.errors.password_confirmation ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                      <p v-if="updatePass.errors.password_confirmation" class="mt-2 text-sm text-red-600" id="password_confirmation-error">{{ updatePass.errors.password_confirmation }}</p>
                      </div>
                    </dd>
                  </div>
                  
                </dl>
              </div>
              
            </div>
          </section>

          <section aria-labelledby="user-signature" v-if="form.id === $props?.auth?.user?.id">
            <SignaturePad :current-signature="props.record?.signature_url" @save="saveSignature" @delete="deleteSignature" />
          </section>

          
        </div>
      </div>
      </form>
    </div>

</template>