<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { ref } from 'vue'
import { Download, Upload } from '@lucide/vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const props = defineProps({
  requestKey: String,
  maxRows: Number,
  columns: Array,
  equipment: Array,
  categories: Array,
  suppliers: Array,
})

const form = useForm({ file: null, request_key: props.requestKey })
const requestError = ref('')
const submitting = ref(false)

function chooseFile(event) {
  if (submitting.value || form.processing) return
  form.file = event.target.files?.[0] ?? null
  form.request_key = crypto.randomUUID()
  form.clearErrors()
  requestError.value = ''
}

function submit() {
  if (submitting.value || form.processing || !form.file) return
  submitting.value = true
  requestError.value = ''
  try {
    form.post(route('maintenancetasks.import.upload'), {
      forceFormData: true,
      preserveScroll: true,
      onHttpException: response => {
        requestError.value = [403, 404].includes(response.status)
          ? 'O laboratório ou a autorização já não está disponível. Actualize a página.'
          : 'Não foi possível confirmar a importação. Mantenha este ficheiro e tente novamente; a mesma operação não cria duplicados.'
        return false
      },
      onNetworkError: () => {
        requestError.value = 'Falha de ligação. Tente novamente sem seleccionar outro ficheiro; a mesma operação não cria duplicados.'
        return false
      },
      onCancel: () => { requestError.value = 'Operação interrompida. Tente novamente com o mesmo ficheiro para confirmar o resultado.' },
      onFinish: () => { submitting.value = false },
    })
  } catch {
    submitting.value = false
    requestError.value = 'Não foi possível iniciar a importação. O ficheiro foi mantido; tente novamente.'
  }
}
</script>

<template>
  <div class="pl-page space-y-6" :class="commercialDocumentThemeClasses">
    <Head title="Importar tarefas" />
    <PageHeader :trail="[{ title: 'Manutenção', url: route('vap-maintenance.tasks') }, { title: 'Importar tarefas' }]" title="Importar tarefas" lede="Crie tarefas pendentes a partir de um CSV. Qualquer erro impede a gravação de todo o ficheiro; nenhuma linha é ignorada." />

    <ul class="ds-copy mt-5 space-y-2 text-sm">
      <li>Até {{ maxRows }} linhas e 2 MB, em UTF-8, separadas por ponto e vírgula.</li>
      <li>Obrigatórios: <code>equipment_code</code>, <code>name</code>, <code>category_id</code> e <code>due_date</code>.</li>
      <li>Datas: <code>AAAA-MM-DD</code>. Booleanos: <code>0</code> ou <code>1</code>. Decimais usam ponto.</li>
      <li>O código identifica equipamento deste laboratório. Categoria e fornecedor usam os IDs de referência abaixo.</li>
      <li>Periodicidade exige quantidade e unidade: <code>hours</code>, <code>days</code>, <code>weeks</code>, <code>months</code> ou <code>years</code>.</li>
      <li>Laboratório, número, ano e datas derivadas são atribuídos pelo sistema. A conclusão é feita depois, no fluxo normal.</li>
    </ul>

    <a :href="route('maintenancetasks.import.template')" class="ds-button ds-button-secondary mt-5">
      <Download class="h-4 w-4" aria-hidden="true" /> Descarregar modelo CSV
    </a>

    <details class="mt-5 text-sm text-[var(--ds-text-muted)]">
      <summary class="cursor-pointer font-semibold">Todas as colunas permitidas</summary>
      <p class="mt-2 break-words"><code>{{ columns.join('; ') }}</code></p>
    </details>

    <section class="ds-panel p-5 sm:p-6">
      <form class="space-y-4" enctype="multipart/form-data" @submit.prevent="submit">
        <label class="ds-field-group">
          <span class="ds-field-label">Ficheiro CSV</span>
          <input type="file" accept=".csv,.txt,text/csv,text/plain" class="ds-field" :disabled="submitting || form.processing" :aria-invalid="form.hasErrors" :aria-describedby="form.hasErrors || requestError ? 'maintenance-import-errors' : undefined" required @change="chooseFile" />
        </label>
        <div v-if="form.hasErrors || requestError" id="maintenance-import-errors" class="ds-field-error" role="alert">
          <p v-for="(error, field) in form.errors" :key="field">{{ error }}</p>
          <p v-if="requestError">{{ requestError }}</p>
        </div>
        <p v-if="submitting || form.processing" class="ds-copy text-sm" role="status">A validar e guardar todas as tarefas. Aguarde a confirmação.</p>
        <button type="submit" class="ds-button ds-button-primary" :disabled="submitting || form.processing || !form.file" :aria-busy="submitting || form.processing">
          <Upload class="h-4 w-4" aria-hidden="true" />
          {{ submitting || form.processing ? 'A importar…' : 'Importar tarefas' }}
        </button>
      </form>
    </section>

    <section class="ds-panel p-5 sm:p-6">
      <h2 class="ds-heading text-lg">Referências do ficheiro</h2>
      <p class="ds-copy mt-2 text-sm">As referências são novamente verificadas ao guardar. Equipamentos arquivados ou de outro laboratório não são aceites.</p>
      <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div>
          <h3 class="ds-field-label">Códigos de equipamento</h3>
          <ul v-if="equipment.length" class="ds-copy mt-3 space-y-2 text-sm">
            <li v-for="item in equipment" :key="item.id"><code>{{ item.internal_code || 'Sem código' }}</code> — {{ item.name }}</li>
          </ul>
          <p v-else class="ds-copy mt-3 text-sm">Nenhum equipamento disponível neste laboratório.</p>
        </div>
        <div>
          <h3 class="ds-field-label">IDs de categoria</h3>
          <ul class="ds-copy mt-3 space-y-2 text-sm">
            <li v-for="category in categories" :key="category.id"><code>{{ category.id }}</code> — {{ category.name }}</li>
          </ul>
        </div>
        <div>
          <h3 class="ds-field-label">IDs de fornecedor</h3>
          <p class="ds-copy mt-2 text-xs">Obrigatório apenas quando <code>executed_by_supplier</code> é 1.</p>
          <ul class="ds-copy mt-3 space-y-2 text-sm">
            <li v-for="supplier in suppliers" :key="supplier.id"><code>{{ supplier.id }}</code> — {{ supplier.name }}</li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</template>
