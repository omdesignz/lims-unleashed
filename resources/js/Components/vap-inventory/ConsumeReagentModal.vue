<template>
  <Modal :show="show" @close="close" max-width="lg">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <BeakerIcon class="h-5 w-5 text-red-900" />
          Registrar Consumo de Reagentes
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-500">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- REAGENT INFO -->
        <div class="bg-gradient-to-r from-red-50 to-white rounded-lg border border-red-100 p-4">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center">
                <BeakerIcon class="h-6 w-6 text-red-900" />
              </div>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ item.name }}</div>
              <div class="text-sm text-gray-500">{{ item.internal_code || 'Sem Código Interno' }}</div>
              <div class="text-xs text-gray-400">{{ item.category?.name || 'Sem Categoria' }}</div>
                <span v-if="item.reagent_expiry_date" 
                  :class="getExpiryStatusClass(item)">
                  • Validade: {{ formatDate(item.reagent_expiry_date) }}
                </span>
                <span v-if="item.days_to_expiry !== null" 
                  :class="getDaysClass(item.days_to_expiry)">
                  • {{ Math.abs(item.days_to_expiry) }} dias {{ item.days_to_expiry < 0 ? 'atrás' : 'restantes' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- WAREHOUSE SELECTION -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Armazém
            <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.warehouse_id"
            @change="updateWarehouseInfo"
            :class="[
              'w-full rounded-lg border px-3 py-2.5 text-sm',
              form.errors.warehouse_id ? 'border-red-300' : 'border-gray-300'
            ]"
            required
          >
            <option value="">Seleccione um Armazém</option>
            <option 
              v-for="inv in availableInventory"
              :key="inv.warehouse_id"
              :value="inv.warehouse_id"
            >
              {{ inv.warehouse?.name }} 
              (Disponível: {{ inv.qty_available }} {{ item.unit?.code || 'unidades' }})
              <template v-if="inv.warehouse?.is_refrigerated">
                • Refrigerado
              </template>
            </option>
          </select>
          <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
            {{ form.errors.warehouse_id }}
          </p>
        </div>

        <!-- CONSUMPTION DETAILS -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Consumo
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.date"
              type="date"
              :max="new Date().toISOString().split('T')[0]"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.date ? 'border-red-300' : 'border-gray-300'
              ]"
            />
            <p v-if="form.errors.date" class="text-xs text-red-600">
              {{ form.errors.date }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Quantidade Usada
              <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model.number="form.quantity_used"
                type="number"
                :min="0.01"
                :max="selectedInventory?.qty_available || 0"
                step="0.01"
                required
                :class="[
                  'w-full rounded-lg border px-3 py-2.5 text-sm pr-12',
                  form.errors.quantity_used ? 'border-red-300' : 'border-gray-300'
                ]"
                placeholder="0.00"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-sm text-gray-500">{{ item.unit?.code || 'unidades' }}</span>
              </div>
            </div>
            <p v-if="form.errors.quantity_used" class="text-xs text-red-600">
              {{ form.errors.quantity_used }}
            </p>
            <div v-if="selectedInventory" class="text-xs text-gray-500">
              Disponível: {{ selectedInventory.qty_available }} {{ item.unit?.code || 'unidades' }}
              <template v-if="form.quantity_used && form.quantity_used > selectedInventory.qty_available">
                <span class="text-red-600 font-semibold ml-2">
                  Sem Estoque Disponível!
                </span>
              </template>
            </div>
          </div>
        </div>

        <!-- USAGE INFORMATION -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Usado Por
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.used_by"
              type="text"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.used_by ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Digite o nome do utilizador"
            />
            <p v-if="form.errors.used_by" class="text-xs text-red-600">
              {{ form.errors.used_by }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Usado Em (Hora)
            </label>
            <input
              v-model="form.used_at"
              type="time"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            />
          </div>
        </div>

        <!-- USAGE CONTEXT -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Contexto de Uso
          </label>
          <input
            v-model="form.project"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Digite o nome do projeto ou experimento"
          />
        </div>

        <!-- USAGE TYPE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Tipo de Uso
          </label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button
              type="button"
              v-for="type in usageTypes"
              :key="type.value"
              @click="form.usage_type = type.value"
              :class="[
                'rounded-lg border p-2 text-xs font-medium transition-all',
                form.usage_type === type.value
                  ? 'border-blue-900 bg-blue-50 text-blue-900'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              {{ type.label }}
            </button>
          </div>
        </div>

        <!-- REMARKS -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Observações
          </label>
          <textarea
            v-model="form.remarks"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Adicione qualquer observação sobre este consumo..."
          ></textarea>
          <p v-if="form.errors.remarks" class="text-xs text-red-600">
            {{ form.errors.remarks }}
          </p>
        </div>

        <!-- QUALITY CHECK -->
        <div v-if="item.is_expired" class="bg-gradient-to-r from-red-50 to-white rounded-lg border border-red-100 p-4">
          <div class="flex items-center gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-900 flex-shrink-0" />
            <div>
              <div class="text-sm font-semibold text-red-900">Aviso: Reagento Expirado</div>
              <div class="text-sm text-red-700 mt-1">
                Este reagento expirou em {{ formatDate(item.reagent_expiry_date) }}. 
                Por favor, confirme que o uso está autorizado.
              </div>
            </div>
          </div>
          <div class="mt-3 flex items-center">
            <input
              v-model="form.authorized_usage"
              type="checkbox"
              id="authorized"
              class="h-4 w-4 rounded border-gray-300 text-red-900 focus:ring-red-900"
            />
            <label for="authorized" class="ml-2 text-sm text-red-700">
              Eu confirmo que o uso deste reagente expirado está autorizado
            </label>
          </div>
        </div>

        <!-- CONSUMPTION SUMMARY -->
        <div v-if="form.warehouse_id && form.quantity_used" 
          class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 p-4">
          <h4 class="text-sm font-semibold text-gray-900 mb-2">Resumo de Consumo</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <div class="flex items-center justify-between">
              <span>Reagente:</span>
              <span class="font-medium">{{ item.name }}</span>
            </div>
            <div v-if="selectedInventory" class="flex items-center justify-between">
              <span>Armazém:</span>
              <span class="font-medium">{{ selectedInventory.warehouse?.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Quantidade:</span>
              <span class="font-medium">{{ form.quantity_used }} {{ item.unit?.code || 'unidades' }}</span>
            </div>
            <div v-if="form.used_by" class="flex items-center justify-between">
              <span>Usado Por:</span>
              <span class="font-medium">{{ form.used_by }}</span>
            </div>
            <div v-if="form.date" class="flex items-center justify-between">
              <span>Data:</span>
              <span class="font-medium">{{ formatDate(form.date) }}</span>
            </div>
            <div v-if="selectedInventory" class="flex items-center justify-between">
              <span>Novo Nível de Estoque:</span>
              <span :class="getNewStockClass()">
                {{ calculateNewStock() }} {{ item.unit?.code || 'unidades' }}
              </span>
            </div>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            type="button"
            @click="submit"
            :disabled="form.processing || !isFormValid"
            :class="[
              'rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all',
              form.processing || !isFormValid
                ? 'bg-gray-300 cursor-not-allowed'
                : item.is_expired && !form.authorized_usage
                  ? 'bg-gray-300 cursor-not-allowed'
                  : 'bg-red-900 hover:bg-red-800'
            ]"
          >
            <span v-if="form.processing">Processando...</span>
            <span v-else-if="item.is_expired && !form.authorized_usage">Requer Autorização</span>
            <span v-else>Registrar Consumo</span>
          </button>
        </div>
      </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  BeakerIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  item: Object,
  inventory: Array,
})

const emit = defineEmits(['close', 'success'])

const selectedInventory = ref(null)

const usageTypes = [
  { value: 'experiment', label: 'Experimental' },
  { value: 'calibration', label: 'Calibração' },
  { value: 'qc', label: 'Quality Control' },
  { value: 'maintenance', label: 'Manutenção' },
  { value: 'production', label: 'Produção' },
  { value: 'testing', label: 'Testes' },
  { value: 'training', label: 'Treinamento' },
  { value: 'other', label: 'Outro' },
]

const form = useForm({
  reagent_id: props.item?.id,  
  warehouse_id: '',
  date: new Date().toISOString().split('T')[0],
  quantity_used: 0.01,
  used_by: '',
  used_at: new Date().toTimeString().slice(0, 5),
  project: '',
  usage_type: 'experiment',
  remarks: '',
  authorized_usage: false,
})

const availableInventory = computed(() => {
  return props.inventory.filter(inv => inv.qty_available > 0)
})

const isFormValid = computed(() => {
  return form.warehouse_id && 
         form.date && 
         form.quantity_used && 
         form.quantity_used > 0 &&
         form.used_by &&
         (!selectedInventory.value || form.quantity_used <= selectedInventory.value.qty_available) &&
         (!props.item.is_expired || form.authorized_usage)
})

const updateWarehouseInfo = () => {
  if (form.warehouse_id) {
    selectedInventory.value = props.inventory.find(
      inv => inv.warehouse_id == form.warehouse_id
    )
  } else {
    selectedInventory.value = null
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getExpiryStatusClass = (item) => {
  if (item.is_expired) return 'text-red-900'
  if (item.days_to_expiry <= 30) return 'text-orange-900'
  if (item.days_to_expiry <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const getDaysClass = (days) => {
  if (days <= 0) return 'text-red-900'
  if (days <= 30) return 'text-orange-900'
  if (days <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const calculateNewStock = () => {
  if (!selectedInventory.value || !form.quantity_used) return 0
  return Math.max(0, selectedInventory.value.qty_available - form.quantity_used)
}

const getNewStockClass = () => {
  const newStock = calculateNewStock()
  const minStock = selectedInventory.value?.min_stock_level || 0
  const reorderPoint = selectedInventory.value?.reorder_point || 0
  
  if (newStock <= 0) return 'text-red-900 font-semibold'
  if (newStock <= minStock) return 'text-red-900 font-semibold'
  if (newStock <= reorderPoint) return 'text-orange-900 font-semibold'
  return 'text-green-900 font-semibold'
}

const submit = () => {
  if (!isFormValid.value) return

  // Combine date and time
  const usedAt = form.used_at ? `${form.date}T${form.used_at}:00` : null

  form.post(route('vap-inventory.reagents.consume', props.item.id), {
    ...form.data(), 
    used_at: usedAt,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      emit('success')
      close()
    },
  })
}

const close = () => {
  form.reset()
  form.date = new Date().toISOString().split('T')[0]
  form.used_at = new Date().toTimeString().slice(0, 5)
  selectedInventory.value = null
  emit('close')
}
</script>