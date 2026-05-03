<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">Metrology</p>
      <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Registo de fontes de incerteza</h1>
      <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">
        Documente fontes de incerteza associadas a equipamento, método, ambiente, amostragem, pessoal e materiais de referência.
      </p>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
      <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ editingId ? 'Editar fonte' : 'Nova fonte' }}</h2>
        <input v-model="form.title" type="text" placeholder="Título" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        <div class="grid gap-4 md:grid-cols-2">
          <select v-model="form.source_type" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <option value="">Tipo de fonte</option>
            <option value="equipment">Equipamento</option>
            <option value="method">Método</option>
            <option value="environment">Ambiente</option>
            <option value="sampling">Amostragem</option>
            <option value="personnel">Pessoal</option>
            <option value="reference_material">Material de referência</option>
          </select>
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Seleccione um departamento" :options="departmentOptions" />
          <comboboxEnhanced v-model="selectedParameter" title-label="Parâmetro associado" placeholder="Seleccione o parâmetro associado" :options="parameterOptions" />
          <comboboxEnhanced v-model="selectedInventoryItem" title-label="Equipamento / item associado" placeholder="Seleccione o equipamento ou item" :options="inventoryItemOptions" />
        </div>
        <textarea v-model="form.description" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Descrição da fonte de incerteza"></textarea>
        <textarea v-model="form.estimation_method" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Como a incerteza é estimada"></textarea>
        <textarea v-model="form.control_strategy" rows="3" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Como a fonte é monitorizada e controlada"></textarea>
        <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-slate-700">
          Fonte ativa
        </label>
        <div class="flex gap-3">
          <button type="submit" class="rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800">{{ editingId ? 'Atualizar' : 'Guardar' }}</button>
          <button v-if="editingId" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="resetForm">Cancelar</button>
        </div>
      </form>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div v-if="sources.length" class="space-y-4">
          <article v-for="source in sources" :key="source.id" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ source.source_type }}</span>
                  <span v-if="source.is_active" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                </div>
                <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ source.title }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ source.description || 'Sem descrição adicional.' }}</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                  <div>Departamento: {{ source.department?.name || '—' }}</div>
                  <div>Parâmetro: {{ source.parameter?.name || '—' }}</div>
                  <div>Item associado: {{ source.inventory_item?.name || '—' }}</div>
                </div>
              </div>
              <div class="flex gap-2">
                <button type="button" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="edit(source)">Editar</button>
                <button type="button" class="rounded-2xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="destroy(source)">Arquivar</button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
          Nenhuma fonte de incerteza registada.
        </div>
      </section>
    </section>
  </div>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  sources: Array,
  departments: Array,
  parameters: Array,
  inventoryItems: Array,
})

const editingId = ref(null)
const mapOptions = (items = [], labelBuilder) => items.map(item => ({
  value: item.id,
  label: labelBuilder(item),
}))

const departmentOptions = computed(() => mapOptions(props.departments, item => item.name))
const parameterOptions = computed(() => mapOptions(props.parameters, item => `${item.name}${item.code ? ` · ${item.code}` : ''}`))
const inventoryItemOptions = computed(() => mapOptions(props.inventoryItems, item => `${item.name}${item.code ? ` · ${item.code}` : ''}`))

const selectedDepartment = ref(null)
const selectedParameter = ref(null)
const selectedInventoryItem = ref(null)

const form = useForm({
  title: '',
  source_type: '',
  department_id: '',
  parameter_id: '',
  inventory_item_id: '',
  description: '',
  estimation_method: '',
  control_strategy: '',
  is_active: true,
})

watch(selectedDepartment, (option) => {
  form.department_id = option?.value ?? ''
})

watch(selectedParameter, (option) => {
  form.parameter_id = option?.value ?? ''
})

watch(selectedInventoryItem, (option) => {
  form.inventory_item_id = option?.value ?? ''
})

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.is_active = true
  selectedDepartment.value = null
  selectedParameter.value = null
  selectedInventoryItem.value = null
}

const edit = (source) => {
  editingId.value = source.id
  Object.assign(form, {
    title: source.title || '',
    source_type: source.source_type || '',
    department_id: source.department_id || '',
    parameter_id: source.parameter_id || '',
    inventory_item_id: source.inventory_item_id || '',
    description: source.description || '',
    estimation_method: source.estimation_method || '',
    control_strategy: source.control_strategy || '',
    is_active: Boolean(source.is_active),
  })
  selectedDepartment.value = departmentOptions.value.find(option => option.value === source.department_id) ?? null
  selectedParameter.value = parameterOptions.value.find(option => option.value === source.parameter_id) ?? null
  selectedInventoryItem.value = inventoryItemOptions.value.find(option => option.value === source.inventory_item_id) ?? null
}

const submit = () => {
  if (editingId.value) {
    form.put(route('uncertainty-sources.update', editingId.value), { preserveScroll: true, onSuccess: () => resetForm() })
    return
  }

  form.post(route('uncertainty-sources.store'), { preserveScroll: true, onSuccess: () => resetForm() })
}

const destroy = (source) => {
  router.delete(route('uncertainty-sources.destroy', source.id), { preserveScroll: true })
}
</script>
