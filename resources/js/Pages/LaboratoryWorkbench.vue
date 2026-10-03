<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ClipboardCheck as ClipboardDocumentCheckIcon, ArrowRight as ArrowRightIcon, FlaskConical as BeakerIcon, Pause as PauseIcon, CircleCheck as CheckCircleIcon, Archive as ArchiveBoxIcon, Plus as PlusIcon, Search as MagnifyingGlassIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'
import AnimatedNumber from '@/Components/motion/AnimatedNumber.vue'
import RevealGroup from '@/Components/motion/RevealGroup.vue'

defineOptions({ layout: Layout })
const props = defineProps({ lab: Object, filters: Object, metrics: Object, samples: Object, stockAlerts: Number })
const form = useForm({ search: props.filters.search ?? '', status: props.filters.status ?? '' })
const statuses = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em pausa', COMPLETADO: 'Concluída', CANCELADO: 'Cancelada' }
const tones = { POR_INICIAR: 'received', EN_PROGRESO: 'analysis', EN_PAUSA: 'hold', COMPLETADO: 'complete', CANCELADO: 'hold' }
const activeCount = computed(() => props.metrics.waiting + props.metrics.in_progress + props.metrics.on_hold)
const steps = computed(() => [
  { label: 'Recepção', note: 'Registo e triagem', value: props.metrics.waiting, status: 'POR_INICIAR', tone: 'received' },
  { label: 'Em análise', note: 'Ensaios em execução', value: props.metrics.in_progress, status: 'EN_PROGRESO', tone: 'analysis' },
  { label: 'Em pausa', note: 'Informação em falta', value: props.metrics.on_hold, status: 'EN_PAUSA', tone: 'hold' },
  { label: 'Concluídas', note: 'Análises terminadas', value: props.metrics.completed, status: 'COMPLETADO', tone: 'complete' },
])
const activeSteps = computed(() => steps.value.filter((step) => step.status !== 'COMPLETADO' && step.value > 0))
const statusOptions = [{ value: '', label: 'Todos os estados' }, ...Object.entries(statuses).map(([value, label]) => ({ value, label }))]
const today = new Intl.DateTimeFormat('pt-AO', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date())
const number = value => String(value).padStart(2, '0')
const date = value => value ? new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short' }).format(new Date(value)) : 'Por registar'
function filter(status = form.status) {
  form.status = status
  form.get(route('dashboard'), { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
  <div class="workbench-page">
    <Head title="Visão geral" />
    <header class="app-page-header lab-page-head">
      <div class="app-page-header-row">
        <div><h1 class="app-page-title">{{ lab?.name || 'Visão geral' }}</h1><p class="app-page-meta">O laboratório, em foco.<span aria-hidden="true"> · </span>{{ activeCount }} amostras em curso<span aria-hidden="true"> · </span>{{ today }}</p></div>
        <div class="app-page-actions"><Link v-if="lab" :href="route('vap_samples.index')" class="lab-btn lab-primary"><PlusIcon aria-hidden="true" />Receber amostra</Link></div>
      </div>
    </header>
    <div v-if="!lab" class="lab-panel"><h2>Falta associar o seu laboratório</h2><p class="lab-muted mt-1">Peça ao administrador uma associação. Nenhum dado de outros laboratórios é mostrado nesta vista.</p></div>
    <template v-else>
      <RevealGroup as="section" class="lab-metrics" aria-label="Estado das amostras">
        <div class="lab-metric">
          <div class="lab-metric-title"><span class="lab-metric-icon"><BeakerIcon aria-hidden="true" /></span>Amostras activas</div>
          <AnimatedNumber class="lab-metric-value lab-number" :value="activeCount" :format="number" />
          <div class="lab-flow-bar" aria-hidden="true">
            <span v-for="step in activeSteps" :key="step.status" :data-tone="step.tone" :style="{ flexGrow: step.value }" />
            <span v-if="!activeSteps.length" style="flex-grow: 1" />
          </div>
          <p class="lab-metric-note">Da recepção à conclusão</p>
        </div>
        <button type="button" class="lab-metric" :aria-pressed="form.status === 'EN_PROGRESO'" @click="filter(form.status === 'EN_PROGRESO' ? '' : 'EN_PROGRESO')">
          <span class="lab-metric-title"><span class="lab-metric-icon"><ClipboardDocumentCheckIcon aria-hidden="true" /></span>Em análise</span>
          <AnimatedNumber class="lab-metric-value lab-number" :value="metrics.in_progress" :format="number" />
          <span class="lab-metric-note">Ensaios em execução</span>
        </button>
        <button type="button" class="lab-metric" :aria-pressed="form.status === 'EN_PAUSA'" @click="filter(form.status === 'EN_PAUSA' ? '' : 'EN_PAUSA')">
          <span class="lab-metric-title"><span class="lab-metric-icon"><PauseIcon aria-hidden="true" /></span>Em espera</span>
          <AnimatedNumber class="lab-metric-value lab-number" :value="metrics.on_hold" :format="number" />
          <span class="lab-metric-note" :class="{ 'lab-emphasis': metrics.on_hold }">Amostras que requerem atenção</span>
        </button>
        <div class="lab-metric">
          <div class="lab-metric-title"><span class="lab-metric-icon"><ArchiveBoxIcon aria-hidden="true" /></span>Alertas de existências</div>
          <AnimatedNumber class="lab-metric-value lab-number" :value="stockAlerts" :format="number" />
          <p class="lab-metric-note" :class="{ 'lab-emphasis': stockAlerts }">Posições que precisam de reposição</p>
        </div>
      </RevealGroup>
      <div class="lab-overview-grid">
        <div class="lab-overview-main">
          <section class="lab-queue" aria-labelledby="queue-heading" :aria-busy="form.processing">
            <div class="lab-section-head"><h2 id="queue-heading">Na sua bancada</h2><Link :href="route('vap_samples.queue')" class="lab-link lab-small">Ver todas <ArrowRightIcon aria-hidden="true" /></Link></div>
            <form class="lab-toolbar" @submit.prevent="filter()">
              <div class="min-w-44 max-w-xs flex-1">
                <BaseInput v-model="form.search" type="search" aria-label="Pesquisar amostras" placeholder="Nome ou código da amostra" maxlength="100">
                  <template #leading><MagnifyingGlassIcon aria-hidden="true" /></template>
                </BaseInput>
              </div>
              <div class="w-48">
                <BaseSelect :model-value="form.status" :options="statusOptions" aria-label="Estado da amostra" @update:model-value="filter($event)" />
              </div>
              <button class="lab-btn" type="submit" :disabled="form.processing">{{ form.processing ? 'A pesquisar…' : 'Pesquisar' }}</button>
            </form>
            <div v-if="samples.data.length" class="lab-table-wrap">
              <DataTable class="lab-table">
                <thead><tr><th scope="col">Amostra</th><th scope="col">Estado</th><th scope="col">Recepção</th><th scope="col">Retenção até</th></tr></thead>
                <tbody>
                  <tr v-for="sample in samples.data" :key="sample.id">
                    <td><Link :href="route('vap_samples.show', sample.id)" class="lab-link">{{ sample.code || 'Código por atribuir' }}</Link><small>{{ sample.name }}</small></td>
                    <td><span class="lab-pill" :data-tone="tones[sample.status]"><span class="lab-dot"></span>{{ statuses[sample.status] || sample.status }}</span></td>
                    <td>{{ date(sample.received_at) }}</td>
                    <td>{{ date(sample.retention_due_at) }}</td>
                  </tr>
                </tbody>
              </DataTable>
            </div>
            <div v-else class="lab-empty"><strong>{{ form.search || form.status ? 'Nenhuma amostra neste filtro' : 'A sua bancada está pronta' }}</strong>{{ form.search || form.status ? 'Experimente outro código, nome ou estado.' : 'As amostras recebidas neste laboratório aparecerão aqui.' }}</div>
            <Pagination v-if="samples.total" v-bind="samples" />
          </section>
        </div>
        <div class="lab-overview-main">
          <section class="lab-panel" aria-labelledby="attention-heading">
            <div class="lab-section-head"><h2 id="attention-heading">Precisa da sua atenção</h2></div>
            <div class="lab-task"><span class="lab-task-icon" :class="{ 'lab-warning': metrics.on_hold }"><PauseIcon v-if="metrics.on_hold" aria-hidden="true" /><CheckCircleIcon v-else aria-hidden="true" /></span><div class="lab-spacer"><h3>{{ metrics.on_hold ? metrics.on_hold + ' amostras em espera' : 'Nenhuma amostra em espera' }}</h3><p>{{ metrics.on_hold ? 'Confirme a informação em falta antes de continuar.' : 'As amostras com trabalho suspenso aparecerão aqui.' }}</p><button v-if="metrics.on_hold" type="button" class="lab-link" @click="filter('EN_PAUSA')">Ver amostras em espera <ArrowRightIcon aria-hidden="true" /></button></div></div>
            <div class="lab-task"><span class="lab-task-icon" :class="{ 'lab-warning': stockAlerts }"><ArchiveBoxIcon aria-hidden="true" /></span><div class="lab-spacer"><h3>{{ stockAlerts ? stockAlerts + ' posições de existências a repor' : 'Materiais sem alertas de reposição' }}</h3><p>Consulte as existências dos laboratórios da sua rede.</p><Link v-if="lab.network_id" :href="route('lab-network.index', lab.network_id)" class="lab-link">Encontrar materiais na rede <ArrowRightIcon aria-hidden="true" /></Link></div></div>
          </section>
          <aside class="lab-overview-rail" aria-label="Fluxo de trabalho">
            <div class="lab-section-head"><h2>Fluxo de trabalho</h2><span class="lab-muted lab-small">Cada amostra, no seu lugar</span></div>
            <div class="lab-flow"><button v-for="(step, index) in steps" :key="step.status" class="lab-flow-step" type="button" :aria-pressed="form.status === step.status" @click="filter(form.status === step.status ? '' : step.status)"><span class="lab-flow-circle">{{ index + 1 }}</span><span>{{ step.label }}<small>{{ step.note }}</small></span><b class="lab-number">{{ step.value }}</b></button></div>
            <div class="lab-note"><div class="lab-flex"><PauseIcon aria-hidden="true" /><span>{{ metrics.on_hold }} amostras em espera<br><span class="lab-small">Apenas {{ lab.name }}.</span></span></div></div>
          </aside>
        </div>
      </div>
    </template>
  </div>
</template>
