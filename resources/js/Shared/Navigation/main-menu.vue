<template>
  <Popover class="relative" v-slot="{ open }">
    <PopoverButton
      :class="[
        open ? 'bg-primary-700 text-white' : 'text-primary-100 hover:bg-white/10 hover:text-white',
        'group inline-flex items-center rounded-full p-2 text-base font-bold transition focus:outline-none focus:ring-2 focus:ring-white/70',
      ]"
    >
      <Bars4Icon class="h-7 w-7" aria-hidden="true" />
      <span class="sr-only">Abrir menu rápido</span>
    </PopoverButton>

    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
      <PopoverPanel class="absolute left-1/2 z-10 mt-3 w-screen max-w-md -translate-x-1/2 transform px-5 sm:px-0">
        <div class="overflow-hidden rounded-[1.75rem] border border-[#ded3bf] bg-[#fffaf0] shadow-[0_26px_80px_rgba(20,61,55,0.22)] ring-1 ring-[#143d37]/5 dark:border-[#25443c] dark:bg-[#0c1714] dark:ring-white/10">
          <div class="grid gap-2 p-3">
            <Link
              v-for="item in solutions"
              :key="item.name"
              :href="item.href"
              class="group flex items-start gap-4 rounded-[1.25rem] p-3 transition hover:bg-[#ede5d6] dark:hover:bg-[#10231f]"
            >
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-700 text-white shadow-[0_12px_35px_rgba(20,61,55,0.24)] dark:bg-primary-600">
                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
              </span>
              <span>
                <span class="block text-sm font-extrabold text-[#15231f] dark:text-[#f7f1e7]">{{ item.name }}</span>
                <span class="mt-1 block text-sm font-medium leading-5 text-slate-600 dark:text-slate-300">{{ item.description }}</span>
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
    description: 'Visão consolidada de tarefas, amostras, alertas e prioridades do laboratório.',
    href: route('dashboard'),
    icon: ChartBarIcon,
  },
  {
    name: 'Amostras pendentes',
    description: 'Entrada, validação, resultados, aprovação e contra-análise no fluxo técnico.',
    href: route('vap_samples.index'),
    icon: BeakerIcon,
  },
  {
    name: 'Relatórios e modelos',
    description: 'Estúdio de relatórios, certificados e documentos multi-página com rastreabilidade.',
    href: route('report-studios.index'),
    icon: DocumentTextIcon,
  },
  {
    name: 'Gestor documental',
    description: 'Controlo documental, anexos, versões e evidência associada aos processos.',
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
    description: 'Alertas técnicos, lembretes e comunicações que impedem amostras paradas.',
    href: route('notifications.index'),
    icon: BellAlertIcon,
  },
]
</script>
