<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Item controlado</span>
            <span :class="getStatusClasses(item.status)">{{ item.status?.name || 'Estado por definir' }}</span>
          </div>
          <h1 class="ds-heading mt-3 flex items-center gap-3 text-2xl">
            <CubeIcon class="h-7 w-7 text-primary-700 dark:text-primary-300" />
            {{ item.name }}
          </h1>
          <p class="ds-copy mt-2 text-sm">
            {{ item.description || 'Sem descrição disponível.' }}
          </p>
          <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 font-mono text-xs font-semibold text-[color:var(--ds-text-soft)]">
            <span>Código: {{ item.code || 'N/A' }}</span>
            <span>Interno: {{ item.internal_code || 'N/A' }}</span>
            <span v-if="item.barcode">Barcode: {{ item.barcode }}</span>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Link :href="route('vap-inventory.items.index')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            Itens
          </Link>
          <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-button ds-button-primary">
            <PencilSquareIcon class="h-4 w-4" />
            Modificar
          </Link>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Stock total</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ totalStock || 0 }}</dd>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ item.unit?.code || 'unidades' }}</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Armazéns</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ inventory.length }}</dd>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">localizações ativas</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Reabastecimento</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ item.reorder_qty || 0 }}</dd>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ item.unit?.code || 'unidades' }}</p>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Atividade recente</dt>
          <dd class="mt-2 text-2xl font-bold text-[color:var(--ds-text)]">{{ recentTransactions.length }}</dd>
          <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">transações</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]">
      <article class="ds-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="ds-kicker">Disponibilidade</p>
            <h2 class="ds-heading mt-2 text-base">Distribuição de stock</h2>
            <p class="ds-copy mt-1 text-xs">Saldo disponível por armazém.</p>
          </div>
          <span class="ds-chip">{{ stockDistributionTotal }} monitorizadas</span>
        </div>
        <apexchart class="mt-3" type="bar" height="260" :options="stockDistributionChartOptions" :series="stockDistributionChartSeries" />
      </article>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
        <article class="ds-card p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="ds-kicker">Movimento</p>
              <h2 class="ds-heading mt-2 text-base">Mix operacional</h2>
            </div>
            <span class="ds-chip">{{ activityMixTotal }} registos</span>
          </div>
          <apexchart class="mt-2" type="donut" height="205" :options="activityMixChartOptions" :series="activityMixChartSeries" />
        </article>

        <article class="ds-card p-5">
          <div>
            <p class="ds-kicker">Conformidade</p>
            <h2 class="ds-heading mt-2 text-base">Pulso técnico</h2>
            <p class="ds-copy mt-1 text-xs">Caducidade, criticidade e prontidão.</p>
          </div>
          <apexchart class="mt-2" type="bar" height="185" :options="compliancePulseChartOptions" :series="compliancePulseChartSeries" />
        </article>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
      <div class="space-y-4">
        <section class="ds-panel overflow-hidden">
          <div class="flex items-center gap-2 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
            <InformationCircleIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Dossier do item</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Classificação, identificação e condições técnicas.</p>
            </div>
          </div>

          <div class="divide-y divide-[color:var(--ds-border)]">
            <section class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Classificação</h3>
                <p class="ds-copy mt-1 text-xs">Propriedade, fornecimento e operação corrente.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in overviewFields" :key="field.label">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
                </div>
              </dl>
            </section>

            <section class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Identificação</h3>
                <p class="ds-copy mt-1 text-xs">Códigos, fabricante e rastreabilidade física.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in identificationFields" :key="field.label">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                  <dd class="mt-1 break-words text-sm font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
                </div>
              </dl>
            </section>

            <section class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Custos e controlo</h3>
                <p class="ds-copy mt-1 text-xs">Valores de compra e requisitos de conservação.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Custo padrão</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ item.standard_cost || 'N/A' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Último preço</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ item.last_purchase_price || 'N/A' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Documentação de segurança</dt>
                  <dd class="mt-1">
                    <span :class="['ds-chip', item.has_safety_documentation ? 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-zinc-300 bg-zinc-50 text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200']">
                      {{ item.has_safety_documentation ? 'Disponível' : 'Não disponível' }}
                    </span>
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Refrigeração</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ item.refrigerated ? 'Obrigatória' : 'Não aplicável' }}</dd>
                </div>
              </dl>
            </section>

            <section v-if="hasTechnicalSpecs" class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Especificações técnicas</h3>
                <p class="ds-copy mt-1 text-xs">Capacidade, software e rastreabilidade metrológica.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in technicalSpecFields" :key="field.label">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                  <dd class="mt-1 break-words text-sm font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
                </div>
              </dl>
            </section>

            <section v-if="isReagent" class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Reagente e validade</h3>
                <p class="ds-copy mt-1 text-xs">Lote, abertura e janela de utilização.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Data de validade</dt>
                  <dd class="mt-1 text-sm font-bold" :class="getExpiryDateColor(item)">{{ formatDate(item.reagent_expiry_date) || 'N/A' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Data de abertura</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatDate(item.reagent_open_date) || 'Não aberto' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Dias para caducidade</dt>
                  <dd class="mt-1 text-sm font-bold" :class="getDaysColor(daysToExpiry)">{{ Number(daysToExpiry || 0).toFixed(0) }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Estado de validade</dt>
                  <dd class="mt-1"><span :class="getExpiryStatusClasses(item)">{{ getExpiryStatusText(item) }}</span></dd>
                </div>
              </dl>
            </section>

            <section v-if="item.next_calibration_date" class="grid gap-5 p-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
              <div>
                <h3 class="ds-heading text-sm">Calibração e metrologia</h3>
                <p class="ds-copy mt-1 text-xs">Prontidão técnica e próxima revisão.</p>
              </div>
              <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Última calibração</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatDate(item.last_calibration_date) || 'Nunca' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Próxima calibração</dt>
                  <dd class="mt-1 text-sm font-bold" :class="getCalibrationDateColor(item)">{{ formatDate(item.next_calibration_date) }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Estado de calibração</dt>
                  <dd class="mt-1"><span :class="getCalibrationStatusClasses(item)">{{ getCalibrationStatusText(item) }}</span></dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Estado metrológico</dt>
                  <dd class="mt-1"><span :class="getMetrologyStatusClasses(item.metrology_status)">{{ getMetrologyStatusText(item.metrology_status) }}</span></dd>
                </div>
                <div v-if="item.metrology_review_due_at">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Próxima revisão</dt>
                  <dd class="mt-1 text-sm font-bold text-[color:var(--ds-text)]">{{ formatDate(item.metrology_review_due_at) }}</dd>
                </div>
                <div v-if="item.metrology_notes" class="sm:col-span-2 lg:col-span-3">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Notas metrológicas</dt>
                  <dd class="mt-1 text-sm font-semibold leading-6 text-[color:var(--ds-text-muted)]">{{ item.metrology_notes }}</dd>
                </div>
              </dl>
            </section>
          </div>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <h2 class="ds-heading text-base">Stock por armazém</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ inventory.length }} localizações com registo.</p>
            </div>
            <BuildingLibraryIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
          </div>

          <div v-if="inventory.length === 0" class="p-5">
            <div class="ds-empty-state px-5 py-10 text-center">
              <BuildingLibraryIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="ds-heading mt-3 text-sm">Sem stock disponível</h3>
              <p class="ds-copy mt-1 text-xs">Este item ainda não está associado a um armazém.</p>
            </div>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full align-middle">
              <thead class="ds-table-head">
                <tr>
                  <th class="ds-table-heading px-5 py-3 text-left">Armazém</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Disponível</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Mínimo</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Reabastecimento</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Estado</th>
                  <th class="ds-table-heading px-5 py-3 text-right">Ações</th>
                </tr>
              </thead>
              <tbody class="ds-table-body divide-y divide-[color:var(--ds-border)]">
                <tr v-for="inv in inventory" :key="inv?.id" class="ds-table-row">
                  <td class="px-5 py-3">
                    <span class="block text-sm font-bold text-[color:var(--ds-text)]">{{ inv?.warehouse?.name || 'Armazém' }}</span>
                    <span class="mt-0.5 block text-xs text-[color:var(--ds-text-soft)]">{{ inv?.warehouse?.location?.name || 'Sem localização' }}</span>
                    <span v-if="inv?.warehouse?.is_refrigerated" class="ds-chip mt-2 border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200">Refrigerado</span>
                  </td>
                  <td class="px-5 py-3">
                    <span class="block text-xl font-bold text-[color:var(--ds-text)]">{{ inv.qty_available }}</span>
                    <span class="text-xs text-[color:var(--ds-text-soft)]">{{ item.unit?.code || 'unidades' }}</span>
                  </td>
                  <td class="ds-table-cell px-5 py-3">{{ inv.min_stock_level }}</td>
                  <td class="ds-table-cell px-5 py-3">{{ inv.reorder_point }}</td>
                  <td class="px-5 py-3"><span :class="getStockStatusClasses(inv)">{{ inv.stock_status_label }}</span></td>
                  <td class="px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                      <button type="button" class="ds-table-action" @click="adjustStock(inv)">
                        <ArrowsUpDownIcon class="h-4 w-4" />
                        Ajustar
                      </button>
                      <button type="button" class="ds-table-action" @click="transferStock(inv)">
                        <ArrowsRightLeftIcon class="h-4 w-4" />
                        Transferir
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="ds-table-shell">
          <div class="ds-table-summary px-5 py-4">
            <div>
              <h2 class="ds-heading text-base">Transações recentes</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ recentTransactions.length }} movimentos registados.</p>
            </div>
            <ClockIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
          </div>

          <div v-if="recentTransactions.length === 0" class="p-5">
            <div class="ds-empty-state px-5 py-10 text-center">
              <ClockIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="ds-heading mt-3 text-sm">Sem transações recentes</h3>
              <p class="ds-copy mt-1 text-xs">Os movimentos deste item aparecerão aqui.</p>
            </div>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full align-middle">
              <thead class="ds-table-head">
                <tr>
                  <th class="ds-table-heading px-5 py-3 text-left">Data</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Tipo</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Quantidade</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Armazém</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Utilizador</th>
                  <th class="ds-table-heading px-5 py-3 text-left">Motivo</th>
                </tr>
              </thead>
              <tbody class="ds-table-body divide-y divide-[color:var(--ds-border)]">
                <tr v-for="transaction in recentTransactions" :key="transaction.id" class="ds-table-row">
                  <td class="ds-table-cell whitespace-nowrap px-5 py-3">{{ formatDateTime(transaction.created_at) }}</td>
                  <td class="px-5 py-3"><span :class="getTransactionTypeClasses(transaction)">{{ transaction.type?.name || 'Movimento' }}</span></td>
                  <td class="px-5 py-3">
                    <span :class="transaction.is_addition ? 'font-bold text-emerald-700 dark:text-emerald-300' : 'font-bold text-rose-700 dark:text-rose-300'">
                      {{ transaction.is_addition ? '+' : '-' }}{{ transaction.qty }}
                    </span>
                  </td>
                  <td class="ds-table-cell px-5 py-3">{{ transaction.warehouse?.name || 'N/A' }}</td>
                  <td class="ds-table-cell px-5 py-3">{{ transaction.user?.name || 'N/A' }}</td>
                  <td class="ds-table-cell min-w-60 px-5 py-3">{{ transaction.reason || 'Sem motivo registado' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
            <div>
              <h2 class="ds-heading text-base">Documentos</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ documents.length }} ficheiros associados.</p>
            </div>
            <DocumentTextIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
          </div>

          <div v-if="documents.length === 0" class="p-5">
            <div class="ds-empty-state px-5 py-10 text-center">
              <DocumentTextIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <h3 class="ds-heading mt-3 text-sm">Nenhum documento associado</h3>
              <p class="ds-copy mt-1 text-xs">Adicione certificados, fichas de segurança ou evidência técnica no editor do item.</p>
            </div>
          </div>

          <div v-else class="divide-y divide-[color:var(--ds-border)]">
            <article v-for="document in documents" :key="document.id || document.name" class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)]">
                  <DocumentTextIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                </div>
                <div class="min-w-0">
                  <h3 class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ document.name }}</h3>
                  <p class="mt-0.5 text-xs text-[color:var(--ds-text-soft)]">{{ (document.extension || document.name.split('.').pop() || 'ficheiro').toUpperCase() }} · {{ readableFileSize(document.size) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button type="button" class="ds-table-action" title="Descarregar" @click="downloadAttachment(document)">
                  <CloudArrowDownIcon class="h-4 w-4" />
                  Descarregar
                </button>
                <button type="button" class="ds-table-action ds-table-action-danger" title="Remover documento" @click="deleteAttachment(item.id, document.id)">
                  <TrashIcon class="h-4 w-4" />
                  Remover
                </button>
              </div>
            </article>
          </div>
        </section>
      </div>

      <aside class="space-y-4">
        <section class="ds-command-surface p-5">
          <p class="ds-kicker">Comandos de stock</p>
          <h2 class="ds-heading mt-2 text-base">Ações rápidas</h2>
          <div class="mt-4 grid gap-2">
            <button type="button" class="ds-button ds-button-primary w-full" @click="adjustStockModal = true">
              <ArrowsUpDownIcon class="h-4 w-4" />
              Ajustar stock
            </button>
            <button type="button" class="ds-button ds-button-secondary w-full" @click="transferStockModal = true">
              <ArrowsRightLeftIcon class="h-4 w-4" />
              Transferir stock
            </button>
            <button v-if="item.is_reagent || isReagent" type="button" class="ds-button ds-button-secondary w-full" @click="consumeReagentModal = true">
              <BeakerIcon class="h-4 w-4" />
              Registar consumo
            </button>
            <button v-if="item.next_calibration_date" type="button" class="ds-button ds-button-secondary w-full" @click="recordCalibrationModal = true">
              <WrenchScrewdriverIcon class="h-4 w-4" />
              Registar calibração
            </button>
            <Link :href="route('vap-inventory.orders.create', { item_id: item.id })" class="ds-button ds-button-secondary w-full">
              <ShoppingCartIcon class="h-4 w-4" />
              Criar pedido
            </Link>
          </div>
        </section>

        <section class="ds-card p-5">
          <div class="flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <h2 class="ds-heading text-base">Estado operacional</h2>
          </div>
          <dl class="mt-4 divide-y divide-[color:var(--ds-border)]">
            <div class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Item</dt>
              <dd><span :class="getStatusClasses(item.status)">{{ item.status?.name || 'N/A' }}</span></dd>
            </div>
            <div v-if="isReagent" class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Validade</dt>
              <dd><span :class="getExpiryStatusClasses(item)">{{ getExpiryStatusText(item) }}</span></dd>
            </div>
            <div v-if="item.next_calibration_date" class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Calibração</dt>
              <dd><span :class="getCalibrationStatusClasses(item)">{{ getCalibrationStatusText(item) }}</span></dd>
            </div>
            <div v-if="item.metrology_status" class="flex items-center justify-between gap-3 py-3">
              <dt class="text-xs font-semibold text-[color:var(--ds-text-muted)]">Metrologia</dt>
              <dd><span :class="getMetrologyStatusClasses(item.metrology_status)">{{ getMetrologyStatusText(item.metrology_status) }}</span></dd>
            </div>
          </dl>
        </section>

        <section class="ds-card p-5">
          <h2 class="ds-heading text-base">Atividade recente</h2>
          <div v-if="recentActivity.length" class="mt-4 space-y-4">
            <article v-for="activity in recentActivity" :key="activity.type + '-' + activity.id" class="flex items-start gap-3">
              <span :class="['mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full', getActivityColor(activity.type)]">
                <component :is="getActivityIcon(activity.type)" class="h-4 w-4 text-white" />
              </span>
              <div class="min-w-0">
                <p class="text-sm font-semibold leading-5 text-[color:var(--ds-text)]">{{ activity.description }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ formatTimeAgo(activity.timestamp) }}</p>
              </div>
            </article>
          </div>
          <div v-else class="ds-empty-state mt-4 px-4 py-6 text-center text-xs font-semibold text-[color:var(--ds-text-soft)]">Sem atividade recente.</div>
        </section>
      </aside>
    </section>

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
import { ref, computed, onBeforeUnmount, onMounted } from 'vue'
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
  return (props.item.category?.name || '').toLowerCase().includes('reagente');
})

const isEquipment = computed(() => {
  return (props.item.category?.name || '').toLowerCase().includes('equipamento');
})

const adjustStockModal = ref(false)
const transferStockModal = ref(false)
const consumeReagentModal = ref(false)
const recordCalibrationModal = ref(false)
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

const hasTechnicalSpecs = computed(() => {
  return props.item.resolution || props.item.precision || props.item.range ||
         props.item.firmware || props.item.software ||
         props.item.metrological_uncertainty_value || props.item.metrological_traceability_reference
})

const overviewFields = computed(() => [
  { label: 'Categoria', value: props.item.category?.name || 'N/A' },
  { label: 'Tipo', value: props.item.type?.name || 'N/A' },
  { label: 'Unidade', value: props.item.unit?.code || 'N/A' },
  { label: 'Fornecedor', value: props.item.supplier?.name || 'N/A' },
  { label: 'Marca', value: props.item.brand || 'N/A' },
  { label: 'Modelo', value: props.item.model || 'N/A' },
])

const identificationFields = computed(() => [
  { label: 'Código principal', value: props.item.code || 'N/A' },
  { label: 'Código interno', value: props.item.internal_code || 'N/A' },
  { label: 'Código de barras', value: props.item.barcode || 'N/A' },
  isEquipment.value ? { label: 'Número de série', value: props.item.serial_number || 'N/A' } : null,
  isReagent.value ? { label: 'Lote', value: props.item.lot || 'N/A' } : null,
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

const statusChipClasses = {
  neutral: 'ds-chip border-zinc-300 bg-zinc-50 text-zinc-800 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200',
  success: 'ds-chip border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200',
  danger: 'ds-chip border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-400/30 dark:bg-rose-500/10 dark:text-rose-200',
  warning: 'ds-chip border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-200',
  info: 'ds-chip border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200',
}

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
  colors: ['#0f172a', '#2563eb', '#0f766e', '#f59e0b', '#7c3aed'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.stock_distribution?.labels || [],
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
  tooltip: {
    theme: chartTooltipTheme.value,
  },
  legend: { show: false }
}))

const activityMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.activity_mix?.labels || [],
  colors: ['#2563eb', '#f59e0b', '#14b8a6', '#dc2626'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom',
    labels: {
      colors: chartTextColor.value,
    },
  },
  stroke: {
    colors: [isDarkMode.value ? '#020617' : '#ffffff']
  },
  tooltip: {
    theme: chartTooltipTheme.value,
  },
}))

const compliancePulseChartOptions = computed(() => ({
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
  colors: ['#0f766e', '#dc2626', '#f59e0b', '#2563eb'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.compliance_pulse?.labels || [],
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
  tooltip: {
    theme: chartTooltipTheme.value,
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
  if (!status) return statusChipClasses.neutral
  
  const statusName = status.name.toLowerCase()
  if (statusName.includes('active') || statusName.includes('ativo')) {
    return statusChipClasses.success
  } else if (statusName.includes('inactive') || statusName.includes('inativo') || statusName.includes('out')) {
    return statusChipClasses.danger
  } else if (statusName.includes('maintenance') || statusName.includes('calibration')) {
    return statusChipClasses.warning
  } else {
    return statusChipClasses.neutral
  }
}

const getExpiryDateColor = (item) => {
  if (item.is_expired) return 'text-red-900 dark:text-red-200'
  if (item.days_to_expiry <= 30) return 'text-orange-900 dark:text-orange-200'
  if (item.days_to_expiry <= 60) return 'text-yellow-900 dark:text-amber-200'
  return 'text-green-900 dark:text-emerald-200'
}

const getDaysColor = (days) => {
  if (days <= 0) return 'text-red-900 dark:text-red-200'
  if (days <= 30) return 'text-orange-900 dark:text-orange-200'
  if (days <= 60) return 'text-yellow-900 dark:text-amber-200'
  return 'text-green-900 dark:text-emerald-200'
}

const getExpiryStatusClasses = (item) => {
  if (item.is_expired) {
    return statusChipClasses.danger
  } else if (item.days_to_expiry <= 30) {
    return statusChipClasses.danger
  } else if (item.days_to_expiry <= 60) {
    return statusChipClasses.warning
  } else {
    return statusChipClasses.success
  }
}

const getExpiryStatusText = (item) => {
  if (item.is_expired) return 'Expirado'
  if (item.days_to_expiry <= 30) return 'Expirando em Breve'
  if (item.days_to_expiry <= 60) return 'Preste a Expirar'
  return 'Bom'
}

const getCalibrationDateColor = (item) => {
  if (item.needs_calibration) return 'text-red-900 dark:text-red-200'
  if (item.days_to_calibration <= 30) return 'text-orange-900 dark:text-orange-200'
  if (item.days_to_calibration <= 90) return 'text-yellow-900 dark:text-amber-200'
  return 'text-green-900 dark:text-emerald-200'
}

const getCalibrationStatusClasses = (item) => {
  if (item.needs_calibration) {
    return statusChipClasses.danger
  } else if (item.days_to_calibration <= 30) {
    return statusChipClasses.danger
  } else if (item.days_to_calibration <= 90) {
    return statusChipClasses.warning
  } else {
    return statusChipClasses.success
  }
}

const getCalibrationStatusText = (item) => {
  if (item.needs_calibration) return 'Atrasado'
  if (item.days_to_calibration <= 30) return 'A vencer em breve'
  if (item.days_to_calibration <= 90) return 'Em breve'
  return 'Agendado'
}

const getMetrologyStatusClasses = (status) => {
  if (status === 'hold') return statusChipClasses.danger
  if (status === 'incomplete') return statusChipClasses.danger
  if (status === 'review_due') return statusChipClasses.warning
  if (status === 'validated') return statusChipClasses.success
  return statusChipClasses.neutral
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
    return statusChipClasses.danger
  } else if (status === 'critical_stock') {
    return statusChipClasses.danger
  } else if (status === 'low_stock') {
    return statusChipClasses.warning
  } else {
    return statusChipClasses.success
  }
}

const getTransactionTypeClasses = (transaction) => {
  const type = transaction.type?.code
  if (type === 'stock_in' || type === 'stock_adjustment_add') {
    return statusChipClasses.success
  } else if (type === 'stock_out' || type === 'consumption') {
    return statusChipClasses.danger
  } else if (type === 'stock_transfer') {
    return statusChipClasses.info
  } else {
    return statusChipClasses.neutral
  }
}

const getActivityColor = (type) => {
  const colors = {
    transaction: 'bg-blue-900',
    consumption: 'bg-red-900',
    transfer: 'bg-green-900',
    calibration: 'bg-indigo-900 dark:bg-indigo-500',
  }
  return colors[type] || 'bg-zinc-700 dark:bg-zinc-600'
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
    window.location.assign(route('vap-inventory.items.attachments.download-single', { model_id: file.id }));
}

onMounted(() => {
  syncDarkMode()

  if (typeof MutationObserver !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
