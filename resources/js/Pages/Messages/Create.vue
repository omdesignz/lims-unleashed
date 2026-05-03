<template>
  <div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Nova mensagem interna</h1>
      <p class="mt-2 text-sm text-slate-600">Envie uma mensagem curta e adicione anexos quando necessário.</p>
    </section>

    <form class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4" @submit.prevent="submit">
      <select v-model="form.receiver_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
        <option value="">Selecione o destinatário</option>
        <option v-for="receiver in receivers" :key="receiver.id" :value="receiver.id">{{ receiver.name }} · {{ receiver.email }}</option>
      </select>
      <textarea v-model="form.message" rows="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Mensagem"></textarea>
      <input type="file" multiple @input="form.attachments = $event.target.files" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
      <div class="flex gap-3">
        <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Enviar</button>
        <Link :href="route('messages.index')" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</Link>
      </div>
    </form>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const props = defineProps({ receivers: Array })

const form = useForm({
  receiver_id: '',
  message: '',
  attachments: [],
})

const submit = () => {
  form.post(route('messages.store'))
}
</script>
