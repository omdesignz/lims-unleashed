<template>
  <div :class="commercialDocumentThemeClasses" class="space-y-8 text-[#18241f] dark:text-slate-100">
    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-[#fbfaf6] shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950">
      <div class="bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.22),transparent_34%),linear-gradient(135deg,#fffaf0,#f7f1e6_58%,#143d37_58%,#143d37)] px-6 py-7 dark:bg-[radial-gradient(circle_at_top_left,rgba(199,154,67,0.18),transparent_34%),linear-gradient(135deg,#17231f,#101815_58%,#0b1210_58%,#0b1210)] sm:px-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
          <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-3">
              <span class="inline-flex items-center gap-2 rounded-full border border-[#c79a43]/40 bg-white/85 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] shadow-sm dark:bg-white/10 dark:text-amber-100">
                <DocumentTextIcon class="h-4 w-4 text-[#c79a43]" />
                {{ $t('gestlab.general.labels.vap_proposals.surface.commercial_management') }}
              </span>
              <span v-if="selectedTemplate" class="inline-flex items-center gap-2 rounded-full border border-emerald-300/40 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200">
                {{ $t('gestlab.general.labels.vap_proposals.surface.template_badge', { name: selectedTemplate.name }) }}
              </span>
            </div>
            <h1 class="mt-5 text-3xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white sm:text-5xl">
              {{ $t('gestlab.general.labels.vap_proposals.title') }}
            </h1>
            <p class="mt-4 max-w-3xl text-base font-medium leading-7 text-[#59665f] dark:text-slate-300">
              {{ $t('gestlab.general.labels.vap_proposals.description') }}
              <span class="font-black text-[#143d37] dark:text-emerald-100">{{ $t('gestlab.general.labels.vap_proposals.surface.proposals_count', { count: formatNumber(stats.total) }) }}</span>
            </p>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row">
            <Link
              :href="route('vap-proposals.templates.index')"
              class="inline-flex items-center justify-center gap-2 rounded-[20px] border border-white/45 bg-white/85 px-5 py-3 text-sm font-black text-[#143d37] shadow-[0_18px_42px_-28px_rgba(20,61,55,0.65)] transition hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/10 dark:text-emerald-100 dark:hover:bg-white/15"
            >
              <DocumentDuplicateIcon class="h-5 w-5 text-[#c79a43]" />
              {{ $t('gestlab.general.labels.vap_proposals.surface.templates') }}
            </Link>
            <Link
              :href="route('vap-proposals.create')"
              class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white shadow-[0_18px_42px_-24px_rgba(20,61,55,0.75)] transition hover:bg-[#0f302b] focus:outline-none focus:ring-2 focus:ring-[#c79a43] focus:ring-offset-2 dark:ring-offset-slate-950"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_proposals.create_new') }}
            </Link>
          </div>
        </div>
      </div>

      <div class="grid gap-4 border-t border-[#ded2bb] bg-white/55 px-6 py-5 dark:border-white/10 dark:bg-white/5 sm:grid-cols-2 xl:grid-cols-6 sm:px-8">
        <article
          v-for="stat in statCards"
          :key="stat.key"
          class="rounded-[24px] border border-[#ded2bb] bg-white/85 p-4 shadow-[0_18px_48px_-36px_rgba(20,61,55,0.48)] dark:border-white/10 dark:bg-white/5"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t(stat.labelKey) }}</p>
              <p class="mt-3 text-2xl font-black tracking-[-0.04em] text-[#10221d] dark:text-white">
                {{ stat.currency ? formatCurrency(stat.value) : formatNumber(stat.value) }}
              </p>
            </div>
            <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-[18px]', stat.tone]">
              <component :is="stat.icon" class="h-5 w-5" />
            </span>
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_25rem]">
      <div class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90 sm:p-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.filters.indicators') }}</p>
            <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
              <ChartBarIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
              {{ $t('gestlab.general.labels.vap_proposals.chart.title') }}
            </h2>
          </div>
          <select
            v-model="period"
            class="rounded-[18px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] outline-none transition focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100"
          >
            <option value="7">{{ $t('gestlab.general.labels.vap_proposals.chart.last_7_days') }}</option>
            <option value="30">{{ $t('gestlab.general.labels.vap_proposals.chart.last_30_days') }}</option>
            <option value="90">{{ $t('gestlab.general.labels.vap_proposals.chart.last_90_days') }}</option>
          </select>
        </div>

        <div class="mt-7 h-80 rounded-[26px] border border-[#ded2bb] bg-[#fbfaf6] p-4 dark:border-white/10 dark:bg-white/5">
          <apexchart
            v-if="chartData.length"
            type="area"
            height="100%"
            :options="chartOptions"
            :series="chartData"
          />
          <div v-else class="flex h-full items-center justify-center text-center">
            <p class="max-w-sm text-sm font-semibold text-[#78847c] dark:text-slate-400">
              {{ $t('gestlab.general.labels.vap_proposals.chart.empty') }}
            </p>
          </div>
        </div>
      </div>

      <aside class="rounded-[30px] border border-[#ded2bb] bg-white/90 p-6 shadow-[0_22px_70px_-46px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.filters.search') }}</p>
            <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">{{ $t('gestlab.general.labels.vap_proposals.filters.title') }}</h2>
          </div>
          <FunnelIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
        </div>

        <div class="mt-6 space-y-4">
          <label class="block">
            <span class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.filters.search') }}</span>
            <span class="relative mt-2 block">
              <MagnifyingGlassIcon class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#9aa59d] dark:text-slate-500" />
              <input
                v-model="search"
                type="search"
                :placeholder="$t('gestlab.general.labels.vap_proposals.filters.search_placeholder')"
                class="w-full rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] py-3 pl-12 pr-4 text-sm font-semibold text-[#33413a] outline-none transition placeholder:text-[#9aa59d] focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-slate-100 dark:placeholder:text-slate-500"
                @input="debouncedApplyFilters"
              />
            </span>
          </label>

          <label class="block">
            <span class="text-xs font-black uppercase tracking-[0.18em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.filters.status') }}</span>
            <select
              v-model="statusFilter"
              class="mt-2 w-full rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] outline-none transition focus:border-[#c79a43] focus:ring-2 focus:ring-[#c79a43]/30 dark:border-white/10 dark:bg-white/5 dark:text-emerald-100"
            >
              <option value="all">{{ $t('gestlab.general.labels.vap_proposals.filters.all_statuses') }}</option>
              <option value="PENDING">{{ $t('gestlab.general.labels.vap_proposals.status.pending') }}</option>
              <option value="SENT">{{ $t('gestlab.general.labels.vap_proposals.status.sent') }}</option>
              <option value="VIEWED">{{ $t('gestlab.general.labels.vap_proposals.status.viewed') }}</option>
              <option value="ACCEPTED">{{ $t('gestlab.general.labels.vap_proposals.status.accepted') }}</option>
              <option value="REJECTED">{{ $t('gestlab.general.labels.vap_proposals.status.rejected') }}</option>
              <option value="REVISED">{{ $t('gestlab.general.labels.vap_proposals.status.revised') }}</option>
              <option value="EXPIRED">{{ $t('gestlab.general.labels.vap_proposals.status.expired') }}</option>
            </select>
          </label>

          <div v-if="selectedTemplate" class="rounded-[22px] border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-300/20 dark:bg-emerald-400/10">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-800 dark:text-emerald-200">{{ $t('gestlab.general.labels.vap_proposals.filters.active_template') }}</p>
            <p class="mt-2 text-sm font-black text-emerald-950 dark:text-emerald-100">{{ selectedTemplate.name }}</p>
            <Link :href="route('vap-proposals.index')" class="mt-3 inline-flex text-sm font-black text-emerald-800 underline decoration-emerald-500/50 underline-offset-4 dark:text-emerald-200">
              {{ $t('gestlab.general.labels.vap_proposals.filters.clear_template') }}
            </Link>
          </div>

          <button
            type="button"
            @click="resetFilters"
            class="inline-flex w-full items-center justify-center gap-2 rounded-[20px] border border-[#ded2bb] bg-[#fbfaf6] px-4 py-3 text-sm font-black text-[#143d37] transition hover:border-[#c79a43] hover:bg-[#fff7e5] dark:border-white/10 dark:bg-white/5 dark:text-emerald-100 dark:hover:bg-white/10"
          >
            <ArrowPathIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.filters.clear_search') }}
          </button>
        </div>
      </aside>
    </section>

    <section class="overflow-hidden rounded-[34px] border border-[#ded2bb] bg-white/90 shadow-[0_26px_70px_-44px_rgba(20,61,55,0.5)] dark:border-white/10 dark:bg-slate-950/90">
      <div class="flex flex-col gap-4 border-b border-[#ded2bb] px-6 py-6 dark:border-white/10 lg:flex-row lg:items-end lg:justify-between sm:px-8">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.28em] text-[#c79a43]">{{ $t('gestlab.general.labels.vap_proposals.filters.pipeline') }}</p>
          <h2 class="mt-2 flex items-center gap-2 text-2xl font-black tracking-[-0.03em] text-[#10221d] dark:text-white">
            <ListBulletIcon class="h-6 w-6 text-[#143d37] dark:text-emerald-100" />
            {{ $t('gestlab.general.labels.vap_proposals.list.title') }}
          </h2>
          <p class="mt-1 text-sm font-semibold text-[#78847c] dark:text-slate-400">
            {{ $t('gestlab.general.labels.vap_proposals.filters.proposals_count', { from: proposals.from || 0, to: proposals.to || 0, total: proposals.total || 0 }) }}
          </p>
        </div>
        <Link
          :href="route('vap-proposals.create')"
          class="inline-flex items-center justify-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white transition hover:bg-[#0f302b]"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.create_new') }}
        </Link>
      </div>

      <div v-if="!proposals.data.length" class="px-6 py-16 text-center sm:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-[24px] bg-[#f7f1e6] text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10">
          <DocumentTextIcon class="h-8 w-8" />
        </div>
        <h3 class="mt-5 text-xl font-black text-[#10221d] dark:text-white">{{ $t('gestlab.general.labels.vap_proposals.empty_state.title') }}</h3>
        <p class="mx-auto mt-2 max-w-md text-sm font-medium leading-6 text-[#59665f] dark:text-slate-300">
          {{ $t('gestlab.general.labels.vap_proposals.empty_state.description') }}
        </p>
        <Link
          :href="route('vap-proposals.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-[20px] bg-[#143d37] px-5 py-3 text-sm font-black text-white transition hover:bg-[#0f302b]"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.create_first') }}
        </Link>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-[1040px] w-full">
          <thead>
            <tr class="border-b border-[#ded2bb] bg-[#f7f1e6] text-left dark:border-white/10 dark:bg-white/5">
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.proposal_no') }}</th>
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.customer') }}</th>
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.department') }}</th>
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.total') }}</th>
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.status') }}</th>
              <th class="px-6 py-4 text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.expiry') }}</th>
              <th class="px-6 py-4 text-right text-xs font-black uppercase tracking-[0.24em] text-[#78847c] dark:text-slate-400">{{ $t('gestlab.general.labels.vap_proposals.table.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#ebe1cf] dark:divide-white/10">
            <tr
              v-for="proposal in proposals.data"
              :key="proposal.id"
              class="transition hover:bg-[#fbfaf6] dark:hover:bg-white/5"
            >
              <td class="px-6 py-5">
                <div class="flex items-center gap-4">
                  <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[18px] bg-[#f7f1e6] text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10">
                    <DocumentTextIcon class="h-6 w-6" />
                  </span>
                  <div>
                    <Link :href="route('vap-proposals.show', proposal.id)" class="text-sm font-black text-[#10221d] transition hover:text-[#143d37] dark:text-white dark:hover:text-emerald-100">
                      {{ proposal.proposal_number }}
                    </Link>
                    <p class="mt-1 text-xs font-semibold text-[#78847c] dark:text-slate-400">{{ formatDate(proposal.created_at) }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5">
                <p class="text-sm font-black text-[#10221d] dark:text-white">{{ proposal.customer?.name || '—' }}</p>
                <p class="mt-1 text-xs font-semibold text-[#78847c] dark:text-slate-400">{{ proposal.customer?.code || $t('gestlab.general.labels.vap_proposals.row.no_customer_code') }}</p>
              </td>
              <td class="px-6 py-5">
                <p class="text-sm font-semibold text-[#33413a] dark:text-slate-200">{{ proposal.department?.name || '—' }}</p>
                <p v-if="proposal.template" class="mt-1 text-xs font-semibold text-[#78847c] dark:text-slate-400">{{ proposal.template.name }}</p>
              </td>
              <td class="px-6 py-5">
                <p class="text-sm font-black text-[#143d37] dark:text-emerald-100">{{ formatCurrency(proposal.total) }}</p>
                <p class="mt-1 text-xs font-semibold text-[#78847c] dark:text-slate-400">{{ proposal.items_count }} {{ proposal.items_count === 1 ? $t('gestlab.general.labels.vap_proposals.row.single_item') : $t('gestlab.general.labels.vap_proposals.row.multiple_items') }}</p>
              </td>
              <td class="px-6 py-5">
                <span :class="['inline-flex items-center rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.14em]', statusBadgeClass(proposal.status)]">
                  {{ proposal.status_badge?.text || proposal.status }}
                </span>
              </td>
              <td class="px-6 py-5">
                <p class="text-sm font-semibold text-[#33413a] dark:text-slate-200">{{ formatDate(proposal.expiry_date) }}</p>
                <p :class="['mt-1 text-xs font-black', proposal.days_until_expiry <= 3 ? 'text-red-600 dark:text-red-300' : 'text-[#78847c] dark:text-slate-400']">
                  {{ expiryLabel(proposal.days_until_expiry) }}
                </p>
              </td>
              <td class="px-6 py-5">
                <div class="flex justify-end gap-2">
                  <Link
                    :href="route('vap-proposals.show', proposal.id)"
                    class="rounded-[14px] p-2 text-[#143d37] transition hover:bg-[#f7f1e6] dark:text-emerald-100 dark:hover:bg-white/10"
                    :title="$t('gestlab.general.labels.vap_proposals.row.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    v-if="canRevise(proposal)"
                    :href="route('vap-proposals.edit', proposal.id)"
                    class="rounded-[14px] p-2 text-amber-700 transition hover:bg-amber-50 dark:text-amber-200 dark:hover:bg-amber-400/10"
                    :title="$t('gestlab.general.labels.vap_proposals.row.revise')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </Link>
                  <a
                    v-if="proposal.file_path"
                    :href="route('vap-proposals.download.pdf', proposal.id)"
                    class="rounded-[14px] p-2 text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-200 dark:hover:bg-emerald-400/10"
                    :title="$t('gestlab.general.labels.vap_proposals.row.download_pdf')"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                  </a>
                  <button
                    v-if="canDelete(proposal)"
                    type="button"
                    @click="confirmDelete(proposal)"
                    class="rounded-[14px] p-2 text-red-600 transition hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-400/10"
                    :title="$t('gestlab.general.labels.vap_proposals.row.delete')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="proposals.data.length > 0" class="border-t border-[#ded2bb] px-6 py-5 dark:border-white/10 sm:px-8">
        <Pagination
          :links="proposals.links"
          :from="proposals.from"
          :to="proposals.to"
          :total="proposals.total"
          :current_page="proposals.current_page"
          :last_page="proposals.last_page"
        />
      </div>
    </section>

    <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false" @confirm="deleteProposal">
      <template #title>
        {{ $t('gestlab.general.labels.vap_proposals.delete.title') }}
      </template>
      <template #content>
        <div class="space-y-3 text-sm font-medium text-[#59665f] dark:text-slate-300">
          <p>{{ $t('gestlab.general.labels.vap_proposals.delete.message', { number: selectedProposal?.proposal_number }) }}</p>
          <p class="font-black text-red-600 dark:text-red-300">{{ $t('gestlab.general.labels.vap_proposals.delete.warning') }}</p>
        </div>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  BanknotesIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  ListBulletIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  TrashIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const props = defineProps({
  proposals: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    required: true,
  },
  chartSeries: {
    type: Array,
    default: () => [],
  },
  selectedTemplate: {
    type: Object,
    default: null,
  },
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const period = ref(String(props.filters.period || 30))
const showDeleteModal = ref(false)
const selectedProposal = ref(null)
const isDark = ref(false)

let darkModeObserver = null

const statCards = computed(() => [
  { key: 'total', labelKey: 'gestlab.general.labels.vap_proposals.stats.total', value: props.stats.total, icon: DocumentTextIcon, tone: 'bg-[#f7f1e6] text-[#143d37] dark:bg-white/10 dark:text-emerald-100' },
  { key: 'pending', labelKey: 'gestlab.general.labels.vap_proposals.stats.pending', value: props.stats.pending, icon: ClockIcon, tone: 'bg-amber-50 text-amber-800 dark:bg-amber-400/10 dark:text-amber-200' },
  { key: 'accepted', labelKey: 'gestlab.general.labels.vap_proposals.stats.accepted', value: props.stats.accepted, icon: CheckCircleIcon, tone: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200' },
  { key: 'rejected', labelKey: 'gestlab.general.labels.vap_proposals.stats.rejected', value: props.stats.rejected, icon: XCircleIcon, tone: 'bg-red-50 text-red-800 dark:bg-red-400/10 dark:text-red-200' },
  { key: 'expired', labelKey: 'gestlab.general.labels.vap_proposals.stats.expired', value: props.stats.expired, icon: ExclamationTriangleIcon, tone: 'bg-orange-50 text-orange-800 dark:bg-orange-400/10 dark:text-orange-200' },
  { key: 'total_value', labelKey: 'gestlab.general.labels.vap_proposals.stats.accepted_value', value: props.stats.total_value, icon: BanknotesIcon, currency: true, tone: 'bg-[#143d37] text-white dark:bg-emerald-400/15 dark:text-emerald-100' },
])

const chartData = computed(() => props.chartSeries || [])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 350,
    toolbar: { show: false },
    zoom: { enabled: false },
    foreColor: isDark.value ? '#cbd5e1' : '#59665f',
  },
  colors: ['#143d37', '#c79a43'],
  dataLabels: { enabled: false },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 0.35,
      opacityFrom: isDark.value ? 0.28 : 0.34,
      opacityTo: 0.02,
      stops: [0, 90, 100],
    },
  },
  grid: {
    borderColor: isDark.value ? 'rgba(255,255,255,0.1)' : '#ded2bb',
    strokeDashArray: 5,
  },
  markers: {
    size: 4,
    strokeWidth: 2,
    strokeColors: isDark.value ? '#0f172a' : '#ffffff',
  },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    x: { format: 'dd MMM yyyy' },
  },
  xaxis: {
    type: 'datetime',
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#78847c',
        fontWeight: 700,
      },
    },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: {
        colors: isDark.value ? '#94a3b8' : '#78847c',
        fontWeight: 700,
      },
    },
  },
}))

const applyFilters = () => {
  router.get(route('vap-proposals.index'), {
    search: search.value || undefined,
    status: statusFilter.value,
    template_id: props.selectedTemplate?.id || undefined,
    period: period.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const debouncedApplyFilters = debounce(applyFilters, 400)

watch([statusFilter, period], () => {
  applyFilters()
})

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  darkModeObserver = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
  darkModeObserver?.disconnect()
})

const resetFilters = () => {
  search.value = ''
  statusFilter.value = 'all'
  period.value = '30'
  applyFilters()
}

const formatNumber = (value) => new Intl.NumberFormat('pt-AO').format(Number(value || 0))

const formatDate = (date) => {
  if (!date) {
    return '—'
  }

  return new Intl.DateTimeFormat('pt-AO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date))
}

const expiryLabel = (days) => {
  if (days === null || days === undefined) {
    return trans('gestlab.general.labels.vap_proposals.expiry.undefined')
  }

  if (days < 0) {
    const overdueDays = Math.abs(days)
    return trans('gestlab.general.labels.vap_proposals.expiry.overdue', {
      days: overdueDays,
      unit: overdueDays === 1
        ? trans('gestlab.general.labels.vap_proposals.expiry.one_day')
        : trans('gestlab.general.labels.vap_proposals.expiry.days'),
    })
  }

  return `${days} ${days === 1 ? trans('gestlab.general.labels.vap_proposals.expiry.one_day_left') : trans('gestlab.general.labels.vap_proposals.expiry.days_left')}`
}

const formatCurrency = (amount) => new Intl.NumberFormat('pt-AO', {
  style: 'currency',
  currency: 'AOA',
}).format(Number(amount || 0))

const statusBadgeClass = (status) => {
  const classes = {
    PENDING: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-200 dark:ring-amber-300/20',
    SENT: 'bg-[#f7f1e6] text-[#143d37] ring-1 ring-[#ded2bb] dark:bg-white/10 dark:text-emerald-100 dark:ring-white/10',
    VIEWED: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200 dark:bg-cyan-400/10 dark:text-cyan-200 dark:ring-cyan-300/20',
    ACCEPTED: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-400/10 dark:text-emerald-200 dark:ring-emerald-300/20',
    REJECTED: 'bg-red-50 text-red-800 ring-1 ring-red-200 dark:bg-red-400/10 dark:text-red-200 dark:ring-red-300/20',
    REVISED: 'bg-orange-50 text-orange-800 ring-1 ring-orange-200 dark:bg-orange-400/10 dark:text-orange-200 dark:ring-orange-300/20',
    EXPIRED: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-white/10',
  }

  return classes[status] || classes.PENDING
}

const canRevise = (proposal) => ['PENDING', 'SENT', 'VIEWED', 'REJECTED'].includes(proposal.status)

const canDelete = (proposal) => ['PENDING', 'REJECTED'].includes(proposal.status)

const confirmDelete = (proposal) => {
  selectedProposal.value = proposal
  showDeleteModal.value = true
}

const deleteProposal = () => {
  router.delete(route('vap-proposals.destroy', selectedProposal.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
      selectedProposal.value = null
    },
  })
}
</script>
