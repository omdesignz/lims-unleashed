<template>
  <Combobox v-model="selectedPeople" multiple nullable>
    <div className="flex flex-auto flex-wrap">
    <div
    v-for="person in selectedPeople"
              as="template"
              :key="person.id"
        class="flex justify-center items-center m-1 font-medium py-1 px-2 bg-white rounded-full text-teal-700 bg-teal-100 border border-teal-300"
    >
        <div class="text-xs font-normal leading-none max-w-full flex-initial">
        {{person.name}}
        </div>
        <div class="flex flex-auto flex-row-reverse">
        <div>

        </div>
        </div>
    </div>
    <div class="flex-1">
    <ComboboxInput class="bg-transparent p-1 px-2 appearance-none outline-none h-full w-full text-gray-800" />
    </div>
    </div>
    <ComboboxOptions class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
      <ComboboxOption v-for="person in people" :key="person.id" :value="person" class="relative cursor-default select-none py-2 pl-10 pr-4">
        {{ person.name }}
      </ComboboxOption>
    </ComboboxOptions>
  </Combobox>
</template>

<script setup>
  import { ref } from 'vue'
  import {
    Combobox,
    ComboboxInput,
    ComboboxOptions,
    ComboboxOption,
  } from '@headlessui/vue'

  const people = [
    { id: 1, name: 'Durward Reynolds' },
    { id: 2, name: 'Kenton Towne' },
    { id: 3, name: 'Therese Wunsch' },
    { id: 4, name: 'Benedict Kessler' },
    { id: 5, name: 'Katelyn Rohan' },
  ]
  const selectedPeople = ref([people[0], people[1]])
</script>