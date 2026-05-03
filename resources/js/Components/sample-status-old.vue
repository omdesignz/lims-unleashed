<template>
    <nav aria-label="Progress">
      <ol role="list" class="overflow-hidden">
        <li v-for="(step, stepIdx) in steps" :key="step.name" :class="[stepIdx !== steps.length - 1 ? 'pb-10' : '', 'relative']">
          <template v-if="step.status === 'complete'">
            <div v-if="stepIdx !== steps.length - 1" class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-blue-900" aria-hidden="true" />
            <a :href="step.href" class="group relative flex items-start">
              <span class="flex h-9 items-center">
                <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 group-hover:bg-blue-800">
                  <CheckIcon class="h-5 w-5 text-white" aria-hidden="true" />
                </span>
              </span>
              <span class="ml-4 flex min-w-0 flex-col">
                <span class="text-sm font-medium">{{ step.name }}</span>
                <span class="text-sm text-gray-500">{{ step.description }}</span>
              </span>
            </a>
          </template>
          <template v-else-if="step.status === 'current'">
            <div v-if="stepIdx !== steps.length - 1" class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300" aria-hidden="true" />
            <a :href="step.href" class="group relative flex items-start" aria-current="step">
              <span class="flex h-9 items-center" aria-hidden="true">
                <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-blue-900 bg-white">
                  <span class="h-2.5 w-2.5 rounded-full bg-blue-900" />
                </span>
              </span>
              <span class="ml-4 flex min-w-0 flex-col">
                <span class="text-sm font-medium text-blue-900">{{ step.name }}</span>
                <span class="text-sm text-gray-500">{{ step.description }}</span>
              </span>
            </a>
          </template>
          <template v-else>
            <div v-if="stepIdx !== steps.length - 1" class="absolute left-4 top-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300" aria-hidden="true" />
            <a :href="step.href" class="group relative flex items-start">
              <span class="flex h-9 items-center" aria-hidden="true">
                <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white group-hover:border-gray-400">
                  <span class="h-2.5 w-2.5 rounded-full bg-transparent group-hover:bg-gray-300" />
                </span>
              </span>
              <span class="ml-4 flex min-w-0 flex-col">
                <span class="text-sm font-medium text-gray-500">{{ step.name }}</span>
                <span class="text-sm text-gray-500">{{ step.description }}</span>
              </span>
            </a>
          </template>
        </li>
      </ol>
    </nav>
  </template>
  
  <script setup>
  import { CheckIcon } from '@heroicons/vue/24/solid'

  const props = defineProps({
    steps: {
      type: Array,
      default: () => []
    },
  });
  </script>