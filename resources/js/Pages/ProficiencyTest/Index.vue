<template>
  <div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="bg-gradient-to-r from-slate-950 via-sky-950 to-cyan-900 px-6 py-8 text-white sm:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200">Qualidade externa</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Ensaios de proficiência e interlaboratoriais</h1>
            <p class="mt-3 text-sm leading-7 text-slate-200">
              Planeie rondas, acompanhe o estado, registe z-score, trate ações corretivas e mantenha a evidência pronta para auditoria.
            </p>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-100"
            @click="openCreate"
          >
            Novo programa
          </button>
        </div>
      </div>

      <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-2xl border border-slate-200 bg-white p-4">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          <p class="mt-1 text-sm text-slate-600">{{ card.caption }}</p>
        </article>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),11rem,11rem,auto]">
        <input
          v-model="filters.search"
          type="search"
          placeholder="Pesquisar por nome, provedor ou ronda"
          class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20"
        />

        <select v-model="filters.scheme_type" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20">
          <option value="">Todos os tipos</option>
          <option v-for="option in schemeOptions" :key="option" :value="option">{{ schemeLabel(option) }}</option>
        </select>

        <select v-model="filters.status" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20">
          <option value="">Todos os estados</option>
          <option v-for="option in statusOptions" :key="option" :value="option">{{ statusLabel(option) }}</option>
        </select>

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
          @click="clearFilters"
        >
          Limpar
        </button>
      </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="hidden border-b border-slate-200 bg-slate-50 px-6 py-4 lg:grid lg:grid-cols-[1.2fr,0.9fr,0.8fr,0.8fr,0.8fr,auto] lg:gap-4">
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Programa</div>
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Provedor</div>
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tipo</div>
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Estado</div>
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resultado</div>
        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 text-right">Ações</div>
      </div>

      <div v-if="records.length" class="divide-y divide-slate-200">
        <article v-for="record in records" :key="record.id" class="px-6 py-5">
          <div class="hidden items-start gap-4 lg:grid lg:grid-cols-[1.2fr,0.9fr,0.8fr,0.8fr,0.8fr,auto]">
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ record.name }}</p>
              <p class="mt-1 text-sm text-slate-600">{{ record.round_reference || 'Sem referência' }}</p>
              <p class="mt-2 text-xs text-slate-500">Data base {{ formatDate(record.date) }}</p>
            </div>
            <div class="text-sm text-slate-700">{{ record.provider_name || '—' }}</div>
            <div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ schemeLabel(record.scheme_type) }}</span></div>
            <div><span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(record.status)">{{ statusLabel(record.status) }}</span></div>
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ outcomeLabel(record.outcome) }}</p>
              <p v-if="record.z_score !== null" class="mt-1 text-xs text-slate-500">z-score {{ record.z_score }}</p>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" @click="openEdit(record)">Editar</button>
              <button
                v-if="!record.deleted"
                type="button"
                class="rounded-xl border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                @click="destroy(record)"
              >
                Arquivar
              </button>
              <button
                v-else
                type="button"
                class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
                @click="restore(record)"
              >
                Restaurar
              </button>
            </div>
          </div>

          <div class="space-y-4 lg:hidden">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold text-slate-900">{{ record.name }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ record.provider_name || '—' }}</p>
              </div>
              <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(record.status)">{{ statusLabel(record.status) }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tipo</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ schemeLabel(record.scheme_type) }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Resultado</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ outcomeLabel(record.outcome) }}</p>
              </div>
            </div>
            <div class="flex gap-2">
              <button type="button" class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="openEdit(record)">Editar</button>
              <button
                v-if="!record.deleted"
                type="button"
                class="flex-1 rounded-2xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50"
                @click="destroy(record)"
              >
                Arquivar
              </button>
              <button
                v-else
                type="button"
                class="flex-1 rounded-2xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                @click="restore(record)"
              >
                Restaurar
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-else class="px-6 py-14 text-center">
        <p class="text-lg font-semibold text-slate-900">Sem programas registados</p>
        <p class="mt-2 text-sm text-slate-600">Crie o primeiro ensaio de proficiência ou interlaboratorial e acompanhe-o até ao encerramento.</p>
      </div>
    </section>

    <div v-if="record.meta?.links?.length" class="flex flex-wrap gap-2">
      <Link
        v-for="link in record.meta.links"
        :key="`${link.label}-${link.url}`"
        :href="link.url || '#'"
        class="rounded-xl border px-3 py-2 text-sm transition"
        :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
        v-html="link.label"
      />
    </div>

    <TransitionRoot as="template" :show="showEditor">
      <Dialog as="div" class="relative z-50" @close="closeEditor">
        <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" />
        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <DialogPanel class="w-full max-w-4xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
              <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-xl font-semibold text-slate-900">{{ editorTitle }}</h2>
                <p class="mt-1 text-sm text-slate-600">Registe os dados do programa, o resultado e as ações corretivas associadas.</p>
              </div>

              <form class="space-y-6 px-6 py-6" @submit.prevent="submit">
                <div class="grid gap-5 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Nome</label>
                    <input v-model="form.name" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20" />
                    <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Provedor</label>
                    <input v-model="form.provider_name" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20" />
                    <p v-if="form.errors.provider_name" class="text-xs text-red-600">{{ form.errors.provider_name }}</p>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Tipo</label>
                    <select v-model="form.scheme_type" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20">
                      <option v-for="option in schemeOptions" :key="option" :value="option">{{ schemeLabel(option) }}</option>
                    </select>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Ronda / referência</label>
                    <input v-model="form.round_reference" type="text" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20" />
                    <p v-if="form.errors.round_reference" class="text-xs text-red-600">{{ form.errors.round_reference }}</p>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Estado</label>
                    <select v-model="form.status" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20">
                      <option v-for="option in statusOptions" :key="option" :value="option">{{ statusLabel(option) }}</option>
                    </select>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Resultado</label>
                    <select v-model="form.outcome" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20">
                      <option value="pending">Pendente</option>
                      <option value="satisfactory">Satisfatório</option>
                      <option value="questionable">Questionável</option>
                      <option value="unsatisfactory">Insatisfatório</option>
                    </select>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Data base</label>
                    <input v-model="form.date" type="date" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20" />
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">z-score</label>
                    <input v-model="form.z_score" type="number" step="0.01" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20" />
                  </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Escopo</label>
                    <textarea v-model="form.scope" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20"></textarea>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Ações corretivas</label>
                    <textarea v-model="form.corrective_actions" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20"></textarea>
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700">Notas</label>
                  <textarea v-model="form.notes" rows="3" class="block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-sky-800 focus:ring-2 focus:ring-sky-800/20"></textarea>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                  <button type="button" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="closeEditor">Cancelar</button>
                  <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60" :disabled="form.processing">
                    {{ form.processing ? 'A guardar...' : 'Guardar programa' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { computed, ref, watch } from 'vue'
import { Dialog, DialogPanel, TransitionRoot } from '@headlessui/vue'
import { Link, router, useForm } from '@inertiajs/vue3'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  record: { type: Object, required: true },
  query: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  schemeOptions: { type: Array, default: () => [] },
})

const record = computed(() => props.record)
const records = computed(() => props.record?.data ?? [])
const statusOptions = computed(() => props.statusOptions ?? [])
const schemeOptions = computed(() => props.schemeOptions ?? [])

const filters = useForm({
  search: props.query?.search || '',
  status: props.query?.status || '',
  scheme_type: props.query?.scheme_type || '',
  filter: props.query?.filter || '',
})

watch(() => [filters.search, filters.status, filters.scheme_type], () => {
  router.get(route('proficiency_tests.index'), filters.data(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, { deep: true })

const summaryCards = computed(() => {
  const total = records.value.length
  const closed = records.value.filter((item) => item.status === 'closed').length
  const attention = records.value.filter((item) => ['questionable', 'unsatisfactory'].includes(item.outcome)).length
  const interlaboratory = records.value.filter((item) => item.scheme_type === 'interlaboratory').length

  return [
    { label: 'Programas visíveis', value: total, caption: 'Janela atual filtrada para acompanhamento operacional.' },
    { label: 'Encerrados', value: closed, caption: 'Rondas com evidência e fecho registados.' },
    { label: 'Requer atenção', value: attention, caption: 'Resultados questionáveis ou insatisfatórios.' },
    { label: 'Interlaboratoriais', value: interlaboratory, caption: 'Comparações externas entre laboratórios.' },
  ]
})

const showEditor = ref(false)
const editingRecordId = ref(null)
const form = useForm({
  name: '',
  scheme_type: 'proficiency',
  provider_name: '',
  round_reference: '',
  status: 'planned',
  date: '',
  scheduled_at: '',
  closed_at: '',
  scope: '',
  outcome: 'pending',
  z_score: '',
  corrective_actions: '',
  notes: '',
  results: [],
})

const editorTitle = computed(() => editingRecordId.value ? 'Editar programa' : 'Novo programa')

const schemeLabel = (value) => ({
  proficiency: 'Proficiência',
  interlaboratory: 'Interlaboratorial',
}[value] || value || '—')

const statusLabel = (value) => ({
  planned: 'Planeado',
  in_progress: 'Em curso',
  completed: 'Concluído',
  reviewed: 'Revisto',
  closed: 'Fechado',
}[value] || value || '—')

const outcomeLabel = (value) => ({
  pending: 'Pendente',
  satisfactory: 'Satisfatório',
  questionable: 'Questionável',
  unsatisfactory: 'Insatisfatório',
}[value] || 'Pendente')

const statusBadgeClass = (status) => ({
  'bg-slate-100 text-slate-700': status === 'planned',
  'bg-sky-100 text-sky-700': status === 'in_progress',
  'bg-blue-100 text-blue-700': status === 'completed',
  'bg-amber-100 text-amber-700': status === 'reviewed',
  'bg-emerald-100 text-emerald-700': status === 'closed',
}[status] || 'bg-slate-100 text-slate-700')

const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'

const resetForm = () => {
  form.reset()
  form.clearErrors()
  form.defaults({
    name: '',
    scheme_type: 'proficiency',
    provider_name: '',
    round_reference: '',
    status: 'planned',
    date: '',
    scheduled_at: '',
    closed_at: '',
    scope: '',
    outcome: 'pending',
    z_score: '',
    corrective_actions: '',
    notes: '',
    results: [],
  })
}

const openCreate = () => {
  editingRecordId.value = null
  resetForm()
  showEditor.value = true
}

const openEdit = (record) => {
  editingRecordId.value = record.id
  form.defaults({
    name: record.name || '',
    scheme_type: record.scheme_type || 'proficiency',
    provider_name: record.provider_name || '',
    round_reference: record.round_reference || '',
    status: record.status || 'planned',
    date: record.date ? String(record.date).slice(0, 10) : '',
    scheduled_at: record.scheduled_at ? String(record.scheduled_at).slice(0, 16) : '',
    closed_at: record.closed_at ? String(record.closed_at).slice(0, 16) : '',
    scope: record.scope || '',
    outcome: record.outcome || 'pending',
    z_score: record.z_score ?? '',
    corrective_actions: record.corrective_actions || '',
    notes: record.notes || '',
    results: record.results || [],
  })
  form.reset()
  form.clearErrors()
  showEditor.value = true
}

const closeEditor = () => {
  showEditor.value = false
  editingRecordId.value = null
  resetForm()
}

const submit = () => {
  if (editingRecordId.value) {
    form.put(route('proficiency_tests.update', { test: editingRecordId.value }), {
      preserveScroll: true,
      onSuccess: closeEditor,
    })
    return
  }

  form.post(route('proficiency_tests.store'), {
    preserveScroll: true,
    onSuccess: closeEditor,
  })
}

const destroy = (record) => {
  router.get(route('proficiency_tests.destroy', { recordIds: [record.id] }))
}

const restore = (record) => {
  router.get(route('proficiency_tests.restore', { recordIds: [record.id] }))
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.scheme_type = ''
  filters.filter = ''
}
</script>
