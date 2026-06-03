<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Relatórios operacionais"
      :title="title || 'Relatórios de Amostras'"
      description="Consolidação executiva das amostras recebidas, descartes, tempos de análise, CQ interno e distribuição por estado para apoiar gestão laboratorial e auditorias."
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <a
            :href="sampleExportUrl"
            class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white/85 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar amostras
          </a>
          <a
            :href="discardExportUrl"
            class="inline-flex items-center gap-2 rounded-full bg-primary-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
          >
            <DocumentArrowDownIcon class="h-4 w-4" />
            Exportar descartes
          </a>
        </div>
      </template>

      <div class="mt-6 flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-950/60 dark:text-slate-200 dark:ring-slate-700">
          Gerado em {{ formatDateTime(generatedAt) }}
        </span>
        <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-500/30">
          {{ samples.total || 0 }} amostras no recorte
        </span>
      </div>
    </ModuleHero>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-7">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total de amostras</p>
        <p class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ summary.total_samples }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Completadas</p>
        <p class="mt-3 text-3xl font-bold text-emerald-700 dark:text-emerald-200">{{ summary.completed_samples }}</p>
      </div>
      <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Por iniciar</p>
        <p class="mt-3 text-3xl font-bold text-amber-700 dark:text-amber-200">{{ summary.pending_samples }}</p>
      </div>
      <div class="rounded-2xl border border-primary-200 bg-primary-50/70 p-5 shadow-sm dark:border-primary-500/20 dark:bg-primary-500/10">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Em progresso</p>
        <p class="mt-3 text-3xl font-bold text-primary-800 dark:text-primary-200">{{ summary.in_progress_samples }}</p>
      </div>
      <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-5 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total de descartes</p>
        <p class="mt-3 text-3xl font-bold text-rose-700 dark:text-rose-200">{{ summary.total_discards }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tempo médio</p>
        <p class="mt-3 text-3xl font-bold text-primary-800 dark:text-primary-200">{{ summary.avg_turnaround_hours }}h</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Taxa de descarte: {{ summary.discard_rate }}%</p>
      </div>
      <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">CQ interno</p>
        <p class="mt-3 text-3xl font-bold text-emerald-700 dark:text-emerald-200">{{ summary.internal_qc_samples || 0 }}</p>
        <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">Matéria-prima no fluxo normal</p>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Filtros operacionais</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Refine o período, o escopo laboratorial e o tipo de descarte sem depender de listas longas.
          </p>
        </div>
        <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
          {{ samples.total || 0 }} amostras no recorte actual
        </span>
      </div>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <combobox-enhanced v-model="sampleScopeSelection" title-label="Escopo" placeholder="Todas as origens" :options="sampleScopeFilterOptions" :load-options="loadSampleScopes" />
        <combobox-enhanced v-model="qcReleaseSelection" title-label="Decisão CQ" placeholder="Todas as decisões" :options="qcReleaseFilterOptions" :load-options="loadQcReleaseStatuses" />
        <date-picker-enhanced v-model="form.date_from" label="Data inicial" :is-dark="isDark" />
        <date-picker-enhanced v-model="form.date_to" label="Data final" :is-dark="isDark" />
        <combobox-enhanced v-model="statusSelection" title-label="Estado" placeholder="Todos os estados" :options="statusFilterOptions" :load-options="loadStatuses" />
        <combobox-enhanced v-model="sampleTypeSelection" title-label="Tipo de amostra" placeholder="Todos os tipos" :options="sampleTypeOptions" :load-options="loadSampleTypes" />
        <combobox-enhanced v-model="customerSelection" title-label="Cliente" placeholder="Todos os clientes" :options="customerOptions" :load-options="loadCustomers" />
        <combobox-enhanced v-model="labSelection" title-label="Laboratório" placeholder="Todos os laboratórios" :options="labOptions" :load-options="loadLabs" />
        <combobox-enhanced v-model="departmentSelection" title-label="Departamento" placeholder="Todos os departamentos" :options="departmentOptions" :load-options="loadDepartments" />
        <combobox-enhanced v-model="discardMethodSelection" title-label="Método de descarte" placeholder="Todos os métodos" :options="discardMethodOptions" :load-options="loadDiscardMethods" />
      </div>

      <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
        <button
          type="button"
          class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="resetFilters"
        >
          Redefinir
        </button>
        <button
          type="button"
          class="rounded-2xl bg-primary-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500"
          @click="applyFilters"
        >
          Aplicar filtros
        </button>
      </div>
    </div>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[0.9fr_1.25fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Estados do ciclo</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Leitura rápida da pressão no fluxo de análise.</p>
          </div>
          <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800 dark:bg-primary-500/10 dark:text-primary-200">
            {{ statusChartTotal }} total
          </span>
        </div>
        <div class="mt-6">
          <apexchart type="donut" height="310" :options="statusChartOptions" :series="statusChartSeries" />
        </div>
      </article>

      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Receção no período</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Evolução das entradas para antecipar capacidade técnica.</p>
          </div>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
            {{ sampleTimeline.length }} pontos
          </span>
        </div>
        <div class="mt-6">
          <apexchart type="area" height="310" :options="timelineChartOptions" :series="timelineChartSeries" />
        </div>
      </article>

      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Descarte</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Métodos mais usados no recorte atual.</p>
          </div>
          <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-200">
            {{ discardChartTotal }} registos
          </span>
        </div>
        <div class="mt-6">
          <apexchart type="bar" height="310" :options="discardChartOptions" :series="discardChartSeries" />
        </div>
      </article>
    </section>

    <section class="overflow-hidden rounded-[2rem] border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-primary-50/70 shadow-sm dark:border-emerald-500/20 dark:from-emerald-950/40 dark:via-slate-900 dark:to-primary-950/30">
      <div class="grid grid-cols-1 gap-6 p-6 xl:grid-cols-[1.05fr_1.4fr]">
        <div class="space-y-5">
          <div class="inline-flex rounded-full bg-white/80 p-1 text-xs font-semibold text-emerald-800 shadow-sm ring-1 ring-emerald-200 dark:bg-slate-950/70 dark:text-emerald-200 dark:ring-emerald-500/20">
            <span class="rounded-full bg-emerald-600 px-3 py-1 text-white">ISO 17025</span>
            <span class="px-3 py-1">CQ interno</span>
          </div>
          <div>
            <h2 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
              <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                <BeakerIcon class="h-6 w-6" />
              </span>
              Matéria-prima em controlo interno
            </h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
              Este recorte mostra as amostras internas de matéria-prima que seguem o mesmo ciclo de receção, lab code,
              análises, resultados, verificação e aprovação. A proposta não é exigida, mas a rastreabilidade continua ligada ao fluxo normal.
            </p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 dark:bg-emerald-500 dark:hover:bg-emerald-400"
            @click="showInternalQcOnly"
          >
            <CheckCircleIcon class="h-4 w-4" />
            Ver apenas CQ interno
          </button>
        </div>

        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="metric in internalQcMetrics"
              :key="metric.label"
              class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/60"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ metric.label }}</p>
              <p class="mt-2 text-2xl font-bold" :class="metric.tone">{{ metric.value }}</p>
            </article>
          </div>

          <div class="grid grid-cols-1 gap-4 xl:grid-cols-[0.8fr_0.8fr_1.4fr]">
            <div class="rounded-3xl border border-white/80 bg-white/85 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/60">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Disciplinas</h3>
              <div class="mt-4 space-y-3">
                <div
                  v-for="item in disciplineBreakdown"
                  :key="item.label"
                  class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2 dark:bg-slate-900"
                >
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ item.label }}</span>
                  <span class="text-sm font-bold text-slate-950 dark:text-white">{{ item.value }}</span>
                </div>
              </div>
            </div>

            <div class="rounded-3xl border border-white/80 bg-white/85 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/60">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Decisão final</h3>
              <div class="mt-4 space-y-3">
                <div
                  v-for="item in releaseDecisionBreakdown"
                  :key="item.value"
                  class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2 dark:bg-slate-900"
                >
                  <span class="text-sm text-slate-600 dark:text-slate-300">{{ item.label }}</span>
                  <span class="text-sm font-bold text-slate-950 dark:text-white">{{ item.total }}</span>
                </div>
              </div>
            </div>

            <div class="rounded-3xl border border-white/80 bg-white/85 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/60">
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Últimas entradas de CQ</h3>
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">
                  {{ internalQcSamples.length }} recentes
                </span>
              </div>
              <div class="mt-4 space-y-3">
                <article
                  v-for="sample in internalQcSamples"
                  :key="sample.id"
                  class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900"
                >
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                      <p class="text-sm font-semibold text-primary-900 dark:text-primary-300">{{ sample.code }}</p>
                      <p class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ sample.name }}</p>
                      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ disciplineLabel(sample.quality_control?.discipline) }} · {{ qcPurposeLabel(sample.quality_control?.purpose) }}
                      </p>
                    </div>
                    <span :class="statusBadgeClass(sample.status)" class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold">
                      {{ getStatusLabel(sample.status) }}
                    </span>
                  </div>
                  <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                    Lote {{ sample.quality_control?.lot || 'N/A' }} · Fornecedor {{ sample.quality_control?.supplier_name || 'N/A' }} · {{ qcDecisionLabel(sample.quality_control?.decision) }}
                  </p>
                  <p class="mt-2">
                    <span :class="qcReleaseBadgeClass(sample.quality_control?.final_decision)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                      {{ qcReleaseLabel(sample.quality_control?.final_decision) }}
                    </span>
                  </p>
                </article>
                <p v-if="internalQcSamples.length === 0" class="text-sm text-slate-500 dark:text-slate-400">
                  Sem amostras internas de matéria-prima neste recorte.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Distribuição por estado</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in statusBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80"
          >
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ getStatusLabel(item.label) }}</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.total }}</span>
          </div>
          <p v-if="statusBreakdown.length === 0" class="text-sm text-slate-500 dark:text-slate-400">Sem dados para o filtro atual.</p>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tipos de amostra</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in sampleTypeBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80"
          >
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ item.label }}</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.total }}</span>
          </div>
          <p v-if="sampleTypeBreakdown.length === 0" class="text-sm text-slate-500 dark:text-slate-400">Sem dados para o filtro atual.</p>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Métodos de descarte</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in discardMethodBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80"
          >
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ item.label }}</span>
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.total }}</span>
          </div>
          <p v-if="discardMethodBreakdown.length === 0" class="text-sm text-slate-500 dark:text-slate-400">Sem descartes para o filtro atual.</p>
        </div>
      </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Linha temporal de recebimento</h2>
      <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="item in sampleTimeline"
          :key="item.date"
          class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/80"
        >
          <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ formatDate(item.date) }}</p>
          <p class="mt-1 text-2xl font-bold text-primary-900 dark:text-primary-400">{{ item.total }}</p>
        </div>
        <p v-if="sampleTimeline.length === 0" class="text-sm text-slate-500 dark:text-slate-400">Sem movimentação no período selecionado.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Amostras</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ samples.total }} registros filtrados
          </p>
        </div>

        <div v-if="samples.data.length === 0" class="p-6 text-sm text-slate-500 dark:text-slate-400">
          Nenhuma amostra encontrada para os filtros informados.
        </div>

        <div v-if="samples.data.length > 0" class="lg:hidden space-y-3 p-4">
          <article v-for="sample in samples.data" :key="sample.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/80">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-primary-900 dark:text-primary-400">{{ sample.code }}</p>
                <p class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ sample.name }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ sample.sample_type }}</p>
                <span
                  v-if="isInternalQcSample(sample)"
                  class="mt-2 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200"
                >
                  CQ interno · {{ disciplineLabel(sample.quality_control?.discipline) }}
                </span>
              </div>
              <span :class="statusBadgeClass(sample.status)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                {{ getStatusLabel(sample.status) }}
              </span>
            </div>
            <div
              v-if="isInternalQcSample(sample)"
              class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-3 text-xs text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-100"
            >
              <p class="font-semibold">{{ qcPurposeLabel(sample.quality_control?.purpose) }}</p>
              <p class="mt-1">
                Lote {{ sample.quality_control?.lot || 'N/A' }} · Fornecedor {{ sample.quality_control?.supplier_name || 'N/A' }} · {{ qcDecisionLabel(sample.quality_control?.decision) }}
              </p>
              <p class="mt-2">
                <span :class="qcReleaseBadgeClass(sample.quality_control?.final_decision)" class="inline-flex rounded-full px-2.5 py-1 font-semibold">
                  {{ qcReleaseLabel(sample.quality_control?.final_decision) }}
                </span>
              </p>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Cliente</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ sample.customer?.name || 'N/A' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Recebida</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ formatDateTime(sample.received_at) }}</dd>
              </div>
            </dl>
          </article>
        </div>

        <div v-if="samples.data.length > 0" class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-800/70">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Código</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Recebida</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
              <tr v-for="sample in samples.data" :key="sample.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-primary-900 dark:text-primary-400">{{ sample.code }}</div>
                  <div class="text-sm text-slate-600 dark:text-slate-300">{{ sample.name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ sample.sample_type }}</div>
                  <div
                    v-if="isInternalQcSample(sample)"
                    class="mt-2 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200"
                  >
                    CQ interno · {{ disciplineLabel(sample.quality_control?.discipline) }}
                  </div>
                  <div v-if="isInternalQcSample(sample)" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Lote {{ sample.quality_control?.lot || 'N/A' }} · {{ qcDecisionLabel(sample.quality_control?.decision) }}
                  </div>
                  <div v-if="isInternalQcSample(sample)" class="mt-2">
                    <span :class="qcReleaseBadgeClass(sample.quality_control?.final_decision)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                      {{ qcReleaseLabel(sample.quality_control?.final_decision) }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
                  {{ sample.customer?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4">
                  <span :class="statusBadgeClass(sample.status)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ getStatusLabel(sample.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
                  {{ formatDateTime(sample.received_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="samples.data.length > 0" class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-800">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando {{ samples.from }}-{{ samples.to }} de {{ samples.total }}
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              :disabled="!samples.prev_page_url"
              class="rounded-2xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-slate-200"
              @click="visitPage(samples.prev_page_url)"
            >
              Anterior
            </button>
            <button
              type="button"
              :disabled="!samples.next_page_url"
              class="rounded-2xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-slate-200"
              @click="visitPage(samples.next_page_url)"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Descartes</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ discards.total }} registros filtrados
          </p>
        </div>

        <div v-if="discards.data.length === 0" class="p-6 text-sm text-slate-500 dark:text-slate-400">
          Nenhum descarte encontrado para os filtros informados.
        </div>

        <div v-if="discards.data.length > 0" class="lg:hidden space-y-3 p-4">
          <article v-for="discard in discards.data" :key="discard.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/80">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-primary-900 dark:text-primary-400">{{ discard.sample?.code || 'N/A' }}</p>
                <p class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ discard.sample?.name || 'Amostra removida' }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ discard.sample?.customer?.name || 'Sem cliente' }}</p>
              </div>
              <span class="text-sm font-semibold text-rose-600">{{ discard.qty }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Método</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ discard.discard_method }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Data</dt>
                <dd class="mt-1 text-slate-700 dark:text-slate-200">{{ formatDateTime(discard.discarded_at) }}</dd>
              </div>
            </dl>
          </article>
        </div>

        <div v-if="discards.data.length > 0" class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-800/70">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Amostra</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Método</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Qtd.</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Data</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
              <tr v-for="discard in discards.data" :key="discard.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-primary-900 dark:text-primary-400">{{ discard.sample?.code || 'N/A' }}</div>
                  <div class="text-sm text-slate-600 dark:text-slate-300">{{ discard.sample?.name || 'Amostra removida' }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ discard.sample?.customer?.name || 'Sem cliente' }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
                  {{ discard.discard_method }}
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-rose-600">
                  {{ discard.qty }}
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-200">
                  {{ formatDateTime(discard.discarded_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="discards.data.length > 0" class="flex items-center justify-between border-t border-slate-200 px-6 py-4 dark:border-slate-800">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando {{ discards.from }}-{{ discards.to }} de {{ discards.total }}
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              :disabled="!discards.prev_page_url"
              class="rounded-2xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-slate-200"
              @click="visitPage(discards.prev_page_url)"
            >
              Anterior
            </button>
            <button
              type="button"
              :disabled="!discards.next_page_url"
              class="rounded-2xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:text-slate-400 dark:border-slate-700 dark:text-slate-200"
              @click="visitPage(discards.next_page_url)"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import datePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import { router, useForm } from '@inertiajs/vue3'
import { useTheme } from '@/Composables/useTheme'
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  CheckCircleIcon,
  DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  title: {
    type: String,
    default: 'Relatórios de Amostras',
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  samples: {
    type: Object,
    default: () => ({ data: [] }),
  },
  discards: {
    type: Object,
    default: () => ({ data: [] }),
  },
  internalQualityControl: {
    type: Object,
    default: () => ({
      total: 0,
      in_progress: 0,
      completed: 0,
      waiting_release: 0,
      pending_release_decision: 0,
      released: 0,
      quarantined: 0,
      rejected: 0,
      investigation_required: 0,
      linked_to_normal_flow: 0,
      by_discipline: {},
      by_release_decision: {},
      samples: [],
    }),
  },
  statusBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTypeBreakdown: {
    type: Array,
    default: () => [],
  },
  discardMethodBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTimeline: {
    type: Array,
    default: () => [],
  },
  customers: {
    type: Array,
    default: () => [],
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  discardMethods: {
    type: Array,
    default: () => [],
  },
  sampleTypes: {
    type: Array,
    default: () => [],
  },
  generatedAt: {
    type: String,
    default: '',
  },
})

const { isDark } = useTheme()

const chartThemeOptions = computed(() => ({
  theme: {
    mode: isDark.value ? 'dark' : 'light',
  },
  chart: {
    background: 'transparent',
    foreColor: isDark.value ? '#cbd5e1' : '#475569',
    toolbar: { show: false },
  },
  grid: {
    borderColor: isDark.value ? '#1e293b' : '#e2e8f0',
    strokeDashArray: 4,
  },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
  },
}))

const statusChartLabels = computed(() => props.statusBreakdown.map((item) => getStatusLabel(item.label)))
const statusChartSeries = computed(() => props.statusBreakdown.map((item) => Number(item.total) || 0))
const statusChartTotal = computed(() => statusChartSeries.value.reduce((total, value) => total + value, 0))
const statusChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    fontFamily: 'inherit',
  },
  labels: statusChartLabels.value,
  colors: ['#0f766e', '#d97706', '#059669', '#e11d48', '#64748b'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  legend: {
    position: 'bottom',
    labels: { colors: isDark.value ? '#cbd5e1' : '#334155' },
  },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Amostras',
            formatter: () => String(statusChartTotal.value),
          },
        },
      },
    },
  },
  stroke: {
    colors: [isDark.value ? '#0f172a' : '#ffffff'],
  },
}))

const timelineChartSeries = computed(() => [
  {
    name: 'Amostras recebidas',
    data: props.sampleTimeline.map((item) => Number(item.total) || 0),
  },
])
const timelineChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    fontFamily: 'inherit',
    zoom: { enabled: false },
  },
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  fill: {
    type: 'gradient',
    gradient: {
      opacityFrom: 0.26,
      opacityTo: 0.03,
      stops: [0, 95, 100],
    },
  },
  grid: chartThemeOptions.value.grid,
  xaxis: {
    categories: props.sampleTimeline.map((item) => formatDate(item.date)),
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: isDark.value ? '#94a3b8' : '#64748b' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: isDark.value ? '#94a3b8' : '#64748b' },
    },
  },
}))

const discardChartLabels = computed(() => props.discardMethodBreakdown.map((item) => item.label))
const discardChartSeries = computed(() => [
  {
    name: 'Descartes',
    data: props.discardMethodBreakdown.map((item) => Number(item.total) || 0),
  },
])
const discardChartTotal = computed(() => discardChartSeries.value[0].data.reduce((total, value) => total + value, 0))
const discardChartOptions = computed(() => ({
  ...chartThemeOptions.value,
  chart: {
    ...chartThemeOptions.value.chart,
    fontFamily: 'inherit',
  },
  colors: ['#e11d48'],
  dataLabels: { enabled: false },
  grid: chartThemeOptions.value.grid,
  plotOptions: {
    bar: {
      borderRadius: 9,
      horizontal: true,
      barHeight: '58%',
    },
  },
  xaxis: {
    categories: discardChartLabels.value,
    labels: {
      style: { colors: isDark.value ? '#94a3b8' : '#64748b' },
    },
  },
  yaxis: {
    labels: {
      style: { colors: isDark.value ? '#94a3b8' : '#64748b' },
    },
  },
}))

const statusOptions = [
  { value: 'POR_INICIAR', label: 'Por iniciar' },
  { value: 'EN_PROGRESO', label: 'Em progresso' },
  { value: 'COMPLETADO', label: 'Completado' },
  { value: 'CANCELADO', label: 'Cancelado' },
  { value: 'EN_PAUSA', label: 'Em pausa' },
]

const sampleScopeFilterOptions = computed(() => [
  { value: '', label: 'Todas as origens' },
  { value: 'internal_qc', label: 'CQ interno / matéria-prima' },
  { value: 'internal', label: 'Amostras internas' },
  { value: 'client', label: 'Cliente / portal' },
])
const qcReleaseFilterOptions = computed(() => [
  { value: '', label: 'Todas as decisões' },
  { value: 'pending', label: 'Sem decisão final' },
  { value: 'released', label: 'Liberada para uso' },
  { value: 'quarantined', label: 'Em quarentena' },
  { value: 'investigation_required', label: 'Investigação requerida' },
  { value: 'rejected', label: 'Rejeitada' },
  { value: 'trend_recorded', label: 'Registada para tendência' },
])
const statusFilterOptions = computed(() => statusOptions)
const sampleTypeOptions = computed(() => props.sampleTypes.map((type) => ({ value: type, label: type })))
const discardMethodOptions = computed(() => props.discardMethods.map((method) => ({ value: method, label: method })))
const customerOptions = computed(() => props.customers.map((customer) => ({ value: String(customer.id), label: customer.name })))
const labOptions = computed(() => props.labs.map((lab) => ({ value: String(lab.id), label: lab.name })))
const departmentOptions = computed(() => props.departments.map((department) => ({ value: String(department.id), label: department.name })))
const internalQc = computed(() => props.internalQualityControl || {})
const internalQcSamples = computed(() => internalQc.value.samples || [])
const internalQcMetrics = computed(() => [
  { label: 'Amostras CQ', value: internalQc.value.total || 0, tone: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Em análise', value: internalQc.value.in_progress || 0, tone: 'text-primary-800 dark:text-primary-300' },
  { label: 'Sem decisão final', value: internalQc.value.pending_release_decision || 0, tone: 'text-amber-700 dark:text-amber-300' },
  { label: 'Liberadas', value: internalQc.value.released || 0, tone: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Quarentena', value: internalQc.value.quarantined || 0, tone: 'text-amber-800 dark:text-amber-300' },
  { label: 'Ligadas ao fluxo', value: internalQc.value.linked_to_normal_flow || 0, tone: 'text-primary-800 dark:text-primary-300' },
])
const disciplineBreakdown = computed(() => [
  { label: 'Microbiologia', value: internalQc.value.by_discipline?.microbiology || 0 },
  { label: 'Química', value: internalQc.value.by_discipline?.chemistry || 0 },
  { label: 'Micro + Química', value: internalQc.value.by_discipline?.microbiology_and_chemistry || 0 },
])
const releaseDecisionBreakdown = computed(() => [
  { value: 'pending', label: 'Sem decisão final', total: internalQc.value.by_release_decision?.pending || 0 },
  { value: 'released', label: 'Liberada para uso', total: internalQc.value.by_release_decision?.released || 0 },
  { value: 'quarantined', label: 'Em quarentena', total: internalQc.value.by_release_decision?.quarantined || 0 },
  { value: 'investigation_required', label: 'Investigação requerida', total: internalQc.value.by_release_decision?.investigation_required || 0 },
  { value: 'rejected', label: 'Rejeitada', total: internalQc.value.by_release_decision?.rejected || 0 },
  { value: 'trend_recorded', label: 'Registada para tendência', total: internalQc.value.by_release_decision?.trend_recorded || 0 },
])

const form = useForm({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  status: props.filters.status || '',
  sample_type: props.filters.sample_type || '',
  sample_scope: props.filters.sample_scope || '',
  qc_release: props.filters.qc_release || '',
  customer_id: props.filters.customer_id || '',
  lab_id: props.filters.lab_id || '',
  department_id: props.filters.department_id || '',
  discard_method: props.filters.discard_method || '',
})

function bindSelection(options, field) {
  return computed({
    get() {
      return options.value.find((option) => String(option.value) === String(form[field])) ?? null
    },
    set(option) {
      form[field] = option?.value ? String(option.value) : ''
    },
  })
}

function buildLocalLoader(options) {
  return (query, setOptions) => {
    const normalizedQuery = String(query || '').trim().toLowerCase()
    const filtered = !normalizedQuery
      ? options.value
      : options.value.filter((option) => option.label.toLowerCase().includes(normalizedQuery))

    setOptions(filtered)
  }
}

const statusSelection = bindSelection(statusFilterOptions, 'status')
const sampleTypeSelection = bindSelection(sampleTypeOptions, 'sample_type')
const sampleScopeSelection = bindSelection(sampleScopeFilterOptions, 'sample_scope')
const qcReleaseSelection = bindSelection(qcReleaseFilterOptions, 'qc_release')
const customerSelection = bindSelection(customerOptions, 'customer_id')
const labSelection = bindSelection(labOptions, 'lab_id')
const departmentSelection = bindSelection(departmentOptions, 'department_id')
const discardMethodSelection = bindSelection(discardMethodOptions, 'discard_method')

const loadStatuses = buildLocalLoader(statusFilterOptions)
const loadSampleTypes = buildLocalLoader(sampleTypeOptions)
const loadSampleScopes = buildLocalLoader(sampleScopeFilterOptions)
const loadQcReleaseStatuses = buildLocalLoader(qcReleaseFilterOptions)
const loadCustomers = buildLocalLoader(customerOptions)
const loadLabs = buildLocalLoader(labOptions)
const loadDepartments = buildLocalLoader(departmentOptions)
const loadDiscardMethods = buildLocalLoader(discardMethodOptions)

const sampleExportUrl = computed(() => buildUrl(route('vap_samples.samples.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  status: form.status,
  sample_type: form.sample_type,
  sample_scope: form.sample_scope,
  qc_release: form.qc_release,
  customer_id: form.customer_id,
  lab_id: form.lab_id,
  department_id: form.department_id,
}))

const discardExportUrl = computed(() => buildUrl(route('vap_samples.discards.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  method: form.discard_method,
}))

function applyFilters() {
  router.get(route('vap_samples.reports'), normalizeFilters(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  form.reset()
  applyFilters()
}

function visitPage(url) {
  if (!url) return

  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  })
}

function normalizeFilters() {
  return Object.fromEntries(
    Object.entries({
      date_from: form.date_from,
      date_to: form.date_to,
      status: form.status,
      sample_type: form.sample_type,
      sample_scope: form.sample_scope,
      qc_release: form.qc_release,
      customer_id: form.customer_id,
      lab_id: form.lab_id,
      department_id: form.department_id,
      discard_method: form.discard_method,
    }).filter(([, value]) => value !== '' && value !== null && value !== undefined)
  )
}

function buildUrl(baseUrl, params) {
  const search = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      search.set(key, value)
    }
  })

  const query = search.toString()

  return query ? `${baseUrl}?${query}` : baseUrl
}

function formatDate(value) {
  if (!value) return '-'

  return new Date(value).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getStatusLabel(status) {
  return statusOptions.find((option) => option.value === status)?.label || status || 'N/A'
}

function statusBadgeClass(status) {
  const map = {
    COMPLETADO: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    EN_PROGRESO: 'bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200',
    POR_INICIAR: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    CANCELADO: 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
    EN_PAUSA: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
  }

  return map[status] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'
}

function showInternalQcOnly() {
  form.sample_scope = 'internal_qc'
  applyFilters()
}

function isInternalQcSample(sample) {
  return Boolean(sample?.quality_control?.is_internal_qc)
}

function disciplineLabel(value) {
  const map = {
    microbiology: 'Microbiologia',
    chemistry: 'Química',
    microbiology_and_chemistry: 'Microbiologia + Química',
  }

  return map[value] || value || 'Não definido'
}

function qcPurposeLabel(value) {
  const map = {
    raw_material_release: 'Liberação de matéria-prima',
    supplier_qualification: 'Qualificação de fornecedor',
    process_validation: 'Validação de processo',
    stability_follow_up: 'Seguimento de estabilidade',
    investigation: 'Investigação',
    other: 'Outro',
  }

  return map[value] || value || 'Não definido'
}

function qcDecisionLabel(value) {
  const map = {
    hold_until_release: 'Reter até liberação',
    release_if_compliant: 'Liberar se conforme',
    investigate_before_release: 'Investigar antes de liberar',
    trend_only: 'Apenas tendência',
  }

  return map[value] || value || 'Não definido'
}

function qcReleaseLabel(value) {
  const map = {
    pending: 'Sem decisão final',
    released: 'Liberada para uso',
    quarantined: 'Em quarentena',
    investigation_required: 'Investigação requerida',
    rejected: 'Rejeitada',
    trend_recorded: 'Registada para tendência',
  }

  return map[value || 'pending'] || value || 'Sem decisão final'
}

function qcReleaseBadgeClass(value) {
  const map = {
    released: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200',
    quarantined: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    investigation_required: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-500/10 dark:text-rose-200',
    trend_recorded: 'bg-primary-100 text-primary-800 dark:bg-primary-500/10 dark:text-primary-200',
  }

  return map[value] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}
</script>

<style scoped>
:deep(.apexcharts-tooltip),
:deep(.apexcharts-menu) {
  border-radius: 1rem !important;
  border-color: rgb(203 213 225 / 0.9) !important;
  box-shadow: 0 18px 45px rgb(15 23 42 / 0.16) !important;
}

:global(.dark) :deep(.apexcharts-tooltip),
:global(.dark) :deep(.apexcharts-menu) {
  border-color: rgb(51 65 85 / 0.9) !important;
  background: #0f172a !important;
  color: #e2e8f0 !important;
}
</style>
