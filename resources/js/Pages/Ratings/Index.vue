<template>
  <div class="pl-page space-y-8" :class="commercialDocumentThemeClasses">
    <PageHeader title="Avaliações de processos e serviços" lede="Acompanhe a satisfação interna e de clientes para identificar oportunidades de melhoria, risco operacional e evidências de feedback.">
      <template #actions>
        <Link
          :href="route('rating.create', { rateableType: 'service' })"
          class="ds-button ds-button-primary"
        >
          Avaliar serviço geral
        </Link>
      </template>
    </PageHeader>

    <section v-if="canInvite" class="ds-card p-5">
      <h2 class="text-base font-bold text-[var(--ds-text)]">Convidar cliente a avaliar o serviço</h2>
      <p class="ds-copy mt-1 text-sm">Use o email de uma conta do portal verificada. Convite privado deste laboratório, válido por 30 dias.</p>
      <form class="mt-4 space-y-3" :aria-busy="invitationForm.processing" @submit.prevent="issueInvitation">
        <label for="rating-recipient" class="block text-sm font-semibold text-[var(--ds-text)]">Email do destinatário</label>
        <BaseInput id="rating-recipient" v-model="invitationForm.recipient_email" type="email" required maxlength="255" class="ds-input w-full" :disabled="invitationForm.processing" :aria-invalid="Boolean(invitationForm.errors.recipient_email)" :aria-describedby="invitationForm.hasErrors ? 'invitation-errors' : undefined" />
        <div v-if="invitationForm.hasErrors" id="invitation-errors" role="alert" class="text-sm text-red-600">
          <p v-for="(message, field) in invitationForm.errors" :key="field">{{ message }}</p>
        </div>
        <button type="submit" class="ds-button ds-button-primary" :disabled="invitationForm.processing">{{ invitationForm.processing ? 'A registar…' : 'Registar convite' }}</button>
      </form>
    </section>

    <section v-if="invitations.length" class="ds-card p-5">
      <h2 class="text-base font-bold text-[var(--ds-text)]">Últimos 20 convites deste laboratório</h2>
      <p v-if="revokeForm.hasErrors" role="alert" class="mt-2 text-sm text-red-600">{{ Object.values(revokeForm.errors).join(' ') }}</p>
      <div class="mt-3 divide-y divide-[var(--ds-border)]">
        <article v-for="invitation in invitations" :key="invitation.invitation" class="flex flex-wrap items-center justify-between gap-3 py-3">
          <div><p class="text-sm font-semibold text-[var(--ds-text)]">{{ invitation.recipient }}</p><p class="ds-copy text-xs">{{ invitation.rateable_type }} · {{ invitationStatus(invitation.status) }}</p></div>
          <button v-if="canInvite && invitation.status === 'pending'" type="button" class="ds-button ds-button-secondary" :disabled="revokeForm.processing" @click="revokeInvitation(invitation)">{{ revokeForm.processing ? 'A actualizar…' : 'Revogar convite' }}</button>
        </article>
      </div>
    </section>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3" aria-label="Indicadores de feedback">
      <article class="pl-panel min-w-0 xl:col-span-3">
        <header class="pl-panel-head"><h2 class="pl-k">Avaliações por mês</h2><span class="pl-k pl-faint">Portal e interno</span></header>
        <div class="p-4">
          <PlanoChart kind="column" label="Avaliações registadas por mês" :categories="charts.monthly?.categories || []" :series="charts.monthly?.series || []" :height="240" />
        </div>
      </article>
      <article class="pl-panel min-w-0">
        <header class="pl-panel-head"><h2 class="pl-k">Processos avaliados</h2></header>
        <div class="p-4">
          <PlanoChart kind="bar" label="Avaliações por processo" :categories="charts.by_type?.labels || []" :series="[{ name: 'Avaliações', data: charts.by_type?.series || [] }]" :height="220" />
        </div>
      </article>
      <article class="pl-panel min-w-0 xl:col-span-2">
        <header class="pl-panel-head"><h2 class="pl-k">Distribuição de pontuações</h2><span class="pl-k pl-faint">1 a 5</span></header>
        <div class="p-4">
          <PlanoChart kind="column" label="Avaliações por pontuação" :categories="charts.score_distribution?.labels || []" :series="[{ name: 'Avaliações', data: charts.score_distribution?.series || [] }]" :height="220" />
        </div>
      </article>
    </section>

    <section class="pl-panel" aria-labelledby="ratings-recent">
      <header class="pl-panel-head"><h2 id="ratings-recent" class="pl-k">Registos recentes</h2></header>
      <div class="overflow-x-auto">
        <DataTable>
          <thead>
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Canal</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pontuação média</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Comentário</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="rating in ratings.data" :key="rating.id" class="text-sm">
              <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ rating.rateable_type }} #{{ rating.rateable_id }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ rating.channel || 'internal' }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ averageRating(rating.criteria) }}</td>
              <td class="max-w-md px-6 py-4 text-slate-600 dark:text-slate-300">{{ rating.review || 'Sem comentário' }}</td>
              <td class="px-6 py-4 text-slate-500">{{ formatDate(rating.created_at) }}</td>
            </tr>
          </tbody>
        </DataTable>
      </div>
      <div v-if="ratings.links" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <Pagination :links="ratings.links" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, useForm } from '@inertiajs/vue3'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import Pagination from '@/Components/pagination.vue'

const props = defineProps({
  ratings: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
  canInvite: { type: Boolean, default: false },
  invitations: { type: Array, default: () => [] },
})

const invitationForm = useForm({ recipient_email: '', rateable_type: 'service', rateable_id: 0 })
const revokeForm = useForm({})

function issueInvitation() {
  if (invitationForm.processing) return
  invitationForm.post(route('ratings.invitations.store'), { preserveState: 'errors', onSuccess: () => invitationForm.reset() })
}

function revokeInvitation(invitation) {
  if (revokeForm.processing || !window.confirm('Revogar este convite? O destinatário deixará de poder responder.')) return
  revokeForm.post(route('ratings.invitations.revoke', { invitation: invitation.invitation }), { preserveState: 'errors' })
}

function invitationStatus(status) {
  return { pending: 'Pendente', completed: 'Respondido', expired: 'Expirado', revoked: 'Revogado' }[status] || status
}

const metrics = computed(() => [
  { label: 'Total', value: props.stats.total },
  { label: 'Portal', value: props.stats.portal },
  { label: 'Interno', value: props.stats.internal },
  { label: 'Média', value: props.stats.average },
])

function averageRating(criteria) {
  const values = Object.values(criteria || {}).map((value) => Number(value)).filter(Boolean)
  if (!values.length) return '0.00'
  return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(2)
}

function formatDate(value) {
  if (!value) return '--'
  return new Date(value).toLocaleDateString('pt-PT')
}
</script>
