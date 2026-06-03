<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            Detalhes da Tarefa de Manutenção
          </h1>
          <div class="mt-2 flex items-center gap-4">
            <span :class="getStatusClasses(task)">
              {{ getStatusText(task) }}
            </span>
            <span class="text-sm text-gray-600">
              <CalendarIcon class="h-4 w-4 inline mr-1" />
              Criada em {{ formatDateTime(task.created_at) }}
            </span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-maintenance.tasks')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar à Lista
          </Link>
          <Link
            :href="route('vap-maintenance.tasks.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <DocumentDuplicateIcon class="h-5 w-5" />
            Duplicar
          </Link>
        </div>
      </div>
    </div>

    <!-- TASK DETAILS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- MAIN CONTENT (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- TASK INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-white">
                {{ task.name }}
              </h2>
              <div class="text-white text-sm font-medium">
                {{ task.maintenance_task_no }}
              </div>
            </div>
          </div>
          
          <!-- TASK CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- BASIC INFO -->
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Categoria
                  </label>
                  <div class="flex items-center gap-2">
                    <TagIcon class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900">
                      {{ task.category?.name || 'Não definida' }}
                    </span>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Equipamento
                  </label>
                  <div class="flex items-center gap-2">
                    <CogIcon class="h-4 w-4 text-gray-400" />
                    <span class="text-sm font-medium text-gray-900">
                      {{ task.equipment?.name || 'Equipamento não encontrado' }}
                    </span>
                  </div>
                  <div v-if="task.equipment" class="ml-6 text-xs text-gray-500 mt-1">
                    {{ task.equipment.internal_code || 'Sem código' }}
                    <span v-if="task.equipment.model">• {{ task.equipment.model }}</span>
                    <span v-if="task.equipment.serial_number">• S/N: {{ task.equipment.serial_number }}</span>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Descrição
                  </label>
                  <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-4">
                    {{ task.description || 'Sem descrição' }}
                  </div>
                </div>
              </div>

              <!-- SCHEDULING INFO -->
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Datas
                  </label>
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">Vencimento:</span>
                      <span :class="getDueDateColor(task)" class="font-semibold">
                        {{ formatDate(task.due_date) }}
                      </span>
                    </div>
                    <div v-if="task.previous_date" class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">Anterior:</span>
                      <span class="text-sm font-medium text-gray-900">
                        {{ formatDate(task.previous_date) }}
                      </span>
                    </div>
                    <div v-if="task.next_date" class="flex items-center justify-between">
                      <span class="text-sm text-gray-600">Próximo:</span>
                      <span class="text-sm font-medium text-gray-900">
                        {{ formatDate(task.next_date) }}
                      </span>
                    </div>
                    <div v-if="task.periodicity" class="mt-3 p-3 bg-blue-50 rounded-lg">
                      <div class="flex items-center gap-2 text-sm text-blue-900">
                        <ArrowPathRoundedSquareIcon class="h-4 w-4" />
                        <span>Recorrência: A cada {{ task.periodicity }} {{ getPeriodicityUnitText(task.periodicity_unit) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Estado
                  </label>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <span :class="[
                        'h-3 w-3 rounded-full',
                        task.is_planned ? 'bg-blue-900' : 'bg-gray-300'
                      ]"></span>
                      <span class="text-sm text-gray-700">
                        {{ task.is_planned ? 'Tarefa Planeada' : 'Tarefa Não Planeada' }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span :class="[
                        'h-3 w-3 rounded-full',
                        task.is_executed ? 'bg-green-900' : 'bg-gray-300'
                      ]"></span>
                      <span class="text-sm text-gray-700">
                        {{ task.is_executed ? 'Tarefa Executada' : 'Tarefa Pendente' }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span :class="[
                        'h-3 w-3 rounded-full',
                        task.executed_by_supplier ? 'bg-purple-900' : 'bg-gray-300'
                      ]"></span>
                      <span class="text-sm text-gray-700">
                        {{ task.executed_by_supplier ? 'Executada por Fornecedor' : 'Executada Internamente' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TECHNICAL DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white">
              Detalhes Técnicos
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CALIBRATION DETAILS -->
              <div class="space-y-4">
                <div v-if="task.acceptance_criteria">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Critério de Aceitação
                  </label>
                  <div class="text-sm font-medium text-gray-900 bg-green-50 rounded-lg p-3">
                    {{ task.acceptance_criteria }}
                  </div>
                </div>

                <div v-if="task.range">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Gama
                  </label>
                  <div class="text-sm font-medium text-gray-900">
                    {{ task.range }}
                  </div>
                </div>

                <div v-if="task.calibration_points">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Pontos de Calibração
                  </label>
                  <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 whitespace-pre-line">
                    {{ task.calibration_points }}
                  </div>
                </div>
              </div>

              <!-- RESULTS & CERTIFICATES -->
              <div class="space-y-4">
                <div v-if="task.calibration_certificate_no">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Certificado de Calibração
                  </label>
                  <div class="flex items-center gap-2">
                    <DocumentTextIcon class="h-4 w-4 text-blue-900" />
                    <span class="text-sm font-medium text-blue-900">
                      {{ task.calibration_certificate_no }}
                    </span>
                  </div>
                  <div v-if="task.calibration_status" class="ml-6 mt-1">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                      task.calibration_status === 'approved' ? 'bg-green-100 text-green-800' :
                      task.calibration_status === 'rejected' ? 'bg-red-100 text-red-800' :
                      'bg-yellow-100 text-yellow-800'
                    ]">
                      {{ getCalibrationStatusText(task.calibration_status) }}
                    </span>
                  </div>
                </div>

                <div v-if="task.result">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Resultado da Manutenção
                  </label>
                  <div class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 whitespace-pre-line">
                    {{ task.result }}
                  </div>
                </div>

                <div v-if="task.obs">
                  <label class="block text-xs font-medium text-gray-500 mb-1">
                    Observações
                  </label>
                  <div class="text-sm text-gray-700 bg-yellow-50 rounded-lg p-3 whitespace-pre-line">
                    {{ task.obs }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SIDEBAR (1/3 width) -->
      <div class="space-y-6">
        <!-- COST & SUPPLIER -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CurrencyEuroIcon class="h-5 w-5 text-blue-900" />
            Custo e Fornecedor
          </h3>
          <div class="space-y-4">
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-900">
                {{ formatCurrency(task.cost) }}
              </div>
              <div class="text-sm text-gray-500 mt-1">Custo da Tarefa</div>
            </div>

            <div v-if="task.supplier" class="pt-4 border-t border-gray-200">
              <label class="block text-xs font-medium text-gray-500 mb-2">
                Fornecedor
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <TruckIcon class="h-5 w-5 text-gray-400" />
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ task.supplier.name }}
                  </div>
                  <div v-if="task.supplier.email" class="text-xs text-gray-500">
                    {{ task.supplier.email }}
                  </div>
                  <div v-if="task.supplier.phone" class="text-xs text-gray-500">
                    {{ task.supplier.phone }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="task.executed_by_supplier && !task.supplier" class="pt-4 border-t border-gray-200">
              <div class="text-center text-sm text-gray-500">
                <ExclamationTriangleIcon class="h-5 w-5 text-orange-500 mx-auto mb-2" />
                Tarefa marcada como executada por fornecedor,<br>
                mas nenhum fornecedor foi especificado.
              </div>
            </div>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções
          </h3>
          <div class="space-y-3">
            <button
              v-if="!task.is_executed"
              @click="markAsExecuted"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700"
            >
              <CheckCircleIcon class="h-5 w-5" />
              Marcar como Executada
            </button>

            <button
              v-if="task.is_executed"
              @click="recordResult"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
            >
              <PencilIcon class="h-5 w-5" />
              Registar Resultado
            </button>

            <Link
              :href="route('vap-maintenance.tasks.create')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              Duplicar Tarefa
            </Link>

            <button
              @click="printTask"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <PrinterIcon class="h-5 w-5" />
              Imprimir
            </button>

            <button
              @click="notifyCompletion"
              v-if="task.is_executed"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-purple-600 bg-purple-50 px-4 py-3 text-sm font-medium text-purple-900 hover:bg-purple-100"
            >
              <BellAlertIcon class="h-5 w-5" />
              Notificar Conclusão
            </button>

            <button
              @click="deleteTask"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-red-600 bg-red-50 px-4 py-3 text-sm font-medium text-red-900 hover:bg-red-100"
            >
              <TrashIcon class="h-5 w-5" />
              Eliminar Tarefa
            </button>
          </div>
        </div>

        <!-- TASK HISTORY -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            Histórico
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Criada em:</span>
              <span class="font-medium text-gray-900">{{ formatDateTime(task.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Última atualização:</span>
              <span class="font-medium text-gray-900">{{ formatDateTime(task.updated_at) }}</span>
            </div>
            <div v-if="task.deleted_at" class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Eliminada em:</span>
              <span class="font-medium text-red-900">{{ formatDateTime(task.deleted_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- EQUIPMENT SUMMARY -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <CogIcon class="h-5 w-5 text-blue-900" />
          Histórico do Equipamento
        </h2>
        <Link
          :href="route('vap-inventory.items.show', task.equipment?.id)"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          <ArrowRightIcon class="h-4 w-4" />
          Ver Detalhes do Equipamento
        </Link>
      </div>

      <div v-if="equipmentHistory" class="space-y-4">
        <!-- EQUIPMENT STATS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Total de Tarefas</div>
            <div class="text-2xl font-bold text-blue-900 mt-1">
              {{ equipmentHistory.stats.total_tasks }}
            </div>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Tarefas Executadas</div>
            <div class="text-2xl font-bold text-green-900 mt-1">
              {{ equipmentHistory.stats.executed_tasks }}
            </div>
          </div>
          <div class="bg-purple-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Custo Total</div>
            <div class="text-2xl font-bold text-purple-900 mt-1">
              {{ formatCurrency(equipmentHistory.stats.total_cost) }}
            </div>
          </div>
          <div class="bg-orange-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">Custo Médio</div>
            <div class="text-2xl font-bold text-orange-900 mt-1">
              {{ formatCurrency(equipmentHistory.stats.avg_cost) }}
            </div>
          </div>
        </div>

        <!-- RECENT TASKS -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 mb-3">Tarefas Recentes</h3>
          <div class="space-y-2">
            <div
              v-for="historyTask in equipmentHistory.tasks.slice(0, 5)"
              :key="historyTask.id"
              class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50"
            >
              <div class="flex items-center gap-3">
                <div :class="[
                  'h-8 w-8 rounded-lg flex items-center justify-center',
                  historyTask.is_executed ? 'bg-green-100' : 'bg-red-100'
                ]">
                  <WrenchScrewdriverIcon :class="[
                    'h-4 w-4',
                    historyTask.is_executed ? 'text-green-900' : 'text-red-900'
                  ]" />
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ historyTask.name }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ historyTask.category?.name }} • {{ formatDate(historyTask.due_date) }}
                  </div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatCurrency(historyTask.cost) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ historyTask.is_executed ? 'Executada' : 'Pendente' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        <CogIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
        <p>Nenhum histórico disponível para este equipamento</p>
      </div>
    </div>
  </div>

  <!-- RECORD RESULT MODAL -->
  <Modal :show="showRecordResultModal" @close="showRecordResultModal = false">
    <div class="p-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-6">
        Registar Resultado da Manutenção
      </h2>
      
      <form @submit.prevent="submitResult">
        <div class="space-y-6">
          <!-- RESULT -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Resultado da Manutenção *
            </label>
            <textarea
              v-model="resultForm.result"
              rows="6"
              required
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-blue-900"
              placeholder="Descreva os resultados da manutenção, observações, peças substituídas, etc..."
            ></textarea>
            <p v-if="resultForm.errors.result" class="mt-1 text-xs text-red-600">
              {{ resultForm.errors.result }}
            </p>
          </div>

          <!-- CALIBRATION STATUS -->
          <div v-if="isCalibrationTask">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Estado da Calibração
            </label>
            <select
              v-model="resultForm.calibration_status"
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-blue-900"
            >
              <option value="">Selecione um estado</option>
              <option value="approved">Aprovado</option>
              <option value="rejected">Rejeitado</option>
              <option value="pending">Pendente</option>
            </select>
          </div>

          <!-- NEXT DATE -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Próxima Manutenção
            </label>
            <input
              v-model="resultForm.next_date"
              type="date"
              class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-900 focus:ring-blue-900"
            />
            <p class="mt-1 text-xs text-gray-500">
              Deixe em branco para calcular automaticamente com base na periodicidade
            </p>
          </div>

          <!-- FORM ACTIONS -->
          <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="showRecordResultModal = false"
              class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="resultForm.processing"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ resultForm.processing ? 'A processar...' : 'Registar Resultado' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  ArrowLeftIcon,
  DocumentDuplicateIcon,
  TagIcon,
  CogIcon,
  CalendarIcon,
  ArrowPathRoundedSquareIcon,
  DocumentTextIcon,
  CurrencyEuroIcon,
  TruckIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  PencilIcon,
  PrinterIcon,
  BellAlertIcon,
  TrashIcon,
  ClockIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  task: Object,
})

// State
const showRecordResultModal = ref(false)
const equipmentHistory = ref(null)

// Forms
const resultForm = useForm({
  result: props.task.result || '',
  calibration_status: props.task.calibration_status || '',
  next_date: props.task.next_date ? new Date(props.task.next_date).toISOString().split('T')[0] : '',
})

// Computed
const isCalibrationTask = computed(() => {
  return props.task.category?.code?.includes('CAL') || 
         ['Calibration', 'Calibração'].includes(props.task.category?.name || '')
})

// Methods
const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount || 0)
}

const getPeriodicityUnitText = (unit) => {
  const units = {
    hours: 'horas',
    days: 'dias',
    weeks: 'semanas',
    months: 'meses',
    years: 'anos'
  }
  return units[unit] || unit
}

const getStatusClasses = (task) => {
  if (task.is_executed) {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (daysDiff <= 7) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (daysDiff <= 30) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800'
  }
}

const getStatusText = (task) => {
  if (task.is_executed) return 'Concluída'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'Atrasada'
  if (daysDiff <= 7) return 'Vencendo em Breve'
  if (daysDiff <= 30) return 'Próxima'
  return 'Agendada'
}

const getDueDateColor = (task) => {
  if (task.is_executed) return 'text-green-900'
  
  const dueDate = new Date(task.due_date)
  const today = new Date()
  const daysDiff = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24))
  
  if (daysDiff < 0) return 'text-red-900'
  if (daysDiff <= 7) return 'text-orange-900'
  if (daysDiff <= 30) return 'text-yellow-900'
  return 'text-blue-900'
}

const getCalibrationStatusText = (status) => {
  const statusMap = {
    approved: 'Aprovado',
    rejected: 'Rejeitado',
    pending: 'Pendente'
  }
  return statusMap[status] || status
}

const markAsExecuted = () => {
  if (confirm('Marcar esta tarefa como executada?')) {
    router.put(route('vap-maintenance.tasks.update', props.task.id), {
      is_executed: true
    })
  }
}

const recordResult = () => {
  showRecordResultModal.value = true
}

const submitResult = () => {
  resultForm.put(route('vap-maintenance.tasks.update', props.task.id), {
    onSuccess: () => {
      showRecordResultModal.value = false
    }
  })
}

const printTask = () => {
  window.open(route('vap-maintenance.export', {
    format: 'pdf',
    type: 'task',
    task_id: props.task.id
  }), '_blank')
}

const notifyCompletion = async () => {
  try {
    await axios.post(route('vap-maintenance.tasks.notify-completion', props.task.id))
    alert('Notificação de conclusão enviada com sucesso!')
  } catch (error) {
    console.error('Error sending completion notification:', error)
    alert('Erro ao enviar notificação')
  }
}

const deleteTask = () => {
  if (confirm('Tem a certeza que deseja eliminar esta tarefa? Esta ação não pode ser revertida.')) {
    router.delete(route('vap-maintenance.tasks.destroy', props.task.id))
  }
}

const loadEquipmentHistory = async () => {
  try {
    const response = await axios.get(route('vap-maintenance.equipment.history', props.task.equipment_id))
    equipmentHistory.value = response.data
  } catch (error) {
    console.error('Error loading equipment history:', error)
  }
}

onMounted(() => {
  if (props.task.equipment_id) {
    loadEquipmentHistory()
  }
})
</script>
