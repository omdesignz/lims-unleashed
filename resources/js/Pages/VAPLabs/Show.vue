<script setup>
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import {
  ArrowRight as ArrowRightIcon,
  FlaskConical as BeakerIcon,
  Landmark as BuildingLibraryIcon,
  Mail as EnvelopeIcon,
  MapPin as MapPinIcon,
  SquarePen as PencilSquareIcon,
  Phone as PhoneIcon,
  Trash2 as TrashIcon,
  CircleUser as UserCircleIcon,
  Users as UserGroupIcon,
} from '@lucide/vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  lab: Object,
  stats: { type: Object, default: () => ({}) },
})

const isDeleteDialogOpen = ref(false)
const formatDate = (value) => value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-'

function deleteLab() {
  router.delete(route('vap-labs.labs.destroy', props.lab.id))
}
</script>

<template>
  <div class="pl-page space-y-5">
    <PageHeader :trail="[{ title: 'Laboratórios', url: route('vap-labs.labs.index') }, { title: lab.name }]" :title="lab.name" :lede="lab.description || $t('gestlab.general.labels.vap_labs.no_description')">
      <template #badges>
        <span class="ds-chip pl-num">{{ lab.code }}</span>
        <StatusChip tone="ok">{{ $t('gestlab.general.labels.vap_labs.status.active') }}</StatusChip>
      </template>
      <template #actions>
        <Link :href="route('vap-labs.labs.edit', lab.id)" class="ds-button ds-button-primary"><PencilSquareIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.buttons.edit') }}</Link>
      </template>
    </PageHeader>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
      <div class="space-y-5">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.lab_information') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.basic_info') }}</h2></header>
          <dl class="grid sm:grid-cols-2 lg:grid-cols-3">
            <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:border-r"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><MapPinIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.room_no') }}</dt><dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ lab.room_no || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd></div>
            <div class="border-b border-[var(--ds-border)] px-5 py-4 lg:border-r"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><BuildingLibraryIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.department') }}</dt><dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ lab.department?.name || $t('gestlab.general.labels.vap_labs.not_assigned') }}</dd></div>
            <div class="border-b border-[var(--ds-border)] px-5 py-4"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><PhoneIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.contact') }}</dt><dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ lab.contact || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd></div>
            <div class="px-5 py-4 sm:border-r"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.extension') }}</dt><dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ lab.extension || '-' }}</dd></div>
            <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 lg:border-r"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><EnvelopeIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.email') }}</dt><dd class="mt-2 break-all text-sm font-bold text-[var(--ds-text)]">{{ lab.email || '-' }}</dd></div>
            <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.created_at') }}</dt><dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(lab.created_at) }}</dd></div>
          </dl>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.staff_assignment') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.status.staff_assignment') }}</h2></header>
          <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0">
            <article class="flex items-center gap-3 p-5"><UserCircleIcon class="h-9 w-9 text-[var(--ds-text-soft)]" /><div class="min-w-0"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.supervisor') }}</p><p class="mt-1 truncate text-sm font-bold text-[var(--ds-text)]">{{ lab.supervisor?.name || $t('gestlab.general.labels.vap_labs.not_assigned') }}</p><p v-if="lab.supervisor?.email" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ lab.supervisor.email }}</p></div></article>
            <article class="flex items-center gap-3 p-5"><UserGroupIcon class="h-9 w-9 text-[var(--ds-text-soft)]" /><div class="min-w-0"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.technical_head') }}</p><p class="mt-1 truncate text-sm font-bold text-[var(--ds-text)]">{{ lab.technical_head?.name || $t('gestlab.general.labels.vap_labs.not_assigned') }}</p><p v-if="lab.technical_head?.email" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ lab.technical_head.email }}</p></div></article>
          </div>
        </section>

        <section v-if="lab.parent_lab || lab.sub_labs?.length" class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.lab_hierarchy') }}</p><h2 class="ds-heading mt-1 text-base">{{ $t('gestlab.general.labels.vap_labs.sub_labs') }}</h2></header>
          <div class="divide-y divide-[var(--ds-border)]">
            <Link v-if="lab.parent_lab" :href="route('vap-labs.labs.show', lab.parent_lab.id)" class="flex items-center gap-3 px-5 py-4 hover:bg-[var(--ds-panel-subtle)]"><BuildingLibraryIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /><div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.parent_lab') }}</p><p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ lab.parent_lab.name }}</p></div><ArrowRightIcon class="h-4 w-4" /></Link>
            <Link v-for="subLab in lab.sub_labs" :key="subLab.id" :href="route('vap-labs.labs.show', subLab.id)" class="flex items-center gap-3 px-5 py-4 hover:bg-[var(--ds-panel-subtle)]"><BeakerIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ subLab.name }}</p><p class="font-mono text-xs font-semibold text-[var(--ds-text-muted)]">{{ subLab.code }}</p></div><ArrowRightIcon class="h-4 w-4" /></Link>
          </div>
        </section>
      </div>

      <aside class="space-y-5">
        <section class="ds-panel overflow-hidden"><header class="border-b border-[var(--ds-border)] px-4 py-3"><p class="ds-kicker">{{ $t('gestlab.general.labels.vap_labs.stats.title') }}</p></header><dl class="divide-y divide-[var(--ds-border)]"><div class="flex justify-between gap-3 px-4 py-3 text-sm"><dt class="font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_labs.stats.total_equipment') }}</dt><dd class="font-black text-[var(--ds-text)]">{{ stats.total_equipment ?? 0 }}</dd></div><div class="flex justify-between gap-3 px-4 py-3 text-sm"><dt class="font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_labs.stats.active_tests') }}</dt><dd class="font-black text-[var(--ds-text)]">{{ stats.active_tests ?? 0 }}</dd></div><div class="flex justify-between gap-3 px-4 py-3 text-sm"><dt class="font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.vap_labs.stats.staff_count') }}</dt><dd class="font-black text-[var(--ds-text)]">{{ stats.staff_count ?? 0 }}</dd></div></dl></section>
        <section class="ds-panel p-4"><Link :href="route('vap-labs.labs.edit', lab.id)" class="ds-button ds-button-secondary w-full"><PencilSquareIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.buttons.edit_lab') }}</Link><button type="button" class="ds-button ds-button-danger mt-2 w-full" @click="isDeleteDialogOpen = true"><TrashIcon class="h-4 w-4" />{{ $t('gestlab.general.labels.vap_labs.buttons.delete_lab') }}</button><p class="mt-4 text-xs font-semibold text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_labs.stats.last_updated') }}: {{ formatDate(lab.updated_at) }}</p></section>
      </aside>
    </div>

    <ConfirmDialog v-if="isDeleteDialogOpen" :title="$t('gestlab.general.labels.vap_labs.modal.delete_lab.title')" :description="$t('gestlab.general.labels.vap_labs.modal.delete_lab.message')" :confirm="$t('gestlab.general.labels.vap_labs.buttons.delete')" :cancel="$t('gestlab.general.labels.vap_labs.buttons.cancel')" @canceled="isDeleteDialogOpen = false" @confirmed="deleteLab" />
  </div>
</template>
