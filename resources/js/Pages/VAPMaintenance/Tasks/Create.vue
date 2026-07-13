<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:flex sm:items-start sm:justify-between sm:gap-6 lg:px-6">
        <div class="min-w-0">
          <p class="ds-kicker">Metrologia e manutenção</p>
          <h1 class="ds-heading mt-2 text-2xl">Nova tarefa de manutenção</h1>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            Registe uma atividade de calibração, verificação ou manutenção com equipamento, agenda, fornecedor e custo rastreáveis.
          </p>
        </div>

        <Link :href="route('vap-maintenance.tasks')" class="ds-button ds-button-secondary mt-4 sm:mt-0">
          <ArrowLeftIcon class="h-4 w-4" />
          Voltar
        </Link>
      </div>
    </section>

    <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]" @submit.prevent="submit">
      <div class="space-y-6">
        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <InformationCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
              Informação básica
            </h2>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-2">
            <label class="ds-field-group">
              <span class="ds-field-label">Nome da tarefa <span class="ds-field-required">*</span></span>
              <BaseInput
                v-model="form.name"
                type="text"
                required
                :class="fieldClass('name')"
                placeholder="Ex: Calibração anual do espectrofotómetro"
              />
              <span v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</span>
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Categoria <span class="ds-field-required">*</span></span>
              <BaseSelect v-model="form.category_id" required :class="fieldClass('category_id')">
                <option value="">Selecione uma categoria</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </BaseSelect>
              <span v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</span>
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Equipamento <span class="ds-field-required">*</span></span>
              <BaseSelect v-model="form.equipment_id" required :class="fieldClass('equipment_id')">
                <option value="">Selecione um equipamento</option>
                <option v-for="equipment in equipmentList" :key="equipment.id" :value="equipment.id">
                  {{ equipment.name }} ({{ equipment.internal_code || equipment.code || 'N/A' }})
                </option>
              </BaseSelect>
              <span v-if="form.errors.equipment_id" class="ds-field-error">{{ form.errors.equipment_id }}</span>
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Número da tarefa</span>
              <BaseInput
                v-model="form.maintenance_task_no"
                type="text"
                :class="fieldClass('maintenance_task_no')"
                placeholder="Gerado automaticamente se vazio"
              />
              <span v-if="form.errors.maintenance_task_no" class="ds-field-error">{{ form.errors.maintenance_task_no }}</span>
            </label>

            <label class="ds-field-group md:col-span-2">
              <span class="ds-field-label">Descrição</span>
              <textarea
                v-model="form.description"
                rows="3"
                class="ds-field min-h-28"
                placeholder="Detalhes da intervenção, requisitos ou contexto operacional"
              />
            </label>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <CalendarIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
              Agendamento
            </h2>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-2">
            <label class="ds-field-group">
              <span class="ds-field-label">Data de vencimento <span class="ds-field-required">*</span></span>
              <DateTimePicker v-model="form.due_date" type="date" required :class="fieldClass('due_date')" />
              <span v-if="form.errors.due_date" class="ds-field-error">{{ form.errors.due_date }}</span>
            </label>

            <div class="ds-field-group">
              <span class="ds-field-label">Estado inicial</span>
              <div class="grid gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                <label class="flex items-center gap-3 text-sm font-semibold text-[var(--ds-text-muted)]">
                  <CheckboxInput v-model="form.is_planned" type="checkbox" class="ds-checkbox" />
                  Tarefa planeada
                </label>
                <label class="flex items-center gap-3 text-sm font-semibold text-[var(--ds-text-muted)]">
                  <CheckboxInput v-model="form.executed_by_supplier" type="checkbox" class="ds-checkbox" />
                  Executada por fornecedor
                </label>
              </div>
            </div>

            <div class="ds-field-group md:col-span-2">
              <span class="ds-field-label">Periodicidade</span>
              <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
                <BaseInput v-model="form.periodicity" type="number" min="1" class="ds-field" placeholder="1" />
                <BaseSelect v-model="form.periodicity_unit" class="ds-field">
                  <option value="">Selecione unidade</option>
                  <option value="hours">Horas</option>
                  <option value="days">Dias</option>
                  <option value="weeks">Semanas</option>
                  <option value="months">Meses</option>
                  <option value="years">Anos</option>
                </BaseSelect>
              </div>
              <span class="ds-field-hint">Use apenas para tarefas recorrentes.</span>
            </div>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
              <Cog6ToothIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
              Detalhes técnicos
            </h2>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-2">
            <label class="ds-field-group">
              <span class="ds-field-label">Critério de aceitação</span>
              <BaseInput v-model="form.acceptance_criteria" type="text" class="ds-field" placeholder="Ex: +/- 0.5% de precisão" />
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Gama</span>
              <BaseInput v-model="form.range" type="text" class="ds-field" placeholder="Ex: 0-1000 mg/L" />
            </label>

            <label class="ds-field-group md:col-span-2">
              <span class="ds-field-label">Pontos de calibração</span>
              <textarea v-model="form.calibration_points" rows="2" class="ds-field min-h-24" placeholder="Liste os pontos de calibração necessários" />
            </label>

            <label class="ds-field-group md:col-span-2">
              <span class="ds-field-label">Observações</span>
              <textarea v-model="form.obs" rows="2" class="ds-field min-h-24" placeholder="Observações adicionais" />
            </label>
          </div>
        </section>
      </div>

      <aside class="space-y-6">
        <section class="ds-card p-5">
          <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <TruckIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Fornecedor
          </h3>
          <div class="mt-5 space-y-4">
            <label class="ds-field-group">
              <span class="ds-field-label">Fornecedor</span>
              <BaseSelect v-model="form.supplier_id" class="ds-field">
                <option value="">Selecione um fornecedor</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </BaseSelect>
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Certificado de calibração</span>
              <BaseInput v-model="form.calibration_certificate_no" type="text" class="ds-field" placeholder="Número do certificado" />
            </label>
          </div>
        </section>

        <section class="ds-card p-5">
          <h3 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]">
            <CurrencyEuroIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb)/1)]" />
            Custos
          </h3>
          <label class="ds-field-group mt-5">
            <span class="ds-field-label">Custo (AOA)</span>
            <BaseInput v-model="form.cost" type="number" step="0.01" min="0" class="ds-field" placeholder="0.00" />
          </label>
        </section>

        <section class="ds-command-surface p-5">
          <h3 class="text-base font-bold text-[var(--ds-text)]">Ações</h3>
          <div class="mt-4 space-y-3">
            <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing">
              <CheckCircleIcon class="h-4 w-4" />
              {{ form.processing ? 'A processar...' : 'Criar tarefa' }}
            </button>

            <Link :href="route('vap-maintenance.tasks')" class="ds-button ds-button-secondary w-full">
              <XMarkIcon class="h-4 w-4" />
              Cancelar
            </Link>
          </div>
        </section>
      </aside>
    </form>
  </div>
</template>

<script setup>
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
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
})

const equipmentList = props.equipment ?? []

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

const fieldClass = (field) => [
  'ds-field',
  form.errors[field] ? 'border-rose-500 focus:border-rose-500 focus:shadow-[0_0_0_4px_rgb(244_63_94_/_0.12)]' : '',
]

const submit = () => {
  form.post(route('vap-maintenance.tasks.store'), {
    onSuccess: () => {
      router.visit(route('vap-maintenance.tasks'))
    }
  })
}
</script>
