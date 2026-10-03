<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowRight as ArrowRightIcon, Search as MagnifyingGlassIcon, ShieldCheck as ShieldCheckIcon, Eye as EyeIcon } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'

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
  <div class="workbench-page">
    <Head title="Rede de laboratórios" />
    <header class="app-page-header lab-page-head">
      <div class="app-page-header-row">
        <div><h1 class="app-page-title">{{ networkOverview ? 'Rede de laboratórios' : 'O seu laboratório' }}</h1><p class="app-page-meta">Uma rede, visão partilhada · Disponibilidade de materiais e indicadores dos laboratórios da sua rede.</p></div>
        <div class="app-page-actions"><span class="lab-pill" data-tone="received"><EyeIcon aria-hidden="true" />Consulta de rede</span></div>
      </div>
    </header>
    <div class="lab-scope-note"><ShieldCheckIcon /><span>Acesso partilhado: existências e indicadores. Resultados, clientes e documentos de outros laboratórios permanecem privados.</span></div>
    <section class="lab-network-cards" aria-label="Laboratórios">
      <article v-for="lab in labs" :key="lab.id" class="lab-network-card"><div><h3>{{ lab.name }}</h3><p>{{ lab.id === network.main_lab_id ? 'Sede da rede' : network.name }}</p></div><div><span class="lab-number">{{ lab.active_samples }}</span><p>Amostras activas</p></div><p class="lab-network-summary">{{ lab.positions }} posições de existências · {{ lab.stock_alerts }} alertas de existências</p><button type="button" class="lab-link" @click="form.lab_id = lab.id; search()">Consultar existências <ArrowRightIcon /></button></article>
    </section>
    <section class="lab-queue" aria-labelledby="stock-heading" :aria-busy="form.processing">
      <div class="lab-section-head"><h2 id="stock-heading">Encontrar materiais na rede</h2><span class="lab-small lab-muted">Sem juntar unidades diferentes</span></div>
      <form class="lab-stock-tools" @submit.prevent="search">
        <div class="min-w-44 max-w-xs flex-1">
          <BaseInput v-model="form.search" type="search" placeholder="Material, código ou lote" aria-label="Pesquisar materiais na rede" maxlength="100">
            <template #leading><MagnifyingGlassIcon aria-hidden="true" /></template>
          </BaseInput>
        </div>
        <div class="w-56"><BaseSelect :model-value="form.lab_id" :options="labOptions" aria-label="Filtrar laboratório" @update:model-value="form.lab_id = $event; search()" /></div>
        <label class="lab-flex"><CheckboxInput v-model="form.available" @change="search" />Só disponíveis</label>
        <button class="lab-btn" type="submit" :disabled="form.processing">{{ form.processing ? 'A pesquisar…' : 'Pesquisar' }}</button>
        <p v-if="form.errors.search || form.errors.lab_id" role="alert">{{ form.errors.search || form.errors.lab_id }}</p>
      </form>
      <div v-if="stock.data.length" class="lab-table-wrap"><DataTable class="lab-table"><caption class="sr-only">Disponibilidade de materiais nos laboratórios autorizados</caption><thead><tr><th scope="col">Material</th><th scope="col">Laboratório / armazém</th><th scope="col">Disponível</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Detalhes</span></th></tr></thead><tbody><tr v-for="row in stock.data" :key="row.id"><td><strong>{{ row.name }}</strong><small>{{ row.code || 'Sem código' }}<span v-if="row.lot"> · {{ row.lot }}</span></small></td><td>{{ row.lab_name }}<small>{{ row.warehouse_name }}</small></td><td class="lab-number">{{ formatNumber(row.available_quantity) }} {{ row.unit }}</td><td><span class="lab-pill" :data-tone="Number(row.available_quantity) > 0 ? 'complete' : 'hold'">{{ statusLabel(row) }}</span></td><td><button type="button" class="lab-link" :aria-label="`Ver detalhes de ${row.name}`" :aria-expanded="selected?.id === row.id" @click="selected = selected?.id === row.id ? null : row">Consultar <ArrowRightIcon /></button></td></tr></tbody></DataTable></div>
      <div v-else class="lab-empty"><strong>{{ form.search || form.lab_id || form.available ? 'Nenhum material encontrado' : 'Ainda não há materiais partilhados' }}</strong>{{ form.search || form.lab_id || form.available ? 'Altere o termo de pesquisa ou os filtros.' : 'Associe os armazéns aos laboratórios para consultar os seus saldos nesta vista.' }}</div>
      <p class="lab-stock-count lab-muted lab-small" role="status">{{ stock.total }} posições de existências · Materiais expirados ou bloqueados não contam como disponíveis.</p>
      <Pagination v-if="stock.total" v-bind="stock" />
    </section>
    <section v-if="selected" class="lab-panel lab-stock-details" aria-live="polite" aria-label="Detalhes do material">
      <div class="lab-section-head"><div><p class="lab-muted lab-small">{{ selected.lab_name }} · {{ selected.warehouse_name }}</p><h2>{{ selected.name }}</h2></div><button type="button" class="lab-btn" @click="selected = null">Fechar</button></div>
      <dl class="lab-detail-list lab-material-details"><div><dt>Saldo registado</dt><dd>{{ formatNumber(selected.recorded_quantity) }} {{ selected.unit }}</dd></div><div><dt>Disponível</dt><dd>{{ formatNumber(selected.available_quantity) }} {{ selected.unit }}</dd></div><div><dt>Lote</dt><dd>{{ selected.lot || 'Não registado' }}</dd></div><div><dt>Validade</dt><dd>{{ selected.expiry_date || 'Não registada' }}</dd></div></dl>
      <p class="lab-stock-count lab-muted lab-small">Consulta apenas. Confirme a disponibilidade com o laboratório antes de solicitar uma transferência.</p>
    </section>
  </div>
</template>
