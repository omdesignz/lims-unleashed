<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import {throttle} from "lodash";
import { TrashIcon, PlusCircleIcon, ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';
import { Disclosure, DisclosureButton, DisclosurePanel } from "@headlessui/vue";
import confirmDialog from "@/Components/confirm-dialog.vue";


defineOptions({
  layout: Layout
});

const props = defineProps({
   
});

const form = useForm({
    code: '',
    description: '',
    price: 0,
    fixed_price: 0,
    exemption_id: '',
    tax_id: '',
    tax_percentage: '',
    exemption: '',
    charge_tax: false,
    withhold_tax: false,
    profiles: []
});

const totalMatrixPrice = computed(() => {
  return form.profiles.reduce((acc, item) => {
    return parseFloat(parseFloat(acc) + parseFloat(item.price)).toFixed(2);
  }, 0);
});

const showDeleteConfirmation = ref(false);


const addProfile = () => {
    form.profiles.push({
        profile_id: '',
        profile: '',
        price: 0,
    });
}

const removeProfile = (index) => {
    form.profiles.splice(index, 1);
}

const onSearchAnalysisCategoryChange = throttle(function (term) {
    router.get(route('profiles.create'), {term}, {
    preserveState: true,
    preserveScroll: true,
    replace: true
    })
}, 300)

function loadExemptions(query, setOptions) {
    fetch('/taxexemptions/getExemption?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.code,
            };
        })
        );
    });
}

function loadTaxTypes(query, setOptions) {
    fetch('/taxtypes/getTaxType?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            percent: result.percent,
            };
        })
        );
    });
}

function loadProfiles(query, setOptions) {
    fetch('/profiles/getProfile?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            price: result.price,
            };
        })
        );
    });
}



let submit = () => {

if(!form.id) {
  form.post(route('matrixes.store'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset()
      },
  });
} else {
  form.put(route('matrixes.update',{matrix: form.id}), {
      preserveScroll: true,
      onSuccess: () => {
        openslideover.value = false;
        form.reset()
      },
  });
}

}

</script>

<template>
<div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">{{ $t('gestlab.general.labels.matrixes.page_create_description') }} {{ form.code }}</p>
</div>

<form @submit.prevent>
    <div class="space-y-12">
      
        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-10">
          

          <!-- <div class="col-span-full">
            <label for="street-address" class="block text-sm font-medium leading-6 text-gray-900">Nome</label>
            <div class="mt-2">
              <input type="text" name="street-address" id="street-address" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" />
            </div>
          </div> -->

          <div class="sm:col-span-2">
            <label for="code" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.code') }}</label>
            <div class="mt-2">
              <input v-model="form.code" type="text" name="code" id="code" autocomplete="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.code ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.code" class="mt-2 text-xs text-red-600" id="code-error">{{ form.errors.code }}</p>
          </div>

          

          <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.description') }}</label>
            <div class="mt-2">
              <input v-model="form.description" type="text" name="description" id="description" autocomplete="description" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.description ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.description" class="mt-2 text-xs text-red-600" id="description-error">{{ form.errors.description }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="price" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.price') }}</label>
            <div class="mt-2">
              <input disabled :value="totalMatrixPrice" v-model="form.price" type="number" name="price" id="price" autocomplete="price" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.price ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.price" class="mt-2 text-xs text-red-600" id="price-error">{{ form.errors.price }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="fixed_price" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.fixed_price') }}</label>
            <div class="mt-2">
              <input v-model="form.fixed_price" type="number" name="fixed_price" id="fixed_price" autocomplete="fixed_price" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.fixed_price ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.fixed_price" class="mt-2 text-xs text-red-600" id="fixed_price-error">{{ form.errors.fixed_price }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="charge_tax" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.charge_tax') }}</label>
            <div class="mt-2">
              <input v-model="form.charge_tax" type="checkbox" name="charge_tax" id="charge_tax" autocomplete="charge_tax" class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6" :class="[form.errors.charge_tax ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" />
            </div>
            <p v-if="form.errors.charge_tax" class="mt-2 text-xs text-red-600" id="charge_tax-error">{{ form.errors.charge_tax }}</p>
          </div>

          <div class="sm:col-span-2" v-if="!form.charge_tax">
            <label for="exemption_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.exemption_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.exemption_id" v-model="form.exemption_id" :load-options="loadExemptions"/>
            </div>
            <p v-if="form.errors.exemption_id" class="mt-2 text-xs text-red-600" id="exemption_id-error">{{ form.errors.exemption_id }}</p>
          </div>

          <div class="sm:col-span-2" v-else>
            <label for="tax_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.tax_id') }}</label>
            <div class="mt-2">
              <combobox :hasError="form.errors.tax_id" v-model="form.tax_id" :load-options="loadTaxTypes"/>
            </div>
            <p v-if="form.errors.tax_id" class="mt-2 text-xs text-red-600" id="tax_id-error">{{ form.errors.tax_id }}</p>
          </div>

          <!-- Withhold Tax -->
            <div class="sm:col-span-2">
              <label for="withhold_tax" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.withhold_tax') }}</label>
              <div class="mt-2">
                <input v-model="form.withhold_tax" type="checkbox" name="withhold_tax" id="withhold_tax" class="focus:ring-blue-900 h-4 w-4 text-blue-900 border-blue-900 rounded-full" />
              </div>
              <p v-if="form.errors.withhold_tax" class="mt-2 text-xs text-red-600" id="withhold_tax-error">{{ form.errors.withhold_tax }}</p>
            </div>

        </div>

      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900 flex items-center">
          {{ form.profiles.length }} {{ $t('gestlab.general.labels.matrixes.items') }}
          <button @click="addProfile" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
            <PlusCircleIcon class="h-5 w-5" />
          </button>
        </h2>
        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $t('gestlab.general.labels.matrixes.items_tagline') }} {{ form.code }}</p>

      </div>

      <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
        <li v-for="(profile, index) in form.profiles" :key="profile.value" class="rounded-xl border border-gray-200"
                v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
        >
          <div class="flex items-center gap-x-4 border-b border-gray-900/5 bg-gray-50 p-3">
            <div class="text-sm font-medium leading-6 text-gray-900">

              <ClipboardDocumentCheckIcon class="h-5 w-5" />

              </div>
              {{ index + 1 }}º {{ $t('gestlab.general.labels.matrixes.profile_id') }}

            <button @click="removeProfile(index)" class="hover:text-blue-900 transform transition-all duration-200 hover:scale-150 ml-auto">
              <TrashIcon class="h-5 w-5" />
            </button>
          </div>
          <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6">
            <div class="gap-x-4 py-3">
              <dd class="text-gray-700">
                <div class="sm:col-span-full">
                  <label for="profile_id" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.profile_id') }}</label>
                  <div class="mt-2">
                      <combobox :hasError="form.errors[`profiles.${index}.profile_id`]" v-model="profile.profile_id" :load-options="loadProfiles" @update:model-value="(e) => {
                        profile.price = e.price;
                      }"/>
                  </div>
                  <p v-if="form.errors[`profiles.${index}.profile_id`]" class="mt-2 text-xs text-red-600" :id="`item-${index}-error`">{{ form.errors[`profiles.${index}.profile_id`] }}</p>
                </div>
              </dd>
            </div>

          </dl>
        </li>
      </ul>
      <p v-if="form.errors.profiles" class="mt-2 text-xs text-red-600">{{ form.errors.profiles }}</p>
    </div>

    <div class="mt-6 flex items-center justify-end gap-x-6">
      <!-- <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancela</button> -->
      <button v-if="form.isDirty" @click="showDeleteConfirmation = true" class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">{{ $t('gestlab.general.buttons.submit') }}</button>
    </div>
  </form>

  <confirm-dialog size="sm:max-w-2xl" alignment="sm:items-start" @canceled="showDeleteConfirmation=false" @close="showDeleteConfirmation=false" @confirmed="submit" v-if="showDeleteConfirmation" :title="$t('gestlab.actions.confirmation_dialog_title.default')" :description="$t('gestlab.actions.confirmation_dialog_description.default')" confirm="Sim" cancel="Não">
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
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.code') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.code }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.description') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.description }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.price') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.price }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.fixed_price') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.fixed_price }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.charge_tax') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.charge_tax }}</dd>
            </div>
            <div v-if="!form.charge_tax" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.exemption_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.exemption_id?.label }}</dd>
            </div>
            <div v-else class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.tax_id') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.tax_id?.label }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.withhold_tax') }}</dt>
              <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ form.withhold_tax }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-1 sm:gap-4 sm:px-0">
              
              <div class="w-full pt-2">
                <div class="mx-auto w-full rounded-2xl bg-white">
                  <Disclosure v-slot="{ open }" v-for="(profile, index) in form.profiles" :key="profile.value" v-if="form.profiles.length">
                    <DisclosureButton
                      class="flex w-full justify-between rounded-lg bg-blue-900 px-4 py-2 mb-2 text-left text-sm font-medium text-white focus:outline-none focus-visible:ring focus-visible:ring-blue-900"
                    >
                      <span>{{ parameter.parameter_id?.label }}</span>
                      <ChevronUpIcon
                        :class="open ? 'rotate-180 transform' : ''"
                        class="h-5 w-5 text-white"
                      />
                    </DisclosureButton>
                    <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                      <div class="mt-6 border-t border-gray-100">
                        <dl class="divide-y divide-gray-100">
                          <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.matrixes.profile_id') }}</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ profile.profile_id?.label }}</dd>
                          </div>
                        </dl>
                      </div>
                    </DisclosurePanel>
                  </Disclosure>
                </div>
              </div>
            </div>
          </dl>
        </div>
      </div>

    </div>
  </confirm-dialog>

</template>