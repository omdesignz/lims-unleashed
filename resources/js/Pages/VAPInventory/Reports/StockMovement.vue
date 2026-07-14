<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Inventory traceability</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-release"></span>
              Livro de movimentos
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ArrowsUpDownIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Movimento de existências</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Audite entradas, saídas, ajustes, transferências e consumo com rastreabilidade por item, armazém e operador.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="exportReport">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar PDF
          </button>
          <Link :href="route('vap-inventory.items.index')" class="ds-button ds-button-primary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao inventário
          </Link>
        </div>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 truncate text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5 shrink-0', card.tone]" />
          </div>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
        <BaseInput v-model="filters.date_to" type="date" label="Data final" />

        <div class="ds-field-group xl:col-span-2">
          <label class="ds-field-label">Item de inventário</label>
          <ComboboxEnhanced
            :model-value="selectedItem"
            :options="itemOptions"
            placeholder="Pesquisar item por nome ou código"
            @update:model-value="selectItem"
          />
        </div>

        <BaseSelect v-model="filters.warehouse_id" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>

        <BaseInput v-model="filters.search" label="Pesquisa livre" placeholder="Item, código ou utilizador">
          <template #leading><MagnifyingGlassIcon class="h-4 w-4" /></template>
        </BaseInput>

        <BaseSelect v-model="filters.sort_by" label="Ordenar por">
          <option value="created_at">Data e hora</option>
          <option value="qty">Quantidade</option>
        </BaseSelect>

        <BaseSelect v-model="filters.sort_direction" label="Direcção">
          <option value="desc">Descendente</option>
          <option value="asc">Ascendente</option>
        </BaseSelect>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ transactions.total || transactionRows.length }} movimentos no resultado</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">A mostrar o histórico completo disponível.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="inline-flex rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1" aria-label="Modo do relatório">
            <button
              type="button"
              :aria-pressed="filters.view === 'detailed'"
              :class="modeButtonClass('detailed')"
              @click="setView('detailed')"
            >
              Movimentos
            </button>
            <button
              type="button"
              :aria-pressed="filters.view === 'summary'"
              :class="modeButtonClass('summary')"
              @click="setView('summary')"
            >
              Resumo diário
            </button>
          </div>
          <button type="button" class="ds-button ds-button-secondary" :disabled="!hasActiveFilters" @click="clearFilters">
            <FunnelIcon class="h-4 w-4" />
            Limpar filtros
          </button>
        </div>
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Análise de fluxo</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Direcção, composição e ritmo diário</h2>
        </div>
        <span class="ds-chip">{{ filterPeriod || 'Período completo' }}</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] xl:grid-cols-[1.2fr_0.8fr] xl:divide-x xl:divide-y-0">
        <article class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Actividade diária</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Entradas, saídas e número de transacções ao longo do período.</p>
            </div>
            <span class="ds-chip">{{ dailyActivityDays }} dias</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="line" height="288" :options="dailyActivityChartOptions" :series="dailyActivityChartSeries" />
          </div>
        </article>

        <div class="grid divide-y divide-[var(--ds-border)]">
          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Mix operacional</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Peso relativo dos tipos de movimento.</p>
              </div>
              <span class="ds-chip">{{ typeMixTotal }} eventos</span>
            </div>
            <div class="mt-4 min-h-64">
              <apexchart type="donut" height="256" :options="typeMixChartOptions" :series="typeMixChartSeries" />
            </div>
          </article>

          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Balanço do período</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Volume de entrada, saída e saldo líquido.</p>
              </div>
              <ShieldCheckIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
            </div>
            <div class="mt-4 min-h-56">
              <apexchart type="bar" height="224" :options="directionBreakdownChartOptions" :series="directionBreakdownChartSeries" />
            </div>
          </article>
        </div>
      </div>
    </section>

    <div v-if="filters.view === 'detailed'" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Audit trail</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Livro de movimentos</h2>
          </div>
          <span class="ds-chip">{{ transactions.total || transactionRows.length }} registos</span>
        </div>

        <div v-if="loading" class="ds-empty-state m-5 p-8 text-center">
          <span class="mx-auto block h-7 w-7 animate-spin rounded-full border-2 border-[var(--ds-border)] border-t-[rgb(var(--primary-700-rgb))]"></span>
          <p class="mt-3 text-sm font-semibold text-[var(--ds-text-muted)]">A actualizar movimentos...</p>
        </div>

        <div v-else-if="transactionRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="transaction in transactionRows" :key="`mobile-${transaction.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ transaction.item?.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ transaction.item?.name || 'Item não identificado' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ formatDateTime(transaction.created_at) }}</p>
              </div>
              <span :class="['ds-chip shrink-0', transactionTypeTone(transaction.type?.code)]">{{ transaction.type?.name || 'Movimento' }}</span>
            </div>

            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</dt>
                <dd :class="['mt-2 font-mono text-sm font-black tabular-nums', quantityTone(transaction.type?.code)]">{{ quantityLabel(transaction) }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ transaction.warehouse?.name || 'N/D' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Operador</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ transaction.user?.name || 'N/D' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Categoria</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ transaction.item?.category?.name || 'N/D' }}</dd>
              </div>
            </dl>
            <p class="text-sm font-semibold leading-5 text-[var(--ds-text-muted)]">{{ transaction.notes || 'Sem observações.' }}</p>
          </article>
        </div>

        <div v-else-if="!loading" class="ds-empty-state m-5 p-8 text-center">
          <ClipboardDocumentListIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem movimentos encontrados</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste o período ou os filtros de rastreabilidade.</p>
        </div>

        <div v-if="!loading && transactionRows.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Data e hora</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Item</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Tipo</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Operador</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Evidência</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="transaction in transactionRows" :key="transaction.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="whitespace-nowrap px-5 py-4 align-top font-mono text-xs font-black tabular-nums text-[var(--ds-text)]">{{ formatDateTime(transaction.created_at) }}</td>
                <td class="px-5 py-4 align-top">
                  <p class="font-black text-[var(--ds-text)]">{{ transaction.item?.name || 'Item não identificado' }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ transaction.item?.code || 'Sem código' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ transaction.item?.category?.name || 'Sem categoria' }}</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-bold text-[var(--ds-text)]">{{ transaction.warehouse?.name || 'N/D' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ transaction.warehouse?.location?.name || 'Sem localização' }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-4 align-top">
                  <span :class="['ds-chip', transactionTypeTone(transaction.type?.code)]">{{ transaction.type?.name || 'Movimento' }}</span>
                </td>
                <td :class="['whitespace-nowrap px-5 py-4 text-right align-top font-mono font-black tabular-nums', quantityTone(transaction.type?.code)]">{{ quantityLabel(transaction) }}</td>
                <td class="px-5 py-4 align-top font-bold text-[var(--ds-text)]">{{ transaction.user?.name || 'N/D' }}</td>
                <td class="max-w-xs px-5 py-4 align-top text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ transaction.notes || 'Sem observações.' }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <Pagination
          v-if="transactionRows.length"
          :links="transactions.links"
          :total="transactions.total"
          :from="transactions.from"
          :to="transactions.to"
          :last_page="transactions.last_page"
          :current_page="transactions.current_page"
        />
      </section>

      <aside class="space-y-6">
        <section class="ds-panel p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Maior actividade</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-cyan-50 text-cyan-800 dark:bg-cyan-500/10 dark:text-cyan-200">
              <TrophyIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="truncate text-lg font-black text-[var(--ds-text)]">{{ stats.most_active_item?.item?.name || 'Sem dados' }}</p>
              <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ stats.most_active_item?.transaction_count || 0 }} transacções no período</p>
            </div>
          </div>
        </section>

        <section class="ds-panel p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Responsabilidade</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
              <UserIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="truncate text-lg font-black text-[var(--ds-text)]">{{ stats.most_active_user?.user?.name || 'Sem dados' }}</p>
              <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ stats.most_active_user?.transaction_count || 0 }} movimentos registados</p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Composição</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Tipos de movimento</h2>
          </div>
          <ol class="divide-y divide-[var(--ds-border)]">
            <li v-for="row in typeMixRows" :key="row.label" class="flex items-center gap-3 px-5 py-3">
              <span :class="['h-2.5 w-2.5 shrink-0 rounded-full', row.dot]"></span>
              <span class="min-w-0 flex-1 truncate text-sm font-bold text-[var(--ds-text)]">{{ row.label }}</span>
              <span class="font-mono text-xs font-black tabular-nums text-[var(--ds-text-muted)]">{{ row.value }}</span>
            </li>
          </ol>
        </section>

        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <MapPinIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-300" />
            <div>
              <p class="text-sm font-black text-[var(--ds-text)]">Rastreabilidade operacional</p>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Cada linha preserva o item, local, tipo, quantidade, operador, momento e observação associados ao movimento.</p>
            </div>
          </div>
        </section>
      </aside>
    </div>

    <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Reconciliação diária</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Resumo por dia</h2>
          </div>
          <span class="ds-chip">{{ summaryRows.length }} dias</span>
        </div>

        <div v-if="loading" class="ds-empty-state m-5 p-8 text-center">
          <span class="mx-auto block h-7 w-7 animate-spin rounded-full border-2 border-[var(--ds-border)] border-t-[rgb(var(--primary-700-rgb))]"></span>
          <p class="mt-3 text-sm font-semibold text-[var(--ds-text-muted)]">A consolidar o período...</p>
        </div>

        <div v-else-if="summaryRows.length" class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Data</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Transacções</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Entradas</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Saídas</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Saldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="day in summaryRows" :key="day.date" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 font-mono text-xs font-black text-[var(--ds-text)]">{{ formatDate(day.date) }}</td>
                <td class="px-5 py-4 text-right font-black tabular-nums text-[var(--ds-text)]">{{ day.total_transactions }}</td>
                <td class="px-5 py-4 text-right font-mono font-black tabular-nums text-emerald-700 dark:text-emerald-300">+{{ formatQuantity(day.total_in) }}</td>
                <td class="px-5 py-4 text-right font-mono font-black tabular-nums text-rose-700 dark:text-rose-300">-{{ formatQuantity(day.total_out) }}</td>
                <td :class="['px-5 py-4 text-right font-mono font-black tabular-nums', netTone(day)]">{{ netLabel(day) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-else-if="!loading" class="ds-empty-state m-5 p-8 text-center">
          <ChartBarSquareIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem actividade diária</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Seleccione outro período para gerar a reconciliação.</p>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="ds-panel p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Ritmo médio</p>
          <p class="mt-3 text-3xl font-black tabular-nums text-[var(--ds-text)]">{{ formatQuantity(stats.avg_daily_transactions) }}</p>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">transacções por dia no período</p>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Controlo de balanço</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Totais reconciliados</h2>
          </div>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-4 px-5 py-4">
              <dt class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text-muted)]"><ArrowDownCircleIcon class="h-4 w-4 text-emerald-700 dark:text-emerald-300" /> Entradas</dt>
              <dd class="font-mono text-sm font-black tabular-nums text-emerald-700 dark:text-emerald-300">{{ formatQuantity(stats.total_in) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-5 py-4">
              <dt class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text-muted)]"><ArrowUpCircleIcon class="h-4 w-4 text-rose-700 dark:text-rose-300" /> Saídas</dt>
              <dd class="font-mono text-sm font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatQuantity(stats.total_out) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 px-5 py-4">
              <dt class="text-sm font-bold text-[var(--ds-text-muted)]">Saldo líquido</dt>
              <dd :class="['font-mono text-sm font-black tabular-nums', Number(stats.net_movement || 0) >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300']">{{ signedNumber(stats.net_movement) }}</dd>
            </div>
          </dl>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowDownCircleIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowUpCircleIcon,
  ArrowsUpDownIcon,
  ChartBarSquareIcon,
  ClipboardDocumentListIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  ShieldCheckIcon,
  TrophyIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  transactions: { type: Object, default: () => ({ data: [] }) },
  summary: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
})

const inboundCodes = ['stock_in', 'stock_adjustment_add']
const outboundCodes = ['stock_out', 'stock_adjustment_remove', 'consumption']
const loading = ref(false)
const isDarkMode = ref(false)
let themeObserver

const filters = reactive({
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  item_id: props.filters?.item_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  view: props.filters?.view === 'summary' ? 'summary' : 'detailed',
  sort_by: ['created_at', 'qty'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'created_at',
  sort_direction: props.filters?.sort_direction === 'asc' ? 'asc' : 'desc',
})

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.code ? ` · ${item.code}` : ''}`,
})))
const selectedItem = ref(itemOptions.value.find((option) => String(option.value) === String(filters.item_id)) || null)
const transactionRows = computed(() => props.transactions?.data || [])
const summaryRows = computed(() => props.summary || [])
const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const summaryCards = computed(() => [
  {
    label: 'Transacções',
    value: formatQuantity(props.stats?.total_transactions),
    detail: 'Registos no período',
    icon: ClipboardDocumentListIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Entradas',
    value: formatQuantity(props.stats?.total_in),
    detail: 'Unidades adicionadas',
    icon: ArrowDownCircleIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Saídas',
    value: formatQuantity(props.stats?.total_out),
    detail: 'Unidades removidas',
    icon: ArrowUpCircleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Saldo líquido',
    value: signedNumber(props.stats?.net_movement),
    detail: `${formatQuantity(props.stats?.avg_daily_transactions)} transacções / dia`,
    icon: ChartBarSquareIcon,
    tone: Number(props.stats?.net_movement || 0) >= 0 ? 'text-violet-700 dark:text-violet-300' : 'text-amber-700 dark:text-amber-300',
  },
])

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) return `${formatDate(filters.date_from)} - ${formatDate(filters.date_to)}`
  if (filters.date_from) return `Desde ${formatDate(filters.date_from)}`
  if (filters.date_to) return `Até ${formatDate(filters.date_to)}`
  return ''
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filterPeriod.value) pills.push(filterPeriod.value)
  if (filters.item_id) pills.push(`Item: ${selectedItem.value?.label || 'Seleccionado'}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const directionBreakdownChartSeries = computed(() => props.charts?.direction_breakdown?.series || [])
const typeMixChartSeries = computed(() => props.charts?.type_mix?.series || [])
const typeMixTotal = computed(() => typeMixChartSeries.value.reduce((total, value) => total + Number(value || 0), 0))
const dailyActivityChartSeries = computed(() => props.charts?.daily_activity?.series || [])
const dailyActivityDays = computed(() => props.charts?.daily_activity?.labels?.length || 0)
const typeMixRows = computed(() => {
  const dots = ['bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-cyan-600']
  return (props.charts?.type_mix?.labels || []).map((label, index) => ({
    label,
    value: props.charts?.type_mix?.series?.[index] || 0,
    dot: dots[index] || 'bg-zinc-500',
  }))
})

const directionBreakdownChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0e7490'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '52%' } },
  xaxis: {
    categories: props.charts?.direction_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const typeMixChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.type_mix?.labels || [],
  colors: ['#059669', '#e11d48', '#d97706', '#0891b2'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const dailyActivityChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#059669', '#e11d48', '#0e7490'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  stroke: { curve: 'straight', width: [3, 3, 2] },
  markers: { size: 2 },
  xaxis: {
    categories: props.charts?.daily_activity?.labels || [],
    labels: { rotate: -20, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatDateTime(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function formatQuantity(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function signedNumber(value) {
  const number = Number(value || 0)
  return `${number > 0 ? '+' : ''}${formatQuantity(number)}`
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function selectItem(option) {
  selectedItem.value = option
  filters.item_id = option?.value ?? ''
}

function setView(view) {
  filters.view = view
}

function modeButtonClass(view) {
  return [
    'rounded-lg px-3 py-2 text-xs font-black transition-colors',
    filters.view === view
      ? 'bg-[rgb(var(--primary-700-rgb))] text-white shadow-xs'
      : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]',
  ]
}

function transactionTypeTone(code) {
  if (inboundCodes.includes(code)) return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
  if (outboundCodes.includes(code)) return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
  if (code === 'transfer') return 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-200'
  return 'text-[var(--ds-text-muted)]'
}

function quantityTone(code) {
  if (inboundCodes.includes(code)) return 'text-emerald-700 dark:text-emerald-300'
  if (outboundCodes.includes(code)) return 'text-rose-700 dark:text-rose-300'
  return 'text-[var(--ds-text)]'
}

function quantityLabel(transaction) {
  const code = transaction.type?.code
  const sign = inboundCodes.includes(code) ? '+' : outboundCodes.includes(code) ? '-' : ''
  return `${sign}${formatQuantity(Math.abs(Number(transaction.qty || 0)))}`
}

function netValue(day) {
  return Number(day.total_in || 0) - Number(day.total_out || 0)
}

function netLabel(day) {
  return signedNumber(netValue(day))
}

function netTone(day) {
  return netValue(day) >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'
}

function clearFilters() {
  selectedItem.value = null
  Object.assign(filters, {
    date_from: '',
    date_to: '',
    item_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'created_at',
    sort_direction: 'desc',
  })
}

function exportReport() {
  router.post(route('vap-inventory.reports.export'), {
    report_type: 'stock_movement',
    format: 'pdf',
    filters: { ...filters },
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.stock-movement'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onStart: () => { loading.value = true },
      onFinish: () => { loading.value = false },
    })
  }, 350),
  { deep: true },
)

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
