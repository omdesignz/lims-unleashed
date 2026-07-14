<template>
  <div class="space-y-6">
    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseSelect v-model="filters.dateRange" label="Período analítico">
          <option value="7d">Últimos 7 dias</option>
          <option value="30d">Últimos 30 dias</option>
          <option value="90d">Últimos 90 dias</option>
          <option value="1y">Último ano</option>
          <option value="custom">Período personalizado</option>
        </BaseSelect>

        <BaseSelect v-model="filters.categoryId" label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.warehouseId" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>

        <div v-if="filters.dateRange === 'custom'" class="grid grid-cols-2 gap-3">
          <BaseInput v-model="filters.startDate" type="date" label="Início" />
          <BaseInput v-model="filters.endDate" type="date" label="Fim" />
        </div>
        <div v-else class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Âmbito activo</p>
          <p class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ periodLabel }}</p>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Actualização automática dos indicadores.</p>
        </div>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <p class="text-sm font-bold text-[var(--ds-text)]">{{ consumptionHistory.length }} eventos de consumo na amostra</p>
            <span v-if="isLoading" class="ds-chip">
              <ArrowPathIcon class="h-3.5 w-3.5 animate-spin" />
              A actualizar
            </span>
          </div>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
          <button type="button" class="ds-button ds-button-secondary" :disabled="!hasScopedFilters" @click="clearScopedFilters">
            <FunnelIcon class="h-4 w-4" />
            Limpar âmbito
          </button>
          <button type="button" class="ds-button ds-button-primary" @click="$emit('request-report', 'consumption')">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar análise
          </button>
        </div>
      </div>

      <div v-if="requestError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
        {{ requestError }}
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Operational metrics</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Consumo, valor e carga de alerta</h2>
        </div>
        <span :class="['ds-chip', usageChangeTone]">{{ usageChangeLabel }}</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <article v-for="card in metricCards" :key="card.label" class="min-w-0 p-5">
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

    <section class="ds-command-surface overflow-hidden" :class="isLoading ? 'opacity-70' : ''">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Analytical workspace</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Fluxo, distribuição e concentração</h2>
        </div>
        <span class="ds-chip">{{ periodLabel }}</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] xl:grid-cols-2 xl:divide-x">
        <article class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Tendência de consumo</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Volume diário de reagentes consumidos.</p>
            </div>
            <ChartBarSquareIcon class="h-5 w-5 text-rose-700 dark:text-rose-300" />
          </div>
          <div v-if="consumptionTrend.length" class="mt-4 min-h-72">
            <apexchart type="line" height="288" :options="consumptionChartOptions" :series="consumptionChartSeries" />
          </div>
          <div v-else class="ds-empty-state mt-4 grid min-h-72 place-items-center p-6 text-center">
            <div>
              <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
              <p class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem consumo no período</p>
            </div>
          </div>
        </article>

        <article class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Existências por categoria</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Distribuição das unidades disponíveis.</p>
            </div>
            <span class="ds-chip">{{ stockDistribution.length }} categorias</span>
          </div>
          <div v-if="stockDistribution.length" class="mt-4 min-h-72">
            <apexchart type="donut" height="288" :options="stockChartOptions" :series="stockChartSeries" />
          </div>
          <div v-else class="ds-empty-state mt-4 grid min-h-72 place-items-center p-6 text-center">
            <div>
              <CircleStackIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
              <p class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem existências distribuído</p>
            </div>
          </div>
        </article>

        <article class="min-w-0 border-t border-[var(--ds-border)] p-5 xl:border-t">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Comparação mensal</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Ano actual comparado com o período homólogo.</p>
            </div>
            <ArrowsRightLeftIcon class="h-5 w-5 text-violet-700 dark:text-violet-300" />
          </div>
          <div v-if="monthlyComparison.length" class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="monthlyChartOptions" :series="monthlyChartSeries" />
          </div>
          <div v-else class="ds-empty-state mt-4 grid min-h-72 place-items-center p-6 text-center">
            <p class="text-sm font-black text-[var(--ds-text)]">Sem comparação mensal disponível</p>
          </div>
        </article>

        <article class="min-w-0 border-t border-[var(--ds-border)] p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Reagentes mais consumidos</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Concentração do consumo nos principais itens.</p>
            </div>
            <TrophyIcon class="h-5 w-5 text-amber-700 dark:text-amber-300" />
          </div>
          <div v-if="topReagents.length" class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="topReagentsChartOptions" :series="topReagentsChartSeries" />
          </div>
          <div v-else class="ds-empty-state mt-4 grid min-h-72 place-items-center p-6 text-center">
            <p class="text-sm font-black text-[var(--ds-text)]">Sem consumo por reagente</p>
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(22rem,0.75fr)]">
      <div class="ds-command-surface overflow-hidden">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Supply assurance</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Desempenho de fornecedores</h2>
          </div>
          <span class="ds-chip">Meta: 90% no prazo</span>
        </div>
        <div v-if="supplierPerformance.length" class="min-h-80 p-5">
          <apexchart type="bar" height="320" :options="supplierChartOptions" :series="supplierChartSeries" />
        </div>
        <div v-else class="ds-empty-state m-5 grid min-h-72 place-items-center p-6 text-center">
          <div>
            <TruckIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
            <p class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem entregas avaliáveis</p>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">O indicador aparece quando existirem ordens com datas de entrega.</p>
          </div>
        </div>
      </div>

      <div class="grid gap-6">
        <section v-for="alert in alertGroups" :key="alert.label" class="ds-panel overflow-hidden">
          <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ alert.kicker }}</p>
              <h3 class="mt-1 text-sm font-black text-[var(--ds-text)]">{{ alert.label }}</h3>
            </div>
            <span :class="['ds-chip', alert.tone]">{{ alert.count }}</span>
          </div>
          <ul v-if="alert.items.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="item in alert.items" :key="item" class="flex items-start gap-3 px-5 py-3">
              <span :class="['mt-1.5 h-2 w-2 shrink-0 rounded-full', alert.dot]"></span>
              <span class="min-w-0 text-sm font-bold leading-5 text-[var(--ds-text)]">{{ item }}</span>
            </li>
          </ul>
          <div v-else class="px-5 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">Sem ocorrências nesta categoria.</div>
        </section>

        <button v-if="Number(metrics.criticalAlerts || 0) > 0" type="button" class="ds-button ds-button-primary w-full" @click="restockDialogOpen = true">
          <ShoppingCartIcon class="h-4 w-4" />
          Criar rascunho de reposição
        </button>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Reagent stewardship</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Histórico de consumo</h2>
          </div>
          <span class="ds-chip">{{ consumptionHistory.length }} eventos</span>
        </div>

        <div v-if="consumptionHistory.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="event in consumptionHistory" :key="`mobile-${event.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ formatDate(event.date) }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ event.reagent_name || 'Reagente não identificado' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ event.warehouse?.name || 'Sem armazém' }}</p>
              </div>
              <span class="ds-chip shrink-0">{{ formatNumber(event.quantity_used) }} un.</span>
            </div>
            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Operador</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ event.used_by || 'N/D' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Existências actual</dt>
                <dd :class="['mt-2 text-sm font-black', stockTone(event)]">{{ formatNumber(event.current_stock) }} un.</dd>
              </div>
            </dl>
            <p class="text-sm font-semibold leading-5 text-[var(--ds-text-muted)]">{{ event.remarks || 'Sem observações.' }}</p>
          </article>
        </div>

        <div v-if="consumptionHistory.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Data</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Reagente</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Consumo</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Operador</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Saúde do existências</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Cobertura</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="event in consumptionHistory" :key="event.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="whitespace-nowrap px-5 py-4 align-top font-mono text-xs font-black text-[var(--ds-text)]">{{ formatDate(event.date) }}</td>
                <td class="px-5 py-4 align-top">
                  <p class="font-black text-[var(--ds-text)]">{{ event.reagent_name || 'Reagente não identificado' }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ event.item?.code || 'Sem código' }}</p>
                  <p class="mt-1 max-w-xs text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ event.remarks || 'Sem observações.' }}</p>
                </td>
                <td class="px-5 py-4 align-top font-bold text-[var(--ds-text)]">{{ event.warehouse?.name || 'N/D' }}</td>
                <td class="px-5 py-4 text-right align-top font-mono font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatNumber(event.quantity_used) }}</td>
                <td class="px-5 py-4 align-top font-bold text-[var(--ds-text)]">{{ event.used_by || 'N/D' }}</td>
                <td class="min-w-44 px-5 py-4 align-top">
                  <div class="flex items-center justify-between gap-3">
                    <span :class="['text-xs font-black', stockTone(event)]">{{ stockLabel(event) }}</span>
                    <span class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ formatNumber(event.current_stock) }} / {{ formatNumber(Number(event.min_level || 0) * 2) }}</span>
                  </div>
                  <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
                    <div :class="['h-full rounded-full', stockBarTone(event)]" :style="{ width: `${stockPercentage(event)}%` }"></div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-5 py-4 text-right align-top">
                  <p :class="['font-black tabular-nums', coverageTone(event)]">{{ coverageLabel(event) }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ event.predicted_out_date ? `até ${formatDate(event.predicted_out_date)}` : 'sem previsão' }}</p>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-else class="ds-empty-state m-5 p-8 text-center">
          <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem histórico de consumo</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste o período ou confirme a existência de registos.</p>
        </div>
      </section>

      <aside class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Predição de rutura</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Cobertura mais curta</h2>
          </div>
          <ol v-if="depletionRisks.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="(risk, index) in depletionRisks" :key="`${risk.id}-${index}`" class="px-5 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ risk.reagent_name || 'Reagente' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ risk.warehouse?.name || 'Sem armazém' }}</p>
                </div>
                <span :class="['shrink-0 font-mono text-xs font-black tabular-nums', coverageTone(risk)]">{{ coverageLabel(risk) }}</span>
              </div>
            </li>
          </ol>
          <div v-else class="px-5 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">Sem risco calculável no período.</div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Concentração</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Top reagentes</h2>
          </div>
          <ol v-if="topReagents.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="(reagent, index) in topReagents.slice(0, 7)" :key="reagent.id || index" class="flex items-center gap-3 px-5 py-3">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-xs font-black text-[var(--ds-text-soft)]">{{ index + 1 }}</span>
              <span class="min-w-0 flex-1 truncate text-sm font-black text-[var(--ds-text)]">{{ reagent.name || 'Sem nome' }}</span>
              <span class="shrink-0 font-mono text-xs font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatNumber(reagent.consumption) }}</span>
            </li>
          </ol>
          <div v-else class="px-5 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">Sem dados por reagente.</div>
        </section>
      </aside>
    </div>

    <TransitionRoot as="template" :show="restockDialogOpen">
      <Dialog as="div" class="relative z-50" @close="restockDialogOpen = false">
        <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-150" leave-from="opacity-100" leave-to="opacity-0">
          <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0 translate-y-4 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-150" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:scale-95">
              <DialogPanel class="ds-modal-panel w-full max-w-lg overflow-hidden text-left transition-all">
                <div class="px-5 py-5 sm:px-6">
                  <span class="grid h-11 w-11 place-items-center rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                    <ShoppingCartIcon class="h-5 w-5" />
                  </span>
                  <DialogTitle class="mt-4 text-lg font-black text-[var(--ds-text)]">Criar rascunho de reposição?</DialogTitle>
                  <p class="mt-2 text-sm font-semibold leading-6 text-[var(--ds-text-muted)]">Será criado um rascunho para os itens atualmente sem existências. A ordem continuará sujeita a revisão e aprovação.</p>
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                  <button type="button" class="ds-button ds-button-secondary" :disabled="creatingDrafts" @click="restockDialogOpen = false">Cancelar</button>
                  <button type="button" class="ds-button ds-button-primary" :disabled="creatingDrafts" @click="createRestockDraft">
                    <ArrowPathIcon v-if="creatingDrafts" class="h-4 w-4 animate-spin" />
                    <ShoppingCartIcon v-else class="h-4 w-4" />
                    {{ creatingDrafts ? 'A criar...' : 'Criar rascunho' }}
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
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  BanknotesIcon,
  BeakerIcon,
  BellAlertIcon,
  ChartBarSquareIcon,
  CircleStackIcon,
  FunnelIcon,
  ShoppingCartIcon,
  TrophyIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
})

defineEmits(['request-report'])

const analyticsData = ref(normalizeData(props.initialData))
const isLoading = ref(false)
const requestError = ref('')
const isDarkMode = ref(false)
const restockDialogOpen = ref(false)
const creatingDrafts = ref(false)
let themeObserver
let requestController

const filters = reactive({
  dateRange: '30d',
  categoryId: '',
  warehouseId: '',
  startDate: '',
  endDate: '',
})

const metrics = computed(() => analyticsData.value.metrics ?? {})
const consumptionTrend = computed(() => analyticsData.value.consumptionTrend)
const stockDistribution = computed(() => analyticsData.value.stockDistribution)
const monthlyComparison = computed(() => analyticsData.value.monthlyComparison)
const topReagents = computed(() => analyticsData.value.topReagents)
const consumptionHistory = computed(() => analyticsData.value.consumptionHistory)
const supplierPerformance = computed(() => Array.isArray(metrics.value.supplierPerformance)
  ? [...metrics.value.supplierPerformance].sort((a, b) => Number(b.on_time_rate || 0) - Number(a.on_time_rate || 0))
  : [])

const periodLabel = computed(() => {
  if (filters.dateRange === 'custom') {
    if (filters.startDate && filters.endDate) return `${formatDate(filters.startDate)} - ${formatDate(filters.endDate)}`
    return 'Defina as duas datas'
  }
  return ({ '7d': 'Últimos 7 dias', '30d': 'Últimos 30 dias', '90d': 'Últimos 90 dias', '1y': 'Último ano' })[filters.dateRange] || 'Últimos 30 dias'
})

const activeFilterPills = computed(() => {
  const pills = [periodLabel.value]
  if (filters.categoryId) pills.push(`Categoria: ${categoryName(filters.categoryId)}`)
  if (filters.warehouseId) pills.push(`Armazém: ${warehouseName(filters.warehouseId)}`)
  return pills
})

const hasScopedFilters = computed(() => filters.dateRange !== '30d' || Boolean(filters.categoryId || filters.warehouseId))
const totalAlerts = computed(() => Number(metrics.value.reorderAlerts || 0) + Number(metrics.value.criticalAlerts || 0) + Number(metrics.value.expiringAlerts || 0))
const usageChangeLabel = computed(() => {
  const change = Number(metrics.value.usageChange || 0)
  if (change === 0) return 'Sem variação vs. período anterior'
  return `${change > 0 ? '+' : ''}${formatNumber(change)}% vs. período anterior`
})
const usageChangeTone = computed(() => Number(metrics.value.usageChange || 0) > 0
  ? 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300'
  : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300')

const metricCards = computed(() => [
  {
    label: 'Consumo total',
    value: formatNumber(metrics.value.totalConsumption),
    detail: `${formatNumber(metrics.value.monthlyConsumption)} unidades no mês`,
    icon: BeakerIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Média diária',
    value: formatNumber(metrics.value.dailyAverage),
    detail: usageChangeLabel.value,
    icon: ChartBarSquareIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Carga de alerta',
    value: totalAlerts.value,
    detail: `${metrics.value.criticalAlerts || 0} ocorrências críticas`,
    icon: BellAlertIcon,
    tone: totalAlerts.value ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Valor em existências',
    value: formatCurrency(metrics.value.inventoryValue),
    detail: 'Âmbito seleccionado',
    icon: BanknotesIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
])

const alertGroups = computed(() => [
  {
    kicker: 'Controlo crítico',
    label: 'Sem existências ou expirado',
    count: Number(metrics.value.criticalAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.critical),
    tone: 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300',
    dot: 'bg-rose-500',
  },
  {
    kicker: 'Reposição',
    label: 'Abaixo do nível mínimo',
    count: Number(metrics.value.reorderAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.reorder),
    tone: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
    dot: 'bg-amber-500',
  },
  {
    kicker: 'Controlo de validade',
    label: 'Validade próxima',
    count: Number(metrics.value.expiringAlerts || 0),
    items: arrayValue(metrics.value.alertDetails?.expiring),
    tone: 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300',
    dot: 'bg-violet-500',
  },
])

const depletionRisks = computed(() => consumptionHistory.value
  .filter((event) => event.days_remaining !== null && event.days_remaining !== undefined)
  .sort((a, b) => Number(a.days_remaining) - Number(b.days_remaining))
  .slice(0, 7))

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')
const baseChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const consumptionChartSeries = computed(() => [{
  name: 'Consumo diário',
  data: consumptionTrend.value.map((item) => Number(item.quantity || 0)),
}])
const consumptionChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#be123c'],
  stroke: { curve: 'straight', width: 3 },
  markers: { size: 3 },
  xaxis: {
    categories: consumptionTrend.value.map((item) => item.date),
    labels: { rotate: -20, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

const stockChartSeries = computed(() => stockDistribution.value.map((item) => Number(item.quantity || 0)))
const stockChartOptions = computed(() => ({
  ...baseChartOptions.value,
  labels: stockDistribution.value.map((item) => item.category || 'Sem categoria'),
  colors: ['#0e7490', '#059669', '#7c3aed', '#d97706', '#e11d48', '#475569'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
}))

const monthlyChartSeries = computed(() => [
  { name: 'Ano actual', data: monthlyComparison.value.map((month) => Number(month.current || 0)) },
  { name: 'Ano anterior', data: monthlyComparison.value.map((month) => Number(month.previous || 0)) },
])
const monthlyChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#0e7490', '#7c3aed'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '52%' } },
  xaxis: {
    categories: monthlyComparison.value.map((month) => month.month),
    labels: { style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  legend: { position: 'top', horizontalAlign: 'right', labels: { colors: chartTextColor.value } },
}))

const topReagentsChartSeries = computed(() => [{
  name: 'Consumo',
  data: topReagents.value.slice(0, 8).map((item) => Number(item.consumption || 0)),
}])
const topReagentsChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#d97706'],
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: {
    categories: topReagents.value.slice(0, 8).map((item) => item.name || 'Sem nome'),
    labels: { style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { maxWidth: 180, style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

const supplierChartSeries = computed(() => [{
  name: 'Entregas no prazo',
  data: supplierPerformance.value.map((supplier) => Number(supplier.on_time_rate || 0)),
}])
const supplierChartOptions = computed(() => ({
  ...baseChartOptions.value,
  colors: ['#059669'],
  annotations: {
    xaxis: [{
      x: 90,
      borderColor: '#e11d48',
      label: { text: 'Meta 90%', style: { color: '#fff', background: '#e11d48' } },
    }],
  },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: {
    categories: supplierPerformance.value.map((supplier) => supplier.supplier || 'Sem fornecedor'),
    min: 0,
    max: 100,
    labels: { formatter: (value) => `${value}%`, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { maxWidth: 220, style: { colors: chartTextColor.value } } },
  legend: { show: false },
}))

function normalizeData(data = {}) {
  return {
    consumptionTrend: arrayValue(data.consumptionTrend),
    stockDistribution: arrayValue(data.stockDistribution),
    monthlyComparison: arrayValue(data.monthlyComparison),
    topReagents: arrayValue(data.topReagents),
    consumptionHistory: arrayValue(data.consumptionHistory),
    metrics: data.metrics ?? {},
  }
}

function arrayValue(value) {
  return Array.isArray(value) ? value : []
}

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function clearScopedFilters() {
  Object.assign(filters, {
    dateRange: '30d',
    categoryId: '',
    warehouseId: '',
    startDate: '',
    endDate: '',
  })
}

function queryParameters() {
  const parameters = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) parameters.set(key, value)
  })
  return parameters
}

async function loadAnalytics() {
  if (filters.dateRange === 'custom' && (!filters.startDate || !filters.endDate)) return

  requestController?.abort()
  requestController = new AbortController()
  isLoading.value = true
  requestError.value = ''

  try {
    const response = await fetch(`${route('vap-inventory.analytics.data')}?${queryParameters().toString()}`, {
      headers: { Accept: 'application/json' },
      signal: requestController.signal,
    })
    if (!response.ok) throw new Error('Não foi possível actualizar os indicadores de inventário.')
    analyticsData.value = normalizeData(await response.json())
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') return
    requestError.value = error instanceof Error ? error.message : 'Não foi possível actualizar os indicadores.'
  } finally {
    isLoading.value = false
  }
}

function stockPercentage(event) {
  const benchmark = Math.max(Number(event.min_level || 0) * 2, 1)
  return Math.min(Math.max((Number(event.current_stock || 0) / benchmark) * 100, 0), 100)
}

function stockLabel(event) {
  if (Number(event.current_stock || 0) <= 0) return 'Sem existências'
  if (Number(event.current_stock || 0) <= Number(event.min_level || 0)) return 'Existências baixo'
  return 'Saudável'
}

function stockTone(event) {
  if (Number(event.current_stock || 0) <= 0) return 'text-rose-700 dark:text-rose-300'
  if (Number(event.current_stock || 0) <= Number(event.min_level || 0)) return 'text-amber-700 dark:text-amber-300'
  return 'text-emerald-700 dark:text-emerald-300'
}

function stockBarTone(event) {
  if (Number(event.current_stock || 0) <= 0) return 'bg-rose-500'
  if (Number(event.current_stock || 0) <= Number(event.min_level || 0)) return 'bg-amber-500'
  return 'bg-emerald-500'
}

function coverageLabel(event) {
  if (event.days_remaining === null || event.days_remaining === undefined) return 'Sem previsão'
  return `${formatNumber(event.days_remaining)} dias`
}

function coverageTone(event) {
  const days = Number(event.days_remaining)
  if (event.days_remaining === null || event.days_remaining === undefined) return 'text-[var(--ds-text-soft)]'
  if (days <= 7) return 'text-rose-700 dark:text-rose-300'
  if (days <= 30) return 'text-amber-700 dark:text-amber-300'
  return 'text-emerald-700 dark:text-emerald-300'
}

function createRestockDraft() {
  creatingDrafts.value = true
  router.post(route('vap-inventory.analytics.restock'), {}, {
    preserveScroll: true,
    onSuccess: () => { restockDialogOpen.value = false },
    onFinish: () => { creatingDrafts.value = false },
  })
}

watch(filters, debounce(loadAnalytics, 350), { deep: true })

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  requestController?.abort()
  themeObserver?.disconnect()
})
</script>
