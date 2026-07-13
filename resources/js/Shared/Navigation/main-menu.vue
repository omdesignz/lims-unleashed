<template>
  <Popover class="relative" v-slot="{ open }">
    <PopoverButton
      :class="[
        open ? 'border-cyan-300/30 bg-white/10 text-white' : 'border-transparent text-slate-300 hover:bg-white/10 hover:text-white',
        'group inline-flex h-10 w-10 items-center justify-center rounded-lg border text-base font-bold transition focus:outline-none focus:ring-2 focus:ring-cyan-200/50',
      ]"
    >
      <Bars4Icon class="h-6 w-6" aria-hidden="true" />
      <span class="sr-only">Abrir menu rápido</span>
    </PopoverButton>

    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
      <PopoverPanel class="absolute left-1/2 z-10 mt-3 w-screen max-w-md -translate-x-1/2 transform px-5 sm:px-0">
        <div class="ds-command-palette overflow-hidden p-2">
          <div class="border-b border-[var(--ds-border)] px-3 py-2">
            <p class="ds-kicker">Navegação rápida</p>
            <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Áreas críticas do laboratório e QMS.</p>
          </div>
          <div class="grid gap-1 p-2">
            <Link
              v-for="item in solutions"
              :key="item.name"
              :href="item.href"
              class="ds-command-palette-item"
            >
              <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[rgb(var(--primary-50-rgb)/0.9)] text-[rgb(var(--primary-800-rgb)/1)] ring-1 ring-[rgb(var(--primary-200-rgb)/0.75)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-cyan-100 dark:ring-[rgb(var(--primary-300-rgb)/0.18)]">
                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-bold text-[var(--ds-text)]">{{ item.name }}</span>
                <span class="mt-1 block text-xs font-medium leading-5 text-[var(--ds-text-muted)]">{{ item.description }}</span>
              </span>
            </Link>
          </div>
        </div>
      </PopoverPanel>
    </transition>
  </Popover>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import {
  Bars4Icon,
  BeakerIcon,
  BellAlertIcon,
  ChartBarIcon,
  DocumentTextIcon,
  FolderOpenIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const solutions = [
  {
    name: 'Painel operacional',
    description: 'Tarefas, amostras, alertas e prioridades do laboratório.',
    href: route('dashboard'),
    icon: ChartBarIcon,
  },
  {
    name: 'Amostras pendentes',
    description: 'Entrada, validação, resultados, aprovação e contra-análise.',
    href: route('vap_samples.index'),
    icon: BeakerIcon,
  },
  {
    name: 'Relatórios e modelos',
    description: 'Estúdio de relatórios, certificados e documentos rastreáveis.',
    href: route('report-studios.index'),
    icon: DocumentTextIcon,
  },
  {
    name: 'Gestor documental',
    description: 'Controlo documental, anexos, versões e evidência associada.',
    href: route('file-manager'),
    icon: FolderOpenIcon,
  },
  {
    name: 'Qualidade e conformidade',
    description: 'QMS, não conformidades, proficiência e evidência ISO 17025.',
    href: route('qms.index'),
    icon: ShieldCheckIcon,
  },
  {
    name: 'Notificações',
    description: 'Alertas técnicos, lembretes e comunicações operacionais.',
    href: route('notifications.index'),
    icon: BellAlertIcon,
  },
]
</script>
