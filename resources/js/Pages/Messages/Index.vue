<template>
  <div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Mensagens internas</h1>
          <p class="mt-2 text-sm text-slate-600">Troque instruções operacionais e mantenha um registo simples das comunicações internas.</p>
        </div>
        <Link :href="route('messages.create')" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
          Nova mensagem
        </Link>
      </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div v-if="record.data.length" class="space-y-4">
        <article v-for="message in record.data" :key="message.id" class="rounded-2xl border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-slate-900">{{ message.sender || '—' }} → {{ message.receiver || '—' }}</div>
              <p class="mt-2 text-sm text-slate-600">{{ message.message }}</p>
            </div>
            <Link :href="route('messages.edit', message.id)" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
              Abrir
            </Link>
          </div>
        </article>
      </div>
      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
        Ainda não existem mensagens internas.
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

defineProps({ record: Object })
</script>
