<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="relative isolate p-6 sm:p-8">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.18),transparent_34%),linear-gradient(135deg,rgba(15,23,42,0.04),transparent)] dark:bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_38%),linear-gradient(135deg,rgba(15,23,42,0.78),rgba(15,23,42,0.18))]" />
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <div class="inline-flex rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-primary-800 dark:border-primary-800/60 dark:bg-primary-950/50 dark:text-primary-200">
            Perfil
          </div>
          <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ warehouse?.name || 'Armazém' }}</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            Esta área resume os dados de contacto e a atividade recente do armazém autenticado no portal.
          </p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
          <Link
            :href="route('portal.security')"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-800 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-primary-900/20 transition hover:bg-primary-700"
          >
            <ShieldCheckIcon class="h-4 w-4" />
            Segurança
          </Link>
          <Link
            :href="route('portal.requests.index')"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            Ver solicitações
          </Link>
        </div>
      </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-black text-slate-950 dark:text-white">Dados do armazém</h2>
        <dl class="mt-5 space-y-4">
          <div>
            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Cliente</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ warehouse?.customer || 'Sem cliente associado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Email</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ warehouse?.email || 'Sem email registado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Telefone principal</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ warehouse?.primary_phone || 'Sem telefone registado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Contacto focal</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">
              {{ warehouse?.focal_point || 'Sem responsável focal' }}
              <span v-if="warehouse?.focal_point_contact" class="text-slate-500 dark:text-slate-400"> · {{ warehouse.focal_point_contact }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Morada</dt>
            <dd class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ warehouse?.address || 'Sem morada registada' }}</dd>
          </div>
        </dl>
      </div>

      <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <h2 class="text-lg font-black text-slate-950 dark:text-white">Atividade no portal</h2>
          <div class="mt-5 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Pedidos totais</p>
              <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white">{{ requestStats?.total ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Pedidos em aberto</p>
              <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white">{{ requestStats?.open ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
              <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Pedidos concluídos</p>
              <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white">{{ requestStats?.completed ?? 0 }}</p>
            </article>
          </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <h2 class="text-lg font-black text-slate-950 dark:text-white">Próximos passos recomendados</h2>
          <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
            <p>Use “Solicitações” para novos pedidos de análise, colheita, faturação ou apoio documental.</p>
            <p>Consulte “Serviços” para abrir o tipo de pedido certo sem omitir informação importante.</p>
            <p>Abra “Segurança” para gerir palavra-passe, 2FA, sessões activas e passkeys do portal.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/PortalLayout.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

defineOptions({
  layout: Layout,
})

defineProps({
  warehouse: Object,
  requestStats: Object,
})
</script>
