<template>
        <div v-if="!props.item.children.length">
            <Link :href="props.item.href" :class="[props.item.current ? 'bg-gradient-to-r from-sky-800 to-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gradient-to-r from-sky-800 to-cyan-600 hover:text-white hover:bg-opacity-5', 'group w-full flex items-center pl-2 py-2 text-sm font-medium rounded-r-3xl']">
                <component :is="props.item.icon" :class="[props.item.current ? 'text-white' : 'text-gray-400 group-hover:text-white', 'mr-3 flex-shrink-0 h-6 w-6']" aria-hidden="true" />
                {{ props.item.label }}
            </Link>
        </div>
        <Disclosure as="div" v-else class="space-y-1" v-slot="{ open }" :default-open="hasActiveChild">
            <DisclosureButton :class="[props.item.current ? 'bg-gradient-to-r from-sky-800 to-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gradient-to-r from-sky-800 to-cyan-600 hover:text-white hover:bg-opacity-5', 'group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-r-3xl']">
                <component v-if="props.item.icon" :is="props.item.icon" class="mr-3 h-6 w-6 flex-shrink-0 text-gray-400 group-hover:text-white" aria-hidden="true" />
                <span class="flex-1">{{ props.item.label }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" :class="[open ? 'text-gray-400 rotate-90' : 'text-gray-300', 'ml-3 h-5 w-5 flex-shrink-0 transform transition-colors duration-150 ease-in-out group-hover:text-white group-hover:transform group-hover:transition-all group-hover:duration-200 group-hover:scale-125']">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                </svg>
            </DisclosureButton>
            <DisclosurePanel class="space-y-1">
                <NavItem
                    v-for="child in props.item.children"
                    :item="child"
                />
            </DisclosurePanel>
        </Disclosure>
    </template>

<script setup>
import {computed} from "vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'


const props = defineProps({
    item: Object,
});

const hasActiveChild = computed(() => {
    function hasActiveItem(items) {
        return items.some(item => item.current || hasActiveItem(item.children));
    }

    return hasActiveItem(props.item.children);
});
</script>

<style scoped>

</style>
