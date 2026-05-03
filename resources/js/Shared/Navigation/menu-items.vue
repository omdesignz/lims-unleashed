<template>
    <div class="flex flex-grow flex-col overflow-y-auto bg-white pt-5 pb-4">
        <div class="mt-5 flex flex-grow flex-col">
            <nav class="flex-1 space-y-1 bg-white px-2" aria-label="Sidebar">
                <NavItem
                    :item="item"
                    v-for="item in navItems"
                    :key="item.label"
                />
            </nav>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import {
    CalendarIcon,
    ChartBarIcon,
    FolderIcon,
    HomeIcon,
    InboxIcon,
    UsersIcon,
    ChevronRightIcon,
    PowerIcon
} from '@heroicons/vue/24/outline'
import NavItem from "./nav-item.vue";

const navItems = [
    { label: 'Dashboard', icon: HomeIcon, current: true, children: [], href: route('dashboard') },
    {
        label: 'Team',
        icon: UsersIcon,
        current: false,
        children: [
            { label: 'Overview', icon: null, current: false, children: [], href: '#' },
            { label: 'Members', icon: null, current: false, children: [], href: '#' },
            { label: 'Calendar', icon: null, current: false, children: [], href: '#' },
            { label: 'Settings', icon: null, current: false, children: [], href: '#' },
        ],
    },
    {
        label: 'Projects',
        icon: FolderIcon,
        current: false,
        children: [
            { label: 'Overview', icon: null, current: false, children: [], href: '#' },
            { label: 'Members', icon: null, current: false, children: [], href: '#' },
            { label: 'Calendar', icon: null, current: false, children: [], href: '#' },
            { label: 'Settings', icon: null, current: false, children: [], href: '#' },
        ],
    },
    {
        label: 'Calendar',
        icon: CalendarIcon,
        current: false,
        children: [
            { label: 'Overview', icon: null, current: false, children: [], href: '#' },
            { label: 'Members', icon: null, current: false, children: [], href: '#' },
            { label: 'Calendar', icon: null, current: false, children: [], href: '#' },
            { label: 'Settings', icon: null, current: false, children: [], href: '#' },
        ],
    },
    {
        label: 'Documents',
        icon: InboxIcon,
        current: false,
        children: [
            { label: 'Overview', icon: null, current: false, children: [], href: '#' },
            { label: 'Members', icon: null, current: false, children: [], href: '#' },
            { label: 'Calendar', icon: null, current: false, children: [{ label: 'Birthday Cards', icon: null, current: false, children: [], href: route('dashboard') },], href: '#' },
            { label: 'Settings', icon: null, current: false, children: [], href: '#' },
        ],
    },
    {
        label: 'Reports',
        icon: ChartBarIcon,
        current: false,
        children: [
            { label: 'Overview', icon: null, current: false, children: [], href: '#' },
            { label: 'Members', icon: null, current: false, children: [], href: '#' },
            { label: 'Calendar', icon: null, current: false, children: [], href: '#' },
            { label: 'Settings', icon: null, current: false, children: [], href: '#' },
        ],
    },
    { label: 'Perfil', icon: UsersIcon, current: false, children: [], href: route('profile.show') },
    { label: 'Terminar Sessão', icon: PowerIcon, current: false, children: [], href: route('logout') },
];

const logout = () => {
    router.post(route('logout'));
};

</script>
