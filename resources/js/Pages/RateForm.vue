<template>
  <div class="mx-auto max-w-3xl space-y-8" :class="commercialDocumentThemeClasses">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate overflow-hidden p-6">
        <div class="absolute inset-x-0 top-0 -z-10 h-28 bg-gradient-to-r from-primary-600/15 via-sky-400/10 to-emerald-400/10 dark:from-primary-500/20 dark:via-sky-500/10 dark:to-emerald-500/10"></div>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-700 dark:text-primary-300">
          {{ trans('gestlab.rating.kicker') }}
        </p>
        <h1 class="mt-3 text-2xl font-bold text-slate-950 dark:text-white">
          {{ trans('gestlab.rating.title') }}
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
          {{ trans('gestlab.rating.description') }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
          <div class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
            {{ rateableLabel }}
          </div>
          <div v-if="ratingRequest" class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
            {{ trans(`gestlab.rating.status.${ratingRequest.status}`) }}
          </div>
        </div>
      </div>
    </div>

    <form class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80" @submit.prevent="submit">
      <div v-if="criteria.length === 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
        {{ trans('gestlab.rating.empty_criteria') }}
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="criterion in criteria"
          :key="criterion.id"
          class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
        >
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 class="text-sm font-semibold text-slate-950 dark:text-white">
                {{ criterion.name }}
              </h2>
              <p v-if="criterion.description" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ criterion.description }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-for="score in [1, 2, 3, 4, 5]"
                :key="score"
                type="button"
                :class="[
                  'flex h-10 w-10 items-center justify-center rounded-2xl border text-sm font-semibold transition',
                  Number(form.criteria[criterion.id]) === score
                    ? 'border-primary-500 bg-primary-600 text-white shadow-sm shadow-primary-600/20'
                    : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'
                ]"
                @click="form.criteria[criterion.id] = score"
              >
                {{ score }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <p v-if="form.errors.criteria" class="mt-4 text-sm text-red-600 dark:text-red-300">
        {{ form.errors.criteria }}
      </p>

      <div class="mt-6 space-y-2">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          {{ trans('gestlab.rating.review') }}
        </label>
        <textarea
          v-model="form.review"
          rows="4"
          class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
          :placeholder="trans('gestlab.rating.review_placeholder')"
        />
        <p v-if="form.errors.review" class="text-sm text-red-600 dark:text-red-300">
          {{ form.errors.review }}
        </p>
      </div>

      <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Link
          :href="route(returnRoute)"
          class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          {{ trans('gestlab.rating.cancel') }}
        </Link>
        <button
          type="submit"
          :disabled="form.processing || criteria.length === 0"
          :class="[
            'inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-semibold shadow-sm transition',
            form.processing || criteria.length === 0
              ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
              : 'bg-primary-600 text-white hover:bg-primary-700'
          ]"
        >
          {{ form.processing ? trans('gestlab.rating.saving') : trans('gestlab.rating.submit') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

const props = defineProps({
  criteria: {
    type: Array,
    default: () => [],
  },
  rateableType: {
    type: String,
    required: true,
  },
  rateableId: {
    type: Number,
    default: 0,
  },
  rateableLabel: {
    type: String,
    default: '',
  },
  ratingRequest: {
    type: Object,
    default: null,
  },
  storeRoute: {
    type: String,
    default: 'rating.store',
  },
  returnRoute: {
    type: String,
    default: 'dashboard',
  },
})

const form = useForm({
  criteria: Object.fromEntries(props.criteria.map((criterion) => [criterion.id, null])),
  review: '',
})

function submit() {
  form.post(route(props.storeRoute, {
    rateableType: props.rateableType,
    rateableId: props.rateableId,
  }))
}
</script>
