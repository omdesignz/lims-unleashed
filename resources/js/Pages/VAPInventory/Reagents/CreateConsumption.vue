<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import { router, useForm } from '@inertiajs/vue3'
import { TriangleAlert as ExclamationTriangleIcon } from '@lucide/vue'
import { computed, ref } from 'vue'

/**
 * New reagent consumption (Plano form). The quantity is taken from one warehouse
 * that holds the reagent; the technician defaults to the signed-in operator and
 * the submission is confirmed before stock is written off.
 */
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
  backUrl: {
    type: String,
    default: '',
  },
})

const quantityFormatter = new Intl.NumberFormat('pt-PT', {
  maximumFractionDigits: 4,
})

const form = useForm({
  reagent_id: '',
  warehouse_id: '',
  quantity_used: '0.0001',
  used_by: props.users[0]?.name ?? '',
  date: new Date().toISOString().split('T')[0],
  remarks: '',
})

const showSubmitConfirmation = ref(false)
const maxDate = new Date().toISOString().split('T')[0]
const registerUrl = route('vap-inventory.reagents.consumption.index')

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

const exceedsStock = computed(() => Boolean(form.warehouse_id) && currentStock.value < Number(form.quantity_used || 0))

const reagentHint = computed(() => {
  if (!selectedReagent.value) {
    return 'Só reagentes activos com existências no laboratório.'
  }

  return [
    selectedReagent.value.category?.name,
    selectedReagent.value.unit?.code,
    selectedReagent.value.reagent_expiry_date ? `Validade ${formatDate(selectedReagent.value.reagent_expiry_date)}` : 'Sem validade registada',
  ].filter(Boolean).join(' · ')
})

const nextStep = computed(() => {
  if (!form.reagent_id || !form.warehouse_id) {
    return 'Seleccione reagente e armazém para validar as existências.'
  }

  if (exceedsStock.value) {
    return `Só há ${formatQuantity(currentStock.value)} em ${getWarehouseName(form.warehouse_id)}. Reduza a quantidade.`
  }

  return `Saída de ${formatQuantity(form.quantity_used)} a partir de ${getWarehouseName(form.warehouse_id)}; ficam ${formatQuantity(remainingAfterConsumption.value)}.`
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
    },
  })
}

function goBack() {
  router.visit(props.backUrl || route('dashboard'))
}
</script>

<template>
  <form class="pl-page" data-template="form" @submit.prevent="submit">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Consumo de reagentes', url: backUrl === registerUrl ? registerUrl : undefined }, { title: 'Novo' }]"
      title="Registar consumo de reagente"
      lede="Registe a saída a partir de um armazém com existências, com responsável, data e observações. A saída é confirmada antes de abater as existências."
    />

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Reagente e origem</h2>
        <p>O armazém lista apenas posições com saldo do reagente. A quantidade aceita até quatro casas decimais.</p>
      </header>
      <div class="pl-form-grid">
        <BaseSelect
          v-model="form.reagent_id"
          label="Reagente"
          :error="form.errors.reagent_id"
          :hint="reagentHint"
          required
          @change="onReagentChange"
        >
          <option value="">Seleccione um reagente</option>
          <option v-for="reagent in reagents" :key="reagent.id" :value="reagent.id">
            {{ reagent.name }} ({{ reagent.code }})
            <template v-if="reagent.total_stock !== undefined">
              · Existências totais: {{ formatQuantity(reagent.total_stock) }}
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

        <BaseInput
          v-model="form.quantity_used"
          type="number"
          label="Quantidade usada"
          :min="0.0001"
          :max="maxQuantity"
          :step="0.0001"
          :error="form.errors.quantity_used"
          :hint="`Máximo: ${formatQuantity(maxQuantity)} · restam ${formatQuantity(remainingAfterConsumption)} após a saída`"
          placeholder="Digite a quantidade"
          required
        />

        <div v-if="exceedsStock || isReagentExpired" class="grid content-start gap-3">
          <p v-if="exceedsStock" class="pl-banner pl-banner-bad text-sm" role="alert">
            <ExclamationTriangleIcon aria-hidden="true" />
            <span>Existências insuficientes. Disponível: {{ formatQuantity(currentStock) }} · requisitado: {{ formatQuantity(form.quantity_used) }}.</span>
          </p>
          <p v-if="isReagentExpired" class="pl-banner pl-banner-warn text-sm">
            <ExclamationTriangleIcon aria-hidden="true" />
            <span>Reagente vencido em {{ formatDate(selectedReagent.reagent_expiry_date) }}. Documente a decisão técnica antes de continuar.</span>
          </p>
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Responsável e data</h2>
        <p>Por omissão, o responsável é o operador com sessão iniciada. A data não pode ser futura.</p>
      </header>
      <div class="pl-form-grid">
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

        <div class="pl-span-2">
          <BaseTextarea
            v-model="form.remarks"
            rows="3"
            label="Observações"
            :error="form.errors.remarks"
            placeholder="Observação adicional sobre o consumo, ensaio, lote ou desvio"
          />
        </div>
      </div>
    </section>

    <NextStepBar>
      {{ nextStep }}
      <template #actions>
        <button type="button" class="ds-button ds-button-quiet" @click="goBack">Cancelar</button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormValid">
          {{ form.processing ? 'A processar…' : 'Registar consumo' }}
        </button>
      </template>
    </NextStepBar>

    <confirm-dialog
      v-if="showSubmitConfirmation"
      title="Registar consumo de reagente"
      description="Confirme a saída controlada antes de abater existências do armazém seleccionado."
      confirm="Registar consumo"
      cancel="Rever dados"
      variant="warning"
      @confirmed="confirmSubmit"
      @canceled="showSubmitConfirmation = false"
    >
      <dl class="pl-panel pl-facts mt-4 text-left">
        <div class="pl-fact"><dt>Reagente</dt><dd>{{ selectedReagent?.name || 'N/A' }}</dd></div>
        <div class="pl-fact"><dt>A consumir</dt><dd class="pl-num">{{ formatQuantity(form.quantity_used) }}</dd></div>
        <div class="pl-fact"><dt>Restante</dt><dd class="pl-num">{{ formatQuantity(remainingAfterConsumption) }}</dd></div>
      </dl>
    </confirm-dialog>
  </form>
</template>
