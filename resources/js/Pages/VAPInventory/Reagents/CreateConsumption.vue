<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Reagent control</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-hold" />
              Saída controlada
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Registrar consumo de reagente</h1>
          <p class="ds-copy mt-2 text-sm">
            Registe a saída com stock disponível, armazém, responsável, data e observações para preservar rastreabilidade operacional.
          </p>
        </div>

        <button type="button" class="ds-button ds-button-secondary shrink-0" @click="goBack">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar
        </button>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-4 md:divide-y-0">
        <div v-for="metric in workspaceMetrics" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <BeakerIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Detalhes do consumo</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Seleccione material, origem de stock, quantidade e responsável técnico.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-4 p-5 md:grid-cols-2">
            <BaseSelect
              v-model="form.reagent_id"
              label="Reagente"
              :error="form.errors.reagent_id"
              required
              @change="onReagentChange"
            >
              <option value="">Seleccione um reagente</option>
              <option v-for="reagent in reagents" :key="reagent.id" :value="reagent.id">
                {{ reagent.name }} ({{ reagent.code }})
                <template v-if="reagent.total_stock !== undefined">
                  · Stock total: {{ formatQuantity(reagent.total_stock) }}
                </template>
              </option>
            </BaseSelect>

            <BaseSelect
              v-model="form.warehouse_id"
              label="Armazém"
              :error="form.errors.warehouse_id"
              :disabled="!form.reagent_id"
              required
              @change="onWarehouseChange"
            >
              <option value="">Seleccione um armazém</option>
              <option v-for="warehouse in availableWarehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.name }}
                <template v-if="warehouse.available_stock !== undefined">
                  · Disponível: {{ formatQuantity(warehouse.available_stock) }}
                </template>
              </option>
            </BaseSelect>

            <div class="md:col-span-2">
              <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                <BaseInput
                  v-model="form.quantity_used"
                  type="number"
                  label="Quantidade usada"
                  :min="0.01"
                  :max="maxQuantity"
                  :step="0.01"
                  :error="form.errors.quantity_used"
                  placeholder="Digite a quantidade"
                  required
                />
                <div class="flex items-end">
                  <span class="ds-chip min-h-10">
                    <span class="lims-status-dot" :class="currentStock >= Number(form.quantity_used || 0) ? 'lims-status-dot-release' : 'lims-status-dot-critical'" />
                    Máx: {{ formatQuantity(maxQuantity) }}
                  </span>
                </div>
              </div>
            </div>

            <BaseInput
              v-model="form.used_by"
              type="text"
              label="Usado por"
              :error="form.errors.used_by"
              placeholder="Nome da pessoa que usou o reagente"
              required
            />

            <BaseInput
              v-model="form.date"
              type="date"
              label="Data"
              :max="maxDate"
              :error="form.errors.date"
              required
            />

            <BaseTextarea
              v-model="form.remarks"
              class="md:col-span-2"
              rows="3"
              label="Observações"
              :error="form.errors.remarks"
              placeholder="Observação adicional sobre o consumo, ensaio, lote ou desvio"
            />
          </div>
        </article>

        <aside class="space-y-4">
          <article class="ds-panel overflow-hidden">
            <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
              <h2 class="ds-heading text-base">Validação de stock</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Confirme disponibilidade antes de submeter.</p>
            </div>

            <div class="grid gap-3 p-5">
              <div v-for="item in stockReview" :key="item.label" class="ds-card p-4">
                <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ item.label }}</p>
                <p class="mt-2 text-xl font-bold" :class="item.valueClass">{{ item.value }}</p>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ item.caption }}</p>
              </div>
            </div>
          </article>

          <article v-if="selectedReagent" class="ds-panel overflow-hidden">
            <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
              <h2 class="ds-heading text-base">Reagente seleccionado</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Identificação e validade do material.</p>
            </div>

            <dl class="divide-y divide-[color:var(--ds-border)]">
              <div v-for="field in reagentFields" :key="field.label" class="flex items-start justify-between gap-4 px-5 py-3 text-sm">
                <dt class="font-semibold text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                <dd class="text-right font-bold" :class="field.valueClass">{{ field.value }}</dd>
              </div>
            </dl>
          </article>
        </aside>
      </section>

      <section v-if="currentStock < Number(form.quantity_used || 0) || isReagentExpired" class="grid gap-3 md:grid-cols-2">
        <article v-if="currentStock < Number(form.quantity_used || 0)" class="ds-card border-l-4 border-rose-500 p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-rose-700 dark:text-rose-300" />
            <div>
              <h3 class="text-sm font-bold text-[color:var(--ds-text)]">Stock insuficiente</h3>
              <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">
                Disponível: {{ formatQuantity(currentStock) }} · Requisitado: {{ formatQuantity(form.quantity_used) }}.
              </p>
            </div>
          </div>
        </article>

        <article v-if="isReagentExpired" class="ds-card border-l-4 border-amber-500 p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-amber-700 dark:text-amber-300" />
            <div>
              <h3 class="text-sm font-bold text-[color:var(--ds-text)]">Reagente vencido</h3>
              <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">
                Validade: {{ formatDate(selectedReagent.reagent_expiry_date) }}. Documente a decisão técnica antes de continuar.
              </p>
            </div>
          </div>
        </article>
      </section>

      <section class="ds-command-surface p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm font-semibold text-[color:var(--ds-text-soft)]">
            <span v-if="form.reagent_id && form.warehouse_id">
              Saída a partir de <span class="font-bold text-[color:var(--ds-text)]">{{ getWarehouseName(form.warehouse_id) }}</span>.
            </span>
            <span v-else>Seleccione reagente e armazém para validar stock.</span>
          </p>

          <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" class="ds-button ds-button-secondary" @click="goBack">
              Cancelar
            </button>
            <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormValid">
              <CheckCircleIcon v-if="!form.processing" class="h-4 w-4" />
              <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
              {{ form.processing ? 'A processar...' : 'Registrar consumo' }}
            </button>
          </div>
        </div>
      </section>
    </form>

    <confirm-dialog
      v-if="showSubmitConfirmation"
      title="Registrar consumo de reagente"
      description="Confirme a saída controlada antes de abater stock do armazém selecionado."
      confirm="Registrar consumo"
      cancel="Rever dados"
      variant="warning"
      @confirmed="confirmSubmit"
      @canceled="showSubmitConfirmation = false"
    >
      <div class="mt-4 grid gap-3 text-left sm:grid-cols-3">
        <div class="rounded-lg border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-3">
          <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Reagente</p>
          <p class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ selectedReagent?.name || 'N/A' }}</p>
        </div>
        <div class="rounded-lg border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-3">
          <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">A consumir</p>
          <p class="mt-1 text-sm font-bold text-rose-700 dark:text-rose-300">{{ formatQuantity(form.quantity_used) }}</p>
        </div>
        <div class="rounded-lg border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-3">
          <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Restante</p>
          <p class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatQuantity(remainingAfterConsumption) }}</p>
        </div>
      </div>
    </confirm-dialog>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BeakerIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
  reagents: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  users: {
    type: Array,
    default: () => [],
  },
})

const quantityFormatter = new Intl.NumberFormat('pt-PT', {
  maximumFractionDigits: 2,
})

const form = useForm({
  reagent_id: '',
  warehouse_id: '',
  quantity_used: 0.01,
  used_by: '',
  date: new Date().toISOString().split('T')[0],
  remarks: '',
})

const showSubmitConfirmation = ref(false)
const maxDate = new Date().toISOString().split('T')[0]

const selectedReagent = computed(() => {
  return props.reagents.find((reagent) => reagent.id == form.reagent_id)
})

const availableWarehouses = computed(() => {
  if (!form.reagent_id) {
    return props.warehouses
  }

  const reagent = selectedReagent.value

  if (!reagent?.inventory) {
    return props.warehouses
  }

  return props.warehouses
    .map((warehouse) => {
      const inventory = reagent.inventory.find((stockItem) => stockItem.warehouse_id == warehouse.id)

      return {
        ...warehouse,
        available_stock: inventory ? inventory.qty_available : 0,
      }
    })
    .filter((warehouse) => warehouse.available_stock > 0)
})

const currentStock = computed(() => {
  if (!form.reagent_id || !form.warehouse_id) {
    return 0
  }

  const reagent = selectedReagent.value

  if (!reagent?.inventory) {
    return 0
  }

  const inventory = reagent.inventory.find((stockItem) => stockItem.warehouse_id == form.warehouse_id)

  return Number(inventory?.qty_available ?? 0)
})

const maxQuantity = computed(() => {
  return currentStock.value
})

const remainingAfterConsumption = computed(() => {
  return Math.max(0, currentStock.value - Number(form.quantity_used || 0))
})

const isReagentExpired = computed(() => {
  if (!selectedReagent.value?.reagent_expiry_date) {
    return false
  }

  return new Date(selectedReagent.value.reagent_expiry_date) < new Date()
})

const isFormValid = computed(() => {
  return Boolean(
    form.reagent_id
    && form.warehouse_id
    && Number(form.quantity_used) > 0
    && Number(form.quantity_used) <= maxQuantity.value
    && form.used_by
    && form.date
    && !form.processing,
  )
})

const workspaceMetrics = computed(() => [
  {
    label: 'Reagentes',
    value: formatQuantity(props.reagents.length),
    caption: 'Activos para consumo',
    dotClass: 'lims-status-dot-instrument',
  },
  {
    label: 'Armazéns',
    value: formatQuantity(props.warehouses.length),
    caption: 'Com stock operacional',
    dotClass: 'lims-status-dot-release',
  },
  {
    label: 'Disponível',
    value: formatQuantity(currentStock.value),
    caption: 'No armazém seleccionado',
    dotClass: currentStock.value > 0 ? 'lims-status-dot-release' : 'lims-status-dot-critical',
  },
  {
    label: 'Restante',
    value: formatQuantity(remainingAfterConsumption.value),
    caption: 'Após submissão',
    dotClass: remainingAfterConsumption.value > 0 ? 'lims-status-dot-release' : 'lims-status-dot-hold',
  },
])

const stockReview = computed(() => [
  {
    label: 'Stock actual',
    value: formatQuantity(currentStock.value),
    caption: 'Quantidade disponível no armazém',
    valueClass: currentStock.value > 0 ? 'text-[color:var(--ds-text)]' : 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'A consumir',
    value: formatQuantity(form.quantity_used),
    caption: 'Quantidade que será abatida',
    valueClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Restante',
    value: formatQuantity(remainingAfterConsumption.value),
    caption: 'Saldo estimado após consumo',
    valueClass: remainingAfterConsumption.value > 0 ? 'text-[color:var(--ds-text)]' : 'text-amber-700 dark:text-amber-300',
  },
])

const reagentFields = computed(() => {
  if (!selectedReagent.value) {
    return []
  }

  return [
    ['Nome', selectedReagent.value.name],
    ['Código', selectedReagent.value.code],
    ['Categoria', selectedReagent.value.category?.name || 'N/A'],
    ['Unidade', selectedReagent.value.unit?.code || 'N/A'],
    [
      'Validade',
      selectedReagent.value.reagent_expiry_date ? formatDate(selectedReagent.value.reagent_expiry_date) : 'N/A',
      isReagentExpired.value ? 'text-rose-700 dark:text-rose-300' : 'text-[color:var(--ds-text)]',
    ],
  ].map(([label, value, valueClass = 'text-[color:var(--ds-text)]']) => ({ label, value, valueClass }))
})

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return quantityFormatter.format(numericValue)
}

function getWarehouseName(id) {
  const warehouse = props.warehouses.find((warehouseItem) => warehouseItem.id == id)

  return warehouse ? warehouse.name : 'N/A'
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

function onReagentChange() {
  form.warehouse_id = ''
  form.quantity_used = 0.01

  if (form.reagent_id && props.users.length > 0) {
    form.used_by = props.users[0].name
  }
}

function onWarehouseChange() {
  if (form.warehouse_id) {
    form.quantity_used = Math.min(Number(form.quantity_used || 0), maxQuantity.value)
  }
}

function submit() {
  if (!isFormValid.value) {
    return
  }

  showSubmitConfirmation.value = true
}

function confirmSubmit() {
  showSubmitConfirmation.value = false

  form.post(route('vap-inventory.reagents.consumption.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      router.visit(route('vap-inventory.reagents.consumption.index'), {
        preserveScroll: true,
      })
    },
  })
}

function goBack() {
  router.visit(route('vap-inventory.reagents.consumption.index'))
}

onMounted(() => {
  if (props.users.length > 0) {
    form.used_by = props.users[0].name
  }
})
</script>
