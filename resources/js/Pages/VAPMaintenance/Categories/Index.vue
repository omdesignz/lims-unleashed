<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <Head title="Categorias de manutenção" />
    <p v-if="mutationError" class="ds-field-error" role="alert">{{ mutationError }}</p>
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
        <div class="min-w-0">
          <p class="text-xs font-black uppercase tracking-[0.18em] text-[var(--ds-text-soft)]">
            Biblioteca metrologica
          </p>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <TagIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]"> Categorias de manutenção </h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]"> Controle os tipos de manutenção, calibração e verificação usados nos planos de equipamento. </p>
            </div>
          </div>
        </div>

        <button
          v-if="can.create && !archived"
          type="button"
          class="ds-button ds-button-primary"
          @click="showCreateModal = true"
        >
          <PlusIcon class="h-4 w-4" />
          Nova categoria
        </button>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <article
          v-for="stat in statsCards"
          :key="stat.label"
          class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4"
        >
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
            {{ stat.label }}
          </p>
          <div class="mt-3 flex items-end justify-between gap-3">
            <p class="text-2xl font-black text-[var(--ds-text)]">
              {{ stat.value }}
            </p>
            <component :is="stat.icon" :class="['h-5 w-5', stat.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ stat.detail }}
          </p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 lg:grid-cols-[minmax(18rem,32rem)_minmax(0,1fr)] lg:items-end">
        <label class="ds-field-group">
          <span class="ds-field-label">Pesquisar categoria</span>
          <span class="relative block">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              v-model="search"
              type="search"
              placeholder="Nome, código ou descrição"
              class="ds-field pl-10"
            />
          </span>
        </label>

        <div class="flex flex-wrap items-center justify-start gap-2 lg:justify-end">
          <Link :href="route('vap-maintenance.categories', archived ? {} : { archived: 1 })" class="ds-button ds-button-secondary">{{ archived ? 'Categorias activas' : 'Arquivo' }}</Link>
          <span class="rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-1.5 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-muted)]">
            {{ categoryTotal }} categorias
          </span>
          <span
            v-if="hasSearch"
            class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-black uppercase tracking-[0.12em] text-cyan-800 dark:border-cyan-400/30 dark:bg-cyan-400/10 dark:text-cyan-100"
          >
            Pesquisa activa
          </span>
        </div>
      </div>
    </section>

    <section v-if="categoryItems.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="category in categoryItems"
        :key="category.id"
        class="ds-card flex min-h-full flex-col overflow-hidden p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ category.is_preset ? 'Predefinição partilhada · Só leitura' : 'Categoria privada do laboratório' }}</p>
            <h2 class="mt-2 truncate text-base font-black text-[var(--ds-text)]">
              {{ category.name }}
            </h2>
          </div>
          <span class="rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2.5 py-1 font-mono text-[0.68rem] font-black uppercase text-[var(--ds-text-muted)]">
            {{ category.code || 'S/C' }}
          </span>
        </div>

        <div class="mt-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
            Descrição operacional
          </p>
          <p class="mt-2 line-clamp-3 min-h-16 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
            {{ category.description || 'Sem descrição operacional definida.' }}
          </p>
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
            <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
              Criada em
            </dt>
            <dd class="mt-1 font-bold text-[var(--ds-text)]">
              {{ formatDate(category.created_at) || 'Sem data' }}
            </dd>
          </div>
          <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
            <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
              Registo
            </dt>
            <dd class="mt-1 font-mono text-xs font-bold text-[var(--ds-text-muted)]">
              #{{ category.id }}
            </dd>
          </div>
        </dl>

        <div class="mt-auto flex items-center justify-end gap-2 pt-5">
          <button
            v-if="can.edit && !category.is_preset && !category.deleted"
            type="button"
            class="ds-table-action"
            title="Editar categoria"
            @click="editCategory(category)"
          >
            <PencilIcon class="h-4 w-4" />
            <span class="sr-only">Editar categoria</span>
          </button>
          <button
            v-if="can.archive && !category.is_preset && !category.deleted"
            type="button"
            class="ds-table-action text-rose-700 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-800 dark:text-rose-200 dark:hover:border-rose-400/30 dark:hover:bg-rose-400/10"
            title="Arquivar categoria"
            :disabled="mutationPending"
            @click="deleteCategory(category)"
          >
            <TrashIcon class="h-4 w-4" />
            <span class="sr-only">Arquivar categoria</span>
          </button>
          <button v-if="can.restore && !category.is_preset && category.deleted" type="button" class="ds-table-action" :disabled="mutationPending" @click="restoreCategory(category)">Restaurar</button>
        </div>
      </article>
    </section>

    <section v-else class="ds-empty-state p-8 text-center">
      <TagIcon class="mx-auto h-11 w-11 text-[var(--ds-text-soft)]" />
      <h2 class="mt-4 text-base font-black text-[var(--ds-text)]">
        Nenhuma categoria encontrada
      </h2>
      <p class="mx-auto mt-2 max-w-md text-sm font-medium leading-6 text-[var(--ds-text-muted)]"> Crie categorias para separar calibração interna, calibração externa, manutenção preventiva e verificacoes. </p>
      <button
        v-if="can.create && !archived"
        type="button"
        class="ds-button ds-button-primary mt-6"
        @click="showCreateModal = true"
      >
        <PlusIcon class="h-4 w-4" />
        Criar categoria
      </button>
    </section>

    <section v-if="categoryItems.length > 0" class="ds-table-summary flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm font-semibold text-[var(--ds-text-muted)]"> Biblioteca de manutenção pronta para planos preventivos e calibracoes. </p>
      <Pagination
        :links="categories.links"
        :from="categories.from"
        :to="categories.to"
        :total="categories.total"
        :current_page="categories.current_page"
        :last_page="categories.last_page"
      />
    </section>

    <Modal :show="showCreateModal || Boolean(editingCategory)" :closeable="!form.processing" @close="closeModal">
      <div class="p-6">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] pb-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--ds-text-soft)]">
              Biblioteca metrologica
            </p>
            <h2 class="mt-2 text-lg font-black text-[var(--ds-text)]">
              {{ editingCategory ? 'Editar categoria' : 'Nova categoria de manutenção' }}
            </h2>
          </div>
          <button
            type="button"
            class="ds-table-action"
            title="Fechar"
            @click="closeModal"
          >
            <XMarkIcon class="h-4 w-4" />
            <span class="sr-only">Fechar</span>
          </button>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="submitForm">
          <p v-if="form.hasErrors" class="ds-field-error" role="alert">{{ Object.values(form.errors).join(' ') }}</p>
          <p v-if="form.processing" class="ds-copy" role="status">A guardar categoria…</p>
          <label class="ds-field-group">
            <span class="ds-field-label">Nome da categoria <span class="ds-field-required">*</span></span>
            <BaseInput
              v-model="form.name"
              type="text"
              required
              :class="fieldClass('name')"
              :aria-invalid="Boolean(form.errors.name)"
              placeholder="Ex: Calibração interna"
            />
            <span v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</span>
          </label>

          <label class="ds-field-group">
            <span class="ds-field-label">Código</span>
            <BaseInput
              v-model="form.code"
              required
              :readonly="Boolean(editingCategory?.code_locked)"
              type="text"
              :class="fieldClass('code')"
              :aria-invalid="Boolean(form.errors.code)"
              placeholder="Ex: CAL_INT"
            />
            <span v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</span>
            <span v-else class="ds-field-hint">{{ editingCategory?.code_locked ? 'Código fixo: já integra números emitidos.' : 'Código único com letras maiúsculas, números, hífen ou sublinhado. Fica fixo após o primeiro uso.' }}</span>
          </label>

          <label class="ds-field-group">
            <span class="ds-field-label">Descrição</span>
            <textarea
              v-model="form.description"
              rows="3"
              class="ds-field min-h-28"
              placeholder="Descreva quando esta categoria deve ser usada."
            />
          </label>

          <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="ds-button ds-button-secondary"
              @click="closeModal"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="ds-button ds-button-primary"
              :disabled="form.processing"
              :aria-busy="form.processing"
            >
              <CheckIcon class="h-4 w-4" />
              {{ form.processing ? 'A processar...' : (editingCategory ? 'Actualizar' : 'Criar') }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import {
  Check as CheckIcon,
  Hash as HashtagIcon,
  Search as MagnifyingGlassIcon,
  Pencil as PencilIcon,
  Plus as PlusIcon,
  Tag as TagIcon,
  Trash2 as TrashIcon,
  Wrench as WrenchScrewdriverIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import { debounce } from 'lodash'
import Modal from '@/Components/modal.vue'
import Pagination from '@/Components/pagination.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import { useRecordArchive } from '@/Composables/useRecordArchive'

const props = defineProps({
  categories: Object,
  filters: Object,
  stats: Object,
  can: { type: Object, default: () => ({}) },
})

const search = ref(props.filters?.search || '')
const showCreateModal = ref(false)
const editingCategory = ref(null)
const archived = computed(() => Boolean(Number(props.filters?.archived ?? 0)))
const archive = useRecordArchive({
  destroyUrl: ids => route('vap-maintenance.categories.destroy', ids[0]),
  restoreUrl: ids => route('vap-maintenance.categories.restore', ids[0]),
})
const mutationPending = archive.processing
const mutationError = computed(() => archive.failed.value ? archive.message.value : '')

const form = useForm({
  name: '',
  code: '',
  description: '',
})

const categoryItems = computed(() => props.categories?.data ?? [])
const categoryTotal = computed(() => props.categories?.total ?? categoryItems.value.length)
const hasSearch = computed(() => search.value.trim().length > 0)

const statsCards = computed(() => [
  {
    label: 'Categorias',
    value: categoryTotal.value,
    detail: 'Tipos disponíveis',
    icon: TagIcon,
    tone: 'text-cyan-700 dark:text-cyan-200',
  },
  {
    label: 'Predefinições',
    value: props.stats?.presets ?? 0,
    detail: 'Partilhadas e só de leitura',
    icon: HashtagIcon,
    tone: 'text-emerald-700 dark:text-emerald-200',
  },
  {
    label: 'Do laboratório',
    value: props.stats?.owned ?? 0,
    detail: 'Categorias privadas',
    icon: WrenchScrewdriverIcon,
    tone: 'text-amber-700 dark:text-amber-200',
  },
])

const formatDate = (dateString) => {
  if (!dateString) {
    return ''
  }

  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const fieldClass = (field) => [
  'ds-field',
  form.errors[field] ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20' : '',
]

const applySearch = debounce(() => {
  router.get(route('vap-maintenance.categories'), { search: search.value, archived: archived.value ? 1 : undefined }, {
    preserveState: true,
    replace: true,
  })
}, 300)

const editCategory = (category) => {
  if (!props.can.edit || category.is_preset || category.deleted) return
  form.clearErrors()
  editingCategory.value = category
  form.name = category.name
  form.code = category.code
  form.description = category.description
}

const deleteCategory = (category) => {
  if (!props.can.archive || category.is_preset || !confirm('Arquivar esta categoria? As tarefas existentes mantêm o seu histórico.')) return
  changeArchive(category, false)
}

const restoreCategory = category => {
  if (!props.can.restore || category.is_preset) return
  changeArchive(category, true)
}

const changeArchive = (category, restore) => {
  archive.submit(restore ? 'restore' : 'delete', [category.id])
}

const submitForm = () => {
  if (form.processing) return
  form.clearErrors()
  const options = {
    onSuccess: () => closeModal(true),
    onHttpException: () => {
      form.setError('request', 'O registo ou a autorização já não está disponível. Actualize a lista.')
      return false
    },
    onNetworkError: () => {
      form.setError('request', 'Falha de ligação. Os dados foram mantidos; tente novamente.')
      return false
    },
    onCancel: () => form.setError('request', 'Operação interrompida. Confirme a lista antes de repetir.'),
  }
  try {
    if (editingCategory.value) form.put(route('vap-maintenance.categories.update', editingCategory.value.id), options)
    else form.post(route('vap-maintenance.categories.store'), options)
  } catch {
    form.setError('request', 'Não foi possível iniciar a operação. Os dados foram mantidos.')
  }
}

const closeModal = (saved = false) => {
  if (form.processing && saved !== true) return
  showCreateModal.value = false
  editingCategory.value = null
  form.reset()
  form.clearErrors()
}

watch(search, applySearch)
onUnmounted(() => applySearch.cancel())
</script>
