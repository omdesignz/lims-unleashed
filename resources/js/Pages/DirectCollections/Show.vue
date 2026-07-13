<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Rastreabilidade da colheita</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="statusDotClass" />
              {{ getStatusLabel(data.sample_status || 'pending') }}
            </span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">{{ collectionTitle }}</h1>
          <p class="ds-copy mt-2 text-sm">
            {{ collectionDescription }}
            <span v-if="data.cl" class="font-mono font-bold text-[color:var(--ds-text)]">{{ data.cl }}</span>
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
          <Link :href="collectionEditUrl" class="ds-button ds-button-primary">
            <PencilIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.direct_collections.edit') }}
          </Link>
          <button type="button" class="ds-button ds-button-secondary" @click="router.reload()">
            <ArrowPathRoundedSquareIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.direct_collections.update_status') }}
          </button>
          <Link :href="collectionIndexUrl" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.direct_collections.back') }}
          </Link>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Código laboratorial</dt>
          <dd class="mt-2 truncate font-mono text-sm font-bold text-[color:var(--ds-text)]">{{ data.cl || 'N/D' }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Origem</dt>
          <dd class="mt-2 truncate text-sm font-bold text-[color:var(--ds-text)]">{{ sampleEntry ? 'Sample Entry' : 'Registo legado' }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Quantidade recolhida</dt>
          <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ data.collected_qty || 0 }} / {{ data.qty || 0 }}</dd>
        </div>
        <div class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Conclusão</dt>
          <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ completionPercentage }}%</dd>
        </div>
      </dl>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <CircleStackIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <p class="ds-kicker">Entrada canónica do processo</p>
            <h2 class="ds-heading mt-2 text-base">{{ sampleEntry ? (sampleEntry.code || `Sample Entry #${sampleEntry.id}`) : 'Registo legado de colheita' }}</h2>
            <p class="ds-copy mt-2 max-w-3xl text-xs">
              {{ sampleEntry
                ? 'Produto, matriz, escopo analítico e códigos laboratoriais permanecem ligados à receção da amostra.'
                : 'Este registo antecede o fluxo de Sample Entry. Novas colheitas devem iniciar na receção para garantir rastreabilidade ponta a ponta.' }}
            </p>
          </div>
        </div>
        <Link v-if="sampleEntry" :href="sampleEntry.show_url" class="ds-button ds-button-secondary shrink-0">
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          Abrir Sample Entry
        </Link>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <ClipboardDocumentCheckIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.analysis_progress') }}</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Sequência operacional da colheita à aprovação.</p>
          </div>
        </div>
        <span class="ds-chip">
          <span class="lims-status-dot lims-status-dot-instrument" />
          {{ completionPercentage }}% concluído
        </span>
      </div>

      <div class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] sm:grid-cols-5 sm:divide-y-0">
        <div v-for="step in workflowSteps" :key="step.key" class="relative min-w-0 px-4 py-5">
          <div class="flex items-center gap-3 sm:flex-col sm:items-start">
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center border"
              :class="step.complete
                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300'
                : step.active
                  ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300'
                  : 'border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] text-[color:var(--ds-text-soft)]'"
            >
              <component :is="step.icon" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-bold text-[color:var(--ds-text)]">{{ step.label }}</p>
              <p class="mt-1 truncate text-xs text-[color:var(--ds-text-soft)]">{{ formatDate(step.date) }}</p>
            </div>
          </div>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] border-t border-[color:var(--ds-border)] sm:grid-cols-4 sm:divide-y-0">
        <div v-for="stat in analysisSummary" :key="stat.label" class="px-5 py-4">
          <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ stat.label }}</dt>
          <dd class="mt-2 text-2xl font-bold" :class="stat.valueClass">{{ stat.value }}</dd>
        </div>
      </dl>

      <div v-if="hasScopeControl" class="border-t border-[color:var(--ds-border)] p-5">
        <div class="border-l-4 border-amber-500 bg-amber-50/70 p-4 dark:bg-amber-500/10">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h3 class="text-sm font-bold text-amber-950 dark:text-amber-100">Escopo controlado da receção</h3>
              <p class="mt-1 text-xs leading-5 text-amber-800 dark:text-amber-200">Planeamento analítico e condicionamento herdados da receção.</p>
            </div>
            <span class="ds-chip shrink-0">{{ scopeControl.required_parameter_count || 0 }} parâmetros previstos</span>
          </div>

          <dl class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="border border-amber-200/80 bg-[color:var(--ds-panel)] p-3 dark:border-amber-500/20">
              <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Condicionamento</dt>
              <dd class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ getConditioningLabel(scopeControl.conditioning_status) }}</dd>
              <p v-if="scopeControl.packaging_condition" class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Embalagem: {{ scopeControl.packaging_condition }}</p>
              <p v-if="scopeControl.temperature_condition" class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Temperatura: {{ scopeControl.temperature_condition }}</p>
            </div>
            <div class="border border-amber-200/80 bg-[color:var(--ds-panel)] p-3 dark:border-amber-500/20">
              <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Perfis resolvidos</dt>
              <dd v-if="scopeControl.resolved_profiles?.length" class="mt-2 flex flex-wrap gap-2">
                <span v-for="profile in scopeControl.resolved_profiles" :key="profile.id" class="ds-chip">{{ profile.name }}</span>
              </dd>
              <p v-else class="mt-2 text-xs text-[color:var(--ds-text-soft)]">Nenhum perfil resolvido registado.</p>
            </div>
          </dl>

          <div v-if="scopeControl.required_parameters?.length" class="mt-4">
            <p class="text-xs font-bold uppercase text-amber-900 dark:text-amber-200">Checklist de parâmetros</p>
            <div class="mt-2 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
              <div v-for="parameter in scopeControl.required_parameters" :key="parameter.id" class="border border-amber-200/80 bg-[color:var(--ds-panel)] px-3 py-2 dark:border-amber-500/20">
                <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ parameter.code || 'N/D' }} · {{ parameter.name }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ parameter.profiles?.join(', ') || 'Sem perfil associado' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
      <div class="min-w-0 space-y-4">
        <section class="ds-panel overflow-hidden">
          <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
            <DocumentTextIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.sample_details') }}</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Identificação física e estados do processo.</p>
            </div>
          </div>

          <div class="grid gap-5 p-5 md:grid-cols-[9rem_minmax(0,1fr)]">
            <div>
              <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ $t('gestlab.general.labels.direct_collections.qr_code') }}</p>
              <div class="mt-2 flex aspect-square w-32 items-center justify-center border border-[color:var(--ds-border)] bg-white p-2">
                <img v-if="data.qr" :src="data.qr" alt="QR Code" class="h-full w-full object-contain" />
                <QrCodeIcon v-else class="h-8 w-8 text-[color:var(--ds-text-soft)]" />
              </div>
            </div>

            <div class="min-w-0 space-y-5">
              <dl class="grid gap-3 sm:grid-cols-2">
                <div v-for="field in sampleIdentityFields" :key="field.label" class="border-b border-[color:var(--ds-border)] pb-3">
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
                  <dd class="mt-2 break-words text-sm font-bold text-[color:var(--ds-text)]">{{ field.value || 'N/D' }}</dd>
                </div>
              </dl>

              <div class="grid gap-3 sm:grid-cols-3">
                <div v-for="flag in processFlags" :key="flag.label" class="border-l-4 bg-[color:var(--ds-panel-subtle)] p-3" :class="flag.active ? 'border-emerald-500' : 'border-[color:var(--ds-border-strong)]'">
                  <p class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ flag.label }}</p>
                  <p class="mt-2 text-sm font-bold text-[color:var(--ds-text)]">{{ flag.active ? flag.activeLabel : flag.inactiveLabel }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex items-start gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
            <CircleStackIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.collection_details') }}</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Condições, produto, quantidade e logística da colheita.</p>
            </div>
          </div>

          <dl class="grid gap-x-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="field in collectionDetailFields" :key="field.label" class="border-b border-[color:var(--ds-border)] py-3 first:pt-0">
              <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="mt-2 break-words text-sm font-bold text-[color:var(--ds-text)]">{{ field.value || 'N/D' }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.analysis_results && hasRole('admin')" class="ds-panel overflow-hidden">
          <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4">
            <div class="flex items-start gap-3">
              <BeakerIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.analysis_results') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Resultados e intervalos de referência associados.</p>
              </div>
            </div>
            <span class="ds-chip">{{ data.analysis_results.length }} resultados</span>
          </div>

          <div v-if="data.analysis_results.length" class="divide-y divide-[color:var(--ds-border)]">
            <article v-for="(result, index) in data.analysis_results" :key="result.id || index" class="p-5">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-bold text-[color:var(--ds-text)]">{{ result.parameter_label }}</h3>
                    <span class="ds-chip">
                      <span class="lims-status-dot" :class="getResultStatusDot(result.status)" />
                      {{ getResultStatusLabel(result.status) }}
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ result.unit_label || 'Sem unidade' }}</p>
                </div>
                <p class="font-mono text-xl font-bold text-primary-800 dark:text-primary-200">{{ result.verified_value || result.inserted_value || 'N/D' }}</p>
              </div>
              <dl class="ds-command-toolbar mt-4 grid gap-3 p-3 sm:grid-cols-2">
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Intervalo de referência</dt>
                  <dd class="mt-1 text-sm font-semibold text-[color:var(--ds-text)]">{{ result.min_ref_value || 'N/D' }} - {{ result.max_ref_value || 'N/D' }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">Última atualização</dt>
                  <dd class="mt-1 text-sm font-semibold text-[color:var(--ds-text)]">{{ formatDate(result.updated_at) }}</dd>
                </div>
              </dl>
            </article>
          </div>
          <div v-else class="p-5">
            <div class="ds-empty-state px-5 py-8 text-center">
              <BeakerIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
              <p class="ds-copy mt-2 text-xs">Nenhum resultado registado.</p>
            </div>
          </div>
        </section>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="ds-command-surface p-5">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.actions') }}</p>
          <h2 class="ds-heading mt-2 text-base">Operação analítica</h2>
          <p class="ds-copy mt-2 text-xs">Aceda aos resultados por departamento sem perder o contexto da colheita.</p>

          <div v-if="data.samples?.length" class="mt-4 divide-y divide-[color:var(--ds-border)] border-y border-[color:var(--ds-border)]">
            <div v-for="sample in data.samples" :key="sample.id" class="py-3">
              <p class="truncate text-xs font-bold text-[color:var(--ds-text)]">{{ sample.analysis?.department?.name || 'Análise' }}</p>
              <Link v-if="sample.analysis?.id" :href="route('analysis.edit', sample.analysis.id)" class="ds-button ds-button-primary mt-2 w-full">
                <CheckCircleIcon class="h-4 w-4" />
                Gerir resultados
              </Link>
            </div>
          </div>
          <div v-else class="ds-empty-state mt-4 p-4 text-center">
            <p class="text-xs text-[color:var(--ds-text-soft)]">Sem análises disponíveis.</p>
          </div>
        </section>

        <section class="ds-card p-5">
          <div class="flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.analysis_status') }}</h2>
          </div>
          <div class="mt-4">
            <div class="flex items-center justify-between gap-3 text-xs">
              <span class="font-semibold text-[color:var(--ds-text-soft)]">{{ $t('gestlab.general.labels.direct_collections.completion') }}</span>
              <span class="font-bold text-primary-800 dark:text-primary-200">{{ completionPercentage }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-[color:var(--ds-border)]">
              <div class="h-full bg-primary-600 transition-all duration-500" :style="{ width: completionPercentage + '%' }"></div>
            </div>
          </div>
          <dl class="mt-4 divide-y divide-[color:var(--ds-border)] border-t border-[color:var(--ds-border)]">
            <div v-for="stat in analysisBreakdown" :key="stat.label" class="flex items-center justify-between gap-3 py-3">
              <dt class="flex items-center gap-2 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                <span class="lims-status-dot" :class="stat.dotClass" />
                {{ stat.label }}
              </dt>
              <dd class="text-sm font-bold text-[color:var(--ds-text)]">{{ stat.value }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="data.quality_certificate && hasPermission('view_quality_certificates')" class="ds-card p-5">
          <div class="flex items-center gap-2">
            <DocumentCheckIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.quality_certificate') }}</h2>
          </div>
          <div class="mt-4 border-l-4 p-3" :class="data.quality_certificate.validated_by_id ? 'border-emerald-500 bg-emerald-50/70 dark:bg-emerald-500/10' : 'border-amber-500 bg-amber-50/70 dark:bg-amber-500/10'">
            <div class="flex items-center justify-between gap-3">
              <p class="font-mono text-sm font-bold text-[color:var(--ds-text)]">{{ data.quality_certificate.code }}</p>
              <span class="ds-chip">{{ data.quality_certificate.validated_by_id ? 'Validado' : 'Pendente' }}</span>
            </div>
            <p class="mt-3 text-xs text-[color:var(--ds-text-soft)]">Validado por: {{ data.quality_certificate.validated_by || 'N/D' }}</p>
            <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">Data: {{ formatDate(data.quality_certificate.validated_at) }}</p>
          </div>
        </section>

        <section class="ds-card p-5">
          <div class="flex items-center gap-2">
            <PaperClipIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.direct_collections.documents') }}</h2>
          </div>
          <div v-if="documentLinks.length" class="mt-4 space-y-2">
            <a v-for="document in documentLinks" :key="document.label" :href="document.href" target="_blank" rel="noopener" class="ds-command-palette-item group">
              <component :is="document.icon" class="h-4 w-4 shrink-0 text-primary-700 dark:text-primary-300" />
              <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ document.label }}</span>
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0 text-[color:var(--ds-text-soft)]" />
            </a>
          </div>
          <div v-else class="ds-empty-state mt-4 p-4 text-center">
            <p class="text-xs text-[color:var(--ds-text-soft)]">Nenhum documento disponível.</p>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { usePermission } from '@/Composables/usePermissions'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
  ArrowLeftIcon,
  ArrowPathRoundedSquareIcon,
  ArrowTopRightOnSquareIcon,
  BeakerIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
  CircleStackIcon,
  DocumentCheckIcon,
  DocumentIcon,
  DocumentTextIcon,
  InboxIcon,
  PaperClipIcon,
  PencilIcon,
  QrCodeIcon,
  Square2StackIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'

const { hasRole, hasPermission } = usePermission()

const props = defineProps({
  record: Object,
  collectionPresentation: {
    type: Object,
    default: () => ({}),
  },
})

defineOptions({
  layout: Layout,
})

const data = computed(() => props.record?.data || {})
const presentation = computed(() => props.collectionPresentation || {})
const sampleEntry = computed(() => data.value.sample_entry || null)
const scopeControl = computed(() => data.value.scope_control || {})
const collectionTitle = computed(() => presentation.value.title || 'Colheita direta')
const collectionDescription = computed(() => presentation.value.description || 'Etapa operacional ligada à Sample Entry e ao código laboratorial.')
const collectionIndexUrl = computed(() => presentation.value.index_url || route('directcollections.index'))
const collectionEditUrl = computed(() => presentation.value.edit_url || (data.value.id ? route('directcollections.edit', { collection: data.value.id }) : '#'))
const completionPercentage = computed(() => getCompletionPercentage())
const hasScopeControl = computed(() => Boolean(scopeControl.value.required_parameter_count || scopeControl.value.conditioning_status))

const statusDotClass = computed(() => {
  const statusClasses = {
    pending: 'lims-status-dot-hold',
    in_progress: 'lims-status-dot-instrument',
    completed: 'lims-status-dot-release',
    verified: 'lims-status-dot-instrument',
    approved: 'lims-status-dot-release',
    rejected: 'lims-status-dot-critical',
    cancelled: 'lims-status-dot-critical',
  }

  return statusClasses[data.value.sample_status] || 'lims-status-dot-hold'
})

const workflowSteps = computed(() => [
  { key: 'collection', label: 'Colheita', date: data.value.collection_date, icon: CircleStackIcon, complete: isStepComplete('collection'), active: false },
  { key: 'reception', label: 'Receção', date: data.value.created_at, icon: InboxIcon, complete: isStepComplete('reception'), active: false },
  { key: 'analysis', label: 'Análise', date: data.value.analysis_start_date, icon: BeakerIcon, complete: isStepComplete('analysis'), active: Boolean(data.value.placed_analysis) },
  { key: 'verification', label: 'Verificação', date: data.value.verified_date, icon: CheckCircleIcon, complete: isStepComplete('verification'), active: false },
  { key: 'approval', label: 'Aprovação', date: data.value.approved_date, icon: DocumentCheckIcon, complete: isStepComplete('approval'), active: false },
])

const analysisSummary = computed(() => [
  { label: 'Amostras', value: data.value.total_samples || 0, valueClass: 'text-[color:var(--ds-text)]' },
  { label: 'Concluídas', value: data.value.completed_analysis || 0, valueClass: 'text-emerald-700 dark:text-emerald-300' },
  { label: 'Em curso', value: data.value.in_progress_analysis || 0, valueClass: 'text-amber-700 dark:text-amber-300' },
  { label: 'Pendentes', value: data.value.pending_analysis || 0, valueClass: 'text-primary-800 dark:text-primary-200' },
])

const analysisBreakdown = computed(() => [
  { label: 'Pendentes', value: data.value.pending_analysis || 0, dotClass: 'lims-status-dot-instrument' },
  { label: 'Em curso', value: data.value.in_progress_analysis || 0, dotClass: 'lims-status-dot-hold' },
  { label: 'Concluídas', value: data.value.completed_analysis || 0, dotClass: 'lims-status-dot-release' },
  { label: 'Críticas', value: data.value.critical_analysis || 0, dotClass: 'lims-status-dot-critical' },
])

const sampleIdentityFields = computed(() => [
  { label: 'Código laboratorial', value: data.value.cl },
  { label: 'Tipo', value: data.value.type },
])

const processFlags = computed(() => [
  { label: 'Análise', active: Boolean(data.value.placed_analysis), activeLabel: 'Em análise', inactiveLabel: 'Aguardando' },
  { label: 'Faturação', active: Boolean(data.value.invoiced), activeLabel: 'Faturado', inactiveLabel: 'Por faturar' },
  { label: 'Processamento', active: Boolean(data.value.processed), activeLabel: 'Processado', inactiveLabel: 'Por processar' },
])

const collectionDetailFields = computed(() => [
  { label: 'Data da colheita', value: formatDate(data.value.collection_date) },
  { label: 'Data de validade', value: formatDate(data.value.expiry_date) },
  { label: 'Produto', value: data.value.product },
  { label: 'Marca comercial', value: data.value.comercial_brand },
  { label: 'Quantidade prevista', value: data.value.qty },
  { label: 'Quantidade recolhida', value: data.value.collected_qty },
  { label: 'Temperatura', value: `${data.value.temperature_value || 'N/D'} ${data.value.temperature || '°C'}` },
  { label: 'Localização', value: data.value.location },
  { label: 'Viatura', value: data.value.vehicle },
  { label: 'Embalagem', value: data.value.pack },
  { label: 'Lote', value: data.value.lot },
])

const documentLinks = computed(() => {
  const links = [
    { label: 'Folha de trabalho', href: data.value.links?.pdf_path, icon: DocumentIcon },
    { label: 'Etiquetas', href: data.value.links?.pdf_collection_labels, icon: TagIcon },
    { label: 'Termo de colheita', href: data.value.links?.pdf_collection_term, icon: DocumentTextIcon },
  ]

  if (hasPermission('view_quality_certificates')) {
    links.push({ label: 'Certificado de qualidade', href: data.value.links?.pdf_quality_certificate, icon: Square2StackIcon })
  }

  return links.filter(link => link.href)
})

const getStatusLabel = (status) => {
  const statusLabels = {
    pending: 'Pendente',
    in_progress: 'Em análise',
    completed: 'Completo',
    verified: 'Verificado',
    approved: 'Aprovado',
    rejected: 'Rejeitado',
    cancelled: 'Cancelado',
  }

  return statusLabels[status] || status
}

const getResultStatusDot = (status) => {
  const statusClasses = {
    0: 'lims-status-dot-instrument',
    1: 'lims-status-dot-hold',
    2: 'lims-status-dot-release',
    3: 'lims-status-dot-instrument',
    4: 'lims-status-dot-release',
    5: 'lims-status-dot-critical',
  }

  return statusClasses[status] || 'lims-status-dot-instrument'
}

const getResultStatusLabel = (status) => {
  const statusLabels = {
    0: 'Pendente',
    1: 'Em progresso',
    2: 'Completo',
    3: 'Verificado',
    4: 'Aprovado',
    5: 'Rejeitado',
  }

  return statusLabels[status] || 'Desconhecido'
}

const getConditioningLabel = (status) => {
  const labels = {
    accepted: 'Aceite',
    restricted: 'Aceite com restrições',
    rejected: 'Rejeitado / quarentena',
  }

  return labels[status] || 'Não avaliado'
}

const isStepComplete = (step) => {
  switch (step) {
    case 'collection':
      return Boolean(data.value.collection_date)
    case 'reception':
      return Boolean(data.value.created_at)
    case 'analysis':
      return Boolean(data.value.placed_analysis && data.value.analysis_start_date)
    case 'verification':
      return Boolean(data.value.verified_date)
    case 'approval':
      return Boolean(data.value.approved_date)
    default:
      return false
  }
}

const getCompletionPercentage = () => {
  const steps = ['collection', 'reception', 'analysis', 'verification', 'approval']
  const completedSteps = steps.filter(step => isStepComplete(step)).length

  return Math.round((completedSteps / steps.length) * 100)
}

const formatDate = (date) => {
  if (!date) {
    return 'N/D'
  }

  return new Date(date).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
