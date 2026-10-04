<template>
  <div class="pl-page space-y-6" :class="commercialDocumentThemeClasses">
    <PageHeader :trail="[{ title: 'Manutenção', url: route('vap-maintenance.tasks') }, { title: task ? 'Editar tarefa de manutenção' : 'Nova tarefa de manutenção' }]" :title="task ? 'Editar tarefa de manutenção' : 'Nova tarefa de manutenção'" lede="Registe uma actividade de calibração, verificação ou manutenção com equipamento, agenda, fornecedor e custo rastreáveis." />

    <div v-if="form.hasErrors" class="ds-panel ds-field-error p-4" role="alert">
      <p v-for="(error, field) in form.errors" :key="field">{{ error }}</p>
    </div>

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
              <BaseSelect v-model="form.category_id" required :disabled="Boolean(task)" :class="fieldClass('category_id')">
                <option value="">Seleccione uma categoria</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </BaseSelect>
              <span v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</span>
              <span v-if="task" class="ds-field-hint">A categoria faz parte do número emitido e não pode ser alterada.</span>
            </label>

            <label class="ds-field-group">
              <span class="ds-field-label">Equipamento <span class="ds-field-required">*</span></span>
              <BaseSelect v-model="form.equipment_id" required :class="fieldClass('equipment_id')">
                <option value="">Seleccione um equipamento</option>
                <option v-for="equipment in equipmentList" :key="equipment.id" :value="equipment.id">
                  {{ equipment.name }} ({{ equipment.internal_code || equipment.code || 'N/A' }})
                </option>
              </BaseSelect>
              <span v-if="form.errors.equipment_id" class="ds-field-error">{{ form.errors.equipment_id }}</span>
            </label>

            <div class="ds-field-group">
              <span class="ds-field-label">Número da tarefa</span>
              <p class="ds-copy text-sm">{{ task?.maintenance_task_no || 'Gerado automaticamente ao guardar.' }}</p>
            </div>

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
                  <option value="">Seleccione unidade</option>
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
                <option value="">Seleccione um fornecedor</option>
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
          <h3 class="text-base font-bold text-[var(--ds-text)]">Acções</h3>
          <div class="mt-4 space-y-3">
            <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing">
              <CheckCircleIcon class="h-4 w-4" />
              {{ form.processing ? 'A guardar...' : task ? 'Guardar alterações' : 'Criar tarefa' }}
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
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  Info as InformationCircleIcon,
  Calendar as CalendarIcon,
  Settings as Cog6ToothIcon,
  Truck as TruckIcon,
  Euro as CurrencyEuroIcon,
  CircleCheck as CheckCircleIcon,
  X as XMarkIcon,
} from '@lucide/vue'

const props = defineProps({
  task: { type: Object, default: null },
  today: String,
  categories: Array,
  equipment: Array,
  suppliers: Array,
})

const equipmentList = props.equipment ?? []

const defaults = {
  name: '',
  description: '',
  category_id: '',
  equipment_id: '',
  due_date: props.today,
  acceptance_criteria: '',
  range: '',
  calibration_certificate_no: '',
  periodicity: '',
  periodicity_unit: '',
  executed_by_supplier: false,
  supplier_id: '',
  obs: '',
  cost: 0,
  is_planned: true,
  calibration_points: '',
}
const form = useForm(Object.fromEntries(Object.entries(defaults).map(([key, value]) => [
  key, props.task?.[key] ?? value,
])))
form.due_date = String(form.due_date).slice(0, 10)

const fieldClass = (field) => [
  'ds-field',
  form.errors[field] ? 'border-rose-500 focus:border-rose-500 focus:shadow-[0_0_0_4px_rgb(244_63_94_/_0.12)]' : '',
]

const submit = () => {
  if (form.processing) return
  if (props.task) {
    form.put(route('vap-maintenance.tasks.update', props.task.id))
  } else {
    form.post(route('vap-maintenance.tasks.store'))
  }
}
</script>
