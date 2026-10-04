<script setup>
import PortalLayout from "@/Shared/Layouts/PortalLayout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import { Link } from "@inertiajs/vue3";
import { Mail as EnvelopeIcon, MapPin as MapPinIcon, Phone as PhoneIcon, ShieldCheck as ShieldCheckIcon, CircleUser as UserCircleIcon } from "@lucide/vue";

defineOptions({ layout: PortalLayout });

defineProps({ warehouse: Object, requestStats: Object });
</script>

<template>
  <div class="space-y-6">
    <PageHeader :title="warehouse?.name || warehouse?.customer || 'Conta do cliente'" lede="Dados do local autenticado e resumo da actividade submetida no portal.">
      <template #actions>
        <Link :href="route('portal.security')" class="ds-button ds-button-primary"><ShieldCheckIcon class="h-4 w-4" />Seguranca</Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Pedidos totais</dt>
        <dd class="pl-cell-value">{{ requestStats?.total || 0 }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Em aberto</dt>
        <dd class="pl-cell-value">{{ requestStats?.open || 0 }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Concluídos</dt>
        <dd class="pl-cell-value">{{ requestStats?.completed || 0 }}</dd>
      </div>
    </dl>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-card overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><UserCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Contacto e local</h2></header>
        <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2">
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Cliente</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.customer || "Não associado" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Local</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.name || warehouse?.code || "Não definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Correio electrónico</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.email || "Não definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><PhoneIcon class="h-4 w-4" />Telefone</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.primary_phone || "Não definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Ponto focal</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.focal_point || "Não definido" }}</dd></div>
          <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><MapPinIcon class="h-4 w-4" />Morada</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ warehouse?.address || "Não definida" }}</dd></div>
        </dl>
      </section>

      <aside class="ds-card overflow-hidden self-start">
        <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Acções da conta</h2></header>
        <nav class="divide-y divide-[var(--ds-border)]">
          <Link :href="route('portal.requests.index')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Ver pedidos</Link>
          <Link :href="route('portal.services')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Abrir catálogo de serviços</Link>
          <Link :href="route('portal.security')" class="block px-5 py-3.5 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]">Gerir seguranca</Link>
        </nav>
      </aside>
    </div>
  </div>
</template>
