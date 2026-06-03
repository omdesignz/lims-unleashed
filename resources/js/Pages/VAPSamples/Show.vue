<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Sample Entry Code"
      :title="sample.name"
      description="Esta vista liga a receção da amostra ao fluxo normal de colheita, análises, contra-análises e certificado, para que o código da amostra seja a referência transversal do processo."
    >
      <template #actions>
        <div class="flex flex-wrap gap-3">
          <Link
            :href="route('vap_samples.index')"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar
          </Link>
          <a
            :href="route('vap_samples.samples.pdf', sample.id)"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 dark:bg-primary-500 dark:text-slate-950 dark:hover:bg-primary-400"
          >
            <DocumentArrowDownIcon class="h-4 w-4" />
            PDF da entrada
          </a>
          <a
            v-if="sample.collection_product?.workflow_url"
            :href="sample.collection_product.workflow_url"
            class="inline-flex items-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-900 transition hover:bg-primary-100 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/15"
          >
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
            Fluxo normal
          </a>
          <Link
            v-if="sample.workflow_links?.analysis_queue_url"
            :href="sample.workflow_links.analysis_queue_url"
            class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900 transition hover:bg-sky-100 dark:border-sky-500/25 dark:bg-sky-500/10 dark:text-sky-200 dark:hover:bg-sky-500/15"
          >
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Fila de resultados
          </Link>
          <Link
            v-if="workflowSummary.counter_analysis_count && sample.workflow_links?.counter_analysis_url"
            :href="sample.workflow_links.counter_analysis_url"
            class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 transition hover:bg-amber-100 dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/15"
          >
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Contra-análises
          </Link>
          <a
            v-if="sample.quality_certificate?.pdf_url"
            :href="sample.quality_certificate.pdf_url"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-100 dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/15"
          >
            <ShieldCheckIcon class="h-4 w-4" />
            PDF certificado
          </a>
        </div>
      </template>

      <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-primary-800 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20">
        Código
        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] text-slate-700 dark:bg-slate-900 dark:text-slate-200">
          {{ sample.code }}
        </span>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</p>
          <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">{{ statusLabel(sample.status) }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Lab code</p>
          <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">{{ workflowSummary.linked_lab_code || sample.collection_product?.code || 'Pendente' }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Amostras ligadas</p>
          <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">{{ workflowSummary.linked_sample_count }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Análises</p>
          <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">{{ workflowSummary.analysis_count }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
          <p class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Certificado</p>
          <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">{{ workflowSummary.quality_certificate_ready ? 'Emitido' : 'Ainda não' }}</p>
        </article>
      </div>
    </ModuleHero>

    <section class="grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
      <div class="space-y-6">
        <ModuleCard title="Receção e enquadramento">
          <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Tipo</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sampleTypeLabel(sample.sample_type) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Recebida em</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDateTime(sample.received_at) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Cliente</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.customer?.name || 'Sem cliente' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Recebida por</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.received_by?.name || 'Sem registo' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Laboratório</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.lab?.name || 'Sem laboratório' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Departamento</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.department?.name || 'Sem departamento' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Armazém</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.warehouse?.name || 'Sem armazém' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Embalagem</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.packaging?.name || 'Sem embalagem' }}</dd>
            </div>
          </dl>
          <div v-if="sample.obs" class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-300">
            {{ sample.obs }}
          </div>
        </ModuleCard>

        <ModuleCard title="Rastreabilidade e retenção">
          <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Período de retenção</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.retention_period_days || 0 }} dias</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Estado de retenção</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ retentionLabel(sample.retention_status) }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Prazo de retenção</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.retention_due_at || 'N/D' }}</dd>
            </div>
            <div>
              <dt class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Descarte previsto</dt>
              <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ sample.discard_scheduled_at || 'N/D' }}</dd>
            </div>
          </dl>

          <div v-if="sample.discards?.length" class="mt-5">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Histórico de descarte</h3>
            <div class="mt-3 space-y-3">
              <div
                v-for="discard in sample.discards"
                :key="discard.id"
                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/70"
              >
                <div class="flex items-center justify-between gap-3">
                  <span class="font-medium text-slate-900 dark:text-slate-100">{{ discard.discard_method }}</span>
                  <span class="text-slate-500 dark:text-slate-400">{{ formatDateTime(discard.discarded_at) }}</span>
                </div>
                <p class="mt-1 text-slate-600 dark:text-slate-300">{{ discard.qty }} · {{ discard.discarded_by || 'Sem operador' }}</p>
              </div>
            </div>
          </div>
        </ModuleCard>
      </div>

      <div class="space-y-6">
        <ModuleCard>
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Fluxo ligado</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Receção → colheita → análises → contra-análise → certificado</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
              {{ linkedSampleIds.length }} sample IDs internos
            </span>
          </div>

          <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
              <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Collection product</p>
              <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ sample.collection_product?.id || 'Pendente' }}</p>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ sample.collection_product?.product || 'Ainda não integrado no fluxo normal.' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
              <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Certificado</p>
              <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ sample.quality_certificate?.code || 'Ainda não emitido' }}</p>
              <div class="mt-2 flex flex-wrap gap-3">
                <a
                  v-if="sample.quality_certificate?.show_url"
                  :href="sample.quality_certificate.show_url"
                  class="inline-flex items-center gap-1 text-xs font-semibold text-primary-800 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200"
                >
                  Abrir certificado
                  <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" />
                </a>
                <a
                  v-if="sample.quality_certificate?.pdf_url"
                  :href="sample.quality_certificate.pdf_url"
                  target="_blank"
                  class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-600 dark:text-emerald-300 dark:hover:text-emerald-200"
                >
                  Baixar PDF
                  <DocumentArrowDownIcon class="h-3.5 w-3.5" />
                </a>
              </div>
            </div>
          </div>

          <div class="mt-5 rounded-3xl border border-primary-200 bg-primary-50/70 p-5 dark:border-primary-500/25 dark:bg-primary-500/10">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-800 dark:text-primary-300">Próxima ação operacional</p>
                <h3 class="mt-2 text-base font-semibold text-primary-950 dark:text-primary-100">
                  {{ workflowSummary.next_action?.label || 'Abrir fila operacional' }}
                </h3>
                <p class="mt-1 text-sm leading-6 text-primary-800 dark:text-primary-200">
                  {{ workflowSummary.next_action?.description || 'Continue o processo a partir da fila de resultados.' }}
                </p>
              </div>
              <Link
                v-if="workflowSummary.next_action?.url"
                :href="workflowSummary.next_action.url"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 dark:bg-primary-300 dark:text-primary-950 dark:hover:bg-primary-200"
              >
                Abrir
                <ArrowTopRightOnSquareIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>

          <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
              <thead class="bg-slate-50 dark:bg-slate-900/80">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Análise</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Perfil</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Departamento</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Resultado</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Contra-análise</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950/70">
                <tr v-if="!analyses.length">
                  <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    Ainda não há análises ligadas a esta entrada de amostra.
                  </td>
                </tr>
                <tr v-for="analysis in analyses" :key="analysis.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/70">
                  <td class="px-4 py-4 text-sm">
                    <a :href="analysis.analysis_url" class="font-semibold text-primary-900 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200">
                      #{{ analysis.id }}
                    </a>
                  </td>
                  <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-200">{{ analysis.profile || 'Sem perfil' }}</td>
                  <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-200">{{ analysis.department || 'Sem departamento' }}</td>
                  <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-200">
                    <div class="space-y-1">
                      <p>{{ analysis.result_id ? `#${analysis.result_id}` : 'Pendente' }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ resultStageLabel(analysis.workflow_stage) }}
                      </p>
                      <p v-if="analysis.results_summary?.total" class="text-xs text-slate-500 dark:text-slate-400">
                        {{ analysis.results_summary.approved }}/{{ analysis.results_summary.total }} aprovados · {{ analysis.results_summary.with_uncertainty }} c/ incerteza
                      </p>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-sm">
                    <div v-if="analysis.counter_analysis_items?.length" class="space-y-2">
                      <div
                        v-for="counterAnalysis in analysis.counter_analysis_items"
                        :key="`${analysis.id}-${counterAnalysis.result_id}`"
                      >
                        <a
                          v-if="counterAnalysis.counter_analysis_url"
                          :href="counterAnalysis.counter_analysis_url"
                          class="font-semibold text-amber-700 hover:text-amber-600 dark:text-amber-300 dark:hover:text-amber-200"
                        >
                          #{{ counterAnalysis.counter_analysis_id }}
                        </a>
                        <span
                          v-else
                          class="inline-flex rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/25"
                        >
                          Solicitada
                        </span>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                          {{ counterAnalysis.parameter || `Resultado #${counterAnalysis.result_id}` }}
                        </p>
                      </div>
                    </div>
                    <span v-else class="text-slate-500 dark:text-slate-400">Não aberta</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </ModuleCard>

        <ModuleCard title="Origem e contexto submetido">
          <div v-if="isInternalQcSample" class="mt-5 rounded-3xl border border-emerald-200 bg-emerald-50/80 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:text-emerald-300">
                  CQ interno
                </p>
                <h3 class="mt-2 text-base font-semibold text-emerald-950 dark:text-emerald-100">
                  {{ qualityControlPath.name || 'Controlo interno de matéria-prima' }}
                </h3>
                <p class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200">
                  Esta amostra segue o fluxo normal de análise sem proposta comercial, mantendo rastreabilidade para lab code, resultados, verificação, aprovação e relatório.
                </p>
              </div>
              <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200 dark:bg-slate-950/50 dark:text-emerald-200 dark:ring-emerald-500/30">
                {{ disciplineLabel(sample.client_submitted_info?.analysis_discipline) }}
              </span>
            </div>

            <div
              class="mt-5 rounded-2xl border p-4"
              :class="releaseGatePanelClass(releaseGate.status)"
            >
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-80">Gate de liberação</p>
                  <h4 class="mt-1 text-base font-bold">{{ releaseGate.label || 'Aguardar decisão' }}</h4>
                  <p class="mt-1 text-sm leading-6">{{ releaseGate.message || 'A decisão operacional será apresentada quando houver dados suficientes.' }}</p>
                </div>
                <span
                  class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-bold"
                  :class="releaseGateBadgeClass(releaseGate.status)"
                >
                  {{ releaseGateStatusLabel(releaseGate.status) }}
                </span>
              </div>

              <dl class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white/55 px-3 py-2 dark:bg-slate-950/30">
                  <dt class="text-[11px] uppercase tracking-[0.16em] opacity-75">Resultados</dt>
                  <dd class="mt-1 text-sm font-bold">{{ releaseGate.totals?.approved || 0 }}/{{ releaseGate.totals?.results || 0 }} aprovados</dd>
                </div>
                <div class="rounded-xl bg-white/55 px-3 py-2 dark:bg-slate-950/30">
                  <dt class="text-[11px] uppercase tracking-[0.16em] opacity-75">Incerteza</dt>
                  <dd class="mt-1 text-sm font-bold">{{ releaseGate.totals?.with_uncertainty || 0 }} resultados</dd>
                </div>
                <div class="rounded-xl bg-white/55 px-3 py-2 dark:bg-slate-950/30">
                  <dt class="text-[11px] uppercase tracking-[0.16em] opacity-75">Contra-análise</dt>
                  <dd class="mt-1 text-sm font-bold">{{ releaseGate.totals?.counter_analysis_requested || 0 }}</dd>
                </div>
                <div class="rounded-xl bg-white/55 px-3 py-2 dark:bg-slate-950/30">
                  <dt class="text-[11px] uppercase tracking-[0.16em] opacity-75">Decisão configurada</dt>
                  <dd class="mt-1 text-sm font-bold">{{ qcDecisionLabel(releaseGate.decision) }}</dd>
                </div>
              </dl>

              <div v-if="latestReleaseDecision" class="mt-4 rounded-2xl border border-white/50 bg-white/70 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/40">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] opacity-75">Última decisão registada</p>
                    <p class="mt-1 text-sm font-bold">{{ finalQcDecisionLabel(latestReleaseDecision.decision) }}</p>
                    <p v-if="latestReleaseDecision.notes" class="mt-2 text-sm leading-6 opacity-80">{{ latestReleaseDecision.notes }}</p>
                  </div>
                  <p class="text-xs font-semibold opacity-75">
                    {{ latestReleaseDecision.decided_by_name || 'Utilizador' }} · {{ formatDateTime(latestReleaseDecision.decided_at) }}
                  </p>
                </div>
              </div>

              <form class="mt-4 rounded-2xl border border-white/50 bg-white/75 p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/45" @submit.prevent="submitQcDecision">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p class="text-sm font-bold">Registar decisão operacional</p>
                    <p class="mt-1 text-xs leading-5 opacity-75">Use este ponto apenas depois de a equipa técnica validar resultados, incerteza e eventual contra-análise.</p>
                  </div>
                  <button
                    type="submit"
                    :disabled="qcDecisionForm.processing || releaseDecisionBlocked"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                  >
                    {{ qcDecisionForm.processing ? 'A registar...' : 'Guardar decisão' }}
                  </button>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
                  <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] opacity-75">Decisão final</span>
                    <select
                      v-model="qcDecisionForm.decision"
                      class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm outline-none transition focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                      <option v-for="option in releaseDecisionOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                    <p v-if="qcDecisionForm.errors.decision" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-300">{{ qcDecisionForm.errors.decision }}</p>
                    <p v-else-if="releaseDecisionBlocked" class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">
                      A liberação fica bloqueada até os resultados estarem aprovados e sem revisão pendente.
                    </p>
                  </label>
                  <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] opacity-75">Notas de decisão</span>
                    <textarea
                      v-model="qcDecisionForm.notes"
                      rows="3"
                      placeholder="Ex.: lote retido para investigação, liberado para produção, ou registado apenas para tendência..."
                      class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/15 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                    />
                    <p v-if="qcDecisionForm.errors.notes" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-300">{{ qcDecisionForm.errors.notes }}</p>
                  </label>
                </div>
              </form>
            </div>

            <dl class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Objetivo</dt>
                <dd class="mt-1 text-sm font-semibold text-emerald-950 dark:text-emerald-100">{{ qcPurposeLabel(sample.client_submitted_info?.quality_control_purpose) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Decisão</dt>
                <dd class="mt-1 text-sm font-semibold text-emerald-950 dark:text-emerald-100">{{ qcDecisionLabel(sample.client_submitted_info?.qc_decision) }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Lote</dt>
                <dd class="mt-1 text-sm font-semibold text-emerald-950 dark:text-emerald-100">{{ sample.client_submitted_info?.lot || 'N/D' }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Fornecedor</dt>
                <dd class="mt-1 text-sm font-semibold text-emerald-950 dark:text-emerald-100">{{ sample.client_submitted_info?.supplier_name || 'N/D' }}</dd>
              </div>
            </dl>
          </div>

          <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
              <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Origem</p>
              <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ sample.client_submitted_info?.request_origin || 'client' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/70">
              <p class="text-xs uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Pedido portal</p>
              <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ sample.portal_request?.reference || 'Sem pedido portal' }}</p>
            </div>
          </div>
          <pre class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-slate-950 px-4 py-4 text-xs text-slate-100 dark:border-slate-800">{{ formattedClientInfo }}</pre>
        </ModuleCard>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, ArrowTopRightOnSquareIcon, ClipboardDocumentListIcon, DocumentArrowDownIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })

const props = defineProps({
  sample: { type: Object, required: true },
  analyses: { type: Array, default: () => [] },
  linkedSampleIds: { type: Array, default: () => [] },
  workflowSummary: { type: Object, default: () => ({}) },
})

const formattedClientInfo = computed(() => JSON.stringify(props.sample.client_submitted_info || {}, null, 2))
const qualityControlPath = computed(() => props.sample.client_submitted_info?.quality_control_path || {})
const releaseGate = computed(() => props.workflowSummary?.quality_control_release || {})
const latestReleaseDecision = computed(() => releaseGate.value?.current_decision || null)
const isInternalQcSample = computed(() => (
  props.sample.client_submitted_info?.request_origin === 'internal'
  && ['MATERIA_PRIMA', 'RAW_MATERIAL'].includes(props.sample.sample_type)
))
const releaseDecisionOptions = [
  { value: 'released', label: 'Liberada para uso' },
  { value: 'quarantined', label: 'Manter em quarentena' },
  { value: 'investigation_required', label: 'Abrir investigação' },
  { value: 'rejected', label: 'Rejeitada' },
  { value: 'trend_recorded', label: 'Registar para tendência' },
]
const qcDecisionForm = useForm({
  decision: latestReleaseDecision.value?.decision || 'released',
  notes: '',
})
const releaseDecisionBlocked = computed(() => qcDecisionForm.decision === 'released' && !releaseGate.value?.can_release)

const submitQcDecision = () => {
  if (releaseDecisionBlocked.value) {
    return
  }

  qcDecisionForm.patch(
    route('vap_samples.samples.internal-quality-control-decision', { sampleEntry: props.sample.id }),
    {
      preserveScroll: true,
      onSuccess: () => qcDecisionForm.reset('notes'),
    },
  )
}

const formatDateTime = (value) => {
  if (!value) return 'N/D'
  return new Date(value).toLocaleString('pt-PT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const statusLabel = (status) => ({
  POR_INICIAR: 'Por iniciar',
  EN_PROGRESO: 'Em progresso',
  EN_PAUSA: 'Em pausa',
  COMPLETADO: 'Completado',
  CANCELADO: 'Cancelado',
}[status] || status || 'N/D')

const sampleTypeLabel = (type) => ({
  ROTINA: 'Rotina',
  MATERIA_PRIMA: 'Matéria-prima',
  PRODUTO_ACABADO: 'Produto acabado',
  ESTABILIDADE: 'Estabilidade',
  CONTRAPROVA: 'Contraprova',
  COUNTER_ANALYSIS: 'Contra-análise',
  INTERLABORATORIAL: 'Interlaboratorial',
  RETENCAO: 'Retenção',
}[type] || type || 'N/D')

const disciplineLabel = (discipline) => ({
  microbiology: 'Microbiologia',
  chemistry: 'Química / físico-química',
  microbiology_and_chemistry: 'Microbiologia + química',
}[discipline] || 'Disciplina não definida')

const qcPurposeLabel = (purpose) => ({
  raw_material_release: 'Liberação de matéria-prima',
  supplier_qualification: 'Qualificação de fornecedor',
  process_validation: 'Validação de processo',
  stability_follow_up: 'Acompanhamento de estabilidade',
  investigation: 'Investigação interna',
  other: 'Outro',
}[purpose] || 'N/D')

const qcDecisionLabel = (decision) => ({
  hold_until_release: 'Reter até liberação',
  release_if_compliant: 'Liberar se conforme',
  investigate_before_release: 'Investigar antes de liberar',
  trend_only: 'Apenas tendência',
}[decision] || 'N/D')

const finalQcDecisionLabel = (decision) => ({
  released: 'Liberada para uso',
  rejected: 'Rejeitada',
  quarantined: 'Em quarentena',
  investigation_required: 'Investigação requerida',
  trend_recorded: 'Registada para tendência',
}[decision] || 'Decisão registada')

const resultStageLabel = (stage) => ({
  pending_results: 'Sem resultados inseridos',
  insertion: 'Inserção pendente',
  verification: 'Verificação pendente',
  approval: 'Aprovação pendente',
  approved: 'Resultados aprovados',
}[stage] || 'Estado não definido')

const releaseGateStatusLabel = (status) => ({
  pending_results: 'Retida',
  awaiting_approval: 'Em validação',
  requires_review: 'Revisão técnica',
  ready_for_release: 'Liberável',
  not_applicable: 'N/A',
}[status] || 'Em avaliação')

const releaseGatePanelClass = (status) => ({
  pending_results: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
  awaiting_approval: 'border-blue-200 bg-blue-50 text-blue-950 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-100',
  requires_review: 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100',
  ready_for_release: 'border-emerald-200 bg-emerald-100/80 text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-100',
}[status] || 'border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100')

const releaseGateBadgeClass = (status) => ({
  pending_results: 'bg-amber-600 text-white dark:bg-amber-400 dark:text-amber-950',
  awaiting_approval: 'bg-blue-600 text-white dark:bg-blue-400 dark:text-blue-950',
  requires_review: 'bg-rose-600 text-white dark:bg-rose-400 dark:text-rose-950',
  ready_for_release: 'bg-emerald-700 text-white dark:bg-emerald-400 dark:text-emerald-950',
}[status] || 'bg-slate-700 text-white dark:bg-slate-200 dark:text-slate-950')

const retentionLabel = (status) => ({
  active: 'Ativa',
  due_soon: 'Próxima do descarte',
  overdue: 'Vencida',
}[status] || status || 'N/D')
</script>
