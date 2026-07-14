<script setup>
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ArrowRightIcon, BanknotesIcon, BeakerIcon, DocumentTextIcon, ShieldExclamationIcon, TruckIcon, WrenchScrewdriverIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: PortalLayout });

defineProps({ services: { type: Array, default: () => [] }, warehouse: Object });

function serviceIcon(service) {
  return { beaker: BeakerIcon, truck: TruckIcon, certificate: DocumentTextIcon, document: DocumentTextIcon, currency: BanknotesIcon, shield: ShieldExclamationIcon, support: WrenchScrewdriverIcon }[service.icon] || WrenchScrewdriverIcon;
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex min-w-0 items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><WrenchScrewdriverIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Catálogo do portal</p><h1 class="ds-heading mt-1 text-2xl">Serviços disponíveis</h1><p class="ds-copy mt-1 max-w-3xl text-sm">Escolha o fluxo certo para garantir uma triagem rapida e toda a informação necessária.</p></div></div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Link v-for="service in services" :key="service.type" :href="route('portal.requests.index', { request_type: service.type, new: 1, title: service.title })" class="ds-card group flex flex-col p-5 transition hover:border-[rgb(var(--primary-300-rgb))]">
        <div class="flex items-start justify-between gap-3"><span class="grid h-10 w-10 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] ring-1 ring-[var(--ds-border)]"><component :is="serviceIcon(service)" class="h-5 w-5" /></span><ArrowRightIcon class="h-4 w-4 text-[var(--ds-text-soft)] transition group-hover:translate-x-0.5" /></div>
        <h2 class="mt-5 text-base font-bold text-[var(--ds-text)]">{{ service.title }}</h2>
        <p class="ds-copy mt-2 flex-1 text-sm">{{ service.description }}</p>
        <span class="mt-5 text-xs font-bold text-[rgb(var(--primary-700-rgb))]">Abrir pedido</span>
      </Link>
    </section>

    <section class="ds-card overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="text-base font-bold text-[var(--ds-text)]">Recursos da conta</h2><p class="mt-1 text-sm font-medium text-[var(--ds-text-muted)]">Acesso directo aos registos e documentos já disponíveis.</p></header>
      <nav class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <Link :href="route('portal.collections')" class="bg-[var(--ds-panel)] px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">Histórico de colheitas</Link>
        <Link :href="route('portal.qualitycertificates')" class="bg-[var(--ds-panel)] px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">Certificados disponíveis</Link>
        <Link :href="route('portal.invoices')" class="bg-[var(--ds-panel)] px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">Conta corrente</Link>
        <Link :href="route('portal.faqs')" class="bg-[var(--ds-panel)] px-5 py-4 text-sm font-bold text-[var(--ds-text)] hover:bg-[var(--ds-panel-subtle)]">Perguntas frequentes</Link>
      </nav>
    </section>
  </div>
</template>
