<template>
    <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-gray-50">
      <body class="h-full overflow-hidden">
      ```
    -->
    <div class="flex h-full">
  
      <!-- Content area -->
      <div class="flex flex-1 flex-col overflow-hidden">
        <header class="w-full">

          <!-- <nav class="flex border-b border-gray-200 bg-white pb-2" aria-label="Breadcrumb">
              <ol role="list" class="mx-auto flex w-full max-w-screen-xl space-x-4 px-4 sm:px-6 lg:px-8">
                <li class="flex">
                  <div class="flex items-center">
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                      <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
                      <span class="sr-only">Home</span>
                    </a>
                  </div>
                </li>
                <li v-for="page in pages" :key="page.name" class="flex">
                  <div class="flex items-center">
                    <svg class="h-full w-6 flex-shrink-0 text-gray-200" viewBox="0 0 24 44" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                      <path d="M.293 0l22 22-22 22h1.414l22-22-22-22H.293z" />
                    </svg>
                    <a :href="page.href" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700" :aria-current="page.current ? 'page' : undefined">{{ page.name }}</a>
                  </div>
                </li>
              </ol>
            </nav> -->

          <breadcrumbs :pages="props.breadcrumbs" />
            
          <div class="relative z-10 flex h-16 flex-shrink-0 border-b border-gray-200 bg-white shadow-sm">

            <div class="flex flex-1 justify-between px-4 sm:px-6">
              <div class="flex flex-1">
                <form class="flex w-full md:ml-0" action="#" method="GET">
                  <label for="desktop-search-field" class="sr-only">Search all files</label>
                  <label for="mobile-search-field" class="sr-only">Search all files</label>
                  <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                      <MagnifyingGlassIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
                    </div>
                    <input name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-0 py-2 pl-8 pr-3 text-base text-gray-900 focus:outline-none focus:ring-0 focus:placeholder:text-gray-400 sm:hidden" placeholder="Search" type="search" />
                    <input name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-0 py-2 pl-8 pr-3 text-sm text-gray-900 focus:outline-none focus:ring-0 focus:placeholder:text-gray-400 sm:block" placeholder="Search all files" type="search" />
                  </div>
                </form>
              </div>
              <div class="ml-2 flex items-center space-x-4 sm:ml-6 sm:space-x-6">
                <Menu as="div" class="relative flex-shrink-0">
                  <div>
                    <MenuButton class="relative flex rounded-full bg-blue-900 p-1.5 text-white text-sm ffocus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
                      <span class="absolute -inset-1.5" />
                      <span class="sr-only">Open file menu</span>
                      <PlusIcon class="h-5 w-5" aria-hidden="true" />
                    </MenuButton>
                  </div>
                  <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                    <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                      <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                        <a :href="item.href" :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">{{ item.name }}</a>
                      </MenuItem>
                    </MenuItems>
                  </transition>
                </Menu>
              </div>
            </div>
          </div>
        </header>
  
        <!-- Main content -->
        <div class="flex flex-1 items-stretch overflow-hidden">
          <main class="flex-1 overflow-y-auto">
            <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
              <div class="flex">
                <h1 class="flex-1 text-2xl font-bold text-gray-900">{{ props.breadcrumbs.length }}</h1>
                <div class="ml-6 flex items-center rounded-lg bg-gray-100 p-0.5 sm:hidden">
                  <button type="button" class="rounded-md p-1.5 text-gray-400 hover:bg-white hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-900">
                    <Bars4Icon class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Use list view</span>
                  </button>
                  <button type="button" class="ml-0.5 rounded-md bg-white p-1.5 text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-900">
                    <Squares2X2IconMini class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Use grid view</span>
                  </button>
                </div>
              </div>
  
              <!-- Tabs -->
              <div class="mt-3 sm:mt-2">
                <div class="sm:hidden">
                  <label for="tabs" class="sr-only">Select a tab</label>
                  <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
                  <select id="tabs" name="tabs" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option selected="">Recently Viewed</option>
                    <option>Recently Added</option>
                    <option>Favorited</option>
                  </select>
                </div>
                <div class="hidden sm:block">
                  <div class="flex items-center border-b border-gray-200 justify-end">
                    <!-- <nav class="-mb-px flex flex-1 space-x-6 xl:space-x-8" aria-label="Tabs">
                      <a v-for="tab in tabs" :key="tab.name" :href="tab.href" :aria-current="tab.current ? 'page' : undefined" :class="[tab.current ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium']">{{ tab.name }}</a>
                    </nav> -->
                    <div class="ml-6 hidden items-center rounded-lg bg-gray-100 p-0.5 sm:flex">
                      <button type="button" class="rounded-md p-1.5 text-white hover:bg-blue-900 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <Bars4Icon class="h-5 w-5" aria-hidden="true" />
                        <span class="sr-only">Use list view</span>
                      </button>
                      <button type="button" class="ml-0.5 rounded-md bg-blue-900 p-1.5 text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                        <Squares2X2IconMini class="h-5 w-5" aria-hidden="true" />
                        <span class="sr-only">Use grid view</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
  
              <!-- Gallery -->
              <section class="mt-8 pb-16" aria-labelledby="gallery-heading">
                <h2 id="gallery-heading" class="sr-only">Recently viewed</h2>
                <!-- <ul role="list" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 xl:gap-x-8">
                  <li v-for="file in files" :key="file.name" class="relative">
                    <div :class="[file.current ? 'ring-2 ring-indigo-500 ring-offset-2' : 'focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100', 'group aspect-h-7 aspect-w-10 block w-full overflow-hidden rounded-lg bg-gray-100']">
                      <img :src="file.source" alt="" :class="[file.current ? '' : 'group-hover:opacity-75', 'pointer-events-none object-cover']" />
                      <button type="button" class="absolute inset-0 focus:outline-none">
                        <span class="sr-only">View details for {{ file.name }}</span>
                      </button>
                    </div>
                    <p class="pointer-events-none mt-2 block truncate text-sm font-medium text-gray-900">{{ file.name }}</p>
                    <p class="pointer-events-none block text-sm font-medium text-gray-500">{{ file.size }}</p>
                  </li>
                </ul> -->

                <!-- <ul role="list" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">
                  <li v-for="(folder, index) in props.folders" :key="index" class="relative">
                    <div class="group aspect-h-7 aspect-w-10 block w-full overflow-hidden rounded-lg bg-gray-100 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                      <img :src="file.source" alt="" class="pointer-events-none object-cover group-hover:opacity-75" />
                      <button type="button" class="absolute inset-0 focus:outline-none">
                        <span class="sr-only">View details for {{ file.title }}</span>
                      </button>
                      <FolderIcon class="h-7 w-7" />
                    </div>
                    <p class="pointer-events-none mt-2 block truncate text-sm font-medium text-gray-900">{{ folder.name }}</p>
                    <p class="pointer-events-none block text-sm font-medium text-gray-500">{{ file.size }}</p>
                  </li>
                </ul> -->

                <ul role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                  <folderItem v-for="(folder, index) in props.folders" :key="index" :folder="folder" @navigate="navigateToFolder" />
                  <!-- <li v-for="(folder, index) in props.folders" :key="index" class="col-span-1 flex rounded-md shadow-sm text-gray-900 pointer-events-auto cursor-pointer">
                    <div class="flex w-16 flex-shrink-0 items-center justify-center rounded-l-md text-sm font-medium bg-gray-200 text-gray-500 hover:bg-gray-300 hover:text-gray-700">
                      <FolderIcon class="h-7 w-7" />
                    </div>
                    <div class="flex flex-1 items-center justify-between truncate rounded-r-md border-b border-r border-t border-gray-200 bg-white">
                      <div class="flex-1 truncate px-4 py-2 text-sm">
                        <a :href="route('modern-folders.show', folder.slug)" class="font-medium text-gray-900 hover:text-gray-600">{{ folder.name }}</a>
                      </div>
                      <div class="flex-shrink-0 pr-2">
                        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                          <span class="sr-only">Open options</span>
                          <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </li> -->
                </ul>

              </section>
            </div>
          </main>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import {
    Dialog,
    DialogPanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot,
  } from '@headlessui/vue'
  import {
    Bars3BottomLeftIcon,
    CogIcon,
    HeartIcon,
    HomeIcon,
    PhotoIcon,
    RectangleStackIcon,
    Squares2X2Icon as Squares2X2IconOutline,
    UserGroupIcon,
    FolderIcon,
    XMarkIcon,
    EllipsisVerticalIcon,
  } from '@heroicons/vue/24/outline'
  import {
    Bars4Icon,
    MagnifyingGlassIcon,
    PencilIcon,
    PlusIcon,
    Squares2X2Icon as Squares2X2IconMini,
  } from '@heroicons/vue/20/solid'

import breadcrumbs from '@/Components/breadcrumbs.vue';
import folderItem from '@/Pages/Folders/folder-item.vue';

  
  const navigation = [
    { name: 'Home', href: '#', icon: HomeIcon, current: false },
    { name: 'All Files', href: '#', icon: Squares2X2IconOutline, current: false },
    { name: 'Photos', href: '#', icon: PhotoIcon, current: true },
    { name: 'Shared', href: '#', icon: UserGroupIcon, current: false },
    { name: 'Albums', href: '#', icon: RectangleStackIcon, current: false },
    { name: 'Settings', href: '#', icon: CogIcon, current: false },
  ]
  const userNavigation = [
    { name: 'Your profile', href: '#' },
    { name: 'Sign out', href: '#' },
  ]
  const tabs = [
    { name: 'Recently Viewed', href: '#', current: true },
    { name: 'Recently Added', href: '#', current: false },
    { name: 'Favorited', href: '#', current: false },
  ]
 
  const navigateToFolder = (folder) => {
    router.get(route('modern-folders.show', folder.slug), {}, {
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        console.log('reloaded successfully');
      },
    });
  }

  const props = defineProps({
    breadcrumbs: {
      type: Array,
      default: () => [],
    },
    folders: {
      type: Array,
      default: () => [],
    },
  });
  
  const files = [
  {
    title: 'IMG_4985.HEIC',
    size: '3.9 MB',
    source:
      'https://images.unsplash.com/photo-1582053433976-25c00369fc93?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=512&q=80',
  },
  // More files...
]
  </script>