<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Reagent usage"
      title="Registrar Consumo de Reagentes"
      description="Registre consumo com stock disponível, armazém, responsável e evidência operacional em uma única tela."
    >
      <template #actions>
        <button
          @click="goBack"
          class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          Voltar
        </button>
      </template>
    </ModuleHero>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- CONSUMPTION DETAILS -->
      <ModuleCard title="Detalhes de Consumo" description="Escolha o reagente, confirme o armazém e registre a quantidade consumida.">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Reagent Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Reagente
              <span class="text-red-500">*</span>
            </label>
            <BaseSelect
              v-model="form.reagent_id"
              @change="onReagentChange"
              :error="form.errors.reagent_id"
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
            </BaseSelect>
            <p v-if="form.errors.reagent_id" class="text-xs text-red-600">
              {{ form.errors.reagent_id }}
            </p>
          </div>

          <!-- Warehouse Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Armazém
              <span class="text-red-500">*</span>
            </label>
            <BaseSelect
              v-model="form.warehouse_id"
              @change="onWarehouseChange"
              :error="form.errors.warehouse_id"
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
            </BaseSelect>
            <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
              {{ form.errors.warehouse_id }}
            </p>
          </div>

          <!-- Quantity Used -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Quantidade Usada
              <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center gap-2">
              <BaseInput
                type="number"
                v-model="form.quantity_used"
                :min="0.01"
                :max="maxQuantity"
                :step="0.01"
                :error="form.errors.quantity_used"
                placeholder="Digite a quantidade"
                required
              />
              <span class="whitespace-nowrap rounded-2xl bg-slate-100 px-3 py-2 text-sm font-medium text-slate-600 dark:bg-white/5 dark:text-slate-300">
                Máx: {{ maxQuantity }}
              </span>
            </div>
            <p v-if="form.errors.quantity_used" class="text-xs text-red-600">
              {{ form.errors.quantity_used }}
            </p>
          </div>

          <!-- Used By -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Usado Por
              <span class="text-red-500">*</span>
            </label>
            <BaseInput
              type="text"
              v-model="form.used_by"
              :error="form.errors.used_by"
              placeholder="Digite o nome da pessoa que usou o reagente"
              required
            />
            <p v-if="form.errors.used_by" class="text-xs text-red-600">
              {{ form.errors.used_by }}
            </p>
          </div>

          <!-- Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Data
              <span class="text-red-500">*</span>
            </label>
            <BaseInput
              type="date"
              v-model="form.date"
              :max="maxDate"
              :error="form.errors.date"
              required
            />
            <p v-if="form.errors.date" class="text-xs text-red-600">
              {{ form.errors.date }}
            </p>
          </div>

          <!-- Remarks -->
          <div class="space-y-2 md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Observações
            </label>
            <BaseTextarea
              v-model="form.remarks"
              rows="3"
              :error="form.errors.remarks"
              placeholder="Qualquer observação adicional sobre o consumo..."
            />
            <p v-if="form.errors.remarks" class="text-xs text-red-600">
              {{ form.errors.remarks }}
            </p>
          </div>
        </div>
      </ModuleCard>

      <!-- REAGENT INFORMATION -->
      <ModuleCard v-if="selectedReagent" title="Informações do Reagente" description="Valide stock, validade e impacto antes de registrar o consumo.">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Reagent Details -->
          <div class="space-y-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Nome:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ selectedReagent.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Código:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ selectedReagent.code }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Categoria:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ selectedReagent.category?.name || 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Unidade:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ selectedReagent.unit?.code || 'N/A' }}</span>
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
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Disponível na Armazém Seleccionado:</span>
                  <span :class="[
                    'font-bold',
                    currentStock >= form.quantity_used ? 'text-green-600' : 'text-red-600'
                  ]">
                    {{ currentStock }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Quantidade a Consumir:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ form.quantity_used || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500 dark:text-slate-400">Restante após o Consumo:</span>
                  <span class="font-medium text-slate-900 dark:text-white">{{ remainingAfterConsumption }}</span>
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
      </ModuleCard>

      <!-- ACTIONS -->
      <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-500 dark:text-slate-400">
          <p v-if="form.reagent_id && form.warehouse_id">
            Consumindo de <span class="font-semibold">{{ getWarehouseName(form.warehouse_id) }}</span>
          </p>
        </div>
        <div class="flex items-center gap-4">
          <button
            type="button"
            @click="goBack"
            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.processing || !isFormValid"
            :class="[
              'inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
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
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  BeakerIcon,
  ArrowLeftIcon,
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
