<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <div class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">
            Perfil
          </div>
          <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ warehouse?.name || 'Armazém' }}</h1>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Esta área resume os dados de contacto e a atividade recente do armazém autenticado no portal.
          </p>
        </div>
        <Link
          :href="route('portal.requests.index')"
          class="inline-flex rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
        >
          Ver solicitações
        </Link>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Dados do armazém</h2>
        <dl class="mt-5 space-y-4">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cliente</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ warehouse?.customer || 'Sem cliente associado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ warehouse?.email || 'Sem email registado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Telefone principal</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ warehouse?.primary_phone || 'Sem telefone registado' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Contacto focal</dt>
            <dd class="mt-1 text-sm text-slate-700">
              {{ warehouse?.focal_point || 'Sem responsável focal' }}
              <span v-if="warehouse?.focal_point_contact" class="text-slate-500"> · {{ warehouse.focal_point_contact }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Morada</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ warehouse?.address || 'Sem morada registada' }}</dd>
          </div>
        </dl>
      </div>

      <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-900">Atividade no portal</h2>
          <div class="mt-5 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-sm font-medium text-slate-500">Pedidos totais</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ requestStats?.total ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-sm font-medium text-slate-500">Pedidos em aberto</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ requestStats?.open ?? 0 }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-sm font-medium text-slate-500">Pedidos concluídos</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ requestStats?.completed ?? 0 }}</p>
            </article>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-900">Próximos passos recomendados</h2>
          <div class="mt-4 space-y-3 text-sm text-slate-600">
            <p>Use “Solicitações” para novos pedidos de análise, colheita, faturação ou apoio documental.</p>
            <p>Consulte “Serviços” para abrir o tipo de pedido certo sem omitir informação importante.</p>
            <p>Verifique regularmente certificados, faturas, recibos e histórico de colheitas disponíveis no menu lateral.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layouts/PortalLayout.vue'

defineOptions({
  layout: Layout,
})

defineProps({
  warehouse: Object,
  requestStats: Object,
})
</script>
