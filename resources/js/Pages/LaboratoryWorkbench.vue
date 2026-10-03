<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ChevronRight as ChevronRightIcon, Search as MagnifyingGlassIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'
import AnimatedNumber from '@/Components/motion/AnimatedNumber.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

defineOptions({ layout: Layout })
const props = defineProps({ lab: Object, filters: Object, metrics: Object, samples: Object, stockAlerts: Number })
const form = useForm({ search: props.filters.search ?? '', status: props.filters.status ?? '' })
const statuses = { POR_INICIAR: 'Por iniciar', EN_PROGRESO: 'Em análise', EN_PAUSA: 'Em espera', COMPLETADO: 'Concluída', CANCELADO: 'Cancelada' }
const tones = { POR_INICIAR: 'neutral', EN_PROGRESO: 'run', EN_PAUSA: 'wait', COMPLETADO: 'ok', CANCELADO: 'done' }
const activeCount = computed(() => props.metrics.waiting + props.metrics.in_progress + props.metrics.on_hold)
const flow = computed(() => [
  { label: 'Por iniciar', value: props.metrics.waiting, status: 'POR_INICIAR' },
  { label: 'Em análise', value: props.metrics.in_progress, status: 'EN_PROGRESO' },
  { label: 'Em espera', value: props.metrics.on_hold, status: 'EN_PAUSA', late: true },
  { label: 'Concluídas', value: props.metrics.completed, status: 'COMPLETADO' },
])
const flowMax = computed(() => Math.max(1, ...flow.value.map((step) => step.value)))
const statusOptions = [{ value: '', label: 'Todos os estados' }, ...Object.entries(statuses).map(([value, label]) => ({ value, label }))]
const today = new Intl.DateTimeFormat('pt-AO', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' }).format(new Date()).replace(/,/g, ' ·')
const number = value => String(value)
const date = value => value ? new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short' }).format(new Date(value)) : 'Por registar'

// One sentence that says what needs attention now, from the laboratory's own counts.
const lede = computed(() => {
  const parts = []
  parts.push(props.metrics.waiting === 1 ? '1 amostra espera início' : `${props.metrics.waiting} amostras esperam início`)
  parts.push(props.metrics.on_hold === 1 ? '1 está em espera' : `${props.metrics.on_hold} estão em espera`)
  const sentence = `${parts.join(' e ')}.`

  return props.stockAlerts ? `${sentence} ${props.stockAlerts === 1 ? 'Há 1 posição de existências a repor.' : `Há ${props.stockAlerts} posições de existências a repor.`}` : sentence
})

function filter(status = form.status) {
  form.status = status
  form.get(route('dashboard'), { preserveState: true, preserveScroll: true, replace: true })
}

const toggle = (status) => filter(form.status === status ? '' : status)
</script>

<template>
  <div class="pl-today" data-template="today">
    <Head title="Hoje" />
    <div class="pl-band">
      <span class="pl-k">{{ today }}</span>
      <span class="pl-k">{{ lab?.network_name || 'Laboratório' }}</span>
    </div>
    <section class="pl-hero" aria-labelledby="today-title">
      <div class="flex justify-between gap-4"><span class="pl-k pl-muted">{{ lab?.name || 'Sem laboratório associado' }}</span><span class="pl-k pl-muted">{{ activeCount }} amostras em curso</span></div>
      <div class="pl-hero-row">
        <h1 id="today-title" class="pl-d0">Hoje</h1>
        <p v-if="lab" class="pl-lede max-w-[400px]">{{ lede }}</p>
        <p v-else class="pl-lede max-w-[400px]">Falta associar o seu laboratório. Peça ao administrador uma associação; nenhum dado de outros laboratórios é mostrado nesta vista.</p>
      </div>
    </section>

    <div v-if="lab" class="pl-today-body">
      <section aria-labelledby="waiting-heading">
        <p id="waiting-heading" class="pl-k pl-muted pl-prompt mb-3.5">À sua espera</p>
        <div class="pl-work">
          <button type="button" class="pl-job text-left" :data-lead="form.status === 'POR_INICIAR'" :aria-pressed="form.status === 'POR_INICIAR'" @click="toggle('POR_INICIAR')">
            <span class="pl-k">Por iniciar</span>
            <AnimatedNumber class="pl-job-value" :value="metrics.waiting" :format="number" />
            <span class="pl-job-foot"><span class="pl-muted">Recebidas, à espera de análise</span><ChevronRightIcon aria-hidden="true" /></span>
          </button>
          <button type="button" class="pl-job text-left" :data-lead="form.status === 'EN_PROGRESO'" :aria-pressed="form.status === 'EN_PROGRESO'" @click="toggle('EN_PROGRESO')">
            <span class="pl-k">Em análise</span>
            <AnimatedNumber class="pl-job-value" :value="metrics.in_progress" :format="number" />
            <span class="pl-job-foot"><span class="pl-muted">Ensaios em execução</span><ChevronRightIcon aria-hidden="true" /></span>
          </button>
          <button type="button" class="pl-job text-left" :data-lead="form.status === 'EN_PAUSA'" :aria-pressed="form.status === 'EN_PAUSA'" @click="toggle('EN_PAUSA')">
            <span class="pl-k" :class="{ 'text-[var(--pl-warn)]': metrics.on_hold && form.status !== 'EN_PAUSA' }">Em espera</span>
            <AnimatedNumber class="pl-job-value" :value="metrics.on_hold" :format="number" />
            <span class="pl-job-foot"><span class="pl-muted">{{ metrics.on_hold ? 'Informação em falta' : 'Nada suspenso' }}</span><ChevronRightIcon aria-hidden="true" /></span>
          </button>
          <component :is="lab.network_id ? Link : 'div'" :href="lab.network_id ? route('lab-network.index', lab.network_id) : undefined" class="pl-job">
            <span class="pl-k" :class="{ 'text-[var(--pl-bad)]': stockAlerts }">Existências a repor</span>
            <AnimatedNumber class="pl-job-value" :value="stockAlerts" :format="number" />
            <span class="pl-job-foot"><span class="pl-muted">{{ lab.network_id ? 'Procurar na rede' : 'Posições abaixo do mínimo' }}</span><ChevronRightIcon v-if="lab.network_id" aria-hidden="true" /></span>
          </component>
        </div>
      </section>

      <div class="pl-today-grid">
        <section aria-labelledby="queue-heading" :aria-busy="form.processing">
          <div class="mb-3.5 flex items-center justify-between gap-4"><h2 id="queue-heading" class="pl-d3">Na sua bancada</h2><Link :href="route('vap_samples.queue')" class="pl-k pl-acc">Ver fila completa →</Link></div>
          <form class="pl-filter" @submit.prevent="filter()">
            <label for="today-search" class="pl-filter-prompt">Filtro://</label>
            <BaseInput id="today-search" v-model="form.search" type="search" data-bare class="pl-filter-input" placeholder="código ou nome da amostra" maxlength="100" />
            <div class="w-48"><BaseSelect :model-value="form.status" :options="statusOptions" aria-label="Estado da amostra" @update:model-value="filter($event)" /></div>
            <button class="ds-button ds-button-quiet" type="submit" :disabled="form.processing"><MagnifyingGlassIcon aria-hidden="true" />{{ form.processing ? 'A procurar…' : 'Procurar' }}</button>
          </form>
          <div class="pl-panel">
            <DataTable v-if="samples.data.length">
              <thead><tr><th scope="col">Código</th><th scope="col">Amostra</th><th scope="col">Estado</th><th scope="col" class="text-right">Recepção</th><th scope="col" class="text-right">Retenção até</th></tr></thead>
              <tbody>
                <tr v-for="sample in samples.data" :key="sample.id">
                  <td><Link :href="route('vap_samples.show', sample.id)" class="pl-num font-medium hover:text-[var(--pl-accent-text)]">{{ sample.code || 'Por atribuir' }}</Link></td>
                  <td class="max-w-[16rem] truncate">{{ sample.name }}</td>
                  <td><StatusChip :tone="tones[sample.status]">{{ statuses[sample.status] || sample.status }}</StatusChip></td>
                  <td class="pl-num text-right">{{ date(sample.received_at) }}</td>
                  <td class="pl-num text-right">{{ date(sample.retention_due_at) }}</td>
                </tr>
              </tbody>
            </DataTable>
            <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
              <span class="pl-k">{{ form.search || form.status ? 'Nenhuma amostra neste filtro' : 'A sua bancada está pronta' }}</span>
              <p class="text-sm text-[var(--pl-muted)]">{{ form.search || form.status ? 'Experimente outro código, nome ou estado.' : 'As amostras recebidas neste laboratório aparecerão aqui.' }}</p>
            </div>
            <Pagination v-if="samples.total" v-bind="samples" />
          </div>
        </section>

        <aside class="grid content-start gap-7" aria-label="Fluxo de trabalho">
          <section class="pl-panel">
            <div class="pl-panel-head"><span class="pl-k">Fluxo de trabalho</span><span class="pl-k pl-faint">Só {{ lab.name }}</span></div>
            <button v-for="step in flow" :key="step.status" type="button" class="pl-hbar" :aria-pressed="form.status === step.status" @click="toggle(step.status)">
              <span>{{ step.label }}</span>
              <span class="pl-bar"><i :class="{ 'pl-bar-late': step.late && step.value }" :style="{ width: `${(step.value / flowMax) * 100}%` }" /></span>
              <span class="pl-num text-right" :class="{ 'text-[var(--pl-warn)]': step.late && step.value }">{{ step.value }}</span>
            </button>
          </section>
          <section>
            <h2 class="pl-d3 mb-3.5">Precisa da sua atenção</h2>
            <div class="pl-panel">
              <button type="button" class="pl-row w-full text-left" :disabled="!metrics.on_hold" @click="filter('EN_PAUSA')">
                <span>{{ metrics.on_hold ? `${metrics.on_hold} amostras em espera` : 'Nenhuma amostra em espera' }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ metrics.on_hold ? 'Confirme a informação em falta antes de continuar.' : 'As amostras com trabalho suspenso aparecerão aqui.' }}</span></span>
                <StatusChip :tone="metrics.on_hold ? 'wait' : 'ok'">{{ metrics.on_hold ? 'A rever' : 'Em dia' }}</StatusChip>
              </button>
              <component :is="lab.network_id ? Link : 'div'" :href="lab.network_id ? route('lab-network.index', lab.network_id) : undefined" class="pl-row">
                <span>{{ stockAlerts ? `${stockAlerts} posições de existências a repor` : 'Materiais sem alertas de reposição' }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ lab.network_id ? 'Encontrar materiais na rede de laboratórios.' : 'Consulte o inventário do laboratório.' }}</span></span>
                <StatusChip :tone="stockAlerts ? 'bad' : 'ok'">{{ stockAlerts ? 'Repor' : 'Apto' }}</StatusChip>
              </component>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </div>
</template>
