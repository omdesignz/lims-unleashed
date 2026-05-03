<template>
  <Modal :show="show" @close="close">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ArrowsRightLeftIcon class="h-5 w-5 text-blue-900" />
          Movimento de Estoque
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-500">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- ITEM INFO -->
        <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-blue-100 p-4">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <CubeIcon class="h-6 w-6 text-blue-900" />
              </div>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ item.name }}</div>
              <div class="text-sm text-gray-500">{{ item.internal_code || 'Sem Código Interno' }}</div>
              <div class="text-xs text-gray-400">{{ item.category?.name || 'Sem Categoria' }}</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <!-- SOURCE WAREHOUSE -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Armazém de Origem
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.source_id"
              @change="updateSourceStock"
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.source_id ? 'border-red-300' : 'border-gray-300'
              ]"
              required
            >
              <option value="">Seleccione um Armazém</option>
              <option 
                v-for="inv in sourceWarehouses"
                :key="inv.warehouse_id"
                :value="inv.warehouse_id"
              >
                {{ inv.warehouse?.name }} (Disponível: {{ inv.qty_available }} {{ item.unit?.code || 'unidades' }})
              </option>
            </select>
            <p v-if="form.errors.source_id" class="text-xs text-red-600">
              {{ form.errors.source_id }}
            </p>
          </div>

          <!-- DESTINATION WAREHOUSE -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Armazém de Destino
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.destination_id"
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.destination_id ? 'border-red-300' : 'border-gray-300'
              ]"
              required
            >
              <option value="">Seleccione um Armazém</option>
              <option 
                v-for="warehouse in destinationWarehouses"
                :key="warehouse.id"
                :value="warehouse.id"
              >
                {{ warehouse.name }}
                <template v-if="warehouse.location">
                  ({{ warehouse.location.name }})
                </template>
              </option>
            </select>
            <p v-if="form.errors.destination_id" class="text-xs text-red-600">
              {{ form.errors.destination_id }}
            </p>
          </div>
        </div>

        <!-- QUANTITY -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Quantidade a Transferir
            <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model.number="form.qty"
              type="number"
              :min="1"
              :max="sourceStock?.qty_available || 0"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm pr-12',
                form.errors.qty ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Digite a quantidade"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-sm text-gray-500">{{ item.unit?.code || 'unidades' }}</span>
            </div>
          </div>
          <p v-if="form.errors.qty" class="text-xs text-red-600">
            {{ form.errors.qty }}
          </p>
          <div v-if="sourceStock" class="text-xs text-gray-500">
            Disponível: {{ sourceStock.qty_available }} {{ item.unit?.code || 'unidades' }}
            <template v-if="form.qty && form.qty > sourceStock.qty_available">
              <span class="text-red-600 font-semibold ml-2">
                Sem Estoque Disponível!
              </span>
            </template>
          </div>
        </div>

        <!-- DATES -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Entrega Esperada
            </label>
            <!-- <input
              v-model="form.expected_date"
              type="date"
              :min="new Date().toISOString().split('T')[0]"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            /> -->
            <date-picker-enhanced
              v-model="form.expected_date"
              :has-error="form.errors.expected_date"
              :error-message="form.errors.expected_date"
              placeholder="Selecione uma Data"
            />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Transferência
            </label>
            <!-- <input
              v-model="form.sent_date"
              type="date"
              :max="new Date().toISOString().split('T')[0]"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            /> -->
            <date-picker-enhanced
              v-model="form.sent_date"
              :has-error="form.errors.sent_date"
              :error-message="form.errors.sent_date"
              placeholder="Selecione uma Data"
              />
          </div>
        </div>

        <!-- NOTES -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Observações
          </label>
          <textarea
            v-model="form.obs"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Qualquer observação sobre este transferência..."
          ></textarea>
          <p v-if="form.errors.obs" class="text-xs text-red-600">
            {{ form.errors.obs }}
          </p>
        </div>

        <!-- TRANSFER SUMMARY -->
        <div v-if="form.source_id && form.destination_id && form.qty" 
          class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 p-4">
          <h4 class="text-sm font-semibold text-gray-900 mb-2">Resumo de Transferência</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <div class="flex items-center justify-between">
              <span>Armazém de Origem:</span>
              <span class="font-medium">{{ sourceWarehouse?.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Armazém de Destino:</span>
              <span class="font-medium">{{ destinationWarehouse?.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Quantidade:</span>
              <span class="font-medium">{{ form.qty }} {{ item.unit?.code || 'unidades' }}</span>
            </div>
            <div v-if="form.expected_date" class="flex items-center justify-between">
              <span>Data de Entrega Esperada:</span>
              <span class="font-medium">{{ formatDate(form.expected_date) }}</span>
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
                : 'bg-green-900 hover:bg-green-800'
            ]"
          >
            <span v-if="form.processing">Processando...</span>
            <span v-else>Iniciar Transferência</span>
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  ArrowsRightLeftIcon,
  XMarkIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'

const props = defineProps({
  show: Boolean,
  item: Object,
  inventory: Array,
})

const emit = defineEmits(['close', 'success'])

const sourceStock = ref(null)
const sourceWarehouse = ref(null)
const destinationWarehouse = ref(null)

const form = useForm({
  item_id: props.item?.id,  
  source_id: '',
  destination_id: '',
  qty: '',
  expected_date: '',
  sent_date: new Date().toISOString().split('T')[0],
  obs: '',
})

const sourceWarehouses = computed(() => {
  return props.inventory.filter(inv => inv.qty_available > 0)
})

const destinationWarehouses = computed(() => {
  // Get all warehouses except the selected source
  const warehouses = []
  const allWarehouses = new Set()
  
  // Add all warehouses from inventory
  props.inventory.forEach(inv => {
    if (inv.warehouse) {
      allWarehouses.add(inv.warehouse)
    }
  })
  
  // Convert set to array and filter out source
  return Array.from(allWarehouses).filter(wh => 
    !form.source_id || wh.id != form.source_id
  )
})

const isFormValid = computed(() => {
  return form.source_id && 
         form.destination_id && 
         form.qty && 
         form.qty > 0 &&
         (!sourceStock.value || form.qty <= sourceStock.value.qty_available)
})

const updateSourceStock = () => {
  if (form.source_id) {
    sourceStock.value = props.inventory.find(
      inv => inv.warehouse_id == form.source_id
    )
    sourceWarehouse.value = sourceStock.value?.warehouse
  } else {
    sourceStock.value = null
    sourceWarehouse.value = null
  }
}

watch(() => form.destination_id, (destinationId) => {
  if (destinationId) {
    destinationWarehouse.value = destinationWarehouses.value.find(
      wh => wh.id == destinationId
    )
  } else {
    destinationWarehouse.value = null
  }
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const submit = () => {
  if (!isFormValid.value) return

  form.post(route('vap-inventory.transfers.store'), { 
    preserveScroll: true,
    onSuccess: () => {
      emit('success')
      close()
    },
  })
}

const close = () => {
  form.reset()
  form.sent_date = new Date().toISOString().split('T')[0]
  sourceStock.value = null
  sourceWarehouse.value = null
  destinationWarehouse.value = null
  emit('close')
}

// Reset form when modal opens
watch(() => props.show, (show) => {
  if (show) {
    form.reset()
    form.sent_date = new Date().toISOString().split('T')[0]
    sourceStock.value = null
    sourceWarehouse.value = null
    destinationWarehouse.value = null
  }
})
</script>