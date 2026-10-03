<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowRightIcon, MagnifyingGlassIcon, ShieldCheckIcon, EyeIcon } from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'

defineOptions({ layout: Layout })
const props = defineProps({ network: Object, labs: Array, stock: Object, filters: Object, networkOverview: Boolean })
const form = useForm({ search: props.filters.search ?? '', lab_id: props.filters.lab_id ?? '', available: Boolean(Number(props.filters.available ?? 0)) })
const selected = ref(null)
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
    <header class="lab-page-head"><div><h1>{{ networkOverview ? 'Uma rede. Visão partilhada.' : 'O seu laboratório.' }}</h1><p>Disponibilidade de materiais e indicadores dos laboratórios da sua rede.</p></div><span class="lab-pill" data-tone="received"><EyeIcon />Consulta de rede</span></header>
    <div class="lab-scope-note"><ShieldCheckIcon /><span>Acesso partilhado: existências e indicadores. Resultados, clientes e documentos de outros laboratórios permanecem privados.</span></div>
    <section class="lab-network-cards" aria-label="Laboratórios">
      <article v-for="lab in labs" :key="lab.id" class="lab-network-card"><div><h3>{{ lab.name }}</h3><p>{{ lab.id === network.main_lab_id ? 'Sede da rede' : network.name }}</p></div><div><span class="lab-number">{{ lab.active_samples }}</span><p>Amostras activas</p></div><p class="lab-network-summary">{{ lab.positions }} posições de stock · {{ lab.stock_alerts }} alertas de stock</p><button type="button" class="lab-link" @click="form.lab_id = lab.id; search()">Consultar existências <ArrowRightIcon /></button></article>
    </section>
    <section aria-labelledby="stock-heading" :aria-busy="form.processing">
      <div class="lab-section-head"><h2 id="stock-heading">Encontrar materiais na rede</h2><span class="lab-small lab-muted">Sem juntar unidades diferentes</span></div>
      <form class="lab-stock-tools" @submit.prevent="search">
        <label class="lab-inline-search"><MagnifyingGlassIcon /><input v-model="form.search" type="search" placeholder="Material, código ou lote" aria-label="Pesquisar materiais na rede" maxlength="100" /></label>
        <select v-model="form.lab_id" class="lab-field" aria-label="Filtrar laboratório" @change="search"><option value="">Todos os laboratórios</option><option v-for="lab in labs" :key="lab.id" :value="lab.id">{{ lab.name }}</option></select>
        <label class="lab-flex"><input v-model="form.available" type="checkbox" @change="search" />Só disponíveis</label>
        <button class="lab-btn" type="submit" :disabled="form.processing">{{ form.processing ? 'A pesquisar…' : 'Pesquisar' }}</button>
        <p v-if="form.errors.search || form.errors.lab_id" role="alert">{{ form.errors.search || form.errors.lab_id }}</p>
      </form>
      <div v-if="stock.data.length" class="lab-table-wrap"><table class="lab-table"><caption class="sr-only">Disponibilidade de materiais nos laboratórios autorizados</caption><thead><tr><th scope="col">Material</th><th scope="col">Laboratório / armazém</th><th scope="col">Disponível</th><th scope="col">Estado</th><th scope="col"><span class="sr-only">Detalhes</span></th></tr></thead><tbody><tr v-for="row in stock.data" :key="row.id"><td><strong>{{ row.name }}</strong><small>{{ row.code || 'Sem código' }}<span v-if="row.lot"> · {{ row.lot }}</span></small></td><td>{{ row.lab_name }}<small>{{ row.warehouse_name }}</small></td><td class="lab-number">{{ formatNumber(row.available_quantity) }} {{ row.unit }}</td><td><span class="lab-pill" :data-tone="Number(row.available_quantity) > 0 ? 'complete' : 'hold'">{{ statusLabel(row) }}</span></td><td><button type="button" class="lab-link" :aria-label="`Ver detalhes de ${row.name}`" :aria-expanded="selected?.id === row.id" @click="selected = selected?.id === row.id ? null : row">Consultar <ArrowRightIcon /></button></td></tr></tbody></table></div>
      <div v-else class="lab-empty"><strong>{{ form.search || form.lab_id || form.available ? 'Nenhum material encontrado' : 'Ainda não há materiais partilhados' }}</strong>{{ form.search || form.lab_id || form.available ? 'Altere o termo de pesquisa ou os filtros.' : 'Associe os armazéns aos laboratórios para consultar os seus saldos nesta vista.' }}</div>
      <p class="lab-stock-count lab-muted lab-small" role="status">{{ stock.total }} posições de stock · Materiais expirados ou bloqueados não contam como disponíveis.</p>
      <Pagination v-if="stock.total" v-bind="stock" />
    </section>
    <section v-if="selected" class="lab-panel lab-stock-details" aria-live="polite" aria-label="Detalhes do material">
      <div class="lab-section-head"><div><p class="lab-muted lab-small">{{ selected.lab_name }} · {{ selected.warehouse_name }}</p><h2>{{ selected.name }}</h2></div><button type="button" class="lab-btn" @click="selected = null">Fechar</button></div>
      <dl class="lab-detail-list lab-material-details"><div><dt>Saldo registado</dt><dd>{{ formatNumber(selected.recorded_quantity) }} {{ selected.unit }}</dd></div><div><dt>Disponível</dt><dd>{{ formatNumber(selected.available_quantity) }} {{ selected.unit }}</dd></div><div><dt>Lote</dt><dd>{{ selected.lot || 'Não registado' }}</dd></div><div><dt>Validade</dt><dd>{{ selected.expiry_date || 'Não registada' }}</dd></div></dl>
      <p class="lab-stock-count lab-muted lab-small">Consulta apenas. Confirme a disponibilidade com o laboratório antes de solicitar uma transferência.</p>
    </section>
  </div>
</template>
