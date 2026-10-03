<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ChevronRight as ChevronRightIcon, ShieldCheck as ShieldCheckIcon, X as XMarkIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'

defineOptions({ layout: Layout })
const props = defineProps({ network: Object, labs: Array, stock: Object, filters: Object, networkOverview: Boolean })
const form = useForm({ search: props.filters.search ?? '', lab_id: props.filters.lab_id ?? '', available: Boolean(Number(props.filters.available ?? 0)) })
const selected = ref(null)
const labOptions = computed(() => [{ value: '', label: 'Todos os laboratórios' }, ...props.labs.map((lab) => ({ value: lab.id, label: lab.name }))])
const formatNumber = (value) => new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 4 }).format(Number(value))
const statusLabel = (row) => Number(row.available_quantity) > 0 ? 'Disponível' : row.expiry_date && row.expiry_date < new Date().toISOString().slice(0, 10) ? 'Expirado' : 'Indisponível'
function search() {
  selected.value = null
  form.transform((data) => ({ ...data, available: data.available ? 1 : 0 })).get(route('lab-network.index', props.network.id), { preserveState: true, preserveScroll: true, replace: true })
}
</script>

<template>
  <div class="pl-page" data-template="report">
    <Head title="Rede de laboratórios" />
    <PageHeader :crumbs="[{ title: 'Início' }, { title: network.name }]" :title="networkOverview ? 'Rede de laboratórios' : 'O seu laboratório'" lede="Disponibilidade de materiais e indicadores dos laboratórios da sua rede, numa só vista.">
      <template #badges><StatusChip tone="run">Consulta de rede</StatusChip></template>
    </PageHeader>

    <div class="pl-banner mb-10"><ShieldCheckIcon aria-hidden="true" /><p class="text-sm"><span class="pl-k mr-2">Acesso partilhado</span>Existências e indicadores. Resultados, clientes e documentos de outros laboratórios permanecem privados.</p></div>

    <section class="lab-network-cards pl-network-cards mb-10" aria-label="Laboratórios">
      <article v-for="lab in labs" :key="lab.id" class="pl-network-card">
        <div class="flex items-start justify-between gap-3"><h3 class="text-[15px] font-semibold">{{ lab.name }}</h3><span class="pl-k pl-faint">{{ lab.id === network.main_lab_id ? 'Sede' : 'Rede' }}</span></div>
        <div><span class="pl-cell-value">{{ lab.active_samples }}</span><p class="pl-k pl-muted mt-2">Amostras activas</p></div>
        <p class="pl-num text-[12.5px] text-[var(--pl-muted)]">{{ lab.positions }} posições · <span :class="{ 'text-[var(--pl-bad)]': lab.stock_alerts }">{{ lab.stock_alerts }} alertas</span></p>
        <button type="button" class="pl-k pl-acc justify-self-start" @click="form.lab_id = lab.id; search()">Consultar existências →</button>
      </article>
    </section>

    <section aria-labelledby="stock-heading" :aria-busy="form.processing">
      <div class="mb-3.5 flex flex-wrap items-center justify-between gap-4"><h2 id="stock-heading" class="pl-d3">Encontrar materiais na rede</h2><span class="pl-k pl-faint">Sem juntar unidades diferentes</span></div>
      <form class="pl-filter" @submit.prevent="search">
        <label for="network-search" class="pl-filter-prompt">Filtro://</label>
        <BaseInput id="network-search" v-model="form.search" type="search" data-bare class="pl-filter-input" placeholder="material, código, lote" maxlength="100" />
        <div class="w-56"><BaseSelect :model-value="form.lab_id" :options="labOptions" aria-label="Filtrar laboratório" @update:model-value="form.lab_id = $event; search()" /></div>
        <label class="flex items-center gap-2.5 text-sm"><CheckboxInput v-model="form.available" @change="search" />Só disponíveis</label>
        <button class="ds-button ds-button-quiet" type="submit" :disabled="form.processing">{{ form.processing ? 'A procurar…' : 'Procurar' }}</button>
        <p v-if="form.errors.search || form.errors.lab_id" class="ds-field-error w-full" role="alert">{{ form.errors.search || form.errors.lab_id }}</p>
      </form>
      <div class="pl-panel">
        <DataTable v-if="stock.data.length"><caption class="sr-only">Disponibilidade de materiais nos laboratórios autorizados</caption><thead><tr><th scope="col">Material</th><th scope="col">Laboratório / armazém</th><th scope="col" class="text-right">Disponível</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Detalhes</span></th></tr></thead><tbody>
          <tr v-for="row in stock.data" :key="row.id" :data-selected="selected?.id === row.id">
            <td><strong class="font-semibold">{{ row.name }}</strong><span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ row.code || 'Sem código' }}<span v-if="row.lot"> · {{ row.lot }}</span></span></td>
            <td>{{ row.lab_name }}<span class="block text-[12.5px] text-[var(--pl-muted)]">{{ row.warehouse_name }}</span></td>
            <td class="pl-num text-right">{{ formatNumber(row.available_quantity) }} {{ row.unit }}</td>
            <td><StatusChip :tone="Number(row.available_quantity) > 0 ? 'ok' : statusLabel(row) === 'Expirado' ? 'bad' : 'wait'">{{ statusLabel(row) }}</StatusChip></td>
            <td class="text-right"><button type="button" class="ds-icon-button pl-row-go" :aria-label="`Ver detalhes de ${row.name}`" :aria-expanded="selected?.id === row.id" @click="selected = selected?.id === row.id ? null : row"><ChevronRightIcon aria-hidden="true" /></button></td>
          </tr>
        </tbody></DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6"><span class="pl-k">{{ form.search || form.lab_id || form.available ? 'Nenhum material encontrado' : 'Ainda não há materiais partilhados' }}</span><p class="text-sm text-[var(--pl-muted)]">{{ form.search || form.lab_id || form.available ? 'Altere o termo de pesquisa ou os filtros.' : 'Associe os armazéns aos laboratórios para consultar os seus saldos nesta vista.' }}</p></div>
        <p class="border-t border-[var(--pl-line)] px-4 py-3 text-[12.5px] text-[var(--pl-muted)]" role="status">{{ stock.total }} posições · Materiais expirados ou bloqueados não contam como disponíveis.</p>
        <Pagination v-if="stock.total" v-bind="stock" />
      </div>
    </section>

    <section v-if="selected" class="pl-panel mt-7" aria-live="polite" aria-label="Detalhes do material">
      <div class="pl-panel-head h-auto py-4"><div><p class="pl-k pl-muted">{{ selected.lab_name }} · {{ selected.warehouse_name }}</p><h2 class="pl-d3 mt-2">{{ selected.name }}</h2></div><button type="button" class="ds-icon-button" aria-label="Fechar detalhes" @click="selected = null"><XMarkIcon aria-hidden="true" /></button></div>
      <dl class="pl-facts pl-facts-2"><div class="pl-fact"><dt>Saldo registado</dt><dd class="pl-num">{{ formatNumber(selected.recorded_quantity) }} {{ selected.unit }}</dd></div><div class="pl-fact"><dt>Disponível</dt><dd class="pl-num">{{ formatNumber(selected.available_quantity) }} {{ selected.unit }}</dd></div><div class="pl-fact"><dt>Lote</dt><dd>{{ selected.lot || 'Não registado' }}</dd></div><div class="pl-fact"><dt>Validade</dt><dd class="pl-num">{{ selected.expiry_date || 'Não registada' }}</dd></div></dl>
      <p class="border-t border-[var(--pl-line)] p-4 text-[12.5px] text-[var(--pl-muted)]">Consulta apenas. Confirme a disponibilidade com o laboratório antes de solicitar uma transferência.</p>
    </section>
  </div>
</template>
