<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ArrowRight as ArrowRightIcon,
  FlaskConical as BeakerIcon,
  CircleCheck as CheckCircleIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Clock as ClockIcon,
  FileCheck as DocumentCheckIcon,
  Search as MagnifyingGlassIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  dossiers: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stageOptions: {
    type: Array,
    default: () => [],
  },
})

const search = ref(props.filters.search || '')
const stage = ref(props.filters.stage || '')

const metrics = computed(() => [
  { label: 'Dossiers activos', value: props.stats.total || 0, note: 'propostas aceites', icon: ClipboardDocumentCheckIcon, tone: 'instrument' },
  { label: 'Aguardar amostra', value: props.stats.awaiting_samples || 0, note: 'sem recepção associada', icon: ClockIcon, tone: 'hold' },
  { label: 'Em execução', value: props.stats.in_execution || 0, note: 'recepção a aprovação', icon: BeakerIcon, tone: 'review' },
  { label: 'Emitidos', value: props.stats.issued || 0, note: `${props.stats.awaiting_release || 0} por libertar`, icon: DocumentCheckIcon, tone: 'release' },
])

function applyFilters() {
  router.get(route('laboratory-workflow.index'), {
    search: search.value || undefined,
    stage: stage.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearFilters() {
  search.value = ''
  stage.value = ''
  applyFilters()
}

function stageClass(tone) {
  return {
    hold: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-100',
    instrument: 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-400/30 dark:bg-sky-500/10 dark:text-sky-100',
    review: 'border-violet-300 bg-violet-50 text-violet-900 dark:border-violet-400/30 dark:bg-violet-500/10 dark:text-violet-100',
    release: 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100',
    complete: 'border-teal-300 bg-teal-50 text-teal-900 dark:border-teal-400/30 dark:bg-teal-500/10 dark:text-teal-100',
  }[tone] || 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text)]'
}

function formatDate(value) {
  if (!value) return 'Sem data'

  return new Intl.DateTimeFormat('pt-AO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value))
}
</script>

<template>
  <Head title="Fluxo laboratorial" />

  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <p class="ds-kicker">Operação ponta a ponta</p>
          <h1 class="ds-heading mt-2 text-2xl">Fluxo laboratorial</h1>
          <p class="ds-copy mt-2 text-sm">
            Dossiers aceites pelo cliente, acompanhados da recepção da amostra até à validação do boletim.
          </p>
        </div>

        <div class="lims-status-strip flex items-center gap-3 px-4 py-3">
          <span class="lims-status-dot lims-status-dot-instrument" />
          <div>
            <p class="text-xs font-bold text-[var(--ds-text)]">Fila operacional única</p>
            <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">Cada dossier mostra a próxima acção exacta</p>
          </div>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[var(--ds-border)] lg:grid-cols-4 lg:divide-y-0">
        <div v-for="metric in metrics" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
            <component :is="metric.icon" class="h-4 w-4" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.note }}</p>
        </div>
      </dl>
    </section>

    <section class="ds-panel overflow-hidden">
      <form class="grid gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:grid-cols-[minmax(0,1fr)_15rem_auto] sm:items-end" @submit.prevent="applyFilters">
        <div class="ds-field-group">
          <label for="workflow-search" class="ds-field-label">Pesquisar dossier</label>
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput id="workflow-search" v-model="search" type="search" class="ds-field w-full pl-9" placeholder="Proposta, cliente ou local" />
          </div>
        </div>
        <div class="ds-field-group">
          <label for="workflow-stage" class="ds-field-label">Etapa actual</label>
          <BaseSelect id="workflow-stage" v-model="stage" class="ds-field w-full">
            <option value="">Todas as etapas</option>
            <option v-for="option in stageOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </BaseSelect>
        </div>
        <div class="flex items-center gap-2">
          <button type="submit" class="ds-button ds-button-primary">Aplicar</button>
          <button v-if="search || stage" type="button" class="ds-icon-button" title="Limpar filtros" @click="clearFilters">
            <XMarkIcon class="h-4 w-4" />
            <span class="sr-only">Limpar filtros</span>
          </button>
        </div>
      </form>

      <div v-if="dossiers.length" class="divide-y divide-[var(--ds-border)]">
        <article v-for="dossier in dossiers" :key="dossier.id" class="p-5 lg:p-6">
          <div class="grid gap-5 xl:grid-cols-[minmax(15rem,0.8fr)_minmax(28rem,1.6fr)_18rem] xl:items-start">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <Link :href="dossier.links.proposal_url" class="font-mono text-sm font-bold text-primary-800 hover:underline dark:text-primary-200">
                  {{ dossier.proposal_number }}
                </Link>
                <span :class="['inline-flex border px-2.5 py-1 text-xs font-bold', stageClass(dossier.stage.tone)]">
                  {{ dossier.stage.label }}
                </span>
              </div>
              <h2 class="mt-3 truncate text-base font-bold text-[var(--ds-text)]" :title="dossier.customer">{{ dossier.customer }}</h2>
              <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]" :title="dossier.service_location">
                {{ dossier.department || 'Sem departamento' }} · {{ dossier.service_location || 'Sem local' }}
              </p>
              <p class="mt-3 text-xs leading-5 text-[var(--ds-text-soft)]">Aceite em {{ formatDate(dossier.accepted_at) }}</p>
            </div>

            <div class="min-w-0">
              <ol class="grid grid-cols-6 gap-1" :aria-label="`Etapas de ${dossier.proposal_number}`">
                <li v-for="(step, index) in dossier.steps" :key="step.key" class="min-w-0 text-center">
                  <div class="flex items-center">
                    <span v-if="index > 0" :class="['h-px flex-1', step.status === 'pending' ? 'bg-[var(--ds-border)]' : 'bg-emerald-500']" />
                    <span
                      :class="[
                        'flex h-7 w-7 shrink-0 items-center justify-center border text-[10px] font-bold',
                        step.status === 'complete'
                          ? 'border-emerald-600 bg-emerald-600 text-white'
                          : step.status === 'current'
                            ? 'border-primary-600 bg-primary-50 text-primary-800 ring-2 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-100 dark:ring-primary-500/20'
                            : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel)] text-[var(--ds-text-soft)]'
                      ]"
                    >
                      <CheckCircleIcon v-if="step.status === 'complete'" class="h-4 w-4" />
                      <span v-else>{{ index + 1 }}</span>
                    </span>
                    <span v-if="index < dossier.steps.length - 1" :class="['h-px flex-1', step.status === 'complete' ? 'bg-emerald-500' : 'bg-[var(--ds-border)]']" />
                  </div>
                  <span class="mt-2 block truncate text-[10px] font-bold text-[var(--ds-text-soft)]" :title="step.label">{{ step.label }}</span>
                </li>
              </ol>

              <dl class="mt-5 grid grid-cols-4 divide-x divide-[var(--ds-border)] border-y border-[var(--ds-border)] py-3">
                <div class="px-3 first:pl-0">
                  <dt class="text-[10px] font-bold uppercase text-[var(--ds-text-soft)]">Amostras</dt>
                  <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ dossier.counts.samples }}</dd>
                </div>
                <div class="px-3">
                  <dt class="text-[10px] font-bold uppercase text-[var(--ds-text-soft)]">Análises</dt>
                  <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ dossier.counts.analyses }}</dd>
                </div>
                <div class="px-3">
                  <dt class="text-[10px] font-bold uppercase text-[var(--ds-text-soft)]">Aprovados</dt>
                  <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ dossier.counts.approved_results }}/{{ dossier.counts.results }}</dd>
                </div>
                <div class="px-3">
                  <dt class="text-[10px] font-bold uppercase text-[var(--ds-text-soft)]">Boletins</dt>
                  <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ dossier.counts.validated_reports }}/{{ dossier.counts.reports }}</dd>
                </div>
              </dl>
            </div>

            <aside class="border-l-4 border-primary-500 bg-[var(--ds-panel-subtle)] p-4">
              <p class="text-xs font-bold text-[var(--ds-text)]">{{ dossier.primary_action.label }}</p>
              <p class="mt-1 text-xs leading-5 text-[var(--ds-text-muted)]">{{ dossier.primary_action.description }}</p>
              <p v-if="dossier.blockers.length" class="mt-3 text-xs font-semibold leading-5 text-amber-800 dark:text-amber-200">
                {{ dossier.blockers[0] }}
              </p>

              <button v-if="dossier.primary_action.disabled" type="button" class="ds-button ds-button-secondary mt-4 w-full" disabled>
                {{ dossier.primary_action.label }}
              </button>
              <Link
                v-else-if="dossier.primary_action.method === 'post'"
                :href="dossier.primary_action.url"
                method="post"
                as="button"
                class="ds-button ds-button-primary mt-4 w-full"
              >
                {{ dossier.primary_action.label }}
                <ArrowRightIcon class="h-4 w-4" />
              </Link>
              <Link v-else :href="dossier.primary_action.url" class="ds-button ds-button-primary mt-4 w-full">
                {{ dossier.primary_action.label }}
                <ArrowRightIcon class="h-4 w-4" />
              </Link>
            </aside>
          </div>
        </article>
      </div>

      <div v-else class="px-5 py-16 text-center">
        <ClipboardDocumentCheckIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
        <h2 class="ds-heading mt-4 text-base">Nenhum dossier nesta fila</h2>
        <p class="ds-copy mx-auto mt-2 max-w-lg text-sm">Ajuste os filtros ou aguarde a aceitação de uma proposta comercial.</p>
      </div>
    </section>
  </div>
</template>
