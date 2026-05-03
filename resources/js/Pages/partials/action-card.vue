<template>
    <!-- Actions panel -->
    <section aria-labelledby="quick-links-title">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm dark:border-gray-700 dark:bg-gray-800/50 sm:grid sm:grid-cols-2 sm:gap-px">
            <h2 class="sr-only" id="quick-links-title">Acções rápidas</h2>
            <div v-for="(action, actionIdx) in actions" :key="action.name" :class="[actionIdx === 0 ? 'rounded-tl-2xl rounded-tr-2xl sm:rounded-tr-none' : '', actionIdx === 1 ? 'sm:rounded-tr-2xl' : '', actionIdx === actions.length - 2 ? 'sm:rounded-bl-2xl' : '', actionIdx === actions.length - 1 ? 'rounded-bl-2xl rounded-br-2xl sm:rounded-bl-none' : '', 'relative group bg-white p-6 transition hover:bg-gray-50 focus-within:ring-2 focus-within:ring-inset focus-within:ring-cyan-500 dark:bg-gray-900 dark:hover:bg-gray-800']">
                <div>
                    <span :class="[action.iconBackground, action.iconForeground, 'rounded-lg inline-flex p-3 ring-4 ring-white']">
                      <component :is="action.icon" class="h-6 w-6" aria-hidden="true" />
                    </span>
                </div>
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        <a :href="action.href" class="focus:outline-none">
                            <!-- Extend touch target to entire panel -->
                            <span class="absolute inset-0" aria-hidden="true" />
                            {{ action.name }}
                        </a>
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ action.description }}</p>
                </div>
                <span class="pointer-events-none absolute top-6 right-6 text-gray-300 group-hover:text-gray-400 dark:text-gray-600 dark:group-hover:text-gray-500" aria-hidden="true">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z" />
                    </svg>
                  </span>
            </div>
        </div>
    </section>
</template>

<script setup>
import {
    AcademicCapIcon,
    BanknotesIcon,
    CheckBadgeIcon,
    ClockIcon,
    ReceiptRefundIcon,
    UsersIcon
} from "@heroicons/vue/24/outline";

const actions = [
    {
        icon: ClockIcon,
        name: 'Agendar actividade',
        href: '#',
        description: 'Planeie actividades laboratoriais, visitas e recolhas de forma rápida.',
        iconForeground: 'text-teal-700',
        iconBackground: 'bg-teal-50',
    },
    {
        icon: CheckBadgeIcon,
        name: 'Conformidade',
        href: '#',
        description: 'Consulte estados de conformidade, controlos críticos e acções pendentes.',
        iconForeground: 'text-purple-700',
        iconBackground: 'bg-purple-50',
    },
    {
        icon: UsersIcon,
        name: 'Coordenação da equipa',
        href: '#',
        description: 'Acompanhe a equipa, competências, aprovações e cargas de trabalho.',
        iconForeground: 'text-sky-700',
        iconBackground: 'bg-sky-50',
    },
    {
        icon: BanknotesIcon,
        name: 'Gestão financeira',
        href: '#',
        description: 'Aceda a facturação, propostas, recebimentos e controlo financeiro.',
        iconForeground: 'text-yellow-700',
        iconBackground: 'bg-yellow-50',
    },
    {
        icon: ReceiptRefundIcon,
        name: 'Solicitações operacionais',
        href: '#',
        description: 'Registe necessidades, despesas e pedidos internos com fluxo de aprovação.',
        iconForeground: 'text-rose-700',
        iconBackground: 'bg-rose-50',
    },
    {
        icon: AcademicCapIcon,
        name: 'Capacitação',
        href: '#',
        description: 'Monitore formações, certificações e planos de desenvolvimento técnico.',
        iconForeground: 'text-indigo-700',
        iconBackground: 'bg-indigo-50',
    },
]
</script>

<style scoped>

</style>
