<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Controlo de reagentes</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-release" />
              Registado
            </span>
            <span class="ds-chip">#{{ consumption.id }}</span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Registo de consumo #{{ consumption.id }}</h1>
          <p class="ds-copy mt-2 text-sm">
            Reveja material, quantidade, armazém, responsável e impacto de existências associados a este consumo.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button type="button" class="ds-button ds-button-secondary" @click="goBack">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </button>
          <button type="button" class="ds-button ds-button-danger" @click="showDeleteConfirmation = true">
            <TrashIcon class="h-4 w-4" />
            Eliminar
          </button>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-4 md:divide-y-0">
        <div v-for="metric in summaryCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_22rem]">
      <div class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <ClipboardDocumentListIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Detalhes do consumo</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Rastreabilidade do registo e da saída de existências.</p>
              </div>
            </div>
          </div>

          <dl class="grid divide-y divide-[color:var(--ds-border)] md:grid-cols-2 md:divide-x md:divide-y-0">
            <div class="space-y-4 p-5">
              <div v-for="field in primaryFields" :key="field.label" class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                  <component :is="field.icon" class="h-4 w-4" />
                </span>
                <div class="min-w-0">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
                  <p v-if="field.caption" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ field.caption }}</p>
                </div>
              </div>
            </div>

            <div class="space-y-3 p-5">
              <div v-for="field in auditFields" :key="field.label" class="flex items-start justify-between gap-4 text-sm">
                <dt class="font-semibold text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                <dd class="text-right font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
              </div>
            </div>
          </dl>

          <div v-if="consumption.remarks" class="border-t border-[color:var(--ds-border)] p-5">
            <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Observações</p>
            <p class="mt-2 whitespace-pre-line text-sm text-[color:var(--ds-text-muted)]">{{ consumption.remarks }}</p>
          </div>
        </article>

        <article v-if="consumption.item" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Dossier do reagente</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Identificação do material afectado por este consumo.</p>
              </div>
            </div>
          </div>

          <dl class="grid divide-y divide-[color:var(--ds-border)] md:grid-cols-2 md:divide-x md:divide-y-0">
            <div class="p-5">
              <div v-for="field in reagentIdentityFields" :key="field.label" class="flex items-start justify-between gap-4 py-2 text-sm">
                <dt class="font-semibold text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                <dd class="text-right font-bold" :class="field.valueClass">{{ field.value }}</dd>
              </div>
            </div>
            <div class="p-5">
              <div v-for="field in reagentSupplyFields" :key="field.label" class="flex items-start justify-between gap-4 py-2 text-sm">
                <dt class="font-semibold text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                <dd class="text-right font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
              </div>
            </div>
          </dl>
        </article>
      </div>

      <aside class="space-y-4">
        <section class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">Acções</h2>
          <div class="mt-4 grid gap-2">
            <Link :href="route('vap-inventory.items.show', consumption.reagent_id)" class="ds-button ds-button-secondary w-full">
              <PencilIcon class="h-4 w-4" />
              Ver reagente
            </Link>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="printConsumption">
              <PrinterIcon class="h-4 w-4" />
              Imprimir registo
            </button>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Linha do tempo</h2>
          </div>
          <ol class="space-y-4 p-5">
            <li v-for="item in timelineItems" :key="item.label" class="flex gap-3">
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                <component :is="item.icon" class="h-4 w-4" />
              </span>
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ item.label }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ item.timestamp }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-muted)]">{{ item.caption }}</p>
              </div>
            </li>
          </ol>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Impacto no existências</h2>
          </div>
          <div class="grid gap-3 p-5">
            <div v-for="item in stockImpactCards" :key="item.label" class="ds-card p-4">
              <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ item.label }}</p>
              <p class="mt-2 text-xl font-bold" :class="item.valueClass">{{ item.value }}</p>
              <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ item.caption }}</p>
            </div>
          </div>
        </section>

        <section v-if="isReagentExpired" class="ds-card border-l-4 border-amber-500 p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-amber-700 dark:text-amber-300" />
            <div>
              <h3 class="text-sm font-bold text-[color:var(--ds-text)]">Reagente vencido</h3>
              <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">
                Validade: {{ formatDate(consumption.item.reagent_expiry_date) }}.
              </p>
            </div>
          </div>
        </section>
      </aside>
    </section>

    <confirm-dialog
      v-if="showDeleteConfirmation"
      title="Eliminar registo de consumo"
  description="Esta acção restaura as existências associadas ao consumo e remove o registo do histórico operacional visível."
      confirm="Eliminar registo"
      cancel="Manter registo"
      variant="danger"
      @confirmed="confirmDeleteConsumption"
      @canceled="showDeleteConfirmation = false"
    >
      <div class="mt-4 rounded-lg border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-4 text-left">
        <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ consumption.reagent_name }}</p>
        <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
          {{ formatQuantity(consumption.quantity_used) }} em {{ consumption.warehouse?.name || 'armazém não definido' }}
        </p>
      </div>
    </confirm-dialog>
  </div>
</template>

<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  BeakerIcon,
  BuildingStorefrontIcon,
  CheckIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  PencilIcon,
  PrinterIcon,
  TrashIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

const props = defineProps({
  consumption: {
    type: Object,
    required: true,
  },
})

const quantityFormatter = new Intl.NumberFormat('pt-PT', {
  maximumFractionDigits: 2,
})
const showDeleteConfirmation = ref(false)

const isReagentExpired = computed(() => {
  if (!props.consumption.item?.reagent_expiry_date) {
    return false
  }

  return new Date(props.consumption.item.reagent_expiry_date) < new Date()
})

const summaryCards = computed(() => [
  {
    label: 'Quantidade',
    value: formatQuantity(props.consumption.quantity_used),
    caption: props.consumption.item?.unit?.code || 'unidades',
    dotClass: 'lims-status-dot-critical',
    valueClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Consumo',
    value: formatDate(props.consumption.date),
    caption: 'Data operacional',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Armazém',
    value: props.consumption.warehouse?.name || 'N/A',
    caption: props.consumption.warehouse?.location?.name || 'Local não definido',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Estado',
    value: isReagentExpired.value ? 'Atenção' : 'Registado',
    caption: isReagentExpired.value ? 'Validade expirada' : 'Rastreabilidade activa',
    dotClass: isReagentExpired.value ? 'lims-status-dot-hold' : 'lims-status-dot-release',
    valueClass: isReagentExpired.value ? 'text-amber-700 dark:text-amber-300' : 'text-[color:var(--ds-text)]',
  },
])

const primaryFields = computed(() => [
  {
    label: 'Reagente',
    value: props.consumption.reagent_name,
    caption: props.consumption.item?.internal_code || props.consumption.item?.internalcode || 'Código N/A',
    icon: BeakerIcon,
  },
  {
    label: 'Quantidade usada',
    value: formatQuantity(props.consumption.quantity_used),
    caption: props.consumption.item?.unit?.code || 'unidades',
    icon: CubeIcon,
  },
  {
    label: 'Armazém',
    value: props.consumption.warehouse?.name || 'N/A',
    caption: props.consumption.warehouse?.location?.name || 'Localização N/A',
    icon: BuildingStorefrontIcon,
  },
  {
    label: 'Usado por',
    value: props.consumption.used_by,
    caption: `Registado por ${props.consumption.user?.name || 'Sistema'}`,
    icon: UserIcon,
  },
])

const auditFields = computed(() => [
  ['Data de consumo', formatDate(props.consumption.date)],
  ['Data de registo', formatDateTime(props.consumption.created_at)],
  ['Usado em', formatDateTime(props.consumption.used_at)],
  ['ID de registo', `#${props.consumption.id}`],
  ['Estado', 'Registado'],
].map(([label, value]) => ({ label, value })))

const reagentIdentityFields = computed(() => [
  ['Nome', props.consumption.item?.name || 'N/A'],
  ['Código interno', props.consumption.item?.internalcode || props.consumption.item?.internal_code || 'N/A'],
  ['Categoria', props.consumption.item?.category?.name || 'N/A'],
  ['Unidade', props.consumption.item?.unit?.code || 'N/A'],
  [
    'Validade',
    props.consumption.item?.reagent_expiry_date ? formatDate(props.consumption.item.reagent_expiry_date) : 'N/A',
    isReagentExpired.value ? 'text-rose-700 dark:text-rose-300' : 'text-[color:var(--ds-text)]',
  ],
].map(([label, value, valueClass = 'text-[color:var(--ds-text)]']) => ({ label, value, valueClass })))

const reagentSupplyFields = computed(() => [
  ['Marca', props.consumption.item?.brand || 'N/A'],
  ['Modelo', props.consumption.item?.model || 'N/D'],
  ['Número de série', props.consumption.item?.serial_number || 'N/A'],
  ['Fornecedor', props.consumption.item?.supplier?.name || 'N/A'],
].map(([label, value]) => ({ label, value })))

const timelineItems = computed(() => [
  {
    label: 'Registo de consumo',
    timestamp: formatDateTime(props.consumption.created_at),
    caption: `por ${props.consumption.user?.name || 'Sistema'}`,
    icon: CheckIcon,
  },
  {
    label: 'Existências actualizado',
    timestamp: formatDateTime(props.consumption.created_at),
    caption: `Existências reduzido em ${formatQuantity(props.consumption.quantity_used)}.`,
    icon: CubeIcon,
  },
])

const stockImpactCards = computed(() => [
  {
    label: 'Quantidade consumida',
    value: formatQuantity(props.consumption.quantity_used),
    caption: props.consumption.item?.unit?.code || 'unidades',
    valueClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Existências actual',
    value: formatQuantity(getCurrentStockInWarehouse()),
    caption: props.consumption.warehouse?.name || 'Armazém N/A',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Impacto',
    value: 'Saída',
    caption: `Redução aplicada ao ${props.consumption.warehouse?.name || 'armazém'}.`,
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return 'N/A'
  }

  return quantityFormatter.format(numericValue)
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function formatDateTime(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function getCurrentStockInWarehouse() {
  if (!props.consumption.item?.inventory) {
    return null
  }

  const inventory = props.consumption.item.inventory.find((stockItem) => stockItem.warehouse_id === props.consumption.warehouse_id)

  return inventory?.qty_available ?? null
}

function confirmDeleteConsumption() {
  showDeleteConfirmation.value = false

  router.delete(route('vap-inventory.reagents.consumption.destroy', props.consumption.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vap-inventory.reagents.consumption.index'))
    },
  })
}

function printConsumption() {
  window.print()
}

function goBack() {
  router.visit(route('vap-inventory.reagents.consumption.index'))
}
</script>
