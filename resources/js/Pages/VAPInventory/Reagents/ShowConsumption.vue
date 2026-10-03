<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { useConsumptionReversal } from '@/Composables/useConsumptionReversal'
import { usePermission } from '@/Composables/usePermissions'
import { Link } from '@inertiajs/vue3'
import { ArrowRight as ArrowRightIcon, TriangleAlert as ExclamationTriangleIcon, Undo2 as ArrowUturnLeftIcon } from '@lucide/vue'
import { computed } from 'vue'

/**
 * Consumption dossier (Plano). The record is evidence and is never deleted: a
 * reversal restores the stock once through a compensating movement and keeps
 * the original consumption alongside it.
 */
const props = defineProps({
  consumption: {
    type: Object,
    required: true,
  },
})

const quantityFormatter = new Intl.NumberFormat('pt-PT', {
  maximumFractionDigits: 4,
})
const { hasPermission } = usePermission()
const reversal = useConsumptionReversal({
  canReverse: () => hasPermission('delete_reagent_consumption'),
  reverseUrl: id => route('vap-inventory.reagents.consumption.reverse', id),
})

const unit = computed(() => props.consumption.item?.unit?.code || 'unidades')

const isReagentExpired = computed(() => {
  if (!props.consumption.item?.reagent_expiry_date) {
    return false
  }

  return new Date(props.consumption.item.reagent_expiry_date) < new Date()
})

const consumptionFacts = computed(() => [
  ['Reagente', props.consumption.reagent_name],
  ['Código interno', props.consumption.item?.internal_code || props.consumption.item?.internalcode || 'N/A'],
  ['Quantidade usada', `${formatQuantity(props.consumption.quantity_used)} ${unit.value}`],
  ['Armazém', [props.consumption.warehouse?.name || 'N/A', props.consumption.warehouse?.location?.name].filter(Boolean).join(' · ')],
  ['Usado por', props.consumption.used_by],
  ['Registado por', props.consumption.user?.name || 'Sistema'],
  ['Data de consumo', formatDate(props.consumption.date)],
  ['Usado em', formatDateTime(props.consumption.used_at)],
])

const reagentFacts = computed(() => [
  ['Nome', props.consumption.item?.name || 'N/A'],
  ['Categoria', props.consumption.item?.category?.name || 'N/A'],
  ['Unidade', props.consumption.item?.unit?.code || 'N/A'],
  ['Validade', props.consumption.item?.reagent_expiry_date ? formatDate(props.consumption.item.reagent_expiry_date) : 'N/A'],
  ['Marca', props.consumption.item?.brand || 'N/A'],
  ['Modelo', props.consumption.item?.model || 'N/D'],
  ['Número de série', props.consumption.item?.serial_number || 'N/A'],
  ['Fornecedor', props.consumption.item?.supplier?.name || 'N/A'],
])

const movements = computed(() => {
  const rows = [
    {
      label: 'Consumo registado',
      timestamp: formatDateTime(props.consumption.created_at),
      caption: `por ${props.consumption.user?.name || 'Sistema'}`,
    },
    {
      label: `Saída de ${formatQuantity(props.consumption.quantity_used)} ${unit.value}`,
      timestamp: formatDateTime(props.consumption.created_at),
      caption: props.consumption.warehouse?.name || 'Armazém N/A',
    },
  ]

  if (props.consumption.reversal) {
    rows.push({
      label: `Reposição de ${formatQuantity(props.consumption.quantity_used)} ${unit.value}`,
      timestamp: formatDateTime(props.consumption.reversal.reversed_at),
      caption: `Revertido por ${props.consumption.reversal.user?.name || 'operador registado'}`,
    })
  }

  return rows
})

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

function printConsumption() {
  window.print()
}
</script>

<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Consumo de reagentes', url: route('vap-inventory.reagents.consumption.index') }, { title: `#${consumption.id}` }]"
      :title="`Consumo #${consumption.id}`"
      :lede="`${consumption.reagent_name} · ${formatQuantity(consumption.quantity_used)} ${unit} · ${consumption.warehouse?.name || 'armazém não definido'}`"
    >
      <template #badges>
        <StatusChip :tone="consumption.reversal ? 'done' : 'ok'">{{ consumption.reversal ? 'Revertido' : 'Registado' }}</StatusChip>
        <StatusChip v-if="isReagentExpired" tone="wait">Reagente vencido</StatusChip>
      </template>
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="printConsumption">Imprimir</button>
      </template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <p v-if="consumption.reversal" class="pl-banner pl-banner-ok text-sm">
          <ArrowUturnLeftIcon aria-hidden="true" />
          <span>
            Existências repostas em {{ formatDateTime(consumption.reversal.reversed_at) }}
            por {{ consumption.reversal.user?.name || 'operador registado' }}.
            Consumo e movimentos originais preservados.
          </span>
        </p>

        <section class="pl-panel" aria-labelledby="consumption-facts-title">
          <div class="pl-panel-head"><h2 id="consumption-facts-title" class="pl-k">Consumo</h2><span class="pl-k pl-faint">Registado {{ formatDateTime(consumption.created_at) }}</span></div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="[label, value] in consumptionFacts" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value }}</dd></div>
          </dl>
          <div v-if="consumption.remarks" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Observações</h3>
            <p class="whitespace-pre-line text-sm">{{ consumption.remarks }}</p>
          </div>
        </section>

        <section class="pl-panel" aria-labelledby="consumption-movements-title">
          <div class="pl-panel-head"><h2 id="consumption-movements-title" class="pl-k">Movimentos</h2><span class="pl-k pl-faint">{{ movements.length }} registos</span></div>
          <ol>
            <li v-for="movement in movements" :key="movement.label" class="pl-row">
              <span>{{ movement.label }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ movement.caption }}</span></span>
              <span class="pl-num text-[var(--pl-muted)]">{{ movement.timestamp }}</span>
            </li>
          </ol>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-labelledby="consumption-stock-title">
          <div class="pl-panel-head"><h2 id="consumption-stock-title" class="pl-k">Existências</h2><span class="pl-k pl-faint">{{ consumption.warehouse?.name || 'Armazém N/A' }}</span></div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Consumido</dt><dd class="pl-num">{{ formatQuantity(consumption.quantity_used) }} {{ unit }}</dd></div>
            <div class="pl-fact"><dt>Disponível agora</dt><dd class="pl-num">{{ formatQuantity(getCurrentStockInWarehouse()) }} {{ unit }}</dd></div>
          </dl>
        </section>

        <section v-if="consumption.item" class="pl-panel" aria-labelledby="consumption-reagent-title">
          <div class="pl-panel-head"><h2 id="consumption-reagent-title" class="pl-k">Reagente</h2><span class="pl-k pl-faint">{{ consumption.item?.code || 'Sem código' }}</span></div>
          <p v-if="isReagentExpired" class="pl-banner pl-banner-warn m-4 text-sm">
            <ExclamationTriangleIcon aria-hidden="true" />
            <span>Validade expirada em {{ formatDate(consumption.item.reagent_expiry_date) }}.</span>
          </p>
          <dl class="pl-facts">
            <div v-for="[label, value] in reagentFacts" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Dossier</h2><span class="pl-k pl-faint">#{{ consumption.id }}</span></div>
          <Link v-if="consumption.item && !consumption.item.is_archived" :href="route('vap-inventory.items.show', consumption.reagent_id)" class="pl-row"><span>Ver reagente</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Reagente arquivado. Identificação preservada neste registo.</p>
          <Link :href="route('vap-inventory.reagents.consumption.index')" class="pl-row"><span>Registo de consumos</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
        </section>
      </aside>
    </div>

    <NextStepBar>
      <template v-if="consumption.reversal">Consumo revertido. As existências foram repostas uma vez e não há mais acções sobre este registo.</template>
      <template v-else-if="hasPermission('delete_reagent_consumption')">Consumo registado. Se foi lançado por engano, reverta-o: as existências são repostas e o registo original fica preservado.</template>
      <template v-else>Consumo registado. A reversão exige permissão para reverter consumos.</template>
      <template #actions>
        <button v-if="hasPermission('delete_reagent_consumption') && !consumption.reversal" type="button" class="ds-button ds-button-danger" :disabled="reversal.processing.value" @click="reversal.open(consumption)">
          <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" />
          Reverter consumo
        </button>
      </template>
    </NextStepBar>

    <confirm-dialog
      v-if="reversal.pending.value"
      title="Reverter consumo"
      description="Esta acção repõe as existências uma única vez. O consumo original e o movimento de reposição ficam preservados."
      :confirm="reversal.processing.value ? 'A reverter…' : 'Reverter consumo'"
      cancel="Manter registo"
      variant="danger"
      keep-open-on-confirm
      :disabled="reversal.processing.value"
      @confirmed="reversal.confirm"
      @canceled="reversal.close"
    >
      <p v-if="reversal.error.value" role="alert" class="ds-field-error mt-3">{{ reversal.error.value }}</p>
      <dl class="pl-panel pl-facts mt-4 text-left">
        <div class="pl-fact"><dt>Reagente</dt><dd>{{ consumption.reagent_name }}</dd></div>
        <div class="pl-fact"><dt>Quantidade</dt><dd class="pl-num">{{ formatQuantity(consumption.quantity_used) }}</dd></div>
        <div class="pl-fact"><dt>Armazém</dt><dd>{{ consumption.warehouse?.name || 'armazém não definido' }}</dd></div>
      </dl>
    </confirm-dialog>
  </div>
</template>
