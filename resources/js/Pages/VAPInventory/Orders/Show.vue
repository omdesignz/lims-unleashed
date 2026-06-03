<template>
  <div class="procurement-show-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      :icon="ShoppingCartIcon"
      :title="`Pedido #${order.seq || order.id}`"
      :subtitle="order.reference || 'Detalhes do pedido de compra, recepção e evidências operacionais.'"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            statusClass
          ]">
            {{ formatStatus(order.status) }}
          </span>
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white dark:border-white/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            Voltar
          </button>
        </div>
      </template>
    </ModuleHero>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Ritmo de recepção</h2>
            <p class="mt-1 text-sm text-gray-500">
              Quantidade encomendada versus entrada efectiva e saldo ainda em aberto.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Unidades monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ receptionProgressTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="receptionProgressChartOptions" :series="receptionProgressChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Distribuição das linhas</h2>
              <p class="mt-1 text-sm text-gray-500">Leitura rápida entre itens pendentes, parciais e concluídos.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ itemStatusMixTotal }} linhas
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="itemStatusMixChartOptions" :series="itemStatusMixChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pulso de governação</h2>
              <p class="mt-1 text-sm text-gray-500">Fornecedor, não conformidades e envelhecimento operacional do pedido.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="governanceSummaryChartOptions" :series="governanceSummaryChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- ORDER DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
            Detalhes do Pedido
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Supplier Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Fornecedor
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <TruckIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ order.supplier?.name }}</p>
                  <p class="text-xs text-gray-500">{{ order.supplier?.address || 'Sem endereço' }}</p>
                  <div v-if="order.supplier?.latest_assessment" class="mt-2 flex flex-wrap gap-2">
                    <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                      {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                    </span>
                    <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                      {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                    </span>
                  </div>
                  <p v-else class="mt-2 text-xs font-medium text-amber-700">Fornecedor sem avaliação registada.</p>
                </div>
              </div>
            </div>

            <!-- Order Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Informações do Pedido
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Data do Pedido:</span>
                  <span class="font-medium text-gray-900">{{ formatDate(order.date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Ano do Pedido:</span>
                  <span class="font-medium text-gray-900">{{ order.order_year || 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Criado em:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(order.created_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Status Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Informações do Estado
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Estado:</span>
                  <span :class="statusClass">{{ formatStatus(order.status) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Última Atualização:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(order.updated_at) }}</span>
                </div>
                <div v-if="order.user" class="flex justify-between text-sm">
                  <span class="text-gray-600">Criado Por:</span>
                  <span class="font-medium text-gray-900">{{ order.user.name }}</span>
                </div>
              </div>
            </div>

            <!-- Summary -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Resumo do Pedido
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Total de Itens:</span>
                  <span class="font-medium text-gray-900">{{ order.items?.length || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Quantidade Total:</span>
                  <span class="font-medium text-gray-900">{{ totalQuantity }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Quantidade Recebida:</span>
                  <span class="font-medium text-green-600">{{ receivedQuantity }}</span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="order.supplier?.latest_assessment" class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">Estado da avaliação do fornecedor</h3>
                <p class="mt-1 text-xs text-slate-500">Contexto de risco e conformidade no momento da compra.</p>
              </div>
              <div class="text-right text-xs text-slate-500">
                <div>Score {{ order.supplier.latest_assessment.total_score ?? '—' }}/100</div>
                <div>Próxima revisão {{ formatDate(order.supplier.latest_assessment.next_review_at) || '—' }}</div>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
              </span>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
              </span>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', order.supplier.latest_assessment.approved_supplier ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                {{ order.supplier.latest_assessment.approved_supplier ? 'Fornecedor aprovado' : 'Aprovação pendente' }}
              </span>
            </div>
          </div>

          <!-- Observations -->
          <div v-if="order.obs" class="mt-6 space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ order.obs }}</p>
            </div>
          </div>
        </div>

        <!-- ORDER ITEMS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <CubeIcon class="h-5 w-5 text-blue-900" />
              Itens do Pedido
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ order.items?.length || 0 }} itens)
              </span>
            </h2>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!order.items || order.items.length === 0" class="p-12 text-center">
            <CubeIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhum item no pedido
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Este pedido não contém nenhum item
            </p>
          </div>

          <!-- ITEMS TABLE -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Armazém</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Prevista</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data Actual</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acções</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="item in order.items" 
                  :key="item.id"
                  class="hover:bg-gray-50"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-8 w-8">
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                          <CubeIcon class="h-4 w-4 text-blue-900" />
                        </div>
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ item.item?.name }}</div>
                        <div class="text-xs text-gray-500">{{ item.item?.internal_code }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center gap-2">
                      <span class="font-medium">{{ item.qty }}</span>
                      <span v-if="item.received_qty" class="text-xs text-green-600">
                        (Recebida: {{ item.received_qty }})
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.warehouse?.name || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(item.expected_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span :class="item.actual_date ? 'text-green-600' : 'text-yellow-600'">
                      {{ formatDate(item.actual_date) || 'Pendente' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      getItemStatusClass(item.status)
                    ]">
                      {{ formatItemStatus(item.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button 
                      @click="receiveItem(item)"
                      class="text-purple-900 hover:text-purple-700"
                      v-if="canReceiveItem(item)"
                    >
                      Receber
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RECEIVE ORDER SECTION -->
        <div v-if="canReceiveOrder" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CheckCircleIcon class="h-5 w-5 text-blue-900" />
            Receber Itens do Pedido
          </h2>

          <div
            v-if="order.supplier?.latest_assessment"
            class="mb-4 rounded-xl border px-4 py-4"
            :class="receivingSupplierPanelClass"
          >
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold">Contexto do fornecedor para recepção</p>
                <p class="mt-1 text-xs opacity-80">{{ receivingSupplierMessage }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                  {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                </span>
                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                  {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="space-y-4">
            <div v-for="item in pendingItems" :key="item.id" class="p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ item.item?.name }}</p>
                  <p class="text-xs text-gray-500">
                    Pedido: {{ item.qty }}, Recebido: {{ item.received_qty || 0 }}, Pendente: {{ item.qty - (item.received_qty || 0) }}
                  </p>
                </div>
                <button 
                  @click="receiveItem(item)"
                  class="inline-flex items-center gap-2 rounded-lg bg-purple-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-800"
                >
                  <ArrowDownTrayIcon class="h-3 w-3" />
                  Receber
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções
          </h3>
          <div class="space-y-3">
            <!-- Edit Button -->
            <a 
              v-if="canEdit"
              :href="route('vap-inventory.orders.edit', order.id)"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PencilIcon class="h-5 w-5" />
              Modificar Pedido
            </a>

            <!-- Receive Button -->
            <button 
              v-if="canReceiveOrder"
              @click="receiveOrder"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-green-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2"
            >
              <CheckCircleIcon class="h-5 w-5" />
              Receber Pedido
            </button>

            <!-- Cancel Button -->
            <button 
              v-if="canCancel"
              @click="cancelOrder"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
            >
              <XCircleIcon class="h-5 w-5" />
              Cancelar Pedido
            </button>

            <!-- Print Button -->
            <button 
              @click="printOrder"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PrinterIcon class="h-5 w-5" />
              Imprimir Pedido
            </button>

            <!-- Export Button -->
            <button 
              @click="exportOrder"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar como PDF
            </button>
          </div>
        </div>

        <!-- ORDER TIMELINE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            Linha do Tempo do Pedido
          </h3>
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-500 flex items-center justify-center">
                <CheckIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Criado</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(order.created_at) }}</p>
                <p class="text-xs text-gray-500">by {{ order.user?.name || 'Sistema' }}</p>
              </div>
            </div>
            
            <div v-if="order.status !== 'pending'" class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-500 flex items-center justify-center">
                <CheckIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Estado Atualizado</p>
                <p class="text-xs text-gray-500">{{ formatStatus(order.status) }}</p>
              </div>
            </div>
            
            <div v-if="order.updated_at && order.updated_at !== order.created_at" class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                <PencilIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Última Atualização</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(order.updated_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ORDER STATS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Estatísticas do Pedido
          </h3>
          <div class="space-y-3">
            <div>
              <p class="text-sm font-medium text-gray-600">Taxa de Recepção</p>
              <div class="mt-1">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">{{ receivedQuantity }}/{{ totalQuantity }}</span>
                  <span class="font-medium text-gray-900">{{ completionRate }}%</span>
                </div>
                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-green-600 h-2 rounded-full" 
                    :style="{ width: completionRate + '%' }"
                  ></div>
                </div>
              </div>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Estado dos Itens</p>
              <div class="mt-1 space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Pendentes:</span>
                  <span class="font-medium text-yellow-600">{{ pendingItemsCount }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Recebidos Parcialmente:</span>
                  <span class="font-medium text-orange-600">{{ partiallyReceivedItemsCount }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-600">Recebidos Completamente:</span>
                  <span class="font-medium text-green-600">{{ fullyReceivedItemsCount }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <!-- RECEIVE ITEM MODAL -->
<TransitionRoot as="template" :show="isReceivingModalOpen">
  <Dialog as="div" class="relative z-50" @close="closeReceivingModal">
    <TransitionChild
      as="template"
      enter="ease-out duration-300"
      enter-from="opacity-0"
      enter-to="opacity-100"
      leave="ease-in duration-200"
      leave-from="opacity-100"
      leave-to="opacity-0"
    >
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
    </TransitionChild>

    <div class="fixed inset-0 z-10 overflow-y-auto">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to="opacity-100 translate-y-0 sm:scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 translate-y-0 sm:scale-100"
          leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
            <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
              <button
                type="button"
                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none"
                @click="closeReceivingModal"
              >
                <span class="sr-only">Fechar</span>
                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
              </button>
            </div>
            
            <div>
              <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                <CheckCircleIcon class="h-6 w-6 text-green-600" aria-hidden="true" />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                  {{ isReceivingSingleItem ? 'Receber Item' : 'Receber Itens do Pedido' }}
                </DialogTitle>
                
                <div class="mt-4">
                  <div
                    v-if="order.supplier?.latest_assessment"
                    class="mb-4 rounded-xl border px-4 py-3 text-left"
                    :class="receivingSupplierPanelClass"
                  >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                      <div>
                        <p class="text-sm font-semibold">Recepção com contexto do fornecedor</p>
                        <p class="mt-1 text-xs opacity-80">{{ receivingSupplierMessage }}</p>
                      </div>
                      <div class="flex flex-wrap gap-2">
                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierStatusClass(order.supplier.latest_assessment.status)]">
                          {{ supplierStatusLabel(order.supplier.latest_assessment.status) }}
                        </span>
                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', supplierRiskClass(order.supplier.latest_assessment.risk_level)]">
                          {{ supplierRiskLabel(order.supplier.latest_assessment.risk_level) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div v-if="isReceivingSingleItem && receivingItem" class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium text-gray-900">{{ receivingItem.item?.name }}</p>
                    <p class="text-xs text-gray-500">
                      Código: {{ receivingItem.item?.internal_code }}
                    </p>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                      <div class="text-center">
                        <span class="block font-semibold text-gray-700">Pedido</span>
                        <span class="text-gray-600">{{ receivingItem.qty }}</span>
                      </div>
                      <div class="text-center">
                        <span class="block font-semibold text-gray-700">Recebido</span>
                        <span class="text-green-600">{{ receivingItem.received_qty || 0 }}</span>
                      </div>
                      <div class="text-center">
                        <span class="block font-semibold text-gray-700">Pendente</span>
                        <span class="text-yellow-600">{{ receivingItem.qty - (receivingItem.received_qty || 0) }}</span>
                      </div>
                    </div>
                  </div>

                  <div v-else class="mb-4">
                    <p class="text-sm text-gray-500 mb-3">
                      Itens pendentes para recebimento:
                    </p>
                    <div v-for="item in pendingItems" :key="item.id" class="mb-2 p-2 bg-gray-50 rounded">
                      <div class="flex justify-between items-center">
                        <div>
                          <p class="text-xs font-medium text-gray-900">{{ item.item?.name }}</p>
                          <p class="text-xs text-gray-500">
                            Pendente: {{ item.qty - (item.received_qty || 0) }} de {{ item.qty }}
                          </p>
                        </div>
                        <input
                          type="number"
                          :min="1"
                          :max="item.qty - (item.received_qty || 0)"
                          v-model.number="receivingQuantities[item.id]"
                          class="w-20 text-sm border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring-purple-500"
                          placeholder="Qtd"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="submitReceipt">
              <div class="mt-5 space-y-4">
                <!-- Single Item Quantity Input -->
                <div v-if="isReceivingSingleItem && receivingItem">
                  <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">
                    Quantidade a Receber
                  </label>
                  <input
                    type="number"
                    id="quantity"
                    v-model.number="receivingQuantity"
                    :min="1"
                    :max="receivingItem.qty - (receivingItem.received_qty || 0)"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    Máximo: {{ receivingItem.qty - (receivingItem.received_qty || 0) }} unidades
                  </p>
                </div>

                <!-- Date Input -->
                <div>
                  <label for="receiveDate" class="block text-sm font-medium text-gray-700 mb-1">
                    Data de Recebimento
                  </label>
                  <input
                    type="date"
                    id="receiveDate"
                    v-model="receiveDate"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                  />
                </div>

                <!-- Unit Price (for single item) -->
                <div v-if="isReceivingSingleItem && receivingItem">
                  <label for="unitPrice" class="block text-sm font-medium text-gray-700 mb-1">
                    Preço Unitário
                  </label>
                  <input
                    type="number"
                    id="unitPrice"
                    v-model.number="receivingUnitPrice"
                    :step="0.01"
                    :min="0"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    Preço original: {{ formatCurrency(receivingItem.unit_price || 0) }}
                  </p>
                </div>

                <!-- Reason -->
                <div>
                  <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo
                  </label>
                  <input
                    type="text"
                    id="reason"
                    v-model="receivingReason"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                    placeholder="Ex: Recebimento normal"
                  />
                </div>

                <!-- Notes -->
                <div>
                  <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                    Observações
                  </label>
                  <textarea
                    id="notes"
                    v-model="receivingNotes"
                    rows="2"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-purple-500"
                    placeholder="Notas adicionais..."
                  />
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4">
                  <div class="flex items-start gap-3">
                    <input
                      id="registerNonConformity"
                      v-model="registerNonConformity"
                      type="checkbox"
                      class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-700 focus:ring-amber-500"
                    />
                    <div class="flex-1">
                      <label for="registerNonConformity" class="block text-sm font-semibold text-amber-900">
                        Registar não conformidade de recepção
                      </label>
                      <p class="mt-1 text-xs text-amber-800">
                        Use esta opção quando houver desvio de qualidade, documentação incompleta, dano, divergência comercial ou qualquer outra ocorrência que exija rastreabilidade formal.
                      </p>
                    </div>
                  </div>

                  <div v-if="registerNonConformity" class="mt-4 grid gap-4">
                    <div>
                      <label for="ncTitle" class="block text-sm font-medium text-gray-700 mb-1">
                        Título da não conformidade
                      </label>
                      <input
                        id="ncTitle"
                        v-model="nonConformityTitle"
                        type="text"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-amber-500"
                        placeholder="Ex: Divergência na recepção do fornecedor"
                      />
                    </div>

                    <div>
                      <label for="ncSeverity" class="block text-sm font-medium text-gray-700 mb-1">
                        Severidade
                      </label>
                      <select
                        id="ncSeverity"
                        v-model="nonConformitySeverity"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-amber-500"
                      >
                        <option value="low">Baixa</option>
                        <option value="medium">Média</option>
                        <option value="high">Alta</option>
                        <option value="critical">Crítica</option>
                      </select>
                    </div>

                    <div>
                      <label for="ncDescription" class="block text-sm font-medium text-gray-700 mb-1">
                        Descrição do desvio
                      </label>
                      <textarea
                        id="ncDescription"
                        v-model="nonConformityDescription"
                        rows="3"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm placeholder-gray-400 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-amber-500"
                        placeholder="Descreva claramente o desvio encontrado na recepção."
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                <button
                  type="submit"
                  :disabled="isSubmitting"
                  class="inline-flex w-full justify-center rounded-md bg-purple-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-purple-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-900 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <template v-if="isSubmitting">
                    <ArrowPathIcon class="h-4 w-4 mr-2 animate-spin" />
                    Processando...
                  </template>
                  <template v-else>
                    Confirmar Recebimento
                  </template>
                </button>
                <button
                  type="button"
                  @click="closeReceivingModal"
                  :disabled="isSubmitting"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Cancelar
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </div>
  </Dialog>
</TransitionRoot>
  </div>

</template>

<script setup>
import { ref, computed, reactive, onMounted, onBeforeUnmount } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router } from '@inertiajs/vue3'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import {
  ShoppingCartIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  CheckCircleIcon,
  TruckIcon,
  PencilIcon,
  XCircleIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
  ClockIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  charts: {
    type: Object,
    default: () => ({})
  }
})

const isDarkMode = ref(false)
let themeObserver

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

// Modal state
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

const formatStatus = (status) => {
  const statusMap = {
    'PENDING': 'Pendente',
    'APPROVED': 'Aprovado',
    'ORDERED': 'Pedido',
    'PARTIALLY_RECEIVED': 'Recebido Parcialmente',
    'RECEIVED': 'Recebido',
    'CANCELLED': 'Cancelado'
  }
  return statusMap[status] || status
}

const supplierStatusLabel = (status) => {
  const map = {
    approved: 'Aprovado',
    conditional: 'Condicional',
    suspended: 'Suspenso',
    rejected: 'Rejeitado',
  }

  return map[status] || status || 'Sem avaliação'
}

const supplierRiskLabel = (risk) => {
  const map = {
    low: 'Risco baixo',
    medium: 'Risco médio',
    high: 'Risco elevado',
    critical: 'Risco crítico',
  }

  return map[risk] || risk || 'Sem classificação'
}

const supplierStatusClass = (status) => {
  const map = {
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    conditional: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    suspended: 'bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-200',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
  }

  return map[status] || 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-200'
}

const supplierRiskClass = (risk) => {
  const map = {
    low: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    medium: 'bg-sky-100 text-sky-800 dark:bg-sky-500/10 dark:text-sky-200',
    high: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    critical: 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
  }

  return map[risk] || 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-200'
}

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

const receivingSupplierPanelClass = computed(() => {
  const assessment = props.order?.supplier?.latest_assessment

  if (!assessment) {
    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100'
  }

  if (['rejected', 'suspended'].includes(assessment.status) || assessment.risk_level === 'critical') {
    return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100'
  }

  if (assessment.status === 'conditional' || assessment.risk_level === 'high') {
    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100'
  }

  return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100'
})

const receptionProgressChartSeries = computed(() => [
  {
    name: 'Quantidade',
    data: props.charts?.reception_progress?.series || []
  }
])

const receptionProgressTotal = computed(() =>
  (props.charts?.reception_progress?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
)

const itemStatusMixChartSeries = computed(() => props.charts?.item_status_mix?.series || [])

const itemStatusMixTotal = computed(() =>
  itemStatusMixChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0)
)

const governanceSummaryChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.governance_summary?.series || []
  }
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
      columnWidth: '48%'
    }
  },
  colors: ['#0f172a', '#2563eb', '#f59e0b'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.reception_progress?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    }
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false }
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
  colors: ['#f59e0b', '#fb923c', '#16a34a'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom',
    labels: { colors: chartTextColor.value },
  },
  stroke: {
    colors: [isDarkMode.value ? '#020617' : '#ffffff']
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
      columnWidth: '52%'
    }
  },
  colors: ['#0f766e', '#dc2626', '#7c3aed', '#475569'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.governance_summary?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    }
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false }
}))

// For bulk receiving
const receivingQuantities = reactive({})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: props.order.currency || 'AOA'
  }).format(amount)
}

const statusClass = computed(() => {
  const classMap = {
    'PENDING': 'bg-yellow-100 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20',
    'APPROVED': 'bg-blue-100 text-blue-800 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20',
    'ORDERED': 'bg-purple-100 text-purple-800 ring-purple-600/20 dark:bg-purple-500/10 dark:text-purple-200 dark:ring-purple-400/20',
    'PARTIALLY_RECEIVED': 'bg-orange-100 text-orange-800 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/20',
    'RECEIVED': 'bg-green-100 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20',
    'CANCELLED': 'bg-red-100 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-400/20'
  }
  return classMap[props.order.status] || 'bg-gray-100 text-gray-800 ring-gray-600/20 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-500/20'
})

const totalQuantity = computed(() => {
  if (!props.order.items) return 0
  return props.order.items.reduce((sum, item) => sum + parseInt(item.qty || 0), 0)
})

const receivedQuantity = computed(() => {
  if (!props.order.items) return 0
  return props.order.items.reduce((sum, item) => sum + parseInt(item.received_qty || 0), 0)
})

const completionRate = computed(() => {
  if (totalQuantity.value === 0) return 0
  return Math.round((receivedQuantity.value / totalQuantity.value) * 100)
})

const pendingItems = computed(() => {
  if (!props.order.items) return []
  return props.order.items.filter(item => {
    const received = item.received_qty || 0
    return received < item.qty
  })
})

const pendingItemsCount = computed(() => {
  if (!props.order.items) return 0
  return props.order.items.filter(item => !item.received_qty || item.received_qty === 0).length
})

const partiallyReceivedItemsCount = computed(() => {
  if (!props.order.items) return 0
  return props.order.items.filter(item => {
    const received = item.received_qty || 0
    return received > 0 && received < item.qty
  }).length
})

const fullyReceivedItemsCount = computed(() => {
  if (!props.order.items) return 0
  return props.order.items.filter(item => {
    const received = item.received_qty || 0
    return received >= item.qty
  }).length
})

const canEdit = computed(() => {
  return ['PENDING', 'APPROVED'].includes(props.order.status)
})

const canReceiveOrder = computed(() => {
  return ['ORDERED', 'PARTIALLY_RECEIVED'].includes(props.order.status) && pendingItems.value.length > 0
})

const canCancel = computed(() => {
  return ['PENDING', 'APPROVED', 'ORDERED'].includes(props.order.status)
})

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatItemStatus(status) {
  const statusMap = {
    'PENDING': 'Pendente',
    'ORDERED': 'Pedido',
    'PARTIALLY_RECEIVED': 'Recebido Parcialmente',
    'RECEIVED': 'Recebido',
    'CANCELLED': 'Cancelado',
    'REJECTED': 'Rejeitado',
    'APPROVED': 'Aprovado',
    'CANCELLED_BY_SUPPLIER': 'Cancelado Pelo Fornecedor',
    'CANCELLED_BY_USER': 'Cancelado Pelo Usuário',
  }
  return statusMap[status] || status
}

function getItemStatusClass(status) {
  const classMap = {
    'PENDING': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-200',
    'ORDERED': 'bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-200',
    'PARTIALLY_RECEIVED': 'bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-200',
    'RECEIVED': 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
    'CANCELLED': 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
  }
  return classMap[status] || 'bg-gray-100 text-gray-800 dark:bg-slate-800 dark:text-slate-200'
}

function canReceiveItem(item) {
  const received = item.received_qty || 0
  return received < item.qty && ['ORDERED', 'PARTIALLY_RECEIVED'].includes(item.status)
}

// function receiveItem(item) {
//   // This would open a modal to receive the specific item
//   router.post(route('vap-inventory.orders.receive', props.order.id), {
//     data: { item_id: item.id },
//     preserveScroll: true
//   })
// }

// function receiveOrder() {
//   // This would open a modal to receive all items
//   router.post(route('vap-inventory.orders.receive', props.order.id), {
//     preserveScroll: true
//   })
// }

function cancelOrder() {
  if (!confirm('Tem a certeza de que pretende cancelar este pedido?')) {
    return
  }

  router.post(route('vap-inventory.orders.cancel', props.order.id), {}, {
    preserveScroll: true,
    preserveState: true
  })
}

function printOrder() {
  window.print()
}

function exportOrder() {
  // Open PDF in new tab
  const url = route('vap-inventory.orders.export-pdf', props.order.id);
  window.open(url, '_blank');
}

function goBack() {
  router.visit(route('vap-inventory.orders.index'))
}

// Modal functions
function openReceivingModal(item = null) {
  isReceivingSingleItem.value = !!item
  receivingItem.value = item
  
  if (item) {
    receivingQuantity.value = Math.max(1, item.qty - (item.received_qty || 0))
    receivingUnitPrice.value = item.unit_price || 0
  } else {
    // Initialize quantities for all pending items
    pendingItems.value.forEach(item => {
      receivingQuantities[item.id] = item.qty - (item.received_qty || 0)
    })
  }
  
  isReceivingModalOpen.value = true
}

function closeReceivingModal() {
  if (isSubmitting.value) return
  
  isReceivingModalOpen.value = false
  setTimeout(() => {
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
    // Clear receiving quantities
    Object.keys(receivingQuantities).forEach(key => {
      delete receivingQuantities[key]
    })
  }, 300)
}

function receiveItem(item) {
  openReceivingModal(item)
}

function receiveOrder() {
  openReceivingModal()
}

// async function submitReceipt() {
//   if (isSubmitting.value) return
  
//   try {
//     isSubmitting.value = true
    
//     let itemsData = []
    
//     if (isReceivingSingleItem.value && receivingItem.value) {
//       // Single item receipt
//       if (receivingQuantity.value <= 0 || 
//           receivingQuantity.value > (receivingItem.value.qty - (receivingItem.value.received_qty || 0))) {
//         alert('Quantidade inválida')
//         isSubmitting.value = false
//         return
//       }
      
//       itemsData.push({
//         id: receivingItem.value.id,
//         received_qty: receivingQuantity.value,
//         unit_price: receivingUnitPrice.value || receivingItem.value.unit_price || 0
//       })
//     } else {
//       // Bulk receipt
//       itemsData = pendingItems.value
//         .filter(item => receivingQuantities[item.id] > 0)
//         .map(item => ({
//           id: item.id,
//           received_qty: receivingQuantities[item.id],
//           unit_price: item.unit_price || 0
//         }))
      
//       if (itemsData.length === 0) {
//         alert('Selecione quantidades para pelo menos um item')
//         isSubmitting.value = false
//         return
//       }
//     }
    
//     const receiptData = {
//       items: itemsData,
//       receive_date: receiveDate.value,
//       reason: receivingReason.value || 'Order Receipt',
//       notes: receivingNotes.value
//     }
    
//     await router.post(route('vap-inventory.orders.receive', props.order.id), receiptData, {
//       preserveScroll: true,
//       onSuccess: () => {
//         closeReceivingModal()
//       },
//       onError: (errors) => {
//         alert('Erro ao processar recebimento: ' + (errors.message || 'Erro desconhecido'))
//       }
//     })
//   } catch (error) {
//     console.error('Receipt error:', error)
//     alert('Erro ao processar recebimento')
//   } finally {
//     isSubmitting.value = false
//   }
// }

async function submitReceipt() {
  if (isSubmitting.value) return
  
  try {
    isSubmitting.value = true
    
    let itemsData = []
    
    if (isReceivingSingleItem.value && receivingItem.value) {
      // Single item receipt
      if (receivingQuantity.value <= 0 || 
          receivingQuantity.value > (receivingItem.value.qty - (receivingItem.value.received_qty || 0))) {
        alert('Quantidade inválida')
        isSubmitting.value = false
        return
      }
      
      itemsData.push({
        id: receivingItem.value.id,
        received_qty: receivingQuantity.value,
        unit_price: receivingUnitPrice.value || receivingItem.value.unit_price || 0
      })
    } else {
      // Bulk receipt
      itemsData = pendingItems.value
        .filter(item => receivingQuantities[item.id] > 0)
        .map(item => ({
          id: item.id,
          received_qty: receivingQuantities[item.id],
          unit_price: item.unit_price || 0
        }))
      
      if (itemsData.length === 0) {
        alert('Selecione quantidades para pelo menos um item')
        isSubmitting.value = false
        return
      }
    }

    if (registerNonConformity.value && !nonConformityDescription.value.trim()) {
      alert('Descreva o desvio antes de registar a não conformidade.')
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
      non_conformity_description: registerNonConformity.value ? nonConformityDescription.value : null
    }
    
    await router.post(route('vap-inventory.orders.receive', props.order.id), receiptData, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        closeReceivingModal()
        // Refresh the page to get updated data
        router.reload({ only: ['order'] })
      },
      onError: (errors) => {
        alert('Erro ao processar recebimento: ' + (errors.message || 'Erro desconhecido'))
      }
    })
  } catch (error) {
    console.error('Receipt error:', error)
    alert('Erro ao processar recebimento')
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

<style scoped>
.procurement-show-shell :deep(.bg-white.rounded-xl),
.procurement-show-shell :deep(.rounded-2xl.border.border-gray-200.bg-white),
.procurement-show-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(226 232 240);
  border-radius: 1.5rem;
  background: rgb(255 255 255);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
}

.procurement-show-shell :deep(.rounded-2xl.border.border-gray-200.bg-white) {
  background:
    radial-gradient(circle at top right, rgb(var(--color-primary-50, 239 246 255) / 0.72), transparent 34%),
    rgb(255 255 255);
}

.procurement-show-shell :deep(.bg-gray-50),
.procurement-show-shell :deep(.bg-slate-50) {
  border-color: rgb(226 232 240);
  background: rgb(248 250 252 / 0.84);
}

.procurement-show-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-900, 30 58 138));
}

.procurement-show-shell :deep(.bg-blue-100) {
  background-color: rgb(var(--color-primary-100, 219 234 254));
}

.procurement-show-shell :deep(.bg-blue-50) {
  background-color: rgb(var(--color-primary-50, 239 246 255));
}

.procurement-show-shell :deep(.border-gray-200),
.procurement-show-shell :deep(.border-gray-300),
.procurement-show-shell :deep(.border-slate-200),
.procurement-show-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(226 232 240);
}

.procurement-show-shell :deep(input),
.procurement-show-shell :deep(select),
.procurement-show-shell :deep(textarea) {
  border-color: rgb(203 213 225);
  border-radius: 0.875rem;
  background: rgb(255 255 255);
  color: rgb(15 23 42);
}

.procurement-show-shell :deep(input:focus),
.procurement-show-shell :deep(select:focus),
.procurement-show-shell :deep(textarea:focus) {
  border-color: rgb(var(--color-primary-500, 59 130 246));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500, 59 130 246) / 0.16);
}

.procurement-show-shell :deep(textarea::placeholder),
.procurement-show-shell :deep(input::placeholder) {
  color: rgb(148 163 184);
}

.procurement-show-shell :deep(.hover\:bg-gray-50:hover),
.procurement-show-shell :deep(tr:hover) {
  background: rgb(var(--color-primary-50, 239 246 255) / 0.58);
}

.procurement-show-shell :deep(.apexcharts-tooltip),
.procurement-show-shell :deep(.apexcharts-menu) {
  border-radius: 0.875rem;
  border-color: rgb(226 232 240);
  box-shadow: 0 20px 45px rgb(15 23 42 / 0.14);
}

:global(.dark) .procurement-show-shell :deep(.bg-white.rounded-xl),
:global(.dark) .procurement-show-shell :deep(.rounded-2xl.border.border-gray-200.bg-white),
:global(.dark) .procurement-show-shell :deep(.rounded-xl.border.border-gray-200) {
  border-color: rgb(30 41 59);
  background:
    radial-gradient(circle at top right, rgb(var(--color-primary-500, 59 130 246) / 0.12), transparent 32%),
    rgb(2 6 23);
}

:global(.dark) .procurement-show-shell :deep(.bg-white),
:global(.dark) .procurement-show-shell :deep(tbody.bg-white) {
  background: rgb(2 6 23);
}

:global(.dark) .procurement-show-shell :deep(.bg-gray-50),
:global(.dark) .procurement-show-shell :deep(.bg-slate-50),
:global(.dark) .procurement-show-shell :deep(.hover\:bg-gray-50:hover),
:global(.dark) .procurement-show-shell :deep(tr:hover) {
  border-color: rgb(51 65 85);
  background: rgb(15 23 42 / 0.72);
}

:global(.dark) .procurement-show-shell :deep(.bg-gray-100),
:global(.dark) .procurement-show-shell :deep(.bg-gray-200) {
  background-color: rgb(30 41 59);
}

:global(.dark) .procurement-show-shell :deep(.text-gray-900),
:global(.dark) .procurement-show-shell :deep(.text-gray-800),
:global(.dark) .procurement-show-shell :deep(.text-gray-700),
:global(.dark) .procurement-show-shell :deep(.text-slate-900),
:global(.dark) .procurement-show-shell :deep(.text-slate-700) {
  color: rgb(226 232 240);
}

:global(.dark) .procurement-show-shell :deep(.text-gray-600),
:global(.dark) .procurement-show-shell :deep(.text-gray-500),
:global(.dark) .procurement-show-shell :deep(.text-slate-600),
:global(.dark) .procurement-show-shell :deep(.text-slate-500) {
  color: rgb(148 163 184);
}

:global(.dark) .procurement-show-shell :deep(.border-gray-200),
:global(.dark) .procurement-show-shell :deep(.border-gray-300),
:global(.dark) .procurement-show-shell :deep(.border-slate-200),
:global(.dark) .procurement-show-shell :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(30 41 59);
}

:global(.dark) .procurement-show-shell :deep(.text-blue-900) {
  color: rgb(var(--color-primary-200, 191 219 254));
}

:global(.dark) .procurement-show-shell :deep(.bg-blue-100),
:global(.dark) .procurement-show-shell :deep(.bg-blue-50) {
  background-color: rgb(var(--color-primary-500, 59 130 246) / 0.1);
}

:global(.dark) .procurement-show-shell :deep(input),
:global(.dark) .procurement-show-shell :deep(select),
:global(.dark) .procurement-show-shell :deep(textarea) {
  border-color: rgb(51 65 85);
  background: rgb(2 6 23 / 0.78);
  color: rgb(241 245 249);
}

:global(.dark) .procurement-show-shell :deep(input[type='date']) {
  color-scheme: dark;
}

:global(.dark) .procurement-show-shell :deep(textarea::placeholder),
:global(.dark) .procurement-show-shell :deep(input::placeholder) {
  color: rgb(100 116 139);
}

:global(.dark) .procurement-show-shell :deep(.bg-amber-50\/80),
:global(.dark) .procurement-show-shell :deep(.bg-amber-50) {
  border-color: rgb(245 158 11 / 0.32);
  background-color: rgb(245 158 11 / 0.1);
}

:global(.dark) .procurement-show-shell :deep(.text-amber-900),
:global(.dark) .procurement-show-shell :deep(.text-amber-800),
:global(.dark) .procurement-show-shell :deep(.text-amber-700) {
  color: rgb(253 230 138);
}

:global(.dark) .procurement-show-shell :deep(.text-green-600) {
  color: rgb(110 231 183);
}

:global(.dark) .procurement-show-shell :deep(.text-yellow-600),
:global(.dark) .procurement-show-shell :deep(.text-orange-600) {
  color: rgb(252 211 77);
}

:global(.dark) .procurement-show-shell :deep(.text-red-600) {
  color: rgb(252 165 165);
}

:global(.dark) .procurement-show-shell :deep(.fixed.inset-0.bg-gray-500) {
  background: rgb(2 6 23 / 0.78);
  backdrop-filter: blur(10px);
}

:global(.dark) .procurement-show-shell :deep(.relative.transform.overflow-hidden.rounded-lg.bg-white) {
  border: 1px solid rgb(30 41 59);
  background:
    radial-gradient(circle at top left, rgb(var(--color-primary-500, 59 130 246) / 0.14), transparent 34%),
    rgb(2 6 23);
  color: rgb(226 232 240);
}

:global(.dark) .procurement-show-shell :deep(.rounded-md.bg-white) {
  background: rgb(15 23 42);
  color: rgb(203 213 225);
}
</style>
