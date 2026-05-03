<template>
  <Modal :show="show" @close="close" max-width="lg">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <WrenchScrewdriverIcon class="h-5 w-5 text-purple-900" />
          Registrar Calibração de Equipamento
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-500">
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- EQUIPMENT INFO -->
        <div class="bg-gradient-to-r from-purple-50 to-white rounded-lg border border-purple-100 p-4">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                <WrenchScrewdriverIcon class="h-6 w-6 text-purple-900" />
              </div>
            </div>
            <div>
              <div class="text-sm font-semibold text-gray-900">{{ item.name }}</div>
              <div class="text-sm text-gray-500">{{ item.internal_code || 'Sem Código Interno' }}</div>
              <div class="text-xs text-gray-400">{{ item.category?.name || 'Sem Categoria' }}</div>
                <span v-if="item.next_calibration_date" 
                  :class="getCalibrationStatusClass(item)">
                  • Próxima Data de Calibração: {{ formatDate(item.next_calibration_date) }}
                </span>
                <span v-if="item.days_to_calibration !== null" 
                  :class="getDaysClass(item.days_to_calibration)">
                  • {{ Math.abs(item.days_to_calibration) }} dias {{ item.days_to_calibration < 0 ? 'atrasado' : 'restantes' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- CALIBRATION DETAILS -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Calibração
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.calibration_date"
              type="date"
              :max="new Date().toISOString().split('T')[0]"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.calibration_date ? 'border-red-300' : 'border-gray-300'
              ]"
            />
            <p v-if="form.errors.calibration_date" class="text-xs text-red-600">
              {{ form.errors.calibration_date }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data de Próxima Calibração
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.next_calibration_date"
              type="date"
              :min="form.calibration_date || new Date().toISOString().split('T')[0]"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.next_calibration_date ? 'border-red-300' : 'border-gray-300'
              ]"
            />
            <p v-if="form.errors.next_calibration_date" class="text-xs text-red-600">
              {{ form.errors.next_calibration_date }}
            </p>
            <div class="text-xs text-gray-500">
              Recomendado: {{ formatDate(recommendedNextDate) }}
            </div>
          </div>
        </div>

        <!-- CALIBRATION TYPE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Tipo de Calibração
            <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button
              type="button"
              v-for="type in calibrationTypes"
              :key="type.value"
              @click="form.calibration_type = type.value"
              :class="[
                'rounded-lg border p-2 text-xs font-medium transition-all',
                form.calibration_type === type.value
                  ? 'border-purple-900 bg-purple-50 text-purple-900'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              {{ type.label }}
            </button>
          </div>
          <p v-if="form.errors.calibration_type" class="text-xs text-red-600">
            {{ form.errors.calibration_type }}
          </p>
        </div>

        <!-- PERFORMED BY -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Realizado Por
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.performed_by"
              type="text"
              required
              :class="[
                'w-full rounded-lg border px-3 py-2.5 text-sm',
                form.errors.performed_by ? 'border-red-300' : 'border-gray-300'
              ]"
              placeholder="Digite o nome do técnico"
            />
            <p v-if="form.errors.performed_by" class="text-xs text-red-600">
              {{ form.errors.performed_by }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Empresa / Fornecedor de Serviço
            </label>
            <input
              v-model="form.service_provider"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
              placeholder="Digite o nome da empresa"
            />
          </div>
        </div>

        <!-- CALIBRATION RESULTS -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Resultados da Calibração
            <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-3 gap-3">
            <button
              type="button"
              v-for="result in calibrationResults"
              :key="result.value"
              @click="form.result = result.value"
              :class="[
                'rounded-lg border p-3 text-sm font-medium transition-all',
                form.result === result.value
                  ? result.borderClass + ' ' + result.bgClass + ' ' + result.textClass
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              <div class="flex items-center justify-center gap-2">
                <component :is="result.icon" class="h-4 w-4" />
                {{ result.label }}
              </div>
            </button>
          </div>
          <p v-if="form.errors.result" class="text-xs text-red-600">
            {{ form.errors.result }}
          </p>
        </div>

        <!-- MEASUREMENT DATA -->
        <div class="space-y-4">
          <h4 class="text-sm font-semibold text-gray-900">Dados de Medição</h4>
          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Antes da Calibração
              </label>
              <input
                v-model="form.measurement_before"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                placeholder="Ex.: -0,5%"
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Depois da Calibração
              </label>
              <input
                v-model="form.measurement_after"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                placeholder="Ex.: +0,1%"
              />
            </div>
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Tolerância
              </label>
              <input
                v-model="form.tolerance"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                placeholder="Ex.: ±0,5%"
              />
            </div>
          </div>
        </div>

        <!-- CALIBRATION STANDARDS -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Normativas Usadas
          </label>
          <input
            v-model="form.standards_used"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Indique as normas ou padrões de calibração utilizados"
          />
        </div>

        <!-- CERTIFICATE INFORMATION -->
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Número do Certificado
            </label>
            <input
              v-model="form.certificate_number"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
              placeholder="Indique o número do certificado"
            />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Data do Certificado
            </label>
            <input
              v-model="form.certificate_date"
              type="date"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            />
          </div>
        </div>

        <!-- ATTACHMENTS -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Anexos
          </label>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <DocumentArrowUpIcon class="mx-auto h-8 w-8 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500">
              Arraste e solte certificados de calibração ou fotos aqui, ou
              <button type="button" class="text-blue-900 hover:text-blue-800">
                navegue
              </button>
            </p>
            <p class="text-xs text-gray-400 mt-1">
              Suporta PDF, JPG, PNG até 10MB
            </p>
          </div>
        </div>

        <!-- NOTES -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            Observações / Notas
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
            placeholder="Adicione observações relevantes sobre a calibração..."
          ></textarea>
          <p v-if="form.errors.notes" class="text-xs text-red-600">
            {{ form.errors.notes }}
          </p>
        </div>

        <!-- CALIBRATION SUMMARY -->
        <div v-if="form.calibration_date && form.next_calibration_date" 
          class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 p-4">
          <h4 class="text-sm font-semibold text-gray-900 mb-2">Resumo da Calibração</h4>
          <div class="space-y-1 text-sm text-gray-600">
            <div class="flex items-center justify-between">
              <span>Equipamento:</span>
              <span class="font-medium">{{ item.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Data de Calibração:</span>
              <span class="font-medium">{{ formatDate(form.calibration_date) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Próxima Data de Calibração:</span>
              <span class="font-medium">{{ formatDate(form.next_calibration_date) }}</span>
            </div>
            <div v-if="form.performed_by" class="flex items-center justify-between">
              <span>Realizado Por:</span>
              <span class="font-medium">{{ form.performed_by }}</span>
            </div>
            <div v-if="form.result" class="flex items-center justify-between">
              <span>Resultado:</span>
              <span :class="getResultClass(form.result)">
                {{ getResultLabel(form.result) }}
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
                : 'bg-purple-900 hover:bg-purple-800'
            ]"
          >
            <span v-if="form.processing">Processando...</span>
            <span v-else>Registrar Calibração</span>
          </button>
        </div>
      </div>
    
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  DocumentArrowUpIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  show: Boolean,
  item: Object,
})

const emit = defineEmits(['close', 'success'])

const calibrationTypes = [
  { value: 'internal', label: 'Interno' },
  { value: 'external', label: 'Externo' },
  { value: 'on_site', label: 'Em Sitio' },
  { value: 'in_house', label: 'Localmente' },
]

const calibrationResults = [
  { 
    value: 'passed', 
    label: 'Aceite',
    icon: CheckCircleIcon,
    borderClass: 'border-green-900',
    bgClass: 'bg-green-50',
    textClass: 'text-green-900'
  },
  { 
    value: 'passed_with_adjustment', 
    label: 'Aceite com Ajuste',
    icon: CheckCircleIcon,
    borderClass: 'border-yellow-900',
    bgClass: 'bg-yellow-50',
    textClass: 'text-yellow-900'
  },
  { 
    value: 'failed', 
    label: 'Falhou',
    icon: XCircleIcon,
    borderClass: 'border-red-900',
    bgClass: 'bg-red-50',
    textClass: 'text-red-900'
  },
]

const form = useForm({
  calibration_date: new Date().toISOString().split('T')[0],
  next_calibration_date: '',
  calibration_type: 'external',
  performed_by: '',
  service_provider: '',
  result: 'passed',
  measurement_before: '',
  measurement_after: '',
  tolerance: '',
  standards_used: '',
  certificate_number: '',
  certificate_date: '',
  notes: '',
})

const isFormValid = computed(() => {
  return form.calibration_date && 
         form.next_calibration_date && 
         form.performed_by && 
         form.result
})

const recommendedNextDate = computed(() => {
  if (!form.calibration_date) return ''
  
  const calibrationDate = new Date(form.calibration_date)
  // Default to 1 year from calibration date
  calibrationDate.setFullYear(calibrationDate.getFullYear() + 1)
  return calibrationDate.toISOString().split('T')[0]
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getCalibrationStatusClass = (item) => {
  if (item.needs_calibration) return 'text-red-900'
  if (item.days_to_calibration <= 30) return 'text-orange-900'
  if (item.days_to_calibration <= 90) return 'text-yellow-900'
  return 'text-green-900'
}

const getDaysClass = (days) => {
  if (days <= 0) return 'text-red-900'
  if (days <= 30) return 'text-orange-900'
  if (days <= 90) return 'text-yellow-900'
  return 'text-green-900'
}

const getResultClass = (result) => {
  switch (result) {
    case 'passed': return 'text-green-900 font-semibold'
    case 'passed_with_adjustment': return 'text-yellow-900 font-semibold'
    case 'failed': return 'text-red-900 font-semibold'
    default: return 'text-gray-900'
  }
}

const getResultLabel = (result) => {
  switch (result) {
    case 'passed': return 'Passed'
    case 'passed_with_adjustment': return 'Passed with Adjustment'
    case 'failed': return 'Failed'
    default: return result
  }
}

const submit = () => {
  if (!isFormValid.value) return

  form.put(route('vap-inventory.calibration.record', props.item.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('success')
      close()
    },
  })
}

const close = () => {
  form.reset()
  form.calibration_date = new Date().toISOString().split('T')[0]
  emit('close')
}
</script>
