<template>
  <Modal :show="show" @close="close">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ArrowsUpDownIcon class="h-5 w-5 text-blue-900" />
          Ajustar Estoque
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

        <!-- WAREHOUSE SELECTION -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Armazém
            <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.warehouse_id"
            @change="updateWarehouseStock"
            :class="[
              'w-full rounded-lg border px-3 py-2.5 text-sm',
              form.errors.warehouse_id ? 'border-red-300' : 'border-gray-300'
            ]"
            required
          >
            <option value="">Seleccione um Armazém</option>
            <option 
              v-for="inv in inventory"
              :key="inv.id"
              :value="inv.warehouse_id"
            >
              {{ inv.warehouse?.name }} (Atual: {{ inv.qty_available }} {{ item.unit?.code || 'unidades' }})
            </option>
          </select>
          <p v-if="form.errors.warehouse_id" class="text-xs text-red-600">
            {{ form.errors.warehouse_id }}
          </p>
        </div>

        <!-- ADJUSTMENT TYPE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Tipo de Ajuste
            <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="form.adjustment_type = 'add'"
              :class="[
                'rounded-lg border p-3 text-sm font-medium transition-all',
                form.adjustment_type === 'add'
                  ? 'border-green-900 bg-green-50 text-green-900'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <PlusIcon class="h-4 w-4" />
                Adicionar Estoque
              </div>
            </button>
            <button
              type="button"
              @click="form.adjustment_type = 'remove'"
              :class="[
                'rounded-lg border p-3 text-sm font-medium transition-all',
                form.adjustment_type === 'remove'
                  ? 'border-red-900 bg-red-50 text-red-900'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <MinusIcon class="h-4 w-4" />
                Remover Estoque
              </div>
            </button>
          </div>
          <p v-if="form.errors.adjustment_type" class="text-xs text-red-600">
            {{ form.errors.adjustment_type }}
          </p>
        </div>

        <!-- QUANTITY -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Quantidade
            <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model.number="form.quantity"
              type="number"
              :min="form.adjustment_type === 'remove' ? 1 : 1"
              :max="form.adjustment_type === 'remove' ? selectedInventory?.qty_available : null"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm pr-12',
                form.errors.quantity ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Introduza a quantidade"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-sm text-gray-500">{{ item.unit?.code || 'unidades' }}</span>
            </div>
          </div>
          <p v-if="form.errors.quantity" class="text-xs text-red-600">
            {{ form.errors.quantity }}
          </p>
          <div v-if="selectedInventory && form.adjustment_type === 'remove'" class="text-xs text-gray-500">
            Máximo: {{ selectedInventory.qty_available }} {{ item.unit?.code || 'unidades' }} disponíveis
          </div>
          <div v-if="form.quantity && selectedInventory" class="text-xs text-gray-500">
            Novo nível de estoque:
            <span :class="form.adjustment_type === 'add' ? 'text-green-900 font-semibold' : 'text-red-900 font-semibold'">
              {{ calculateNewStock() }} {{ item.unit?.code || 'unidades' }}
            </span>
          </div>
        </div>

        <!-- REASON -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Motivo
            <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.reason"
            :class="[
              'w-full rounded-lg border px-3 py-2.5 text-sm',
              form.errors.reason ? 'border-red-300' : 'border-gray-300'
            ]"
            required
          >
            <option value="">Seleccione um Motivo</option>
            <option value="physical_count">Ajuste de Contagem Física</option>
            <option value="damaged">Itens Danificados/Perdidos</option>
            <option value="found_extra">Encontrado Estoque Extra</option>
            <option value="quality_control">Rejeição de Controle de Qualidade</option>
            <option value="expired">Itens Expirados</option>
            <option value="calibration">Ajuste de Calibração</option>
            <option value="other">Outro</option>
          </select>
          <p v-if="form.errors.reason" class="text-xs text-red-600">
            {{ form.errors.reason }}
          </p>
        </div>

        <!-- NOTES -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Observações
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Adicione quaisquer observações adicionais..."
          ></textarea>
          <p v-if="form.errors.notes" class="text-xs text-red-600">
            {{ form.errors.notes }}
          </p>
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
                : form.adjustment_type === 'add'
                  ? 'bg-green-900 hover:bg-green-800'
                  : 'bg-red-900 hover:bg-red-800'
            ]"
          >
            <span v-if="form.processing">Processando...</span>
            <span v-else>{{ form.adjustment_type === 'add' ? 'Adicionar Estoque' : 'Remover Estoque' }}</span>
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
  ArrowsUpDownIcon,
  XMarkIcon,
  CubeIcon,
  PlusIcon,
  MinusIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  item: Object,
  inventory: Array,
})

const emit = defineEmits(['close', 'success'])

const selectedInventory = ref(null)

const form = useForm({
  warehouse_id: '',
  adjustment_type: 'add', 
  quantity: '',
  reason: '',
  notes: '',
})

const isFormValid = computed(() => {
  return form.warehouse_id && form.adjustment_type && form.quantity && form.reason
})

const updateWarehouseStock = () => {
  if (form.warehouse_id) {
    selectedInventory.value = props.inventory.find(
      inv => inv.warehouse_id == form.warehouse_id
    )
  } else {
    selectedInventory.value = null
  }
}

const calculateNewStock = () => {
  if (!selectedInventory.value || !form.quantity) return 0
  
  const current = selectedInventory.value.qty_available
  const adjustment = parseInt(form.quantity)
  
  if (form.adjustment_type === 'add') {
    return current + adjustment
  } else {
    return Math.max(0, current - adjustment)
  }
}

const submit = () => {
  if (!isFormValid.value) return

  form.post(route('vap-inventory.items.adjust-stock', props.item.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('success')
      close()
    },
  })
}

const close = () => {
  form.reset()
  selectedInventory.value = null
  emit('close')
}

// Reset form when modal opens
watch(() => props.show, (show) => {
  if (show) {
    form.reset()
    selectedInventory.value = null
  }
})
</script>
