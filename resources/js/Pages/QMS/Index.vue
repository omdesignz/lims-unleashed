<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-cyan-950 p-6 text-white shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-300">QMS</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight">Quality Management System</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-200">
            Consolida competência técnica, revisões, não conformidades, reclamações, responsabilidades e fontes de incerteza num único painel operacional.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <Link :href="route('users.index')" class="rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15">Competência do pessoal</Link>
          <Link :href="route('supplier-assessments.index')" class="rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15">Avaliação de fornecedores</Link>
          <Link :href="route('responsibility-matrix.index')" class="rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15">Matriz de responsabilidades</Link>
          <Link :href="route('uncertainty-sources.index')" class="rounded-2xl bg-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-cyan-400">Fontes de incerteza</Link>
        </div>
      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article v-for="card in cards" :key="card.label" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">{{ card.label }}</div>
        <div class="mt-3 text-3xl font-semibold text-slate-900">{{ card.value }}</div>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Competências em risco</h2>
          <span class="text-sm text-slate-500">{{ expiringQualifications.length }} registos</span>
        </div>
        <div v-if="expiringQualifications.length" class="mt-6 space-y-4">
          <article v-for="qualification in expiringQualifications" :key="qualification.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-900">{{ qualification.user?.name }}</div>
                <div class="text-xs text-slate-500">{{ qualification.capability }} · {{ qualification.department?.name || 'Sem departamento' }}</div>
              </div>
              <div class="text-right">
                <div :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', statusTone(qualification.monitoring_status)]">
                  {{ statusLabel(qualification.monitoring_status) }}
                </div>
                <div class="mt-2 text-sm text-amber-700">Válida até {{ formatDate(qualification.authorized_until) }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ expiryLabel(qualification.days_until_expiry) }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhuma competência com renovação próxima.
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Plano de follow-up</h2>
          <span class="text-sm text-slate-500">{{ qualificationFollowUps.length }} registos</span>
        </div>
        <div v-if="qualificationFollowUps.length" class="mt-6 space-y-4">
          <article v-for="qualification in qualificationFollowUps" :key="qualification.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900">{{ qualification.user?.name }}</div>
                  <div class="text-xs text-slate-500">{{ qualification.capability }}</div>
                </div>
                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', followUpTone(qualification.follow_up_state)]">
                  {{ followUpLabel(qualification.follow_up_state) }}
                </span>
              </div>
              <div class="text-sm text-slate-600">
                Próxima ação até <span class="font-semibold text-slate-900">{{ formatDate(qualification.follow_up_due_at) }}</span>
              </div>
              <div :class="['inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium', readinessTone(qualification.renewal_readiness)]">
                {{ readinessLabel(qualification.renewal_readiness) }}
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhum follow-up de competência com ação imediata.
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Prontidão para renovação</h2>
          <span class="text-sm text-slate-500">{{ renewalReadyQualifications.length }} registos</span>
        </div>
        <div v-if="renewalReadyQualifications.length" class="mt-6 space-y-4">
          <article v-for="qualification in renewalReadyQualifications" :key="qualification.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-2">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900">{{ qualification.user?.name }}</div>
                  <div class="text-xs text-slate-500">{{ qualification.capability }}</div>
                </div>
                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', readinessTone(qualification.renewal_readiness)]">
                  {{ readinessLabel(qualification.renewal_readiness) }}
                </span>
              </div>
              <div class="text-sm text-slate-600">
                Evidência: <span class="font-medium text-slate-900">{{ qualification.training_reference || 'Não registada' }}</span>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhuma renovação requer ação adicional neste momento.
        </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Avaliações de fornecedores em revisão</h2>
          <span class="text-sm text-slate-500">{{ dueSupplierAssessments.length }} registos</span>
        </div>
        <div v-if="dueSupplierAssessments.length" class="mt-6 space-y-4">
          <article v-for="assessment in dueSupplierAssessments" :key="assessment.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-900">{{ assessment.supplier?.name }}</div>
                <div class="text-xs text-slate-500">{{ assessment.department?.name || 'Cobertura transversal' }} · Risco {{ assessment.risk_level }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm text-cyan-700">Rever até {{ formatDate(assessment.next_review_at) }}</div>
                <div class="mt-1 text-xs text-slate-500">Score {{ assessment.total_score }}/100 · {{ assessment.status }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhuma avaliação de fornecedor requer revisão imediata.
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Recepções com não conformidade</h2>
          <span class="text-sm text-slate-500">{{ receivingNonConformities.length }} registos</span>
        </div>
        <div v-if="receivingNonConformities.length" class="mt-6 space-y-4">
          <article v-for="record in receivingNonConformities" :key="record.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <div class="text-sm font-semibold text-slate-900">{{ record.title }}</div>
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', receivingSeverityTone(record.severity)]">
                    {{ receivingSeverityLabel(record.severity) }}
                  </span>
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ record.nc_number }} · {{ record.department?.name || 'Sem departamento' }} · lote/referência {{ record.batch_number || 'N/D' }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-slate-900">{{ formatDate(record.reported_at) }}</div>
                <div class="mt-1 text-xs text-slate-500">Estado {{ record.status }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhuma recepção com não conformidade aberta.
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Documentos com revisão próxima</h2>
          <span class="text-sm text-slate-500">{{ dueDocumentReviews.length }} registos</span>
        </div>
        <div v-if="dueDocumentReviews.length" class="mt-6 space-y-4">
          <article v-for="document in dueDocumentReviews" :key="document.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-900">{{ document.name }}</div>
                <div class="text-xs text-slate-500">Responsável: {{ document.owner?.name || 'Sem responsável' }}</div>
              </div>
              <div class="text-sm text-cyan-700">Rever até {{ formatDate(document.review_due_at) }}</div>
            </div>
          </article>
        </div>
        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
          Nenhum documento crítico com revisão próxima.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  summary: Object,
  expiringQualifications: Array,
  qualificationFollowUps: Array,
  renewalReadyQualifications: Array,
  dueSupplierAssessments: Array,
  receivingNonConformities: Array,
  dueDocumentReviews: Array,
})

const cards = computed(() => [
  { label: 'Reclamações em aberto', value: props.summary.open_complaints },
  { label: 'Não conformidades em aberto', value: props.summary.open_non_conformities },
  { label: 'Revisões de gestão planeadas', value: props.summary.scheduled_management_reviews },
  { label: 'Registos ambientais de hoje', value: props.summary.environmental_entries_today },
  { label: 'Competências em vencimento', value: props.summary.expiring_qualifications },
  { label: 'Competências expiradas', value: props.summary.expired_qualifications },
  { label: 'Prontas para renovação', value: props.summary.renewal_ready_qualifications },
  { label: 'Follow-ups em aberto', value: props.summary.qualification_followups_due },
  { label: 'Sem evidência', value: props.summary.qualifications_missing_evidence },
  { label: 'Responsabilidades ativas', value: props.summary.responsibility_assignments },
  { label: 'Fontes de incerteza ativas', value: props.summary.uncertainty_sources },
  { label: 'Avaliações de fornecedores em revisão', value: props.summary.supplier_assessments_due },
  { label: 'Fornecedores de alto risco', value: props.summary.suppliers_high_risk },
  { label: 'NCs de recepção abertas', value: props.summary.receiving_non_conformities_open },
  { label: 'Documentos com revisão próxima', value: props.summary.documents_due_review },
])

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'
const statusLabel = (status) => ({
  expired: 'Expirada',
  expiring_critical: 'Urgente',
  expiring_soon: 'A vencer',
  active: 'Ativa',
  scheduled: 'Programada',
  inactive: 'Inativa',
}[status] || 'Acompanhar')
const statusTone = (status) => ({
  expired: 'bg-rose-100 text-rose-800',
  expiring_critical: 'bg-orange-100 text-orange-800',
  expiring_soon: 'bg-amber-100 text-amber-800',
  active: 'bg-emerald-100 text-emerald-800',
  scheduled: 'bg-sky-100 text-sky-800',
  inactive: 'bg-slate-100 text-slate-700',
}[status] || 'bg-slate-100 text-slate-700')
const readinessLabel = (status) => ({
  ready_for_review: 'Pronta para renovação',
  missing_evidence: 'Falta evidência',
  training_pending: 'Formação pendente',
  on_track: 'Em conformidade',
}[status] || 'Acompanhar')
const readinessTone = (status) => ({
  ready_for_review: 'bg-blue-100 text-blue-800',
  missing_evidence: 'bg-rose-100 text-rose-800',
  training_pending: 'bg-amber-100 text-amber-800',
  on_track: 'bg-emerald-100 text-emerald-800',
}[status] || 'bg-slate-100 text-slate-700')
const followUpLabel = (status) => ({
  overdue: 'Em atraso',
  due_soon: 'Próximo',
  scheduled: 'Planeado',
  unscheduled: 'Sem plano',
}[status] || 'Acompanhar')
const followUpTone = (status) => ({
  overdue: 'bg-rose-100 text-rose-800',
  due_soon: 'bg-amber-100 text-amber-800',
  scheduled: 'bg-sky-100 text-sky-800',
  unscheduled: 'bg-slate-100 text-slate-700',
}[status] || 'bg-slate-100 text-slate-700')
const expiryLabel = (days) => {
  if (days === null || days === undefined) {
    return 'Validade em aberto';
  }
  if (days < 0) {
    return `Atraso de ${Math.abs(days)} dias`;
  }
  if (days === 0) {
    return 'Expira hoje';
  }
  return `${days} dias restantes`;
}
const receivingSeverityLabel = (value) => ({
  low: 'Baixa',
  medium: 'Média',
  high: 'Alta',
  critical: 'Crítica',
}[value] || 'Acompanhar')
const receivingSeverityTone = (value) => ({
  low: 'bg-emerald-100 text-emerald-800',
  medium: 'bg-amber-100 text-amber-800',
  high: 'bg-orange-100 text-orange-800',
  critical: 'bg-rose-100 text-rose-800',
}[value] || 'bg-slate-100 text-slate-700')
</script>
