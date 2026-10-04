<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { debounce } from 'lodash'
import {
  CircleCheck as CheckCircleIcon,
  Eye as EyeIcon,
  CircleX as XCircleIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StateCells from '@/Components/plano/StateCells.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

/**
 * Transfer queue (Plano). Each transfer reserves stock at the source when it is
 * created and only moves it into the destination when the physical receipt is
 * confirmed; cancelling returns the reserved quantity to the source.
 */
const props = defineProps({
  transfers: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
})

const localFilters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  source_id: props.filters?.source_id || '',
  destination_id: props.filters?.destination_id || '',
})

const selectedTransfer = ref(null)
const isReceiveModalOpen = ref(false)
const isCancelModalOpen = ref(false)

const receiveForm = useForm({
  actual_qty: '',
  received_date: '',
  notes: '',
})

const cancelForm = useForm({
  notes: '',
})

const transferRows = computed(() => props.transfers?.data || [])

const cells = computed(() => [
  { key: '', label: 'Todas', value: props.stats?.total_transfers || 0 },
  { key: 'pending', label: 'Por receber', value: props.stats?.pending_transfers || 0 },
  { key: 'sent', label: 'Em trânsito', value: props.stats?.in_transit || 0 },
  { key: 'received', label: 'Recebidas', value: props.stats?.received || 0 },
])

const lede = computed(() => {
  const pending = Number(props.stats?.pending_transfers || 0)
  const today = `Hoje: ${props.stats?.sent_today || 0} expedidas, ${props.stats?.received_today || 0} recebidas.`

  return pending
    ? `${pending} ${pending === 1 ? 'transferência aguarda' : 'transferências aguardam'} recepção no destino. ${today}`
    : `Nenhuma transferência por receber. ${today}`
})

const hasActiveFilters = computed(() => Boolean(
  localFilters.search || localFilters.status || localFilters.source_id || localFilters.destination_id,
))

const canSubmitReceive = computed(() => (
  Number(receiveForm.actual_qty) > 0
  && Number(receiveForm.actual_qty) <= Number(selectedTransfer.value?.qty || 0)
  && Boolean(receiveForm.received_date)
))

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function statusText(transfer) {
  if (transfer.deleted_at) return 'Cancelada'
  if (transfer.received_date) return 'Recebida'
  if (transfer.sent_date) return 'Em trânsito'
  return 'Pendente'
}

function statusTone(transfer) {
  if (transfer.deleted_at) return 'neutral'
  if (transfer.received_date) return 'done'
  if (transfer.sent_date) return isOverdue(transfer) ? 'bad' : 'run'
  return 'wait'
}

function isOverdue(transfer) {
  if (transfer.received_date || !transfer.expected_date) return false
  const expectedDate = new Date(transfer.expected_date)
  expectedDate.setHours(23, 59, 59, 999)
  return expectedDate < new Date()
}

function canReceiveTransfer(transfer) {
  return !transfer.deleted_at && !transfer.received_date && Boolean(transfer.sent_date)
}

function canCancelTransfer(transfer) {
  return !transfer.deleted_at && !transfer.received_date
}

function clearFilters() {
  Object.assign(localFilters, {
    search: '',
    status: '',
    source_id: '',
    destination_id: '',
  })
}

function openReceiveModal(transfer) {
  selectedTransfer.value = transfer
  receiveForm.clearErrors()
  receiveForm.actual_qty = transfer.qty
  receiveForm.received_date = new Date().toISOString().split('T')[0]
  receiveForm.notes = ''
  isReceiveModalOpen.value = true
}

function closeReceiveModal() {
  if (receiveForm.processing) return
  isReceiveModalOpen.value = false
  selectedTransfer.value = null
  receiveForm.reset()
  receiveForm.clearErrors()
}

function submitReceive() {
  if (!selectedTransfer.value || !canSubmitReceive.value) return

  receiveForm.post(route('vap-inventory.transfers.receive', selectedTransfer.value.id), {
    preserveScroll: true,
    onSuccess: closeReceiveModal,
  })
}

function openCancelModal(transfer) {
  selectedTransfer.value = transfer
  cancelForm.reset()
  cancelForm.clearErrors()
  isCancelModalOpen.value = true
}

function closeCancelModal() {
  if (cancelForm.processing) return
  isCancelModalOpen.value = false
  selectedTransfer.value = null
  cancelForm.reset()
  cancelForm.clearErrors()
}

function submitCancel() {
  if (!selectedTransfer.value || !cancelForm.notes.trim()) return

  cancelForm.post(route('vap-inventory.transfers.cancel', selectedTransfer.value.id), {
    preserveScroll: true,
    onSuccess: closeCancelModal,
  })
}

watch(
  localFilters,
  debounce((filters) => {
    router.get(route('vap-inventory.transfers.index'), {
      search: filters.search || undefined,
      status: filters.status || undefined,
      source_id: filters.source_id || undefined,
      destination_id: filters.destination_id || undefined,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350),
  { deep: true },
)
</script>

<template>
  <div class="pl-page" data-template="queue">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Transferências' }]"
      title="Transferências de existências"
      :lede="lede"
    >
      <template #actions>
        <Link :href="route('vap-inventory.transfers.create')" class="ds-button ds-button-primary">Nova transferência</Link>
      </template>
    </PageHeader>

    <StateCells v-model="localFilters.status" class="mb-10" :items="cells" label="Filtrar transferências por estado" />

    <form class="pl-filter" role="search" @submit.prevent>
      <label for="transfer-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="transfer-search" v-model="localFilters.search" type="search" data-bare class="pl-filter-input" maxlength="255" placeholder="item ou código interno" />
      <div class="w-48">
        <BaseSelect v-model="localFilters.source_id" aria-label="Armazém de origem">
          <option value="">Todas as origens</option>
          <option v-for="warehouse in warehouses" :key="`source-${warehouse.id}`" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-48">
        <BaseSelect v-model="localFilters.destination_id" aria-label="Armazém de destino">
          <option value="">Todos os destinos</option>
          <option v-for="warehouse in warehouses" :key="`destination-${warehouse.id}`" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>

    <section class="pl-panel" aria-label="Transferências registadas">
      <DataTable v-if="transferRows.length">
        <thead>
          <tr>
            <th scope="col">Transferência</th>
            <th scope="col">Item</th>
            <th scope="col">Origem → destino</th>
            <th scope="col" class="text-right">Quantidade</th>
            <th scope="col">Expedição</th>
            <th scope="col">Estado</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transfer in transferRows" :key="transfer.id">
            <td><Link :href="route('vap-inventory.transfers.show', transfer.id)" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">TRF-{{ transfer.id }}</Link></td>
            <td>
              {{ transfer.item?.name || 'Item sem identificação' }}
              <span class="block pl-num text-[12.5px] text-[var(--pl-muted)]">{{ transfer.item?.internal_code || transfer.item?.code || 'Sem código' }}</span>
            </td>
            <td>{{ transfer.source?.name || 'N/D' }} → {{ transfer.destination?.name || 'N/D' }}</td>
            <td class="pl-num text-right">{{ transfer.qty }} {{ transfer.item?.unit?.code || 'UN' }}</td>
            <td>
              <span class="pl-num">{{ formatDate(transfer.sent_date) || 'Por expedir' }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">Prevista: {{ formatDate(transfer.expected_date) || 'sem prazo' }}</span>
            </td>
            <td>
              <StatusChip :tone="statusTone(transfer)">{{ statusText(transfer) }}</StatusChip>
              <span v-if="isOverdue(transfer)" class="block pt-1 text-[12px] text-[var(--pl-bad)]">Prazo excedido</span>
            </td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link :href="route('vap-inventory.transfers.show', transfer.id)" class="ds-table-action" :aria-label="`Abrir transferência ${transfer.id}`">
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
                <button v-if="canReceiveTransfer(transfer)" type="button" class="ds-table-action" :aria-label="`Receber transferência ${transfer.id}`" @click="openReceiveModal(transfer)">
                  <CheckCircleIcon class="h-4 w-4" aria-hidden="true" />
                </button>
                <button v-if="canCancelTransfer(transfer)" type="button" class="ds-table-action ds-table-action-danger" :aria-label="`Cancelar transferência ${transfer.id}`" @click="openCancelModal(transfer)">
                  <XCircleIcon class="h-4 w-4" aria-hidden="true" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ hasActiveFilters ? 'Nenhuma transferência neste filtro' : 'Ainda não há transferências' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ hasActiveFilters ? 'Experimente outro termo, estado ou armazém.' : 'Registe um movimento entre armazéns para reservar existências na origem.' }}</p>
        <Link v-if="!hasActiveFilters" :href="route('vap-inventory.transfers.create')" class="ds-button ds-button-secondary mt-2">Registar transferência</Link>
      </div>
      <Pagination
        v-if="transferRows.length"
        :links="transfers.links"
        :from="transfers.from"
        :to="transfers.to"
        :total="transfers.total"
        :current_page="transfers.current_page"
        :last_page="transfers.last_page"
      />
    </section>

    <TransitionRoot as="template" :show="isReceiveModalOpen">
      <Dialog class="relative z-50" @close="closeReceiveModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0" enter-to="opacity-100 translate-y-0" leave="ease-out duration-150" leave-from="opacity-100 translate-y-0" leave-to="opacity-0 translate-y-4 sm:translate-y-0">
              <DialogPanel class="ds-modal-panel w-full max-w-xl p-0 text-left">
                <form @submit.prevent="submitReceive">
                  <div class="flex items-start justify-between gap-4 border-b border-[var(--pl-line)] p-5">
                    <div>
                      <DialogTitle class="pl-d3">Confirmar recepção</DialogTitle>
                      <p class="mt-1 text-sm text-[var(--pl-muted)]">Registe a quantidade física verificada no destino.</p>
                    </div>
                    <button type="button" class="ds-table-action" aria-label="Fechar" @click="closeReceiveModal">
                      <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </div>

                  <div class="grid gap-5 p-5">
                    <dl class="pl-panel pl-facts">
                      <div class="pl-fact"><dt>Item</dt><dd>{{ selectedTransfer?.item?.name || 'N/D' }}</dd></div>
                      <div class="pl-fact"><dt>Rota</dt><dd>{{ selectedTransfer?.source?.name }} → {{ selectedTransfer?.destination?.name }}</dd></div>
                    </dl>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <div class="ds-field-group">
                        <label for="index-receive-qty" class="ds-field-label">Quantidade recebida <span class="ds-field-required">*</span></label>
                        <BaseInput id="index-receive-qty" v-model="receiveForm.actual_qty" type="number" min="0.0001" step="0.0001" :max="selectedTransfer?.qty" class="ds-field" required />
                        <span class="ds-field-hint">Máximo previsto: {{ selectedTransfer?.qty || 0 }}</span>
                        <span v-if="receiveForm.errors.actual_qty" class="ds-field-error" role="alert">{{ receiveForm.errors.actual_qty }}</span>
                      </div>
                      <DateTimePicker v-model="receiveForm.received_date" type="date" label="Data de recepção" :error="receiveForm.errors.received_date" required />
                    </div>

                    <div class="ds-field-group">
                      <label for="index-receive-notes" class="ds-field-label">Observações</label>
                      <textarea id="index-receive-notes" v-model="receiveForm.notes" rows="3" class="ds-field" placeholder="Condição da carga, divergências ou evidências de recepção"></textarea>
                      <span v-if="receiveForm.errors.notes" class="ds-field-error" role="alert">{{ receiveForm.errors.notes }}</span>
                    </div>
                  </div>

                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-quiet" @click="closeReceiveModal">Voltar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="receiveForm.processing || !canSubmitReceive">
                      {{ receiveForm.processing ? 'A registar…' : 'Confirmar recepção' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <TransitionRoot as="template" :show="isCancelModalOpen">
      <Dialog class="relative z-50" @close="closeCancelModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0" enter-to="opacity-100 translate-y-0" leave="ease-out duration-150" leave-from="opacity-100 translate-y-0" leave-to="opacity-0 translate-y-4 sm:translate-y-0">
              <DialogPanel class="ds-modal-panel w-full max-w-lg p-0 text-left">
                <form @submit.prevent="submitCancel">
                  <div class="border-b border-[var(--pl-line)] p-5">
                    <DialogTitle class="pl-d3">Cancelar transferência</DialogTitle>
                    <p class="mt-1 text-sm leading-6 text-[var(--pl-muted)]">
                      As existências reservadas regressam ao armazém de origem. Registe o motivo para a trilha de auditoria.
                    </p>
                  </div>
                  <div class="p-5">
                    <div class="ds-field-group">
                      <label for="index-cancel-notes" class="ds-field-label">Motivo do cancelamento <span class="ds-field-required">*</span></label>
                      <textarea id="index-cancel-notes" v-model="cancelForm.notes" rows="4" class="ds-field" placeholder="Descreva a razão operacional" required></textarea>
                      <span v-if="cancelForm.errors.notes" class="ds-field-error" role="alert">{{ cancelForm.errors.notes }}</span>
                    </div>
                  </div>
                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-quiet" @click="closeCancelModal">Manter transferência</button>
                    <button type="submit" class="ds-button ds-button-danger" :disabled="cancelForm.processing || !cancelForm.notes.trim()">
                      {{ cancelForm.processing ? 'A cancelar…' : 'Cancelar transferência' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>
