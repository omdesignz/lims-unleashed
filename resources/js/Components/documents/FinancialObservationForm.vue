<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  kind: { type: String, required: true, validator: (value) => ['invoice', 'credit_note', 'receipt', 'quote', 'import_certificate', 'export_certificate'].includes(value) },
  record: { type: Object, required: true },
});
const source = props.record?.data || props.record;
const config = computed(() => ({
  invoice: { title: 'Factura', route: 'invoices' },
  credit_note: { title: 'Nota de crédito', route: 'creditnotes' },
  receipt: { title: 'Recibo', route: 'receipts' },
  quote: { title: 'Cotação facturada', route: 'quotes' },
  import_certificate: { title: 'Certificado de importação facturado', route: 'importcertificates' },
  export_certificate: { title: 'Certificado de exportação facturado', route: 'exportcertificates' },
})[props.kind]);
const form = useForm({ obs: source.obs ?? '' });

function submit() {
  if (form.processing) return;
  try {
    form.put(route(`${config.value.route}.update`, source.id), {
    preserveScroll: true,
    onSuccess: () => form.defaults(),
    onNetworkError: () => form.setError('obs', 'Ligação interrompida. As suas observações foram preservadas; tente novamente.'),
    onHttpException: () => form.setError('obs', 'Não foi possível guardar as observações. Tente novamente.'),
    });
  } catch {
    form.setError('obs', 'Não foi possível iniciar a correcção. As suas observações foram preservadas; tente novamente.');
  }
}
</script>

<template>
  <form class="ds-panel flex flex-col gap-5 p-5 sm:p-6" @submit.prevent="submit">
    <Head :title="`${config.title} · Observações`" />
    <header class="flex flex-col gap-2">
      <p class="ds-kicker">{{ ['quote', 'import_certificate', 'export_certificate'].includes(kind) ? 'Documento facturado' : 'Documento financeiro emitido' }}</p>
      <h1 class="ds-heading text-2xl">{{ config.title }} · {{ source.document_no || source.cert_no || source.quote_no || `#${source.id}` }}</h1>
      <p class="ds-copy">Cliente, local, valores, impostos, linhas e identificação emitida estão bloqueados. Apenas as observações podem ser corrigidas aqui.</p>
    </header>
    <div class="ds-field-group">
      <label for="financial-observations" class="ds-field-label">Observações</label>
      <textarea id="financial-observations" v-model="form.obs" class="ds-field min-h-32 resize-y" rows="6" maxlength="5000" :disabled="form.processing" :aria-invalid="Boolean(form.errors.obs)" :aria-describedby="form.errors.obs ? 'financial-observation-error' : undefined"></textarea>
      <p v-if="form.errors.obs" id="financial-observation-error" role="alert" class="ds-field-error">{{ form.errors.obs }}</p>
    </div>
    <footer class="flex flex-wrap items-center gap-3">
      <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">{{ form.processing ? 'A guardar...' : 'Guardar observações' }}</button>
      <Link :href="route(`${config.route}.index`)" class="ds-button ds-button-secondary">Voltar ao registo</Link>
      <p v-if="form.recentlySuccessful" role="status" class="ds-copy">Observações guardadas.</p>
    </footer>
  </form>
</template>
