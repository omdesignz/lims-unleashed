<template>
    <TransitionRoot as="template" :show="props.open">
        <Dialog as="div" class="relative z-50" @close="open = false, emit('picker-closed')">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 w-25 sm:p-6">
                            <div>
                                <!-- <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <OutlinedIcons.FaceSmileIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
                                </div> -->
                                <div class="mt-3 text-center sm:mt-5">
                                    <!-- <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">IconFactory | {{ selected }}</DialogTitle> -->
                                    <div class="mt-2">
                                        <div class="flex items-center justify-center px-6 py-4 md:mx-auto md:max-w-3xl lg:mx-0 lg:max-w-none xl:px-0">
                                            <div class="w-1/2">
                                                <label for="search" class="sr-only">Search</label>
                                                <div class="relative">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                        <OutlinedIcons.MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                                    </div>
                                                    <input v-model="query" id="search" name="search" class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm placeholder-gray-500 focus:border-blue-500 focus:text-gray-900 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm" placeholder="Search" type="text" />
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h2 class="text-sm font-medium text-gray-500">Search Icons</h2>
                                            <ul role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                                                <li v-for="item in filteredItems" :key="item.name" class="col-span-1 flex rounded-md shadow-sm group">
                                                    <div class="bg-blue-100 flex-shrink-0 flex items-center justify-center w-16 text-white text-sm font-medium rounded-l-md hover:text-red-500 transform transition-all duration-200 hover:scale-150">
                                                        <button type="button" @click="select(item.name)">
                                                            <component :is="item.icon" class="h-6 w-6 flex-shrink-0 text-gray-900" aria-hidden="true" />

                                                        </button>
                                                    </div>
                                                    <div class="flex flex-1 items-center justify-between truncate rounded-r-md border-t border-r border-b border-gray-200 bg-white">
                                                        <div class="flex-1 truncate px-4 py-2 text-sm">
                                                            <p class="text-gray-500">{{ item.name }}</p>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="mt-5 sm:mt-6">
                                <button type="button" class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:text-sm" @click="open = false">Go back to dashboard</button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import * as OutlinedIcons from '@heroicons/vue/24/outline'

const props =defineProps({
    items: Array,
    open: Boolean,
    query: String,
    selected: String,
    modelValue: [String, Date, Object],
});

const emit = defineEmits(['icon-selected', 'picker-closed', 'update:modelValue'])

const query = ref(null);
const selected = ref(null);
const items = ref([]);
const icons = ref([]);


onMounted(() => {
    query.value = '';
    selected.value = '';

    Object.keys(OutlinedIcons).filter((i) => i.endsWith('Icon')).forEach((elem) => {
        icons.value.push({
            // name: elem.split(/(?=[A-Z])/).join('-').toLowerCase(), arrow-down-icon
            name: elem,
            icon: OutlinedIcons[elem]
        });
    })

    // console.log(icons.value[0]);
});

const filteredItems = computed(() => {
    return icons.value.filter((icon) => {
        return icon.name.toLowerCase().indexOf(query.value.toLowerCase()) !== -1
    });
});

const select = (selection) => {
    selected.value = selection;
    emit('icon-selected', selection);
    // emit("update:modelValue", selection);
    // open.value = false;
    emit('picker-closed');
}

function handleUpdateModelValue(selected) {
  emit("update:modelValue", selected);
}

</script>
