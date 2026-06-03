<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2.35rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_30px_100px_rgba(20,61,55,0.12)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="grid gap-0 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <div class="relative isolate p-6 sm:p-8">
          <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_12%_0%,rgba(217,176,95,0.26),transparent_35%),linear-gradient(135deg,#fffdf7,#f7f1e7)] dark:bg-[radial-gradient(circle_at_12%_0%,rgba(217,176,95,0.16),transparent_34%),linear-gradient(135deg,#07110f,#10231f)]" />
          <div class="inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
            Cockpit LIMS
          </div>
          <h1 class="mt-5 max-w-4xl text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-4xl">
            O trabalho crítico do laboratório, sem ruído operacional.
          </h1>
          <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            Comece pelas acções que desbloqueiam amostras, resultados, certificados, compras e SGQ. Os painéis executivos ficam abaixo para leitura de gestão, não para bloquear a operação diária.
          </p>

          <div class="mt-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a
              v-for="action in dashboardPrimaryActions"
              :key="action.label"
              :href="action.href"
              class="group rounded-3xl border border-[#ded3bf] bg-white/85 p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-xl dark:border-[#25443c] dark:bg-[#081512] dark:hover:border-[#d9b05f]/60"
            >
              <component :is="action.icon" class="h-6 w-6 text-[#143d37] transition group-hover:scale-110 dark:text-[#f1d78b]" />
              <span class="mt-4 block text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ action.label }}</span>
              <span class="mt-1 block text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">{{ action.hint }}</span>
            </a>
          </div>
        </div>

        <aside class="border-t border-[#ded3bf] bg-[#f7f1e7] p-6 dark:border-[#25443c] dark:bg-[#081512] xl:border-l xl:border-t-0">
          <div class="flex items-center gap-3 rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-[#f1d78b] dark:bg-[#f1d78b] dark:text-[#07110f]">
              <UserCircleIcon class="h-7 w-7" />
            </div>
            <div>
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">Sessão activa</p>
              <p class="mt-1 text-sm font-black text-[#15231f] dark:text-[#f7f1e7]">{{ $page?.props?.auth?.user?.name || 'Utilizador' }}</p>
            </div>
          </div>

          <div class="mt-4 grid gap-3">
            <article
              v-for="signal in dashboardSignalCards"
              :key="signal.label"
              class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]"
            >
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">{{ signal.label }}</p>
              <p class="mt-2 text-3xl font-black text-[#143d37] dark:text-[#f1d78b]">{{ signal.value }}</p>
              <p class="mt-1 text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">{{ signal.hint }}</p>
            </article>
          </div>
        </aside>
      </div>
    </section>

    <section class="grid gap-6">
      <div class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-5 shadow-[0_22px_70px_rgba(20,61,55,0.08)] dark:border-[#25443c] dark:bg-[#07110f] sm:p-6">
        <div class="mb-4 flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">Pulso operacional</p>
            <h2 class="mt-2 text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">Indicadores principais</h2>
          </div>
        </div>
        <quick-stats :stats="props.stats" />
      </div>

      <div class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-5 shadow-[0_22px_70px_rgba(20,61,55,0.08)] dark:border-[#25443c] dark:bg-[#07110f] sm:p-6">
        <div class="mb-2 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">Atalhos secundários</p>
            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
              Acesso rápido aos módulos complementares, agora em largura total para leitura clara e navegação menos comprimida.
            </p>
          </div>
        </div>
        <quick-menu :default-open="false" />
      </div>
    </section>

    <!-- Executive Dashboard -->
    <section v-if="props.executive" class="space-y-5">
      <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(15,118,110,0.18),_transparent_30%),linear-gradient(135deg,_#ffffff,_#f8fafc)] shadow-sm dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top_left,_rgba(45,212,191,0.18),_transparent_30%),linear-gradient(135deg,_#0f172a,_#111827)]">
        <div class="flex flex-col gap-5 px-6 py-6 md:flex-row md:items-end md:justify-between">
          <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700 dark:text-teal-300">{{ $t('Gestão executiva') }}</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ $t('Painel executivo') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $t('Resumo operacional, procurement, fornecedores e conformidade num único cockpit com exportação pronta para gestão.') }}</p>
          </div>
          <div class="flex flex-wrap gap-3">
            <a
              :href="`${props.executive.export_url}?format=pdf`"
              class="inline-flex items-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400"
            >
              {{ $t('Exportar PDF executivo') }}
            </a>
            <a
              :href="`${props.executive.export_url}?format=csv`"
              class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
            >
              {{ $t('Exportar CSV') }}
            </a>
          </div>
        </div>
        <div class="grid gap-3 border-t border-slate-200 px-6 py-4 sm:grid-cols-3 dark:border-slate-700">
          <div class="rounded-2xl bg-white/80 px-4 py-3 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('Visão') }}</p>
            <p class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $t('Operações, compliance e procurement') }}</p>
          </div>
          <div class="rounded-2xl bg-white/80 px-4 py-3 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('Horizonte') }}</p>
            <p class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $t('Últimos 6 meses e risco actual') }}</p>
          </div>
          <div class="rounded-2xl bg-white/80 px-4 py-3 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('Uso') }}</p>
            <p class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $t('Leitura rápida para direcção e coordenação técnica') }}</p>
          </div>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="(kpi, index) in props.executive.kpis"
          :key="kpi.label"
          class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
        >
          <div
            class="h-1.5 w-full"
            :class="[
              index % 4 === 0 ? 'bg-teal-500' :
              index % 4 === 1 ? 'bg-sky-500' :
              index % 4 === 2 ? 'bg-amber-500' :
              'bg-rose-500'
            ]"
          />
          <div class="p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $t(kpi.label) }}</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 tabular-nums">{{ kpi.value }}</p>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $t(kpi.hint) }}</p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[1.5fr,1fr]">
        <div class="card p-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Ritmo operacional dos últimos 6 meses') }}</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Compara propostas aceites, amostras concluídas e certificados emitidos.') }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-800 dark:text-slate-200">
              {{ throughputTotal }} {{ $t('eventos') }}
            </span>
          </div>

          <div class="mt-5">
            <apexchart
              type="line"
              height="340"
              :options="throughputChartOptions"
              :series="throughputChartSeries"
            />
          </div>
        </div>

        <div class="card p-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Pipeline laboratorial') }}</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Onde a carga operacional está concentrada neste momento.') }}</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
              {{ sampleStatusTotal }} {{ $t('amostras') }}
            </span>
          </div>

          <div class="mt-5">
            <apexchart
              type="donut"
              height="340"
              :options="sampleStatusChartOptions"
              :series="sampleStatusChartSeries"
            />
          </div>
        </div>
      </div>

      <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <div class="card p-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Pedidos do portal por estado') }}</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Mostra o acúmulo entre pedidos pendentes, em curso e concluídos.') }}</p>
            </div>
            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">
              {{ portalRequestTotal }} {{ $t('pedidos') }}
            </span>
          </div>

          <div class="mt-5">
            <apexchart
              type="bar"
              height="300"
              :options="portalRequestsChartOptions"
              :series="portalRequestsChartSeries"
            />
          </div>
        </div>

        <div class="card p-6">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Prontidão de procurement') }}</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Indica quanto da fila aprovada pode avançar sem bloqueios.') }}</p>
            </div>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
              {{ procurementReadinessTotal }} {{ $t('necessidades') }}
            </span>
          </div>

          <div class="mt-5">
            <apexchart
              type="donut"
              height="300"
              :options="procurementReadinessChartOptions"
              :series="procurementReadinessChartSeries"
            />
          </div>
        </div>

        <div class="card p-6 lg:col-span-2 xl:col-span-1">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Risco fornecedor e severidade de receção') }}</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Combina o perfil de risco dos fornecedores ativos com a pressão de NCs abertas.') }}</p>
            </div>
            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">
              {{ supplierRiskTotal }} {{ $t('fornecedores') }}
            </span>
          </div>

          <div class="mt-5 space-y-5">
            <apexchart
              type="bar"
              height="160"
              :options="supplierRiskChartOptions"
              :series="supplierRiskChartSeries"
            />
            <apexchart
              type="bar"
              height="160"
              :options="receivingNcChartOptions"
              :series="receivingNcChartSeries"
            />
          </div>
        </div>
      </div>

      <!-- Top Customers -->
      <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Clientes com atividade recente') }}</h3>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="customer in props.executive.top_customers"
            :key="customer.id"
            class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-3"
          >
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ customer.name }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ customer.code || $t('Sem código') }}</p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ customer.warehouses_count }} {{ $t('armazéns registados') }}</p>
          </div>
        </div>
      </div>

      <div class="card p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Fornecedores sob observação') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Risco elevado, estado condicional ou revisão próxima.') }}</p>
          </div>
          <a
            :href="route('supplier-assessments.index')"
            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800"
          >
            {{ $t('Abrir avaliações') }}
          </a>
        </div>

        <div v-if="props.executive.supplier_watchlist?.length" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="assessment in props.executive.supplier_watchlist"
            :key="assessment.id"
            class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50"
          >
            <div class="flex flex-wrap items-center gap-2">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', supplierStatusClass(assessment.status)]">
                {{ supplierStatusLabel(assessment.status) }}
              </span>
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', supplierRiskClass(assessment.risk_level)]">
                {{ supplierRiskLabel(assessment.risk_level) }}
              </span>
            </div>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ assessment.supplier_name }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ assessment.department_name || $t('Cobertura transversal') }}</p>
            <div class="mt-3 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
              <span>{{ $t('Score') }} {{ assessment.total_score }}/100</span>
              <span>{{ assessment.next_review_at ? formatShortDate(assessment.next_review_at) : $t('Sem revisão') }}</span>
            </div>
          </div>
        </div>
        <div v-else class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/30 dark:text-gray-400">
          {{ $t('Nenhum fornecedor crítico ou com revisão próxima no momento.') }}
        </div>
      </div>

      <div class="card p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Necessidades à espera de compra') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Fila aprovada que ainda precisa de conversão em pedido de compra.') }}</p>
          </div>
          <a
            :href="route('vap-inventory.needs.index', { status: 'approved' })"
            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800"
          >
            {{ $t('Abrir procurement') }}
          </a>
        </div>

        <div v-if="props.executive.procurement_queue?.length" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="need in props.executive.procurement_queue"
            :key="need.id"
            class="rounded-xl border px-4 py-3"
            :class="procurementCardClass(need)"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="rounded-full bg-white/70 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-gray-900/40 dark:text-gray-100">{{ need.reference }}</span>
              <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ procurementUrgencyLabel(need) }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', procurementReadinessClass(need.supplier_readiness)]">
                {{ procurementReadinessLabel(need.supplier_readiness) }}
              </span>
            </div>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
              {{ need.department_name }}<span v-if="need.lab_name"> · {{ need.lab_name }}</span>
            </p>
            <p class="mt-2 line-clamp-3 text-sm text-gray-600 dark:text-gray-300">{{ need.justification || $t('Sem justificação adicional.') }}</p>
            <div class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
              <div>{{ $t('Itens') }}: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ need.items_count }}</span></div>
              <div>{{ $t('Solicitante') }}: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ need.requested_by_name || '—' }}</span></div>
              <div>{{ $t('Necessário até') }}: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatShortDate(need.needed_by_date) }}</span></div>
            </div>
            <div class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
              <div v-if="need.supplier_summary?.blocked_supplier_count">{{ $t('Fornecedores bloqueados') }}: <span class="font-semibold text-rose-700 dark:text-rose-300">{{ need.supplier_summary.blocked_supplier_count }}</span></div>
              <div v-if="need.supplier_summary?.missing_supplier_count">{{ $t('Itens sem fornecedor') }}: <span class="font-semibold text-amber-700 dark:text-amber-300">{{ need.supplier_summary.missing_supplier_count }}</span></div>
              <div v-if="need.supplier_summary?.unassessed_supplier_count">{{ $t('Sem avaliação') }}: <span class="font-semibold text-amber-700 dark:text-amber-300">{{ need.supplier_summary.unassessed_supplier_count }}</span></div>
              <div v-if="need.supplier_summary?.conditional_supplier_count">{{ $t('Acompanhamento reforçado') }}: <span class="font-semibold text-cyan-700 dark:text-cyan-300">{{ need.supplier_summary.conditional_supplier_count }}</span></div>
            </div>
            <a
              :href="route('vap-inventory.needs.show', need.id)"
              class="mt-4 inline-flex items-center text-sm font-semibold text-cyan-700 transition hover:text-cyan-800 dark:text-cyan-300 dark:hover:text-cyan-200"
            >
              {{ $t('Abrir necessidade') }}
            </a>
          </div>
        </div>
        <div v-else class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/30 dark:text-gray-400">
          {{ $t('Não existem necessidades aprovadas pendentes de compra neste momento.') }}
        </div>
      </div>

      <div class="card p-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $t('Recepções com desvio formal') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $t('Não conformidades abertas registadas durante o recebimento de encomendas.') }}</p>
          </div>
          <a
            :href="route('vap_non_conformities.index', { category: 'quality' })"
            class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800"
          >
            {{ $t('Abrir NCs') }}
          </a>
        </div>

        <div v-if="props.executive.receiving_non_conformities?.length" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="record in props.executive.receiving_non_conformities"
            :key="record.id"
            class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50"
          >
            <div class="flex flex-wrap items-center gap-2">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold', receptionSeverityClass(record.severity)]">
                {{ receptionSeverityLabel(record.severity) }}
              </span>
              <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-900/40 dark:text-gray-100">
                {{ record.status }}
              </span>
            </div>
            <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ record.title }}</p>
            <p class="mt-1 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ record.nc_number }} · {{ record.department_name || $t('Sem departamento') }}</p>
            <div class="mt-3 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
              <span>{{ record.batch_number || $t('Sem referência') }}</span>
              <span>{{ record.reported_at ? formatShortDate(record.reported_at) : '—' }}</span>
            </div>
          </div>
        </div>
        <div v-else class="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/30 dark:text-gray-400">
          {{ $t('Nenhuma recepção com não conformidade aberta no momento.') }}
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import QuickMenu from '@/Components/quick-menu.vue';
import QuickStats from '@/Components/quick-stats.vue';
import Layout from "@/Shared/Layouts/Layout.vue";
import { computed } from "vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    auth: Object,
    items: Array,
    record: Object,
    stats: Object,
    executive: Object,
    query: Object
});

defineOptions({
  layout: Layout
});

const formatShortDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'
const executiveCharts = computed(() => props.executive?.charts || {})
const safeRoute = (name, params = undefined, fallback = '#') => {
  if (typeof route === 'function') {
    return route(name, params)
  }

  return fallback
}
const dashboardPrimaryActions = computed(() => [
  {
    label: 'Receber amostras',
    hint: 'Entrada individual, fila manual ou Excel.',
    href: safeRoute('vap_samples.index', undefined, '/vap-samples'),
    icon: BeakerIcon,
  },
  {
    label: 'Inserir resultados',
    hint: 'Trabalho técnico, verificação e aprovação.',
    href: safeRoute('analysis.index', undefined, '/analysis'),
    icon: ClipboardDocumentCheckIcon,
  },
  {
    label: 'Emitir documentos',
    hint: 'Certificados, propostas e PDFs premium.',
    href: safeRoute('report-studios.index', undefined, '/report-studios'),
    icon: DocumentTextIcon,
  },
  {
    label: 'Controlar SGQ',
    hint: 'NCs, evidências, competência e melhoria.',
    href: safeRoute('qms.index', undefined, '/qms'),
    icon: ShieldCheckIcon,
  },
])
const dashboardSignalCards = computed(() => [
  {
    label: 'Amostras',
    value: props.stats?.analysis || 0,
    hint: 'Carga técnica visível no cockpit.',
  },
  {
    label: 'Certificados',
    value: props.stats?.certificates || 0,
    hint: 'Documentos emitidos ou controlados.',
  },
  {
    label: 'Clientes',
    value: props.stats?.customers || 0,
    hint: 'Carteira comercial e portal.',
  },
])
const throughputChartSeries = computed(() => executiveCharts.value.throughput?.series || [])
const throughputTotal = computed(() => throughputChartSeries.value.reduce((sum, series) => sum + series.data.reduce((inner, value) => inner + value, 0), 0))
const sampleStatusChartSeries = computed(() => executiveCharts.value.sample_status?.series || [])
const sampleStatusTotal = computed(() => (executiveCharts.value.sample_status?.total || 0))
const portalRequestsChartSeries = computed(() => [{
  name: 'Pedidos',
  data: executiveCharts.value.portal_requests?.series || [],
}] )
const portalRequestTotal = computed(() => (executiveCharts.value.portal_requests?.total || 0))
const procurementReadinessChartSeries = computed(() => executiveCharts.value.procurement_readiness?.series || [])
const procurementReadinessTotal = computed(() => executiveCharts.value.procurement_readiness?.total || 0)
const supplierRiskChartSeries = computed(() => [{
  name: 'Fornecedores',
  data: executiveCharts.value.supplier_risk?.series || [],
}] )
const supplierRiskTotal = computed(() => executiveCharts.value.supplier_risk?.total || 0)
const receivingNcChartSeries = computed(() => [{
  name: 'NCs abertas',
  data: executiveCharts.value.receiving_nc_severity?.series || [],
}] )

const throughputChartOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: { show: false },
    zoom: { enabled: false },
  },
  colors: ['#0f766e', '#1d4ed8', '#c2410c'],
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  markers: {
    size: 5,
    strokeWidth: 0,
  },
  dataLabels: {
    enabled: false,
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: executiveCharts.value.throughput?.categories || [],
    labels: {
      style: {
        colors: '#64748b',
        fontSize: '12px',
      },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: {
        colors: '#64748b',
        fontSize: '12px',
      },
    },
  },
  tooltip: {
    shared: true,
    intersect: false,
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
  },
}))

const sampleStatusChartOptions = computed(() => ({
  labels: executiveCharts.value.sample_status?.labels || [],
  colors: ['#f59e0b', '#2563eb', '#64748b', '#16a34a', '#dc2626'],
  legend: {
    position: 'bottom',
    fontSize: '12px',
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Amostras',
            formatter: () => String(sampleStatusTotal.value),
          },
        },
      },
    },
  },
}))

const portalRequestsChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  colors: ['#0369a1'],
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '48%',
    },
  },
  dataLabels: {
    enabled: true,
  },
  xaxis: {
    categories: executiveCharts.value.portal_requests?.labels || [],
    labels: {
      style: {
        colors: '#64748b',
        fontSize: '12px',
      },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  legend: {
    show: false,
  },
}))

const procurementReadinessChartOptions = computed(() => ({
  labels: executiveCharts.value.procurement_readiness?.labels || [],
  colors: ['#16a34a', '#0891b2', '#d97706', '#dc2626'],
  legend: {
    position: 'bottom',
    fontSize: '12px',
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => `${value.toFixed(0)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Fila',
            formatter: () => String(procurementReadinessTotal.value),
          },
        },
      },
    },
  },
}))

const supplierRiskChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  colors: ['#16a34a', '#0284c7', '#d97706', '#dc2626'],
  plotOptions: {
    bar: {
      horizontal: true,
      distributed: true,
      borderRadius: 6,
      barHeight: '50%',
    },
  },
  dataLabels: {
    enabled: true,
  },
  xaxis: {
    categories: executiveCharts.value.supplier_risk?.labels || [],
    min: 0,
  },
  legend: {
    show: false,
  },
  title: {
    text: 'Fornecedores ativos por risco',
    align: 'left',
    style: {
      fontSize: '13px',
      fontWeight: 600,
    },
  },
}))

const receivingNcChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  colors: ['#059669', '#d97706', '#ea580c', '#be123c'],
  plotOptions: {
    bar: {
      borderRadius: 6,
      columnWidth: '50%',
      distributed: true,
    },
  },
  dataLabels: {
    enabled: true,
  },
  xaxis: {
    categories: executiveCharts.value.receiving_nc_severity?.labels || [],
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
  },
  legend: {
    show: false,
  },
  title: {
    text: 'NCs abertas por severidade',
    align: 'left',
    style: {
      fontSize: '13px',
      fontWeight: 600,
    },
  },
}))

const supplierStatusLabel = (value) => ({
  approved: 'Aprovado',
  conditional: 'Condicional',
  suspended: 'Suspenso',
  rejected: 'Rejeitado',
}[value] || value)

const supplierRiskLabel = (value) => ({
  low: 'Risco baixo',
  medium: 'Risco médio',
  high: 'Risco elevado',
  critical: 'Risco crítico',
}[value] || value)

const supplierStatusClass = (value) => ({
  approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  conditional: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  suspended: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
  rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
}[value] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200')

const supplierRiskClass = (value) => ({
  low: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  medium: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
  high: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  critical: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
}[value] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200')

const procurementUrgencyLabel = (need) => {
  if (!need?.needed_by_date) {
    return 'Sem prazo'
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const neededDate = new Date(need.needed_by_date)
  neededDate.setHours(0, 0, 0, 0)
  const diffDays = Math.round((neededDate.getTime() - today.getTime()) / 86400000)

  if (diffDays < 0) {
    return 'Em atraso'
  }

  if (diffDays <= 3) {
    return 'Urgente'
  }

  if (diffDays <= 10) {
    return 'Próximo'
  }

  return 'Planeado'
}

const procurementCardClass = (need) => {
  const label = procurementUrgencyLabel(need)

  if (label === 'Em atraso') {
    return 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/30'
  }

  if (label === 'Urgente') {
    return 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/30'
  }

  if (label === 'Próximo') {
    return 'border-cyan-200 bg-cyan-50 dark:border-cyan-900/40 dark:bg-cyan-950/30'
  }

  return 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40'
}

const procurementReadinessLabel = (value) => ({
  ready: 'Pronta para compra',
  attention: 'Exige acompanhamento',
  incomplete: 'Dados incompletos',
  blocked: 'Bloqueada por fornecedor',
}[value] || 'Sem avaliação')

const procurementReadinessClass = (value) => ({
  ready: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  attention: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
  incomplete: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  blocked: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
}[value] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200')

const receptionSeverityLabel = (value) => ({
  low: 'Baixa',
  medium: 'Média',
  high: 'Alta',
  critical: 'Crítica',
}[value] || value || 'Acompanhar')

const receptionSeverityClass = (value) => ({
  low: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  medium: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
  critical: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
}[value] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200')
</script>
