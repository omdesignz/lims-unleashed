<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import CheckboxInput from '@/Components/base/CheckboxInput.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Mail as EnvelopeIcon, Inbox as InboxIcon, Signal as SignalIcon } from '@lucide/vue'

defineOptions({ layout: Layout })

const props = defineProps({
  preferences: { type: Array, default: () => [] },
})

const form = useForm({
  preferences: props.preferences.map((preference) => ({ ...preference })),
})

const channels = [
  { key: 'database_enabled', label: 'Caixa de entrada', icon: InboxIcon },
  { key: 'broadcast_enabled', label: 'Tempo real', icon: SignalIcon },
  { key: 'mail_enabled', label: 'Email', icon: EnvelopeIcon },
]

const save = () => form.put(route('notification-preferences.update'), { preserveScroll: true })
</script>

<template>
  <Head title="Preferências de notificações" />

  <div class="pl-page w-full space-y-5">
    <PageHeader :trail="[{ title: 'Conta e segurança', url: route('security') }, { title: 'Notificações' }]" title="Notificações" lede="Escolha como recebe alertas por área. Alertas urgentes ignoram o período de silêncio para proteger operações críticas." />

    <form class="space-y-5" @submit.prevent="save">
      <section class="ds-panel overflow-hidden">
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)]">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-black uppercase text-[var(--ds-text-soft)]">Área operacional</th>
                <th v-for="channel in channels" :key="channel.key" class="px-4 py-3 text-center text-xs font-black uppercase text-[var(--ds-text-soft)]">{{ channel.label }}</th>
                <th class="px-4 py-3 text-left text-xs font-black uppercase text-[var(--ds-text-soft)]">Silêncio</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)]">
              <tr v-for="(preference, index) in form.preferences" :key="preference.category" class="align-top hover:bg-[var(--ds-panel-subtle)]">
                <td class="min-w-64 px-5 py-4">
                  <p class="text-sm font-bold text-[var(--ds-text)]">{{ preference.label }}</p>
                  <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ preference.description }}</p>
                </td>
                <td v-for="channel in channels" :key="channel.key" class="px-4 py-4 text-center">
                  <label class="inline-flex cursor-pointer items-center justify-center" :title="channel.label">
                    <CheckboxInput v-model="preference[channel.key]" class="peer sr-only" />
                    <span class="grid h-9 w-9 place-items-center rounded-md border border-[var(--ds-border)] bg-[var(--ds-panel)] text-[var(--ds-text-soft)] transition peer-checked:border-[rgb(var(--primary-500-rgb)/0.5)] peer-checked:bg-[rgb(var(--primary-50-rgb))] peer-checked:text-[rgb(var(--primary-700-rgb))] dark:peer-checked:bg-[rgb(var(--primary-500-rgb)/0.12)]">
                      <component :is="channel.icon" class="h-4 w-4" />
                    </span>
                  </label>
                </td>
                <td class="min-w-60 px-4 py-4">
                  <div class="flex items-center gap-2">
                    <BaseInput v-model="preference.quiet_hours_start" type="time" class="ds-field min-w-28" aria-label="Início do silêncio" />
                    <span class="text-xs font-bold text-[var(--ds-text-soft)]">até</span>
                    <BaseInput v-model="preference.quiet_hours_end" type="time" class="ds-field min-w-28" aria-label="Fim do silêncio" />
                  </div>
                  <input v-model="preference.timezone" type="hidden" />
                  <p v-if="form.errors[`preferences.${index}.quiet_hours_start`]" class="ds-field-error mt-1">{{ form.errors[`preferences.${index}.quiet_hours_start`] }}</p>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </section>

      <div class="flex justify-end gap-2">
        <button type="button" class="ds-button ds-button-secondary" :disabled="!form.isDirty" @click="form.reset()">Repor</button>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">Guardar preferências</button>
      </div>
    </form>
  </div>
</template>
