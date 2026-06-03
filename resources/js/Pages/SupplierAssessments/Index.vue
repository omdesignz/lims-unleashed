<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">QMS · Procurement</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Avaliação de fornecedores</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">
            Monitorize desempenho, risco, conformidade e plano de seguimento dos fornecedores críticos de reagentes, equipamentos e consumíveis.
          </p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Avaliações ativas</div>
            <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ summary?.total ?? 0 }}</div>
          </div>
          <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/40 dark:bg-amber-950/30">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Revisões próximas</div>
            <div class="mt-2 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ summary?.due_reviews ?? 0 }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
      <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ editingId ? 'Editar avaliação' : 'Nova avaliação' }}</h2>

        <div class="grid gap-4 md:grid-cols-2">
          <comboboxEnhanced v-model="selectedSupplier" title-label="Fornecedor" placeholder="Seleccione um fornecedor" :options="supplierOptions" />
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Seleccione um departamento" :options="departmentOptions" />
          <input v-model="form.assessment_date" type="date" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.next_review_at" type="date" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <select v-model="form.status" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <option value="approved">Aprovado</option>
            <option value="conditional">Condicional</option>
            <option value="suspended">Suspenso</option>
            <option value="rejected">Rejeitado</option>
          </select>
          <select v-model="form.risk_level" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <option value="low">Risco baixo</option>
            <option value="medium">Risco médio</option>
            <option value="high">Risco elevado</option>
            <option value="critical">Risco crítico</option>
          </select>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <input v-model="form.delivery_score" type="number" min="1" max="5" placeholder="Entrega (1-5)" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.quality_score" type="number" min="1" max="5" placeholder="Qualidade (1-5)" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.compliance_score" type="number" min="1" max="5" placeholder="Conformidade documental (1-5)" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.responsiveness_score" type="number" min="1" max="5" placeholder="Resposta / suporte (1-5)" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        </div>

        <input v-model="form.evidence_reference" type="text" placeholder="Referência de evidência / auditoria" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        <textarea v-model="form.strengths" rows="3" placeholder="Pontos fortes observados" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
        <textarea v-model="form.gaps" rows="3" placeholder="Lacunas ou riscos encontrados" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
        <textarea v-model="form.corrective_actions" rows="3" placeholder="Ações corretivas acordadas" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
        <textarea v-model="form.follow_up_actions" rows="3" placeholder="Plano de seguimento" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
        <textarea v-model="form.notes" rows="3" placeholder="Observações adicionais" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>

        <div class="grid gap-3 md:grid-cols-2">
          <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="form.approved_supplier" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-slate-700">
            Fornecedor aprovado
          </label>
          <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-slate-700">
            Avaliação ativa
          </label>
        </div>

        <div class="flex gap-3">
          <button type="submit" class="rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800">{{ editingId ? 'Atualizar' : 'Guardar' }}</button>
          <button v-if="editingId" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="resetForm">Cancelar</button>
        </div>
      </form>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div v-if="assessments.length" class="space-y-4">
          <article v-for="assessment in assessments" :key="assessment.id" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', statusTone(assessment.status)]">{{ statusLabel(assessment.status) }}</span>
                  <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', riskTone(assessment.risk_level)]">{{ riskLabel(assessment.risk_level) }}</span>
                  <span v-if="assessment.approved_supplier" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aprovado</span>
                </div>
                <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ assessment.supplier?.name }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ assessment.gaps || assessment.notes || 'Sem observações adicionais.' }}</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                  <div>Departamento: {{ assessment.department?.name || 'Transversal' }}</div>
                  <div>Avaliador: {{ assessment.assessed_by?.name || 'Sistema' }}</div>
                  <div>Data: {{ formatDate(assessment.assessment_date) }}</div>
                  <div>Próxima revisão: {{ formatDate(assessment.next_review_at) }}</div>
                </div>
              </div>
              <div class="flex flex-col items-start gap-2 lg:items-end">
                <div class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ assessment.total_score }}/100</div>
                <div class="text-xs text-slate-500">Entrega {{ assessment.delivery_score ?? '—' }} · Qualidade {{ assessment.quality_score ?? '—' }}</div>
                <div class="flex gap-2">
                  <button type="button" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="edit(assessment)">Editar</button>
                  <button type="button" class="rounded-2xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="destroyAssessment(assessment)">Arquivar</button>
                </div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
          Ainda não existem avaliações de fornecedores registadas.
        </div>
      </section>
    </section>
  </div>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  assessments: Array,
  suppliers: Array,
  departments: Array,
  users: Array,
  summary: Object,
})

const editingId = ref(null)
const mapOptions = (items = [], labelBuilder) => items.map(item => ({ value: item.id, label: labelBuilder(item) }))
const supplierOptions = computed(() => mapOptions(props.suppliers, item => item.name))
const departmentOptions = computed(() => mapOptions(props.departments, item => item.name))

const selectedSupplier = ref(null)
const selectedDepartment = ref(null)

const form = useForm({
  inventory_item_supplier_id: '',
  department_id: '',
  assessment_date: new Date().toISOString().slice(0, 10),
  next_review_at: '',
  status: 'approved',
  risk_level: 'medium',
  delivery_score: '',
  quality_score: '',
  compliance_score: '',
  responsiveness_score: '',
  evidence_reference: '',
  approved_supplier: true,
  is_active: true,
  strengths: '',
  gaps: '',
  corrective_actions: '',
  follow_up_actions: '',
  notes: '',
})

watch(selectedSupplier, (option) => {
  form.inventory_item_supplier_id = option?.value ?? ''
})

watch(selectedDepartment, (option) => {
  form.department_id = option?.value ?? ''
})

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.assessment_date = new Date().toISOString().slice(0, 10)
  form.status = 'approved'
  form.risk_level = 'medium'
  form.approved_supplier = true
  form.is_active = true
  selectedSupplier.value = null
  selectedDepartment.value = null
}

const edit = (assessment) => {
  editingId.value = assessment.id
  Object.assign(form, {
    inventory_item_supplier_id: assessment.inventory_item_supplier_id || '',
    department_id: assessment.department_id || '',
    assessment_date: assessment.assessment_date || '',
    next_review_at: assessment.next_review_at || '',
    status: assessment.status || 'approved',
    risk_level: assessment.risk_level || 'medium',
    delivery_score: assessment.delivery_score || '',
    quality_score: assessment.quality_score || '',
    compliance_score: assessment.compliance_score || '',
    responsiveness_score: assessment.responsiveness_score || '',
    evidence_reference: assessment.evidence_reference || '',
    approved_supplier: Boolean(assessment.approved_supplier),
    is_active: Boolean(assessment.is_active),
    strengths: assessment.strengths || '',
    gaps: assessment.gaps || '',
    corrective_actions: assessment.corrective_actions || '',
    follow_up_actions: assessment.follow_up_actions || '',
    notes: assessment.notes || '',
  })
  selectedSupplier.value = supplierOptions.value.find(option => option.value === assessment.inventory_item_supplier_id) ?? null
  selectedDepartment.value = departmentOptions.value.find(option => option.value === assessment.department_id) ?? null
}

const submit = () => {
  if (editingId.value) {
    form.put(route('supplier-assessments.update', editingId.value), { preserveScroll: true, onSuccess: () => resetForm() })
    return
  }

  form.post(route('supplier-assessments.store'), { preserveScroll: true, onSuccess: () => resetForm() })
}

const destroyAssessment = (assessment) => {
  router.delete(route('supplier-assessments.destroy', assessment.id), { preserveScroll: true })
}

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'
const statusLabel = (value) => ({
  approved: 'Aprovado',
  conditional: 'Condicional',
  suspended: 'Suspenso',
  rejected: 'Rejeitado',
}[value] || value)
const statusTone = (value) => ({
  approved: 'bg-emerald-50 text-emerald-700',
  conditional: 'bg-amber-50 text-amber-700',
  suspended: 'bg-orange-50 text-orange-700',
  rejected: 'bg-rose-50 text-rose-700',
}[value] || 'bg-slate-100 text-slate-700')
const riskLabel = (value) => ({
  low: 'Risco baixo',
  medium: 'Risco médio',
  high: 'Risco elevado',
  critical: 'Risco crítico',
}[value] || value)
const riskTone = (value) => ({
  low: 'bg-emerald-50 text-emerald-700',
  medium: 'bg-sky-50 text-sky-700',
  high: 'bg-amber-50 text-amber-700',
  critical: 'bg-rose-50 text-rose-700',
}[value] || 'bg-slate-100 text-slate-700')
</script>
