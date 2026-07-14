<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="ds-kicker">SGQ</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-[var(--ds-text)] sm:text-3xl">Sistema de gestão da qualidade</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-[var(--ds-text-muted)]">
              Consolida competência técnica, revisões, não conformidades, reclamações, responsabilidades e fontes de incerteza num único painel operacional.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Link :href="route('users.index')" class="ds-button ds-button-secondary">Competência</Link>
            <Link :href="route('supplier-assessments.index')" class="ds-button ds-button-secondary">Fornecedores</Link>
            <Link :href="route('responsibility-matrix.index')" class="ds-button ds-button-secondary">Responsabilidades</Link>
            <Link :href="route('uncertainty-sources.index')" class="ds-button ds-button-primary">Incerteza</Link>
          </div>
        </div>
      </div>

      <dl class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-5">
        <div v-for="card in priorityCards" :key="card.label" class="px-5 py-4 sm:px-6">
          <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--ds-text-soft)]">{{ card.label }}</dt>
          <dd class="mt-2 text-2xl font-semibold text-[var(--ds-text)]">{{ card.value }}</dd>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <h2 class="text-base font-semibold text-[var(--ds-text)]">Indicadores do SGQ</h2>
        <p class="mt-1 text-sm text-[var(--ds-text-muted)]">Resumo compacto para revisão técnica e gestão ISO 17025.</p>
      </div>
      <dl class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-5">
        <div v-for="card in supportingCards" :key="card.label" class="px-5 py-4 sm:px-6">
          <dt class="text-xs font-semibold uppercase tracking-wide text-[var(--ds-text-soft)]">{{ card.label }}</dt>
          <dd class="mt-2 text-xl font-semibold text-[var(--ds-text)]">{{ card.value }}</dd>
        </div>
      </dl>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
      <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Competências em risco</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ expiringQualifications.length }} registos</span>
        </div>
        <div v-if="expiringQualifications.length" class="mt-5 space-y-3">
          <article v-for="qualification in expiringQualifications" :key="qualification.id" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ qualification.user?.name }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ qualification.capability }} · {{ qualification.department?.name || 'Sem departamento' }}</div>
              </div>
              <div class="md:text-right">
                <div :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', statusTone(qualification.monitoring_status)]">
                  {{ statusLabel(qualification.monitoring_status) }}
                </div>
                <div class="mt-2 text-sm font-semibold text-[var(--ds-text)]">Válida até {{ formatDate(qualification.authorized_until) }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-soft)]">{{ expiryLabel(qualification.days_until_expiry) }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhuma competência com renovação próxima.
        </div>
      </div>

      <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Plano de follow-up</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ qualificationFollowUps.length }} registos</span>
        </div>
        <div v-if="qualificationFollowUps.length" class="mt-5 space-y-3">
          <article v-for="qualification in qualificationFollowUps" :key="qualification.id" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ qualification.user?.name }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ qualification.capability }}</div>
              </div>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', followUpTone(qualification.follow_up_state)]">
                {{ followUpLabel(qualification.follow_up_state) }}
              </span>
            </div>
            <div class="mt-3 text-sm text-[var(--ds-text-muted)]">
              Próxima acção até <span class="font-semibold text-[var(--ds-text)]">{{ formatDate(qualification.follow_up_due_at) }}</span>
            </div>
            <div :class="['mt-3 inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-medium', readinessTone(qualification.renewal_readiness)]">
              {{ readinessLabel(qualification.renewal_readiness) }}
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhum follow-up de competência com acção imediata.
        </div>
      </div>

      <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Prontidão para renovação</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ renewalReadyQualifications.length }} registos</span>
        </div>
        <div v-if="renewalReadyQualifications.length" class="mt-5 space-y-3">
          <article v-for="qualification in renewalReadyQualifications" :key="qualification.id" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ qualification.user?.name }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ qualification.capability }}</div>
              </div>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', readinessTone(qualification.renewal_readiness)]">
                {{ readinessLabel(qualification.renewal_readiness) }}
              </span>
            </div>
            <div class="mt-3 text-sm text-[var(--ds-text-muted)]">
              Evidência: <span class="font-semibold text-[var(--ds-text)]">{{ qualification.training_reference || 'Não registada' }}</span>
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhuma renovação requer acção adicional neste momento.
        </div>
      </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
      <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Avaliações de fornecedores em revisão</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ dueSupplierAssessments.length }} registos</span>
        </div>
        <div v-if="dueSupplierAssessments.length" class="mt-5 space-y-3">
          <article v-for="assessment in dueSupplierAssessments" :key="assessment.id" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ assessment.supplier?.name }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">{{ assessment.department?.name || 'Cobertura transversal' }} · Risco {{ assessment.risk_level }}</div>
              </div>
              <div class="md:text-right">
                <div class="text-sm font-semibold text-[var(--ds-text)]">Rever até {{ formatDate(assessment.next_review_at) }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-soft)]">Score {{ assessment.total_score }}/100 · {{ assessment.status }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhuma avaliação de fornecedor requer revisão imediata.
        </div>
      </div>

      <div class="ds-card p-5">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Recepções com não conformidade</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ receivingNonConformities.length }} registos</span>
        </div>
        <div v-if="receivingNonConformities.length" class="mt-5 space-y-3">
          <article v-for="record in receivingNonConformities" :key="record.id" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <div class="text-sm font-semibold text-[var(--ds-text)]">{{ record.title }}</div>
                  <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', receivingSeverityTone(record.severity)]">
                    {{ receivingSeverityLabel(record.severity) }}
                  </span>
                </div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">
                  {{ record.nc_number }} · {{ record.department?.name || 'Sem departamento' }} · lote/referência {{ record.batch_number || 'N/D' }}
                </div>
              </div>
              <div class="md:text-right">
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ formatDate(record.reported_at) }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-soft)]">Estado {{ record.status }}</div>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhuma recepção com não conformidade aberta.
        </div>
      </div>

      <div class="ds-card p-5 xl:col-span-2">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Documentos com revisão próxima</h2>
          <span class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">{{ dueDocumentReviews.length }} registos</span>
        </div>
        <div v-if="dueDocumentReviews.length" class="mt-5 divide-y divide-[var(--ds-border)] overflow-hidden rounded-lg border border-[var(--ds-border)]">
          <article v-for="document in dueDocumentReviews" :key="document.id" class="bg-[var(--ds-panel-subtle)] px-4 py-3">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="text-sm font-semibold text-[var(--ds-text)]">{{ document.name }}</div>
                <div class="mt-1 text-xs text-[var(--ds-text-muted)]">Responsável: {{ document.owner?.name || 'Sem responsável' }}</div>
              </div>
              <div class="text-sm font-semibold text-[var(--ds-text)]">Rever até {{ formatDate(document.review_due_at) }}</div>
            </div>
          </article>
        </div>
        <div v-else class="mt-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-8 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhum documento crítico com revisão próxima.
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
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
  { label: 'Responsabilidades activas', value: props.summary.responsibility_assignments },
  { label: 'Fontes de incerteza activas', value: props.summary.uncertainty_sources },
  { label: 'Avaliações de fornecedores em revisão', value: props.summary.supplier_assessments_due },
  { label: 'Fornecedores de alto risco', value: props.summary.suppliers_high_risk },
  { label: 'NCs de recepção abertas', value: props.summary.receiving_non_conformities_open },
  { label: 'Documentos com revisão próxima', value: props.summary.documents_due_review },
])

const priorityCards = computed(() => cards.value.slice(0, 5))
const supportingCards = computed(() => cards.value.slice(5))

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'
const statusLabel = (status) => ({
  expired: 'Expirada',
  expiring_critical: 'Urgente',
  expiring_soon: 'A vencer',
  active: 'Activa',
  scheduled: 'Programada',
  inactive: 'Inactiva',
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
