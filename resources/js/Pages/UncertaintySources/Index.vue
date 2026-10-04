<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Registo de fontes de incerteza" lede="Documente fontes de incerteza associadas a equipamento, método, ambiente, amostragem, pessoal e materiais de referência.">
      <template #actions>
        <span class="lims-module-chip w-fit">
          <span class="lims-status-dot lims-status-dot-instrument" aria-hidden="true" />
          {{ sources.length }} fontes
        </span>
      </template>
    </PageHeader>

    <section class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
      <form class="ds-card space-y-4 p-5" @submit.prevent="submit">
        <div>
          <p class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">Incerteza</p>
          <h2 class="mt-1 text-lg font-semibold text-[var(--ds-text)]">{{ editingId ? 'Editar fonte' : 'Nova fonte' }}</h2>
        </div>
        <BaseInput v-model="form.title" type="text" placeholder="Título" class="ds-field" />
        <div class="grid gap-4 md:grid-cols-2">
          <BaseSelect v-model="form.source_type" class="ds-field">
            <option value="">Tipo de fonte</option>
            <option value="equipment">Equipamento</option>
            <option value="method">Método</option>
            <option value="environment">Ambiente</option>
            <option value="sampling">Amostragem</option>
            <option value="personnel">Pessoal</option>
            <option value="reference_material">Material de referência</option>
          </BaseSelect>
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Seleccione um departamento" :options="departmentOptions" />
          <comboboxEnhanced v-model="selectedParameter" title-label="Parâmetro associado" placeholder="Seleccione o parâmetro associado" :options="parameterOptions" />
          <comboboxEnhanced v-model="selectedInventoryItem" title-label="Equipamento / item associado" placeholder="Seleccione o equipamento ou item" :options="inventoryItemOptions" />
        </div>
        <textarea v-model="form.description" rows="3" class="ds-field" placeholder="Descrição da fonte de incerteza"></textarea>
        <textarea v-model="form.estimation_method" rows="3" class="ds-field" placeholder="Como a incerteza é estimada"></textarea>
        <textarea v-model="form.control_strategy" rows="3" class="ds-field" placeholder="Como a fonte é monitorizada e controlada"></textarea>
        <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--ds-text-muted)]">
          <CheckboxInput v-model="form.is_active" type="checkbox" class="ds-checkbox" />
          Fonte activa
        </label>
        <div class="flex flex-wrap gap-3">
          <button type="submit" class="ds-button ds-button-primary">{{ editingId ? 'Actualizar' : 'Guardar' }}</button>
          <button v-if="editingId" type="button" class="ds-button ds-button-secondary" @click="resetForm">Cancelar</button>
        </div>
      </form>

      <section class="ds-card overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Fontes documentadas</h2>
          <p class="mt-1 text-sm text-[var(--ds-text-muted)]">Rastreie impacto metrológico, método de estimação e estratégia de controlo.</p>
        </div>

        <div v-if="sources.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="source in sources" :key="source.id" class="px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-[var(--ds-panel-muted)] px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-[var(--ds-text-muted)]">{{ source.source_type }}</span>
                  <span v-if="source.is_active" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activa</span>
                </div>
                <h2 class="mt-2 text-base font-semibold text-[var(--ds-text)]">{{ source.title }}</h2>
                <p class="mt-2 text-sm text-[var(--ds-text-muted)]">{{ source.description || 'Sem descrição adicional.' }}</p>
                <div class="mt-4 grid gap-2 text-sm text-[var(--ds-text-muted)] md:grid-cols-2">
                  <div><span class="font-semibold text-[var(--ds-text)]">Departamento:</span> {{ source.department?.name || '—' }}</div>
                  <div><span class="font-semibold text-[var(--ds-text)]">Parâmetro:</span> {{ source.parameter?.name || '—' }}</div>
                  <div><span class="font-semibold text-[var(--ds-text)]">Item associado:</span> {{ source.inventory_item?.name || '—' }}</div>
                </div>
              </div>
              <div class="flex shrink-0 gap-2">
                <button type="button" class="ds-button ds-button-secondary min-h-0 px-3 py-2" @click="edit(source)">Editar</button>
                <button type="button" class="ds-button ds-button-danger min-h-0 px-3 py-2" @click="destroy(source)">Arquivar</button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="m-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-10 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Nenhuma fonte de incerteza registada.
        </div>
      </section>
    </section>
  </div>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
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
