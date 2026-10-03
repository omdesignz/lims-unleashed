<template>
  <div class="pl-page" data-template="queue">
    <PageHeader
      :crumbs="[{ title: 'Comercial' }, { title: 'Propostas' }]"
      :title="selectedTemplate ? `Propostas · ${selectedTemplate.name}` : 'Propostas'"
      :lede="lede"
    >
      <template #actions>
        <Link :href="route('vap-proposals.templates.index')" class="ds-button ds-button-quiet">Modelos</Link>
        <Link :href="route('vap-proposals.create')" class="ds-button ds-button-primary">{{ $t('gestlab.general.labels.vap_proposals.create_new') }}</Link>
      </template>
    </PageHeader>

    <StateCells class="mb-10" :items="cells" :model-value="statusFilter" label="Filtrar estado das propostas" @update:model-value="statusFilter = $event" />

    <form class="pl-filter" role="search" @submit.prevent="applyFilters">
      <label for="proposal-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="proposal-search" v-model="search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="número, cliente ou código do cliente" @input="debouncedApplyFilters" />
      <button v-if="search || statusFilter !== 'all'" class="ds-chip" type="button" @click="resetFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>

    <section class="pl-panel" aria-label="Propostas">
      <DataTable v-if="proposals.data.length">
        <thead>
          <tr>
            <th scope="col">{{ $t('gestlab.general.labels.vap_proposals.table.proposal_no') }}</th>
            <th scope="col">{{ $t('gestlab.general.labels.vap_proposals.table.customer') }}</th>
            <th scope="col">{{ $t('gestlab.general.labels.vap_proposals.table.department') }}</th>
            <th scope="col">{{ $t('gestlab.general.labels.vap_proposals.table.status') }}</th>
            <th scope="col">{{ $t('gestlab.general.labels.vap_proposals.table.expiry') }}</th>
            <th scope="col" class="text-right">{{ $t('gestlab.general.labels.vap_proposals.table.total') }}</th>
            <th scope="col"><span class="sr-only">{{ $t('gestlab.general.labels.vap_proposals.table.actions') }}</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="proposal in proposals.data" :key="proposal.id">
            <td>
              <Link :href="route('vap-proposals.show', proposal.id)" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">{{ proposal.proposal_number }}</Link>
              <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ formatDate(proposal.created_at) }}</span>
            </td>
            <td>
              {{ proposal.customer?.name || '—' }}
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ proposal.customer?.code || $t('gestlab.general.labels.vap_proposals.row.no_customer_code') }}</span>
            </td>
            <td>
              {{ proposal.department?.name || '—' }}
              <span v-if="proposal.template" class="block max-w-48 truncate text-[12.5px] text-[var(--pl-muted)]" :title="proposal.template.name">{{ proposal.template.name }}</span>
            </td>
            <td><StatusChip :tone="statusTone(proposal.status)">{{ proposal.status_badge?.text || proposal.status }}</StatusChip></td>
            <td>
              <span class="pl-num">{{ formatDate(proposal.expiry_date) }}</span>
              <span :class="['block text-[12.5px]', proposal.days_until_expiry !== null && proposal.days_until_expiry <= 3 ? 'text-[var(--pl-bad)]' : 'text-[var(--pl-muted)]']">{{ expiryLabel(proposal.days_until_expiry) }}</span>
            </td>
            <td class="pl-num text-right">
              {{ formatCurrency(proposal.total) }}
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ proposal.items_count }} {{ proposal.items_count === 1 ? $t('gestlab.general.labels.vap_proposals.row.single_item') : $t('gestlab.general.labels.vap_proposals.row.multiple_items') }}</span>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link v-if="canRevise(proposal)" :href="route('vap-proposals.edit', proposal.id)" class="ds-table-action" :aria-label="`${$t('gestlab.general.labels.vap_proposals.row.revise')} ${proposal.proposal_number}`"><PencilSquareIcon class="h-4 w-4" aria-hidden="true" /></Link>
                <a v-if="proposal.has_document" :href="route('vap-proposals.download.pdf', proposal.id)" class="ds-table-action" :aria-label="`${$t('gestlab.general.labels.vap_proposals.row.download_pdf')} ${proposal.proposal_number}`"><ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" /></a>
                <button v-if="canDelete(proposal)" type="button" class="ds-table-action ds-table-action-danger" :disabled="archive.processing.value" :aria-label="`${$t('gestlab.general.labels.vap_proposals.row.delete')} ${proposal.proposal_number}`" @click="confirmDelete(proposal)"><TrashIcon class="h-4 w-4" aria-hidden="true" /></button>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ search || statusFilter !== 'all' ? 'Nenhuma proposta neste filtro' : $t('gestlab.general.labels.vap_proposals.empty_state.title') }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ search || statusFilter !== 'all' ? 'Experimente outro termo ou estado.' : $t('gestlab.general.labels.vap_proposals.empty_state.description') }}</p>
        <Link v-if="!search && statusFilter === 'all'" :href="route('vap-proposals.create')" class="ds-button ds-button-primary mt-2">{{ $t('gestlab.general.labels.vap_proposals.create_first') }}</Link>
      </div>
      <Pagination
        v-if="proposals.data.length"
        :links="proposals.links"
        :from="proposals.from"
        :to="proposals.to"
        :total="proposals.total"
        :current_page="proposals.current_page"
        :last_page="proposals.last_page"
      />
    </section>

    <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
    <ConfirmationModal :show="showDeleteModal" :closeable="!archive.processing.value" @close="showDeleteModal = false">
      <template #title>{{ $t('gestlab.general.labels.vap_proposals.delete.title') }}</template>
      <template #content>
        <div class="grid gap-3 text-sm">
          <p>{{ $t('gestlab.general.labels.vap_proposals.delete.message', { number: selectedProposal?.proposal_number }) }}</p>
          <p class="font-medium text-[var(--pl-bad)]">{{ $t('gestlab.general.labels.vap_proposals.delete.warning') }}</p>
        </div>
      </template>
      <template #footer>
        <button type="button" class="ds-button ds-button-secondary" :disabled="archive.processing.value" @click="showDeleteModal = false">Cancelar</button>
        <button type="button" class="ds-button ds-button-danger" :disabled="archive.processing.value || !selectedProposal" @click="deleteProposal">
          {{ archive.processing.value ? 'A arquivar…' : 'Arquivar proposta' }}
        </button>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Download as ArrowDownTrayIcon, SquarePen as PencilSquareIcon, Trash2 as TrashIcon, X as XMarkIcon } from '@lucide/vue'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n'
import Pagination from '@/Components/pagination.vue'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { useRecordArchive } from '@/Composables/useRecordArchive'

/**
 * The proposal queue of the active laboratory: what waits to be sent, what waits on
 * the customer, and what was accepted.
 */
const props = defineProps({
  proposals: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, required: true },
  selectedTemplate: { type: Object, default: null },
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const showDeleteModal = ref(false)
const selectedProposal = ref(null)
const archive = useRecordArchive({
  destroyUrl: ids => route('vap-proposals.destroy', ids[0]),
  onSuccess: () => {
    showDeleteModal.value = false
    selectedProposal.value = null
  },
})

const cells = computed(() => [
  { key: 'all', label: 'Todas', value: props.stats.total },
  { key: 'PENDING', label: 'Por enviar', value: props.stats.pending },
  { key: 'ACCEPTED', label: 'Aceites', value: props.stats.accepted },
  { key: 'REJECTED', label: 'Rejeitadas', value: props.stats.rejected, tone: props.stats.rejected ? 'bad' : undefined },
  { key: 'EXPIRED', label: 'Expiradas', value: props.stats.expired },
])

const lede = computed(() => `${props.stats.pending || 0} por enviar · ${formatCurrency(props.stats.total_value)} em propostas aceites.`)

const applyFilters = () => {
  router.get(route('vap-proposals.index'), {
    search: search.value || undefined,
    status: statusFilter.value,
    template_id: props.selectedTemplate?.id || undefined,
    period: props.filters.period || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const debouncedApplyFilters = debounce(applyFilters, 400)

watch(statusFilter, () => {
  applyFilters()
})

const resetFilters = () => {
  search.value = ''
  statusFilter.value = 'all'
  applyFilters()
}

const formatDate = (date) => {
  if (!date) {
    return '—'
  }

  return new Intl.DateTimeFormat('pt-AO', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(date))
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

const formatCurrency = (amount) => new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(Number(amount || 0))

const statusTone = (status) => ({
  PENDING: 'neutral', REVISED: 'wait', SENT: 'run', VIEWED: 'run', ACCEPTED: 'ok', REJECTED: 'bad', EXPIRED: 'done',
}[status] ?? 'neutral')

const canRevise = (proposal) => proposal.can_revise === true

const canDelete = (proposal) => proposal.can_archive === true

const confirmDelete = (proposal) => {
  if (archive.processing.value) return
  selectedProposal.value = proposal
  showDeleteModal.value = true
}

const deleteProposal = () => {
  if (!selectedProposal.value || archive.processing.value) return
  showDeleteModal.value = false
  archive.submit('delete', [selectedProposal.value.id])
}
</script>
