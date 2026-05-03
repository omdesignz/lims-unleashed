<script setup>
import { computed, ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import { useForm, usePage } from '@inertiajs/vue3';
import { ColorPicker } from "vue3-colorpicker"; 
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    settings: Object,
    model: String,
    abilities: Array,
});

const form = useForm(props?.settings);



const editSettings = ref(false);

let submit = () => {

form.post(route('generalsettings.update'), {
      preserveScroll: true,
      preserveState: false,
      replace: true,
      onSuccess: () => {
        form.reset()
      },
  });

}

const tabs = [
  { name: 'Geral', href: '#general', current: true },
]

let selectedTab = ref('#general');

const selectTab = (i) => {
        let selectedIndex = i

        // loop over all the tabs
        tabs.forEach((tab, index) => {
          tab.current = (index === i)
        })
      }
</script>
<template>
    <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.system_settings.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500 flex items-center">
        <button type="button" @click="editSettings = !editSettings" class="bg-blue-900 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2" role="switch" :aria-checked="editSettings" :class="{ 'bg-blue-900': editSettings, 'bg-gray-200': !editSettings }">
            <span class="sr-only">Edit Setting</span>
            <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
            <span class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" :class="{ 'translate-x-5': editSettings, 'translate-x-0': !editSettings }">
                <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                <span class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="!editSettings" :class="{ 'opacity-0 duration-100 ease-out': editSettings, 'opacity-100 duration-200 ease-in': !editSettings }">
                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                </span>
                <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                <span class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true" v-if="editSettings" :class="{ 'opacity-100 duration-200 ease-in': editSettings, 'opacity-0 duration-100 ease-out': !editSettings }">
                <svg class="h-3 w-3 text-blue-900" fill="currentColor" viewBox="0 0 12 12">
                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                </svg>
                </span>
            </span>
        </button>
        <button v-if="editSettings && form.isDirty" @click="submit" class="inline-flex ml-2 justify-center rounded-full bg-blue-900 px-3 text-sm h-full font-semibold text-white shadow-sm hover:bg-blue-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.update') }}</button>
    </p>
</div>
<div class="mx-auto max-w-7xl pt-6 lg:flex lg:gap-x-16 lg:px-8">
  <aside class="flex overflow-x-auto border-b border-gray-900/5 py-4 lg:block lg:w-64 lg:flex-none lg:border-0 lg:py-4">
    <nav class="flex-none px-4 sm:px-6 lg:px-0">
      <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
        <li v-for="(tab, index) in tabs" :key="tab.name" @click="selectTab(index), selectedTab = tab.href">
          <!-- Current: "bg-gray-50 text-blue-900", Default: "text-gray-700 hover:text-blue-900 hover:bg-gray-50" -->
          <a :href="tab.href" class="text-gray-700 hover:text-blue-900 hover:bg-gray-50 group flex gap-x-3 rounded-md py-2 pl-2 pr-3 text-sm leading-6 font-semibold" :class="[tab.current ? 'bg-gray-50 text-blue-900' : '']">
            <!-- <svg class="h-6 w-6 shrink-0 text-blue-900" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg> -->
            {{ tab.name }}
          </a>
        </li>
        
      </ul>
    </nav>
  </aside>

  <main class="px-4 py-16 sm:px-6 lg:flex-auto lg:px-0 lg:py-4">
    <div class="mx-auto max-w-2xl space-y-16 sm:space-y-20 lg:mx-0 lg:max-w-none" v-if="selectedTab == '#general'">
      <div>
        <h2 class="text-base font-semibold leading-7 text-gray-900">{{ $t('gestlab.general.labels.system_settings.categories.general.system.title') }}</h2>
        <p class="mt-1 text-sm leading-6 text-gray-500">{{ $t('gestlab.general.labels.system_settings.categories.general.system.description') }}</p>

        <dl class="mt-6 space-y-6 divide-y divide-gray-100 border-t border-gray-200 text-sm leading-6">
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_name') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_name }}</div>
                <div class="mt-1 w-full" v-else>
                    <input v-model="form.app_name" id="app_name" name="app_name" type="text" autocomplete="app_name" :class="[form.errors?.app_name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_agt_valid_name') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_agt_valid_name }}</div>
              <div class="mt-1 w-full" v-else>
                    <input v-model="form.app_agt_valid_name" id="app_agt_valid_name" name="app_agt_valid_name" type="text" autocomplete="app_agt_valid_name" :class="[form.errors?.app_agt_valid_name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_slogan') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_slogan }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_slogan" id="app_slogan" name="app_slogan" type="text" autocomplete="app_slogan" :class="[form.errors?.app_slogan ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_version') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_version }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_version" id="app_version" name="app_version" type="text" autocomplete="app_version" :class="[form.errors?.app_version ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_nif') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_nif }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_nif" id="app_nif" name="app_nif" type="text" autocomplete="app_nif" :class="[form.errors?.app_nif ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_contact') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_contact }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_contact" id="app_contact" name="app_contact" type="number" autocomplete="app_contact" :class="[form.errors?.app_contact ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_email') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_email }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_email" id="app_email" name="app_email" type="email" autocomplete="app_email" :class="[form.errors?.app_email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_primary_color') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="mt-1 w-full">
                <color-picker v-model:pure-color="form.app_primary_color" id="app_primary_color" format="hex" shape="circle" lang="En"/>  
              </div>
            </dd>
          </div>
        </dl>
      </div>

      <div>
        <h2 class="text-base font-semibold leading-7 text-gray-900">{{ $t('gestlab.general.labels.system_settings.categories.general.customer.title') }}</h2>
        <p class="mt-1 text-sm leading-6 text-gray-500">{{ $t('gestlab.general.labels.system_settings.categories.general.customer.description') }}</p>

        <dl class="mt-6 space-y-6 divide-y divide-gray-100 border-t border-gray-200 text-sm leading-6">
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_name') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_name }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_name" id="app_client_name" name="app_client_name" type="text" autocomplete="app_client_name" :class="[form.errors?.app_client_name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_nif') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_nif }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_nif" id="app_client_nif" name="app_client_nif" type="text" autocomplete="app_client_nif" :class="[form.errors?.app_client_nif ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>  
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_address') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_address }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_address" id="app_client_address" name="app_client_address" type="text" autocomplete="app_client_address" :class="[form.errors?.app_client_address ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_contact') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_contact }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_contact" id="app_client_contact" name="app_client_contact" type="number" autocomplete="app_client_contact" :class="[form.errors?.app_client_contact ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_email') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_email }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_email" id="app_client_email" name="app_client_email" type="email" autocomplete="app_client_email" :class="[form.errors?.app_client_email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_lab_name') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_lab_name }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_lab_name" id="app_client_lab_name" name="app_client_lab_name" type="text" autocomplete="app_client_lab_name" :class="[form.errors?.app_client_lab_name ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_lab_province') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_lab_province }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_lab_province" id="app_client_lab_province" name="app_client_lab_province" type="text" autocomplete="app_client_lab_province" :class="[form.errors?.app_client_lab_province ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_lab_director') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_lab_director }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_lab_director" id="app_client_lab_director" name="app_client_lab_director" type="text" autocomplete="app_client_lab_director" :class="[form.errors?.app_client_lab_director ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.app_client_lab_slogan') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900" v-if="!editSettings">{{ props?.settings?.app_client_lab_slogan }}</div>
              <div class="mt-1 w-full" v-else>
              <input v-model="form.app_client_lab_slogan" id="app_client_lab_slogan" name="app_client_lab_slogan" type="text" autocomplete="app_client_lab_slogan" :class="[form.errors?.app_client_lab_slogan ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
              </div>
            </dd>
          </div>
          
        </dl>
      </div>

      <div>
        <h2 class="text-base font-semibold leading-7 text-gray-900">{{ $t('gestlab.general.labels.system_settings.categories.general.lang_date.title') }}</h2>
        <p class="mt-1 text-sm leading-6 text-gray-500">{{ $t('gestlab.general.labels.system_settings.categories.general.lang_date.description') }}</p>

        <dl class="mt-6 space-y-6 divide-y divide-gray-100 border-t border-gray-200 text-sm leading-6">
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.language') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900">Português</div>
              <!-- <button type="button" class="font-semibold text-blue-900 hover:text-blue-900">Update</button> -->
            </dd>
          </div>
          <div class="pt-6 sm:flex">
            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">{{ $t('gestlab.general.labels.system_settings.date_format') }}</dt>
            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
              <div class="text-gray-900">DD-MM-YYYY</div>
              <!-- <button type="button" class="font-semibold text-blue-900 hover:text-blue-900">Update</button> -->
            </dd>
          </div>
          
        </dl>
      </div>
    </div>

    <div class="mx-auto max-w-2xl space-y-16 sm:space-y-20 lg:mx-0 lg:max-w-none" v-if="selectedTab == '#security'">
    <h2>Securiteit</h2>
    </div>
  </main>
</div>
</template>