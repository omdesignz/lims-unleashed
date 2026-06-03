<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-700">ISO 17025</p>
          <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">Matriz de responsabilidades</h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">
            Defina quem executa, responde, consulta e recebe informação para cada processo crítico do laboratório.
          </p>
        </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
      <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ editingId ? 'Editar entrada' : 'Nova entrada' }}</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <comboboxEnhanced v-model="selectedDepartment" title-label="Departamento" placeholder="Seleccione um departamento" :options="departmentOptions" />
          <comboboxEnhanced v-model="selectedLab" title-label="Laboratório" placeholder="Seleccione um laboratório" :options="labOptions" />
          <input v-model="form.process_area" type="text" placeholder="Área de processo" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.activity" type="text" placeholder="Atividade" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <comboboxEnhanced v-model="selectedResponsibleUser" title-label="Responsável" placeholder="Seleccione o responsável" :options="userOptions" />
          <comboboxEnhanced v-model="selectedAccountableUser" title-label="Aprovador / accountable" placeholder="Seleccione o aprovador" :options="userOptions" />
          <input v-model="form.consulted_roles" type="text" placeholder="Funções consultadas" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.informed_roles" type="text" placeholder="Funções informadas" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.effective_from" type="date" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
          <input v-model="form.effective_until" type="date" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        </div>
        <textarea v-model="form.evidence_requirement" rows="4" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100" placeholder="Evidência esperada para demonstrar execução e controlo."></textarea>
        <label class="inline-flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-slate-700">
          Entrada ativa
        </label>
        <div class="flex gap-3">
          <button type="submit" class="rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800">{{ editingId ? 'Atualizar' : 'Guardar' }}</button>
          <button v-if="editingId" type="button" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="resetForm">Cancelar</button>
        </div>
      </form>

      <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div v-if="entries.length" class="space-y-4">
          <article v-for="entry in entries" :key="entry.id" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ entry.process_area }}</span>
                  <span v-if="entry.is_active" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativa</span>
                </div>
                <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ entry.activity }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ entry.evidence_requirement || 'Sem requisito adicional de evidência.' }}</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                  <div>Responsável: {{ entry.responsible_user?.name || '—' }}</div>
                  <div>Accountable: {{ entry.accountable_user?.name || '—' }}</div>
                  <div>Consultados: {{ entry.consulted_roles || '—' }}</div>
                  <div>Informados: {{ entry.informed_roles || '—' }}</div>
                </div>
              </div>
              <div class="flex gap-2">
                <button type="button" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="edit(entry)">Editar</button>
                <button type="button" class="rounded-2xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50" @click="destroy(entry)">Arquivar</button>
              </div>
            </div>
          </article>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
          Ainda não existem entradas na matriz de responsabilidades.
        </div>
      </section>
    </section>
  </div>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
