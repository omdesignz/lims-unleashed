<template>
  <div class="space-y-6">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_34%),linear-gradient(135deg,_#f8fafc,_#ffffff)] p-6 shadow-sm">
      <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Arquivo documental</p>
      <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Novo documento arquivado</h1>
      <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
        Registe o documento com um título claro, contexto mínimo e o ficheiro final preservado para consulta e retenção.
      </p>
    </section>

    <form class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
      <div class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
        <div class="space-y-5">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Título do registo</label>
            <input v-model="form.title" type="text" placeholder="Ex.: Certificado retido 2024 / SOP substituída / Relatório histórico" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" />
            <p v-if="form.errors.title" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Descrição e contexto</label>
            <textarea v-model="form.description" rows="8" placeholder="Explique a origem, o motivo da retenção, o período ou qualquer informação útil para recuperação futura." class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"></textarea>
            <p v-if="form.errors.description" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.description }}</p>
          </div>

          <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-700">Ficheiro arquivado</label>
            <input type="file" @input="form.file = $event.target.files[0]" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" />
            <p v-if="form.errors.file" class="mt-2 text-xs font-medium text-rose-600">{{ form.errors.file }}</p>
          </div>
        </div>

        <aside class="space-y-4">
          <section class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Checklist mínimo</h2>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
              <div class="rounded-2xl border border-white bg-white px-4 py-3">Título específico e fácil de encontrar.</div>
              <div class="rounded-2xl border border-white bg-white px-4 py-3">Descrição com motivo de retenção ou contexto histórico.</div>
              <div class="rounded-2xl border border-white bg-white px-4 py-3">Ficheiro final anexado para consulta futura.</div>
            </div>
          </section>

          <section class="rounded-[1.75rem] border border-slate-200 bg-white p-5">
            <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700">Objetivo do registo</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">
              Este espaço deve guardar documentos fora do circuito activo, mas ainda relevantes para retenção, histórico, consulta ou evidência.
            </p>
          </section>
        </aside>
      </div>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800" :disabled="form.processing">
          Guardar registo
        </button>
        <Link :href="route('archived_documents.index')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
          Cancelar
        </Link>
      </div>
    </form>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const form = useForm({
  title: '',
  description: '',
  file: null,
})

const submit = () => {
  form.post(route('archived_documents.store'))
}
</script>
