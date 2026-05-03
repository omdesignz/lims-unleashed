<template>

    <div class="min-h-full">
        <ToastList/>
        <Popover as="header" class="bg-gradient-to-r from-sky-800 to-cyan-600 pb-24" v-slot="{ open }">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <div class="relative flex flex-wrap items-center justify-center lg:justify-between">
                    <!-- Logo -->
                    <div class="absolute left-0 flex-shrink-0 py-5 lg:static">
                        <a href="#">
                            <span class="sr-only"></span>
                            <img class="h-8 w-auto" src="https://tailwindui.com/img/logos/mark.svg?color=cyan&shade=200" alt="" />
                        </a>
                    </div>

                    <!-- Right section on desktop -->
                    <div class="hidden lg:ml-4 lg:flex lg:items-center lg:py-5 lg:pr-0.5">
                        <main-menu />
                        <button type="button" class="flex-shrink-0 rounded-full p-1 text-cyan-200 hover:bg-white hover:bg-opacity-10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="h-6 w-6" aria-hidden="true" />
                        </button>

                        <profile-dropdown />
                    </div>

                    <div class="w-full py-5 lg:border-t lg:border-white lg:border-opacity-20">
                        <div class="lg:grid lg:grid-cols-3 lg:items-center lg:gap-8">
                            <!-- Left nav -->
                            <div class="hidden lg:col-span-2 lg:block">
                                <nav class="flex space-x-4">
                                    <a v-for="item in navigation" :key="item.name" :href="item.href" :class="[item.current ? 'text-white' : 'text-cyan-100', 'text-sm font-medium rounded-md bg-white bg-opacity-0 px-3 py-2 hover:bg-opacity-10']" :aria-current="item.current ? 'page' : undefined">{{ item.name }}</a>
                                </nav>
                            </div>
                            <div class="px-12 lg:px-0">
                                <!-- Search -->
                                <div class="mx-auto w-full max-w-xs lg:max-w-md">
                                    <label for="search" class="sr-only">Search</label>
                                    <div class="relative text-white focus-within:text-gray-600">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <input id="search" class="block w-full rounded-md border border-transparent bg-white bg-opacity-20 py-2 pl-10 pr-3 leading-5 text-white placeholder-white focus:border-transparent focus:bg-opacity-100 focus:text-gray-900 focus:placeholder-gray-500 focus:outline-none focus:ring-0 sm:text-sm" placeholder="Search" type="search" name="search" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu button -->
                    <div class="absolute right-0 flex-shrink-0 lg:hidden">
                        <!-- Mobile menu button -->
                        <main-menu />
                    </div>
                </div>
            </div>

            <TransitionRoot as="template" :show="open">
                <div class="lg:hidden">
                    <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                        <PopoverOverlay class="fixed inset-0 z-20 bg-black bg-opacity-25" />
                    </TransitionChild>

                    <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-150 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <PopoverPanel focus class="absolute inset-x-0 top-0 z-30 mx-auto w-full max-w-3xl origin-top transform p-2 transition">
                            <div class="divide-y divide-gray-200 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                                <div class="pt-3 pb-2">
                                    <div class="flex items-center justify-between px-4">
                                        <div>
                                            <img class="h-8 w-auto" src="https://tailwindui.com/img/logos/mark.svg?color=cyan&shade=600" alt="Your Company" />
                                        </div>
                                        <div class="-mr-2">
                                            <PopoverButton class="inline-flex items-center justify-center rounded-md bg-white p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-cyan-500">
                                                <span class="sr-only">Close menu</span>
                                                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                                            </PopoverButton>
                                        </div>
                                    </div>
                                    <div class="mt-3 space-y-1 px-2">
                                        <a v-for="item in navigation" :key="item.name" :href="item.href" class="block rounded-md px-3 py-2 text-base font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-800">{{ item.name }}</a>
                                    </div>
                                </div>
                                <div class="pt-4 pb-2">
                                    <div class="flex items-center px-5">
                                        <div class="flex-shrink-0">
                                            <img class="h-10 w-10 rounded-full" :src="user.imageUrl" alt="" />
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <div class="truncate text-base font-medium text-gray-800">{{ user.name }}</div>
                                            <div class="truncate text-sm font-medium text-gray-500">{{ user.email }}</div>
                                        </div>
                                        <button type="button" class="ml-auto flex-shrink-0 rounded-full bg-white p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                                            <span class="sr-only">View notifications</span>
                                            <BellIcon class="h-6 w-6" aria-hidden="true" />
                                        </button>
                                    </div>
                                    <user-navigation />
                                </div>
                            </div>
                        </PopoverPanel>
                    </TransitionChild>
                </div>
            </TransitionRoot>
        </Popover>
        <main class="-mt-24 pb-8">
            <button type="button" @click="open=true">Open menu</button>
            <slot />
        </main>
        <footer>
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <div class="border-t border-gray-200 py-8 text-center text-sm text-gray-500 sm:text-left"><span class="block sm:inline">&copy; 2021 Your Company, Inc.</span> <span class="block sm:inline">All rights reserved.</span></div>
            </div>
        </footer>
        <slide-over-menu :open="open" @menu-closed="open = false" />
    </div>
</template>

<script setup>
import {
    Popover,
    PopoverButton,
    PopoverOverlay,
    PopoverPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue'
import {
    Bars3Icon,
    BellIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import ProfileDropdown from "../Shared/Navigation/profile-dropdown.vue";
import UserNavigation from "./Navigation/user-navigation.vue";
import MainMenu from "./Navigation/main-menu.vue";
import SlideOverMenu from "./Navigation/slide-over-menu.vue";
import {ref} from "vue";
import ToastList from "../Components/toast-list.vue";

let open = ref(false);



</script>
