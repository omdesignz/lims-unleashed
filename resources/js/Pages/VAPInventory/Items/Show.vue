<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <CubeIcon class="h-7 w-7 text-blue-900" />
            {{ item.name }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ item.description || 'Sem descrição disponível' }}
            <span v-if="item.code" class="font-semibold text-blue-900 ml-2">
              ({{ item.code }})
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-inventory.items.edit', item.id)"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            <PencilSquareIcon class="h-4 w-4" />
            Modificar Item
          </Link>
          <Link
            :href="route('vap-inventory.items.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar para Itens
          </Link>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Distribuição de stock</h2>
            <p class="mt-1 text-sm text-gray-500">
              Leitura imediata do saldo por armazém antes de ajustar ou transferir.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Unidades monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stockDistributionTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="stockDistributionChartOptions" :series="stockDistributionChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Mix operacional</h2>
              <p class="mt-1 text-sm text-gray-500">Transações, pedidos, transferências e consumo recente.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ activityMixTotal }} registos
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="activityMixChartOptions" :series="activityMixChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pulso de conformidade</h2>
              <p class="mt-1 text-sm text-gray-500">Sinal rápido sobre caducidade, criticidade de stock e prontidão técnica.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="compliancePulseChartOptions" :series="compliancePulseChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN -->
      <div class="lg:col-span-2 space-y-6">
        <!-- ITEM DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              Detalhes do Item
            </h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Categoria</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.category?.name || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Tipo</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.type?.name || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Unidade</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.unit?.code || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Estado</label>
                  <div class="mt-1">
                    <span :class="getStatusClasses(item.status)">
                      {{ item.status?.name || 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Fornecedor</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.supplier?.name || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Quantidade de Reabastecimento</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.reorder_qty }} {{ item.unit?.code || 'units' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Documentação de Segurança</label>
                  <div class="mt-1">
                    <span :class="item.has_safety_documentation ? 'text-green-900' : 'text-gray-900'">
                      {{ item.has_safety_documentation ? 'Disponível' : 'Não Disponível' }}
                    </span>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Refrigerado</label>
                  <div class="mt-1">
                    <span :class="item.refrigerated ? 'text-blue-900' : 'text-gray-900'">
                      {{ item.refrigerated ? 'Sim' : 'Não' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- IDENTIFICATION -->
            <div class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Identificação</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.barcode || 'N/A' }}</div>
                </div>
                <div v-if="isEquipment">
                  <label class="block text-sm font-medium text-gray-700">Número de Série</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.serial_number || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Código Interno</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.internal_code || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Marca</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.brand || 'N/A' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Modelo</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.model || 'N/A' }}</div>
                </div>
                
                <div v-if="isReagent">
                  <label class="block text-sm font-medium text-gray-700">Lote</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.lot || 'N/A' }}</div>
                </div>
              </div>
            </div>

            <!-- ITEM PRICES -->
            <div class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Custos de Compra</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Preço Padrão</label>
                  <div class="mt-1">
                    {{ item.standard_cost }}
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Último Preço de Compra</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.last_purchase_price }}</div>
                </div>
                <!-- <div>
                  <label class="block text-sm font-medium text-gray-700">Dias para Caducidade</label>
                  <div class="mt-1">
                    <span :class="getDaysColor(daysToExpiry)">
                      {{ daysToExpiry.toFixed(0) || 'N/A' }}
                    </span>
                  </div>
                </div> -->
              </div>
            </div>

            <!-- TECHNICAL SPECIFICATIONS -->
            <div v-if="hasTechnicalSpecs" class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Especificações Técnicas</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-if="item.resolution">
                  <label class="block text-sm font-medium text-gray-700">Resolução</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.resolution }}</div>
                </div>
                <div v-if="item.precision">
                  <label class="block text-sm font-medium text-gray-700">Precisão</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.precision }}</div>
                </div>
                <div v-if="item.range">
                  <label class="block text-sm font-medium text-gray-700">Alcance / Gama</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.range }}</div>
                </div>
                <div v-if="item.firmware">
                  <label class="block text-sm font-medium text-gray-700">Firmware</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.firmware }}</div>
                </div>
                <div v-if="item.software">
                  <label class="block text-sm font-medium text-gray-700">Software</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.software }}</div>
                </div>
                <div v-if="item.metrological_uncertainty_value">
                  <label class="block text-sm font-medium text-gray-700">Incerteza Metrológica</label>
                  <div class="mt-1 text-sm text-gray-900">
                    {{ item.metrological_uncertainty_value }} {{ item.metrological_uncertainty_unit || '' }}
                  </div>
                </div>
                <div v-if="item.metrological_traceability_reference">
                  <label class="block text-sm font-medium text-gray-700">Rastreabilidade Metrológica</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.metrological_traceability_reference }}</div>
                </div>
              </div>
            </div>

            <!-- REAGENT SPECIFIC -->
            <div v-if="item.is_reagent" class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Informação do Reagente</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Data de Validade</label>
                  <div class="mt-1">
                    <span :class="getExpiryDateColor(item)">
                      {{ formatDate(item.reagent_expiry_date) || 'N/A' }}
                    </span>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Data de Abertura</label>
                  <div class="mt-1 text-sm text-gray-900">{{ formatDate(item.reagent_open_date) || 'Não Aberto' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Dias para Caducidade</label>
                  <div class="mt-1">
                    <span :class="getDaysColor(daysToExpiry)">
                      {{ daysToExpiry.toFixed(0) || 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- CALIBRATION INFO -->
            <div v-if="item.next_calibration_date" class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Informação de Calibração</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Última Calibração</label>
                  <div class="mt-1 text-sm text-gray-900">{{ formatDate(item.last_calibration_date) || 'Nunca' }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Próxima Calibração</label>
                  <div class="mt-1">
                    <span :class="getCalibrationDateColor(item)">
                      {{ formatDate(item.next_calibration_date) }}
                    </span>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Estado de Calibração</label>
                  <div class="mt-1">
                    <span :class="getCalibrationStatusClasses(item)">
                      {{ getCalibrationStatusText(item) }}
                    </span>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Estado Metrológico</label>
                  <div class="mt-1">
                    <span :class="getMetrologyStatusClasses(item.metrology_status)">
                      {{ getMetrologyStatusText(item.metrology_status) }}
                    </span>
                  </div>
                </div>
                <div v-if="item.metrology_review_due_at">
                  <label class="block text-sm font-medium text-gray-700">Próxima Revisão Metrológica</label>
                  <div class="mt-1 text-sm text-gray-900">{{ formatDate(item.metrology_review_due_at) }}</div>
                </div>
                <div v-if="item.metrology_notes" class="md:col-span-2 lg:col-span-3">
                  <label class="block text-sm font-medium text-gray-700">Notas Metrológicas</label>
                  <div class="mt-1 text-sm text-gray-900">{{ item.metrology_notes }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STOCK ACROSS WAREHOUSES -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <BuildingLibraryIcon class="h-5 w-5" />
              Estoque em Armazéns
            </h2>
          </div>
          <div class="p-6">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Armazém
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Estoque Disponível
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nível Mínimo de Estoque
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Ponto de Reabastecimento
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Estado de Estoque
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Acções
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="inv in inventory"
                    :key="inv?.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900">{{ inv?.warehouse?.name }}</div>
                      <div class="text-sm text-gray-500">{{ inv?.warehouse?.location?.name || 'No location' }}</div>
                      <div v-if="inv?.warehouse?.is_refrigerated" class="mt-1">
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                          <SunIcon class="mr-1 h-3 w-3" />
                          Refrigerado
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-2xl font-bold text-blue-900">{{ inv.qty_available }}</div>
                      <div class="text-xs text-gray-500">{{ item.unit?.code || 'unidades' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ inv.min_stock_level }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ inv.reorder_point }}</td>
                    <td class="px-6 py-4">
                      <span :class="getStockStatusClasses(inv)">
                        {{ inv.stock_status_label }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <button
                          @click="adjustStock(inv)"
                          class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900 hover:bg-blue-100"
                        >
                          <ArrowsUpDownIcon class="h-4 w-4 mr-1" />
                          Ajustar
                        </button>
                        <button
                          @click="transferStock(inv)"
                          class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-sm font-medium text-green-900 hover:bg-green-100"
                        >
                          <ArrowsRightLeftIcon class="h-4 w-4 mr-1" />
                        Transferir
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="inventory.length === 0" class="text-center py-8">
              <BuildingLibraryIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">Não há Estoque Disponível</h3>
              <p class="mt-2 text-sm text-gray-500">Este item não está disponível em nenhum armazém</p>
            </div>
          </div>
        </div>

        <!-- RECENT TRANSACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ClockIcon class="h-5 w-5" />
              Transações Recentes
            </h2>
          </div>
          <div class="p-6">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Data
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tipo
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Quantidade
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Armazém
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Usuário
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Motivo
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="transaction in recentTransactions"
                    :key="transaction.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ formatDateTime(transaction.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getTransactionTypeClasses(transaction)">
                        {{ transaction.type?.name }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="transaction.is_addition ? 'text-green-900' : 'text-red-900'">
                        {{ transaction.is_addition ? '+' : '-' }}{{ transaction.qty }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ transaction.warehouse?.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ transaction.user?.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                      {{ transaction.reason }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="recentTransactions.length === 0" class="text-center py-8">
              <ClockIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">Nenhuma Transação Recente</h3>
              <p class="mt-2 text-sm text-gray-500">Nenhuma transação registrada para este item ainda</p>
            </div>
          </div>
        </div>

        <!-- LIST DOCUMENTS -->

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <DocumentTextIcon class="h-5 w-5 text-blue-900" />
                Documentos
              </h2>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="documents.length === 0" class="p-12 text-center">
            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              Nenhum documento adicionado
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              Adicione um documento para o item
            </p>
          </div>

          <!-- DOCUMENTS GRID -->
          <div v-else class="space-y-4">
            <div
              v-for="(document, index) in documents"
              :key="document.name"
              class="group relative bg-white rounded-lg border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm m-4"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- Document Header -->
              <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-start">
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ document.name }}
                    </h3>
                    <p class="text-xs text-gray-500">
                      {{ document.extension || document.name.split('.').pop() }}
                    </p>
                  </div>
                  
                  <!-- Download File -->
                    <button
                      @click="downloadAttachment(document)"
                      type="button"
                      class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-2 rounded-full hover:bg-red-50"
                      :title="'Download'"
                    >
                      <CloudArrowDownIcon class="h-5 w-5" />
                    </button>
                  <button
                    @click="deleteAttachment(item.id, document.id, index)"
                    type="button"
                    class="text-gray-400 hover:text-red-600 transition-colors duration-200 p-2 rounded-full hover:bg-red-50"
                    :title="'Remover documento'"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Document Content -->
              <div class="p-4">
                <div class="space-y-2">
                 
                  <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <DocumentTextIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                    <div>
                      <div class="text-sm font-semibold text-gray-900">
                        {{ document.extension || document.name.split('.').pop() }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ readableFileSize(document.size) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6">
        <!-- QUICK STATS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            Estatísticas Rápidas
          </h3>
          <div class="space-y-4">
            <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-blue-100 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-600">Estoque Total</p>
                  <p class="text-2xl font-bold text-blue-900">{{ totalStock }}</p>
                </div>
                <CubeIcon class="h-8 w-8 text-blue-900/20" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-green-100 p-4">
                <p class="text-sm font-medium text-gray-600">Armazéns</p>
                <p class="text-xl font-bold text-green-900">{{ inventory.length }}</p>
              </div>
              <div class="bg-gradient-to-r from-yellow-50 to-white rounded-lg border border-yellow-100 p-4">
                <p class="text-sm font-medium text-gray-600">Usos Recentes</p>
                <p class="text-xl font-bold text-yellow-900">{{ recentTransactions.length }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Estado</span>
                <span :class="getStatusClasses(item.status)">
                  {{ item.status?.name || 'N/A' }}
                </span>
              </div>
              <div v-if="item.is_reagent" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Estado de Validade</span>
                <span :class="getExpiryStatusClasses(item)">
                  {{ getExpiryStatusText(item) }}
                </span>
              </div>
              <div v-if="item.next_calibration_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Calibração</span>
                <span :class="getCalibrationStatusClasses(item)">
                  {{ getCalibrationStatusText(item) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções Rápidas
          </h3>
          <div class="space-y-3">
            <button
              @click="adjustStockModal = true"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-blue-100 p-2">
                  <ArrowsUpDownIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Ajustar Estoque</div>
                  <div class="text-sm text-gray-500">Adicione ou remova estoque</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </button>

            <button
              @click="transferStockModal = true"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-green-100 p-2">
                  <ArrowsRightLeftIcon class="h-5 w-5 text-green-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Transferir Estoque</div>
                  <div class="text-sm text-gray-500">Mover entre armazéns</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </button>

            <button
              v-if="item.is_reagent"
              @click="consumeReagentModal = true"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-red-100 p-2">
                  <BeakerIcon class="h-5 w-5 text-red-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Registrar Consumo</div>
                  <div class="text-sm text-gray-500">Registrar uso de reagente</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </button>

            <button
              v-if="item.next_calibration_date"
              @click="recordCalibrationModal = true"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-purple-100 p-2">
                  <WrenchScrewdriverIcon class="h-5 w-5 text-purple-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Registrar Calibração</div>
                  <div class="text-sm text-gray-500">Atualizar status de calibração</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </button>

            <Link
              :href="route('vap-inventory.orders.create', { item_id: item.id })"
              class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="rounded-lg bg-yellow-100 p-2">
                  <ShoppingCartIcon class="h-5 w-5 text-yellow-900" />
                </div>
                <div>
                  <div class="font-medium text-gray-900">Criar Pedido</div>
                  <div class="text-sm text-gray-500">Pedir em mais de um fornecedor</div>
                </div>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </Link>
          </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Atividade Recente
          </h3>
          <div class="space-y-4">
            <div
              v-for="activity in recentActivity"
              :key="activity.id"
              class="flex items-start gap-3"
            >
              <div :class="[
                'flex h-8 w-8 items-center justify-center rounded-full',
                getActivityColor(activity.type)
              ]">
                <component
                  :is="getActivityIcon(activity.type)"
                  class="h-4 w-4 text-white"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">
                  {{ activity.description }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  {{ formatTimeAgo(activity.timestamp) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALS -->
    <!-- Adjust Stock Modal -->
    <AdjustStockModal
      :show="adjustStockModal"
      :item="item"
      :inventory="inventory"
      @close="adjustStockModal = false"
      @success="handleStockAdjusted"
    />

    <!-- Transfer Stock Modal -->
    <TransferStockModal
      :show="transferStockModal"
      :item="item"
      :inventory="inventory"
      @close="transferStockModal = false"
      @success="handleTransferCreated"
    />

    <!-- Consume Reagent Modal -->
    <ConsumeReagentModal
      v-if="item.is_reagent" 
      :show="consumeReagentModal"
      :item="item"
      :inventory="inventory"
      @close="consumeReagentModal = false"
      @success="handleConsumptionRecorded"
    />

    <!-- Record Calibration Modal -->
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
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import {
  CubeIcon,
  PencilSquareIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  BuildingLibraryIcon,
  ClockIcon,
  ChartBarIcon,
  ArrowsUpDownIcon,
  ArrowsRightLeftIcon,
  BeakerIcon,
  WrenchScrewdriverIcon,
  ShoppingCartIcon,
  ChevronRightIcon,
  SunIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  DocumentTextIcon,
  TrashIcon,
  CloudArrowDownIcon,
} from '@heroicons/vue/24/outline'
import AdjustStockModal from '@/Components/vap-inventory/AdjustStockModal.vue'
import TransferStockModal from '@/Components/vap-inventory/TransferStockModal.vue'
import ConsumeReagentModal from '@/Components/vap-inventory/ConsumeReagentModal.vue'
import RecordCalibrationModal from '@/Components/vap-inventory/RecordCalibrationModal.vue'

const props = defineProps({
  item: Object,
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
  documents: Array,
  charts: {
    type: Object,
    default: () => ({})
  },
})

const isReagent = computed(() => {
  return (props.item.category?.name).toLowerCase().includes('reagente');
})

const isEquipment = computed(() => {
  return (props.item.category?.name).toLowerCase().includes('equipamento');
})

const adjustStockModal = ref(false)
const transferStockModal = ref(false)
const consumeReagentModal = ref(false)
const recordCalibrationModal = ref(false)

const hasTechnicalSpecs = computed(() => {
  return props.item.resolution || props.item.precision || props.item.range ||
         props.item.firmware || props.item.software ||
         props.item.metrological_uncertainty_value || props.item.metrological_traceability_reference
})

const stockDistributionChartSeries = computed(() => [
  {
    name: 'Stock',
    data: props.charts?.stock_distribution?.series || []
  }
])

const stockDistributionTotal = computed(() =>
  (props.charts?.stock_distribution?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
)

const activityMixChartSeries = computed(() => props.charts?.activity_mix?.series || [])

const activityMixTotal = computed(() =>
  activityMixChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0)
)

const compliancePulseChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.compliance_pulse?.series || []
  }
])

const stockDistributionChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '48%'
    }
  },
  colors: ['#0f172a', '#2563eb', '#0f766e', '#f59e0b', '#7c3aed'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.stock_distribution?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const activityMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  labels: props.charts?.activity_mix?.labels || [],
  colors: ['#2563eb', '#f59e0b', '#14b8a6', '#dc2626'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom'
  },
  stroke: {
    colors: ['#ffffff']
  }
}))

const compliancePulseChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '52%'
    }
  },
  colors: ['#0f766e', '#dc2626', '#f59e0b', '#2563eb'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.compliance_pulse?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const recentActivity = computed(() => {
  const activities = []
  
  // Add recent transactions
  props.recentTransactions.slice(0, 5).forEach(transaction => {
    activities.push({
      id: transaction.id,
      type: 'transaction',
      description: `${transaction.type?.name}: ${transaction.qty} unidades`,
      timestamp: transaction.created_at,
    })
  })
  
  // Add recent consumptions
  props.recentConsumptions.slice(0, 3).forEach(consumption => {
    activities.push({
      id: consumption.id,
      type: 'consumption',
      description: `Consumiu: ${consumption.quantity_used} unidades`,
      timestamp: consumption.used_at,
    })
  })
  
  // Add recent transfers
  props.recentTransfers.slice(0, 3).forEach(transfer => {
    activities.push({
      id: transfer.id,
      type: 'transfer',
      description: `Transferiu: ${transfer.qty} unidades para ${transfer.destination?.name}`,
      timestamp: transfer.created_at,
    })
  })
  
  // Sort by timestamp
  return activities.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp)).slice(0, 8)
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const deleteForm = useForm({
    model_id: null,
    id: null,
});

const formatTimeAgo = (timestamp) => {
  const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000)
  
  if (seconds < 60) return 'agora'
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m atrás`
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h atrás`
  return `${Math.floor(seconds / 86400)}d atrás`
}

const getStatusClasses = (status) => {
  if (!status) return 'inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800'
  
  const statusName = status.name.toLowerCase()
  if (statusName.includes('active')) {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  } else if (statusName.includes('inactive') || statusName.includes('out')) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (statusName.includes('maintenance') || statusName.includes('calibration')) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800'
  }
}

const getExpiryDateColor = (item) => {
  if (item.is_expired) return 'text-red-900'
  if (item.days_to_expiry <= 30) return 'text-orange-900'
  if (item.days_to_expiry <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const getDaysColor = (days) => {
  if (days <= 0) return 'text-red-900'
  if (days <= 30) return 'text-orange-900'
  if (days <= 60) return 'text-yellow-900'
  return 'text-green-900'
}

const getExpiryStatusClasses = (item) => {
  if (item.is_expired) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (item.days_to_expiry <= 30) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (item.days_to_expiry <= 60) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getExpiryStatusText = (item) => {
  if (item.is_expired) return 'Expirado'
  if (item.days_to_expiry <= 30) return 'Expirando em Breve'
  if (item.days_to_expiry <= 60) return 'Preste a Expirar'
  return 'Bom'
}

const getCalibrationDateColor = (item) => {
  if (item.needs_calibration) return 'text-red-900'
  if (item.days_to_calibration <= 30) return 'text-orange-900'
  if (item.days_to_calibration <= 90) return 'text-yellow-900'
  return 'text-green-900'
}

const getCalibrationStatusClasses = (item) => {
  if (item.needs_calibration) {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (item.days_to_calibration <= 30) {
    return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  } else if (item.days_to_calibration <= 90) {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getCalibrationStatusText = (item) => {
  if (item.needs_calibration) return 'Atrasado'
  if (item.days_to_calibration <= 30) return 'A vencer em breve'
  if (item.days_to_calibration <= 90) return 'Em breve'
  return 'Agendado'
}

const getMetrologyStatusClasses = (status) => {
  if (status === 'hold') return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  if (status === 'incomplete') return 'inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-800'
  if (status === 'review_due') return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  if (status === 'validated') return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  return 'inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800'
}

const getMetrologyStatusText = (status) => {
  if (status === 'hold') return 'Bloqueado'
  if (status === 'incomplete') return 'Incompleto'
  if (status === 'review_due') return 'Revisão em breve'
  if (status === 'validated') return 'Validado'
  return 'Não aplicável'
}

const getStockStatusClasses = (inventory) => {
  const status = inventory.stock_status
  if (status === 'out_of_stock') {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (status === 'critical_stock') {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (status === 'low_stock') {
    return 'inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800'
  } else {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  }
}

const getTransactionTypeClasses = (transaction) => {
  const type = transaction.type?.code
  if (type === 'stock_in' || type === 'stock_adjustment_add') {
    return 'inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800'
  } else if (type === 'stock_out' || type === 'consumption') {
    return 'inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800'
  } else if (type === 'stock_transfer') {
    return 'inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800'
  } else {
    return 'inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800'
  }
}

const getActivityColor = (type) => {
  const colors = {
    transaction: 'bg-blue-900',
    consumption: 'bg-red-900',
    transfer: 'bg-green-900',
    calibration: 'bg-purple-900',
  }
  return colors[type] || 'bg-gray-900'
}

const getActivityIcon = (type) => {
  const icons = {
    transaction: ArrowsUpDownIcon,
    consumption: BeakerIcon,
    transfer: ArrowsRightLeftIcon,
    calibration: WrenchScrewdriverIcon,
  }
  return icons[type] || ClockIcon
}

const adjustStock = (inventory) => {
  // Implementation for adjusting stock for specific inventory
  adjustStockModal.value = true
}

const transferStock = (inventory) => {
  // Implementation for transferring stock from specific inventory
  transferStockModal.value = true
}

const handleStockAdjusted = () => {
  adjustStockModal.value = false
  // Reload data
  window.location.reload()
}

const handleTransferCreated = () => {
  transferStockModal.value = false
  // Reload data
  window.location.reload()
}

const handleConsumptionRecorded = () => {
  consumeReagentModal.value = false
  // Reload data
  window.location.reload()
}

const handleCalibrationRecorded = () => {
  recordCalibrationModal.value = false
  // Reload data
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

function deleteAttachment(model_id, id, index) {
    deleteForm.model_id = model_id;
    deleteForm.id = id;

    deleteForm.delete(route('vap-inventory.items.attachments.delete', {model_id: model_id, id: id}), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            
        },
    });
}

function downloadAttachment(file) {
    window.open(route('vap-inventory.items.attachments.download-single', { model_id: file.id }), '_blank');
}

</script>
