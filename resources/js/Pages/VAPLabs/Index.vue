<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import Pagination from '@/Components/pagination.vue'
import {
  BeakerIcon,
  BuildingLibraryIcon,
  CheckCircleIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { ref } from 'vue'

const props = defineProps({
  labs: Object,
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const labPendingDeletion = ref(null)

const searchLabs = debounce(() => {
  router.get(route('vap-labs.labs.index'), { search: search.value || undefined }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300)

function resetSearch() {
  search.value = ''
  router.get(route('vap-labs.labs.index'), {}, { preserveState: true, replace: true })
}

function deleteLab() {
  if (!labPendingDeletion.value) {
    return
  }

  router.delete(route('vap-labs.labs.destroy', labPendingDeletion.value.id), {
    preserveScroll: true,
    onFinish: () => {
      labPendingDeletion.value = null
    },
  })
}
</script>

<template>
  <div class="space-y-5">
    <header class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.title') }}</p>
          <h1 class="ds-heading mt-1 text-xl">{{ $t('gestlab.general.labels.vap_labs.title') }}</h1>
          <p class="ds-copy mt-1 max-w-2xl text-sm">{{ $t('gestlab.general.labels.vap_labs.manage_labs_description') }}</p>
        </div>
        <Link :href="route('vap-labs.labs.create')" class="ds-button ds-button-primary shrink-0">
          <PlusIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_labs.buttons.add_lab') }}
        </Link>
      </div>
      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="flex items-center gap-3 px-5 py-4"><BuildingLibraryIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" /><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.stats.total_labs') }}</dt><dd class="mt-1 text-lg font-black text-[var(--ds-text)]">{{ stats.total ?? 0 }}</dd></div></div>
        <div class="flex items-center gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0"><CheckCircleIcon class="h-5 w-5 text-emerald-600" /><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.stats.active_labs') }}</dt><dd class="mt-1 text-lg font-black text-[var(--ds-text)]">{{ stats.active ?? 0 }}</dd></div></div>
        <div class="flex items-center gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0"><UserGroupIcon class="h-5 w-5 text-amber-600" /><div><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.stats.labs_with_supervisor') }}</dt><dd class="mt-1 text-lg font-black text-[var(--ds-text)]">{{ stats.with_supervisor ?? 0 }}</dd></div></div>
      </dl>
    </header>

    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.list_title') }}</p><h2 class="ds-heading mt-1 text-base">{{ labs.total }} {{ $t('gestlab.general.labels.vap_labs.items') }}</h2></div>
        <div class="flex w-full gap-2 sm:w-auto">
          <div class="relative min-w-0 flex-1 sm:w-72"><MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" /><input v-model="search" type="search" class="ds-field min-h-10 py-2 pl-9" :placeholder="$t('gestlab.general.labels.vap_labs.search.placeholder')" @input="searchLabs" /></div>
          <button v-if="search" type="button" class="ds-button ds-button-secondary" @click="resetSearch">{{ $t('gestlab.general.labels.vap_labs.buttons.reset') }}</button>
        </div>
      </header>

      <div v-if="labs.data.length" class="overflow-x-auto">
        <table class="min-w-full">
          <thead class="ds-table-head"><tr><th class="ds-table-heading">{{ $t('gestlab.general.labels.vap_labs.table.name') }}</th><th class="ds-table-heading">{{ $t('gestlab.general.labels.vap_labs.table.code') }}</th><th class="ds-table-heading">{{ $t('gestlab.general.labels.vap_labs.table.location') }}</th><th class="ds-table-heading">{{ $t('gestlab.general.labels.vap_labs.table.supervisor') }}</th><th class="ds-table-heading"><span class="sr-only">{{ $t('gestlab.general.labels.vap_labs.table.actions') }}</span></th></tr></thead>
          <tbody class="ds-table-body">
            <tr v-for="lab in labs.data" :key="lab.id" class="ds-table-row">
              <td class="ds-table-cell"><div class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]"><BeakerIcon class="h-4 w-4" /></span><div><p class="text-sm font-bold text-[var(--ds-text)]">{{ lab.name }}</p><p class="text-xs font-semibold text-[var(--ds-text-muted)]">{{ lab.department?.name || $t('gestlab.general.labels.vap_labs.no_department') }}</p></div></div></td>
              <td class="ds-table-cell"><span class="ds-badge bg-cyan-50 font-mono text-cyan-800 ring-1 ring-inset ring-cyan-600/20 dark:bg-cyan-500/10 dark:text-cyan-200">{{ lab.code }}</span></td>
              <td class="ds-table-cell text-sm font-semibold text-[var(--ds-text-muted)]">{{ lab.room_no || $t('gestlab.general.labels.vap_labs.not_specified') }}</td>
              <td class="ds-table-cell text-sm font-semibold text-[var(--ds-text-muted)]">{{ lab.supervisor?.name || $t('gestlab.general.labels.vap_labs.not_assigned') }}</td>
              <td class="ds-table-cell"><div class="flex justify-end gap-1"><Link :href="route('vap-labs.labs.show', lab.id)" class="ds-icon-button" :title="$t('gestlab.general.labels.vap_labs.buttons.view')"><EyeIcon class="h-4 w-4" /></Link><Link :href="route('vap-labs.labs.edit', lab.id)" class="ds-icon-button" :title="$t('gestlab.general.labels.vap_labs.buttons.edit')"><PencilSquareIcon class="h-4 w-4" /></Link><button type="button" class="ds-icon-button hover:!text-red-600" :title="$t('gestlab.general.labels.vap_labs.buttons.delete')" @click="labPendingDeletion = lab"><TrashIcon class="h-4 w-4" /></button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="px-5 py-14 text-center"><BuildingLibraryIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" /><h3 class="ds-heading mt-3 text-sm">{{ $t('gestlab.general.labels.vap_labs.messages.empty_labs.title') }}</h3><p class="ds-copy mt-1 text-sm">{{ $t('gestlab.general.labels.vap_labs.messages.empty_labs.description') }}</p></div>
      <Pagination v-if="labs.data.length" v-bind="labs" />
    </section>

    <ConfirmDialog v-if="labPendingDeletion" :title="$t('gestlab.general.labels.vap_labs.modal.delete_lab.title')" :description="$t('gestlab.general.labels.vap_labs.modal.delete_lab.message')" :confirm="$t('gestlab.general.labels.vap_labs.buttons.delete')" :cancel="$t('gestlab.general.labels.vap_labs.buttons.cancel')" @canceled="labPendingDeletion = null" @confirmed="deleteLab" />
  </div>
</template>
