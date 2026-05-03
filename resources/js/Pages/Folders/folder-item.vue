<template>
  <li class="pointer-events-auto col-span-1 flex cursor-pointer rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    <div class="flex w-16 flex-shrink-0 items-center justify-center rounded-l-2xl bg-primary-800 text-sm font-medium text-white transition-colors hover:bg-primary-900">
      <FolderIcon class="h-7 w-7" />
    </div>

    <div class="inline-flex flex-1 rounded-r-2xl bg-white dark:bg-slate-900">
      <Link
        as="button"
        :href="route('modern-folders.show', folder.slug)"
        :title="folder.name"
        class="relative inline-flex flex-1 items-center rounded-r-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition-colors hover:bg-primary-50 hover:text-primary-900 focus:z-10 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 dark:hover:text-white truncate"
      >
        {{ folder.name }}
      </Link>

      <Menu as="div" class="relative -ml-px block">
        <MenuButton class="relative inline-flex items-center rounded-r-2xl bg-transparent px-3 py-2 text-slate-400 transition-colors hover:bg-slate-50 hover:text-slate-600 focus:z-10 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-300">
          <span class="sr-only">Abrir opções</span>
          <ChevronDownIcon class="size-5" aria-hidden="true" />
        </MenuButton>

        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <MenuItems class="absolute right-0 z-10 mt-2 w-44 origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-slate-900/5 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-100/5">
            <div class="py-1">
              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="$emit('rename', folder)"
                  :class="[active ? 'bg-slate-100 text-primary-900 dark:bg-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300', 'inline-flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors']"
                >
                  <PencilIcon class="h-5 w-5" aria-hidden="true" />
                  Renomear
                </button>
              </MenuItem>

              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="$emit('move', folder)"
                  :class="[active ? 'bg-slate-100 text-primary-900 dark:bg-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300', 'inline-flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors']"
                >
                  <ArrowsRightLeftIcon class="h-5 w-5" aria-hidden="true" />
                  Mover
                </button>
              </MenuItem>

              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="$emit('download', folder)"
                  :class="[active ? 'bg-slate-100 text-primary-900 dark:bg-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300', 'inline-flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors']"
                >
                  <CloudArrowDownIcon class="h-5 w-5" aria-hidden="true" />
                  Transferir
                </button>
              </MenuItem>

              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="$emit('share', folder)"
                  :class="[active ? 'bg-slate-100 text-primary-900 dark:bg-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300', 'inline-flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors']"
                >
                  <UserPlusIcon class="h-5 w-5" aria-hidden="true" />
                  Partilhar
                </button>
              </MenuItem>

              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  @click="$emit('delete', folder)"
                  :class="[active ? 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300' : 'text-rose-600 dark:text-rose-400', 'inline-flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-colors']"
                >
                  <TrashIcon class="h-5 w-5" aria-hidden="true" />
                  Eliminar
                </button>
              </MenuItem>
            </div>
          </MenuItems>
        </transition>
      </Menu>
    </div>

    <ul v-if="folder.children > 0" role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
      <folder-item
        v-for="child in folder.children"
        :key="child.id"
        :folder="child"
        @navigate="$emit('navigate', child)"
        @rename="$emit('rename', $event)"
        @delete="$emit('delete', $event)"
        @move="$emit('move', $event)"
        @download="$emit('download', $event)"
        @share="$emit('share', $event)"
      />
    </ul>
  </li>
</template>

<script setup>
import { FolderIcon, TrashIcon, PencilIcon, ArrowsRightLeftIcon, CloudArrowDownIcon, UserPlusIcon } from '@heroicons/vue/24/outline'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

defineProps({
  folder: Object,
})

defineEmits(['navigate', 'rename', 'delete', 'move', 'download', 'share'])
</script>
