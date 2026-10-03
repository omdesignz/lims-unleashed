<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <p class="font-mono text-xs font-black uppercase tracking-[0.16em] text-[var(--ds-text-soft)]">
            TRF-{{ transfer.id }} · Registo logístico
          </p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <TruckIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">{{ transfer.item?.name || 'Transferência de existências' }}</h1>
                <span :class="['ds-chip gap-2', statusClass]">
                  <span :class="['h-2 w-2 rounded-full', statusDotClass]"></span>
                  {{ transferStatus }}
                </span>
              </div>
              <p class="mt-1 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                {{ transfer.source?.name || 'Origem não definida' }} → {{ transfer.destination?.name || 'Destino não definido' }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <Link :href="route('vap-inventory.transfers.index')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </Link>
          <button v-if="canReceive" type="button" class="ds-button ds-button-primary" @click="openReceiveModal">
            <CheckCircleIcon class="h-4 w-4" />
            Confirmar recepção
          </button>
        </div>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 text-2xl font-black text-[var(--ds-text)]">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5', card.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <div class="space-y-6">
        <section class="ds-card overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Dossiê da movimentação</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Identificação e rota</h2>
            </div>
            <ArrowsRightLeftIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          </div>

          <dl class="grid sm:grid-cols-2 xl:grid-cols-3">
            <div v-for="field in detailFields" :key="field.label" class="border-b border-[var(--ds-border)] px-5 py-4 sm:border-r last:border-r-0">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="mt-2 text-sm font-black leading-6 text-[var(--ds-text)]">{{ field.value }}</dd>
              <dd v-if="field.detail" class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ field.detail }}</dd>
            </div>
          </dl>

          <div v-if="transfer.obs" class="border-t border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Observações</p>
            <p class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-[var(--ds-text-muted)]">{{ transfer.obs }}</p>
          </div>
        </section>

        <section class="ds-command-surface overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Telemetria da transferência</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Quantidade, prazo e capacidade de execução</h2>
            </div>
            <span class="ds-chip">Actualizado com o registo actual</span>
          </div>

          <div class="grid gap-4 p-4 lg:grid-cols-2">
            <article class="ds-card p-5">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-sm font-black text-[var(--ds-text)]">Fluxo de quantidade</h3>
                  <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Carga movimentada e saldo observado nos dois armazéns.</p>
                </div>
                <span class="text-xl font-black text-[var(--ds-text)]">{{ quantityFlowTotal }}</span>
              </div>
              <div class="mt-4 min-h-72">
                <apexchart type="bar" height="288" :options="quantityFlowChartOptions" :series="quantityFlowChartSeries" />
              </div>
            </article>

            <article class="ds-card p-5">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-sm font-black text-[var(--ds-text)]">Pressão temporal</h3>
                  <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Dias em curso, margem até ao prazo e eventual atraso.</p>
                </div>
                <span class="text-xl font-black text-[var(--ds-text)]">{{ timingPressureTotal }} d</span>
              </div>
              <div class="mt-4 min-h-72">
                <apexchart type="donut" height="288" :options="timingPressureChartOptions" :series="timingPressureChartSeries" />
              </div>
            </article>

            <article class="ds-card p-5 lg:col-span-2">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-sm font-black text-[var(--ds-text)]">Pulso de execução</h3>
                  <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Gap no destino e disponibilidade das acções de recepção ou cancelamento.</p>
                </div>
                <BoltIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
              </div>
              <div class="mt-4 min-h-56">
                <apexchart type="bar" height="224" :options="executionPulseChartOptions" :series="executionPulseChartSeries" />
              </div>
            </article>
          </div>
        </section>
      </div>

      <aside class="space-y-5">
        <section class="ds-card p-5">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Estado do processo</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Linha de custódia</h2>
            </div>
            <ClockIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>

          <ol class="mt-5 space-y-0">
            <li v-for="(step, index) in workflowSteps" :key="step.label" class="relative flex gap-3 pb-7 last:pb-0">
              <span v-if="index < workflowSteps.length - 1" class="absolute left-[0.4375rem] top-4 h-[calc(100%-0.5rem)] w-px bg-[var(--ds-border)]"></span>
              <span :class="['relative mt-1 h-4 w-4 shrink-0 rounded-full border-4 border-[var(--ds-panel-raised)]', step.complete ? 'bg-emerald-600' : step.current ? 'bg-amber-500' : 'bg-[var(--ds-border-strong)]']"></span>
              <div>
                <p class="text-sm font-black text-[var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ step.detail }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="ds-card p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Posição de existências</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Após reserva / recepção</h2>
          <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-start justify-between gap-4 py-3">
              <dt>
                <p class="text-sm font-black text-[var(--ds-text)]">Origem</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ transfer.source?.name || 'N/D' }}</p>
              </dt>
              <dd class="text-lg font-black text-rose-700 dark:text-rose-300">{{ sourceStock?.qty_available ?? 0 }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
              <dt>
                <p class="text-sm font-black text-[var(--ds-text)]">Destino</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ transfer.destination?.name || 'N/D' }}</p>
              </dt>
              <dd class="text-lg font-black text-emerald-700 dark:text-emerald-300">{{ destinationStock?.qty_available ?? 0 }}</dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Acções do registo</p>
          <div class="mt-4 grid gap-2">
            <button v-if="canReceive" type="button" class="ds-button ds-button-primary w-full" @click="openReceiveModal">
              <CheckCircleIcon class="h-4 w-4" />
              Confirmar recepção
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="printTransfer">
              <PrinterIcon class="h-4 w-4" />
              Imprimir registo
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="exportTransfer">
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar PDF
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="viewTransactions">
              <ArrowsRightLeftIcon class="h-4 w-4" />
              Ver transacções
            </button>
            <button v-if="canCancel" type="button" class="ds-button ds-button-danger w-full" @click="openCancelModal">
              <XCircleIcon class="h-4 w-4" />
              Cancelar transferência
            </button>
          </div>
        </section>
      </aside>
    </div>

    <TransitionRoot as="template" :show="isReceiveModalOpen">
      <Dialog class="relative z-50" @close="closeReceiveModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-xl p-0 text-left">
                <form @submit.prevent="submitReceive">
                  <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] p-5">
                    <div>
                      <DialogTitle class="text-lg font-black text-[var(--ds-text)]">Confirmar recepção física</DialogTitle>
                      <p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">TRF-{{ transfer.id }} · {{ transfer.item?.name }}</p>
                    </div>
                    <button type="button" class="ds-table-action" title="Fechar" @click="closeReceiveModal">
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </div>

                  <div class="space-y-5 p-5">
                    <div class="ds-command-toolbar grid gap-3 p-3 sm:grid-cols-2">
                      <div>
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Origem</p>
                        <p class="mt-1 text-sm font-black text-[var(--ds-text)]">{{ transfer.source?.name }}</p>
                      </div>
                      <div>
                        <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Destino</p>
                        <p class="mt-1 text-sm font-black text-[var(--ds-text)]">{{ transfer.destination?.name }}</p>
                      </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <label class="ds-field-group">
                        <span class="ds-field-label">Data de recepção <span class="ds-field-required">*</span></span>
                        <DateTimePicker v-model="receiveForm.received_date" type="date" :max="maxDate" class="ds-field" required />
                        <span v-if="receiveForm.errors.received_date" class="ds-field-error">{{ receiveForm.errors.received_date }}</span>
                      </label>
                      <label class="ds-field-group">
                        <span class="ds-field-label">Quantidade recebida <span class="ds-field-required">*</span></span>
                        <BaseInput v-model="receiveForm.actual_qty" type="number" min="0.0001" step="0.0001" :max="transfer.qty" class="ds-field" required />
                        <span class="ds-field-hint">Máximo previsto: {{ transfer.qty }}</span>
                        <span v-if="receiveForm.errors.actual_qty" class="ds-field-error">{{ receiveForm.errors.actual_qty }}</span>
                      </label>
                    </div>

                    <label class="ds-field-group">
                      <span class="ds-field-label">Observações da recepção</span>
                      <textarea v-model="receiveForm.notes" rows="4" class="ds-field" placeholder="Condição da carga, divergências ou evidências"></textarea>
                      <span v-if="receiveForm.errors.notes" class="ds-field-error">{{ receiveForm.errors.notes }}</span>
                    </label>
                  </div>

                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" @click="closeReceiveModal">Voltar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="receiveForm.processing || !isReceiveFormValid">
                      <CheckCircleIcon class="h-4 w-4" />
                      {{ receiveForm.processing ? 'A registar...' : 'Marcar como recebida' }}
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
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0" />
        </TransitionChild>
        <div class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-lg p-0 text-left">
                <form @submit.prevent="submitCancel">
                  <div class="border-b border-[var(--ds-border)] p-5">
                    <DialogTitle class="text-lg font-black text-[var(--ds-text)]">Cancelar TRF-{{ transfer.id }}</DialogTitle>
                    <p class="mt-1 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                      {{ transfer.qty }} {{ transfer.item?.unit?.code || 'UN' }} serão devolvidas ao saldo de {{ transfer.source?.name }}.
                    </p>
                  </div>
                  <div class="p-5">
                    <label class="ds-field-group">
                      <span class="ds-field-label">Motivo do cancelamento <span class="ds-field-required">*</span></span>
                      <textarea v-model="cancelForm.notes" rows="4" class="ds-field" placeholder="Descreva a razão operacional" required></textarea>
                      <span v-if="cancelForm.errors.notes" class="ds-field-error">{{ cancelForm.errors.notes }}</span>
                    </label>
                  </div>
                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] p-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" @click="closeCancelModal">Manter transferência</button>
                    <button type="submit" class="ds-button ds-button-danger" :disabled="cancelForm.processing || !cancelForm.notes.trim()">
                      <XCircleIcon class="h-4 w-4" />
                      {{ cancelForm.processing ? 'A cancelar...' : 'Cancelar transferência' }}
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

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowsRightLeftIcon,
  BoltIcon,
  CheckCircleIcon,
  ClockIcon,
  CubeIcon,
  MapPinIcon,
  PrinterIcon,
  TruckIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

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
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const maxDate = new Date().toISOString().split('T')[0]
const isDarkMode = ref(false)
const isReceiveModalOpen = ref(false)
const isCancelModalOpen = ref(false)
let themeObserver

const receiveForm = useForm({
  received_date: maxDate,
  actual_qty: props.transfer.qty,
  notes: '',
})

const cancelForm = useForm({
  notes: '',
})

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const transferStatus = computed(() => {
  if (props.transfer.deleted_at) return 'Cancelada'
  if (props.transfer.received_date) return 'Recebida'
  if (props.transfer.sent_date) return 'Em trânsito'
  return 'Pendente'
})

const statusClass = computed(() => ({
  'Cancelada': 'border-rose-300/70 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200',
  'Recebida': 'border-emerald-300/70 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200',
  'Em trânsito': 'border-cyan-300/70 bg-cyan-50 text-cyan-800 dark:border-cyan-800 dark:bg-cyan-950/30 dark:text-cyan-200',
  'Pendente': 'border-amber-300/70 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
}[transferStatus.value]))

const statusDotClass = computed(() => ({
  'Cancelada': 'bg-rose-600',
  'Recebida': 'bg-emerald-600',
  'Em trânsito': 'bg-cyan-600',
  'Pendente': 'bg-amber-500',
}[transferStatus.value]))

const daysInTransfer = computed(() => {
  const start = new Date(props.transfer.created_at)
  const end = props.transfer.received_date ? new Date(props.transfer.received_date) : new Date()
  if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return 0
  return Math.max(Math.ceil((end.getTime() - start.getTime()) / 86400000), 0)
})

const summaryCards = computed(() => [
  {
    label: 'Quantidade',
    value: `${props.transfer.qty} ${props.transfer.item?.unit?.code || 'UN'}`,
    detail: 'Carga prevista no movimento',
    icon: CubeIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Existências na origem',
    value: props.sourceStock?.qty_available ?? 0,
    detail: props.transfer.source?.name || 'Armazém de origem',
    icon: MapPinIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Existências no destino',
    value: props.destinationStock?.qty_available ?? 0,
    detail: props.transfer.destination?.name || 'Armazém de destino',
    icon: MapPinIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Tempo em processo',
    value: `${daysInTransfer.value} d`,
    detail: props.transfer.received_date ? 'Ciclo concluído' : 'Ciclo ainda em curso',
    icon: ClockIcon,
    tone: 'text-amber-600 dark:text-amber-300',
  },
])

const detailFields = computed(() => [
  {
    label: 'Item',
    value: props.transfer.item?.name || 'N/D',
    detail: props.transfer.item?.internal_code || props.transfer.item?.code || 'Sem código interno',
  },
  {
    label: 'Origem',
    value: props.transfer.source?.name || 'N/D',
    detail: props.transfer.source?.location?.name || 'Sem localização registada',
  },
  {
    label: 'Destino',
    value: props.transfer.destination?.name || 'N/D',
    detail: props.transfer.destination?.location?.name || 'Sem localização registada',
  },
  {
    label: 'Data de envio',
    value: formatDate(props.transfer.sent_date) || 'Por expedir',
    detail: `Criada em ${formatDateTime(props.transfer.created_at)}`,
  },
  {
    label: 'Recepção esperada',
    value: formatDate(props.transfer.expected_date) || 'Sem prazo definido',
    detail: props.transfer.is_overdue ? `${props.transfer.days_overdue || 0} dias de atraso` : 'Sem atraso registado',
  },
  {
    label: 'Recepção efectiva',
    value: formatDate(props.transfer.received_date) || 'Pendente',
    detail: `Última actualização: ${formatDateTime(props.transfer.updated_at)}`,
  },
])

const workflowSteps = computed(() => [
  {
    label: 'Transferência criada',
    detail: formatDateTime(props.transfer.created_at),
    complete: true,
    current: false,
  },
  {
    label: 'Existências expedido',
    detail: props.transfer.sent_date ? formatDate(props.transfer.sent_date) : 'Aguardando expedição',
    complete: Boolean(props.transfer.sent_date),
    current: !props.transfer.sent_date,
  },
  {
    label: 'Recepção no destino',
    detail: props.transfer.received_date ? formatDate(props.transfer.received_date) : 'Aguardando confirmação física',
    complete: Boolean(props.transfer.received_date),
    current: Boolean(props.transfer.sent_date && !props.transfer.received_date),
  },
])

const isReceiveFormValid = computed(() => (
  Boolean(receiveForm.received_date)
  && Number(receiveForm.actual_qty) > 0
  && Number(receiveForm.actual_qty) <= Number(props.transfer.qty)
))

const quantityFlowChartSeries = computed(() => [{
  name: 'Quantidade',
  data: props.charts?.quantity_flow?.series || [],
}])

const quantityFlowTotal = computed(() => (
  (props.charts?.quantity_flow?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
))

const timingPressureChartSeries = computed(() => props.charts?.timing_pressure?.series || [])
const timingPressureTotal = computed(() => timingPressureChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0))

const executionPulseChartSeries = computed(() => [{
  name: 'Indicador',
  data: props.charts?.execution_pulse?.series || [],
}])

const quantityFlowChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: { bar: { borderRadius: 4, distributed: true, columnWidth: '48%' } },
  colors: ['#0e7490', '#be123c', '#047857'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.quantity_flow?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: { labels: { formatter: (value) => Number(value || 0).toLocaleString('pt-AO', { maximumFractionDigits: 4 }), style: { colors: chartTextColor.value } } },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const timingPressureChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.timing_pressure?.labels || [],
  colors: ['#0e7490', '#d97706', '#be123c'],
  dataLabels: { enabled: true, formatter: (value) => `${Math.round(value)}%` },
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  stroke: { colors: [isDarkMode.value ? '#020617' : '#ffffff'] },
  tooltip: { theme: chartTooltipTheme.value },
}))

const executionPulseChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: { bar: { borderRadius: 4, distributed: true, columnWidth: '42%' } },
  colors: ['#7c3aed', '#0e7490', '#475569'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.execution_pulse?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: { labels: { formatter: (value) => Number(value || 0).toFixed(0), style: { colors: chartTextColor.value } } },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

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

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
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

function exportTransfer() {
  window.print()
}

function viewTransactions() {
  router.visit(route('vap-inventory.reports.stock-movement', {
    item_id: props.transfer.item_id,
    search: `Transfer #${props.transfer.id}`,
  }))
}

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
