<template>

    <div class="min-h-full">
        <ToastList/>
        <Popover as="header" class="bg-[radial-gradient(circle_at_top_left,rgba(217,176,95,0.20),transparent_28rem),linear-gradient(135deg,#143d37,#07110f)] pb-24" v-slot="{ open }">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <div class="relative flex flex-wrap items-center justify-center lg:justify-between">
                    <!-- Logo -->
                    <div class="absolute left-0 flex-shrink-0 py-5 lg:static">
                        <a href="#">
                            <span class="sr-only"></span>
                            <img class="h-10 w-auto" src="../../images/sncqa_logo.svg" alt="" />
                        </a>
                    </div>

                    <!-- Right section on desktop -->
                    <div class="hidden lg:ml-4 lg:flex lg:items-center lg:py-5 lg:pr-0.5">
                        <main-menu />
                        <button type="button" class="flex-shrink-0 rounded-full p-2 text-accent-100 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                            <span class="sr-only">Ver notificações</span>
                            <BellIcon class="h-6 w-6" aria-hidden="true" />
                        </button>

                        <profile-dropdown />
                    </div>

                    <div class="w-full py-5 lg:border-t lg:border-white/15">
                        <div class="lg:grid lg:grid-cols-3 lg:items-center lg:gap-8">
                            <!-- Left nav -->
                            <div class="hidden lg:col-span-2 lg:block">
                                <nav class="flex space-x-4">
                                    <a v-for="item in navigation" :key="item.name" :href="item.href" :class="[item.current ? 'bg-white/15 text-white' : 'text-accent-100 hover:bg-white/10 hover:text-white', 'rounded-full px-3 py-2 text-sm font-bold transition']" :aria-current="item.current ? 'page' : undefined">{{ item.name }}</a>
                                </nav>
                            </div>
                            <div class="px-12 lg:px-0">
                                <!-- Search -->
                                <div class="mx-auto w-full max-w-xs lg:max-w-md">
                                    <label for="search" class="sr-only">Search</label>
                                    <div class="relative text-white focus-within:text-[#15231f]">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <MagnifyingGlassIcon class="h-5 w-5" aria-hidden="true" />
                                        </div>
                                        <input id="search" class="block w-full rounded-full border border-white/15 bg-white/12 py-2 pl-10 pr-3 font-semibold leading-5 text-white placeholder:text-white/70 focus:border-transparent focus:bg-[#fffaf0] focus:text-[#15231f] focus:placeholder:text-slate-500 focus:outline-none focus:ring-0 sm:text-sm" placeholder="Pesquisar" type="search" name="search" />
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
                            <div class="divide-y divide-[#ded3bf] rounded-[1.75rem] border border-[#ded3bf] bg-[#fffaf0] shadow-lg ring-1 ring-[#143d37]/5 dark:divide-[#25443c] dark:border-[#25443c] dark:bg-[#0c1714]">
                                <div class="pt-3 pb-2">
                                    <div class="flex items-center justify-between px-4">
                                        <div>
                                            <img class="h-8 w-auto" src="../../images/sncqa_logo.svg" alt="" />
                                        </div>
                                        <div class="-mr-2">
                                            <PopoverButton class="inline-flex items-center justify-center rounded-xl bg-[#ede5d6] p-2 text-slate-600 hover:bg-[#ded3bf] hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-[#10231f] dark:text-slate-200">
                                                <span class="sr-only">Fechar menu</span>
                                                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                                            </PopoverButton>
                                        </div>
                                    </div>
                                    <div class="mt-3 space-y-1 px-2">
                                        <a v-for="item in navigation" :key="item.name" :href="item.href" class="block rounded-xl px-3 py-2 text-base font-bold text-[#15231f] hover:bg-[#ede5d6] hover:text-primary-800 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]">{{ item.name }}</a>
                                    </div>
                                </div>
                                <div class="pt-4 pb-2">
                                    <div class="flex items-center px-5">
                                        <div class="flex-shrink-0">
                                            <img v-if="user.profile_photo_url" class="h-10 w-10 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                            <span v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-700 text-sm font-extrabold text-white">{{ user.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
                                        </div>
                                        <div class="ml-3 min-w-0 flex-1">
                                            <div class="truncate text-base font-medium text-gray-800">{{ user.name }}</div>
                                            <div class="truncate text-sm font-medium text-gray-500">{{ user.email }}</div>
                                        </div>
                                        <button type="button" class="ml-auto flex-shrink-0 rounded-full bg-[#ede5d6] p-2 text-slate-600 hover:text-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-[#10231f] dark:text-slate-200">
                                            <span class="sr-only">Ver notificações</span>
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
            <button type="button" class="ml-4 rounded-full bg-primary-700 px-4 py-2 text-sm font-bold text-white shadow-[0_14px_35px_rgba(20,61,55,0.22)] transition hover:bg-primary-800" @click="open=true">Abrir menu</button>
            <slot />
        </main>
        <footer>
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8">
                <div class="border-t border-[#ded3bf] py-8 text-center text-sm font-medium text-slate-500 dark:border-[#25443c] dark:text-slate-400 sm:text-left"><span class="block sm:inline">&copy; {{ new Date().getFullYear() }} LIMS Unleashed.</span> <span class="block sm:inline">Plataforma laboratorial e QMS.</span></div>
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
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import ToastList from "../Components/toast-list.vue";

const page = usePage();
const open = ref(false);
const user = computed(() => page.props?.auth?.user ?? {
    name: 'Utilizador',
    email: '',
    profile_photo_url: null,
});



</script>
