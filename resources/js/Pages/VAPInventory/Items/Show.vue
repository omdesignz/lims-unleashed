<template>
  <div class="pl-page" data-template="dossier">
    <PageHeader
      :crumbs="[{ title: 'Inventário' }, { title: 'Itens', url: route('vap-inventory.items.index') }, { title: item.name }]"
      :title="item.name"
      :lede="lede"
    >
      <template #badges>
        <StatusChip :tone="statusTone(item.status)">{{ item.status?.name || 'Estado por definir' }}</StatusChip>
        <StatusChip v-if="isReagent && item.reagent_expiry_date" :tone="expiryTone(item)">{{ getExpiryStatusText(item) }}</StatusChip>
        <StatusChip v-if="item.metrology_status && item.metrology_status !== 'not_required'" :tone="metrologyTone(item.metrology_status)">Metrologia: {{ getMetrologyStatusText(item.metrology_status).toLowerCase() }}</StatusChip>
      </template>
      <template #actions>
        <Link v-if="nextStep.action !== 'order'" :href="route('vap-inventory.orders.create', { item_id: item.id })" class="ds-button ds-button-quiet">Criar pedido</Link>
        <Link v-if="canEdit" :href="route('vap-inventory.items.edit', item.id)" class="ds-button ds-button-secondary">Modificar</Link>
      </template>
    </PageHeader>

    <div class="pl-dossier-grid">
      <div class="min-w-0">
        <TabGroup>
          <TabList class="pl-tabs mb-6" aria-label="Registo do item">
            <Tab v-slot="{ selected }" as="template"><button type="button" class="pl-tab" :aria-selected="selected">Existências<span class="pl-num">{{ inventory.length }}</span></button></Tab>
            <Tab v-slot="{ selected }" as="template"><button type="button" class="pl-tab" :aria-selected="selected">Movimentos<span class="pl-num">{{ recentTransactions.length }}</span></button></Tab>
            <Tab v-slot="{ selected }" as="template"><button type="button" class="pl-tab" :aria-selected="selected">Actividade<span class="pl-num">{{ recentActivity.length }}</span></button></Tab>
            <Tab v-slot="{ selected }" as="template"><button type="button" class="pl-tab" :aria-selected="selected">Documentos<span class="pl-num">{{ documents.length }}</span></button></Tab>
          </TabList>

          <TabPanels>
            <TabPanel>
              <section class="pl-panel" aria-labelledby="item-stock-title">
                <div class="pl-panel-head">
                  <h2 id="item-stock-title" class="pl-k">Existências por armazém</h2>
                  <div class="flex flex-wrap items-center gap-1">
                    <button type="button" class="ds-table-action" @click="adjustStockModal = true">Ajustar</button>
                    <button type="button" class="ds-table-action" @click="transferStockModal = true">Transferir</button>
                    <button v-if="hasPermission('add_reagent_consumption') && (item.is_reagent || isReagent)" type="button" class="ds-table-action" @click="consumeReagentModal = true">Registar consumo</button>
                  </div>
                </div>

                <DataTable v-if="inventory.length">
                  <thead>
                    <tr>
                      <th scope="col">Armazém</th>
                      <th scope="col" class="text-right">Disponível</th>
                      <th scope="col" class="text-right">Mínimo</th>
                      <th scope="col" class="text-right">Reposição</th>
                      <th scope="col">Estado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="inv in inventory" :key="inv?.id">
                      <td>
                        <span class="font-medium">{{ inv?.warehouse?.name || 'Armazém' }}</span>
                        <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ inv?.warehouse?.location?.name || 'Sem localização' }}<template v-if="inv?.warehouse?.is_refrigerated"> · refrigerado</template></span>
                      </td>
                      <td class="text-right"><span class="pl-num font-medium">{{ inv.qty_available }}</span> <span class="text-[12.5px] text-[var(--pl-muted)]">{{ unitCode }}</span></td>
                      <td class="pl-num text-right">{{ inv.min_stock_level }}</td>
                      <td class="pl-num text-right">{{ inv.reorder_point }}</td>
                      <td><StatusChip :tone="stockTone(inv)">{{ stockStatusLabels[stockStatus(inv)] }}</StatusChip></td>
                    </tr>
                  </tbody>
                </DataTable>
                <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
                  <span class="pl-k">Sem existências neste laboratório</span>
                  <p class="text-sm text-[var(--pl-muted)]">Este item ainda não está associado a um armazém. A entrada faz-se por pedido de compra ou ajuste de existências.</p>
                </div>
              </section>
            </TabPanel>

            <TabPanel>
              <section class="pl-panel" aria-labelledby="item-movements-title">
                <div class="pl-panel-head">
                  <h2 id="item-movements-title" class="pl-k">Movimentos recentes</h2>
                  <span class="pl-k pl-faint">Últimos {{ recentTransactions.length }}</span>
                </div>
                <DataTable v-if="recentTransactions.length">
                  <thead>
                    <tr>
                      <th scope="col">Data</th>
                      <th scope="col">Tipo</th>
                      <th scope="col" class="text-right">Quantidade</th>
                      <th scope="col">Armazém</th>
                      <th scope="col">Utilizador</th>
                      <th scope="col">Motivo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="transaction in recentTransactions" :key="transaction.id">
                      <td class="pl-num whitespace-nowrap">{{ formatDateTime(transaction.created_at) }}</td>
                      <td><StatusChip :tone="transactionTone(transaction)">{{ transaction.type?.name || 'Movimento' }}</StatusChip></td>
                      <td class="pl-num whitespace-nowrap text-right font-medium" :class="transaction.is_addition ? 'text-[var(--pl-ok)]' : 'text-[var(--pl-bad)]'">
                        {{ transaction.is_addition ? '+' : '−' }}{{ transaction.qty }}
                      </td>
                      <td>{{ transaction.warehouse?.name || '—' }}</td>
                      <td>{{ transaction.user?.name || '—' }}</td>
                      <td class="min-w-60">{{ transaction.reason || 'Sem motivo registado' }}</td>
                    </tr>
                  </tbody>
                </DataTable>
                <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
                  <span class="pl-k">Sem movimentos</span>
                  <p class="text-sm text-[var(--pl-muted)]">Entradas, ajustes, consumos e transferências deste item aparecem aqui.</p>
                </div>
              </section>
            </TabPanel>

            <TabPanel>
              <section class="pl-panel" aria-labelledby="item-activity-title">
                <div class="pl-panel-head">
                  <h2 id="item-activity-title" class="pl-k">Actividade recente</h2>
                  <span class="pl-k pl-faint">Movimentos, consumos e transferências</span>
                </div>
                <template v-if="recentActivity.length">
                  <div v-for="activity in recentActivity" :key="activity.type + '-' + activity.id" class="pl-row">
                    <span class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                      <StatusChip :tone="activity.reversed ? 'neutral' : 'run'">{{ activityLabels[activity.type] || 'Registo' }}</StatusChip>
                      <span class="min-w-0">{{ activity.description }}</span>
                    </span>
                    <span class="pl-num text-[12.5px] text-[var(--pl-muted)]">{{ formatTimeAgo(activity.timestamp) }}</span>
                  </div>
                </template>
                <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
                  <span class="pl-k">Sem actividade recente</span>
                  <p class="text-sm text-[var(--pl-muted)]">Os consumos e transferências deste item aparecem aqui.</p>
                </div>
              </section>
            </TabPanel>

            <TabPanel>
              <section class="pl-panel" aria-labelledby="item-documents-title">
                <div class="pl-panel-head">
                  <h2 id="item-documents-title" class="pl-k">Documentos</h2>
                  <span class="pl-k pl-faint">{{ documents.length }} {{ documents.length === 1 ? 'ficheiro' : 'ficheiros' }}</span>
                </div>

                <p v-if="documentMessage" :role="documentFailed ? 'alert' : 'status'" class="border-b border-[var(--pl-line)] px-4 py-3 text-sm" :class="{ 'text-[var(--pl-bad)]': documentFailed }">{{ documentMessage }}</p>

                <template v-if="documents.length">
                  <div v-for="document in documents" :key="document.id || document.name" class="pl-row">
                    <span class="min-w-0">
                      <span class="block truncate font-medium">{{ document.name }}<span v-if="document.archived" class="text-[12.5px] font-normal text-[var(--pl-muted)]"> · Arquivado</span></span>
                      <span class="pl-num block text-[12.5px] text-[var(--pl-muted)]">{{ (document.extension || document.name.split('.').pop() || 'ficheiro').toUpperCase() }} · {{ readableFileSize(document.size) }}</span>
                    </span>
                    <span class="flex items-center gap-1">
                      <button type="button" class="ds-table-action" title="Descarregar" @click="downloadAttachment(document)">
                        <CloudArrowDownIcon class="h-4 w-4" aria-hidden="true" />
                        Descarregar
                      </button>
                      <button v-if="canEdit && !document.archived" type="button" class="ds-table-action" title="Arquivar documento" :disabled="documentProcessing" @click="deleteAttachment(item.id, document.id)">
                        <ArchiveBoxIcon class="h-4 w-4" aria-hidden="true" />
                        Arquivar
                      </button>
                      <button v-if="canEdit && document.archived" type="button" class="ds-table-action" :disabled="documentProcessing" @click="restoreAttachment(document.id)">
                        <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" />
                        Restaurar
                      </button>
                    </span>
                  </div>
                </template>
                <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
                  <span class="pl-k">Nenhum documento associado</span>
                  <p class="text-sm text-[var(--pl-muted)]">Adicione certificados, fichas de segurança ou evidência técnica ao modificar o item.</p>
                </div>
              </section>
            </TabPanel>
          </TabPanels>
        </TabGroup>
      </div>

      <aside class="grid min-w-0 content-start gap-7">
        <section class="pl-panel" aria-labelledby="item-identity-title">
          <div class="pl-panel-head">
            <h2 id="item-identity-title" class="pl-k">Identificação</h2>
            <span class="pl-k pl-faint">{{ item.code || 'Sem código' }}</span>
          </div>
          <dl class="pl-facts">
            <div v-for="field in overviewFields" :key="field.label" class="pl-fact"><dt>{{ field.label }}</dt><dd>{{ field.value }}</dd></div>
            <div v-for="field in identificationFields" :key="field.label" class="pl-fact"><dt>{{ field.label }}</dt><dd class="pl-num">{{ field.value }}</dd></div>
          </dl>
          <div v-if="item.description" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Descrição</h3>
            <p class="text-sm">{{ item.description }}</p>
          </div>
        </section>

        <section class="pl-panel" aria-labelledby="item-control-title">
          <div class="pl-panel-head"><h2 id="item-control-title" class="pl-k">Custos e conservação</h2></div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Custo padrão</dt><dd class="pl-num">{{ item.standard_cost || '—' }}</dd></div>
            <div class="pl-fact"><dt>Último preço</dt><dd class="pl-num">{{ item.last_purchase_price || '—' }}</dd></div>
            <div class="pl-fact"><dt>Reposição</dt><dd class="pl-num">{{ item.reorder_qty || 0 }} {{ unitCode }}</dd></div>
            <div class="pl-fact"><dt>Segurança</dt><dd><StatusChip :tone="item.has_safety_documentation ? 'ok' : 'neutral'">{{ item.has_safety_documentation ? 'Ficha disponível' : 'Sem ficha' }}</StatusChip></dd></div>
            <div class="pl-fact"><dt>Refrigeração</dt><dd>{{ item.refrigerated ? 'Obrigatória' : 'Não aplicável' }}</dd></div>
          </dl>
        </section>

        <section v-if="hasTechnicalSpecs" class="pl-panel" aria-labelledby="item-specs-title">
          <div class="pl-panel-head"><h2 id="item-specs-title" class="pl-k">Especificações técnicas</h2></div>
          <dl class="pl-facts">
            <div v-for="field in technicalSpecFields" :key="field.label" class="pl-fact"><dt>{{ field.label }}</dt><dd>{{ field.value }}</dd></div>
          </dl>
        </section>

        <section v-if="isReagent" class="pl-panel" aria-labelledby="item-expiry-title">
          <div class="pl-panel-head">
            <h2 id="item-expiry-title" class="pl-k">Reagente e validade</h2>
            <StatusChip :tone="expiryTone(item)">{{ getExpiryStatusText(item) }}</StatusChip>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Validade</dt><dd class="pl-num">{{ formatDate(item.reagent_expiry_date) || '—' }}</dd></div>
            <div class="pl-fact"><dt>Abertura</dt><dd class="pl-num">{{ formatDate(item.reagent_open_date) || 'Não aberto' }}</dd></div>
            <div class="pl-fact"><dt>Dias para caducidade</dt><dd class="pl-num">{{ Number(daysToExpiry || 0).toFixed(0) }}</dd></div>
          </dl>
        </section>

        <section v-if="item.next_calibration_date" class="pl-panel" aria-labelledby="item-calibration-title">
          <div class="pl-panel-head">
            <h2 id="item-calibration-title" class="pl-k">Calibração e metrologia</h2>
            <StatusChip :tone="calibrationTone(item)">{{ getCalibrationStatusText(item) }}</StatusChip>
          </div>
          <dl class="pl-facts">
            <div class="pl-fact"><dt>Última calibração</dt><dd class="pl-num">{{ formatDate(item.last_calibration_date) || 'Nunca' }}</dd></div>
            <div class="pl-fact"><dt>Próxima calibração</dt><dd class="pl-num">{{ formatDate(item.next_calibration_date) }}</dd></div>
            <div class="pl-fact"><dt>Estado metrológico</dt><dd><StatusChip :tone="metrologyTone(item.metrology_status)">{{ getMetrologyStatusText(item.metrology_status) }}</StatusChip></dd></div>
            <div v-if="item.metrology_review_due_at" class="pl-fact"><dt>Próxima revisão</dt><dd class="pl-num">{{ formatDate(item.metrology_review_due_at) }}</dd></div>
          </dl>
          <div v-if="item.metrology_notes" class="grid gap-2 border-t border-[var(--pl-line)] p-4">
            <h3 class="pl-k pl-muted">Notas metrológicas</h3>
            <p class="text-sm">{{ item.metrology_notes }}</p>
          </div>
          <button type="button" class="pl-row w-full border-t border-[var(--pl-line)] text-left hover:bg-[var(--pl-layer)]" @click="recordCalibrationModal = true">
            <span>Registar calibração</span><ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </section>
      </aside>
    </div>

    <NextStepBar>
      {{ nextStep.text }}
      <template #actions>
        <button v-if="nextStep.action === 'calibration'" type="button" class="ds-button ds-button-primary" @click="recordCalibrationModal = true">Registar calibração</button>
        <Link v-else-if="nextStep.action === 'order'" :href="route('vap-inventory.orders.create', { item_id: item.id })" class="ds-button ds-button-primary">Criar pedido</Link>
        <button v-else-if="nextStep.action === 'consume'" type="button" class="ds-button ds-button-primary" @click="consumeReagentModal = true">Registar consumo</button>
        <template v-else>
          <button type="button" class="ds-button ds-button-quiet" @click="transferStockModal = true">Transferir</button>
          <button type="button" class="ds-button ds-button-primary" @click="adjustStockModal = true">Ajustar existências</button>
        </template>
      </template>
    </NextStepBar>

    <AdjustStockModal
      :show="adjustStockModal"
      :item="item"
      :inventory="inventory"
      @close="adjustStockModal = false"
      @success="handleStockAdjusted"
    />

    <TransferStockModal
      :show="transferStockModal"
      :item="item"
      :inventory="inventory"
      @close="transferStockModal = false"
      @success="handleTransferCreated"
    />

    <ConsumeReagentModal
      v-if="item.is_reagent || isReagent"
      :show="consumeReagentModal"
      :item="item"
      :inventory="inventory"
      @close="consumeReagentModal = false"
      @success="handleConsumptionRecorded"
    />

    <RecordCalibrationModal
      v-if="item.next_calibration_date"
      :show="recordCalibrationModal"
      :item="item"
      @close="recordCalibrationModal = false"
      @success="handleCalibrationRecorded"
    />
  </div>
</template>
<script setup>
import { useRecordArchive } from '@/Composables/useRecordArchive'
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import {
  ArrowRight as ArrowRightIcon,
  Archive as ArchiveBoxIcon,
  Undo2 as ArrowUturnLeftIcon,
  CloudDownload as CloudArrowDownIcon,
} from '@lucide/vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NextStepBar from '@/Components/plano/NextStepBar.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import AdjustStockModal from '@/Components/vap-inventory/AdjustStockModal.vue'
import TransferStockModal from '@/Components/vap-inventory/TransferStockModal.vue'
import ConsumeReagentModal from '@/Components/vap-inventory/ConsumeReagentModal.vue'
import RecordCalibrationModal from '@/Components/vap-inventory/RecordCalibrationModal.vue'
import { usePermission } from '@/Composables/usePermissions'

/**
 * Inventory item dossier (Plano). Facts sit in the aside; stock by warehouse,
 * movements, activity and documents are the working tabs. The next-step bar
 * points at the one thing the item needs now: calibration, replenishment,
 * consumption or a stock adjustment.
 */
const { hasPermission } = usePermission()

const props = defineProps({
  item: Object,
  canEdit: { type: Boolean, default: false },
  inventory: Array,
  recentTransactions: Array,
  recentOrders: Array,
  recentTransfers: Array,
  recentConsumptions: Array,
  totalStock: Number,
  isReagent: Boolean,
  isExpired: Boolean,
  daysToExpiry: Number,
  needsCalibration: Boolean,
  calibrationStatus: String,
  metrologyStatus: String,
  isMetrologicallyReady: Boolean,
  documents: Array,
  /** Still sent by the controller; the dossier no longer draws charts. */
  charts: {
    type: Object,
    default: () => ({})
  },
})

const isReagent = computed(() => {
  return (props.item.category?.name || '').toLowerCase().includes('reagente');
})

const isEquipment = computed(() => {
  return props.item.category?.inventory_type === 'equipment';
})

const adjustStockModal = ref(false)
const transferStockModal = ref(false)
const consumeReagentModal = ref(false)
const recordCalibrationModal = ref(false)

const unitCode = computed(() => props.item.unit?.code || 'unidades')

const hasTechnicalSpecs = computed(() => {
  return props.item.resolution || props.item.precision || props.item.range ||
         props.item.firmware || props.item.software ||
         props.item.metrological_uncertainty_value || props.item.metrological_traceability_reference
})

const overviewFields = computed(() => [
  { label: 'Categoria', value: props.item.category?.name || '—' },
  { label: 'Tipo', value: props.item.type?.name || '—' },
  { label: 'Unidade', value: props.item.unit?.code || '—' },
  { label: 'Fornecedor', value: props.item.supplier?.name || '—' },
  { label: 'Marca', value: props.item.brand || '—' },
  { label: 'Modelo', value: props.item.model || '—' },
])

const identificationFields = computed(() => [
  { label: 'Código principal', value: props.item.code || '—' },
  { label: 'Código interno', value: props.item.internal_code || '—' },
  { label: 'Código de barras', value: props.item.barcode || '—' },
  isEquipment.value ? { label: 'Número de série', value: props.item.serial_number || '—' } : null,
  isReagent.value ? { label: 'Lote', value: props.item.lot || '—' } : null,
].filter(Boolean))

const technicalSpecFields = computed(() => [
  props.item.resolution ? { label: 'Resolução', value: props.item.resolution } : null,
  props.item.precision ? { label: 'Precisão', value: props.item.precision } : null,
  props.item.range ? { label: 'Alcance / gama', value: props.item.range } : null,
  props.item.firmware ? { label: 'Firmware', value: props.item.firmware } : null,
  props.item.software ? { label: 'Software', value: props.item.software } : null,
  props.item.metrological_uncertainty_value
    ? {
        label: 'Incerteza metrológica',
        value: `${props.item.metrological_uncertainty_value} ${props.item.metrological_uncertainty_unit || ''}`.trim(),
      }
    : null,
  props.item.metrological_traceability_reference
    ? { label: 'Rastreabilidade metrológica', value: props.item.metrological_traceability_reference }
    : null,
].filter(Boolean))

const plural = (count, singular, pluralForm) => `${count} ${count === 1 ? singular : pluralForm}`

const replenishmentPositions = computed(() => (props.inventory ?? [])
  .filter(position => ['out_of_stock', 'critical_stock', 'low_stock'].includes(stockStatus(position))))

const lede = computed(() => {
  const stock = `${props.totalStock || 0} ${unitCode.value} em ${plural(props.inventory?.length ?? 0, 'armazém', 'armazéns')}`
  const replenish = replenishmentPositions.value.length
    ? `, ${replenishmentPositions.value.length} no ponto de reposição ou abaixo`
    : ''

  return `${stock}${replenish}. ${props.item.category?.name || 'Sem categoria'}${props.item.internal_code ? ` · ${props.item.internal_code}` : ''}.`
})

/** The single next action for this item, in order of urgency. */
const nextStep = computed(() => {
  const nextCalibration = props.item.next_calibration_date

  if (nextCalibration && props.item.needs_calibration) {
    return { action: 'calibration', text: `Calibração em atraso desde ${formatDate(nextCalibration)}. Não use o equipamento em ensaios até registar a calibração.` }
  }

  if (nextCalibration && Number(props.item.days_to_calibration) <= 30) {
    return { action: 'calibration', text: `Calibração prevista para ${formatDate(nextCalibration)}. Registe-a quando o certificado for emitido.` }
  }

  if (isReagent.value && props.item.is_expired) {
    return { action: 'order', text: 'Reagente fora de validade: não o use em ensaios e abra um pedido de reposição.' }
  }

  if (replenishmentPositions.value.length) {
    return { action: 'order', text: `${plural(replenishmentPositions.value.length, 'armazém está', 'armazéns estão')} no ponto de reposição ou abaixo. Abra um pedido de compra.` }
  }

  if (!props.inventory?.length) {
    return { action: 'order', text: 'Sem existências neste laboratório. Abra um pedido de compra para receber o item.' }
  }

  if (hasPermission('add_reagent_consumption') && (props.item.is_reagent || isReagent.value)) {
    return { action: 'consume', text: `${props.totalStock || 0} ${unitCode.value} disponíveis. Registe cada consumo para manter o saldo e a rastreabilidade do lote.` }
  }

  return { action: 'adjust', text: `Existências acima do ponto de reposição. Ajuste ou transfira quando houver movimento físico.` }
})

const activityLabels = {
  transaction: 'Movimento',
  consumption: 'Consumo',
  transfer: 'Transferência',
}

const recentActivity = computed(() => {
  const activities = []

  props.recentTransactions.slice(0, 5).forEach(transaction => {
    activities.push({
      id: transaction.id,
      type: 'transaction',
      description: `${transaction.type?.name}: ${transaction.qty} unidades`,
      timestamp: transaction.created_at,
    })
  })

  props.recentConsumptions.slice(0, 3).forEach(consumption => {
    activities.push({
      id: consumption.id,
      type: 'consumption',
      reversed: Boolean(consumption.reversal),
      description: consumption.reversal ? `Consumo revertido: ${consumption.quantity_used} unidades` : `Consumiu: ${consumption.quantity_used} unidades`,
      timestamp: consumption.used_at,
    })
  })

  props.recentTransfers.slice(0, 3).forEach(transfer => {
    activities.push({
      id: transfer.id,
      type: 'transfer',
      description: `Transferiu: ${transfer.qty} unidades para ${transfer.destination?.name}`,
      timestamp: transfer.created_at,
    })
  })

  return activities.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp)).slice(0, 8)
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('pt-PT', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const { processing: documentProcessing, message: documentMessage, failed: documentFailed, submit: submitDocumentArchive } = useRecordArchive({
  destroyUrl: ids => route('vap-inventory.items.attachments.delete', { model_id: props.item.id, id: ids[0] }),
  restoreUrl: ids => route('vap-inventory.items.attachments.restore', { model_id: props.item.id, id: ids[0] }),
});

const formatTimeAgo = (timestamp) => {
  const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000)

  if (seconds < 60) return 'agora'
  if (seconds < 3600) return `há ${Math.floor(seconds / 60)} min`
  if (seconds < 86400) return `há ${Math.floor(seconds / 3600)} h`
  return `há ${Math.floor(seconds / 86400)} d`
}

const statusTone = (status) => {
  if (!status) return 'neutral'

  const statusName = status.name.toLowerCase()
  if (statusName.includes('inactive') || statusName.includes('inactivo') || statusName.includes('out')) {
    return 'bad'
  } else if (statusName.includes('active') || statusName.includes('activo')) {
    return 'ok'
  } else if (statusName.includes('maintenance') || statusName.includes('calibration')) {
    return 'wait'
  }

  return 'neutral'
}

const expiryTone = (item) => {
  if (item.is_expired || item.days_to_expiry <= 30) return 'bad'
  if (item.days_to_expiry <= 60) return 'wait'
  return 'ok'
}

const getExpiryStatusText = (item) => {
  if (item.is_expired) return 'Expirado'
  if (item.days_to_expiry <= 30) return 'Expira em breve'
  if (item.days_to_expiry <= 60) return 'Prestes a expirar'
  return 'Dentro da validade'
}

const calibrationTone = (item) => {
  if (item.needs_calibration || item.days_to_calibration <= 30) return 'bad'
  if (item.days_to_calibration <= 90) return 'wait'
  return 'ok'
}

const getCalibrationStatusText = (item) => {
  if (item.needs_calibration) return 'Atrasada'
  if (item.days_to_calibration <= 30) return 'A vencer em breve'
  if (item.days_to_calibration <= 90) return 'Em breve'
  return 'Agendada'
}

const metrologyTone = (status) => {
  if (status === 'hold' || status === 'incomplete') return 'bad'
  if (status === 'review_due') return 'wait'
  if (status === 'validated') return 'ok'
  return 'neutral'
}

const getMetrologyStatusText = (status) => {
  if (status === 'hold') return 'Bloqueado'
  if (status === 'incomplete') return 'Incompleto'
  if (status === 'review_due') return 'Revisão em breve'
  if (status === 'validated') return 'Validado'
  return 'Não aplicável'
}

/**
 * Same thresholds as the Inventory model's stock_status accessor, which the
 * payload does not carry: none, at or below the minimum, at or below the
 * reorder point, available.
 */
const stockStatus = (inventory) => {
  const available = Number(inventory.qty_available ?? 0)
  if (available <= 0) return 'out_of_stock'
  if (available <= Number(inventory.min_stock_level ?? 0)) return 'critical_stock'
  if (available <= Number(inventory.reorder_point ?? 0)) return 'low_stock'
  return 'in_stock'
}

const stockStatusLabels = {
  out_of_stock: 'Sem existências',
  critical_stock: 'Crítico',
  low_stock: 'Existências baixas',
  in_stock: 'Disponível',
}

const stockTone = (inventory) => {
  const status = stockStatus(inventory)
  if (status === 'out_of_stock' || status === 'critical_stock') return 'bad'
  if (status === 'low_stock') return 'wait'
  return 'ok'
}

const transactionTone = (transaction) => {
  const type = transaction.type?.code
  if (type === 'stock_in' || type === 'stock_adjustment_add' || type === 'consumption_reversal') {
    return 'ok'
  } else if (type === 'stock_out' || type === 'consumption') {
    return 'bad'
  } else if (type === 'stock_transfer') {
    return 'run'
  }

  return 'neutral'
}

const handleStockAdjusted = () => {
  adjustStockModal.value = false
  window.location.reload()
}

const handleTransferCreated = () => {
  transferStockModal.value = false
  window.location.reload()
}

const handleConsumptionRecorded = () => {
  consumeReagentModal.value = false
  window.location.reload()
}

const handleCalibrationRecorded = () => {
  recordCalibrationModal.value = false
  window.location.reload()
}

const readableFileSize = (size) => {
    const units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return `${size.toFixed(2)} ${units[i]}`;
  };

function deleteAttachment(model_id, id) {
  if (!props.canEdit || model_id !== props.item.id) return;
  submitDocumentArchive('delete', [id]);
}

function restoreAttachment(id) {
  if (!props.canEdit) return;
  submitDocumentArchive('restore', [id]);
}

function downloadAttachment(file) {
    window.location.assign(route('vap-inventory.items.attachments.download-single', { model_id: file.id }));
}
</script>
