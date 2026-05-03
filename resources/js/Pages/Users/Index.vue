<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import ComboboxMultiple from '@/Components/combobox-multiple.vue';
import { TransitionRoot } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { usePermission } from '@/Composables/usePermissions'

const { hasRole, hasPermission } = usePermission();


const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    competenceSummary: Object,
    openCreate: {
      type: Boolean,
      default: false
    },
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

let form = useForm({
    name: '',
    email: '',
    gender: '',
    username: null,
    id: null,
    departments: []
});

const actionId = ref(null);
const recordId = ref(null);

const slideOverDescription = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.description') + form.name : trans('gestlab.slideover.updating.description') + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id ? trans('gestlab.slideover.creating.title') : trans('gestlab.slideover.updating.description');
});

const confirmationDialogTitle = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_title.' + actionId.value);
})


const confirmationDialogDescription = computed(() => {
  return trans('gestlab.actions.confirmation_dialog_description.' + actionId.value);
})

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

const openslideover = ref(props.openCreate);

let actions = [
  {
    id: null,
    label: 'gestlab.actions.bulk_actions_text'
  },
  {
    id: 'delete',
    label: 'gestlab.actions.delete'
  },
  {
    id: 'restore',
    label: 'gestlab.actions.restore'
  },
];

const close = () => {
    openslideover.value = false;
    form.clearErrors();
    // form.reset();
}

const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
    openslideover.value = true;
}

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

let submit = () => {
    form.post('/users', {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
      },
  });
  }

  const confirmAction = () => {
    executeAction(actionId.value);
  }

  const executeAction = (actionId) => {
  const recordIds = props.record.data.filter(record => record.selected).map(record => record.id);

  // if(!recordIds.length) return;

  switch (actionId) {
    case 'delete':
      router.get(`/users/destroy`, {
          recordIds: recordIds
      }, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
        }
      });
      showDeleteConfirmation.value = false;
    break;  

    case 'restore':
        router.get(`/users/restore`, {
          recordIds: recordIds
        }, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
    break;

    case 'ban':
        router.get(route('users.toggleActiveStatus', {id: recordId.value}), {}, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
    break;

    case 'unban':
        router.get(route('users.toggleActiveStatus', {id: recordId.value}), {}, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
    break;

    case 'impersonate':
        router.get(route('users.impersonate', {id: recordId.value}), {}, {
            preserveState:false,
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirmation.value = false;
                actionId = null;
            }
        });
        showDeleteConfirmation.value = false;
  }

}  
</script>
<template>
<div>
<div class="space-y-6">
  <div class="border-b border-gray-200 pb-5">
      <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.page_title') }}</h3>
      <p class="mt-2 max-w-4xl text-sm text-gray-500">
        Gestão operacional de utilizadores, permissões e prontidão de competência técnica para execução, verificação e aprovação.
      </p>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Utilizadores monitorizados</div>
      <div class="mt-2 text-2xl font-semibold text-slate-900">{{ competenceSummary?.tracked_users ?? 0 }}</div>
    </div>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-rose-700">Qualificações expiradas</div>
      <div class="mt-2 text-2xl font-semibold text-rose-900">{{ competenceSummary?.expired_qualifications ?? 0 }}</div>
    </div>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Renovações próximas</div>
      <div class="mt-2 text-2xl font-semibold text-amber-900">{{ competenceSummary?.expiring_soon ?? 0 }}</div>
    </div>
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">Prontas para renovação</div>
      <div class="mt-2 text-2xl font-semibold text-blue-900">{{ competenceSummary?.ready_for_renewal ?? 0 }}</div>
    </div>
    <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-orange-700">Falta evidência</div>
      <div class="mt-2 text-2xl font-semibold text-orange-900">{{ competenceSummary?.missing_evidence ?? 0 }}</div>
    </div>
  </div>

<records-table :record="props.record" :model="props.model" :abilities="props.abilities" :fields="props.fields" :slideOverEdit="props.slideOverEdit" :query="props.query" :actions="actions" @execute-action="($event) => {showDeleteConfirmation = true; actionId = $event}" @create-record="openslideover=true" @slideover-on="openSlideoverWithData">
  <template v-slot:actions="id">
    <!-- content for the actions slot -->
    
    <button type="button" @click="() => {recordId = id.id; actionId='impersonate'; showDeleteConfirmation = true;}" class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-4" v-if="!record.deleted && hasPermission('impersonate_users')">
      <div class="flex">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
      </svg>

      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
        <path fill-rule="evenodd" d="M15.97 2.47a.75.75 0 011.06 0l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 11-1.06-1.06l3.22-3.22H7.5a.75.75 0 010-1.5h11.69l-3.22-3.22a.75.75 0 010-1.06zm-7.94 9a.75.75 0 010 1.06l-3.22 3.22H16.5a.75.75 0 010 1.5H4.81l3.22 3.22a.75.75 0 11-1.06 1.06l-4.5-4.5a.75.75 0 010-1.06l4.5-4.5a.75.75 0 011.06 0z" clip-rule="evenodd" />
      </svg>


      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
      </svg>
      </div>

    </button>

    <button type="button" @click="() => {recordId = id.id; actionId=id.isActive ? 'ban' : 'unban'; showDeleteConfirmation = true;}" class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-4" v-if="!record.deleted && hasPermission('ban_users')">

      <svg v-if="id.isActive" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-green-500">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
      </svg>

      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-red-500">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
      </svg>




    </button>
  </template>
</records-table> <br>
 </div>

<slide-over v-if="openslideover" @close="close" :title="slideOverTitle" :description="slideOverDescription">
    <template #content>
        <div class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0">
                        <!-- Name -->
                        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"> 
                          <div>
                            <label for="name" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.users.name') }}</label>
                          </div>
                          <div class="sm:col-span-2">
                            <input v-model="form.name" type="text" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600" id="name-error">{{ form.errors.name }}</p>
                          </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                          <div>
                            <label for="email" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.users.email') }}</label>
                          </div>
                          <div class="sm:col-span-2">
                            <input v-model="form.email" type="email" name="email" id="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                            <p v-if="form.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.email }}</p>
                          </div>
                        </div>
  
                        <!-- Usuário -->
                        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                          <div>
                            <label for="username" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.users.username') }}</label>
                          </div>
                          <div class="sm:col-span-2">
                            <input v-model="form.username" type="text" name="username" id="username" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.username ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
                            <p v-if="form.errors.username" class="mt-2 text-sm text-red-600" id="username-error">{{ form.errors.username }}</p>
                          </div>
                        </div>

                        <!-- Departamentos -->
                        <div class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                          <div>
                            <label for="departments" class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5">{{ $t('gestlab.general.labels.users.departments') }}</label>
                          </div>
                          <div class="sm:col-span-2">
                            <ComboboxMultiple v-model="form.departments" :load-options="loadDepartments" multiple/>
                            <p v-if="form.errors.departments" class="mt-2 text-sm text-red-600" id="departments-error">{{ form.errors.departments }}</p>
                          </div>
                        </div>
                        
  
                        <!-- Gender -->
                        <fieldset class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5">
                          <legend class="sr-only">{{ $t('gestlab.general.labels.users.gender') }}</legend>
                          <div class="text-sm font-medium leading-6 text-gray-900" aria-hidden="true">{{ $t('gestlab.general.labels.users.gender') }}</div>
                          <div class="space-y-5 sm:col-span-2">
                            <div class="space-y-5 sm:mt-0">
                              <div class="relative flex items-start" v-for="(gender, index) in genderOptions.sort()">
                                <div class="absolute flex h-6 items-center">
                                  <input :value="gender.value" v-model="form.gender" :id="gender.label" :name="gender.label" :aria-describedby="gender.label" type="radio" class="h-4 w-4 border-gray-300 text-blue-900 focus:ring-blue-900" :checked="gender.value == form.gender" />
                                </div>
                                <div class="pl-7 text-sm leading-6">
                                  <label for="gender-male" class="font-medium text-gray-900">{{ gender.value }}</label>
                                  <p id="gender-male" class="text-gray-500">{{ gender.label }}</p>
                                </div>
                              </div>
                            </div>
                            <hr class="border-gray-200" />
                            <p v-if="form.errors.gender" class="mt-2 text-sm text-red-600" id="gender-error">{{ form.errors.gender }}</p>
                            
                          </div>
                        </fieldset>
                      </div>
    </template>

    <template #action_buttons>
        <div class="flex justify-end space-x-3">
        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50" @click="openslideover = false; form.reset()">{{ $t('gestlab.general.buttons.cancel') }}</button>
        <!-- <TransitionRoot
            :show="!form.isDirty"
            enter="transition-opacity duration-75"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150" 
            leave-from="opacity-100"
            leave-to="opacity-0"
        >
            I will appear and disappear.
        </TransitionRoot> -->
        <button v-if="form.isDirty" @click="showDeleteConfirmationSlideover = true" :disabled="form.processing" type="button" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900">{{ !form.id ? $t('gestlab.general.buttons.submit') : $t('gestlab.general.buttons.update') }}</button>
        </div>
    </template>
</slide-over>

<confirm-dialog @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="confirmAction" v-if="showDeleteConfirmation" :title="confirmationDialogTitle" :description="confirmationDialogDescription" confirm="Sim" cancel="Não" />

<confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmationSlideover=false" @close="showDeleteConfirmationSlideover=false" @confirmed="submit" v-if="showDeleteConfirmationSlideover" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
    <div class="mt-4">
      <div class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"><p class="text-xs">{{ $t('gestlab.general.labels.summary') }}</p></div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">

            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.name') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.name }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.email') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.email }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.username') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.username }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.departments') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.departments?.map((item) => item.label).join(", ") }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.users.gender') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.gender?.label }}</dd>
            </div>
            
          </dl>
        </div>
      </div>

    </div>
</confirm-dialog>

</div>
</template>
