<script setup>
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import DataTableShell from '@/Components/tables/DataTableShell.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  BeakerIcon,
  BoltIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  CircleStackIcon,
  ClipboardDocumentIcon,
  CloudArrowDownIcon,
  CloudArrowUpIcon,
  CodeBracketSquareIcon,
  CpuChipIcon,
  ExclamationTriangleIcon,
  KeyIcon,
  LinkIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  PlusIcon,
  QueueListIcon,
  ServerStackIcon,
  ShieldCheckIcon,
  SignalIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  summary: { type: Object, default: () => ({}) },
  connectors: { type: Array, default: () => [] },
  transmissions: { type: Array, default: () => [] },
  deliveries: { type: Array, default: () => [] },
  equipmentOptions: { type: Array, default: () => [] },
  adapterCatalog: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
  canImport: { type: Boolean, default: false },
  revealedToken: { type: String, default: null },
  revealedConnectorUuid: { type: String, default: null },
})

const tabs = [
  { id: 'overview', label: 'Visão operacional', icon: SignalIcon },
  { id: 'connectors', label: 'Conectores', icon: CpuChipIcon, count: () => props.connectors.length },
  { id: 'inbox', label: 'Caixa de entrada', icon: CloudArrowDownIcon, count: () => reviewTransmissions.value.length },
  { id: 'sharing', label: 'Partilha de dados', icon: CloudArrowUpIcon, count: () => deliveryAttention.value },
]
const directionOptions = [
  { value: 'inbound', label: 'Entrada' },
  { value: 'outbound', label: 'Saída' },
  { value: 'bidirectional', label: 'Bidirecional' },
]
const connectorStatusOptions = [
  { value: 'draft', label: 'Rascunho' },
  { value: 'active', label: 'Activo' },
  { value: 'paused', label: 'Pausado' },
  { value: 'error', label: 'Erro' },
]
const activeTab = ref('overview')
const connectorModalOpen = ref(false)
const mappingModalOpen = ref(false)
const mappingTestOpen = ref(false)
const rejectModalOpen = ref(false)
const tokenModalOpen = ref(Boolean(props.revealedToken))
const editingConnectorUuid = ref(null)
const selectedConnectorUuid = ref(props.connectors[0]?.uuid ?? null)
const selectedTransmissionId = ref(props.transmissions[0]?.id ?? null)
const transmissionStatusFilter = ref('actionable')
const connectorSearch = ref('')
const payloadTestText = ref(JSON.stringify({
  message: { id: 'RUN-2026-000184' },
  result: {
    sample_code: '202607/0001',
    parameter_code: 'PH',
    value: '7,21',
    unit: 'pH',
    measured_at: new Date().toISOString(),
  },
}, null, 2))
const payloadTestResult = ref(null)
const payloadTestError = ref('')
const payloadTestRunning = ref(false)
const copiedValue = ref('')

const selectedConnector = computed(() => props.connectors.find((item) => item.uuid === selectedConnectorUuid.value) ?? null)
const selectedTransmission = computed(() => props.transmissions.find((item) => item.id === selectedTransmissionId.value) ?? null)
const reviewTransmissions = computed(() => props.transmissions.filter((item) => ['matched', 'quarantined'].includes(item.status)))
const deliveryAttention = computed(() => props.deliveries.filter((item) => ['retrying', 'failed'].includes(item.status)).length)
const outboundConnectors = computed(() => props.connectors.filter((item) => ['outbound', 'bidirectional'].includes(item.direction)))
const filteredConnectors = computed(() => {
  const query = connectorSearch.value.trim().toLocaleLowerCase()

  if (!query) return props.connectors

  return props.connectors.filter((connector) => [
    connector.name,
    connector.key,
    connector.adapter,
    connector.equipment?.name,
  ].some((value) => String(value ?? '').toLocaleLowerCase().includes(query)))
})
const filteredTransmissions = computed(() => {
  if (transmissionStatusFilter.value === 'all') return props.transmissions
  if (transmissionStatusFilter.value === 'actionable') return reviewTransmissions.value

  return props.transmissions.filter((item) => item.status === transmissionStatusFilter.value)
})
const headlineMetrics = computed(() => [
  {
    label: 'Conectores activos',
    value: props.summary.active_connectors ?? 0,
    detail: `${props.summary.healthy_connectors ?? 0} saudáveis`,
    icon: CpuChipIcon,
    tone: (props.summary.active_connectors ?? 0) > 0 ? 'emerald' : 'slate',
  },
  {
    label: 'Fila de revisão',
    value: props.summary.review_queue ?? 0,
    detail: 'prontos para decisão',
    icon: QueueListIcon,
    tone: (props.summary.review_queue ?? 0) > 0 ? 'amber' : 'emerald',
  },
  {
    label: 'Em quarentena',
    value: props.summary.quarantined ?? 0,
    detail: 'com divergências',
    icon: ExclamationTriangleIcon,
    tone: (props.summary.quarantined ?? 0) > 0 ? 'rose' : 'emerald',
  },
  {
    label: 'Recebidas · 24 h',
    value: props.summary.received_24h ?? 0,
    detail: 'mensagens de equipamento',
    icon: CloudArrowDownIcon,
    tone: 'blue',
  },
  {
    label: 'Entregas em atenção',
    value: props.summary.delivery_failures ?? 0,
    detail: 'falhadas ou em repetição',
    icon: CloudArrowUpIcon,
    tone: (props.summary.delivery_failures ?? 0) > 0 ? 'rose' : 'emerald',
  },
])

const defaultConnectorForm = () => ({
  name: '',
  key: '',
  direction: 'inbound',
  adapter: 'rest_json',
  status: 'draft',
  inventory_item_id: '',
  description: '',
  configuration: {
    endpoint: '',
    timeout_seconds: 10,
    edge_agent_id: '',
  },
  credentials: { bearer_token: '' },
  event_types: ['lims.analysis.validated'],
})
const connectorForm = useForm(defaultConnectorForm())
const equipmentComboboxOptions = computed(() => {
  const options = props.equipmentOptions.map((item) => ({
    value: item.value,
    label: [item.label, item.meta].filter(Boolean).join(' · '),
  }))
  const retainedEquipment = props.connectors.find((connector) => connector.uuid === editingConnectorUuid.value)?.equipment

  if (retainedEquipment?.is_archived && !options.some((item) => String(item.value) === String(retainedEquipment.id))) {
    options.push({
      value: retainedEquipment.id,
      label: [retainedEquipment.name, retainedEquipment.code, retainedEquipment.serial_number, 'Arquivado'].filter(Boolean).join(' · '),
    })
  }

  return options
})
const selectedEquipmentOption = computed({
  get: () => equipmentComboboxOptions.value.find((item) => String(item.value) === String(connectorForm.inventory_item_id)) ?? null,
  set: (item) => { connectorForm.inventory_item_id = item?.value ?? '' },
})
const equipmentLinkUnavailable = computed(() => Boolean(props.connectors.find((connector) => connector.uuid === editingConnectorUuid.value)?.equipment_link_unavailable))
const mappingForm = useForm({
  name: 'Mapeamento de resultados',
  field_paths: {
    external_id: 'message.id',
    sample_code: 'result.sample_code',
    parameter_code: 'result.parameter_code',
    value: 'result.value',
    unit: 'result.unit',
    measured_at: 'result.measured_at',
    instrument_serial: 'instrument.serial',
    operator: 'operator.id',
  },
  transformations: {
    sample_code: ['trim', 'uppercase'],
    parameter_code: ['trim', 'uppercase'],
    value: ['trim', 'decimal_comma'],
  },
  constants: {},
})
const rejectionForm = useForm({ reason: '' })

watch(() => props.revealedToken, (token) => {
  if (token) tokenModalOpen.value = true
})

watch(filteredTransmissions, (items) => {
  if (!items.some((item) => item.id === selectedTransmissionId.value)) {
    selectedTransmissionId.value = items[0]?.id ?? null
  }
})

function openCreateConnector() {
  editingConnectorUuid.value = null
  connectorForm.defaults(defaultConnectorForm())
  connectorForm.reset()
  connectorForm.clearErrors()
  connectorModalOpen.value = true
}

function openEditConnector(connector) {
  editingConnectorUuid.value = connector.uuid
  connectorForm.defaults({
    name: connector.name ?? '',
    key: connector.key ?? '',
    direction: connector.direction ?? 'inbound',
    adapter: connector.adapter ?? 'rest_json',
    status: connector.status ?? 'draft',
    inventory_item_id: connector.equipment?.id ?? '',
    description: connector.description ?? '',
    configuration: {
      endpoint: connector.configuration?.endpoint ?? '',
      timeout_seconds: connector.configuration?.timeout_seconds ?? 10,
      edge_agent_id: connector.configuration?.edge_agent_id ?? '',
    },
    credentials: { bearer_token: '' },
    event_types: connector.event_types?.length ? [...connector.event_types] : [],
  })
  connectorForm.reset()
  connectorForm.clearErrors()
  connectorModalOpen.value = true
}

function submitConnector() {
  connectorForm.transform((data) => {
    if (equipmentLinkUnavailable.value && !data.inventory_item_id) {
      const { inventory_item_id, ...metadata } = data
      return metadata
    }

    return data
  })
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      connectorModalOpen.value = false
      connectorForm.reset()
    },
  }

  if (editingConnectorUuid.value) {
    connectorForm.put(route('integration-hub.connectors.update', editingConnectorUuid.value), options)
    return
  }

  connectorForm.post(route('integration-hub.connectors.store'), options)
}

function openMapping(connector) {
  selectedConnectorUuid.value = connector.uuid
  const mapping = connector.active_mapping
  mappingForm.defaults({
    name: mapping?.name ?? 'Mapeamento de resultados',
    field_paths: {
      external_id: mapping?.field_paths?.external_id ?? 'message.id',
      sample_code: mapping?.field_paths?.sample_code ?? 'result.sample_code',
      parameter_code: mapping?.field_paths?.parameter_code ?? 'result.parameter_code',
      value: mapping?.field_paths?.value ?? 'result.value',
      unit: mapping?.field_paths?.unit ?? 'result.unit',
      measured_at: mapping?.field_paths?.measured_at ?? 'result.measured_at',
      instrument_serial: mapping?.field_paths?.instrument_serial ?? 'instrument.serial',
      operator: mapping?.field_paths?.operator ?? 'operator.id',
    },
    transformations: mapping?.transformations ?? {
      sample_code: ['trim', 'uppercase'],
      parameter_code: ['trim', 'uppercase'],
      value: ['trim', 'decimal_comma'],
    },
    constants: mapping?.constants ?? {},
  })
  mappingForm.reset()
  mappingForm.clearErrors()
  mappingModalOpen.value = true
}

function submitMapping() {
  if (!selectedConnector.value) return

  mappingForm.post(route('integration-hub.mappings.store', selectedConnector.value.uuid), {
    preserveScroll: true,
    onSuccess: () => { mappingModalOpen.value = false },
  })
}

async function runMappingTest() {
  if (!selectedConnector.value) return
  payloadTestError.value = ''
  payloadTestResult.value = null

  let payload
  try {
    payload = JSON.parse(payloadTestText.value)
  } catch {
    payloadTestError.value = 'O payload não contém JSON válido.'
    return
  }

  payloadTestRunning.value = true
  try {
    const response = await window.axios.post(
      route('integration-hub.mappings.test', selectedConnector.value.uuid),
      { payload },
      { headers: { Accept: 'application/json' } },
    )
    payloadTestResult.value = response.data
  } catch (error) {
    payloadTestError.value = error.response?.data?.message ?? 'Não foi possível executar o diagnóstico.'
  } finally {
    payloadTestRunning.value = false
  }
}

function testConnector(connector) {
  router.post(route('integration-hub.connectors.test', connector.uuid), {}, { preserveScroll: true })
}

function rotateToken(connector) {
  router.post(route('integration-hub.connectors.rotate-token', connector.uuid), {}, { preserveScroll: true })
}

function importTransmission(transmission) {
  router.post(route('integration-hub.transmissions.import', transmission.id), {}, { preserveScroll: true })
}

function openRejectTransmission(transmission) {
  selectedTransmissionId.value = transmission.id
  rejectionForm.reset()
  rejectionForm.clearErrors()
  rejectModalOpen.value = true
}

function rejectTransmission() {
  if (!selectedTransmission.value) return

  rejectionForm.post(route('integration-hub.transmissions.reject', selectedTransmission.value.id), {
    preserveScroll: true,
    onSuccess: () => { rejectModalOpen.value = false },
  })
}

function retryDelivery(delivery) {
  router.post(route('integration-hub.deliveries.retry', delivery.id), {}, { preserveScroll: true })
}

async function copyText(value, key) {
  await navigator.clipboard.writeText(value)
  copiedValue.value = key
  window.setTimeout(() => { copiedValue.value = '' }, 1600)
}

function connectorAdapter(connector) {
  return props.adapterCatalog.find((item) => item.value === connector.adapter)
}

function connectorTone(status) {
  return {
    healthy: 'bg-emerald-500',
    ready: 'bg-sky-500',
    warning: 'bg-amber-500',
    error: 'bg-rose-500',
    unknown: 'bg-slate-400',
  }[status] ?? 'bg-slate-400'
}

function statusBadge(status) {
  return {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    healthy: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    ready: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
    matched: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
    imported: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    delivered: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
    draft: 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-white/5 dark:text-slate-300',
    paused: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
    quarantined: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
    retrying: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
    rejected: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
    failed: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
    error: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
    processing: 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-500/10 dark:text-violet-300',
    pending: 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-white/5 dark:text-slate-300',
  }[status] ?? 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-white/5 dark:text-slate-300'
}

function statusLabel(status) {
  return {
    active: 'Activo', draft: 'Rascunho', paused: 'Pausado', error: 'Erro',
    healthy: 'Saudável', ready: 'Pronto', warning: 'Atenção', unknown: 'Sem sinal',
    received: 'Recebida', matched: 'Correspondida', quarantined: 'Quarentena', imported: 'Importada', rejected: 'Rejeitada', failed: 'Falhada',
    pending: 'Pendente', processing: 'A processar', retrying: 'A repetir', delivered: 'Entregue',
  }[status] ?? status
}

function directionLabel(direction) {
  return { inbound: 'Entrada', outbound: 'Saída', bidirectional: 'Bidirecional' }[direction] ?? direction
}

function formatDateTime(value) {
  if (!value) return 'Sem registo'
  return new Intl.DateTimeFormat('pt-AO', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

function relativeTime(value) {
  if (!value) return 'Nunca'
  const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000)
  const formatter = new Intl.RelativeTimeFormat('pt', { numeric: 'auto' })
  if (Math.abs(seconds) < 60) return formatter.format(seconds, 'second')
  const minutes = Math.round(seconds / 60)
  if (Math.abs(minutes) < 60) return formatter.format(minutes, 'minute')
  const hours = Math.round(minutes / 60)
  if (Math.abs(hours) < 24) return formatter.format(hours, 'hour')
  return formatter.format(Math.round(hours / 24), 'day')
}
</script>

<template>
  <Head title="Integration Hub" />

  <div class="space-y-5">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--brand-secondary)] text-white">
                <ArrowsRightLeftIcon class="h-5 w-5" aria-hidden="true" />
              </span>
              <div>
                <p class="ds-kicker">Interoperabilidade laboratorial</p>
                <h1 class="mt-1 text-2xl font-semibold text-[var(--ds-text)]">Integration Hub</h1>
              </div>
            </div>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-[var(--ds-text-muted)]">Equipamentos, mensagens normalizadas e partilha externa sob uma única trilha de auditoria.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex h-9 items-center gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 text-xs font-semibold text-[var(--ds-text-muted)]">
              <span :class="(summary.delivery_failures ?? 0) > 0 ? 'bg-amber-500' : 'bg-emerald-500'" class="h-2 w-2 rounded-full" />
              {{ (summary.delivery_failures ?? 0) > 0 ? 'Operação com alertas' : 'Operação estável' }}
            </span>
            <button v-if="canManage" type="button" class="ds-button ds-button-primary" @click="openCreateConnector">
              <PlusIcon class="h-4 w-4" aria-hidden="true" />
              Novo conector
            </button>
          </div>
        </div>
      </div>

      <dl class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-5">
        <div v-for="metric in headlineMetrics" :key="metric.label" class="min-w-0 px-5 py-4 sm:px-6">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="truncate text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-2xl font-semibold tabular-nums text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 truncate text-xs text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
          </div>
        </div>
      </dl>
    </section>

    <nav class="flex max-w-full gap-1 overflow-x-auto border-b border-[var(--ds-border)]" aria-label="Áreas do Integration Hub">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        :class="activeTab === tab.id ? 'border-[rgb(var(--primary-700-rgb))] text-[var(--ds-text)]' : 'border-transparent text-[var(--ds-text-muted)] hover:text-[var(--ds-text)]'"
        class="flex h-11 shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-semibold transition"
        @click="activeTab = tab.id"
      >
        <component :is="tab.icon" class="h-4 w-4" aria-hidden="true" />
        {{ tab.label }}
        <span v-if="tab.count && tab.count()" class="rounded-full bg-[var(--ds-panel-subtle)] px-2 py-0.5 font-mono text-[0.65rem]">{{ tab.count() }}</span>
      </button>
    </nav>

    <template v-if="activeTab === 'overview'">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)]">
        <section class="ds-panel overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <div>
              <h2 class="text-sm font-semibold text-[var(--ds-text)]">Estado dos conectores</h2>
              <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Telemetria, protocolo e última actividade.</p>
            </div>
            <button type="button" class="ds-icon-button" title="Abrir conectores" @click="activeTab = 'connectors'">
              <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
          <div v-if="connectors.length" class="divide-y divide-[var(--ds-border)]">
            <button
              v-for="connector in connectors.slice(0, 7)"
              :key="connector.uuid"
              type="button"
              class="grid w-full gap-3 px-5 py-4 text-left transition hover:bg-[var(--ds-panel-subtle)] sm:grid-cols-[minmax(0,1fr)_9rem_8rem] sm:items-center"
              @click="selectedConnectorUuid = connector.uuid; activeTab = 'connectors'"
            >
              <span class="flex min-w-0 items-center gap-3">
                <span class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
                  <CpuChipIcon class="h-4 w-4" aria-hidden="true" />
                  <span :class="connectorTone(connector.health_status)" class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-[var(--ds-panel)]" />
                </span>
                <span class="min-w-0">
                  <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ connector.name }}</span>
                  <span class="mt-0.5 block truncate text-xs text-[var(--ds-text-soft)]">{{ connector.equipment?.name || connector.key }}</span>
                </span>
              </span>
              <span class="min-w-0">
                <span class="block truncate text-xs font-semibold text-[var(--ds-text)]">{{ connectorAdapter(connector)?.label || connector.adapter }}</span>
                <span class="mt-0.5 block text-xs text-[var(--ds-text-soft)]">{{ directionLabel(connector.direction) }}</span>
              </span>
              <span class="sm:text-right">
                <span class="block text-xs font-semibold text-[var(--ds-text)]">{{ statusLabel(connector.health_status) }}</span>
                <span class="mt-0.5 block text-xs text-[var(--ds-text-soft)]">{{ relativeTime(connector.last_seen_at) }}</span>
              </span>
            </button>
          </div>
          <div v-else class="px-6 py-14 text-center">
            <CpuChipIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" aria-hidden="true" />
            <p class="mt-3 text-sm font-semibold text-[var(--ds-text)]">Nenhum conector registado</p>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <div>
              <h2 class="text-sm font-semibold text-[var(--ds-text)]">Prioridades de revisão</h2>
              <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Mensagens que aguardam decisão humana.</p>
            </div>
            <span class="font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ reviewTransmissions.length }}</span>
          </div>
          <div v-if="reviewTransmissions.length" class="divide-y divide-[var(--ds-border)]">
            <button
              v-for="transmission in reviewTransmissions.slice(0, 6)"
              :key="transmission.id"
              type="button"
              class="block w-full px-5 py-4 text-left transition hover:bg-[var(--ds-panel-subtle)]"
              @click="selectedTransmissionId = transmission.id; activeTab = 'inbox'"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ transmission.sample_code || 'Amostra não reconhecida' }}</p>
                  <p class="mt-1 truncate text-xs text-[var(--ds-text-muted)]">{{ transmission.parameter_code || 'Parâmetro não reconhecido' }} · {{ transmission.measured_value ?? 'Sem valor' }} {{ transmission.measured_unit }}</p>
                </div>
                <span :class="statusBadge(transmission.status)" class="inline-flex shrink-0 rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(transmission.status) }}</span>
              </div>
              <p class="mt-2 truncate text-xs text-[var(--ds-text-soft)]">{{ transmission.connector?.name }} · {{ relativeTime(transmission.received_at) }}</p>
            </button>
          </div>
          <div v-else class="px-6 py-14 text-center">
            <CheckCircleIcon class="mx-auto h-7 w-7 text-emerald-500" aria-hidden="true" />
            <p class="mt-3 text-sm font-semibold text-[var(--ds-text)]">Fila de revisão limpa</p>
          </div>
        </section>
      </div>

      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <h2 class="text-sm font-semibold text-[var(--ds-text)]">Cobertura de protocolos</h2>
          <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Adaptadores disponíveis no núcleo e no edge relay.</p>
        </div>
        <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4 xl:grid-cols-7">
          <div v-for="adapter in adapterCatalog" :key="adapter.value" class="min-w-0 px-4 py-4">
            <div class="flex items-center gap-2">
              <ServerStackIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" aria-hidden="true" />
              <span class="truncate text-xs font-semibold text-[var(--ds-text)]">{{ adapter.label }}</span>
            </div>
            <p class="mt-2 truncate font-mono text-[0.65rem] uppercase text-[var(--ds-text-soft)]">{{ adapter.transport }}</p>
          </div>
        </div>
      </section>
    </template>

    <template v-else-if="activeTab === 'connectors'">
      <DataTableShell :show-summary="false">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-sm font-semibold text-[var(--ds-text)]">Conectores de equipamento e sistemas</h2>
            <p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ connectors.length }} perfis registados.</p>
          </div>
          <div class="flex items-center gap-2">
            <label class="relative min-w-0 flex-1 sm:w-64">
              <span class="sr-only">Pesquisar conectores</span>
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-[var(--ds-text-soft)]" aria-hidden="true" />
              <BaseInput v-model="connectorSearch" type="search" class="h-9 w-full pl-9" placeholder="Nome, equipamento ou protocolo" />
            </label>
            <button v-if="canManage" type="button" class="ds-icon-button" title="Novo conector" @click="openCreateConnector">
              <PlusIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Conector</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Transporte</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Actividade</th>
                <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-[var(--ds-text-soft)]"><span class="sr-only">Abrir</span></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)]">
              <tr
                v-for="connector in filteredConnectors"
                :key="connector.uuid"
                :class="selectedConnectorUuid === connector.uuid ? 'bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.08)]' : 'hover:bg-[var(--ds-panel-subtle)]'"
                class="cursor-pointer transition"
                @click="selectedConnectorUuid = connector.uuid"
              >
                <td class="px-5 py-4">
                  <div class="flex min-w-56 items-center gap-3">
                    <span class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]">
                      <CpuChipIcon class="h-4 w-4" aria-hidden="true" />
                      <span :class="connectorTone(connector.health_status)" class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-[var(--ds-panel)]" />
                    </span>
                    <span class="min-w-0">
                      <span class="block truncate text-sm font-semibold text-[var(--ds-text)]">{{ connector.name }}</span>
                      <span class="mt-0.5 block truncate font-mono text-[0.68rem] text-[var(--ds-text-soft)]">{{ connector.key }}</span>
                    </span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <p class="whitespace-nowrap text-xs font-semibold text-[var(--ds-text)]">{{ connectorAdapter(connector)?.label || connector.adapter }}</p>
                  <p class="mt-1 whitespace-nowrap text-xs text-[var(--ds-text-soft)]">{{ directionLabel(connector.direction) }}</p>
                </td>
                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-1.5">
                    <span :class="statusBadge(connector.status)" class="inline-flex rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(connector.status) }}</span>
                    <span :class="statusBadge(connector.health_status)" class="inline-flex rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(connector.health_status) }}</span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <p class="whitespace-nowrap text-xs font-semibold text-[var(--ds-text)]">{{ relativeTime(connector.last_seen_at) }}</p>
                  <p class="mt-1 whitespace-nowrap text-xs text-[var(--ds-text-soft)]">{{ connector.transmissions_count }} entrada(s) · {{ connector.deliveries_count }} saída(s)</p>
                </td>
                <td class="px-5 py-4 text-right"><ChevronRightIcon class="ml-auto h-4 w-4 text-[var(--ds-text-soft)]" /></td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </DataTableShell>

      <section v-if="selectedConnector" class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-base font-semibold text-[var(--ds-text)]">{{ selectedConnector.name }}</h2>
              <span :class="statusBadge(selectedConnector.status)" class="inline-flex rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(selectedConnector.status) }}</span>
            </div>
            <p class="mt-1 text-sm text-[var(--ds-text-muted)]">{{ selectedConnector.description || selectedConnector.equipment?.name || 'Conector sem descrição operacional.' }}</p>
          </div>
          <div v-if="canManage" class="flex flex-wrap gap-2">
            <button type="button" class="ds-button ds-button-secondary" @click="testConnector(selectedConnector)">
              <BoltIcon class="h-4 w-4" aria-hidden="true" /> Testar
            </button>
            <button type="button" class="ds-button ds-button-secondary" @click="openEditConnector(selectedConnector)">Editar</button>
          </div>
        </div>

        <div class="grid divide-y divide-[var(--ds-border)] lg:grid-cols-[minmax(0,1fr)_minmax(22rem,0.75fr)] lg:divide-x lg:divide-y-0">
          <div class="px-5 py-5">
            <h3 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Identidade e transporte</h3>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
              <div><dt class="text-xs text-[var(--ds-text-soft)]">Adaptador</dt><dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ connectorAdapter(selectedConnector)?.label }}</dd></div>
              <div><dt class="text-xs text-[var(--ds-text-soft)]">Direcção</dt><dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ directionLabel(selectedConnector.direction) }}</dd></div>
              <div><dt class="text-xs text-[var(--ds-text-soft)]">Equipamento</dt><dd class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ selectedConnector.equipment?.name || (selectedConnector.equipment_link_unavailable ? 'Associação indisponível' : 'Não associado') }}</dd></div>
              <div><dt class="text-xs text-[var(--ds-text-soft)]">Edge agent</dt><dd class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text)]">{{ selectedConnector.configuration?.edge_agent_id || 'Não definido' }}</dd></div>
            </dl>

            <div v-if="['inbound', 'bidirectional'].includes(selectedConnector.direction)" class="mt-6 border-t border-[var(--ds-border)] pt-5">
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Endpoint de ingestão</h3>
                <button v-if="canManage" type="button" class="text-xs font-semibold text-[rgb(var(--primary-700-rgb))]" @click="rotateToken(selectedConnector)">Rodar token</button>
              </div>
              <div class="mt-3 flex items-center gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-2.5">
                <code class="min-w-0 flex-1 truncate text-xs text-[var(--ds-text-muted)]">{{ selectedConnector.ingest_endpoint }}</code>
                <button type="button" class="ds-icon-button h-8 w-8 shrink-0" title="Copiar endpoint" @click="copyText(selectedConnector.ingest_endpoint, 'ingest')">
                  <CheckCircleIcon v-if="copiedValue === 'ingest'" class="h-4 w-4 text-emerald-500" />
                  <ClipboardDocumentIcon v-else class="h-4 w-4" />
                </button>
              </div>
            </div>

            <div v-if="['outbound', 'bidirectional'].includes(selectedConnector.direction)" class="mt-6 border-t border-[var(--ds-border)] pt-5">
              <h3 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Destino de partilha</h3>
              <p class="mt-3 break-all font-mono text-xs text-[var(--ds-text-muted)]">{{ selectedConnector.configuration?.endpoint || 'Endpoint não configurado' }}</p>
            </div>
          </div>

          <div class="px-5 py-5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Mapeamento activo</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text)]">{{ selectedConnector.active_mapping?.name || 'Sem mapeamento' }}</p>
              </div>
              <span v-if="selectedConnector.active_mapping" class="rounded-md border border-[var(--ds-border)] px-2 py-1 font-mono text-[0.65rem] font-semibold text-[var(--ds-text-muted)]">v{{ selectedConnector.active_mapping.version }}</span>
            </div>
            <div v-if="selectedConnector.active_mapping" class="mt-4 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
              <div v-for="field in ['sample_code', 'parameter_code', 'value', 'unit', 'measured_at']" :key="field" class="flex items-center justify-between gap-4 py-2.5">
                <span class="font-mono text-[0.68rem] uppercase text-[var(--ds-text-soft)]">{{ field }}</span>
                <code class="min-w-0 truncate text-right text-xs text-[var(--ds-text)]">{{ selectedConnector.active_mapping.field_paths?.[field] || '—' }}</code>
              </div>
            </div>
            <div v-if="canManage && ['inbound', 'bidirectional'].includes(selectedConnector.direction)" class="mt-4 flex flex-wrap gap-2">
              <button type="button" class="ds-button ds-button-secondary" @click="openMapping(selectedConnector)">
                <CodeBracketSquareIcon class="h-4 w-4" aria-hidden="true" /> Nova versão
              </button>
              <button type="button" class="ds-button ds-button-secondary" @click="mappingTestOpen = true">
                <BeakerIcon class="h-4 w-4" aria-hidden="true" /> Diagnóstico
              </button>
            </div>
          </div>
        </div>
      </section>
    </template>

    <template v-else-if="activeTab === 'inbox'">
      <section class="ds-panel overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-sm font-semibold text-[var(--ds-text)]">Transmissões recebidas</h2>
            <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Payload original, correspondência e decisão de revisão.</p>
          </div>
          <div class="flex max-w-full gap-1 overflow-x-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-1">
            <button v-for="filter in [{value:'actionable',label:'Acção'},{value:'matched',label:'Correspondidas'},{value:'quarantined',label:'Quarentena'},{value:'imported',label:'Importadas'},{value:'all',label:'Todas'}]" :key="filter.value" type="button" :class="transmissionStatusFilter === filter.value ? 'bg-[var(--ds-panel)] text-[var(--ds-text)] shadow-sm' : 'text-[var(--ds-text-muted)]'" class="h-8 shrink-0 rounded-md px-3 text-xs font-semibold" @click="transmissionStatusFilter = filter.value">{{ filter.label }}</button>
          </div>
        </div>

        <div class="grid min-h-[34rem] lg:grid-cols-[minmax(25rem,0.9fr)_minmax(28rem,1.1fr)]">
          <div class="max-h-[46rem] overflow-y-auto border-b border-[var(--ds-border)] lg:border-b-0 lg:border-r">
            <button
              v-for="transmission in filteredTransmissions"
              :key="transmission.id"
              type="button"
              :class="selectedTransmissionId === transmission.id ? 'bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.08)]' : 'hover:bg-[var(--ds-panel-subtle)]'"
              class="block w-full border-b border-[var(--ds-border)] px-5 py-4 text-left transition"
              @click="selectedTransmissionId = transmission.id"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ transmission.sample_code || 'Sem correspondência de amostra' }}</p>
                  <p class="mt-1 truncate text-xs text-[var(--ds-text-muted)]">{{ transmission.parameter_code || 'Sem parâmetro' }} · <span class="font-mono">{{ transmission.measured_value ?? '—' }} {{ transmission.measured_unit }}</span></p>
                </div>
                <span :class="statusBadge(transmission.status)" class="inline-flex shrink-0 rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(transmission.status) }}</span>
              </div>
              <div class="mt-3 flex items-center justify-between gap-3 text-xs text-[var(--ds-text-soft)]">
                <span class="truncate">{{ transmission.connector?.name }}</span>
                <span class="shrink-0">{{ relativeTime(transmission.received_at) }}</span>
              </div>
            </button>
            <div v-if="!filteredTransmissions.length" class="px-6 py-16 text-center text-sm text-[var(--ds-text-muted)]">Nenhuma transmissão neste estado.</div>
          </div>

          <div v-if="selectedTransmission" class="min-w-0">
            <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-sm font-semibold text-[var(--ds-text)]">Mensagem #{{ selectedTransmission.id }}</h3>
                  <span :class="statusBadge(selectedTransmission.status)" class="inline-flex rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(selectedTransmission.status) }}</span>
                </div>
                <p class="mt-1 font-mono text-xs text-[var(--ds-text-soft)]">{{ selectedTransmission.external_id }}</p>
              </div>
              <div v-if="['matched', 'quarantined'].includes(selectedTransmission.status)" class="flex flex-wrap gap-2">
                <button type="button" class="ds-button ds-button-secondary" @click="openRejectTransmission(selectedTransmission)">Rejeitar</button>
                <button v-if="canImport && selectedTransmission.status === 'matched'" type="button" class="ds-button ds-button-primary" @click="importTransmission(selectedTransmission)">
                  <CloudArrowDownIcon class="h-4 w-4" aria-hidden="true" /> Importar resultado
                </button>
              </div>
            </div>

            <div class="grid divide-y divide-[var(--ds-border)] xl:grid-cols-2 xl:divide-x xl:divide-y-0">
              <div class="px-5 py-5">
                <h4 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Dados normalizados</h4>
                <dl class="mt-4 space-y-3">
                  <div v-for="item in [
                    ['Amostra', selectedTransmission.sample_code],
                    ['Parâmetro', selectedTransmission.parameter_code],
                    ['Valor', selectedTransmission.measured_value],
                    ['Unidade', selectedTransmission.measured_unit],
                    ['Medição', formatDateTime(selectedTransmission.measured_at)],
                    ['Mapeamento', selectedTransmission.mapping ? `${selectedTransmission.mapping.name} · v${selectedTransmission.mapping.version}` : 'Sem versão'],
                  ]" :key="item[0]" class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] pb-3">
                    <dt class="text-xs text-[var(--ds-text-soft)]">{{ item[0] }}</dt>
                    <dd class="min-w-0 text-right text-xs font-semibold text-[var(--ds-text)]">{{ item[1] ?? '—' }}</dd>
                  </div>
                </dl>

                <div v-if="selectedTransmission.diagnostics?.issues?.length" class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-500/10">
                  <div class="flex items-center gap-2 text-xs font-semibold text-amber-900 dark:text-amber-200"><ExclamationTriangleIcon class="h-4 w-4" /> Divergências</div>
                  <ul class="mt-3 space-y-2 text-xs leading-5 text-amber-800 dark:text-amber-300">
                    <li v-for="issue in selectedTransmission.diagnostics.issues" :key="issue">{{ issue }}</li>
                  </ul>
                </div>
              </div>

              <div class="min-w-0 px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                  <h4 class="text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Payload original</h4>
                  <span class="font-mono text-[0.65rem] text-[var(--ds-text-soft)]">SHA-256 · {{ selectedTransmission.checksum?.slice(0, 12) }}</span>
                </div>
                <pre class="mt-4 max-h-[28rem] overflow-auto rounded-lg border border-[var(--ds-border)] bg-slate-950 p-4 font-mono text-[0.72rem] leading-5 text-slate-200">{{ JSON.stringify(JSON.parse(selectedTransmission.raw_payload || '{}'), null, 2) }}</pre>
              </div>
            </div>
          </div>
          <div v-else class="grid place-items-center p-10 text-sm text-[var(--ds-text-muted)]">Seleccione uma transmissão.</div>
        </div>
      </section>
    </template>

    <template v-else>
      <div class="grid gap-5 xl:grid-cols-[minmax(20rem,0.65fr)_minmax(0,1.35fr)]">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="text-sm font-semibold text-[var(--ds-text)]">Subscrições de saída</h2>
            <p class="mt-1 text-xs text-[var(--ds-text-muted)]">Endpoints que recebem eventos assinados.</p>
          </div>
          <div v-if="outboundConnectors.length" class="divide-y divide-[var(--ds-border)]">
            <button v-for="connector in outboundConnectors" :key="connector.uuid" type="button" class="block w-full px-5 py-4 text-left transition hover:bg-[var(--ds-panel-subtle)]" @click="selectedConnectorUuid = connector.uuid; activeTab = 'connectors'">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-[var(--ds-text)]">{{ connector.name }}</p><p class="mt-1 truncate text-xs text-[var(--ds-text-soft)]">{{ connector.configuration?.endpoint }}</p></div>
                <span :class="statusBadge(connector.status)" class="inline-flex shrink-0 rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(connector.status) }}</span>
              </div>
              <div class="mt-3 flex flex-wrap gap-1.5"><span v-for="event in connector.event_types" :key="event" class="rounded-md border border-[var(--ds-border)] px-2 py-1 font-mono text-[0.62rem] text-[var(--ds-text-muted)]">{{ event }}</span></div>
            </button>
          </div>
          <div v-else class="px-6 py-14 text-center"><LinkIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" /><p class="mt-3 text-sm font-semibold text-[var(--ds-text)]">Sem subscrições activas</p></div>
        </section>

        <DataTableShell :show-summary="false">
          <div class="flex items-center justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <div><h2 class="text-sm font-semibold text-[var(--ds-text)]">Histórico de entregas</h2><p class="mt-1 text-xs text-[var(--ds-text-muted)]">Tentativas, resposta HTTP e repetição.</p></div>
            <span class="font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ deliveries.length }}</span>
          </div>
          <div class="overflow-x-auto">
            <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
              <thead class="bg-[var(--ds-panel-subtle)]"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Evento</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Destino</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Estado</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Resposta</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase text-[var(--ds-text-soft)]"><span class="sr-only">Acções</span></th></tr></thead>
              <tbody class="divide-y divide-[var(--ds-border)]">
                <tr v-for="delivery in deliveries" :key="delivery.id">
                  <td class="px-5 py-4"><p class="whitespace-nowrap font-mono text-xs font-semibold text-[var(--ds-text)]">{{ delivery.event_type }}</p><p class="mt-1 whitespace-nowrap text-xs text-[var(--ds-text-soft)]">{{ relativeTime(delivery.created_at) }}</p></td>
                  <td class="px-4 py-4 text-xs font-semibold text-[var(--ds-text)]">{{ delivery.connector?.name }}</td>
                  <td class="px-4 py-4"><span :class="statusBadge(delivery.status)" class="inline-flex rounded-full px-2 py-1 text-[0.68rem] font-semibold ring-1 ring-inset">{{ statusLabel(delivery.status) }}</span></td>
                  <td class="px-4 py-4"><p class="text-xs font-semibold text-[var(--ds-text)]">{{ delivery.http_status ? `HTTP ${delivery.http_status}` : 'Sem resposta' }}</p><p class="mt-1 whitespace-nowrap text-xs text-[var(--ds-text-soft)]">{{ delivery.attempts }} tentativa(s)</p></td>
                  <td class="px-5 py-4 text-right"><button v-if="canManage && ['failed', 'retrying'].includes(delivery.status)" type="button" class="ds-icon-button ml-auto" title="Reenviar" @click="retryDelivery(delivery)"><ArrowPathIcon class="h-4 w-4" /></button></td>
                </tr>
              </tbody>
            </DataTable>
          </div>
          <div v-if="!deliveries.length" class="px-6 py-14 text-center text-sm text-[var(--ds-text-muted)]">Ainda não existem entregas externas.</div>
        </DataTableShell>
      </div>
    </template>
  </div>

  <TransitionRoot as="template" :show="connectorModalOpen">
    <Dialog as="div" class="relative z-[80]" @close="connectorModalOpen = false">
      <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/55" /></TransitionChild>
      <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6">
        <div class="flex min-h-full items-start justify-center sm:items-center">
          <DialogPanel class="ds-floating-panel w-full max-w-3xl overflow-hidden">
            <form @submit.prevent="submitConnector">
              <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
                <div><DialogTitle class="text-base font-semibold text-[var(--ds-text)]">{{ editingConnectorUuid ? 'Editar conector' : 'Novo conector' }}</DialogTitle><p class="mt-1 text-xs text-[var(--ds-text-muted)]">Perfil de transporte, autenticação e eventos.</p></div>
                <button type="button" class="ds-icon-button" title="Fechar" @click="connectorModalOpen = false"><XMarkIcon class="h-4 w-4" /></button>
              </div>
              <div class="max-h-[72vh] space-y-6 overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="sm:col-span-2"><BaseInput v-model="connectorForm.name" label="Nome" :error="connectorForm.errors.name" required /></div>
                  <BaseInput v-model="connectorForm.key" label="Identificador" :error="connectorForm.errors.key" class="font-mono" placeholder="gerado pelo nome" />
                  <div>
                    <ComboboxEnhanced
                      v-model="selectedEquipmentOption"
                      title-label="Equipamento associado"
                      :options="equipmentComboboxOptions"
                      placeholder="Pesquisar por nome, código ou série"
                      :has-error="Boolean(connectorForm.errors.inventory_item_id)"
                    />
                    <span v-if="connectorForm.errors.inventory_item_id" class="mt-1 block text-xs text-rose-600">{{ connectorForm.errors.inventory_item_id }}</span>
                    <p v-if="equipmentLinkUnavailable" class="mt-1 text-xs text-[var(--ds-text-muted)]">A associação existente está indisponível. Será preservada se não escolher outro equipamento.</p>
                  </div>
                  <BaseSelect v-model="connectorForm.direction" label="Direcção" :options="directionOptions" />
                  <BaseSelect v-model="connectorForm.adapter" label="Adaptador" :options="adapterCatalog" />
                  <BaseSelect v-model="connectorForm.status" label="Estado" :options="connectorStatusOptions" />
                  <BaseInput v-model.number="connectorForm.configuration.timeout_seconds" type="number" label="Timeout" min="2" max="30">
                    <template #trailing><span class="text-xs">seg</span></template>
                  </BaseInput>
                  <div class="sm:col-span-2"><BaseTextarea v-model="connectorForm.description" label="Descrição operacional" rows="2" class="min-h-20" /></div>
                </div>

                <div v-if="connectorAdapter(connectorForm)?.edge_required || ['astm_edge','hl7_edge','tcp_edge','serial_edge','opc_ua_edge'].includes(connectorForm.adapter)" class="border-t border-[var(--ds-border)] pt-5">
                  <div class="flex items-center gap-2"><ServerStackIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><h3 class="text-sm font-semibold text-[var(--ds-text)]">Edge relay</h3></div>
                  <div class="mt-4"><BaseInput v-model="connectorForm.configuration.edge_agent_id" label="Identificador do agente" class="font-mono" placeholder="lab-edge-01" /></div>
                </div>

                <div v-if="['outbound','bidirectional'].includes(connectorForm.direction)" class="border-t border-[var(--ds-border)] pt-5">
                  <div class="flex items-center gap-2"><CloudArrowUpIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><h3 class="text-sm font-semibold text-[var(--ds-text)]">Partilha externa</h3></div>
                  <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><BaseInput v-model="connectorForm.configuration.endpoint" type="url" label="Endpoint HTTPS" :error="connectorForm.errors['configuration.endpoint']" class="font-mono" placeholder="https://partner.example/api/results" /></div>
                    <div class="sm:col-span-2"><BaseInput v-model="connectorForm.credentials.bearer_token" type="password" autocomplete="new-password" :label="editingConnectorUuid ? 'Bearer token · deixe vazio para manter' : 'Bearer token'" /></div>
                    <label class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] p-3"><CheckboxInput v-model="connectorForm.event_types" value="lims.analysis.validated" /><span><span class="block text-xs font-semibold text-[var(--ds-text)]">Análise validada</span><span class="mt-0.5 block font-mono text-[0.65rem] text-[var(--ds-text-soft)]">lims.analysis.validated</span></span></label>
                  </div>
                </div>
              </div>
              <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:px-6"><button type="button" class="ds-button ds-button-secondary" @click="connectorModalOpen = false">Cancelar</button><button type="submit" class="ds-button ds-button-primary" :disabled="connectorForm.processing">{{ connectorForm.processing ? 'A guardar…' : 'Guardar conector' }}</button></div>
            </form>
          </DialogPanel>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <TransitionRoot as="template" :show="mappingModalOpen">
    <Dialog as="div" class="relative z-[80]" @close="mappingModalOpen = false">
      <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/55" /></TransitionChild>
      <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6"><div class="flex min-h-full items-start justify-center sm:items-center"><DialogPanel class="ds-floating-panel w-full max-w-4xl overflow-hidden">
        <form @submit.prevent="submitMapping">
          <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><div><DialogTitle class="text-base font-semibold text-[var(--ds-text)]">Publicar versão de mapeamento</DialogTitle><p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ selectedConnector?.name }} · a versão anterior permanece no histórico.</p></div><button type="button" class="ds-icon-button" title="Fechar" @click="mappingModalOpen = false"><XMarkIcon class="h-4 w-4" /></button></div>
          <div class="max-h-[72vh] overflow-y-auto px-5 py-5 sm:px-6">
            <BaseInput v-model="mappingForm.name" label="Nome da versão" required />
            <div class="mt-6 overflow-hidden rounded-lg border border-[var(--ds-border)]">
              <div class="grid grid-cols-[8rem_minmax(0,1fr)] gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 text-xs font-semibold uppercase text-[var(--ds-text-soft)] sm:grid-cols-[11rem_minmax(0,1fr)_12rem]"><span>Campo LIMS</span><span>Caminho no payload</span><span class="hidden sm:block">Transformações</span></div>
              <div v-for="field in [{key:'sample_code',label:'Amostra',required:true},{key:'parameter_code',label:'Parâmetro',required:true},{key:'value',label:'Valor',required:true},{key:'unit',label:'Unidade'},{key:'measured_at',label:'Data da medição'},{key:'instrument_serial',label:'N.º de série'},{key:'operator',label:'Operador'}]" :key="field.key" class="grid grid-cols-[8rem_minmax(0,1fr)] items-center gap-3 border-b border-[var(--ds-border)] px-4 py-3 last:border-0 sm:grid-cols-[11rem_minmax(0,1fr)_12rem]"><span class="text-xs font-semibold text-[var(--ds-text)]">{{ field.label }}<span v-if="field.required" class="text-rose-500"> *</span></span><BaseInput v-model="mappingForm.field_paths[field.key]" class="h-9 w-full font-mono text-xs" :aria-label="`Caminho para ${field.label}`" :required="field.required" /><span class="hidden truncate font-mono text-[0.65rem] text-[var(--ds-text-soft)] sm:block">{{ (mappingForm.transformations[field.key] || []).join(' → ') || 'sem transformação' }}</span></div>
            </div>
          </div>
          <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:px-6"><button type="button" class="ds-button ds-button-secondary" @click="mappingModalOpen = false">Cancelar</button><button type="submit" class="ds-button ds-button-primary" :disabled="mappingForm.processing"><CodeBracketSquareIcon class="h-4 w-4" /> Publicar versão</button></div>
        </form>
      </DialogPanel></div></div>
    </Dialog>
  </TransitionRoot>

  <TransitionRoot as="template" :show="mappingTestOpen">
    <Dialog as="div" class="relative z-[80]" @close="mappingTestOpen = false">
      <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/55" /></TransitionChild>
      <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6"><div class="flex min-h-full items-start justify-center sm:items-center"><DialogPanel class="ds-floating-panel w-full max-w-5xl overflow-hidden">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><div><DialogTitle class="text-base font-semibold text-[var(--ds-text)]">Diagnóstico de payload</DialogTitle><p class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ selectedConnector?.name }} · mapeamento v{{ selectedConnector?.active_mapping?.version }}</p></div><button type="button" class="ds-icon-button" title="Fechar" @click="mappingTestOpen = false"><XMarkIcon class="h-4 w-4" /></button></div>
        <div class="grid max-h-[72vh] overflow-y-auto lg:grid-cols-2 lg:divide-x lg:divide-[var(--ds-border)]">
          <div class="p-5 sm:p-6"><BaseTextarea v-model="payloadTestText" label="Payload JSON" class="h-96 w-full resize-none bg-slate-950 p-4 font-mono text-xs leading-5 text-slate-200" spellcheck="false" /></div>
          <div class="p-5 sm:p-6"><h3 class="ds-label">Resultado do diagnóstico</h3><div v-if="payloadTestResult" class="mt-3 space-y-4"><div :class="payloadTestResult.issues.length ? 'border-amber-200 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-500/10' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-400/20 dark:bg-emerald-500/10'" class="rounded-lg border p-4"><div class="flex items-center gap-2 text-sm font-semibold text-[var(--ds-text)]"><CheckCircleIcon v-if="!payloadTestResult.issues.length" class="h-5 w-5 text-emerald-500" /><ExclamationTriangleIcon v-else class="h-5 w-5 text-amber-500" />{{ payloadTestResult.issues.length ? 'Requer revisão' : 'Correspondência completa' }}</div><ul v-if="payloadTestResult.issues.length" class="mt-3 space-y-1 text-xs text-amber-800 dark:text-amber-300"><li v-for="issue in payloadTestResult.issues" :key="issue">{{ issue }}</li></ul></div><div class="divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]"><div v-for="(value, key) in payloadTestResult.values" :key="key" class="flex items-start justify-between gap-4 py-2.5"><span class="font-mono text-[0.68rem] uppercase text-[var(--ds-text-soft)]">{{ key }}</span><code class="min-w-0 break-all text-right text-xs text-[var(--ds-text)]">{{ value ?? '—' }}</code></div></div></div><div v-else class="mt-3 grid h-96 place-items-center rounded-lg border border-dashed border-[var(--ds-border)] text-center text-sm text-[var(--ds-text-muted)]"><div><BeakerIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" /><p class="mt-3">Execute o payload para ver normalização e correspondências.</p></div></div><p v-if="payloadTestError" class="mt-3 text-xs text-rose-600">{{ payloadTestError }}</p></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-[var(--ds-border)] px-5 py-4 sm:px-6"><button type="button" class="ds-button ds-button-secondary" @click="mappingTestOpen = false">Fechar</button><button type="button" class="ds-button ds-button-primary" :disabled="payloadTestRunning" @click="runMappingTest"><BeakerIcon class="h-4 w-4" />{{ payloadTestRunning ? 'A analisar…' : 'Executar diagnóstico' }}</button></div>
      </DialogPanel></div></div>
    </Dialog>
  </TransitionRoot>

  <TransitionRoot as="template" :show="rejectModalOpen">
    <Dialog as="div" class="relative z-[80]" @close="rejectModalOpen = false"><TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/55" /></TransitionChild><div class="fixed inset-0 overflow-y-auto p-4"><div class="flex min-h-full items-center justify-center"><DialogPanel class="ds-floating-panel w-full max-w-lg overflow-hidden"><form @submit.prevent="rejectTransmission"><div class="border-b border-[var(--ds-border)] px-5 py-4"><DialogTitle class="text-base font-semibold text-[var(--ds-text)]">Rejeitar transmissão</DialogTitle><p class="mt-1 text-xs text-[var(--ds-text-muted)]">Mensagem #{{ selectedTransmission?.id }} · {{ selectedTransmission?.external_id }}</p></div><div class="px-5 py-5"><BaseTextarea v-model="rejectionForm.reason" label="Justificação" rows="4" :error="rejectionForm.errors.reason" required /></div><div class="flex justify-end gap-2 border-t border-[var(--ds-border)] px-5 py-4"><button type="button" class="ds-button ds-button-secondary" @click="rejectModalOpen = false">Cancelar</button><button type="submit" class="ds-button bg-rose-600 text-white hover:bg-rose-700" :disabled="rejectionForm.processing">Confirmar rejeição</button></div></form></DialogPanel></div></div></Dialog>
  </TransitionRoot>

  <TransitionRoot as="template" :show="tokenModalOpen">
    <Dialog as="div" class="relative z-[90]" @close="tokenModalOpen = false"><TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-slate-950/60" /></TransitionChild><div class="fixed inset-0 overflow-y-auto p-4"><div class="flex min-h-full items-center justify-center"><DialogPanel class="ds-floating-panel w-full max-w-xl overflow-hidden"><div class="flex items-start gap-4 border-b border-[var(--ds-border)] px-5 py-5"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"><KeyIcon class="h-5 w-5" /></span><div><DialogTitle class="text-base font-semibold text-[var(--ds-text)]">Token de ingestão</DialogTitle><p class="mt-1 text-xs leading-5 text-[var(--ds-text-muted)]">Esta credencial é apresentada uma única vez.</p></div></div><div class="px-5 py-5"><div class="flex items-center gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3"><code class="min-w-0 flex-1 break-all text-xs text-[var(--ds-text)]">{{ revealedToken }}</code><button type="button" class="ds-icon-button shrink-0" title="Copiar token" @click="copyText(revealedToken, 'token')"><CheckCircleIcon v-if="copiedValue === 'token'" class="h-4 w-4 text-emerald-500" /><ClipboardDocumentIcon v-else class="h-4 w-4" /></button></div><p class="mt-3 font-mono text-[0.65rem] text-[var(--ds-text-soft)]">CONNECTOR · {{ revealedConnectorUuid }}</p></div><div class="flex justify-end border-t border-[var(--ds-border)] px-5 py-4"><button type="button" class="ds-button ds-button-primary" @click="tokenModalOpen = false">Concluir</button></div></DialogPanel></div></div></Dialog>
  </TransitionRoot>
</template>
