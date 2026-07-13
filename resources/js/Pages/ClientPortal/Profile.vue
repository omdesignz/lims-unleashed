<script setup>
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import { Link } from "@inertiajs/vue3";
import { BuildingOffice2Icon, CheckBadgeIcon, ClipboardDocumentCheckIcon, ClockIcon, EnvelopeIcon, MapPinIcon, PhoneIcon, ShieldCheckIcon, UserCircleIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: PortalLayout });

defineProps({ warehouse: Object, requestStats: Object });
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><BuildingOffice2Icon class="h-5 w-5" /></span><div><p class="ds-kicker">Perfil do portal</p><h1 class="ds-heading mt-1 break-words text-2xl">{{ warehouse?.name || warehouse?.customer || "Conta do cliente" }}</h1><p class="ds-copy mt-1 max-w-3xl text-sm">Dados do local autenticado e resumo da atividade submetida no portal.</p></div></div>
          <Link :href="route('portal.security')" class="ds-button ds-button-primary"><ShieldCheckIcon class="h-4 w-4" />Seguranca</Link>
        </div>
      </div>
      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-3">
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between"><div><dt class="ds-field-label">Pedidos totais</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ requestStats?.total || 0 }}</dd></div><ClipboardDocumentCheckIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between"><div><dt class="ds-field-label">Em aberto</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ requestStats?.open || 0 }}</dd></div><ClockIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between"><div><dt class="ds-field-label">Concluidos</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ requestStats?.completed || 0 }}</dd></div><CheckBadgeIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-card overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><UserCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Contacto e local</h2></header>
        <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2">
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Cliente</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.customer || "Nao associado" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Local</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.name || warehouse?.code || "Nao definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Email</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.email || "Nao definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><PhoneIcon class="h-4 w-4" />Telefone</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.primary_phone || "Nao definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Ponto focal</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.focal_point || "Nao definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><MapPinIcon class="h-4 w-4" />Morada</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.address || "Nao definida" }}</dd></div>
        </dl>
      </section>

      <aside class="ds-card overflow-hidden self-start">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Acoes da conta</h2></header>
        <nav class="divide-y divide-[var(--ds-border)]">
          <Link :href="route('portal.requests.index')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Ver solicitacoes</Link>
          <Link :href="route('portal.services')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Abrir catalogo de servicos</Link>
          <Link :href="route('portal.security')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Gerir seguranca</Link>
        </nav>
      </aside>
    </div>
  </div>
</template>
