<template>
  <div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Editar mensagem</h1>
      <p class="mt-2 text-sm text-slate-600">Atualize o conteúdo e substitua anexos quando necessário.</p>
    </section>

    <form class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4" @submit.prevent="submit">
      <textarea v-model="form.message" rows="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Mensagem"></textarea>
      <input type="file" multiple @input="form.attachments = $event.target.files" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
      <div class="flex flex-wrap gap-2 text-xs text-slate-500">
        <span v-for="attachment in attachments" :key="attachment.id" class="rounded-full bg-slate-100 px-3 py-1">{{ attachment.file_type }}</span>
      </div>
      <div class="flex gap-3">
        <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Guardar</button>
        <Link :href="route('messages.index')" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voltar</Link>
      </div>
    </form>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const props = defineProps({
  record: Object,
  attachments: Array,
})

const form = useForm({
  message: props.record?.data?.message || '',
  attachments: [],
})

const submit = () => {
  form
    .transform((data) => ({ ...data, _method: 'put' }))
    .post(route('messages.update', props.record.data.id))
}
</script>
