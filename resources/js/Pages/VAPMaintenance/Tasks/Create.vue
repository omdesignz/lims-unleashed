<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            Nova Tarefa de Manutenção
          </h1>
          <p class="mt-2 text-gray-600">
            Crie uma nova tarefa de manutenção ou calibração para equipamento
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-maintenance.tasks')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- MAIN FORM (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              Informação Básica
            </h2>
          </div>
          
          <!-- FORM CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- TASK NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Nome da Tarefa *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    form.errors.name
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                  placeholder="Ex: Calibração Anual do Espectrofotómetro"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>
              
              <!-- CATEGORY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Categoria *
                </label>
                <select
                  v-model="form.category_id"
                  required
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    form.errors.category_id
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                >
                  <option value="">Selecione uma categoria</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <p v-if="form.errors.category_id" class="text-xs text-red-600">
                  {{ form.errors.category_id }}
                </p>
              </div>
              
              <!-- EQUIPMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Equipamento *
                </label>
                <select
                  v-model="form.equipment_id"
                  required
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    form.errors.equipment_id
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                >
                  <option value="">Selecione um equipamento</option>
                  <option v-for="equipment in equipmentList" :key="equipment.id" :value="equipment.id">
                    {{ equipment.name }} ({{ equipment.internal_code || equipment.code }})
                  </option>
                </select>
                <p v-if="form.errors.equipment_id" class="text-xs text-red-600">
                  {{ form.errors.equipment_id }}
                </p>
              </div>
              
              <!-- TASK NUMBER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Número da Tarefa
                </label>
                <input
                  v-model="form.maintenance_task_no"
                  type="text"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    form.errors.maintenance_task_no
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                  placeholder="Deixe em branco para gerar automaticamente"
                />
                <p v-if="form.errors.maintenance_task_no" class="text-xs text-red-600">
                  {{ form.errors.maintenance_task_no }}
                </p>
              </div>
              
              <!-- DESCRIPTION -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Descrição
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Descreva os detalhes da tarefa de manutenção..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- SCHEDULING -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <CalendarIcon class="h-5 w-5" />
              Agendamento
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- DUE DATE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Data de Vencimento *
                </label>
                <input
                  v-model="form.due_date"
                  type="date"
                  required
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm focus:ring-2 focus:ring-offset-1',
                    form.errors.due_date
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                />
                <p v-if="form.errors.due_date" class="text-xs text-red-600">
                  {{ form.errors.due_date }}
                </p>
              </div>
              
              <!-- IS PLANNED -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Estado
                </label>
                <div class="flex items-center gap-4">
                  <label class="inline-flex items-center">
                    <input
                      v-model="form.is_planned"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                    <span class="ml-2 text-sm text-gray-700">Tarefa Planeada</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input
                      v-model="form.executed_by_supplier"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                    <span class="ml-2 text-sm text-gray-700">Executada por Fornecedor</span>
                  </label>
                </div>
              </div>
              
              <!-- PERIODICITY -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Periodicidade
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.periodicity"
                    type="number"
                    min="1"
                    class="w-24 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                    placeholder="1"
                  />
                  <select
                    v-model="form.periodicity_unit"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  >
                    <option value="">Selecione unidade</option>
                    <option value="hours">Horas</option>
                    <option value="days">Dias</option>
                    <option value="weeks">Semanas</option>
                    <option value="months">Meses</option>
                    <option value="years">Anos</option>
                  </select>
                </div>
                <p class="text-xs text-gray-500">
                  Definir periodicidade para tarefas recorrentes
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- TECHNICAL DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <Cog6ToothIcon class="h-5 w-5" />
              Detalhes Técnicos
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- ACCEPTANCE CRITERIA -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Critério de Aceitação
                </label>
                <input
                  v-model="form.acceptance_criteria"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Ex: ±0.5% de precisão"
                />
              </div>
              
              <!-- RANGE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Gama
                </label>
                <input
                  v-model="form.range"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Ex: 0-1000 mg/L"
                />
              </div>
              
              <!-- CALIBRATION POINTS -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Pontos de Calibração
                </label>
                <textarea
                  v-model="form.calibration_points"
                  rows="2"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Liste os pontos de calibração necessários..."
                ></textarea>
              </div>
              
              <!-- OBSERVATIONS -->
              <div class="md:col-span-2 space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Observações
                </label>
                <textarea
                  v-model="form.obs"
                  rows="2"
                  class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                  placeholder="Quaisquer observações adicionais..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- SUPPLIER INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <TruckIcon class="h-5 w-5 text-blue-900" />
            Fornecedor
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Fornecedor
              </label>
              <select
                v-model="form.supplier_id"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
              >
                <option value="">Selecione um fornecedor</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Certificado de Calibração
              </label>
              <input
                v-model="form.calibration_certificate_no"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Número do certificado"
              />
            </div>
          </div>
        </div>

        <!-- COST INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CurrencyEuroIcon class="h-5 w-5 text-blue-900" />
            Custos
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Custo (AOA)
              </label>
              <input
                v-model="form.cost"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="0.00"
              />
            </div>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções
          </h3>
          <div class="space-y-4">
            <button
              @click="submit"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? 'A processar...' : 'Criar Tarefa' }}
            </button>
            
            <Link
              :href="route('vap-maintenance.tasks')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <XMarkIcon class="h-5 w-5" />
              Cancelar
            </Link>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                Informação Rápida
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total de Tarefas</span>
                  <span class="font-semibold text-blue-900">{{ stats?.total_tasks || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Atrasadas</span>
                  <span class="font-semibold text-red-600">{{ stats?.overdue || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  CalendarIcon,
  Cog6ToothIcon,
  TruckIcon,
  CurrencyEuroIcon,
  CheckCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  categories: Array,
  equipment: Array,
  suppliers: Array,
  stats: Object,
})

const equipmentList = props.equipment

const form = useForm({
  name: '',
  description: '',
  category_id: '',
  equipment_id: '',
  due_date: new Date().toISOString().split('T')[0],
  previous_date: null,
  next_date: null,
  maintenance_task_no: '',
  maintenance_task_year: new Date().getFullYear().toString(),
  acceptance_criteria: '',
  range: '',
  calibration_status: 'pending',
  calibration_certificate_no: '',
  periodicity: '',
  periodicity_unit: '',
  executed_by_supplier: false,
  supplier_id: '',
  obs: '',
  cost: 0,
  is_planned: true,
  is_executed: false,
  calibration_points: '',
  result: '',
  seq: null,
})

const submit = () => {
  form.post(route('vap-maintenance.tasks.store'), {
    onSuccess: () => {
      router.visit(route('vap-maintenance.tasks'))
    }
  })
}
</script>