<template>
  <Modal :show="show" @close="close">
    <div class="space-y-6 p-6">
      <div class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] pb-5">
        <h3 class="ds-heading flex items-center gap-2 text-lg">
          <ArrowsUpDownIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          Ajustar existências
        </h3>
        <button type="button" @click="close" class="ds-icon-button">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- ITEM INFO -->
        <div class="ds-card p-4">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
                <CubeIcon class="h-6 w-6 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              </div>
            </div>
            <div>
              <div class="text-sm font-bold text-[var(--ds-text)]">{{ item.name }}</div>
              <div class="text-sm font-semibold text-[var(--ds-text-muted)]">{{ item.internal_code || 'Sem Código Interno' }}</div>
              <div class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.category?.name || 'Sem Categoria' }}</div>
            </div>
          </div>
        </div>

        <!-- WAREHOUSE SELECTION -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Armazém
            <span class="text-red-500">*</span>
          </label>
          <BaseSelect
            v-model="form.warehouse_id"
            @change="updateWarehouseStock"
            :class="[
              'ds-field',
              form.errors.warehouse_id ? 'border-[var(--color-danger-500)]' : 'border-[var(--ds-border)]'
            ]"
            required
          >
            <option value="">Seleccione um Armazém</option>
            <option 
              v-for="inv in inventory"
              :key="inv.id"
              :value="inv.warehouse_id"
            >
              {{ inv.warehouse?.name }} (Actual: {{ inv.qty_available }} {{ item.unit?.code || 'unidades' }})
            </option>
          </BaseSelect>
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
                  : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)]'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <PlusIcon class="h-4 w-4" />
                Adicionar existências
              </div>
            </button>
            <button
              type="button"
              @click="form.adjustment_type = 'remove'"
              :class="[
                'rounded-lg border p-3 text-sm font-medium transition-all',
                form.adjustment_type === 'remove'
                  ? 'border-red-900 bg-red-50 text-red-900'
                  : 'border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)]'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <MinusIcon class="h-4 w-4" />
                Remover existências
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
            <BaseInput
              v-model.number="form.quantity"
              type="number"
              :min="form.adjustment_type === 'remove' ? 1 : 1"
              :max="form.adjustment_type === 'remove' ? selectedInventory?.qty_available : null"
              required
              :class="[
                'ds-field pr-12',
                form.errors.quantity ? 'border-[var(--color-danger-500)]' : 'border-[var(--ds-border)]'
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
            Novo nível de existências:
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
          <BaseSelect
            v-model="form.reason"
            :class="[
              'ds-field',
              form.errors.reason ? 'border-[var(--color-danger-500)]' : 'border-[var(--ds-border)]'
            ]"
            required
          >
            <option value="">Seleccione um Motivo</option>
            <option value="physical_count">Ajuste de Contagem Física</option>
            <option value="damaged">Itens Danificados/Perdidos</option>
            <option value="found_extra">Existências excedentes encontradas</option>
            <option value="quality_control">Rejeição de Controle de Qualidade</option>
            <option value="expired">Itens Expirados</option>
            <option value="calibration">Ajuste de Calibração</option>
            <option value="other">Outro</option>
          </BaseSelect>
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
            class="ds-field"
            placeholder="Adicione quaisquer observações adicionais..."
          ></textarea>
          <p v-if="form.errors.notes" class="text-xs text-red-600">
            {{ form.errors.notes }}
          </p>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-end gap-3 border-t border-[var(--ds-border)] pt-6">
          <button
            type="button"
            @click="close"
            class="ds-button ds-button-secondary"
          >
            Cancelar
          </button>
          <button
            type="button"
            @click="submit"
            :disabled="form.processing || !isFormValid"
            :class="[
              'ds-button',
              form.processing || !isFormValid
                ? 'ds-button-secondary'
                : form.adjustment_type === 'add'
                  ? 'ds-button-primary'
                  : 'ds-button-danger'
            ]"
          >
            <span v-if="form.processing">Processando...</span>
            <span v-else>{{ form.adjustment_type === 'add' ? 'Adicionar existências' : 'Remover existências' }}</span>
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
