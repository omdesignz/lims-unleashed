<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowRightIcon, BeakerIcon, PauseIcon, CheckCircleIcon, ArchiveBoxIcon, PlusIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'

defineOptions({ layout: Layout })
const props = defineProps({ lab: Object, filters: Object, metrics: Object, samples: Object, stockAlerts: Number })
const form = useForm({ search: props.filters.search ?? '', status: props.filters.status ?? '' })
const statuses = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em pausa', COMPLETADO: 'Concluída', CANCELADO: 'Cancelada' }
const tones = { POR_INICIAR: 'received', EN_PROGRESO: 'analysis', EN_PAUSA: 'hold', COMPLETADO: 'complete', CANCELADO: 'hold' }
const activeCount = computed(() => props.metrics.waiting + props.metrics.in_progress + props.metrics.on_hold)
const steps = computed(() => [
  { label: 'Recepção', note: 'Registo e triagem', value: props.metrics.waiting, status: 'POR_INICIAR' },
  { label: 'Em análise', note: 'Ensaios em execução', value: props.metrics.in_progress, status: 'EN_PROGRESO' },
  { label: 'Em pausa', note: 'Informação em falta', value: props.metrics.on_hold, status: 'EN_PAUSA' },
  { label: 'Concluídas', note: 'Análises terminadas', value: props.metrics.completed, status: 'COMPLETADO' },
])
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
    <header class="lab-page-head">
      <div><h1>O laboratório, em foco.</h1><p>{{ today }} <span aria-hidden="true"> · </span> A sua operação de hoje.</p></div>
      <Link v-if="lab" :href="route('vap_samples.index')" class="lab-btn lab-primary"><PlusIcon aria-hidden="true" />Receber amostra</Link>
    </header>
    <div v-if="!lab" class="lab-panel"><h2>Falta associar o seu laboratório</h2><p class="lab-muted">Peça ao administrador uma associação. Nenhum dado de outros laboratórios é mostrado nesta vista.</p></div>
    <template v-else>
      <section class="lab-metrics" aria-label="Estado das amostras">
        <div class="lab-metric"><div class="lab-metric-title"><BeakerIcon />Amostras activas</div><span class="lab-metric-value lab-number">{{ number(activeCount) }}</span><p class="lab-metric-note">Da recepção à conclusão</p></div>
        <button type="button" class="lab-metric" :aria-pressed="form.status === 'EN_PAUSA'" @click="filter(form.status === 'EN_PAUSA' ? '' : 'EN_PAUSA')"><span class="lab-metric-title"><PauseIcon />Em espera</span><span class="lab-metric-value lab-number">{{ number(metrics.on_hold) }}</span><span class="lab-metric-note">Amostras que requerem atenção</span></button>
        <div class="lab-metric"><div class="lab-metric-title"><ArchiveBoxIcon />Alertas de stock</div><span class="lab-metric-value lab-number">{{ number(stockAlerts) }}</span><p class="lab-metric-note" :class="{ 'lab-emphasis': stockAlerts }">Posições que precisam de reposição</p></div>
      </section>
      <div class="lab-overview-grid">
        <div>
          <section class="lab-panel" aria-labelledby="attention-heading">
            <div class="lab-section-head"><h2 id="attention-heading">Precisa da sua atenção</h2><span class="lab-muted lab-small">Próximas acções</span></div>
            <div class="lab-task"><span class="lab-task-icon" :class="{ 'lab-warning': metrics.on_hold }"><PauseIcon v-if="metrics.on_hold" /><CheckCircleIcon v-else /></span><div class="lab-spacer"><h3>{{ metrics.on_hold ? metrics.on_hold + ' amostras em espera' : 'Nenhuma amostra em espera' }}</h3><p>{{ metrics.on_hold ? 'Confirme a informação em falta antes de continuar.' : 'As amostras com trabalho suspenso aparecerão aqui.' }}</p><button v-if="metrics.on_hold" type="button" class="lab-link" @click="filter('EN_PAUSA')">Ver amostras em espera <ArrowRightIcon /></button></div></div>
            <div class="lab-task"><span class="lab-task-icon" :class="{ 'lab-warning': stockAlerts }"><ArchiveBoxIcon /></span><div class="lab-spacer"><h3>{{ stockAlerts ? stockAlerts + ' posições de stock a repor' : 'Materiais sem alertas de reposição' }}</h3><p>Consulte as existências dos laboratórios da sua rede.</p><Link v-if="lab.network_id" :href="route('lab-network.index', lab.network_id)" class="lab-link">Encontrar materiais na rede <ArrowRightIcon /></Link></div></div>
          </section>
          <section class="lab-queue" aria-labelledby="queue-heading" :aria-busy="form.processing">
            <div class="lab-section-head"><h2 id="queue-heading">Na sua bancada</h2><Link :href="route('vap_samples.queue')" class="lab-link lab-small">Ver todas <ArrowRightIcon /></Link></div>
            <form class="lab-toolbar" @submit.prevent="filter()"><label class="lab-inline-search"><MagnifyingGlassIcon aria-hidden="true" /><input v-model="form.search" type="search" aria-label="Pesquisar amostras" placeholder="Nome ou código da amostra" maxlength="100" /></label><select v-model="form.status" class="lab-field" aria-label="Estado da amostra" @change="filter()"><option value="">Todos os estados</option><option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option></select><button class="lab-btn" type="submit" :disabled="form.processing">{{ form.processing ? 'A pesquisar…' : 'Pesquisar' }}</button></form>
            <div v-if="samples.data.length" class="lab-table-wrap"><table class="lab-table"><thead><tr><th scope="col">Amostra</th><th scope="col">Estado</th><th scope="col">Recepção</th><th scope="col">Retenção até</th></tr></thead><tbody><tr v-for="sample in samples.data" :key="sample.id"><td><Link :href="route('vap_samples.show', sample.id)" class="lab-link">{{ sample.code || 'Código por atribuir' }}</Link><small>{{ sample.name }}</small></td><td><span class="lab-pill" :data-tone="tones[sample.status]"><span class="lab-dot"></span>{{ statuses[sample.status] || sample.status }}</span></td><td>{{ date(sample.received_at) }}</td><td>{{ date(sample.retention_due_at) }}</td></tr></tbody></table></div>
            <div v-else class="lab-empty"><strong>{{ form.search || form.status ? 'Nenhuma amostra neste filtro' : 'A sua bancada está pronta' }}</strong>{{ form.search || form.status ? 'Experimente outro código, nome ou estado.' : 'As amostras recebidas neste laboratório aparecerão aqui.' }}</div>
            <Pagination v-if="samples.total" v-bind="samples" />
          </section>
        </div>
        <aside class="lab-overview-rail" aria-label="Fluxo de trabalho">
          <div class="lab-section-head"><h2>Fluxo de trabalho</h2></div><p class="lab-muted lab-small">Cada amostra, no seu lugar.</p>
          <div class="lab-flow"><button v-for="(step, index) in steps" :key="step.status" class="lab-flow-step" type="button" :aria-pressed="form.status === step.status" @click="filter(form.status === step.status ? '' : step.status)"><span class="lab-flow-circle">{{ index + 1 }}</span><span>{{ step.label }}<small>{{ step.note }}</small></span><b class="lab-number">{{ step.value }}</b></button></div>
          <div class="lab-note"><div class="lab-flex"><PauseIcon /><span>{{ metrics.on_hold }} amostras em espera<br><span class="lab-small">Apenas {{ lab.name }}.</span></span></div></div>
        </aside>
      </div>
    </template>
  </div>
</template>
