<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <PlusIcon class="h-7 w-7 text-blue-900" />
            Criar Pedido de Compra
          </h1>
          <p class="mt-2 text-gray-600">
            Registe um novo pedido de compra para abastecimento do inventário
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </button>
        </div>
      </div>
    </div>

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
            <select
              v-model="form.supplier_id"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.supplier_id ? 'border-red-300' : 'border-gray-300'
              ]"
              required
            >
              <option value="">Seleccione um Fornecedor</option>
              <option 
                v-for="supplier in suppliers" 
                :key="supplier.id" 
                :value="supplier.id"
              >
                {{ supplier.name }}
                <template v-if="supplier.address">
                  - {{ supplier.address.substring(0, 30) }}...
                </template>
              </option>
            </select>
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
            <select
              v-model="form.status"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.status ? 'border-red-300' : 'border-gray-300'
              ]"
            >
              <option value="PENDING">Pendente</option>
              <option value="APPROVED">Aprovado</option>
              <option value="ORDERED">Ordenado</option>
              <option value="CANCELLED">Cancelado</option>
            </select>
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
              placeholder="Instruções especiais, condições de entrega ou notas internas..."
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
            Nenhum item adicionado ao pedido
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            Adicione itens para criar um pedido de compra
          </p>
          <button 
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
                  <select
                    v-model="item.item_id"
                    @change="onItemChange(index)"
                    :class="[
                      'block w-full text-sm rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.item_id ? 'border-red-300' : 'border-gray-300'
                    ]"
                  >
                    <option value="">Seleccione um Item</option>
                    <option 
                      v-for="inventoryItem in items" 
                      :key="inventoryItem.id" 
                      :value="inventoryItem.id"
                    >
                      {{ inventoryItem.name }} ({{ inventoryItem.internal_code }})
                      <template v-if="inventoryItem.category">
                        - {{ inventoryItem.category.name }}
                      </template>
                    </option>
                  </select>
                  <p v-if="itemErrors[index]?.item_id" class="text-xs text-red-600">
                    {{ itemErrors[index].item_id }}
                  </p>
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
                    min="1"
                    :class="[
                      'block w-full text-sm rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.qty ? 'border-red-300' : 'border-gray-300'
                    ]"
                    placeholder="1"
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
                  <select
                    v-model="item.warehouse_id"
                    :class="[
                      'block w-full text-sm rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                      itemErrors[index]?.warehouse_id ? 'border-red-300' : 'border-gray-300'
                    ]"
                  >
                    <option value="">Seleccione um Armazém</option>
                    <option 
                      v-for="warehouse in warehouses" 
                      :key="warehouse.id" 
                      :value="warehouse.id"
                    >
                      {{ warehouse.name }}
                    </option>
                  </select>
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
                  />
                  <p v-if="itemErrors[index]?.expected_date" class="text-xs text-red-600">
                    {{ itemErrors[index].expected_date }}
                  </p>
                </div>
              </div>

              <!-- Remove Button -->
              <button 
                @click="removeOrderItem(index)"
                type="button"
                class="ml-4 text-gray-400 hover:text-red-600 transition-colors duration-200 p-2 rounded-full hover:bg-red-50"
                :title="'Remover item'"
              >
                <TrashIcon class="h-5 w-5" />
              </button>
            </div>

            <!-- Item Details -->
            <div v-if="getSelectedItemDetails(item.item_id)" class="mt-3 text-xs text-gray-500">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div class="flex items-center gap-1">
                  <TagIcon class="h-3 w-3" />
                  <span>Código Interno: {{ getSelectedItemDetails(item.item_id).internal_code }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <RectangleStackIcon class="h-3 w-3" />
                  <span>Categoria: {{ getSelectedItemDetails(item.item_id).category?.name || 'Sem Categoria' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <CubeIcon class="h-3 w-3" />
                  <span>Unidade: {{ getSelectedItemDetails(item.item_id).unit?.code || 'Sem Unidade' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <BuildingStorefrontIcon class="h-3 w-3" />
                  <span>Número de Lote: {{ getCurrentStock(item.item_id, item.warehouse_id) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ORDER SUMMARY -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <CalculatorIcon class="h-5 w-5 text-blue-900" />
          Resumo do Pedido
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="space-y-2">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total de Itens:</span>
                  <span class="font-semibold text-gray-900">{{ form.order_items.length }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Quantidade Total:</span>
                  <span class="font-semibold text-gray-900">{{ totalQuantity }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Fornecedor:</span>
                  <span class="font-semibold text-gray-900">{{ selectedSupplier?.name || 'Sem Fornecedor' }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Data do Pedido:</span>
                  <span class="font-semibold text-gray-900">{{ formatDate(form.date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Estado:</span>
                  <span :class="[
                    'font-semibold',
                    getStatusClass(form.status)
                  ]">
                    {{ formatStatus(form.status) }}
                  </span>
                </div>
                <div v-if="earliestExpectedDate" class="flex justify-between text-sm">
                  <span class="text-gray-600">Data Prevista:</span>
                  <span class="font-semibold text-gray-900">{{ formatDate(earliestExpectedDate) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Número de Armazéns Únicos:</span>
                  <span class="font-semibold text-gray-900">{{ uniqueWarehouses }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Número de Referência:</span>
                  <span class="font-semibold text-gray-900">{{ form.reference || 'Auto-gerado' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          <p v-if="form.supplier_id">
            Pedido para <span class="font-semibold">{{ selectedSupplier?.name }}</span>
            com {{ form.order_items.length }} itens
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
            type="button"
            @click="saveAsDraft"
            :disabled="form.processing"
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
          >
            Salvar como Rascunho
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
            <span v-else>Criar Pedido</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  PlusIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PlusCircleIcon,
  ShoppingBagIcon,
  TrashIcon,
  TagIcon,
  RectangleStackIcon,
  BuildingStorefrontIcon,
  CalculatorIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
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
  supplier_id: '',
  date: new Date().toISOString().split('T')[0],
  reference: '',
  status: 'pending',
  obs: '',
  order_items: []
})

const itemErrors = ref([])
const minDate = new Date().toISOString().split('T')[0]
const maxDate = new Date().toISOString().split('T')[0]

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

  return formatDate(selectedSupplierAssessment.value.next_review_at)
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

const totalQuantity = computed(() => {
  return form.order_items.reduce((total, item) => total + parseInt(item.qty || 0), 0)
})

const earliestExpectedDate = computed(() => {
  const dates = form.order_items
    .map(item => item.expected_date)
    .filter(date => date)
    .sort()
  
  return dates[0] || null
})

const uniqueWarehouses = computed(() => {
  const warehouseIds = form.order_items
    .map(item => item.warehouse_id)
    .filter(id => id)
  
  return new Set(warehouseIds).size
})

const isFormValid = computed(() => {
  return form.supplier_id && 
         form.date && 
         form.order_items.length > 0 &&
         form.order_items.every(item => 
           item.item_id && 
           item.qty > 0 && 
           item.warehouse_id
         ) &&
         !form.processing
})

function getSelectedItemDetails(itemId) {
  return props.items.find(item => item.id == itemId)
}

function getCurrentStock(itemId, warehouseId) {
  if (!itemId || !warehouseId) return 'N/A'
  
  const item = getSelectedItemDetails(itemId)
  if (!item || !item.inventory) return 'N/A'
  
  const inventory = item.inventory?.find(inv => inv.warehouse_id == warehouseId)
  return inventory ? inventory?.qty_available : 0
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatStatus(status) {
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

function getStatusClass(status) {
  const classMap = {
    'PENDING': 'text-yellow-600',
    'APPROVED': 'text-blue-600',
    'ORDERED': 'text-purple-600',
    'PARTIALLY_RECEIVED': 'text-orange-600',
    'RECEIVED': 'text-green-600',
    'CANCELLED': 'text-red-600'
  }
  return classMap[status] || 'text-gray-600'
}

function addOrderItem() {
  form.order_items.push({
    item_id: '',
    qty: 1,
    warehouse_id: '',
    expected_date: '',
    status: 'PENDING',
    unit_price: 0,
    total_price: 0,
  })
  itemErrors.value.push({})
}

function removeOrderItem(index) {
  form.order_items.splice(index, 1)
  itemErrors.value.splice(index, 1)
}

function onItemChange(index) {
  const item = form.order_items[index]
  // Auto-select first warehouse if not set
  // if (item.item_id && !item.warehouse_id && props.warehouses.length > 0) {
  //   item.warehouse_id = props.warehouses[0].id
  // }
}

function validateItemQuantity(index) {
  const item = form.order_items[index]
  const errors = {}
  
  if (!item.qty || item.qty <= 0) {
    errors.qty = 'Quantidade deve ser maior que 0'
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

  form.post(route('vap-inventory.orders.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      router.visit(route('vap-inventory.orders.index'))
    }
  })
}

function saveAsDraft() {
  form.status = 'pending'
  submit()
}

function goBack() {
  router.visit(route('vap-inventory.orders.index'))
}

onMounted(() => {
  // Add first item by default
  if (form.order_items.length === 0) {
    addOrderItem()
  }
})
</script>
