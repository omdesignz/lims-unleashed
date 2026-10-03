<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Pedidos de compra', url: route('vap-inventory.orders.index') }, { title: `#${order.seq || order.id}` }]"
      :title="`Pedido #${order.seq || order.id}`"
      :lede="[order.supplier?.name || 'Sem fornecedor', order.reference, formatCurrency(totalAmount), `${orderItems.length} ${orderItems.length === 1 ? 'linha' : 'linhas'}`].filter(Boolean).join(' · ')"
    >
      <template #badges>
        <StatusChip :tone="statusTone(order.status)">{{ formatStatus(order.status) }}</StatusChip>
        <StatusChip v-if="receptionNonConformitySummary.open_count" :tone="receptionSeverityTone(receptionNonConformitySummary.latest_severity)">
          {{ receptionNonConformitySummary.open_count }} NC abertas
        </StatusChip>
      </template>
      <template #actions>
        <button type="button" class="ds-button ds-button-quiet" @click="printOrder">Imprimir</button>
        <button type="button" class="ds-button ds-button-secondary" @click="exportOrder">Exportar PDF</button>
        <button v-if="canCancel" type="button" class="ds-button ds-button-quiet" @click="openCancelModal">Cancelar pedido</button>
      </template>
    </PageHeader>

    <Journey v-if="journey.length" class="mb-10" :steps="journey" label="Percurso do pedido" />

    <div class="pl-dossier-grid">
      <div class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-label="Linhas e recepção">
          <div class="pl-tabs" role="tablist" aria-label="Secções do pedido">
            <button
              id="order-tab-lines"
              type="button"
              role="tab"
              class="pl-tab"
              aria-controls="order-panel-lines"
              :aria-selected="activeTab === 'lines'"
              @click="activeTab = 'lines'"
            >Linhas <span class="pl-num">{{ orderItems.length }}</span></button>
            <button
              id="order-tab-reception"
              type="button"
              role="tab"
              class="pl-tab"
              aria-controls="order-panel-reception"
              :aria-selected="activeTab === 'reception'"
              @click="activeTab = 'reception'"
            >Recepção <span class="pl-num">{{ pendingItems.length }}</span></button>
          </div>

          <div v-show="activeTab === 'lines'" id="order-panel-lines" role="tabpanel" aria-labelledby="order-tab-lines">
            <DataTable v-if="orderItems.length">
              <thead>
                <tr>
                  <th scope="col">Item</th>
                  <th scope="col" class="text-right">Pedido</th>
                  <th scope="col" class="text-right">Recebido</th>
                  <th scope="col">Armazém</th>
                  <th scope="col" class="text-right">Prevista</th>
                  <th scope="col" class="text-right">Valor</th>
                  <th scope="col">Estado</th>
                  <th scope="col"><span class="sr-only">Acções</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in orderItems" :key="item.id">
                  <td>
                    <span class="block max-w-72 truncate font-medium">{{ item.item?.name || 'Item sem nome' }}</span>
                    <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ item.item?.internal_code || item.item?.code || '—' }}</span>
                  </td>
                  <td class="pl-num text-right">{{ formatQuantity(item.qty) }} {{ item.item?.unit?.code }}</td>
                  <td class="pl-num text-right">
                    {{ formatQuantity(item.received_qty || 0) }} {{ item.item?.unit?.code }}
                    <span v-if="item.actual_date" class="block text-[12px] text-[var(--pl-muted)]">{{ formatDate(item.actual_date) }}</span>
                  </td>
                  <td>{{ item.warehouse?.name || '—' }}</td>
                  <td class="pl-num text-right">{{ formatDate(item.expected_date) }}</td>
                  <td class="pl-num text-right">{{ formatCurrency(item.total_price || ((item.unit_price || 0) * (item.qty || 0))) }}</td>
                  <td><StatusChip :tone="itemStatusTone(item.status)">{{ formatItemStatus(item.status) }}</StatusChip></td>
                  <td class="text-right">
                    <button v-if="canReceiveItem(item)" type="button" class="ds-table-action" :aria-label="`Receber ${item.item?.name || 'linha'}`" @click="receiveItem(item)">Receber</button>
                  </td>
                </tr>
              </tbody>
            </DataTable>
            <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
              <span class="pl-k">Nenhum item no pedido</span>
              <p class="text-sm text-[var(--pl-muted)]">Este pedido ainda não contém linhas de compra.</p>
            </div>
          </div>

          <div v-show="activeTab === 'reception'" id="order-panel-reception" role="tabpanel" aria-labelledby="order-tab-reception">
            <p class="pl-banner m-4 text-sm" :class="supplierBannerClass">{{ receivingSupplierMessage }}</p>
            <div class="grid gap-2 border-b border-[var(--pl-line)] px-4 pb-4">
              <div class="flex items-center justify-between text-sm">
                <span class="pl-k pl-muted">Progressão média por linha</span>
                <span class="pl-num font-medium">{{ completionRate }}%</span>
              </div>
              <div class="pl-bar" role="progressbar" :aria-valuenow="completionRate" aria-valuemin="0" aria-valuemax="100" aria-label="Progressão média por linha"><i :style="{ width: `${completionRate}%` }" /></div>
            </div>
            <dl class="pl-facts pl-facts-2 border-b border-[var(--pl-line)]">
              <div v-for="metric in receptionBreakdown" :key="metric.label" class="pl-fact"><dt>{{ metric.label }}</dt><dd class="pl-num">{{ metric.value }}</dd></div>
            </dl>
            <div v-for="item in pendingItems" :key="`pending-${item.id}`" class="pl-row">
              <span class="grid min-w-0 gap-1">
                <span class="truncate font-medium">{{ item.item?.name || 'Item sem nome' }}</span>
                <span class="text-[12.5px] text-[var(--pl-muted)]">
                  Pedido <span class="pl-num">{{ formatQuantity(item.qty) }}</span> · recebido <span class="pl-num">{{ formatQuantity(item.received_qty || 0) }}</span> · pendente <span class="pl-num">{{ formatQuantity(remainingQuantity(item)) }}</span> {{ item.item?.unit?.code }}
                </span>
              </span>
              <button v-if="canReceiveOrder && canReceiveItem(item)" type="button" class="ds-table-action" :aria-label="`Receber ${item.item?.name || 'linha'}`" @click="receiveItem(item)">Receber</button>
            </div>
            <p v-if="!pendingItems.length" class="p-4 text-sm text-[var(--pl-muted)]">Todas as linhas foram recebidas.</p>
          </div>
        </section>
      </div>

      <aside class="grid min-w-0 gap-7">
        <section class="pl-panel" aria-labelledby="order-supplier-title">
          <div class="pl-panel-head">
            <h2 id="order-supplier-title" class="pl-k">Fornecedor</h2>
            <StatusChip v-if="order.supplier?.latest_assessment" :tone="supplierAssessmentTone">{{ supplierStatusLabel(order.supplier.latest_assessment.status) }}</StatusChip>
            <StatusChip v-else tone="wait">Sem avaliação</StatusChip>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Nome</dt><dd>{{ order.supplier?.name || 'Sem fornecedor' }}</dd></div>
            <div class="pl-fact"><dt>Endereço</dt><dd>{{ order.supplier?.address || '—' }}</dd></div>
            <template v-if="order.supplier?.latest_assessment">
              <div class="pl-fact"><dt>Risco</dt><dd>{{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}</dd></div>
              <div class="pl-fact"><dt>Score</dt><dd class="pl-num">{{ order.supplier.latest_assessment.total_score ?? '—' }}/100</dd></div>
            </template>
            <div v-else class="pl-fact"><dt>Avaliação</dt><dd>Sem avaliação formal registada.</dd></div>
          </dl>
        </section>

        <section class="pl-panel" aria-labelledby="order-facts-title">
          <div class="pl-panel-head"><h2 id="order-facts-title" class="pl-k">Pedido</h2><span class="pl-k pl-faint">{{ order.currency || 'AOA' }}</span></div>
          <dl class="pl-facts">
            <div v-for="field in orderDetailFields" :key="field.label" class="pl-fact"><dt>{{ field.label }}</dt><dd>{{ field.value }}<span v-if="field.caption" class="block text-[12.5px] text-[var(--pl-muted)]">{{ field.caption }}</span></dd></div>
          </dl>
          <div v-if="order.obs" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Observações</h3>
            <p class="whitespace-pre-line text-sm">{{ order.obs }}</p>
          </div>
        </section>

        <section class="pl-panel" aria-labelledby="order-quality-title">
          <div class="pl-panel-head"><h2 id="order-quality-title" class="pl-k">Não conformidades de recepção</h2></div>
          <dl v-if="nonConformitiesAvailable" class="pl-facts">
            <div class="pl-fact"><dt>Registadas</dt><dd class="pl-num">{{ receptionNonConformitySummary.count || 0 }}</dd></div>
            <div class="pl-fact"><dt>Abertas</dt><dd class="pl-num">{{ receptionNonConformitySummary.open_count || 0 }}</dd></div>
            <div v-if="receptionNonConformitySummary.latest_severity" class="pl-fact"><dt>Última severidade</dt><dd>{{ severityLabel(receptionNonConformitySummary.latest_severity) }}</dd></div>
          </dl>
          <p v-else class="p-4 text-sm text-[var(--pl-muted)]">Registo de não conformidades indisponível nesta instalação.</p>
        </section>
      </aside>
    </div>

    <NextStepBar>
      <template v-if="canReceiveOrder">{{ pendingItems.length }} {{ pendingItems.length === 1 ? 'linha por receber' : 'linhas por receber' }} · progressão {{ completionRate }}%. Registe a entrada nas existências à chegada.</template>
      <template v-else-if="canEdit">{{ normalizeStatus(order.status) === 'PENDING' ? 'Pedido pendente de aprovação.' : 'Pedido aprovado.' }} Para seguir para recepção, actualize o estado para Encomendado.</template>
      <template v-else-if="isCancelled">Pedido cancelado. Não segue para aquisição nem recepção.</template>
      <template v-else-if="!pendingItems.length && orderItems.length">Todas as linhas foram recebidas. O pedido está concluído.</template>
      <template v-else>{{ formatStatus(order.status) }}. Sem acção pendente.</template>
      <template #actions>
        <button v-if="canReceiveOrder" type="button" class="ds-button ds-button-primary" @click="receiveOrder">Receber pedido</button>
        <Link v-else-if="canEdit" :href="route('vap-inventory.orders.edit', order.id)" class="ds-button ds-button-primary">Modificar pedido</Link>
      </template>
    </NextStepBar>

    <TransitionRoot as="template" :show="isReceivingModalOpen">
      <Dialog as="div" class="relative z-50" @close="closeReceivingModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-out duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]">
              <DialogPanel class="ds-modal-panel w-full max-w-2xl p-0 text-left">
                <div class="pl-panel-head h-auto py-4">
                  <div>
                    <DialogTitle as="h3" class="pl-d3">
                      {{ isReceivingSingleItem ? 'Receber item' : 'Receber itens do pedido' }}
                    </DialogTitle>
                    <p class="mt-1 text-sm text-[var(--pl-muted)]">{{ receivingSupplierMessage }}</p>
                  </div>
                  <button type="button" class="ds-icon-button" aria-label="Fechar" @click="closeReceivingModal">
                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                  </button>
                </div>

                <form @submit.prevent="submitReceipt">
                  <div class="grid gap-5 p-5">
                    <p v-if="receiptError" class="pl-banner pl-banner-bad text-sm" role="alert">{{ receiptError }}</p>

                    <div v-if="isReceivingSingleItem && receivingItem" class="pl-panel">
                      <div class="pl-panel-head h-auto py-3">
                        <span class="font-medium">{{ receivingItem.item?.name || 'Item sem nome' }}</span>
                        <span class="pl-num pl-faint text-[12.5px]">{{ receivingItem.item?.internal_code || receivingItem.item?.code || '—' }}</span>
                      </div>
                      <dl class="pl-facts">
                        <div class="pl-fact"><dt>Pedido</dt><dd class="pl-num">{{ formatQuantity(receivingItem.qty) }}</dd></div>
                        <div class="pl-fact"><dt>Recebido</dt><dd class="pl-num">{{ formatQuantity(receivingItem.received_qty || 0) }}</dd></div>
                        <div class="pl-fact"><dt>Pendente</dt><dd class="pl-num">{{ formatQuantity(remainingQuantity(receivingItem)) }} {{ receivingItem.item?.unit?.code }}</dd></div>
                      </dl>
                    </div>

                    <div v-else class="pl-panel">
                      <div class="pl-panel-head"><span class="pl-k">Quantidades pendentes de recepção</span></div>
                      <div v-for="item in pendingItems" :key="item.id" class="grid gap-3 border-b border-[var(--pl-line)] p-4 last:border-b-0 sm:grid-cols-[1fr_9rem] sm:items-center">
                        <div class="min-w-0">
                          <p :id="`receive-label-${item.id}`" class="truncate text-sm font-medium">{{ item.item?.name || 'Item sem nome' }}</p>
                          <p class="mt-1 text-[12.5px] text-[var(--pl-muted)]">Pendente: <span class="pl-num">{{ formatQuantity(remainingQuantity(item)) }}</span> de <span class="pl-num">{{ formatQuantity(item.qty) }}</span> {{ item.item?.unit?.code }}</p>
                        </div>
                        <BaseInput
                          v-model="receivingQuantities[item.id]"
                          type="number"
                          min="0.0001"
                          step="0.0001"
                          :max="remainingQuantity(item)"
                          class="ds-field"
                          placeholder="Qtd"
                          :aria-labelledby="`receive-label-${item.id}`"
                        />
                      </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <div v-if="isReceivingSingleItem && receivingItem">
                        <label for="quantity" class="ds-field-label">Quantidade a receber</label>
                        <BaseInput
                          id="quantity"
                          v-model="receivingQuantity"
                          type="number"
                          min="0.0001"
                          step="0.0001"
                          :max="remainingQuantity(receivingItem)"
                          required
                          class="ds-field"
                        />
                        <p class="mt-1 text-[12.5px] text-[var(--pl-muted)]">
                          Máximo: {{ formatQuantity(remainingQuantity(receivingItem)) }} {{ receivingItem.item?.unit?.code }}
                        </p>
                      </div>

                      <div>
                        <label for="receiveDate" class="ds-field-label">Data de recepção</label>
                        <DateTimePicker id="receiveDate" v-model="receiveDate" type="date" required class="ds-field" />
                      </div>

                      <div v-if="isReceivingSingleItem && receivingItem">
                        <label for="unitPrice" class="ds-field-label">Preço unitário</label>
                        <BaseInput id="unitPrice" v-model.number="receivingUnitPrice" type="number" step="0.01" min="0" class="ds-field" />
                        <p class="mt-1 text-[12.5px] text-[var(--pl-muted)]">Preço original: {{ formatCurrency(receivingItem.unit_price || 0) }}</p>
                      </div>

                      <div>
                        <label for="reason" class="ds-field-label">Motivo</label>
                        <BaseInput id="reason" v-model="receivingReason" type="text" class="ds-field" placeholder="Ex.: recepção normal" />
                      </div>
                    </div>

                    <div>
                      <label for="notes" class="ds-field-label">Observações</label>
                      <textarea id="notes" v-model="receivingNotes" rows="2" class="ds-field" placeholder="Notas adicionais…" />
                    </div>

                    <div class="pl-panel p-4">
                      <div class="flex items-start gap-3">
                        <CheckboxInput id="registerNonConformity" v-model="registerNonConformity" type="checkbox" class="ds-checkbox mt-1" :disabled="!nonConformitiesAvailable" aria-describedby="ncAvailabilityNote" />
                        <div class="flex-1">
                          <label for="registerNonConformity" class="text-sm font-medium">Registar não conformidade de recepção</label>
                          <p id="ncAvailabilityNote" class="mt-1 text-[12.5px] text-[var(--pl-muted)]">
                            {{ nonConformitiesAvailable ? 'Use esta opção para divergências de qualidade, documentação, dano, preço, lote ou qualquer desvio que exija rastreabilidade.' : 'Registo indisponível nesta instalação. Esta recepção não criará uma não conformidade.' }}
                          </p>
                        </div>
                      </div>

                      <div v-if="registerNonConformity && nonConformitiesAvailable" class="mt-4 grid gap-4">
                        <div>
                          <label for="ncTitle" class="ds-field-label">Título da não conformidade</label>
                          <BaseInput id="ncTitle" v-model="nonConformityTitle" type="text" class="ds-field" placeholder="Ex.: divergência na recepção do fornecedor" />
                        </div>
                        <div>
                          <label for="ncSeverity" class="ds-field-label">Severidade</label>
                          <BaseSelect id="ncSeverity" v-model="nonConformitySeverity" class="ds-field">
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                            <option value="critical">Crítica</option>
                          </BaseSelect>
                        </div>
                        <div>
                          <label for="ncDescription" class="ds-field-label">Descrição do desvio</label>
                          <textarea id="ncDescription" v-model="nonConformityDescription" rows="3" class="ds-field" placeholder="Descreva claramente o desvio encontrado na recepção." />
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-quiet" :disabled="isSubmitting" @click="closeReceivingModal">Cancelar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="isSubmitting">
                      {{ isSubmitting ? 'A processar…' : 'Confirmar recepção' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <TransitionRoot as="template" :show="isCancelModalOpen">
      <Dialog as="div" class="relative z-50" @close="closeCancelModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-out duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]">
              <DialogPanel class="ds-modal-panel w-full max-w-lg p-0 text-left">
                <div class="pl-panel-head h-auto py-4">
                  <div>
                    <DialogTitle as="h3" class="pl-d3">Cancelar pedido</DialogTitle>
                    <p class="mt-1 text-sm text-[var(--pl-muted)]">Confirme apenas se este pedido não deve continuar para recepção ou aquisição.</p>
                  </div>
                </div>

                <p class="pl-banner pl-banner-bad m-5 text-sm">
                  <span>Pedido <span class="pl-num">#{{ order.seq || order.id }}</span> · {{ order.reference || 'Sem referência' }}</span>
                </p>

                <div class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] px-5 py-4 sm:flex-row sm:justify-end">
                  <button type="button" class="ds-button ds-button-quiet" @click="closeCancelModal">Manter pedido</button>
                  <button type="button" class="ds-button ds-button-danger" @click="cancelOrder">Cancelar pedido</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { X as XMarkIcon } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'
import Journey from '@/Components/plano/Journey.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

/**
 * The purchase order dossier: its lines and receipts in tabs, supplier and order facts
 * in the aside, and one next step that follows the tracking status (modify while pending
 * or approved, receive while ordered or partially received). Receipts accept quantities
 * with up to four decimals and never exceed the pending balance. The `charts` prop is
 * still served by the controller (and pinned by its tests); the dossier shows the same
 * counts as facts.
 */
const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
  nonConformitiesAvailable: {
    type: Boolean,
    default: false,
  },
})

const isReceivingModalOpen = ref(false)
const isReceivingSingleItem = ref(false)
const receivingItem = ref(null)
const receivingQuantity = ref(1)
const receivingUnitPrice = ref(0)
const receiveDate = ref(new Date().toISOString().split('T')[0])
const receivingReason = ref('Recepção de Item')
const receivingNotes = ref('')
const registerNonConformity = ref(false)
const nonConformityTitle = ref('')
const nonConformitySeverity = ref('medium')
const nonConformityDescription = ref('')
const isSubmitting = ref(false)
const receiptError = ref('')
const isCancelModalOpen = ref(false)
const receivingQuantities = reactive({})
const activeTab = ref('lines')

const order = computed(() => props.order)
const orderItems = computed(() => props.order.items || [])
const receptionNonConformitySummary = computed(() => props.order.reception_non_conformity_summary || {})

const orderDetailFields = computed(() => [
  {
    label: 'Data do pedido',
    value: formatDate(props.order.date),
    caption: `Ano ${props.order.order_year || '—'}`,
  },
  {
    label: 'Referência',
    value: props.order.reference || 'Sem referência',
  },
  {
    label: 'Criado por',
    value: props.order.user?.name || 'Sistema',
    caption: formatDateTime(props.order.created_at),
  },
  {
    label: 'Última actualização',
    value: formatDateTime(props.order.updated_at),
  },
  {
    label: 'Valor total',
    value: formatCurrency(totalAmount.value),
  },
  {
    label: 'Valor recebido',
    value: formatCurrency(props.order.received_amount || 0),
  },
])

const receptionBreakdown = computed(() => [
  {
    label: 'Sem entrada',
    value: formatQuantity(pendingItemsCount.value),
  },
  {
    label: 'Parciais',
    value: formatQuantity(partiallyReceivedItemsCount.value),
  },
  {
    label: 'Completas',
    value: formatQuantity(fullyReceivedItemsCount.value),
  },
  {
    label: 'Por receber',
    value: formatQuantity(pendingItems.value.length),
  },
])

/**
 * Registered → approved → ordered → received. A cancelled order (or any status outside
 * this sequence) leaves the strip hidden.
 */
const journey = computed(() => {
  const status = normalizeStatus(props.order.status)
  const position = {
    PENDING: 0,
    APPROVED: 1,
    ORDERED: 2,
    PARTIALLY_RECEIVED: 2,
    RECEIVED: 3,
    COMPLETED: 3,
  }[status]

  if (position === undefined) {
    return []
  }

  const stateOf = (index) => {
    if (index <= position) {
      return 'done'
    }

    return index === position + 1 ? 'current' : 'todo'
  }

  return [
    { label: 'Registado', state: stateOf(0), title: props.order.user?.name || 'Sistema', note: formatDateTime(props.order.created_at) },
    { label: 'Aprovado', state: stateOf(1), title: position >= 1 ? 'Aprovado' : 'Por aprovar', note: null },
    { label: 'Encomendado', state: stateOf(2), title: position >= 2 ? formatDate(props.order.date) : null, note: props.order.supplier?.name },
    { label: 'Recebido', state: stateOf(3), title: position >= 2 ? `${completionRate.value}%` : null, note: position >= 2 ? `${fullyReceivedItemsCount.value} de ${orderItems.value.length} linhas completas` : null },
  ]
})

const totalAmount = computed(() => {
  if (props.order.total_amount) {
    return Number(props.order.total_amount)
  }

  return orderItems.value.reduce((sum, item) => sum + Number(item.total_price || ((item.unit_price || 0) * (item.qty || 0))), 0)
})

const completionRate = computed(() => {
  if (orderItems.value.length === 0) {
    return 0
  }

  const lineProgress = orderItems.value.reduce((sum, item) => {
    const ordered = Number(item.qty || 0)
    return sum + (ordered > 0 ? Math.min(1, Number(item.received_qty || 0) / ordered) : 0)
  }, 0)

  return Math.round((lineProgress / orderItems.value.length) * 100)
})

const pendingItems = computed(() => orderItems.value.filter((item) => {
  const received = Number(item.received_qty || 0)

  return received < Number(item.qty || 0)
}))

const pendingItemsCount = computed(() => orderItems.value.filter((item) => Number(item.received_qty || 0) === 0).length)
const partiallyReceivedItemsCount = computed(() => orderItems.value.filter((item) => {
  const received = Number(item.received_qty || 0)

  return received > 0 && received < Number(item.qty || 0)
}).length)
const fullyReceivedItemsCount = computed(() => orderItems.value.filter((item) => Number(item.received_qty || 0) >= Number(item.qty || 0)).length)

const canEdit = computed(() => ['PENDING', 'APPROVED'].includes(normalizeStatus(props.order.status)))
const canReceiveOrder = computed(() => ['ORDERED', 'PARTIALLY_RECEIVED'].includes(normalizeStatus(props.order.status)) && pendingItems.value.length > 0)
const canCancel = computed(() => ['PENDING', 'APPROVED', 'ORDERED'].includes(normalizeStatus(props.order.status)))
const isCancelled = computed(() => normalizeStatus(props.order.status).startsWith('CANCELLED'))

const supplierAssessmentTone = computed(() => {
  const assessment = props.order?.supplier?.latest_assessment

  if (!assessment) {
    return 'wait'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'bad'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'wait'
  }

  return 'ok'
})

const supplierBannerClass = computed(() => ({
  bad: 'pl-banner-bad',
  wait: 'pl-banner-warn',
  ok: 'pl-banner-ok',
}[supplierAssessmentTone.value]))

const receivingSupplierMessage = computed(() => {
  const assessment = props.order?.supplier?.latest_assessment

  if (!assessment) {
    return 'Sem avaliação registada para o fornecedor desta encomenda.'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'Recepção sensível: confirme evidências, conformidade documental e desvios antes de dar entrada nas existências.'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'Fornecedor sob monitorização reforçada. Registe observações de recepção e qualquer não conformidade encontrada.'
  }

  return 'Fornecedor avaliado sem alertas críticos no momento da recepção.'
})

function normalizeStatus(status) {
  return String(status || '').toUpperCase()
}

function formatStatus(status) {
  const statusMap = {
    PENDING: 'Pendente',
    APPROVED: 'Aprovado',
    ORDERED: 'Encomendado',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
    COMPLETED: 'Concluído',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function supplierStatusLabel(status) {
  const map = {
    approved: 'Aprovado',
    conditional: 'Condicional',
    suspended: 'Suspenso',
    rejected: 'Rejeitado',
  }

  return map[status] || status || 'Sem avaliação'
}

function supplierRiskLabel(risk) {
  const map = {
    low: 'Risco baixo',
    medium: 'Risco médio',
    high: 'Risco elevado',
    critical: 'Risco crítico',
  }

  return map[risk] || risk || 'Sem classificação'
}

function statusTone(status) {
  return {
    PENDING: 'wait',
    APPROVED: 'ok',
    ORDERED: 'run',
    PARTIALLY_RECEIVED: 'run',
    RECEIVED: 'done',
    COMPLETED: 'done',
    CANCELLED: 'bad',
  }[normalizeStatus(status)] || 'neutral'
}

function itemStatusTone(status) {
  return {
    PENDING: 'wait',
    APPROVED: 'ok',
    ORDERED: 'run',
    PARTIALLY_RECEIVED: 'run',
    RECEIVED: 'done',
    CANCELLED: 'bad',
    REJECTED: 'bad',
  }[normalizeStatus(status)] || 'neutral'
}

function receptionSeverityTone(severity) {
  return ['high', 'critical'].includes(severity) ? 'bad' : 'wait'
}

function severityLabel(severity) {
  return {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
    critical: 'Crítica',
  }[severity] || severity
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: props.order.currency || 'AOA',
  }).format(Number(amount || 0))
}

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 4 }).format(numericValue)
}

function scaledQuantity(value) {
  const match = String(value ?? '0').match(/^(\d+)(?:\.(\d{1,4}))?$/)

  if (!match) {
    return null
  }

  return (BigInt(match[1]) * 10000n) + BigInt((match[2] || '').padEnd(4, '0'))
}

function remainingQuantity(item) {
  const ordered = scaledQuantity(item.qty) ?? 0n
  const received = scaledQuantity(item.received_qty) ?? 0n
  const balance = ordered > received ? ordered - received : 0n
  return `${balance / 10000n}.${String(balance % 10000n).padStart(4, '0')}`
}

function isValidReceiptQuantity(value, item) {
  const quantity = scaledQuantity(value)
  const remaining = scaledQuantity(remainingQuantity(item))

  return quantity !== null && remaining !== null && quantity > 0n && quantity <= remaining
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  }).format(date)
}

function formatDateTime(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function formatItemStatus(status) {
  const statusMap = {
    PENDING: 'Pendente',
    ORDERED: 'Encomendado',
    PARTIALLY_RECEIVED: 'Recebido parcialmente',
    RECEIVED: 'Recebido',
    CANCELLED: 'Cancelado',
    REJECTED: 'Rejeitado',
    APPROVED: 'Aprovado',
    CANCELLED_BY_SUPPLIER: 'Cancelado pelo fornecedor',
    CANCELLED_BY_USER: 'Cancelado pelo utilizador',
  }

  return statusMap[normalizeStatus(status)] || status || 'Sem estado'
}

function canReceiveItem(item) {
  const received = Number(item.received_qty || 0)

  return received < Number(item.qty || 0) && ['ORDERED', 'PARTIALLY_RECEIVED'].includes(normalizeStatus(item.status))
}

function openCancelModal() {
  isCancelModalOpen.value = true
}

function closeCancelModal() {
  isCancelModalOpen.value = false
}

function cancelOrder() {
  router.post(route('vap-inventory.orders.cancel', props.order.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      closeCancelModal()
    },
  })
}

function printOrder() {
  window.print()
}

function exportOrder() {
  window.location.assign(route('vap-inventory.orders.export-pdf', props.order.id))
}

function openReceivingModal(item = null) {
  receiptError.value = ''
  isReceivingSingleItem.value = Boolean(item)
  receivingItem.value = item

  if (item) {
    receivingQuantity.value = remainingQuantity(item)
    receivingUnitPrice.value = item.unit_price || 0
  } else {
    pendingItems.value.forEach((pendingItem) => {
      receivingQuantities[pendingItem.id] = remainingQuantity(pendingItem)
    })
  }

  isReceivingModalOpen.value = true
}

function closeReceivingModal() {
  if (isSubmitting.value) {
    return
  }

  isReceivingModalOpen.value = false

  window.setTimeout(() => {
    isReceivingSingleItem.value = false
    receivingItem.value = null
    receivingQuantity.value = 1
    receivingUnitPrice.value = 0
    receivingReason.value = 'Recepção de Item'
    receivingNotes.value = ''
    registerNonConformity.value = false
    nonConformityTitle.value = ''
    nonConformitySeverity.value = 'medium'
    nonConformityDescription.value = ''
    receiptError.value = ''

    Object.keys(receivingQuantities).forEach((key) => {
      delete receivingQuantities[key]
    })
  }, 200)
}

function receiveItem(item) {
  openReceivingModal(item)
}

function receiveOrder() {
  openReceivingModal()
}

async function submitReceipt() {
  if (isSubmitting.value) {
    return
  }

  receiptError.value = ''

  try {
    isSubmitting.value = true

    let itemsData = []

    if (isReceivingSingleItem.value && receivingItem.value) {
      if (!isValidReceiptQuantity(receivingQuantity.value, receivingItem.value)) {
        receiptError.value = 'Quantidade inválida para a recepção seleccionada.'
        isSubmitting.value = false

        return
      }

      itemsData.push({
        id: receivingItem.value.id,
        received_qty: receivingQuantity.value,
        unit_price: receivingUnitPrice.value || receivingItem.value.unit_price || 0,
      })
    } else {
      if (pendingItems.value.some((item) => Number(receivingQuantities[item.id] || 0) > 0 && !isValidReceiptQuantity(receivingQuantities[item.id], item))) {
        receiptError.value = 'Uma das quantidades excede o saldo pendente ou tem mais de quatro casas decimais.'
        isSubmitting.value = false

        return
      }

      itemsData = pendingItems.value
        .filter((item) => receivingQuantities[item.id] > 0)
        .map((item) => ({
          id: item.id,
          received_qty: receivingQuantities[item.id],
          unit_price: item.unit_price || 0,
        }))

      if (itemsData.length === 0) {
        receiptError.value = 'Seleccione quantidades para pelo menos um item.'
        isSubmitting.value = false

        return
      }
    }

    if (registerNonConformity.value && !nonConformityDescription.value.trim()) {
      receiptError.value = 'Descreva o desvio antes de registar a não conformidade.'
      isSubmitting.value = false

      return
    }

    const receiptData = {
      items: itemsData,
      receive_date: receiveDate.value,
      reason: receivingReason.value || 'Recepção de Item',
      notes: receivingNotes.value,
      register_non_conformity: registerNonConformity.value,
      non_conformity_title: registerNonConformity.value ? (nonConformityTitle.value || `Não conformidade na recepção do pedido ${props.order.seq || props.order.id}`) : null,
      non_conformity_severity: registerNonConformity.value ? nonConformitySeverity.value : null,
      non_conformity_description: registerNonConformity.value ? nonConformityDescription.value : null,
    }

    await router.post(route('vap-inventory.orders.receive', props.order.id), receiptData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeReceivingModal()
        router.reload({ only: ['order'] })
      },
      onError: (errors) => {
        receiptError.value = errors.register_non_conformity || errors.message || 'Erro ao processar a recepção.'
      },
    })
  } catch (error) {
    receiptError.value = 'Erro ao processar a recepção.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
