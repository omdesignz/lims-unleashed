<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import {
  ArrowLeft as ArrowLeftIcon,
  FlaskConical as BeakerIcon,
  CircleCheck as CheckCircleIcon,
  IdCard as IdentificationIcon,
  Phone as PhoneIcon,
  Users as UserGroupIcon,
} from '@lucide/vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watchEffect } from 'vue'

const props = defineProps({
  lab: Object,
  labsCount: { type: Number, default: 0 },
  activeLabsCount: { type: Number, default: 0 },
  supervisors: { type: Array, default: () => [] },
  technicalHeads: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  lastUpdated: String,
})

const mapOptions = (items) => items.map((item) => ({ value: item.id, label: item.code ? `${item.name} (${item.code})` : item.name }))
const supervisorOptions = computed(() => mapOptions(props.supervisors))
const technicalHeadOptions = computed(() => mapOptions(props.technicalHeads))
const departmentOptions = computed(() => mapOptions(props.departments))

const selectedSupervisor = ref(supervisorOptions.value.find((option) => option.value === props.lab?.supervisor_id) ?? null)
const selectedTechnicalHead = ref(technicalHeadOptions.value.find((option) => option.value === props.lab?.technical_head_id) ?? null)
const selectedDepartment = ref(departmentOptions.value.find((option) => option.value === props.lab?.department_id) ?? null)

const form = useForm({
  name: props.lab?.name ?? '',
  code: props.lab?.code ?? '',
  room_no: props.lab?.room_no ?? '',
  description: props.lab?.description ?? '',
  contact: props.lab?.contact ?? '',
  extension: props.lab?.extension ?? '',
  email: props.lab?.email ?? '',
  supervisor_id: props.lab?.supervisor_id ?? '',
  technical_head_id: props.lab?.technical_head_id ?? '',
  department_id: props.lab?.department_id ?? '',
})

watchEffect(() => {
  form.supervisor_id = selectedSupervisor.value?.value ?? ''
  form.technical_head_id = selectedTechnicalHead.value?.value ?? ''
  form.department_id = selectedDepartment.value?.value ?? ''
})

const isEditing = computed(() => Boolean(props.lab?.id))
const completionChecks = computed(() => [
  { label: 'Identificação', complete: Boolean(form.name.trim() && form.code.trim()) },
  { label: 'Contacto', complete: Boolean(form.contact.trim() || form.email.trim() || form.extension.trim()) },
  { label: 'Responsabilidade', complete: Boolean(form.supervisor_id || form.technical_head_id || form.department_id) },
])

function submit() {
  if (isEditing.value) {
    form.put(route('vap-labs.labs.update', props.lab.id), { preserveScroll: true })
    return
  }

  form.post(route('vap-labs.labs.store'), { preserveScroll: true })
}

function resetForm() {
  form.reset()
  selectedSupervisor.value = supervisorOptions.value.find((option) => option.value === props.lab?.supervisor_id) ?? null
  selectedTechnicalHead.value = technicalHeadOptions.value.find((option) => option.value === props.lab?.technical_head_id) ?? null
  selectedDepartment.value = departmentOptions.value.find((option) => option.value === props.lab?.department_id) ?? null
}
</script>

<template>
  <form class="pl-page space-y-5" @submit.prevent="submit">
    <PageHeader :title="isEditing ? lab.name : $t('gestlab.general.labels.vap_labs.buttons.add_lab')" :lede="$t('gestlab.general.labels.vap_labs.description')">
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="router.visit(route('vap-labs.labs.index'))">
          <ArrowLeftIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_labs.buttons.back_to_labs') }}
        </button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.name.trim() || !form.code.trim()">
          <CheckCircleIcon class="h-4 w-4" />
          {{ form.processing ? $t('gestlab.general.labels.vap_labs.buttons.processing') : $t('gestlab.general.labels.vap_labs.buttons.save_lab') }}
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.vap_labs.stats.total_labs') }}</dt>
        <dd class="pl-cell-text">{{ labsCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.vap_labs.stats.active_labs') }}</dt>
        <dd class="pl-cell-text">{{ activeLabsCount }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.vap_labs.last_updated') }}</dt>
        <dd class="pl-cell-text">{{ lastUpdated || '-' }}</dd>
      </div>
    </dl>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
      <div class="space-y-5">
        <section class="ds-panel overflow-hidden">
          <header class="flex items-center gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <IdentificationIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
            <div><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.basic_info') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.lab_information') }}</h2></div>
          </header>
          <div class="grid gap-5 p-5 md:grid-cols-2 xl:grid-cols-3">
            <div class="ds-field-group"><label for="lab-name" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.name') }} <span class="ds-field-required">*</span></label><BaseInput id="lab-name" v-model="form.name" class="ds-field" :aria-invalid="Boolean(form.errors.name)" /><p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p></div>
            <div class="ds-field-group"><label for="lab-code" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.code') }} <span class="ds-field-required">*</span></label><BaseInput id="lab-code" v-model="form.code" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" /><p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p></div>
            <div class="ds-field-group"><label for="lab-room" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.room_no') }}</label><BaseInput id="lab-room" v-model="form.room_no" class="ds-field" :aria-invalid="Boolean(form.errors.room_no)" /><p v-if="form.errors.room_no" class="ds-field-error">{{ form.errors.room_no }}</p></div>
            <div class="ds-field-group md:col-span-2 xl:col-span-3"><label for="lab-description" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.description') }}</label><textarea id="lab-description" v-model="form.description" rows="4" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.description)" /><p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p></div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="flex items-center gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <PhoneIcon class="h-5 w-5 text-cyan-700 dark:text-cyan-300" />
            <div><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.status.contact_info') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.contact') }}</h2></div>
          </header>
          <div class="grid gap-5 p-5 md:grid-cols-3">
            <div class="ds-field-group"><label for="lab-contact" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.contact') }}</label><BaseInput id="lab-contact" v-model="form.contact" class="ds-field" :aria-invalid="Boolean(form.errors.contact)" /><p v-if="form.errors.contact" class="ds-field-error">{{ form.errors.contact }}</p></div>
            <div class="ds-field-group"><label for="lab-extension" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.extension') }}</label><BaseInput id="lab-extension" v-model="form.extension" class="ds-field" :aria-invalid="Boolean(form.errors.extension)" /><p v-if="form.errors.extension" class="ds-field-error">{{ form.errors.extension }}</p></div>
            <div class="ds-field-group"><label for="lab-email" class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.email') }}</label><BaseInput id="lab-email" v-model="form.email" type="email" class="ds-field" :aria-invalid="Boolean(form.errors.email)" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="flex items-center gap-3 border-b border-[var(--ds-border)] px-5 py-4">
            <UserGroupIcon class="h-5 w-5 text-amber-700 dark:text-amber-300" />
            <div><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.staff_assignment') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.department') }}</h2></div>
          </header>
          <div class="grid gap-5 p-5 md:grid-cols-3">
            <div class="ds-field-group"><label class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.supervisor') }}</label><comboboxEnhanced v-model="selectedSupervisor" :options="supervisorOptions" :has-error="Boolean(form.errors.supervisor_id)" :placeholder="$t('gestlab.general.labels.vap_labs.select_supervisor')" /><p v-if="form.errors.supervisor_id" class="ds-field-error">{{ form.errors.supervisor_id }}</p></div>
            <div class="ds-field-group"><label class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.technical_head') }}</label><comboboxEnhanced v-model="selectedTechnicalHead" :options="technicalHeadOptions" :has-error="Boolean(form.errors.technical_head_id)" :placeholder="$t('gestlab.general.labels.vap_labs.select_technical_head')" /><p v-if="form.errors.technical_head_id" class="ds-field-error">{{ form.errors.technical_head_id }}</p></div>
            <div class="ds-field-group"><label class="ds-field-label">{{ $t('gestlab.general.labels.vap_labs.department') }}</label><comboboxEnhanced v-model="selectedDepartment" :options="departmentOptions" :has-error="Boolean(form.errors.department_id)" :placeholder="$t('gestlab.general.labels.vap_labs.select_department')" /><p v-if="form.errors.department_id" class="ds-field-error">{{ form.errors.department_id }}</p></div>
          </div>
        </section>
      </div>

      <aside class="space-y-5">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-4 py-3"><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.status.title') }}</p><h2 class="ds-heading mt-1 text-sm">{{ $t('gestlab.general.labels.vap_labs.stats.title') }}</h2></header>
          <ul class="divide-y divide-[var(--ds-border)]">
            <li v-for="check in completionChecks" :key="check.label" class="flex items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-[var(--ds-text-muted)]"><span>{{ check.label }}</span><span class="ds-badge ring-1 ring-inset" :class="check.complete ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'">{{ check.complete ? $t('gestlab.general.labels.vap_labs.status.complete') : $t('gestlab.general.labels.vap_labs.status.incomplete') }}</span></li>
          </ul>
        </section>
        <section class="ds-panel p-4">
          <BeakerIcon class="h-6 w-6 text-[var(--ds-text-soft)]" />
          <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">{{ form.name || $t('gestlab.general.labels.vap_labs.name') }}</p>
          <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-muted)]">{{ form.code || '-' }}</p>
          <div class="mt-4 flex gap-2"><button type="button" class="ds-button ds-button-secondary flex-1" @click="resetForm">{{ $t('gestlab.general.labels.vap_labs.buttons.reset') }}</button><button type="submit" class="ds-button ds-button-primary flex-1" :disabled="form.processing || !form.name.trim() || !form.code.trim()">{{ $t('gestlab.general.buttons.save') }}</button></div>
        </section>
      </aside>
    </div>
  </form>
</template>
