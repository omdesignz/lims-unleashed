<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Necessidades', url: route('vap-inventory.needs.index') }, { title: need.reference }]"
      :title="`Necessidade ${need.reference}`"
      :lede="[needTitle, need.needed_by_date ? `necessária até ${formatDate(need.needed_by_date)}` : null, `${itemRows.length} ${itemRows.length === 1 ? 'linha' : 'linhas'}`].filter(Boolean).join(' · ')"
    >
      <template #badges><StatusChip :tone="statusTone">{{ statusLabel }}</StatusChip></template>
      <template #actions>
        <a :href="route('vap-inventory.needs.pdf', need.id)" target="_blank" rel="noopener noreferrer" class="ds-button ds-button-secondary">Exportar PDF<span class="sr-only"> (abre noutra janela)</span></a>
        <Link v-if="need.inventory_order" :href="route('vap-inventory.orders.show', need.inventory_order.id)" class="ds-button ds-button-quiet">Pedido {{ need.inventory_order.reference || `#${need.inventory_order.id}` }}</Link>
      </template>
    </PageHeader>

    <Journey v-if="journey.length" class="mb-10" :steps="journey" aria-label="Percurso da necessidade" />

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-labelledby="need-lines-title">
          <div class="pl-panel-head">
            <h2 id="need-lines-title" class="pl-k">Linhas da necessidade</h2>
            <span class="pl-k pl-faint">{{ approvedLineCount }} de {{ itemRows.length }} aprovadas</span>
          </div>
          <DataTable v-if="itemRows.length">
            <thead>
              <tr>
                <th scope="col">Item</th>
                <th scope="col" class="text-right">Solicitado</th>
                <th scope="col" class="text-right">Aprovado</th>
                <th scope="col">Armazém</th>
                <th scope="col" class="text-right">Preço estimado</th>
                <th scope="col">Notas</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in itemRows" :key="item.id">
                <td>
                  <span class="font-medium">{{ item.inventory_item?.name || 'Item não identificado' }}</span>
                  <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ item.inventory_item?.code || 'Sem código' }}</span>
                </td>
                <td class="pl-num text-right">{{ formatQuantity(item.quantity_requested) }} {{ item.inventory_item?.unit?.code }}</td>
                <td class="pl-num text-right">{{ item.quantity_approved ? `${formatQuantity(item.quantity_approved)} ${item.inventory_item?.unit?.code || ''}` : '—' }}</td>
                <td>{{ item.warehouse?.name || 'A definir' }}</td>
                <td class="pl-num text-right">{{ formatMoney(item.estimated_unit_price) }}</td>
                <td class="max-w-xs !whitespace-normal text-[var(--pl-muted)]"><span class="line-clamp-2">{{ item.notes || '—' }}</span></td>
              </tr>
            </tbody>
          </DataTable>
          <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
            <span class="pl-k">Sem linhas</span>
            <p class="text-sm text-[var(--pl-muted)]">Esta necessidade não tem itens registados.</p>
          </div>
          <dl class="pl-facts pl-facts-2 border-t border-[var(--pl-line)]">
            <div class="pl-fact"><dt>Linhas aprovadas</dt><dd class="pl-num">{{ approvedLineCount }} / {{ itemRows.length }}</dd></div>
            <div class="pl-fact"><dt>Valor estimado</dt><dd class="pl-num">{{ formatMoney(estimatedNeedValue) }}</dd></div>
          </dl>
        </section>

        <section v-if="canApprove" class="pl-panel" aria-labelledby="need-decision-title">
          <div class="pl-panel-head">
            <h2 id="need-decision-title" class="pl-k">Decisão de aprovação</h2>
            <span class="pl-k pl-faint">Quantidades até ao solicitado</span>
          </div>
          <DataTable>
            <thead>
              <tr>
                <th scope="col">Item</th>
                <th scope="col" class="text-right">Solicitado</th>
                <th scope="col">Quantidade aprovada</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in actionForm.items" :key="item.id">
                <td>{{ item.name }}</td>
                <td class="pl-num text-right">{{ formatQuantity(itemRows[index]?.quantity_requested) }} {{ itemRows[index]?.inventory_item?.unit?.code }}</td>
                <td class="w-48">
                  <BaseInput
                    :id="`need-approved-${item.id}`"
                    v-model="item.quantity_approved"
                    type="number"
                    min="0.0001"
                    step="0.0001"
                    :max="itemRows[index]?.quantity_requested"
                    :aria-label="`Quantidade aprovada de ${item.name}`"
                    :error="actionForm.errors[`items.${index}.quantity_approved`]"
                  />
                </td>
              </tr>
            </tbody>
          </DataTable>
          <div class="grid gap-3 border-t border-[var(--pl-line)] p-4">
            <BaseTextarea
              id="need-approval-notes"
              v-model="actionForm.approval_notes"
              :rows="3"
              label="Notas da decisão"
              hint="Obrigatórias para rejeitar."
              placeholder="Notas de aprovação, motivo da rejeição ou instruções para a compra."
              :error="actionForm.errors.approval_notes"
            />
            <p v-if="actionForm.errors.items" class="ds-field-error" role="alert">{{ actionForm.errors.items }}</p>
          </div>
        </section>

        <section v-if="canConvertToOrder" class="pl-panel" aria-labelledby="need-conversion-title">
          <div class="pl-panel-head">
            <h2 id="need-conversion-title" class="pl-k">Conversão em pedido de compra</h2>
            <span class="pl-k pl-faint">{{ approvedLineCount }} linhas aprovadas</span>
          </div>
          <div class="pl-form-grid p-4">
            <div class="ds-field-group pl-span-2">
              <comboboxEnhanced
                v-model="selectedSupplierOption"
                title-label="Fornecedor"
                :has-error="Boolean(conversionForm.errors.supplier_id)"
                :options="supplierOptions"
                placeholder="Pesquisar fornecedor aprovado"
              />
              <p v-if="conversionForm.errors.supplier_id" class="ds-field-error" role="alert">{{ conversionForm.errors.supplier_id }}</p>
            </div>

            <div v-if="selectedSupplierAssessment" class="pl-banner pl-span-2 text-sm" :class="supplierAssessmentBannerClass">
              <div class="grid gap-1">
                <span class="pl-k">Avaliação do fornecedor</span>
                <span>Estado {{ supplierAssessmentStatus }} · Risco {{ supplierAssessmentRisk }} · Score {{ selectedSupplierAssessment.total_score ?? '—' }} · Próxima revisão {{ supplierAssessmentReviewLabel }}</span>
                <span v-if="!selectedSupplierAssessment.approved_supplier">Este fornecedor não está marcado como aprovado. Reveja a avaliação antes de concluir a conversão.</span>
              </div>
            </div>
            <p v-else-if="selectedSupplier" class="pl-banner pl-banner-warn pl-span-2 text-sm">
              Este fornecedor ainda não tem avaliação registada. Recomenda-se revisão antes de concluir a compra.
            </p>

            <BaseInput id="need-order-date" v-model="conversionForm.date" type="date" label="Data do pedido" :error="conversionForm.errors.date" />
            <BaseInput id="need-order-expected" v-model="conversionForm.expected_date" type="date" label="Data esperada" :error="conversionForm.errors.expected_date" />
          </div>
        </section>

        <section class="pl-panel" aria-labelledby="need-history-title">
          <div class="pl-panel-head">
            <h2 id="need-history-title" class="pl-k">Histórico de decisão</h2>
            <span class="pl-k pl-faint">{{ statusLabel }}</span>
          </div>
          <article v-for="event in decisionHistory" :key="event.label" class="grid gap-1 border-b border-[var(--pl-line)] p-4 last:border-b-0">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <span class="font-medium">{{ event.label }}</span>
              <span class="pl-k pl-faint">{{ event.at }}</span>
            </div>
            <p class="text-sm text-[var(--pl-muted)]">{{ event.detail }}</p>
            <p v-if="event.notes" class="whitespace-pre-line text-sm">{{ event.notes }}</p>
          </article>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-labelledby="need-facts-title">
          <div class="pl-panel-head"><h2 id="need-facts-title" class="pl-k">Dossier</h2><span class="pl-k pl-faint">{{ need.reference }}</span></div>
          <dl class="pl-facts">
            <div v-for="field in traceabilityFields" :key="field.label" class="pl-fact"><dt>{{ field.label }}</dt><dd>{{ field.value }}</dd></div>
          </dl>
          <div class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Justificação</h3>
            <p class="whitespace-pre-line text-sm">{{ need.justification || 'Sem justificação adicional.' }}</p>
          </div>
        </section>

        <section class="pl-panel" aria-label="Ligações">
          <Link v-if="need.inventory_order" :href="route('vap-inventory.orders.show', need.inventory_order.id)" class="pl-row"><span>Pedido de compra <span class="pl-num">{{ need.inventory_order.reference || `#${need.inventory_order.id}` }}</span></span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
          <a :href="route('vap-inventory.needs.pdf', need.id)" target="_blank" rel="noopener noreferrer" class="pl-row"><span>PDF da necessidade<span class="sr-only"> (abre noutra janela)</span></span><DownloadIcon class="h-4 w-4" aria-hidden="true" /></a>
          <Link :href="route('vap-inventory.needs.index')" class="pl-row"><span>Todas as necessidades</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" /></Link>
        </section>
      </aside>
    </div>

    <NextStepBar>
      <template v-if="canApprove">Reveja as quantidades aprovadas e registe a decisão. A rejeição exige notas.</template>
      <template v-else-if="canConvertToOrder">{{ conversionForm.supplier_id ? `Converter ${approvedLineCount} ${approvedLineCount === 1 ? 'linha aprovada' : 'linhas aprovadas'} num pedido de compra a ${selectedSupplier?.name || 'este fornecedor'}.` : 'Escolha o fornecedor para converter as linhas aprovadas num pedido de compra.' }}</template>
      <template v-else-if="need.status === 'rejected'">Rejeitada por {{ need.approved_by?.name || '—' }} em {{ formatDateTime(need.rejected_at) }}. A necessidade está fechada.</template>
      <template v-else-if="need.inventory_order">Convertida no pedido {{ need.inventory_order.reference || `#${need.inventory_order.id}` }}. A recepção acompanha-se no pedido de compra.</template>
      <template v-else>{{ statusLabel }}. Sem decisão pendente.</template>
      <template #actions>
        <template v-if="canApprove">
          <button type="button" class="ds-button ds-button-quiet" :disabled="actionForm.processing" @click="reject">Rejeitar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="actionForm.processing" @click="approve">{{ actionForm.processing ? 'A registar…' : 'Aprovar' }}</button>
        </template>
        <button v-else-if="canConvertToOrder" type="button" class="ds-button ds-button-primary" :disabled="conversionForm.processing" @click="convertToOrder">
          {{ conversionForm.processing ? 'A converter…' : 'Converter em pedido' }}
        </button>
        <Link v-else-if="need.inventory_order" :href="route('vap-inventory.orders.show', need.inventory_order.id)" class="ds-button ds-button-secondary">Ver pedido</Link>
      </template>
    </NextStepBar>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Journey from '@/Components/plano/Journey.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowRight as ArrowRightIcon, Download as DownloadIcon } from '@lucide/vue'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

/**
 * The need dossier: its lines, the approval decision and the conversion into a purchase
 * order. `canApprove` and `canConvertToOrder` come from the controller, which also
 * enforces the permission on every action. The `charts` prop is still served (and
 * pinned by the controller tests) but the dossier shows the counts as facts instead.
 */
const props = defineProps({
  need: {
    type: Object,
    required: true,
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  canConvertToOrder: {
    type: Boolean,
    default: false,
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const selectedSupplierOption = ref(null)

const needTitle = computed(() => {
  const department = props.need.department?.name || 'Departamento'

  if (!props.need.lab) {
    return department
  }

  return `${department} · ${props.need.lab.name}`
})

const itemRows = computed(() => props.need.items || [])

const approvedLineCount = computed(() => itemRows.value.filter((item) => Number(item.quantity_approved || 0) > 0).length)
const estimatedNeedValue = computed(() => itemRows.value.reduce((sum, item) => {
  const quantity = Number(item.quantity_approved || item.quantity_requested || 0)
  const unitPrice = Number(item.estimated_unit_price || 0)

  return sum + (quantity * unitPrice)
}, 0))

const actionForm = useForm({
  approval_notes: props.need.approval_notes || '',
  items: itemRows.value.map((item) => ({
    id: item.id,
    name: item.inventory_item?.name || 'Item',
    quantity_approved: item.quantity_approved || item.quantity_requested,
  })),
})

const conversionForm = useForm({
  supplier_id: '',
  date: new Date().toISOString().slice(0, 10),
  expected_date: props.need.needed_by_date || '',
  reference: props.need.reference,
  obs: props.need.justification || '',
})

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

watch(selectedSupplierOption, (supplier) => {
  conversionForm.supplier_id = supplier?.value || ''
})

const selectedSupplier = computed(() => props.suppliers.find((supplier) => String(supplier.id) === String(conversionForm.supplier_id)) ?? null)
const selectedSupplierAssessment = computed(() => selectedSupplier.value?.latest_assessment ?? null)

const statusLabel = computed(() => ({
  submitted: 'Por aprovar',
  approved: 'Aprovada',
  rejected: 'Rejeitada',
  ordered: 'Em pedido',
  partially_fulfilled: 'Parcialmente satisfeita',
  fulfilled: 'Satisfeita',
}[props.need.status] ?? props.need.status))

const statusTone = computed(() => ({
  submitted: 'wait',
  approved: 'ok',
  rejected: 'bad',
  ordered: 'run',
  partially_fulfilled: 'run',
  fulfilled: 'done',
}[props.need.status] ?? 'neutral'))

/**
 * Submitted → approved → ordered → fulfilled. A rejected need leaves the sequence, so
 * the strip is hidden and the decision history tells the story.
 */
const journey = computed(() => {
  const sequence = ['submitted', 'approved', 'ordered', 'fulfilled']
  const position = sequence.indexOf(props.need.status === 'partially_fulfilled' ? 'ordered' : props.need.status)

  if (position < 0) {
    return []
  }

  const stateOf = (index) => {
    if (index <= position) {
      return 'done'
    }

    return index === position + 1 ? 'current' : 'todo'
  }
  const orderReference = props.need.inventory_order ? (props.need.inventory_order.reference || `#${props.need.inventory_order.id}`) : null

  return [
    { label: 'Submetida', state: stateOf(0), title: props.need.requested_by?.name, note: formatDateTime(props.need.submitted_at) },
    { label: 'Aprovada', state: stateOf(1), title: props.need.approved_by?.name || 'Por decidir', note: formatDateTime(props.need.approved_at) },
    { label: 'Em pedido', state: stateOf(2), title: orderReference || 'Sem pedido', note: orderReference ? 'Pedido de compra' : null },
    { label: 'Satisfeita', state: stateOf(3), title: props.need.status === 'partially_fulfilled' ? 'Parcialmente' : null, note: null },
  ]
})

const traceabilityFields = computed(() => [
  ['Departamento', props.need.department?.name || '—'],
  ['Laboratório', props.need.lab?.name || 'Não definido'],
  ['Necessária até', formatDate(props.need.needed_by_date)],
  ['Solicitante', props.need.requested_by?.name || '—'],
  ['Submetida em', formatDateTime(props.need.submitted_at)],
  [props.need.status === 'rejected' ? 'Decisor' : 'Aprovador', props.need.approved_by?.name || '—'],
  [props.need.status === 'rejected' ? 'Rejeitada em' : 'Aprovada em', formatDateTime(props.need.status === 'rejected' ? props.need.rejected_at : props.need.approved_at)],
  ['Pedido criado', props.need.inventory_order?.reference || (props.need.inventory_order ? `#${props.need.inventory_order.id}` : '—')],
].map(([label, value]) => ({ label, value })))

const decisionHistory = computed(() => {
  const events = [
    {
      label: 'Submetida para aprovação',
      at: formatDateTime(props.need.submitted_at),
      detail: `Por ${props.need.requested_by?.name || '—'} · ${itemRows.value.length} ${itemRows.value.length === 1 ? 'linha' : 'linhas'}.`,
    },
  ]

  if (props.need.status === 'rejected') {
    events.push({
      label: 'Rejeitada',
      at: formatDateTime(props.need.rejected_at),
      detail: `Por ${props.need.approved_by?.name || '—'}.`,
      notes: props.need.approval_notes,
    })
  } else if (props.need.approved_at) {
    events.push({
      label: 'Aprovada',
      at: formatDateTime(props.need.approved_at),
      detail: `Por ${props.need.approved_by?.name || '—'} · ${approvedLineCount.value} de ${itemRows.value.length} linhas aprovadas.`,
      notes: props.need.approval_notes,
    })
  } else {
    events.push({ label: 'Decisão', at: '—', detail: 'Aguarda aprovação ou rejeição.' })
  }

  if (props.need.inventory_order) {
    events.push({
      label: 'Convertida em pedido de compra',
      at: props.need.inventory_order.reference || `#${props.need.inventory_order.id}`,
      detail: 'As linhas aprovadas seguem no pedido de compra até à recepção.',
    })
  }

  return events
})

const supplierAssessmentStatus = computed(() => ({
  approved: 'Aprovado',
  conditional: 'Condicionado',
  suspended: 'Suspenso',
  rejected: 'Rejeitado',
}[selectedSupplierAssessment.value?.status] ?? 'Sem estado'))

const supplierAssessmentRisk = computed(() => ({
  low: 'Baixo',
  medium: 'Médio',
  high: 'Alto',
  critical: 'Crítico',
}[selectedSupplierAssessment.value?.risk_level] ?? 'Não classificado'))

const supplierAssessmentReviewLabel = computed(() => formatDate(selectedSupplierAssessment.value?.next_review_at))

const supplierAssessmentBannerClass = computed(() => {
  const assessment = selectedSupplierAssessment.value

  if (!assessment) {
    return ''
  }

  if (['suspended', 'rejected'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'pl-banner-bad'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high' || !assessment.approved_supplier) {
    return 'pl-banner-warn'
  }

  return 'pl-banner-ok'
})

const approve = () => {
  actionForm.post(route('vap-inventory.needs.approve', props.need.id))
}

const reject = () => {
  actionForm.post(route('vap-inventory.needs.reject', props.need.id))
}

const convertToOrder = () => {
  conversionForm.post(route('vap-inventory.needs.convert-to-order', props.need.id))
}

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 4 }).format(numericValue)
}

function formatMoney(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue) || numericValue === 0) {
    return '—'
  }

  return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'AOA' }).format(numericValue)
}

const formatDateTime = (value) => (value ? new Date(value).toLocaleString('pt-PT') : '—')
const formatDate = (value) => (value ? new Date(value).toLocaleDateString('pt-PT') : '—')
</script>
