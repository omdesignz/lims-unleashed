<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Propostas', url: route('vap-proposals.index') }, { title: proposal.proposal_number }]"
      :title="proposal.proposal_number"
      :lede="[proposal.customer?.name, proposal.warehouse?.address || proposal.warehouse?.name, formatCurrency(proposal.total)].filter(Boolean).join(' · ')"
    >
      <template #badges>
        <StatusChip :tone="statusTone">{{ proposal.status_badge?.text || proposal.status }}</StatusChip>
        <StatusChip v-if="!proposal.is_original" tone="wait">{{ $t('gestlab.general.labels.vap_proposals.show.revision') }}</StatusChip>
      </template>
      <template #actions>
        <a v-if="proposal.has_document" :href="route('vap-proposals.download.pdf', proposal.id)" class="ds-button ds-button-secondary">
          <ArrowDownTrayIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.show.download_pdf') }}
        </a>
        <a v-else :href="route('vap-proposals.download.pdf', proposal.id)" target="_blank" rel="noopener" class="ds-button ds-button-secondary">
          {{ $t('gestlab.general.labels.vap_proposals.show.actions.generate_pdf') }}<span class="sr-only"> (abre noutra janela)</span>
        </a>
        <Link v-if="canRevise" :href="route('vap-proposals.edit', proposal.id)" class="ds-button ds-button-quiet">
          {{ $t('gestlab.general.labels.vap_proposals.show.revise') }}
        </Link>
      </template>
    </PageHeader>

    <Journey v-if="journey.length" class="mb-10" :steps="journey" />

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <section v-if="laboratoryDossier" class="pl-panel" aria-labelledby="execution-title">
          <div class="pl-panel-head">
            <h2 id="execution-title" class="pl-k">Execução laboratorial</h2>
            <StatusChip tone="run">{{ laboratoryDossier.stage.label }}</StatusChip>
          </div>
          <p class="border-b border-[var(--pl-line)] p-4 text-sm text-[var(--pl-muted)]">{{ laboratoryDossier.stage.description }}</p>
          <DataTable v-if="laboratoryDossier.samples.length">
            <thead><tr><th scope="col">Amostra</th><th scope="col">Código laboratorial</th><th scope="col">Produto</th><th scope="col"><span class="sr-only">Abrir</span></th></tr></thead>
            <tbody>
              <tr v-for="sample in laboratoryDossier.samples" :key="sample.id">
                <td><span class="pl-num font-medium">{{ sample.code || 'Por gerar' }}</span><span class="block text-[12.5px] text-[var(--pl-muted)]">{{ sample.name }}</span></td>
                <td class="pl-num">{{ sample.lab_code || 'Pendente' }}</td>
                <td>{{ sample.product || 'Âmbito por definir' }}</td>
                <td class="text-right"><Link :href="sample.show_url" class="pl-k pl-acc">Abrir →</Link></td>
              </tr>
            </tbody>
          </DataTable>
          <p v-else class="p-4 text-sm text-[var(--pl-muted)]">Ainda não há amostras recebidas para esta proposta.</p>
          <dl class="pl-facts pl-facts-2 border-t border-[var(--pl-line)]">
            <div class="pl-fact"><dt>Amostras recebidas</dt><dd class="pl-num">{{ laboratoryDossier.counts.accessioned_samples }} / {{ laboratoryDossier.counts.samples }}</dd></div>
            <div class="pl-fact"><dt>Análises</dt><dd class="pl-num">{{ laboratoryDossier.counts.analyses }}</dd></div>
            <div class="pl-fact"><dt>Resultados aprovados</dt><dd class="pl-num">{{ laboratoryDossier.counts.approved_results }} / {{ laboratoryDossier.counts.results }}</dd></div>
            <div class="pl-fact"><dt>Boletins validados</dt><dd class="pl-num">{{ laboratoryDossier.counts.validated_reports }} / {{ laboratoryDossier.counts.reports }}</dd></div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="items-title">
          <div class="pl-panel-head">
            <h2 id="items-title" class="pl-k">{{ $t('gestlab.general.labels.vap_proposals.show.items.title') }}</h2>
            <span class="pl-k pl-faint">{{ proposalItems.length }} {{ $t('gestlab.general.labels.vap_proposals.items') }}</span>
          </div>
          <DataTable v-if="proposalItems.length">
            <thead>
              <tr>
                <th scope="col">Serviço</th>
                <th scope="col" class="text-right">Qtd.</th>
                <th scope="col" class="text-right">{{ $t('gestlab.general.labels.vap_proposals.show.items.unit_price') }}</th>
                <th scope="col" class="text-right">{{ $t('gestlab.general.labels.vap_proposals.show.items.discount') }}</th>
                <th scope="col" class="text-right">{{ $t('gestlab.general.labels.vap_proposals.show.items.tax') }}</th>
                <th scope="col" class="text-right">{{ $t('gestlab.general.labels.vap_proposals.show.items.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in proposalItems" :key="item.id || index">
                <td class="!whitespace-normal">
                  <span class="font-medium">{{ item.item_description }}</span>
                  <span class="block text-[12.5px] text-[var(--pl-muted)]">
                    {{ [item.itemable_type ? getItemableTypeLabel(item.itemable_type) : null, item.standard ? (item.standard.code || item.standard.name || item.standard.description) : null, item.exemption_code].filter(Boolean).join(' · ') || '—' }}
                  </span>
                  <span v-if="item.obs" class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.obs }}</span>
                </td>
                <td class="pl-num text-right">{{ item.qty }} {{ item.unit?.code || item.unit?.symbol || '' }}</td>
                <td class="pl-num text-right">{{ formatCurrency(item.unit_price) }}</td>
                <td class="pl-num text-right">{{ Number(item.discount_amount) > 0 ? `−${formatCurrency(item.discount_amount)}` : '—' }}</td>
                <td class="pl-num text-right">{{ Number(item.tax_amount) > 0 ? `${formatCurrency(item.tax_amount)} (${item.tax_percentage}%)` : (item.charge_tax === false ? 'Isento' : '—') }}</td>
                <td class="pl-num text-right font-medium">{{ formatCurrency(item.total) }}</td>
              </tr>
            </tbody>
          </DataTable>
          <p v-else class="p-4 text-sm text-[var(--pl-muted)]">Esta proposta não tem serviços.</p>
          <dl class="pl-facts border-t border-[var(--pl-line)]">
            <div v-for="[label, value] in totals" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd class="pl-num text-right">{{ value }}</dd></div>
            <div class="pl-fact"><dt>{{ $t('gestlab.general.labels.vap_proposals.show.items.grand_total') }}</dt><dd class="pl-num text-right text-lg font-semibold">{{ formatCurrency(proposal.total) }}</dd></div>
          </dl>
        </section>

        <section v-if="proposal.compliance_agreement" class="pl-panel" aria-labelledby="compliance-title">
          <div class="pl-panel-head">
            <h2 id="compliance-title" class="pl-k">{{ $t('gestlab.general.labels.vap_proposals.show.compliance.title') }} · ISO 17025</h2>
            <StatusChip :tone="proposal.compliance_agreement.acknowledged_at ? 'ok' : 'wait'">
              {{ proposal.compliance_agreement.acknowledged_at ? $t('gestlab.general.labels.vap_proposals.show.compliance.signed') : 'Por aceitar' }}
            </StatusChip>
          </div>
          <dl class="pl-facts pl-facts-2">
            <div v-for="[label, accepted] in complianceChecks" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ accepted ? 'Aceite' : 'Não aceite' }}</dd></div>
            <div v-if="proposal.compliance_agreement.acknowledged_at" class="pl-fact"><dt>{{ $t('gestlab.general.labels.vap_proposals.show.compliance.signed_on') }}</dt><dd class="pl-num">{{ formatDateTime(proposal.compliance_agreement.acknowledged_at) }} · IP {{ proposal.compliance_agreement.client_ip || '—' }}</dd></div>
          </dl>
        </section>

        <section v-if="revisions.length > 0" class="pl-panel" aria-labelledby="history-title">
          <div class="pl-panel-head">
            <h2 id="history-title" class="pl-k">{{ $t('gestlab.general.labels.vap_proposals.show.revisions.title') }}</h2>
            <span class="pl-k pl-faint">{{ revisions.length }} {{ $t('gestlab.general.labels.vap_proposals.changes') }}</span>
          </div>
          <article v-for="revision in revisions" :key="revision.id" class="grid gap-2 border-b border-[var(--pl-line)] p-4 last:border-b-0">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <span class="flex items-center gap-2 font-medium"><UserIcon class="h-4 w-4" aria-hidden="true" />{{ revision.causer?.name || $t('gestlab.general.labels.vap_proposals.show.system_user') }}</span>
              <span class="pl-k pl-faint">{{ formatDateTime(revision.created_at) }}</span>
            </div>
            <p class="text-sm text-[var(--pl-muted)]">{{ revision.description }}</p>
            <p v-if="revision.properties?.reason" class="text-sm"><strong>{{ $t('gestlab.general.labels.vap_proposals.show.revisions.reason') }}:</strong> {{ revision.properties.reason }}</p>
            <div v-if="revision.event === 'revised' && revision.properties?.old_values && revision.properties?.new_values" class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
              <span v-if="revision.properties.old_values.total != null && revision.properties.new_values.total != null && revision.properties.old_values.total !== revision.properties.new_values.total" class="pl-num flex items-center gap-2">
                Total <s class="text-[var(--pl-muted)]">{{ formatCurrency(revision.properties.old_values.total) }}</s><ArrowRightIcon class="h-3 w-3" aria-hidden="true" />{{ formatCurrency(revision.properties.new_values.total) }}
              </span>
              <span v-if="revision.properties.old_values.items_count != null && revision.properties.new_values.items_count != null && revision.properties.old_values.items_count !== revision.properties.new_values.items_count" class="pl-num flex items-center gap-2">
                {{ $t('gestlab.general.labels.vap_proposals.items') }} <s class="text-[var(--pl-muted)]">{{ revision.properties.old_values.items_count }}</s><ArrowRightIcon class="h-3 w-3" aria-hidden="true" />{{ revision.properties.new_values.items_count }}
              </span>
            </div>
          </article>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">Dossier</h2><span class="pl-k pl-faint">{{ proposal.department?.name || '—' }}</span></div>
          <dl class="pl-facts">
            <div v-for="[label, value] in facts" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd>{{ value || '—' }}</dd></div>
          </dl>
          <div v-if="proposal.obs" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">{{ $t('gestlab.general.labels.vap_proposals.show.details.observations') }}</h3>
            <p class="whitespace-pre-wrap text-sm">{{ proposal.obs }}</p>
          </div>
        </section>

        <section v-if="publicLink" class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">{{ $t('gestlab.general.labels.vap_proposals.show.actions.share') }}</h2></div>
          <div class="grid gap-3 p-4">
            <p class="text-sm text-[var(--pl-muted)]">O cliente consulta, aceita ou rejeita a proposta nesta ligação.</p>
            <div class="flex gap-2">
              <BaseInput :value="publicLink" readonly aria-label="Ligação do cliente" class="ds-field min-w-0 flex-1" />
              <button type="button" class="ds-button ds-button-secondary shrink-0" @click="copyToClipboard">
                {{ copied ? $t('gestlab.general.labels.vap_proposals.show.copied') : $t('gestlab.general.labels.vap_proposals.show.copy') }}
              </button>
            </div>
            <a :href="publicLink" target="_blank" rel="noopener" class="pl-k pl-acc">{{ $t('gestlab.general.labels.vap_proposals.show.actions.view_public_link') }} →<span class="sr-only"> (abre noutra janela)</span></a>
          </div>
        </section>

        <section v-if="proposal.template" class="pl-panel">
          <div class="pl-panel-head"><h2 class="pl-k">{{ $t('gestlab.general.labels.vap_proposals.show.template.title') }}</h2></div>
          <div class="grid gap-2 p-4">
            <p class="font-medium">{{ proposal.template.name }}</p>
            <p class="line-clamp-4 text-sm text-[var(--pl-muted)]">{{ resolvedTemplateSummary }}</p>
            <button type="button" class="pl-k pl-acc justify-self-start" @click="showTemplatePreview = true">{{ $t('gestlab.general.labels.vap_proposals.show.view_full_template') }} →</button>
          </div>
        </section>
      </aside>
    </div>

    <NextStepBar>
      {{ nextStep.text }}
      <template #actions>
        <button v-if="canSend" type="button" class="ds-button ds-button-primary" @click="showSendModal = true">
          {{ $t('gestlab.general.labels.vap_proposals.show.send_to_client') }}
        </button>
        <template v-else-if="laboratoryDossier?.primary_action">
          <button v-if="laboratoryDossier.primary_action.disabled" type="button" class="ds-button ds-button-secondary" disabled>{{ laboratoryDossier.primary_action.label }}</button>
          <Link v-else-if="laboratoryDossier.primary_action.method === 'post'" :href="laboratoryDossier.primary_action.url" method="post" as="button" class="ds-button ds-button-primary">{{ laboratoryDossier.primary_action.label }}</Link>
          <Link v-else :href="laboratoryDossier.primary_action.url" class="ds-button ds-button-primary">{{ laboratoryDossier.primary_action.label }}</Link>
        </template>
        <Link v-else-if="canRevise" :href="route('vap-proposals.edit', proposal.id)" class="ds-button ds-button-secondary">{{ $t('gestlab.general.labels.vap_proposals.show.revise') }}</Link>
      </template>
    </NextStepBar>

    <ConfirmationModal :show="showSendModal" @close="showSendModal = false" @confirm="confirmSend">
      <template #title>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.title') }}</template>
      <template #content>
        <div class="grid gap-3 text-sm">
          <p>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.message') }}</p>
          <label class="flex items-center gap-3"><CheckboxInput v-model="sendOptions.generatePdf" type="checkbox" class="ds-checkbox" /><span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.generate_pdf') }}</span></label>
          <label class="flex items-center gap-3"><CheckboxInput v-model="sendOptions.sendEmail" type="checkbox" class="ds-checkbox" /><span>{{ $t('gestlab.general.labels.vap_proposals.show.send_modal.send_email') }}</span></label>
        </div>
      </template>
    </ConfirmationModal>

    <Modal :show="showTemplatePreview" max-width="4xl" @close="showTemplatePreview = false">
      <div class="grid gap-4 p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="pl-k pl-muted">{{ $t('gestlab.general.labels.vap_proposals.show.resolved_preview') }}</p>
            <h2 class="pl-d3 mt-1">{{ proposal.template?.name }}</h2>
            <p class="mt-1 text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_proposals.show.resolved_preview_description') }}</p>
          </div>
          <button type="button" class="ds-icon-button" aria-label="Fechar" @click="showTemplatePreview = false"><XMarkIcon class="h-5 w-5" /></button>
        </div>
        <div class="pl-panel max-h-[65vh] overflow-y-auto p-6">
          <div class="prose max-w-none dark:prose-invert" v-html="resolvedTemplateContent"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { Download as ArrowDownTrayIcon, ArrowRight as ArrowRightIcon, User as UserIcon, X as XMarkIcon } from '@lucide/vue'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import Modal from '@/Components/modal.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import Journey from '@/Components/plano/Journey.vue'

/**
 * The proposal dossier: what was offered, to whom, what the customer decided and,
 * once accepted, how far the laboratory has carried it. One next step at a time.
 */
const props = defineProps({
  proposal: { type: Object, required: true },
  revisions: { type: Array, default: () => [] },
  canSend: { type: Boolean, default: false },
  canRevise: { type: Boolean, default: false },
  parsedTemplateContent: { type: String, default: '' },
  laboratoryDossier: { type: Object, default: null },
})

const showSendModal = ref(false)
const showTemplatePreview = ref(false)
const sendOptions = ref({ generatePdf: true, sendEmail: true })
const copied = ref(false)

const proposalItems = computed(() => props.proposal.items || [])
const resolvedTemplateContent = computed(() => props.parsedTemplateContent || props.proposal.template?.content || '')
const resolvedTemplateSummary = computed(() => stripHtml(resolvedTemplateContent.value) || trans('gestlab.general.labels.vap_proposals.show.empty_template'))
const publicLink = computed(() => (props.proposal.unique_hash ? route('vap-proposals.public.show', props.proposal.unique_hash) : ''))

const statusTone = computed(() => ({
  PENDING: 'neutral', REVISED: 'wait', SENT: 'run', VIEWED: 'run', ACCEPTED: 'ok', REJECTED: 'bad', EXPIRED: 'done',
}[props.proposal.status] ?? 'neutral'))

const facts = computed(() => [
  [trans('gestlab.general.labels.vap_proposals.show.details.customer'), [props.proposal.customer?.name, props.proposal.customer?.code].filter(Boolean).join(' · ')],
  [trans('gestlab.general.labels.vap_proposals.show.details.warehouse'), props.proposal.warehouse?.address || props.proposal.warehouse?.name],
  [trans('gestlab.general.labels.vap_proposals.show.details.service_location'), props.proposal.service_location],
  [trans('gestlab.general.labels.vap_proposals.show.details.created_by'), props.proposal.user?.name],
  [trans('gestlab.general.labels.vap_proposals.show.details.created_on'), formatDate(props.proposal.created_at)],
  [trans('gestlab.general.labels.vap_proposals.show.details.expires_on'), props.proposal.expiry_date ? `${formatDate(props.proposal.expiry_date)} · ${props.proposal.days_until_expiry} d` : `${props.proposal.tolerance_days} d após envio`],
  [trans('gestlab.general.labels.vap_proposals.show.details.pricing_mode'), props.proposal.use_matrix_price ? trans('gestlab.general.labels.vap_proposals.show.matrix') : trans('gestlab.general.labels.vap_proposals.show.parameter')],
  [trans('gestlab.general.labels.vap_proposals.show.details.withhold_tax'), props.proposal.withhold_tax ? trans('gestlab.general.status.yes') : trans('gestlab.general.status.no')],
])

const totals = computed(() => [
  [trans('gestlab.general.labels.vap_proposals.show.items.subtotal'), formatCurrency(props.proposal.sub_total)],
  [trans('gestlab.general.labels.vap_proposals.show.items.total_discount'), Number(props.proposal.discount) > 0 ? `−${formatCurrency(props.proposal.discount)}` : '—'],
  [trans('gestlab.general.labels.vap_proposals.show.items.total_tax'), formatCurrency(props.proposal.tax)],
  ...(Number(props.proposal.withholding_tax_amount) > 0 ? [[trans('gestlab.general.labels.vap_proposals.show.items.withholding_tax'), formatCurrency(props.proposal.withholding_tax_amount)]] : []),
])

const complianceChecks = computed(() => [
  [trans('gestlab.general.labels.vap_proposals.show.compliance.confidentiality'), props.proposal.compliance_agreement?.confidentiality],
  [trans('gestlab.general.labels.vap_proposals.show.compliance.impartiality'), props.proposal.compliance_agreement?.impartiality],
  [trans('gestlab.general.labels.vap_proposals.show.compliance.nondisclosure'), props.proposal.compliance_agreement?.nondisclosure],
])

const journey = computed(() => {
  const commercial = [
    { label: 'Proposta', state: 'done', title: props.proposal.is_original ? 'Criada' : 'Revista', note: formatDate(props.proposal.created_at) },
  ]
  const sent = ['SENT', 'VIEWED', 'ACCEPTED', 'REJECTED', 'EXPIRED'].includes(props.proposal.status)
  const decided = ['ACCEPTED', 'REJECTED'].includes(props.proposal.status)
  commercial.push({ label: 'Envio', state: sent ? 'done' : 'current', title: sent ? 'Enviada' : 'Por enviar', note: props.proposal.status === 'VIEWED' ? 'Vista pelo cliente' : '—' })
  commercial.push({
    label: 'Decisão',
    state: decided ? 'done' : sent ? 'current' : 'todo',
    title: { ACCEPTED: 'Aceite', REJECTED: 'Rejeitada', EXPIRED: 'Expirada' }[props.proposal.status] || 'Cliente',
    note: props.proposal.compliance_agreement?.acknowledged_at ? formatDate(props.proposal.compliance_agreement.acknowledged_at) : '—',
  })

  const steps = (props.laboratoryDossier?.steps || []).map((step) => ({
    label: step.label,
    state: { complete: 'done', current: 'current' }[step.status] || 'todo',
    title: step.status === 'complete' ? 'Concluído' : step.status === 'current' ? 'Em curso' : '—',
    note: '—',
  }))

  return [...commercial, ...steps]
})

const nextStep = computed(() => {
  if (props.canSend) {
    return { text: props.proposal.status === 'REVISED' ? 'Revisão registada. Envie a nova versão ao cliente.' : 'Reveja os serviços e os totais e envie a proposta ao cliente.' }
  }
  if (props.laboratoryDossier?.primary_action) {
    return { text: props.laboratoryDossier.primary_action.description }
  }

  return {
    text: {
      SENT: 'A aguardar a decisão do cliente pela ligação enviada.',
      VIEWED: 'O cliente abriu a proposta. A aguardar a decisão.',
      REJECTED: 'O cliente rejeitou a proposta. Reveja-a para enviar uma nova versão.',
      EXPIRED: 'A proposta expirou sem decisão do cliente.',
      ACCEPTED: 'Proposta aceite. A execução laboratorial segue no dossier.',
    }[props.proposal.status] || 'Sem acções pendentes.',
  }
})

function formatDate(date) {
  return date ? new Intl.DateTimeFormat('pt-AO', { year: 'numeric', month: 'short', day: 'numeric' }).format(new Date(date)) : '—'
}

function formatDateTime(date) {
  return date ? new Intl.DateTimeFormat('pt-AO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(date)) : '—'
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(Number(amount || 0))
}

function stripHtml(html) {
  if (!html) {
    return ''
  }

  const text = html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()

  return text.length > 220 ? `${text.substring(0, 220)}…` : text
}

function getItemableTypeLabel(itemableType) {
  if (itemableType.includes('Matrix')) {
    return trans('gestlab.general.labels.vap_proposals.show.matrix')
  }
  if (itemableType.includes('Parameter')) {
    return trans('gestlab.general.labels.vap_proposals.show.parameter')
  }

  return itemableType.split('\\').pop()
}

function confirmSend() {
  router.post(route('vap-proposals.send', props.proposal.id), { options: sendOptions.value }, {
    onSuccess: () => {
      showSendModal.value = false
    },
  })
}

async function copyToClipboard() {
  try {
    await navigator.clipboard.writeText(publicLink.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch {
    copied.value = false
  }
}
</script>
