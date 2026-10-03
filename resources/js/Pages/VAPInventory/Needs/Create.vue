<template>
  <form class="pl-page" data-template="form" @submit.prevent="submit">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Necessidades', url: route('vap-inventory.needs.index') }, { title: 'Nova' }]"
      title="Nova necessidade"
      lede="Reúna materiais, reagentes e equipamentos numa requisição com âmbito, prazo, armazém de destino e estimativa. A submissão abre o fluxo de aprovação."
    />

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Âmbito e prazo</h2>
        <p>O laboratório disponível acompanha o departamento seleccionado, para evitar requisições fora do âmbito organizacional.</p>
      </header>
      <div class="pl-form-grid">
        <BaseSelect
          id="need-department"
          v-model="form.department_id"
          label="Departamento"
          :error="form.errors.department_id"
          required
        >
          <option value="">Seleccione o departamento</option>
          <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
        </BaseSelect>

        <BaseSelect
          id="need-lab"
          v-model="form.lab_id"
          label="Laboratório"
          hint="Opcional; filtrado pelo departamento."
          :error="form.errors.lab_id"
          :disabled="!form.department_id"
        >
          <option value="">Sem laboratório específico</option>
          <option v-for="lab in filteredLabs" :key="lab.id" :value="lab.id">{{ lab.name }}</option>
        </BaseSelect>

        <BaseInput
          id="need-date"
          v-model="form.needed_by_date"
          type="date"
          label="Necessário até"
          :min="minimumNeedDate"
          :error="form.errors.needed_by_date"
        />

        <div class="ds-field-group">
          <span class="ds-field-label">Urgência calculada</span>
          <p class="mt-1.5 flex items-center gap-3 text-sm">
            <StatusChip :tone="urgencyTone">{{ urgencyLabel }}</StatusChip>
            <span class="text-[var(--pl-muted)]">Pela data necessária.</span>
          </p>
        </div>

        <div class="pl-span-2">
          <BaseTextarea
            id="need-justification"
            v-model="form.justification"
            label="Justificação operacional"
            :rows="4"
            placeholder="Explique o impacto no serviço, a finalidade e eventuais restrições técnicas"
            :error="form.errors.justification"
          />
        </div>
      </div>
    </section>

    <section class="pl-form-section">
      <header>
        <h2 class="pl-d3">Itens solicitados</h2>
        <p>Especificação, quantidade, armazém de destino e preço unitário estimado. As quantidades aceitam até quatro casas decimais, na unidade de cada item.</p>
      </header>
      <div class="grid min-w-0 gap-5">
        <article v-for="(item, index) in form.items" :key="item.client_id" class="pl-panel" :aria-labelledby="`need-item-title-${item.client_id}`">
          <div class="pl-panel-head">
            <h3 :id="`need-item-title-${item.client_id}`" class="pl-k min-w-0 truncate">Item {{ index + 1 }} · {{ inventoryItemName(item.inventory_item_id) }}</h3>
            <button
              v-if="form.items.length > 1"
              type="button"
              class="ds-table-action ds-table-action-danger"
              :aria-label="`Remover item ${index + 1}`"
              @click="removeItem(index)"
            >
              <TrashIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>

          <div class="pl-form-grid p-4">
            <div class="ds-field-group pl-span-2">
              <comboboxEnhanced
                :model-value="item.inventory_item_option"
                :has-error="Boolean(form.errors[`items.${index}.inventory_item_id`])"
                :options="itemOptions"
                title-label="Item"
                placeholder="Pesquisar por nome ou código"
                @update:model-value="selectInventoryItem(item, $event)"
              />
              <p v-if="form.errors[`items.${index}.inventory_item_id`]" class="ds-field-error" role="alert">
                {{ form.errors[`items.${index}.inventory_item_id`] }}
              </p>
            </div>

            <BaseInput
              :id="`need-quantity-${index}`"
              v-model="item.quantity_requested"
              type="number"
              min="0.0001"
              step="0.0001"
              :label="`Quantidade${selectedItemUnit(item.inventory_item_id) ? ` (${selectedItemUnit(item.inventory_item_id)})` : ''}`"
              :error="form.errors[`items.${index}.quantity_requested`]"
              required
            />

            <BaseSelect
              :id="`need-warehouse-${index}`"
              v-model="item.warehouse_id"
              label="Armazém de destino"
              :error="form.errors[`items.${index}.warehouse_id`]"
            >
              <option value="">A definir</option>
              <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
            </BaseSelect>

            <BaseInput
              :id="`need-price-${index}`"
              v-model="item.estimated_unit_price"
              type="number"
              min="0"
              step="0.01"
              label="Preço unitário estimado"
              :error="form.errors[`items.${index}.estimated_unit_price`]"
            />

            <BaseInput
              :id="`need-notes-${index}`"
              v-model="item.notes"
              label="Especificação e notas"
              placeholder="Marca, referência, pureza, tolerância ou armazenamento"
              :error="form.errors[`items.${index}.notes`]"
            />
          </div>
        </article>

        <p v-if="form.errors.items" class="ds-field-error" role="alert">{{ form.errors.items }}</p>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="addItem">
            <PlusIcon class="h-4 w-4" aria-hidden="true" />
            Adicionar item
          </button>
          <p class="pl-k pl-muted">
            <span class="pl-num">{{ itemCount }}</span> {{ itemCount === 1 ? 'item' : 'itens' }} · <span class="pl-num">{{ validQuantityCount }}/{{ form.items.length }}</span> quantidades válidas · estimativa <span class="pl-num">{{ formatMoney(estimatedValue) }}</span>
          </p>
        </div>
      </div>
    </section>

    <p v-if="form.hasErrors" class="pl-banner pl-banner-bad text-sm" role="alert">Reveja os campos assinalados antes de submeter.</p>

    <NextStepBar>
      {{ nextStepMessage }}
      <template #actions>
        <Link :href="route('vap-inventory.needs.index')" class="ds-button ds-button-quiet">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !isFormReady">
          {{ form.processing ? 'A submeter…' : 'Submeter necessidade' }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>

<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Plus as PlusIcon, Trash2 as TrashIcon } from '@lucide/vue'

defineOptions({ layout: Layout })

/**
 * A new laboratory need (Plano form). It is submitted straight into approval; the
 * approver later confirms the quantities and converts it into a purchase order.
 */
const props = defineProps({
  departments: {
    type: Array,
    default: () => [],
  },
  labs: {
    type: Array,
    default: () => [],
  },
  items: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
})

let nextClientId = 2
const minimumNeedDate = new Date().toISOString().split('T')[0]

const form = useForm({
  department_id: '',
  lab_id: '',
  needed_by_date: '',
  justification: '',
  items: [newNeedItem(1)],
})

const filteredLabs = computed(() => {
  if (!form.department_id) {
    return []
  }

  return props.labs.filter((lab) => String(lab.department_id) === String(form.department_id))
})

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.code ? ` · ${item.code}` : ''}`,
})))
const itemCount = computed(() => form.items.filter((item) => item.inventory_item_id).length)
const validQuantityCount = computed(() => form.items.filter((item) => isValidQuantity(item.quantity_requested)).length)
const estimatedValue = computed(() => form.items.reduce((total, item) => (
  total + (item.inventory_item_id
    ? Math.max(Number(item.quantity_requested || 0), 0) * Math.max(Number(item.estimated_unit_price || 0), 0)
    : 0)
), 0))

const isFormReady = computed(() => (
  Boolean(form.department_id)
  && form.items.length > 0
  && form.items.every((item) => Boolean(item.inventory_item_id) && isValidQuantity(item.quantity_requested))
))

const nextStepMessage = computed(() => {
  if (!form.department_id) {
    return 'Seleccione o departamento responsável.'
  }

  if (itemCount.value < form.items.length) {
    return `Identifique o item em ${form.items.length - itemCount.value} ${form.items.length - itemCount.value === 1 ? 'linha' : 'linhas'}.`
  }

  if (validQuantityCount.value < form.items.length) {
    return `${validQuantityCount.value} de ${form.items.length} linhas com quantidade válida. Corrija as restantes.`
  }

  return `${itemCount.value} ${itemCount.value === 1 ? 'item pronto' : 'itens prontos'} · estimativa ${formatMoney(estimatedValue.value)}. A submissão abre o fluxo de aprovação.`
})

const urgencyLabel = computed(() => {
  if (!form.needed_by_date) {
    return 'Sem prazo definido'
  }

  const today = new Date(minimumNeedDate)
  const needed = new Date(`${form.needed_by_date}T00:00:00`)
  const days = Math.ceil((needed.getTime() - today.getTime()) / 86400000)

  if (days < 0) {
    return 'Prazo ultrapassado'
  }

  if (days <= 3) {
    return 'Urgente'
  }

  if (days <= 10) {
    return 'Próxima'
  }

  return 'Planeada'
})

const urgencyTone = computed(() => ({
  'Prazo ultrapassado': 'bad',
  Urgente: 'bad',
  Próxima: 'wait',
  Planeada: 'ok',
}[urgencyLabel.value] ?? 'neutral'))

function newNeedItem(clientId = nextClientId++) {
  return {
    client_id: clientId,
    inventory_item_id: '',
    inventory_item_option: null,
    warehouse_id: '',
    quantity_requested: '1',
    estimated_unit_price: '',
    notes: '',
  }
}

function inventoryItemName(itemId) {
  const item = props.items.find((candidate) => String(candidate.id) === String(itemId))

  return item?.name || 'por seleccionar'
}

function selectedItemUnit(itemId) {
  const item = props.items.find((candidate) => String(candidate.id) === String(itemId))

  return item?.unit?.code || item?.unit?.description || ''
}

function isValidQuantity(value) {
  return /^(?:0|[1-9]\d*)(?:\.\d{1,4})?$/.test(String(value)) && Number(value) >= 0.0001
}

function selectInventoryItem(item, option) {
  item.inventory_item_option = option
  item.inventory_item_id = option?.value || ''
}

function addItem() {
  form.items.push(newNeedItem())
}

function removeItem(index) {
  if (form.items.length <= 1) {
    return
  }

  form.items.splice(index, 1)
  form.clearErrors()
}

function formatMoney(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

function submit() {
  if (!isFormReady.value) {
    return
  }

  form.transform((data) => ({
    ...data,
    items: data.items.map(({ client_id, inventory_item_option, ...item }) => ({
      ...item,
      quantity_requested: item.quantity_requested,
      estimated_unit_price: item.estimated_unit_price === '' ? null : Number(item.estimated_unit_price),
    })),
  })).post(route('vap-inventory.needs.store'), {
    preserveScroll: true,
  })
}

watch(() => form.department_id, () => {
  if (!form.lab_id) {
    return
  }

  const selectedLabStillMatchesDepartment = filteredLabs.value.some((lab) => String(lab.id) === String(form.lab_id))

  if (!selectedLabStillMatchesDepartment) {
    form.lab_id = ''
  }
})
</script>
