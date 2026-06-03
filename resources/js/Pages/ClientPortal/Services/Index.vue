<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="max-w-3xl space-y-3">
        <div class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">
          Catálogo de serviços
        </div>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Serviços disponíveis no portal</h1>
        <p class="text-sm leading-6 text-slate-600">
          Este portal foi melhorado para permitir pedidos estruturados de análises, colheitas, documentos e apoio administrativo. Cada serviço abre um formulário próprio e fica registado com referência rastreável.
        </p>
      </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      <article v-for="service in services" :key="service.type" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">{{ service.title }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ service.description }}</p>
          </div>
          <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">
            {{ labelFor(service.type) }}
          </span>
        </div>

        <div class="mt-6 space-y-3 text-sm text-slate-500">
          <p v-if="service.type === 'analysis_request'">Inclui seleção de perfis analíticos, matriz/produto e indicação de recolha.</p>
          <p v-else-if="service.type === 'collection_request'">Inclui local, endereço, contacto no local, janela horária e lista de itens a recolher.</p>
          <p v-else-if="service.type === 'document_request'">Útil para pedir segundas vias, comprovativos ou documentação contratual.</p>
          <p v-else-if="service.type === 'billing_support'">Centraliza temas de faturação, pagamentos, recibos e notas de crédito.</p>
          <p v-else-if="service.type === 'certificate_support'">Permite solicitar apoio, revisão ou reemissão de certificados.</p>
          <p v-else>Abra um pedido operacional mais geral para situações não cobertas pelos serviços acima.</p>
        </div>

        <div class="mt-6">
          <Link
            :href="route('portal.requests.index', { request_type: service.type, new: 1, title: service.title })"
            class="inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Abrir pedido
          </Link>
        </div>
      </article>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-slate-900">Outros recursos do portal</h2>
      <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Link :href="route('portal.collections')" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 hover:border-slate-300">
          Consultar histórico de colheitas
        </Link>
        <Link :href="route('portal.qualitycertificates')" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 hover:border-slate-300">
          Ver certificados disponíveis
        </Link>
        <Link :href="route('portal.invoices')" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 hover:border-slate-300">
          Acompanhar faturação
        </Link>
        <Link :href="route('portal.faqs')" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 hover:border-slate-300">
          Consultar perguntas frequentes
        </Link>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layouts/PortalLayout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineOptions({
  layout: Layout,
})

defineProps({
  services: {
    type: Array,
    default: () => [],
  },
  warehouse: Object,
})

const labelFor = (type) => ({
  analysis_request: 'Análise',
  collection_request: 'Colheita',
  certificate_support: 'Certificados',
  document_request: 'Documentos',
  billing_support: 'Faturação',
  general_support: 'Suporte',
}[type] || 'Serviço')
</script>
