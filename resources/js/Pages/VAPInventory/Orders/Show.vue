<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Procurement dossier</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="statusDotClass(order.status)" />
              {{ formatStatus(order.status) }}
            </span>
            <span v-if="receptionNonConformitySummary.open_count" class="ds-chip">
              <span class="lims-status-dot" :class="receptionSeverityDotClass(receptionNonConformitySummary.latest_severity)" />
              {{ receptionNonConformitySummary.open_count }} NC(s) abertas
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Pedido #{{ order.seq || order.id }}</h1>
          <p class="ds-copy mt-2 text-sm">
            {{ order.reference || 'Detalhes do pedido de compra, recepção, risco do fornecedor e evidências operacionais.' }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="goBack">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </button>
          <Link v-if="canEdit" :href="route('vap-inventory.orders.edit', order.id)" class="ds-button ds-button-primary">
            <PencilIcon class="h-4 w-4" />
            Modificar
          </Link>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in summaryCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="ds-panel overflow-hidden">
        <div class="ds-table-summary px-5 py-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
              <ClipboardDocumentListIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Ritmo de recepção</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                  Quantidade encomendada versus entrada efectiva e saldo pendente.
                </p>
              </div>
            </div>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              {{ receptionProgressTotal }} unidades
            </span>
          </div>
        </div>
        <div class="p-5">
          <apexchart type="bar" height="300" :options="receptionProgressChartOptions" :series="receptionProgressChartSeries" />
        </div>
      </article>

      <div class="grid gap-4">
        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h2 class="ds-heading text-base">Distribuição das linhas</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Itens pendentes, parciais e concluídos.</p>
              </div>
              <span class="ds-chip">
                <span class="lims-status-dot lims-status-dot-release" />
                {{ itemStatusMixTotal }} linhas
              </span>
            </div>
          </div>
          <div class="p-5">
            <apexchart type="donut" height="280" :options="itemStatusMixChartOptions" :series="itemStatusMixChartSeries" />
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <h2 class="ds-heading text-base">Pulso de governação</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Fornecedor, não conformidades e envelhecimento operacional.</p>
          </div>
          <div class="p-5">
            <apexchart type="bar" height="245" :options="governanceSummaryChartOptions" :series="governanceSummaryChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_22rem]">
      <div class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <TruckIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Contexto do pedido</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Fornecedor, datas, estado e resumo financeiro.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-5 p-5 lg:grid-cols-[0.95fr_1.05fr]">
            <div class="ds-card p-4">
              <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Fornecedor</p>
              <div class="mt-3 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                  <TruckIcon class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ order.supplier?.name || 'Sem fornecedor' }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ order.supplier?.address || 'Sem endereço' }}</p>
                  <div v-if="order.supplier?.latest_assessment" class="mt-3 flex flex-wrap gap-2">
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="supplierStatusDotClass(order.supplier.latest_assessment.status)" />
                      {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                    </span>
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="supplierRiskDotClass(order.supplier.latest_assessment.risk_level)" />
                      {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                    </span>
                    <span class="ds-chip">
                      Score {{ order.supplier.latest_assessment.total_score ?? '-' }}/100
                    </span>
                  </div>
                  <p v-else class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300">Fornecedor sem avaliação formal registada.</p>
                </div>
              </div>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2">
              <div v-for="field in orderDetailFields" :key="field.label" class="ds-card p-4">
                <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
                <p v-if="field.caption" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ field.caption }}</p>
              </div>
            </dl>
          </div>

          <div v-if="order.obs" class="border-t border-[color:var(--ds-border)] px-5 py-4">
            <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Observações</p>
            <p class="mt-2 whitespace-pre-line text-sm text-[color:var(--ds-text)]">{{ order.obs }}</p>
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div class="flex items-start gap-3">
                <CubeIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
                <div>
                  <h2 class="ds-heading text-base">Itens do pedido</h2>
                  <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                    {{ orderItems.length }} linhas com destino, recepção e estado.
                  </p>
                </div>
              </div>
              <button v-if="canReceiveOrder" type="button" class="ds-button ds-button-primary" @click="receiveOrder">
                <CheckCircleIcon class="h-4 w-4" />
                Receber pedido
              </button>
            </div>
          </div>

          <div v-if="!orderItems.length" class="p-5">
            <div class="ds-empty-state p-6 text-center">
              <CubeIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">Nenhum item no pedido</h3>
              <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">Este pedido ainda não contém linhas de compra.</p>
            </div>
          </div>

          <div v-else class="ds-table-shell overflow-x-auto">
            <DataTable class="min-w-[72rem]">
              <thead class="ds-table-head">
                <tr>
                  <th class="ds-table-cell text-left">Item</th>
                  <th class="ds-table-cell text-left">Quantidade</th>
                  <th class="ds-table-cell text-left">Armazém</th>
                  <th class="ds-table-cell text-left">Datas</th>
                  <th class="ds-table-cell text-left">Estado</th>
                  <th class="ds-table-cell text-left">Acção</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in orderItems" :key="item.id" class="ds-table-row">
                  <td class="ds-table-cell align-top">
                    <div class="flex items-start gap-3">
                      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                        <CubeIcon class="h-4 w-4" />
                      </span>
                      <div class="min-w-0">
                        <p class="max-w-72 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ item.item?.name || 'Item sem nome' }}</p>
                        <p class="mt-1 font-mono text-xs text-[color:var(--ds-text-soft)]">{{ item.item?.internal_code || item.item?.code || '-' }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">
                    <div><span class="font-bold">{{ formatQuantity(item.qty) }}</span> pedidas</div>
                    <div class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ formatQuantity(item.received_qty || 0) }} recebidas</div>
                    <div class="mt-1 text-xs font-semibold text-[color:var(--ds-text)]">{{ formatCurrency(item.total_price || ((item.unit_price || 0) * (item.qty || 0))) }}</div>
                  </td>
                  <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">{{ item.warehouse?.name || 'N/A' }}</td>
                  <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">
                    <div>Prevista: <span class="font-bold">{{ formatDate(item.expected_date) }}</span></div>
                    <div class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Actual: {{ item.actual_date ? formatDate(item.actual_date) : 'Pendente' }}</div>
                  </td>
                  <td class="ds-table-cell align-top">
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="itemStatusDotClass(item.status)" />
                      {{ formatItemStatus(item.status) }}
                    </span>
                  </td>
                  <td class="ds-table-cell align-top">
                    <button v-if="canReceiveItem(item)" type="button" class="ds-table-action" @click="receiveItem(item)">
                      Receber
                    </button>
                    <span v-else class="text-xs font-semibold text-[color:var(--ds-text-soft)]">Sem acção</span>
                  </td>
                </tr>
              </tbody>
            </DataTable>
          </div>
        </article>

        <article v-if="canReceiveOrder" class="ds-command-surface overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <div class="flex items-start gap-3">
              <CheckCircleIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">Fila de recepção</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ receivingSupplierMessage }}</p>
              </div>
            </div>
          </div>

          <div class="grid gap-3 p-5">
            <div v-for="item in pendingItems" :key="item.id" class="ds-card p-4">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ item.item?.name || 'Item sem nome' }}</p>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                    Pedido: {{ formatQuantity(item.qty) }} · Recebido: {{ formatQuantity(item.received_qty || 0) }} · Pendente: {{ formatQuantity(item.qty - (item.received_qty || 0)) }}
                  </p>
                </div>
                <button type="button" class="ds-button ds-button-primary shrink-0" @click="receiveItem(item)">
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Receber
                </button>
              </div>
            </div>
          </div>
        </article>
      </div>

      <aside class="space-y-4">
        <article class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">Acções</h2>
          <div class="mt-4 grid gap-2">
            <Link v-if="canEdit" :href="route('vap-inventory.orders.edit', order.id)" class="ds-button ds-button-secondary w-full">
              <PencilIcon class="h-4 w-4" />
              Modificar pedido
            </Link>
            <button v-if="canReceiveOrder" type="button" class="ds-button ds-button-primary w-full" @click="receiveOrder">
              <CheckCircleIcon class="h-4 w-4" />
              Receber pedido
            </button>
            <button v-if="canCancel" type="button" class="ds-button ds-button-danger w-full" @click="openCancelModal">
              <XCircleIcon class="h-4 w-4" />
              Cancelar pedido
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="printOrder">
              <PrinterIcon class="h-4 w-4" />
              Imprimir
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="exportOrder">
              <ArrowDownTrayIcon class="h-4 w-4" />
              Exportar PDF
            </button>
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Linha do tempo</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Eventos principais do pedido.</p>
          </div>
          <div class="space-y-4 p-5">
            <div v-for="event in timelineItems" :key="event.label" class="flex items-start gap-3">
              <span class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)]">
                <span class="lims-status-dot" :class="event.dotClass" />
              </span>
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ event.label }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ event.value }}</p>
                <p v-if="event.caption" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ event.caption }}</p>
              </div>
            </div>
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Recepção</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Taxa operacional e estado das linhas.</p>
          </div>
          <div class="space-y-4 p-5">
            <div>
              <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-[color:var(--ds-text-soft)]">{{ formatQuantity(receivedQuantity) }}/{{ formatQuantity(totalQuantity) }}</span>
                <span class="font-bold text-[color:var(--ds-text)]">{{ completionRate }}%</span>
              </div>
              <div class="mt-2 h-2 overflow-hidden rounded-full bg-[color:var(--ds-panel-subtle)]">
                <div class="h-full rounded-full bg-[color:var(--lims-release)]" :style="{ width: completionRate + '%' }" />
              </div>
            </div>

            <dl class="grid gap-2">
              <div v-for="metric in receptionBreakdown" :key="metric.label" class="flex items-center justify-between gap-3 text-sm">
                <dt class="flex items-center gap-2 font-semibold text-[color:var(--ds-text-soft)]">
                  <span class="lims-status-dot" :class="metric.dotClass" />
                  {{ metric.label }}
                </dt>
                <dd class="font-bold text-[color:var(--ds-text)]">{{ metric.value }}</dd>
              </div>
            </dl>
          </div>
        </article>
      </aside>
    </section>

    <TransitionRoot as="template" :show="isReceivingModalOpen">
      <Dialog as="div" class="relative z-50" @close="closeReceivingModal">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/45 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-2xl p-0 text-left">
                <div class="flex items-start justify-between gap-4 border-b border-[color:var(--ds-border)] px-5 py-4">
                  <div>
                    <DialogTitle as="h3" class="ds-heading text-lg">
                      {{ isReceivingSingleItem ? 'Receber item' : 'Receber itens do pedido' }}
                    </DialogTitle>
                    <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">{{ receivingSupplierMessage }}</p>
                  </div>
                  <button type="button" class="ds-icon-button" @click="closeReceivingModal">
                    <span class="sr-only">Fechar</span>
                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                  </button>
                </div>

                <form @submit.prevent="submitReceipt">
                  <div class="grid gap-5 p-5">
                    <div v-if="receiptError" class="ds-card border-l-4 border-l-[color:var(--lims-critical)] p-4 text-sm font-semibold text-rose-700 dark:text-rose-300">
                      {{ receiptError }}
                    </div>

                    <div v-if="isReceivingSingleItem && receivingItem" class="ds-card p-4">
                      <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ receivingItem.item?.name || 'Item sem nome' }}</p>
                      <p class="mt-1 font-mono text-xs text-[color:var(--ds-text-soft)]">{{ receivingItem.item?.internal_code || receivingItem.item?.code || '-' }}</p>
                      <dl class="mt-4 grid grid-cols-3 gap-3 text-center text-xs">
                        <div class="ds-card p-3">
                          <dt class="font-bold uppercase text-[color:var(--ds-text-soft)]">Pedido</dt>
                          <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatQuantity(receivingItem.qty) }}</dd>
                        </div>
                        <div class="ds-card p-3">
                          <dt class="font-bold uppercase text-[color:var(--ds-text-soft)]">Recebido</dt>
                          <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatQuantity(receivingItem.received_qty || 0) }}</dd>
                        </div>
                        <div class="ds-card p-3">
                          <dt class="font-bold uppercase text-[color:var(--ds-text-soft)]">Pendente</dt>
                          <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatQuantity(receivingItem.qty - (receivingItem.received_qty || 0)) }}</dd>
                        </div>
                      </dl>
                    </div>

                    <div v-else class="grid gap-3">
                      <p class="text-sm font-semibold text-[color:var(--ds-text-soft)]">Quantidades pendentes de recepção</p>
                      <div v-for="item in pendingItems" :key="item.id" class="ds-card grid gap-3 p-4 sm:grid-cols-[1fr_8rem] sm:items-center">
                        <div class="min-w-0">
                          <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ item.item?.name || 'Item sem nome' }}</p>
                          <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Pendente: {{ formatQuantity(item.qty - (item.received_qty || 0)) }} de {{ formatQuantity(item.qty) }}</p>
                        </div>
                        <BaseInput
                          v-model.number="receivingQuantities[item.id]"
                          type="number"
                          :min="1"
                          :max="item.qty - (item.received_qty || 0)"
                          class="ds-field"
                          placeholder="Qtd"
                        />
                      </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                      <div v-if="isReceivingSingleItem && receivingItem">
                        <label for="quantity" class="ds-field-label">Quantidade a receber</label>
                        <BaseInput
                          id="quantity"
                          v-model.number="receivingQuantity"
                          type="number"
                          :min="1"
                          :max="receivingItem.qty - (receivingItem.received_qty || 0)"
                          required
                          class="ds-field"
                        />
                        <p v-if="isReceivingSingleItem && receivingItem" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                          Máximo: {{ formatQuantity(receivingItem.qty - (receivingItem.received_qty || 0)) }} unidades
                        </p>
                      </div>

                      <div>
                        <label for="receiveDate" class="ds-field-label">Data de recepção</label>
                        <DateTimePicker id="receiveDate" v-model="receiveDate" type="date" required class="ds-field" />
                      </div>

                      <div v-if="isReceivingSingleItem && receivingItem">
                        <label for="unitPrice" class="ds-field-label">Preço unitário</label>
                        <BaseInput id="unitPrice" v-model.number="receivingUnitPrice" type="number" step="0.01" min="0" class="ds-field" />
                        <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Preço original: {{ formatCurrency(receivingItem.unit_price || 0) }}</p>
                      </div>

                      <div>
                        <label for="reason" class="ds-field-label">Motivo</label>
                        <BaseInput id="reason" v-model="receivingReason" type="text" class="ds-field" placeholder="Ex.: recepção normal" />
                      </div>
                    </div>

                    <div>
                      <label for="notes" class="ds-field-label">Observações</label>
                      <textarea id="notes" v-model="receivingNotes" rows="2" class="ds-field" placeholder="Notas adicionais..." />
                    </div>

                    <div class="ds-card border-l-4 border-l-[color:var(--lims-hold)] p-4">
                      <div class="flex items-start gap-3">
                        <CheckboxInput id="registerNonConformity" v-model="registerNonConformity" type="checkbox" class="ds-checkbox mt-1" />
                        <div class="flex-1">
                          <label for="registerNonConformity" class="text-sm font-bold text-[color:var(--ds-text)]">Registar não conformidade de recepção</label>
                          <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">
                            Use esta opção para divergências de qualidade, documentação, dano, preço, lote ou qualquer desvio que exija rastreabilidade.
                          </p>
                        </div>
                      </div>

                      <div v-if="registerNonConformity" class="mt-4 grid gap-4">
                        <div>
                          <label for="ncTitle" class="ds-field-label">Título da não conformidade</label>
                          <BaseInput id="ncTitle" v-model="nonConformityTitle" type="text" class="ds-field" placeholder="Ex: Divergência na recepção do fornecedor" />
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

                  <div class="flex flex-col-reverse gap-2 border-t border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" :disabled="isSubmitting" @click="closeReceivingModal">
                      Cancelar
                    </button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="isSubmitting">
                      <ArrowPathIcon v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                      <CheckCircleIcon v-else class="h-4 w-4" />
                      {{ isSubmitting ? 'A processar...' : 'Confirmar recepção' }}
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
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/45 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-lg p-0 text-left">
                <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
                  <DialogTitle as="h3" class="ds-heading text-lg">Cancelar pedido</DialogTitle>
                  <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">Confirme apenas se este pedido não deve continuar para recepção ou aquisição.</p>
                </div>

                <div class="p-5">
                  <div class="ds-card border-l-4 border-l-[color:var(--lims-critical)] p-4">
                    <p class="text-sm font-semibold text-[color:var(--ds-text)]">Pedido #{{ order.seq || order.id }}</p>
                    <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ order.reference || 'Sem referência' }}</p>
                  </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:justify-end">
                  <button type="button" class="ds-button ds-button-secondary" @click="closeCancelModal">Manter pedido</button>
                  <button type="button" class="ds-button ds-button-danger" @click="cancelOrder">
                    <XCircleIcon class="h-4 w-4" />
                    Cancelar pedido
                  </button>
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
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  PencilIcon,
  PrinterIcon,
  TruckIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const isDarkMode = ref(false)
let themeObserver

const chartTextColor = computed(() => (isDarkMode.value ? '#cbd5e1' : '#475569'))
const chartGridColor = computed(() => (isDarkMode.value ? '#1e293b' : '#e2e8f0'))
const chartTooltipTheme = computed(() => (isDarkMode.value ? 'dark' : 'light'))

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

const order = computed(() => props.order)
const orderItems = computed(() => props.order.items || [])
const receptionNonConformitySummary = computed(() => props.order.reception_non_conformity_summary || {})

const pendingQuantity = computed(() => Math.max(totalQuantity.value - receivedQuantity.value, 0))

const summaryCards = computed(() => [
  {
    label: 'Valor',
    value: formatCurrency(totalAmount.value),
    caption: 'Valor total do pedido',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Linhas',
    value: formatQuantity(orderItems.value.length),
    caption: 'Itens no pedido',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Pedida',
    value: formatQuantity(totalQuantity.value),
    caption: 'Quantidade total',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Recebida',
    value: formatQuantity(receivedQuantity.value),
    caption: 'Entrada em existências',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Pendente',
    value: formatQuantity(pendingQuantity.value),
    caption: 'Saldo a receber',
    dotClass: pendingQuantity.value ? 'lims-status-dot-critical' : 'lims-status-dot-release',
    valueClass: pendingQuantity.value ? 'text-rose-700 dark:text-rose-300' : 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Conclusão',
    value: `${completionRate.value}%`,
    caption: 'Taxa de recepção',
    dotClass: completionRate.value >= 100 ? 'lims-status-dot-release' : 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

const orderDetailFields = computed(() => [
  {
    label: 'Data do pedido',
    value: formatDate(props.order.date),
    caption: `Ano ${props.order.order_year || '-'}`,
  },
  {
    label: 'Criado por',
    value: props.order.user?.name || 'Sistema',
    caption: formatDateTime(props.order.created_at),
  },
  {
    label: 'Estado',
    value: formatStatus(props.order.status),
    caption: 'Estado de tracking do pedido',
  },
  {
    label: 'Última actualização',
    value: formatDateTime(props.order.updated_at),
    caption: props.order.reference || 'Sem referência',
  },
])

const receptionBreakdown = computed(() => [
  {
    label: 'Pendentes',
    value: formatQuantity(pendingItemsCount.value),
    dotClass: 'lims-status-dot-hold',
  },
  {
    label: 'Parciais',
    value: formatQuantity(partiallyReceivedItemsCount.value),
    dotClass: 'lims-status-dot-instrument',
  },
  {
    label: 'Completos',
    value: formatQuantity(fullyReceivedItemsCount.value),
    dotClass: 'lims-status-dot-release',
  },
])

const timelineItems = computed(() => [
  {
    label: 'Criado',
    value: formatDateTime(props.order.created_at),
    caption: props.order.user?.name ? `por ${props.order.user.name}` : 'por Sistema',
    dotClass: 'lims-status-dot-release',
  },
  {
    label: 'Estado actual',
    value: formatStatus(props.order.status),
    caption: props.order.reference || 'Sem referência operacional',
    dotClass: statusDotClass(props.order.status),
  },
  {
    label: 'Última actualização',
    value: formatDateTime(props.order.updated_at),
    caption: `${formatQuantity(pendingItems.value.length)} linha(s) pendente(s)`,
    dotClass: pendingItems.value.length ? 'lims-status-dot-hold' : 'lims-status-dot-release',
  },
])

const receptionProgressChartSeries = computed(() => [
  {
    name: 'Quantidade',
    data: props.charts?.reception_progress?.series || [],
  },
])

const receptionProgressTotal = computed(() => (
  (props.charts?.reception_progress?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
))

const itemStatusMixChartSeries = computed(() => props.charts?.item_status_mix?.series || [])
const itemStatusMixTotal = computed(() => itemStatusMixChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0))

const governanceSummaryChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.governance_summary?.series || [],
  },
])

const receptionProgressChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '48%',
    },
  },
  colors: ['#334155', '#0f766e', '#b45309'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.reception_progress?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    },
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const itemStatusMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.item_status_mix?.labels || [],
  colors: ['#b45309', '#2563eb', '#0f766e'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  legend: {
    position: 'bottom',
    labels: { colors: chartTextColor.value },
  },
  stroke: {
    colors: [isDarkMode.value ? '#020617' : '#ffffff'],
  },
  tooltip: { theme: chartTooltipTheme.value },
}))

const governanceSummaryChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '52%',
    },
  },
  colors: ['#0f766e', '#dc2626', '#7c3aed', '#475569'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.governance_summary?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    },
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const totalAmount = computed(() => {
  if (props.order.total_amount) {
    return Number(props.order.total_amount)
  }

  return orderItems.value.reduce((sum, item) => sum + Number(item.total_price || ((item.unit_price || 0) * (item.qty || 0))), 0)
})

const totalQuantity = computed(() => orderItems.value.reduce((sum, item) => sum + Number.parseInt(item.qty || 0, 10), 0))
const receivedQuantity = computed(() => orderItems.value.reduce((sum, item) => sum + Number.parseInt(item.received_qty || 0, 10), 0))

const completionRate = computed(() => {
  if (totalQuantity.value === 0) {
    return 0
  }

  return Math.min(100, Math.round((receivedQuantity.value / totalQuantity.value) * 100))
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

const receivingSupplierMessage = computed(() => {
  const assessment = props.order?.supplier?.latest_assessment

  if (!assessment) {
    return 'Sem avaliação registada para o fornecedor desta encomenda.'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'Recepção sensível: confirme evidências, conformidade documental e desvios antes de dar entrada em stock.'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'Fornecedor sob monitorização reforçada. Registe observações de recepção e qualquer não conformidade encontrada.'
  }

  return 'Fornecedor avaliado sem alertas críticos no momento da recepção.'
})

function syncDarkMode() {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function normalizeStatus(status) {
  return String(status || '').toUpperCase()
}

function formatStatus(status) {
  const statusMap = {
    PENDING: 'Pendente',
    APPROVED: 'Aprovado',
    ORDERED: 'Pedido',
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

function statusDotClass(status) {
  const classMap = {
    PENDING: 'lims-status-dot-hold',
    APPROVED: 'lims-status-dot-instrument',
    ORDERED: 'lims-status-dot-instrument',
    PARTIALLY_RECEIVED: 'lims-status-dot-hold',
    RECEIVED: 'lims-status-dot-release',
    COMPLETED: 'lims-status-dot-release',
    CANCELLED: 'lims-status-dot-critical',
  }

  return classMap[normalizeStatus(status)] || 'lims-status-dot-instrument'
}

function itemStatusDotClass(status) {
  const classMap = {
    PENDING: 'lims-status-dot-hold',
    ORDERED: 'lims-status-dot-instrument',
    PARTIALLY_RECEIVED: 'lims-status-dot-hold',
    RECEIVED: 'lims-status-dot-release',
    CANCELLED: 'lims-status-dot-critical',
    REJECTED: 'lims-status-dot-critical',
    APPROVED: 'lims-status-dot-instrument',
  }

  return classMap[normalizeStatus(status)] || 'lims-status-dot-instrument'
}

function supplierStatusDotClass(status) {
  const map = {
    approved: 'lims-status-dot-release',
    conditional: 'lims-status-dot-hold',
    suspended: 'lims-status-dot-critical',
    rejected: 'lims-status-dot-critical',
  }

  return map[status] || 'lims-status-dot-hold'
}

function supplierRiskDotClass(risk) {
  const map = {
    low: 'lims-status-dot-release',
    medium: 'lims-status-dot-instrument',
    high: 'lims-status-dot-hold',
    critical: 'lims-status-dot-critical',
  }

  return map[risk] || 'lims-status-dot-hold'
}

function receptionSeverityDotClass(severity) {
  const classMap = {
    low: 'lims-status-dot-release',
    medium: 'lims-status-dot-hold',
    high: 'lims-status-dot-critical',
    critical: 'lims-status-dot-critical',
  }

  return classMap[severity] || 'lims-status-dot-hold'
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

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 2 }).format(numericValue)
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
    ORDERED: 'Pedido',
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

function goBack() {
  router.visit(route('vap-inventory.orders.index'))
}

function openReceivingModal(item = null) {
  receiptError.value = ''
  isReceivingSingleItem.value = Boolean(item)
  receivingItem.value = item

  if (item) {
    receivingQuantity.value = Math.max(1, Number(item.qty || 0) - Number(item.received_qty || 0))
    receivingUnitPrice.value = item.unit_price || 0
  } else {
    pendingItems.value.forEach((pendingItem) => {
      receivingQuantities[pendingItem.id] = Number(pendingItem.qty || 0) - Number(pendingItem.received_qty || 0)
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
      const maximumQuantity = Number(receivingItem.value.qty || 0) - Number(receivingItem.value.received_qty || 0)

      if (receivingQuantity.value <= 0 || receivingQuantity.value > maximumQuantity) {
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
        receiptError.value = errors.message || 'Erro ao processar a recepção.'
      },
    })
  } catch (error) {
    receiptError.value = 'Erro ao processar a recepção.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  syncDarkMode()

  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
