<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            Registrar Consumo de Reagentes
          </h1>
          <p class="mt-2 text-gray-600">
            Registre o consumo de reagentes a partir do inventário
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
      <!-- CONSUMPTION DETAILS -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
          Detalhes de Consumo
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Reagent Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Reagente
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.reagent_id"
              @change="onReagentChange"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.reagent_id ? 'border-red-300' : 'border-gray-300'
              ]"
              required
            >
              <option value="">Seleccione um Reagente</option>
              <option 
                v-for="reagent in reagents" 
                :key="reagent.id" 
                :value="reagent.id"
              >
                {{ reagent.name }} ({{ reagent.code }})
                <template v-if="reagent.total_stock !== undefined">
                  - Estoque Total: {{ reagent.total_stock }}
                </template>
              </option>
            </select>
            <p v-if="form.errors.reagent_id" class="text-xs text-red-600">
              {{ form.errors.reagent_id }}
            </p>
          </div>

          <!-- Warehouse Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Armazém
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.warehouse_id"
              @change="onWarehouseChange"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.warehouse_id ? 'border-red-300' : 'border-gray-300'
              ]"
              required
              :disabled="!form.reagent_id"
            >
              <option value="">Seleccione um Armazém</option>
              <option 
                v-for="warehouse in availableWarehouses" 
                :key="warehouse.id" 
                :value="warehouse.id"
              >
                {{ warehouse.name }} 
                <template v-if="warehouse.available_stock !== undefined">
                  (Disponível: {{ warehouse.available_stock }})
                </template>
              </option>
            </select>
            <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
              {{ form.errors.warehouse_id }}
            </p>
          </div>

          <!-- Quantity Used -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Quantidade Usada
              <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
              <input
                type="number"
                v-model="form.quantity_used"
                :min="0.01"
                :max="maxQuantity"
                :step="0.01"
                :class="[
                  'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                  form.errors.quantity_used ? 'border-red-300' : 'border-gray-300'
                ]"
                placeholder="Digite a quantidade"
                required
              />
              <span class="text-sm text-gray-500 whitespace-nowrap">
                Máx: {{ maxQuantity }}
              </span>
            </div>
            <p v-if="form.errors.quantity_used" class="text-xs text-red-600">
              {{ form.errors.quantity_used }}
            </p>
          </div>

          <!-- Used By -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Usado Por
              <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              v-model="form.used_by"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.used_by ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Digite o nome da pessoa que usou o reagente"
              required
            />
            <p v-if="form.errors.used_by" class="text-xs text-red-600">
              {{ form.errors.used_by }}
            </p>
          </div>

          <!-- Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data
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

          <!-- Remarks -->
          <div class="space-y-2 md:col-span-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <textarea
              v-model="form.remarks"
              rows="3"
              :class="[
                'block w-full rounded-lg shadow-sm focus:border-blue-900 focus:ring-blue-900',
                form.errors.remarks ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Qualquer observação adicional sobre o consumo..."
            ></textarea>
            <p v-if="form.errors.remarks" class="text-xs text-red-600">
              {{ form.errors.remarks }}
            </p>
          </div>
        </div>
      </div>

      <!-- REAGENT INFORMATION -->
      <div v-if="selectedReagent" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <InformationCircleIcon class="h-5 w-5 text-blue-900" />
          Informações do Reagente
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Reagent Details -->
          <div class="space-y-2">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Nome:</span>
                  <span class="font-medium text-gray-900">{{ selectedReagent.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Código:</span>
                  <span class="font-medium text-gray-900">{{ selectedReagent.code }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Categoria:</span>
                  <span class="font-medium text-gray-900">{{ selectedReagent.category?.name || 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Unidade:</span>
                  <span class="font-medium text-gray-900">{{ selectedReagent.unit?.code || 'N/A' }}</span>
                </div>
                <div v-if="selectedReagent.reagent_expiry_date" class="flex justify-between text-sm">
                  <span class="text-gray-600">Data de Validade:</span>
                  <span :class="[
                    'font-medium',
                    isReagentExpired ? 'text-red-600' : 'text-gray-900'
                  ]">
                    {{ formatDate(selectedReagent.reagent_expiry_date) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Stock Information -->
          <div class="space-y-2">
            <div class="p-4 bg-gray-50 rounded-lg">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Disponível na Armazém Seleccionado:</span>
                  <span :class="[
                    'font-bold',
                    currentStock >= form.quantity_used ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ currentStock }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Quantidade a Consumir:</span>
                  <span class="font-medium text-gray-900">{{ form.quantity_used || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Restante após o Consumo:</span>
                  <span class="font-medium text-gray-900">{{ remainingAfterConsumption }}</span>
                </div>
              </div>
              
              <div v-if="currentStock < form.quantity_used" class="mt-4 p-3 bg-red-50 rounded border border-red-200">
                <div class="flex items-start">
                  <ExclamationTriangleIcon class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                  <div class="ml-2">
                    <p class="text-sm font-medium text-red-800">Estoque Insuficiente</p>
                    <p class="text-xs text-red-700">
                      Disponível: {{ currentStock }}, Requisitado: {{ form.quantity_used }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="isReagentExpired" class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                <div class="flex items-start">
                  <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" />
                  <div class="ml-2">
                    <p class="text-sm font-medium text-yellow-800">Reagente Vencido</p>
                    <p class="text-xs text-yellow-700">
                      Este reagente venceu em {{ formatDate(selectedReagent.reagent_expiry_date) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          <p v-if="form.reagent_id && form.warehouse_id">
            Consumindo de <span class="font-semibold">{{ getWarehouseName(form.warehouse_id) }}</span>
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
            <span v-else>Registrar Consumo</span>
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
  BeakerIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  reagents: {
    type: Array,
    default: () => []
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  users: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  reagent_id: '',
  warehouse_id: '',
  quantity_used: 0.01,
  used_by: '',
  date: new Date().toISOString().split('T')[0],
  remarks: ''
})

const stockInfo = ref({})
const maxDate = new Date().toISOString().split('T')[0]

const selectedReagent = computed(() => {
  return props.reagents.find(reagent => reagent.id == form.reagent_id)
})

const availableWarehouses = computed(() => {
  if (!form.reagent_id) return props.warehouses
  
  const reagent = selectedReagent.value
  if (!reagent || !reagent.inventory) return props.warehouses
  
  return props.warehouses.map(warehouse => {
    const inventory = reagent.inventory.find(inv => inv.warehouse_id == warehouse.id)
    return {
      ...warehouse,
      available_stock: inventory ? inventory.qty_available : 0
    }
  }).filter(warehouse => warehouse.available_stock > 0)
})

const currentStock = computed(() => {
  if (!form.reagent_id || !form.warehouse_id) return 0
  
  const reagent = selectedReagent.value
  if (!reagent || !reagent.inventory) return 0
  
  const inventory = reagent.inventory.find(inv => 
    inv.warehouse_id == form.warehouse_id
  )
  
  return inventory ? inventory.qty_available : 0
})

const maxQuantity = computed(() => {
  return currentStock.value
})

const remainingAfterConsumption = computed(() => {
  return Math.max(0, currentStock.value - (form.quantity_used || 0))
})

const isReagentExpired = computed(() => {
  if (!selectedReagent.value || !selectedReagent.value.reagent_expiry_date) return false
  
  const expiryDate = new Date(selectedReagent.value.reagent_expiry_date)
  return expiryDate < new Date()
})

const isFormValid = computed(() => {
  return form.reagent_id && 
         form.warehouse_id && 
         form.quantity_used > 0 && 
         form.quantity_used <= maxQuantity.value &&
         form.used_by &&
         form.date &&
         !form.processing
})

function getWarehouseName(id) {
  const warehouse = props.warehouses.find(w => w.id == id)
  return warehouse ? warehouse.name : 'N/A'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
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
    form.quantity_used = Math.min(form.quantity_used, maxQuantity.value)
  }
}

function submit() {
  if (!confirm('Tem a certeza que deseja registrar este consumo?')) {
    return
  }

  form.post(route('vap-inventory.reagents.consumption.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      router.visit(route('vap-inventory.reagents.consumption.index'), {
        preserveScroll: true
      })
    }
  })
}

function goBack() {
  router.visit(route('vap-inventory.reagents.consumption.index'))
}

onMounted(() => {
  // Set default values
  if (props.users.length > 0) {
    form.used_by = props.users[0].name
  }
})
</script>