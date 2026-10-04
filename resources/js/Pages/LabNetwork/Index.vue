<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowRight, Download, Search, ShieldCheck, Eye } from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import Pagination from '@/Components/pagination.vue'
import { networkStockFilters, formatNetworkQuantity, networkAvailabilityLabel } from '@/Support/networkStock'

defineOptions({ layout: Layout })
const props = defineProps({ network: Object, labs: Array, warehouses: Array, stock: Object, filters: Object, networkOverview: Boolean, asOf: String })
const form = useForm(networkStockFilters(props.filters))
const selectedId = ref(null)
const detailPanel = ref(null)
let detailTrigger = null
const selected = computed(() => props.stock.data.find(row => row.id === selectedId.value) ?? null)
const labOptions = computed(() => [{ value: '', label: 'Todos os laboratórios' }, ...props.labs.map(lab => ({ value: lab.id, label: lab.name }))])
const warehouseOptions = computed(() => [
  { value: '', label: 'Todos os armazéns' },
  ...props.warehouses.filter(warehouse => !form.lab_id || String(warehouse.lab_id) === String(form.lab_id))
    .map(warehouse => ({ value: warehouse.id, label: warehouse.name })),
])
const exportUrl = computed(() => route('lab-network.export', { network: props.network.id, ...props.filters, page: undefined }))
const hasFilters = computed(() => Object.values(networkStockFilters(props.filters)).some(Boolean))
const formatTime = value => value ? new Intl.DateTimeFormat('pt-AO', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : 'Sem data registada'
const formatDate = value => value ? value.split('-').reverse().join('/') : 'Não registada'

watch(() => props.filters, filters => Object.assign(form, networkStockFilters(filters)))
watch(() => props.stock, () => { selectedId.value = null })
function changeLab(value) {
  form.lab_id = value
  form.warehouse_id = ''
}
function search() {
  if (form.processing) return
  selectedId.value = null
  form.transform(data => ({ ...data, available: data.available ? 1 : 0 }))
    .get(route('lab-network.index', props.network.id), { preserveState: true, preserveScroll: true, replace: true })
}
function resetFilters() {
  Object.assign(form, networkStockFilters())
  search()
}
function consultLab(id) {
  changeLab(id)
  search()
}
async function toggleDetails(id, event) {
  if (selectedId.value === id) {
    closeDetails()
    return
  }
  detailTrigger = event.currentTarget
  selectedId.value = id
  await nextTick()
  detailPanel.value?.focus()
}
function closeDetails() {
  selectedId.value = null
  detailTrigger?.focus()
}
</script>

<template>
  <div class="workbench-page">
    <Head title="Rede de laboratórios" />
    <header class="app-page-header lab-page-head">
      <div class="app-page-header-row">
        <div>
          <h1 class="app-page-title">{{ networkOverview ? 'Rede de laboratórios' : 'O seu laboratório' }}</h1>
          <p class="app-page-meta">{{ network.name }} · Disponibilidade de materiais e indicadores partilhados.</p>
        </div>
        <div class="app-page-actions"><span class="lab-pill" data-tone="received"><Eye aria-hidden="true" />Consulta de rede</span></div>
      </div>
    </header>
    <div class="lab-scope-note"><ShieldCheck aria-hidden="true" /><span>Consulta apenas. Resultados, clientes, preços e documentos de outros laboratórios permanecem privados.</span></div>
    <section class="lab-network-cards" aria-label="Indicadores dos laboratórios">
      <h2 class="sr-only">Indicadores dos laboratórios</h2>
      <article v-for="lab in labs" :key="lab.id" class="lab-network-card">
        <div><h3>{{ lab.name }}</h3><p>{{ lab.id === network.main_lab_id ? 'Sede da rede' : network.name }}</p></div>
        <div><span class="lab-number">{{ lab.active_samples }}</span><p>Amostras activas · intervalo</p></div>
        <p class="lab-network-summary">{{ lab.positions }} posições de existências · {{ lab.stock_alerts }} alertas de reposição</p>
        <button type="button" class="lab-link" :disabled="form.processing" @click="consultLab(lab.id)">Consultar existências <ArrowRight aria-hidden="true" /></button>
      </article>
    </section>
    <details class="lab-stock-count lab-muted lab-small">
      <summary>Como são calculados os indicadores</summary>
      <p>Uma posição corresponde a um material num armazém. Há alerta quando a quantidade disponível é igual ou inferior ao ponto de reposição.</p>
      <p>Amostras activas: por iniciar, em progresso ou em pausa; excluem amostras arquivadas. São apresentadas em intervalos: 0, 1–4, 5–9, 10–19, 20–49 e 50+. Os indicadores abrangem o laboratório inteiro e não mudam com os filtros.</p>
    </details>
    <section class="lab-queue" aria-labelledby="stock-heading" :aria-busy="form.processing">
      <div class="lab-section-head">
        <h2 id="stock-heading">Encontrar materiais na rede</h2>
        <a class="lab-link" :href="form.processing ? undefined : exportUrl" :aria-disabled="form.processing" :tabindex="form.processing ? -1 : 0">
          <Download aria-hidden="true" />Exportar consulta CSV
        </a>
      </div>
      <p class="lab-small lab-muted">Consultado em {{ formatTime(asOf) }}. Pesquise novamente para actualizar. A exportação usa os filtros aplicados.</p>
      <form class="my-4 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="search">
        <BaseInput v-model="form.search" label="Material ou código" type="search" placeholder="Nome, código ou lote" maxlength="100" :error="form.errors.search">
          <template #leading><Search aria-hidden="true" /></template>
        </BaseInput>
        <BaseSelect :model-value="form.lab_id" label="Laboratório" :options="labOptions" :error="form.errors.lab_id" @update:model-value="changeLab" />
        <BaseSelect v-model="form.warehouse_id" label="Armazém" :options="warehouseOptions" :error="form.errors.warehouse_id" />
        <BaseInput v-model="form.lot" label="Lote" maxlength="100" :error="form.errors.lot" />
        <BaseInput v-model="form.expiry_from" label="Validade desde" type="date" :error="form.errors.expiry_from" />
        <BaseInput v-model="form.expiry_to" label="Validade até" type="date" :error="form.errors.expiry_to" />
        <label class="lab-flex"><CheckboxInput v-model="form.available" />Só disponíveis</label>
        <div class="flex flex-wrap gap-3">
          <button class="lab-btn" type="submit" :disabled="form.processing">{{ form.processing ? 'A pesquisar…' : 'Pesquisar' }}</button>
          <button class="lab-link" type="button" :disabled="form.processing" @click="resetFilters">Limpar filtros</button>
        </div>
        <p v-if="form.errors.available || form.errors.page || form.errors.per_page" role="alert">{{ form.errors.available || form.errors.page || form.errors.per_page }}</p>
      </form>
      <div v-if="stock.data.length" class="lab-table-wrap">
        <DataTable class="lab-table">
          <caption class="sr-only">Saldos por material, armazém e lote nos laboratórios autorizados</caption>
          <thead><tr>
            <th scope="col">Material / lote</th><th scope="col">Laboratório / armazém</th>
            <th scope="col">Físico</th><th scope="col">Reservado</th><th scope="col">Bloqueado</th>
            <th scope="col">Disponível</th><th scope="col">Validade / estado</th><th scope="col"><span class="sr-only">Detalhes</span></th>
          </tr></thead>
          <tbody><tr v-for="row in stock.data" :key="row.id">
            <td><strong>{{ row.name }}</strong><small>{{ row.code || 'Sem código' }} · {{ row.lot || 'Sem lote individual' }}</small></td>
            <td>{{ row.lab_name }}<small>{{ row.warehouse_name }}</small></td>
            <td class="lab-number">{{ formatNetworkQuantity(row.physical_quantity) }}<small>{{ row.unit || 'Unidade não definida' }}</small></td>
            <td class="lab-number">{{ formatNetworkQuantity(row.reserved_quantity) }}</td>
            <td class="lab-number">{{ formatNetworkQuantity(row.blocked_quantity) }}</td>
            <td class="lab-number">{{ formatNetworkQuantity(row.available_quantity) }}</td>
            <td>{{ formatDate(row.expiry_date) }}<small><span class="lab-pill" :data-tone="Number(row.available_quantity) > 0 ? 'complete' : 'hold'">{{ networkAvailabilityLabel(row) }}</span></small></td>
            <td><button type="button" class="lab-link" :aria-label="'Ver detalhes de ' + row.name" :aria-expanded="selected?.id === row.id" :aria-controls="selected?.id === row.id ? 'network-stock-detail' : undefined" @click="toggleDetails(row.id, $event)">Consultar <ArrowRight aria-hidden="true" /></button></td>
          </tr></tbody>
        </DataTable>
      </div>
      <div v-else class="lab-empty">
        <strong>{{ hasFilters ? 'Nenhum material encontrado' : 'Ainda não há existências para consultar' }}</strong>
        <p>{{ hasFilters ? 'Altere o termo de pesquisa ou os filtros.' : 'As existências registadas nos armazéns autorizados aparecerão aqui.' }}</p>
      </div>
      <p class="lab-stock-count lab-muted lab-small" role="status">{{ stock.total }} parcelas de existências. Cada lote e saldo sem lote aparece uma vez; quantidades de unidades diferentes não são somadas.</p>
      <Pagination v-if="stock.total" v-bind="stock" />
    </section>
    <section v-if="selected" id="network-stock-detail" ref="detailPanel" tabindex="-1" class="lab-panel lab-stock-details" aria-live="polite" aria-label="Detalhes do material" @keydown.esc.stop.prevent="closeDetails">
      <div class="lab-section-head">
        <div><p class="lab-muted lab-small">{{ selected.lab_name }} · {{ selected.warehouse_name }}</p><h2>{{ selected.name }}</h2></div>
        <button type="button" class="lab-btn" @click="closeDetails">Fechar</button>
      </div>
      <dl class="lab-detail-list lab-material-details">
        <div><dt>Saldo físico registado</dt><dd>{{ formatNetworkQuantity(selected.physical_quantity) }} {{ selected.unit || '—' }}</dd></div>
        <div><dt>Reservado</dt><dd>{{ formatNetworkQuantity(selected.reserved_quantity) }} {{ selected.unit || '—' }}</dd></div>
        <div><dt>Bloqueado</dt><dd>{{ formatNetworkQuantity(selected.blocked_quantity) }} {{ selected.unit || '—' }}</dd></div>
        <div><dt>Disponível</dt><dd>{{ formatNetworkQuantity(selected.available_quantity) }} {{ selected.unit || '—' }}</dd></div>
        <div><dt>Saída pendente · já deduzida</dt><dd>{{ formatNetworkQuantity(selected.outgoing_quantity) }} {{ selected.unit || '—' }}</dd></div>
        <div><dt>Lote</dt><dd>{{ selected.lot || 'Sem lote individual' }}</dd></div>
        <div><dt>Validade</dt><dd>{{ formatDate(selected.expiry_date) }}</dd></div>
        <div><dt>Último registo da posição</dt><dd>{{ formatTime(selected.updated_at) }}</dd></div>
      </dl>
      <p class="lab-stock-count lab-muted lab-small">Reservado corresponde a posições comprometidas. Bloqueado inclui validade ultrapassada, retenção ou saldo por reconciliar. A saída pendente já foi deduzida do saldo físico e não é deduzida novamente.</p>
      <p class="lab-stock-count lab-muted lab-small">A data do último registo refere-se à posição no armazém, não a uma contagem física. Confirme a disponibilidade com o laboratório antes de solicitar materiais.</p>
    </section>
  </div>
</template>
