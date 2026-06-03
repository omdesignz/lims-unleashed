<template>
  <div class="inventory-order-form-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Compras e abastecimento"
      :title="`Modificar pedido #${order.seq || order.id}`"
      description="Atualize fornecedor, estado, itens e armazéns de destino preservando bloqueios de itens já recebidos."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            statusClass
          ]">
            {{ formatStatus(order.status) }}
          </span>
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </button>
        </div>
      </template>
    </ModuleHero>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- ORDER DETAILS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
          Detalhes do Pedido
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Supplier -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Fornecedor
              <span class="text-red-500">*</span>
            </label>
            <comboboxEnhanced
              v-model="selectedSupplierOption"
              :has-error="form.errors.supplier_id"
              :options="supplierOptions"
              placeholder="Pesquisar fornecedor"
              :disabled="!canEditSupplier"
            />
            <p v-if="form.errors.supplier_id" class="text-xs text-red-600">
              {{ form.errors.supplier_id }}
            </p>
            <div v-if="selectedSupplierAssessment" :class="[
              'rounded-xl border px-4 py-3 text-sm',
              supplierAssessmentPanelClass
            ]">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold">Avaliação ativa</span>
                <span class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold">{{ supplierAssessmentStatus }}</span>
                <span class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold">{{ supplierAssessmentRisk }}</span>
              </div>
              <p class="mt-2">Score {{ selectedSupplierAssessment.total_score }}/100 · Revisão {{ supplierAssessmentReviewLabel }}</p>
            </div>
            <div v-else-if="form.supplier_id" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
              Este fornecedor ainda não tem avaliação formal registada.
            </div>
          </div>

          <!-- Order Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data do Pedido
              <span class="text-red-500">*</span>
            </label>
            <input
              type="date"
              v-model="form.date"
              :max="maxDate"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.date ? 'border-red-300' : 'border-gray-300'
              ]"
              required
            />
            <p v-if="form.errors.date" class="text-xs text-red-600">
              {{ form.errors.date }}
            </p>
          </div>

          <!-- Reference -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Número de Referência
            </label>
            <input
              type="text"
              v-model="form.reference"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.reference ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Ex.: PC-2026-001"
            />
            <p v-if="form.errors.reference" class="text-xs text-red-600">
              {{ form.errors.reference }}
            </p>
          </div>

          <!-- Status -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Estado
            </label>
            <comboboxEnhanced
              v-model="selectedStatusOption"
              :has-error="form.errors.status"
              :options="statusOptions"
              placeholder="Selecionar estado"
              :disabled="!canEditStatus"
            />
            <p v-if="form.errors.status" class="text-xs text-red-600">
              {{ form.errors.status }}
            </p>
          </div>

          <!-- Observations -->
          <div class="space-y-2 md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <textarea
              v-model="form.obs"
              rows="3"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.obs ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Qualquer instrução especial ou nota..."
            ></textarea>
            <p v-if="form.errors.obs" class="text-xs text-red-600">
              {{ form.errors.obs }}
            </p>
          </div>
        </div>
      </div>

      <!-- ORDER ITEMS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <CubeIcon class="h-5 w-5 text-blue-900" />
              Itens do Pedido
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ form.order_items.length }} itens)
              </span>
            </h2>
            <button 
              v-if="canEditItems"
              @click="addOrderItem"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              Adicionar Item
            </button>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="form.order_items.length === 0" class="p-12 text-center">
          <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            Nenhum item no pedido
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            Adicione itens para actualizar o pedido de compra
          </p>
          <button 
            v-if="canEditItems"
            @click="addOrderItem"
            type="button"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PlusCircleIcon class="h-5 w-5" />
            Adicionar Primeiro Item
          </button>
        </div>

        <!-- ITEMS LIST -->
        <div v-else class="divide-y divide-gray-200">
          <!-- ITEM ROW TEMPLATE -->
          <div 
            v-for="(item, index) in form.order_items"
            :key="index"
            class="group px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Item Selection -->
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Item
                    <span class="text-red-500">*</span>
                  </label>
                  <comboboxEnhanced
                    v-model="item.item_obj"
                    :has-error="itemErrors[index]?.item_id"
                    :options="itemOptions"
                    placeholder="Pesquisar item"
                    :disabled="!canEditItems || item.received_qty > 0"
                    @update:modelValue="onItemChange(index)"
                  />
                  <p v-if="itemErrors[index]?.item_id" class="text-xs text-red-600">
                    {{ itemErrors[index].item_id }}
                  </p>
                  <div v-if="item.received_qty > 0" class="text-xs text-green-600">
                    Recebido: {{ item.received_qty }}
                  </div>
                </div>

                <!-- Quantity -->
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Quantidade
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    v-model="item.qty"
                    :min="Math.max(1, item.received_qty || 0)"
                    :class="[
                      'block w-full text-sm rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.qty ? 'border-red-300' : 'border-gray-300'
                    ]"
                    placeholder="1"
                    :disabled="!canEditItems"
                    @input="validateItemQuantity(index)"
                  />
                  <p v-if="itemErrors[index]?.qty" class="text-xs text-red-600">
                    {{ itemErrors[index].qty }}
                  </p>
                </div>

                <!-- Unit Price -->
                
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Preço UN.
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="item.unit_price"
                    type="number"
                    min="0"
                    step="0.01"
                    :class="[
                      'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.unit_price ? 'border-red-300' : 'border-gray-300'
                    ]"
                    placeholder="0.00"
                  />
                  <p v-if="itemErrors[index]?.unit_price" class="text-xs text-red-600">
                    {{ itemErrors[index].unit_price }}
                  </p>
                </div>

                <!-- Total Price -->
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Total
                  </label>
                  <input
                    v-model="item.total_price"
                    :value="item.unit_price * item.qty"
                    type="number"
                    min="0"
                    :disabled="true"
                    :class="[
                      'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.total_price ? 'border-red-300' : 'border-gray-300'
                    ]"
                    placeholder="0.00"
                  />
                  <p v-if="itemErrors[index]?.total_price" class="text-xs text-red-600">
                    {{ itemErrors[index].total_price }}
                  </p>
                </div>

                <!-- Warehouse -->
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Armazém de Destino
                    <span class="text-red-500">*</span>
                  </label>
                  <comboboxEnhanced
                    v-model="item.warehouse_obj"
                    :has-error="itemErrors[index]?.warehouse_id"
                    :options="warehouseOptions"
                    placeholder="Pesquisar armazém"
                    :disabled="!canEditItems"
                    @update:modelValue="onWarehouseChange(index)"
                  />
                  <p v-if="itemErrors[index]?.warehouse_id" class="text-xs text-red-600">
                    {{ itemErrors[index].warehouse_id }}
                  </p>
                </div>

                <!-- Expected Date -->
                <div class="space-y-2">
                  <label class="block text-xs font-medium text-gray-700">
                    Data Prevista
                  </label>
                  <input
                    type="date"
                    v-model="item.expected_date"
                    :min="form.date || minDate"
                    :class="[
                      'block w-full text-sm rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.expected_date ? 'border-red-300' : 'border-gray-300'
                    ]"
                    :disabled="!canEditItems"
                  />
                  <p v-if="itemErrors[index]?.expected_date" class="text-xs text-red-600">
                    {{ itemErrors[index].expected_date }}
                  </p>
                </div>
              </div>

              <!-- Remove Button -->
              <button 
                v-if="canEditItems && item.received_qty === 0"
                @click="removeOrderItem(index)"
                type="button"
                class="ml-4 text-gray-400 hover:text-red-600 transition-colors duration-200 p-2 rounded-full hover:bg-red-50"
                :title="'Remove item'"
              >
                <TrashIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          <p v-if="form.supplier_id">
            Editando pedido para <span class="font-semibold">{{ selectedSupplier?.name }}</span>
          </p>
        </div>
        <div class="flex items-center gap-4">
          <button
            type="button"
            @click="goBack"
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing || !isFormValid"
            :class="[
              'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
              form.processing || !isFormValid
                ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
            ]"
          >
            <CheckCircleIcon v-if="!form.processing" class="h-5 w-5" />
            <span v-if="form.processing">Processando...</span>
            <span v-else>Actualizar Pedido</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, router } from '@inertiajs/vue3'
import {
  PencilIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PlusCircleIcon,
  ShoppingBagIcon,
  TrashIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  items: {
    type: Array,
    default: () => []
  },
  suppliers: {
    type: Array,
    default: () => []
  },
  warehouses: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  supplier_id: props.order.supplier_id || '',
  date: props.order.date ? new Date(props.order.date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
  reference: props.order.reference || '',
  status: props.order.status || 'PENDING',
  obs: props.order.obs || '',
  order_items: props.order.items ? props.order.items.map(item => ({
    id: item.id,
    item_obj: item.item ? {
      value: item.item_id,
      label: `${item.item.name}${item.item.internal_code ? ` (${item.item.internal_code})` : ''}`,
    } : null,
    item_id: item.item_id,
    qty: item.qty,
    unit_price: item.unit_price,
    warehouse_obj: item.warehouse ? {
      value: item.warehouse_id,
      label: item.warehouse.name,
    } : null,
    warehouse_id: item.warehouse_id,
    expected_date: item.expected_date ? new Date(item.expected_date).toISOString().split('T')[0] : '',
    received_qty: item.received_qty || 0
  })) : []
})

const itemErrors = ref([])
const selectedSupplierOption = ref(null)
const selectedStatusOption = ref(null)
const minDate = new Date().toISOString().split('T')[0]
const maxDate = new Date().toISOString().split('T')[0]

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

const statusOptions = [
  { value: 'PENDING', label: 'Pendente' },
  { value: 'APPROVED', label: 'Aprovado' },
  { value: 'ORDERED', label: 'Pedido' },
  { value: 'CANCELLED', label: 'Cancelado' },
]

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.internal_code ? ` (${item.internal_code})` : ''}${item.category?.name ? ` - ${item.category.name}` : ''}`,
})))

const warehouseOptions = computed(() => props.warehouses.map((warehouse) => ({
  value: warehouse.id,
  label: warehouse.location?.name ? `${warehouse.name} (${warehouse.location.name})` : warehouse.name,
})))

watch(selectedSupplierOption, (supplier) => {
  form.supplier_id = supplier?.value || ''
})

watch(selectedStatusOption, (status) => {
  form.status = status?.value || 'PENDING'
})

const formatStatus = (status) => {
  const statusMap = {
    'PENDING': 'Pendente',
    'APPROVED': 'Aprovado',
    'ORDERED': 'Pedido',
    'PARTIALLY_RECEIVED': 'Recebido Parcialmente',
    'RECEIVED': 'Recebido',
    'CANCELLED': 'Cancelado'
  }
  return statusMap[status] || status
}

const statusClass = computed(() => {
  const classMap = {
    'PENDING': 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    'APPROVED': 'bg-blue-100 text-blue-800 ring-blue-600/20',
    'ORDERED': 'bg-purple-100 text-purple-800 ring-purple-600/20',
    'PARTIALLY_RECEIVED': 'bg-orange-100 text-orange-800 ring-orange-600/20',
    'RECEIVED': 'bg-green-100 text-green-800 ring-green-600/20',
    'CANCELLED': 'bg-red-100 text-red-800 ring-red-600/20'
  }
  return classMap[props.order.status] || 'bg-gray-100 text-gray-800 ring-gray-600/20'
})

const selectedSupplier = computed(() => {
  return props.suppliers.find(supplier => supplier.id == form.supplier_id)
})

const selectedSupplierAssessment = computed(() => selectedSupplier.value?.latest_assessment ?? null)

const supplierAssessmentStatus = computed(() => ({
  approved: 'Aprovado',
  conditional: 'Condicional',
  suspended: 'Suspenso',
  rejected: 'Rejeitado'
}[selectedSupplierAssessment.value?.status] || 'Sem estado'))

const supplierAssessmentRisk = computed(() => ({
  low: 'Risco baixo',
  medium: 'Risco médio',
  high: 'Risco elevado',
  critical: 'Risco crítico'
}[selectedSupplierAssessment.value?.risk_level] || 'Risco não definido'))

const supplierAssessmentReviewLabel = computed(() => {
  if (!selectedSupplierAssessment.value?.next_review_at) {
    return 'sem data'
  }

  return new Date(selectedSupplierAssessment.value.next_review_at).toLocaleDateString('pt-PT')
})

const supplierAssessmentPanelClass = computed(() => {
  const assessment = selectedSupplierAssessment.value

  if (!assessment) {
    return 'border-amber-200 bg-amber-50 text-amber-900'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'border-rose-200 bg-rose-50 text-rose-900'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'border-amber-200 bg-amber-50 text-amber-900'
  }

  return 'border-emerald-200 bg-emerald-50 text-emerald-900'
})

const canEditSupplier = computed(() => {
  return ['PENDING', 'APPROVED'].includes(props.order.status)
})

const canEditStatus = computed(() => {
  return ['PENDING', 'APPROVED'].includes(props.order.status)
})

const canEditItems = computed(() => {
  return ['PENDING', 'APPROVED'].includes(props.order.status)
})

const isFormValid = computed(() => {
  return form.supplier_id && 
         form.date && 
         form.order_items.length > 0 &&
         form.order_items.every(item => 
           item.item_id && 
           item.qty > 0 && 
           item.warehouse_id &&
           item.qty >= (item.received_qty || 0)
         ) &&
         !form.processing
})

function addOrderItem() {
  form.order_items.push({
    item_obj: null,
    item_id: '',
    qty: 1,
    warehouse_obj: null,
    warehouse_id: '',
    expected_date: '',
    received_qty: 0,
    status: 'PENDING',
    unit_price: 0,
    total_price: 0,
  })
  itemErrors.value.push({})
}

function onItemChange(index) {
  const item = form.order_items[index]
  item.item_id = item.item_obj?.value || ''
}

function onWarehouseChange(index) {
  const item = form.order_items[index]
  item.warehouse_id = item.warehouse_obj?.value || ''
}

function removeOrderItem(index) {
  form.order_items.splice(index, 1)
  itemErrors.value.splice(index, 1)
}

function validateItemQuantity(index) {
  const item = form.order_items[index]
  const errors = {}
  
  if (!item.qty || item.qty <= 0) {
    errors.qty = 'Quantity must be greater than 0'
  } else if (item.qty < (item.received_qty || 0)) {
    errors.qty = `Quantity cannot be less than already received (${item.received_qty})`
  }
  
  itemErrors.value[index] = errors
}

function submit() {
  // Validate all items before submission
  let isValid = true
  form.order_items.forEach((item, index) => {
    validateItemQuantity(index)
    if (Object.keys(itemErrors.value[index] || {}).length > 0) {
      isValid = false
    }
  })
  
  if (!isValid) {
    return
  }

  form.transform((data) => ({
    ...data,
    order_items: data.order_items.map(({ item_obj, warehouse_obj, ...orderItem }) => orderItem),
  })).put(route('vap-inventory.orders.update', props.order.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('vap-inventory.orders.show', props.order.id))
    }
  })
}

function goBack() {
  router.visit(route('vap-inventory.orders.show', props.order.id))
}

onMounted(() => {
  const supplier = props.suppliers.find(supplier => supplier.id == form.supplier_id)
  if (supplier) {
    selectedSupplierOption.value = {
      value: supplier.id,
      label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
    }
  }

  const status = statusOptions.find(status => status.value === form.status)
  selectedStatusOption.value = status || statusOptions[0]

  // Initialize item errors array
  if (form.order_items.length > 0) {
    itemErrors.value = Array(form.order_items.length).fill({})
  }
})
</script>

<style scoped>
.inventory-order-form-shell :deep(.bg-white.rounded-xl) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.inventory-order-form-shell :deep(label) {
  color: rgb(51 65 85);
}

.inventory-order-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file'])),
.inventory-order-form-shell :deep(textarea),
.inventory-order-form-shell :deep(select) {
  border-color: rgb(203 213 225);
  border-radius: 1rem;
  background: rgb(255 255 255);
  color: rgb(15 23 42);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
  transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.inventory-order-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file']):focus),
.inventory-order-form-shell :deep(textarea:focus),
.inventory-order-form-shell :deep(select:focus) {
  border-color: rgb(var(--color-primary-500, 59 130 246));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500, 59 130 246) / 0.18);
  outline: none;
}

.inventory-order-form-shell :deep(.bg-gradient-to-r.from-blue-900.to-blue-800) {
  --tw-gradient-from: rgb(var(--color-primary-700, 29 78 216)) var(--tw-gradient-from-position);
  --tw-gradient-to: rgb(var(--color-primary-900, 30 58 138)) var(--tw-gradient-to-position);
  --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
}

.inventory-order-form-shell :deep(.bg-blue-900) {
  background-color: rgb(var(--color-primary-600, 37 99 235));
}

.inventory-order-form-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.inventory-order-form-shell :deep(.bg-gray-50) {
  border: 1px solid rgb(226 232 240);
  border-radius: 1rem;
  background: rgb(248 250 252 / 0.75);
}

.inventory-order-form-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(226 232 240);
}

:global(.dark) .inventory-order-form-shell :deep(.bg-white.rounded-xl) {
  border-color: rgb(30 41 59);
  background: rgb(2 6 23);
}

:global(.dark) .inventory-order-form-shell :deep(label),
:global(.dark) .inventory-order-form-shell :deep(.text-gray-900),
:global(.dark) .inventory-order-form-shell :deep(.text-gray-700),
:global(.dark) .inventory-order-form-shell :deep(.text-gray-600) {
  color: rgb(226 232 240);
}

:global(.dark) .inventory-order-form-shell :deep(.text-gray-500) {
  color: rgb(148 163 184);
}

:global(.dark) .inventory-order-form-shell :deep(input:not([type='checkbox']):not([type='radio']):not([type='file'])),
:global(.dark) .inventory-order-form-shell :deep(textarea),
:global(.dark) .inventory-order-form-shell :deep(select) {
  border-color: rgb(51 65 85);
  background: rgb(2 6 23 / 0.72);
  color: rgb(241 245 249);
}

:global(.dark) .inventory-order-form-shell :deep(input::placeholder),
:global(.dark) .inventory-order-form-shell :deep(textarea::placeholder) {
  color: rgb(100 116 139);
}

:global(.dark) .inventory-order-form-shell :deep(.border-gray-200),
:global(.dark) .inventory-order-form-shell :deep(.border-gray-300) {
  border-color: rgb(51 65 85);
}

:global(.dark) .inventory-order-form-shell :deep(.bg-gray-50),
:global(.dark) .inventory-order-form-shell :deep(.group:hover) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.68);
}

:global(.dark) .inventory-order-form-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(30 41 59);
}

:global(.dark) .inventory-order-form-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .inventory-order-form-shell :deep(.bg-blue-900) {
  background-color: rgb(var(--color-primary-500, 59 130 246));
}
</style>
