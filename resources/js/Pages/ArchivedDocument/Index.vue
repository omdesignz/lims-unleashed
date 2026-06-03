<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(59,130,246,0.16),_transparent_34%),linear-gradient(135deg,_#f8fafc,_#ffffff)] p-6 shadow-sm">
      <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-3xl">
          <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Arquivo documental</p>
          <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Documentos arquivados</h1>
          <p class="mt-3 text-sm leading-7 text-slate-600">
            Consolide evidência histórica, retenção e recuperação num espaço menos improvisado e mais alinhado com a disciplina documental do laboratório.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <Link :href="route('file-manager')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            Abrir gestor documental
          </Link>
          <Link :href="route('archived_documents.create')" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
            Novo documento
          </Link>
        </div>
      </div>

      <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article class="rounded-[1.5rem] border border-white bg-white p-4 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Registos visíveis</p>
          <p class="mt-3 text-3xl font-semibold text-slate-900">{{ totalRecords }}</p>
          <p class="mt-2 text-sm leading-6 text-slate-500">Documentos actualmente devolvidos pela pesquisa activa.</p>
        </article>
        <article class="rounded-[1.5rem] border border-white bg-white p-4 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Com descrição</p>
          <p class="mt-3 text-3xl font-semibold text-slate-900">{{ describedRecords }}</p>
          <p class="mt-2 text-sm leading-6 text-slate-500">Itens com contexto suficiente para recuperação futura.</p>
        </article>
        <article class="rounded-[1.5rem] border border-white bg-white p-4 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Sem descrição</p>
          <p class="mt-3 text-3xl font-semibold text-slate-900">{{ undocumentedRecords }}</p>
          <p class="mt-2 text-sm leading-6 text-slate-500">Candidatos a completar antes de a retenção perder utilidade.</p>
        </article>
      </div>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
          <h2 class="text-lg font-semibold text-slate-900">Catálogo de arquivo</h2>
          <p class="mt-1 text-sm leading-6 text-slate-500">
            Use este registo para preservar documentos fora do fluxo activo, sem perder contexto, proveniência e capacidade de localização.
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
          Mantenha título claro, descrição útil e ficheiro anexo válido para evidência.
        </div>
      </div>

      <div v-if="record.data.length" class="mt-6 grid gap-4 xl:grid-cols-2">
        <article
          v-for="document in record.data"
          :key="document.id"
          class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 transition hover:border-slate-300 hover:bg-white"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600">
                Documento retido
              </div>
              <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ document.title }}</h3>
              <p class="mt-3 text-sm leading-7 text-slate-600">{{ document.description || 'Sem descrição.' }}</p>
            </div>
            <Link :href="route('archived_documents.edit', document.id)" class="shrink-0 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
              Editar
            </Link>
          </div>

          <dl class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-white bg-white px-4 py-3">
              <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Ficheiro</dt>
              <dd class="mt-2 truncate text-sm font-medium text-slate-900">{{ document.file_path || 'Sem ficheiro' }}</dd>
            </div>
            <div class="rounded-2xl border border-white bg-white px-4 py-3">
              <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Prontidão</dt>
              <dd class="mt-2 text-sm font-medium text-slate-900">
                {{ document.description ? 'Contexto preservado' : 'Precisa de descrição' }}
              </dd>
            </div>
          </dl>
        </article>
      </div>

      <div v-else class="mt-6 rounded-[1.75rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
        <h3 class="text-base font-semibold text-slate-900">Ainda não existem documentos arquivados</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">
          Crie o primeiro registo para começar a preservar documentação histórica com uma estrutura mais credível.
        </p>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({ record: Object })

const records = computed(() => props.record?.data || [])
const totalRecords = computed(() => records.value.length)
const describedRecords = computed(() => records.value.filter((item) => item.description).length)
const undocumentedRecords = computed(() => totalRecords.value - describedRecords.value)
</script>
