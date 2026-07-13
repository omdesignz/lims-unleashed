<template>
  <div v-if="!hasChildren">
    <Link
      :href="props.item.href"
      :class="[
        props.item.current ? 'ds-nav-item-active' : 'text-slate-300 hover:bg-white/5 hover:text-white',
        'ds-nav-item group w-full',
      ]"
    >
      <component
        :is="props.item.icon"
        :class="[
          props.item.current ? 'text-cyan-100' : 'text-slate-500 group-hover:text-cyan-200',
          'mr-3 h-5 w-5 shrink-0',
        ]"
        aria-hidden="true"
      />
      <span>{{ props.item.label }}</span>
    </Link>
  </div>

  <Disclosure v-else as="div" class="space-y-1" v-slot="{ open }" :default-open="hasActiveChild">
    <DisclosureButton
      :class="[
        props.item.current ? 'ds-nav-item-active' : 'text-slate-300 hover:bg-white/5 hover:text-white',
        'ds-nav-item group w-full',
      ]"
    >
      <component
        v-if="props.item.icon"
        :is="props.item.icon"
        class="mr-3 h-5 w-5 shrink-0 text-slate-500 group-hover:text-cyan-200"
        aria-hidden="true"
      />
      <span class="min-w-0 flex-1 truncate text-left">{{ props.item.label }}</span>
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="1.5"
        stroke="currentColor"
        :class="[
          open ? 'rotate-90 text-cyan-200' : 'text-slate-500',
          'ml-2 h-4 w-4 shrink-0 transform transition',
        ]"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
    </DisclosureButton>

    <DisclosurePanel class="ml-3 space-y-1 border-l border-white/10 pl-3">
      <NavItem
        v-for="child in props.item.children"
        :key="child.href || child.label"
        :item="child"
      />
    </DisclosurePanel>
  </Disclosure>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

const props = defineProps({
  item: Object,
})

const hasChildren = computed(() => Boolean(props.item?.children?.length))

const hasActiveChild = computed(() => {
  function hasActiveItem(items = []) {
    return items.some((item) => item.current || hasActiveItem(item.children))
  }

  return hasActiveItem(props.item?.children)
})
</script>
