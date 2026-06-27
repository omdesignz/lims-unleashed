<template>
    <div class="ds-panel">
      <nav class="mx-auto max-w-7xl p-2 sm:p-3" :aria-label="$t('gestlab.general.navigation.progress')">
        <ol role="list" class="overflow-hidden rounded-[1.35rem] border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] lg:flex">
          <li v-for="(step, stepIdx) in steps" :key="step.id" class="relative overflow-hidden lg:flex-1">
            <div :class="[stepIdx === 0 ? 'rounded-t-[1.35rem] border-b-0 lg:rounded-l-[1.35rem] lg:rounded-tr-none' : '', stepIdx === steps.length - 1 ? 'rounded-b-[1.35rem] border-t-0 lg:rounded-r-[1.35rem] lg:rounded-bl-none' : '', 'overflow-hidden border border-[color:var(--ds-border)] bg-[color:var(--ds-panel)] lg:border-0']">
              <a v-if="step.status === 'complete'" :href="step.href" class="group">
                <span class="absolute left-0 top-0 h-full w-1 bg-transparent group-hover:bg-[rgb(var(--primary-200-rgb))] dark:group-hover:bg-[rgb(var(--primary-500-rgb)/0.35)] lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true" />
                <span :class="[stepIdx !== 0 ? 'lg:pl-9' : '', 'flex items-start px-6 py-5 text-sm font-medium']">
                  <span class="shrink-0">
                    <span class="flex size-10 items-center justify-center rounded-full bg-[rgb(var(--primary-800-rgb))] shadow-sm dark:bg-[rgb(var(--primary-500-rgb))]">
                      <CheckIcon class="size-6 text-white dark:text-[#07110f]" aria-hidden="true" />
                    </span>
                  </span>
                  <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                    <span class="text-sm font-semibold text-[color:var(--ds-text)]">{{ step.name }}</span>
                    <span class="text-sm font-medium text-[color:var(--ds-text-muted)]">{{ step.description }}</span>
                  </span>
                </span>
              </a>
              <a v-else-if="step.status === 'current'" :href="step.href" aria-current="step">
                <span class="absolute left-0 top-0 h-full w-1 bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-400-rgb))] lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true" />
                <span :class="[stepIdx !== 0 ? 'lg:pl-9' : '', 'flex items-start px-6 py-5 text-sm font-medium']">
                  <span class="shrink-0">
                    <span class="flex size-10 items-center justify-center rounded-full border-2 border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-50-rgb))] dark:border-[rgb(var(--primary-300-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)]">
                      <span class="font-semibold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">{{ step.id }}</span>
                    </span>
                  </span>
                  <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                    <span class="text-sm font-semibold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">{{ step.name }}</span>
                    <span class="text-sm font-medium text-[color:var(--ds-text-muted)]">{{ step.description }}</span>
                  </span>
                </span>
              </a>
              <a v-else :href="step.href" class="group">
                <span class="absolute left-0 top-0 h-full w-1 bg-transparent group-hover:bg-[color:var(--ds-border-strong)] lg:bottom-0 lg:top-auto lg:h-1 lg:w-full" aria-hidden="true" />
                <span :class="[stepIdx !== 0 ? 'lg:pl-9' : '', 'flex items-start px-6 py-5 text-sm font-medium']">
                  <span class="shrink-0">
                    <span class="flex size-10 items-center justify-center rounded-full border-2 border-[color:var(--ds-border-strong)] bg-[color:var(--ds-panel-raised)]">
                      <span class="font-semibold text-[color:var(--ds-text-muted)]">{{ step.id }}</span>
                    </span>
                  </span>
                  <span class="ml-4 mt-0.5 flex min-w-0 flex-col">
                    <span class="text-sm font-semibold text-[color:var(--ds-text-muted)]">{{ step.name }}</span>
                    <span class="text-sm font-medium text-[color:var(--ds-text-soft)]">{{ step.description }}</span>
                  </span>
                </span>
              </a>
              <template v-if="stepIdx !== 0">
                <!-- Separator -->
                <div class="absolute inset-0 left-0 top-0 hidden w-3 lg:block" aria-hidden="true">
                  <svg class="size-full text-[color:var(--ds-border-strong)]" viewBox="0 0 12 82" fill="none" preserveAspectRatio="none">
                    <path d="M0.5 0V31L10.5 41L0.5 51V82" stroke="currentcolor" vector-effect="non-scaling-stroke" />
                  </svg>
                </div>
              </template>
            </div>
          </li>
        </ol>
      </nav>
    </div>
  </template>
  
  <script setup>
  import { CheckIcon } from '@heroicons/vue/24/solid'

  defineProps({
    steps: {
      type: Array,
      default: () => []
    },
  });
  </script>
