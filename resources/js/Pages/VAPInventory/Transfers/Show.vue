<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ArrowRight as ArrowRightIcon, Printer as PrinterIcon, X as XMarkIcon } from '@lucide/vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import Journey from '@/Components/plano/Journey.vue'

/**
 * Transfer dossier (Plano). The quantity was reserved at the source when the
 * transfer was created; the receipt confirms the physical quantity at the
 * destination, and a cancellation returns the reservation to the source.
 */
const props = defineProps({
  transfer: {
    type: Object,
    required: true,
  },
  sourceStock: {
    type: Object,
    default: null,
  },
  destinationStock: {
    type: Object,
    default: null,
  },
  canReceive: {
    type: Boolean,
    default: false,
  },
  canCancel: {
    type: Boolean,
    default: false,
  },
})

const maxDate = new Date().toISOString().split('T')[0]
const isReceiveModalOpen = ref(false)
const isCancelModalOpen = ref(false)

const receiveForm = useForm({
  received_date: maxDate,
  actual_qty: props.transfer.qty,
  notes: '',
})

const cancelForm = useForm({
  notes: '',
})

const code = computed(() => `TRF-${props.transfer.id}`)
const unit = computed(() => props.transfer.item?.unit?.code || 'UN')

const transferStatus = computed(() => {
  if (props.transfer.deleted_at) return 'Cancelada'
  if (props.transfer.received_date) return 'Recebida'
  if (props.transfer.sent_date) return 'Em trânsito'
  return 'Pendente'
})

const statusTone = computed(() => ({
  Cancelada: 'neutral',
  Recebida: 'done',
  'Em trânsito': props.transfer.is_overdue ? 'bad' : 'run',
  Pendente: 'wait',
}[transferStatus.value]))

const daysInTransfer = computed(() => {
  const start = new Date(props.transfer.created_at)
  const end = props.transfer.received_date ? new Date(props.transfer.received_date) : new Date()
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return 0
  return Math.max(Math.ceil((end.getTime() - start.getTime()) / 86400000), 0)
})

const journeySteps = computed(() => [
  {
    label: 'Criada',
    state: 'done',
    title: 'Reserva na origem',
    note: formatDateTime(props.transfer.created_at),
  },
  {
    label: 'Expedida',
    state: props.transfer.sent_date ? 'done' : 'current',
    title: props.transfer.sent_date ? formatDate(props.transfer.sent_date) : 'Por expedir',
    note: props.transfer.source?.name || 'Origem',
  },
  {
    label: 'Recebida',
    state: props.transfer.received_date ? 'done' : (props.transfer.sent_date ? 'current' : 'todo'),
    title: props.transfer.received_date ? formatDate(props.transfer.received_date) : 'Por confirmar',
    note: props.transfer.destination?.name || 'Destino',
  },
])

const scheduleFacts = computed(() => [
  ['Criada em', formatDateTime(props.transfer.created_at)],
  ['Data de envio', formatDate(props.transfer.sent_date) || 'Por expedir'],
  ['Recepção esperada', formatDate(props.transfer.expected_date) || 'Sem prazo definido'],
  ['Atraso', props.transfer.is_overdue ? `${props.transfer.days_overdue || 0} dias` : 'Sem atraso'],
  ['Recepção efectiva', formatDate(props.transfer.received_date) || 'Pendente'],
  ['Tempo em processo', `${daysInTransfer.value} d`],
])

const warehouses = computed(() => [
  {
    key: 'source',
    title: 'Origem',
    note: 'Saldo após a reserva',
    name: props.transfer.source?.name,
    location: props.transfer.source?.location?.name,
    stock: props.sourceStock?.qty_available ?? 0,
  },
  {
    key: 'destination',
    title: 'Destino',
    note: props.transfer.received_date ? 'Saldo após a recepção' : 'Saldo antes da recepção',
    name: props.transfer.destination?.name,
    location: props.transfer.destination?.location?.name,
    stock: props.destinationStock?.qty_available ?? 0,
  },
])

const transactionsUrl = computed(() => route('vap-inventory.reports.stock-movement', {
  item_id: props.transfer.item_id,
  search: `Transfer #${props.transfer.id}`,
}))

const isReceiveFormValid = computed(() => (
  Boolean(receiveForm.received_date)
  && Number(receiveForm.actual_qty) > 0
  && Number(receiveForm.actual_qty) <= Number(props.transfer.qty)
))

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function openReceiveModal() {
  receiveForm.reset()
  receiveForm.clearErrors()
  receiveForm.received_date = maxDate
  receiveForm.actual_qty = props.transfer.qty
  isReceiveModalOpen.value = true
}

function closeReceiveModal() {
  if (receiveForm.processing) return
  isReceiveModalOpen.value = false
  receiveForm.reset()
  receiveForm.clearErrors()
}

function submitReceive() {
  if (!isReceiveFormValid.value) return

  receiveForm.transform((data) => ({
    ...data,
    actual_qty: data.actual_qty,
  })).post(route('vap-inventory.transfers.receive', props.transfer.id), {
    preserveScroll: true,
    onSuccess: closeReceiveModal,
  })
}

function openCancelModal() {
  cancelForm.reset()
  cancelForm.clearErrors()
  isCancelModalOpen.value = true
}

function closeCancelModal() {
  if (cancelForm.processing) return
  isCancelModalOpen.value = false
  cancelForm.reset()
  cancelForm.clearErrors()
}

function submitCancel() {
  if (!cancelForm.notes.trim()) return

  cancelForm.post(route('vap-inventory.transfers.cancel', props.transfer.id), {
    preserveScroll: true,
    onSuccess: closeCancelModal,
  })
}

function printTransfer() {
  window.print()
}
</script>

<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Transferências', url: route('vap-inventory.transfers.index') }, { title: code }]"
      :title="`Transferência ${code}`"
      :lede="`${transfer.item?.name || 'Item sem identificação'} · ${transfer.source?.name || 'Origem não definida'} → ${transfer.destination?.name || 'Destino não definido'}`"
    >
      <template #badges><StatusChip :tone="statusTone">{{ transferStatus }}</StatusChip></template>
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="printTransfer">Imprimir</button>
      </template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <Journey :steps="journeySteps" label="Percurso da transferência" />

        <section class="pl-panel" aria-labelledby="transfer-line-title">
          <div class="pl-panel-head">
            <h2 id="transfer-line-title" class="pl-k">Linha transferida</h2>
            <span class="pl-k pl-faint">{{ transfer.item?.category?.name || 'Sem categoria' }}</span>
          </div>
          <DataTable>
            <thead>
              <tr>
                <th scope="col">Item</th>
                <th scope="col">Código</th>
                <th scope="col" class="text-right">Quantidade</th>
                <th scope="col">Unidade</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ transfer.item?.name || 'N/D' }}</td>
                <td class="pl-num">{{ transfer.item?.internal_code || transfer.item?.code || 'Sem código interno' }}</td>
                <td class="pl-num text-right">{{ transfer.qty }}</td>
                <td class="pl-num">{{ unit }}</td>
              </tr>
            </tbody>
          </DataTable>
        </section>

        <section class="pl-panel" aria-labelledby="transfer-schedule-title">
          <div class="pl-panel-head">
            <h2 id="transfer-schedule-title" class="pl-k">Prazos</h2>
            <span class="pl-k pl-faint">Actualizada {{ formatDateTime(transfer.updated_at) }}</span>
          </div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="[label, value] in scheduleFacts" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd class="pl-num">{{ value }}</dd></div>
          </dl>
          <div v-if="transfer.obs" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Observações</h3>
            <p class="whitespace-pre-line text-sm">{{ transfer.obs }}</p>
          </div>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section v-for="warehouse in warehouses" :key="warehouse.key" class="pl-panel" :aria-labelledby="`transfer-${warehouse.key}-title`">
          <div class="pl-panel-head">
            <h2 :id="`transfer-${warehouse.key}-title`" class="pl-k">{{ warehouse.title }}</h2>
            <span class="pl-k pl-faint">{{ warehouse.note }}</span>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Armazém</dt><dd>{{ warehouse.name || 'N/D' }}</dd></div>
            <div class="pl-fact"><dt>Localização</dt><dd>{{ warehouse.location || 'Sem localização registada' }}</dd></div>
            <div class="pl-fact"><dt>Disponível</dt><dd class="pl-num">{{ warehouse.stock }} {{ unit }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Registo</h2><span class="pl-k pl-faint">{{ code }}</span></div>
          <Link :href="transactionsUrl" class="pl-row"><span>Movimentos de existências</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <button type="button" class="pl-row w-full text-left hover:bg-[var(--pl-layer)]" @click="printTransfer"><span>Imprimir registo</span><PrinterIcon class="h-4 w-4" aria-hidden="true" /></button>
        </section>
      </aside>
    </div>

    <NextStepBar>
      <template v-if="transfer.deleted_at">Transferência cancelada. As existências reservadas regressaram a {{ transfer.source?.name || 'origem' }}.</template>
      <template v-else-if="transfer.received_date">Recebida em {{ formatDate(transfer.received_date) }}. As existências do destino foram actualizadas.</template>
      <template v-else-if="canReceive">Em trânsito para {{ transfer.destination?.name }}. Confirme a quantidade física recebida no destino.</template>
      <template v-else-if="transfer.sent_date">Em trânsito. Aguarda um operador com permissão para confirmar a recepção.</template>
      <template v-else>Aguarda expedição a partir de {{ transfer.source?.name || 'origem' }}. A quantidade continua reservada.</template>
      <template #actions>
        <button v-if="canCancel" type="button" class="ds-button ds-button-quiet" @click="openCancelModal">Cancelar transferência</button>
        <button v-if="canReceive" type="button" class="ds-button ds-button-primary" @click="openReceiveModal">Confirmar recepção</button>
        <Link v-else-if="transfer.received_date" :href="transactionsUrl" class="ds-button ds-button-secondary">Ver movimentos</Link>
      </template>
    </NextStepBar>

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
                      <DialogTitle class="pl-d3">Confirmar recepção física</DialogTitle>
                      <p class="mt-1 text-sm text-[var(--pl-muted)]">{{ code }} · {{ transfer.item?.name }}</p>
                    </div>
                    <button type="button" class="ds-table-action" aria-label="Fechar" @click="closeReceiveModal">
                      <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </div>

                  <div class="grid gap-5 p-5">
                    <dl class="pl-panel pl-facts">
                      <div class="pl-fact"><dt>Origem</dt><dd>{{ transfer.source?.name }}</dd></div>
                      <div class="pl-fact"><dt>Destino</dt><dd>{{ transfer.destination?.name }}</dd></div>
                    </dl>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <DateTimePicker v-model="receiveForm.received_date" type="date" label="Data de recepção" :max="maxDate" :error="receiveForm.errors.received_date" required />
                      <div class="ds-field-group">
                        <label for="show-receive-qty" class="ds-field-label">Quantidade recebida <span class="ds-field-required">*</span></label>
                        <BaseInput id="show-receive-qty" v-model="receiveForm.actual_qty" type="number" min="0.0001" step="0.0001" :max="transfer.qty" class="ds-field" required />
                        <span class="ds-field-hint">Máximo previsto: {{ transfer.qty }}</span>
                        <span v-if="receiveForm.errors.actual_qty" class="ds-field-error" role="alert">{{ receiveForm.errors.actual_qty }}</span>
                      </div>
                    </div>

                    <div class="ds-field-group">
                      <label for="show-receive-notes" class="ds-field-label">Observações da recepção</label>
                      <textarea id="show-receive-notes" v-model="receiveForm.notes" rows="4" class="ds-field" placeholder="Condição da carga, divergências ou evidências"></textarea>
                      <span v-if="receiveForm.errors.notes" class="ds-field-error" role="alert">{{ receiveForm.errors.notes }}</span>
                    </div>
                  </div>

                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-quiet" @click="closeReceiveModal">Voltar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="receiveForm.processing || !isReceiveFormValid">
                      {{ receiveForm.processing ? 'A registar…' : 'Marcar como recebida' }}
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
                    <DialogTitle class="pl-d3">Cancelar {{ code }}</DialogTitle>
                    <p class="mt-1 text-sm leading-6 text-[var(--pl-muted)]">
                      {{ transfer.qty }} {{ unit }} serão devolvidas ao saldo de {{ transfer.source?.name }}.
                    </p>
                  </div>
                  <div class="p-5">
                    <div class="ds-field-group">
                      <label for="show-cancel-notes" class="ds-field-label">Motivo do cancelamento <span class="ds-field-required">*</span></label>
                      <textarea id="show-cancel-notes" v-model="cancelForm.notes" rows="4" class="ds-field" placeholder="Descreva a razão operacional" required></textarea>
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
