<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Matriz de responsabilidades" lede="Defina quem executa, responde, consulta e recebe informação para cada processo crítico do laboratório.">
      <template #actions>
        <span class="lims-module-chip w-fit">
          <span class="lims-status-dot lims-status-dot-release" aria-hidden="true" />
          {{ entries.length }} responsabilidades
        </span>
      </template>
    </PageHeader>

    <section class="grid gap-5 xl:grid-cols-[0.85fr_1.15fr]">
      <form class="ds-card space-y-4 p-5" @submit.prevent="submit">
        <div>
          <p class="font-mono text-xs font-semibold uppercase text-[var(--ds-text-soft)]">RACI</p>
          <h2 class="mt-1 text-lg font-semibold text-[var(--ds-text)]">{{ editingId ? 'Editar entrada' : 'Nova entrada' }}</h2>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Seleccione um departamento" :options="departmentOptions" />
          <comboboxEnhanced v-model="selectedLab" title-label="Laboratório" placeholder="Seleccione um laboratório" :options="labOptions" />
          <BaseInput v-model="form.process_area" type="text" placeholder="Área de processo" class="ds-field" />
          <BaseInput v-model="form.activity" type="text" placeholder="Actividade" class="ds-field" />
          <comboboxEnhanced v-model="selectedResponsibleUser" title-label="Responsável" placeholder="Seleccione o responsável" :options="userOptions" />
          <comboboxEnhanced v-model="selectedAccountableUser" title-label="Aprovador / accountable" placeholder="Seleccione o aprovador" :options="userOptions" />
          <BaseInput v-model="form.consulted_roles" type="text" placeholder="Funções consultadas" class="ds-field" />
          <BaseInput v-model="form.informed_roles" type="text" placeholder="Funções informadas" class="ds-field" />
          <DateTimePicker v-model="form.effective_from" type="date" class="ds-field" />
          <DateTimePicker v-model="form.effective_until" type="date" class="ds-field" />
        </div>
        <textarea v-model="form.evidence_requirement" rows="4" class="ds-field" placeholder="Evidência esperada para demonstrar execução e controlo."></textarea>
        <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--ds-text-muted)]">
          <CheckboxInput v-model="form.is_active" type="checkbox" class="ds-checkbox" />
          Entrada activa
        </label>
        <div class="flex flex-wrap gap-3">
          <button type="submit" class="ds-button ds-button-primary">{{ editingId ? 'Actualizar' : 'Guardar' }}</button>
          <button v-if="editingId" type="button" class="ds-button ds-button-secondary" @click="resetForm">Cancelar</button>
        </div>
      </form>

      <section class="ds-card overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4">
          <h2 class="text-base font-semibold text-[var(--ds-text)]">Entradas activas</h2>
          <p class="mt-1 text-sm text-[var(--ds-text-muted)]">Responsabilidade, prestação de contas, consulta e informação por processo.</p>
        </div>

        <div v-if="entries.length" class="divide-y divide-[var(--ds-border)]">
          <article v-for="entry in entries" :key="entry.id" class="px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-[var(--ds-panel-muted)] px-2.5 py-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ entry.process_area }}</span>
                  <span v-if="entry.is_active" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activa</span>
                </div>
                <h2 class="mt-2 text-base font-semibold text-[var(--ds-text)]">{{ entry.activity }}</h2>
                <p class="mt-2 text-sm text-[var(--ds-text-muted)]">{{ entry.evidence_requirement || 'Sem requisito adicional de evidência.' }}</p>
                <div class="mt-4 grid gap-2 text-sm text-[var(--ds-text-muted)] md:grid-cols-2">
                  <div><span class="font-semibold text-[var(--ds-text)]">Responsável:</span> {{ entry.responsible_user?.name || '—' }}</div>
                  <div><span class="font-semibold text-[var(--ds-text)]">Accountable:</span> {{ entry.accountable_user?.name || '—' }}</div>
                  <div><span class="font-semibold text-[var(--ds-text)]">Consultados:</span> {{ entry.consulted_roles || '—' }}</div>
                  <div><span class="font-semibold text-[var(--ds-text)]">Informados:</span> {{ entry.informed_roles || '—' }}</div>
                </div>
              </div>
              <div class="flex shrink-0 gap-2">
                <button type="button" class="ds-button ds-button-secondary min-h-0 px-3 py-2" @click="edit(entry)">Editar</button>
                <button type="button" class="ds-button ds-button-danger min-h-0 px-3 py-2" @click="destroy(entry)">Arquivar</button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="m-5 rounded-lg border border-dashed border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-10 text-center text-sm font-medium text-[var(--ds-text-muted)]">
          Ainda não existem entradas na matriz de responsabilidades.
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
  entries: Array,
  departments: Array,
  labs: Array,
  users: Array,
})

const editingId = ref(null)
const mapOptions = (items = [], labelBuilder) => items.map(item => ({
  value: item.id,
  label: labelBuilder(item),
}))

const departmentOptions = computed(() => mapOptions(props.departments, item => item.name))
const labOptions = computed(() => mapOptions(props.labs, item => item.name))
const userOptions = computed(() => mapOptions(props.users, item => item.name))

const selectedDepartment = ref(null)
const selectedLab = ref(null)
const selectedResponsibleUser = ref(null)
const selectedAccountableUser = ref(null)

const form = useForm({
  department_id: '',
  lab_id: '',
  process_area: '',
  activity: '',
  responsible_user_id: '',
  accountable_user_id: '',
  consulted_roles: '',
  informed_roles: '',
  evidence_requirement: '',
  is_active: true,
  effective_from: '',
  effective_until: '',
})

watch(selectedDepartment, (option) => {
  form.department_id = option?.value ?? ''
})

watch(selectedLab, (option) => {
  form.lab_id = option?.value ?? ''
})

watch(selectedResponsibleUser, (option) => {
  form.responsible_user_id = option?.value ?? ''
})

watch(selectedAccountableUser, (option) => {
  form.accountable_user_id = option?.value ?? ''
})

const resetForm = () => {
  editingId.value = null
  form.reset()
  form.is_active = true
  selectedDepartment.value = null
  selectedLab.value = null
  selectedResponsibleUser.value = null
  selectedAccountableUser.value = null
}

const edit = (entry) => {
  editingId.value = entry.id
  Object.assign(form, {
    department_id: entry.department_id || '',
    lab_id: entry.lab_id || '',
    process_area: entry.process_area || '',
    activity: entry.activity || '',
    responsible_user_id: entry.responsible_user_id || '',
    accountable_user_id: entry.accountable_user_id || '',
    consulted_roles: entry.consulted_roles || '',
    informed_roles: entry.informed_roles || '',
    evidence_requirement: entry.evidence_requirement || '',
    is_active: Boolean(entry.is_active),
    effective_from: entry.effective_from || '',
    effective_until: entry.effective_until || '',
  })
  selectedDepartment.value = departmentOptions.value.find(option => option.value === entry.department_id) ?? null
  selectedLab.value = labOptions.value.find(option => option.value === entry.lab_id) ?? null
  selectedResponsibleUser.value = userOptions.value.find(option => option.value === entry.responsible_user_id) ?? null
  selectedAccountableUser.value = userOptions.value.find(option => option.value === entry.accountable_user_id) ?? null
}

const submit = () => {
  if (editingId.value) {
    form.put(route('responsibility-matrix.update', editingId.value), { preserveScroll: true, onSuccess: () => resetForm() })
    return
  }

  form.post(route('responsibility-matrix.store'), { preserveScroll: true, onSuccess: () => resetForm() })
}

const destroy = (entry) => {
  router.delete(route('responsibility-matrix.destroy', entry.id), { preserveScroll: true })
}
</script>
