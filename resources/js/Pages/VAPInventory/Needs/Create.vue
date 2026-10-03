<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Procurement intake</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument"></span>
              Submissão para aprovação
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ClipboardDocumentListIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Nova necessidade operacional</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Consolide materiais, reagentes e equipamentos numa requisição com âmbito, prazo, armazém alvo e estimativa financeira.
              </p>
            </div>
          </div>
        </div>

        <Link :href="route('vap-inventory.needs.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar à fila
        </Link>
      </div>
    </section>

    <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]" @submit.prevent="submit">
      <div class="space-y-6">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Contexto da requisição</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Departamento, laboratório e prazo</h2>
          </div>

          <div class="grid gap-6 p-5 xl:grid-cols-[15rem_minmax(0,1fr)]">
            <div>
              <div class="flex items-center gap-2 text-[var(--ds-text)]">
                <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
                <h3 class="font-black">Âmbito responsável</h3>
              </div>
              <p class="mt-2 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                O laboratório disponível acompanha o departamento seleccionado para evitar requisições fora do âmbito organizacional.
              </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
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

              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Urgência calculada</p>
                <div class="mt-2 flex items-center gap-2">
                  <span :class="['h-2 w-2 rounded-full', urgencyTone]"></span>
                  <p class="text-sm font-black text-[var(--ds-text)]">{{ urgencyLabel }}</p>
                </div>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Baseada na data necessária.</p>
              </div>

              <div class="md:col-span-2">
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
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Âmbito de aquisição</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Itens solicitados</h2>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Especificação, quantidade, destino e preço unitário estimado.</p>
            </div>
            <button type="button" class="ds-button ds-button-secondary" @click="addItem">
              <PlusIcon class="h-4 w-4" />
              Adicionar item
            </button>
          </div>

          <div class="divide-y divide-[var(--ds-border)]">
            <article v-for="(item, index) in form.items" :key="item.client_id" class="bg-[var(--ds-panel-raised)] p-5">
              <div class="mb-4 flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                  <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
                    <CubeIcon class="h-4 w-4" />
                  </span>
                  <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Item {{ index + 1 }}</p>
                    <h3 class="mt-1 truncate text-sm font-black text-[var(--ds-text)]">{{ inventoryItemName(item.inventory_item_id) }}</h3>
                  </div>
                </div>

                <button
                  v-if="form.items.length > 1"
                  type="button"
                  class="ds-table-action ds-table-action-danger"
                  :title="`Remover item ${index + 1}`"
                  @click="removeItem(index)"
                >
                  <TrashIcon class="h-4 w-4" />
                  <span class="sr-only">Remover item {{ index + 1 }}</span>
                </button>
              </div>

              <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="ds-field-group xl:col-span-2">
                  <label class="ds-field-label">Item <span class="ds-field-required">*</span></label>
                  <comboboxEnhanced
                    :model-value="item.inventory_item_option"
                    :has-error="Boolean(form.errors[`items.${index}.inventory_item_id`])"
                    :options="itemOptions"
                    placeholder="Pesquisar por nome ou código"
                    @update:model-value="selectInventoryItem(item, $event)"
                  />
                  <p v-if="form.errors[`items.${index}.inventory_item_id`]" class="ds-field-error">
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
                  label="Armazém alvo"
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

                <div class="md:col-span-2 xl:col-span-3">
                  <BaseInput
                    :id="`need-notes-${index}`"
                    v-model="item.notes"
                    label="Especificação e notas"
                    placeholder="Marca, referência, pureza, tolerância ou condição de armazenamento"
                    :error="form.errors[`items.${index}.notes`]"
                  />
                </div>
              </div>
            </article>
          </div>

          <div v-if="form.errors.items" class="border-t border-[var(--ds-border)] px-5 py-3">
            <p class="ds-field-error">{{ form.errors.items }}</p>
          </div>

          <div class="ds-table-summary px-5 py-4">
            <p class="text-sm font-bold text-[var(--ds-text)]">{{ itemCount }} itens · {{ validQuantityCount }} quantidades verificadas</p>
            <p class="text-sm font-black text-[var(--ds-text)]">Estimativa: {{ formatMoney(estimatedValue) }}</p>
          </div>
        </section>
      </div>

      <aside>
        <section class="ds-command-surface p-5 xl:sticky xl:top-20">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Resumo da submissão</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Prontidão da requisição</h2>

          <ol class="mt-5 space-y-0">
            <li v-for="(step, index) in readinessSteps" :key="step.label" class="relative flex gap-3 pb-6 last:pb-0">
              <span v-if="index < readinessSteps.length - 1" class="absolute left-[0.4375rem] top-4 h-[calc(100%-0.5rem)] w-px bg-[var(--ds-border)]"></span>
              <span :class="['relative mt-1 h-4 w-4 shrink-0 rounded-full border-4 border-[var(--ds-panel-raised)]', step.complete ? 'bg-emerald-600' : 'bg-[var(--ds-border-strong)]']"></span>
              <div>
                <p class="text-sm font-black text-[var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ step.detail }}</p>
              </div>
            </li>
          </ol>

          <dl class="mt-6 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Departamento</dt>
              <dd class="max-w-[11rem] text-right text-sm font-black text-[var(--ds-text)]">{{ selectedDepartment?.name || 'Por seleccionar' }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Laboratório</dt>
              <dd class="max-w-[11rem] text-right text-sm font-black text-[var(--ds-text)]">{{ selectedLab?.name || 'Não definido' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Itens</dt>
              <dd class="text-lg font-black text-[var(--ds-text)]">{{ itemCount }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Quantidades válidas</dt>
              <dd class="text-lg font-black text-[var(--ds-text)]">{{ validQuantityCount }}/{{ form.items.length }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Estimativa</dt>
              <dd class="text-right text-sm font-black text-[var(--ds-text)]">{{ formatMoney(estimatedValue) }}</dd>
            </div>
          </dl>

          <div v-if="form.hasErrors" class="mt-5 rounded-lg border border-rose-300/60 bg-rose-50 p-3 text-sm font-bold text-rose-800 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200">
            Reveja os campos assinalados antes de submeter.
          </div>

          <button type="submit" class="ds-button ds-button-primary mt-5 w-full" :disabled="form.processing || !isFormReady">
            <PaperAirplaneIcon class="h-4 w-4" />
            {{ form.processing ? 'A submeter...' : 'Submeter necessidade' }}
          </button>
          <Link :href="route('vap-inventory.needs.index')" class="ds-button ds-button-secondary mt-2 w-full">
            Cancelar
          </Link>
        </section>
      </aside>
    </form>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  ArrowLeftIcon,
  BuildingOffice2Icon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PaperAirplaneIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })

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
  if (!form.department_id) return []
  return props.labs.filter((lab) => String(lab.department_id) === String(form.department_id))
})

const selectedDepartment = computed(() => props.departments.find((department) => String(department.id) === String(form.department_id)) || null)
const selectedLab = computed(() => props.labs.find((lab) => String(lab.id) === String(form.lab_id)) || null)
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

const urgencyLabel = computed(() => {
  if (!form.needed_by_date) return 'Sem prazo definido'
  const today = new Date(minimumNeedDate)
  const needed = new Date(`${form.needed_by_date}T00:00:00`)
  const days = Math.ceil((needed.getTime() - today.getTime()) / 86400000)
  if (days < 0) return 'Prazo ultrapassado'
  if (days <= 3) return 'Urgente'
  if (days <= 10) return 'Próxima'
  return 'Planeada'
})

const urgencyTone = computed(() => ({
  'Prazo ultrapassado': 'bg-rose-600',
  'Urgente': 'bg-rose-600',
  'Próxima': 'bg-amber-500',
  'Planeada': 'bg-emerald-600',
  'Sem prazo definido': 'bg-[var(--ds-border-strong)]',
}[urgencyLabel.value]))

const readinessSteps = computed(() => [
  {
    label: 'Âmbito definido',
    detail: selectedDepartment.value?.name || 'Seleccione o departamento responsável.',
    complete: Boolean(form.department_id),
  },
  {
    label: 'Itens identificados',
    detail: itemCount.value ? `${itemCount.value} itens válidos na requisição.` : 'Adicione pelo menos um item.',
    complete: itemCount.value > 0 && itemCount.value === form.items.length,
  },
  {
    label: 'Quantidades verificadas',
    detail: `${validQuantityCount.value} de ${form.items.length} linhas com quantidade válida.`,
    complete: itemCount.value > 0 && validQuantityCount.value === form.items.length,
  },
  {
    label: 'Pronta para aprovação',
    detail: isFormReady.value ? 'A submissão abrirá o fluxo de aprovação.' : 'Complete os campos obrigatórios.',
    complete: isFormReady.value,
  },
])

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
  return item?.name || 'Item por seleccionar'
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
  if (form.items.length <= 1) return
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
  if (!isFormReady.value) return

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
  if (!form.lab_id) return
  const selectedLabStillMatchesDepartment = filteredLabs.value.some((lab) => String(lab.id) === String(form.lab_id))
  if (!selectedLabStillMatchesDepartment) form.lab_id = ''
})
</script>
