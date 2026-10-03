<script setup>
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import ToggleField from '@/Components/base/ToggleField.vue'
import { Calculator as CalculatorIcon, CircleCheck as CheckCircleIcon, CircleDollarSign as CurrencyDollarIcon, BadgePercent as ReceiptPercentIcon } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },
  submitLabel: { type: String, default: 'Guardar serviço' },
})

defineEmits(['submit'])

const formattedPrice = computed(() => new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 2 }).format(Number(props.form.price || 0)))
const taxMode = computed(() => props.form.charge_tax ? 'Tributado' : 'Isento')

function loadExemptions(query, setOptions) {
  fetch(`/taxexemptions/getExemption?q=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((results) => setOptions(results.map((result) => ({ value: result.id, label: result.code, description: result.description, code: result.code }))))
}

function loadTaxTypes(query, setOptions) {
  fetch(`/taxtypes/getTaxType?q=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((results) => setOptions(results.map((result) => ({ value: result.id, label: result.name, percent: result.percent }))))
}

function onTaxTypeSelect(selectedTaxType) {
  props.form.tax_percentage = selectedTaxType?.percent || 0
}

function onExemptionSelect(selectedExemption) {
  props.form.exemption_code = selectedExemption?.code || ''
}
</script>

<template>
  <form class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.65fr)]" @submit.prevent="$emit('submit')">
    <div class="space-y-5">
      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><p class="ds-kicker">Identificação comercial</p><h2 class="ds-heading mt-1 text-base">Dados do serviço</h2></header>
        <div class="space-y-5 p-5 sm:p-6">
          <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_12rem]">
            <div class="ds-field-group"><label for="paid-service-name" class="ds-field-label">Nome <span class="ds-field-required">*</span></label><BaseInput id="paid-service-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" placeholder="Designacao apresentada no documento" /><p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p></div>
            <div class="ds-field-group"><label for="paid-service-price" class="ds-field-label">Preço (AOA) <span class="ds-field-required">*</span></label><BaseInput id="paid-service-price" v-model="form.price" type="number" min="0" step="0.01" class="ds-field text-right tabular-nums" :aria-invalid="Boolean(form.errors.price)" /><p v-if="form.errors.price" class="ds-field-error">{{ form.errors.price }}</p></div>
          </div>
          <div class="ds-field-group"><label for="paid-service-description" class="ds-field-label">Descrição</label><textarea id="paid-service-description" v-model="form.description" rows="6" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.description)" placeholder="Âmbito, entregavel e condições relevantes do serviço" /><p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p></div>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><p class="ds-kicker">Configuração fiscal</p><h2 class="ds-heading mt-1 text-base">Impostos e retencao</h2></header>
        <div class="space-y-5 p-5 sm:p-6">
          <div class="grid gap-3 sm:grid-cols-2">
            <ToggleField id="paid-service-charge-tax" v-model="form.charge_tax" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]" label="Cobrar imposto" description="Aplica a taxa fiscal seleccionada ao serviço." />
            <ToggleField id="paid-service-withhold-tax" v-model="form.withhold_tax" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]" label="Sujeito a retencao" description="Assinala a retencao aplicavel no documento." />
          </div>

          <div v-if="form.charge_tax" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_10rem]">
            <div class="ds-field-group"><label class="ds-field-label">Tipo de imposto <span class="ds-field-required">*</span></label><ComboboxEnhanced v-model="form.tax_id" :load-options="loadTaxTypes" :has-error="Boolean(form.errors.tax_id)" placeholder="Seleccione o imposto" @update:model-value="onTaxTypeSelect" /><p v-if="form.errors.tax_id" class="ds-field-error">{{ form.errors.tax_id }}</p></div>
            <div class="ds-field-group"><label for="paid-service-tax" class="ds-field-label">Taxa (%)</label><BaseInput id="paid-service-tax" v-model="form.tax_percentage" type="number" min="0" step="0.01" readonly class="ds-field cursor-not-allowed bg-[var(--ds-panel-muted)] text-right tabular-nums" /></div>
          </div>

          <div v-else class="ds-field-group"><label class="ds-field-label">Fundamento de isencao <span class="ds-field-required">*</span></label><ComboboxEnhanced v-model="form.exemption_id" :load-options="loadExemptions" :has-error="Boolean(form.errors.exemption_id)" placeholder="Seleccione o fundamento legal" @update:model-value="onExemptionSelect" /><p v-if="form.errors.exemption_id" class="ds-field-error">{{ form.errors.exemption_id }}</p><p v-if="form.errors.exemption_code" class="ds-field-error">{{ form.errors.exemption_code }}</p></div>
        </div>
      </section>
    </div>

    <aside class="ds-panel overflow-hidden xl:sticky xl:top-5">
      <header class="border-b border-[var(--ds-border)] px-5 py-4"><p class="ds-kicker">Resumo comercial</p><h2 class="ds-heading mt-1 text-base">Impacto no documento</h2></header>
      <div class="p-5">
        <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><CurrencyDollarIcon class="h-5 w-5" /></span><div class="min-w-0"><p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Serviço</p><p class="mt-1 break-words text-sm font-bold text-[var(--ds-text)]">{{ form.name || 'Sem designação' }}</p></div></div>
        <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
          <div class="flex items-center justify-between gap-4 py-3"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><CalculatorIcon class="h-4 w-4" />Preço</dt><dd class="text-sm font-black tabular-nums text-[var(--ds-text)]">{{ formattedPrice }}</dd></div>
          <div class="flex items-center justify-between gap-4 py-3"><dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]"><ReceiptPercentIcon class="h-4 w-4" />Fiscalidade</dt><dd class="ds-badge ring-1 ring-inset" :class="form.charge_tax ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'">{{ taxMode }}</dd></div>
          <div class="flex items-center justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Taxa</dt><dd class="text-sm font-black tabular-nums text-[var(--ds-text)]">{{ form.tax_percentage || 0 }}%</dd></div>
          <div class="flex items-center justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Retencao</dt><dd class="text-sm font-bold text-[var(--ds-text)]">{{ form.withhold_tax ? 'Sim' : 'Não' }}</dd></div>
        </dl>
        <div v-if="form.hasErrors" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">Reveja os campos assinalados antes de guardar.</div>
      </div>
      <footer class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4"><button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing || !form.isDirty"><CheckCircleIcon class="h-4 w-4" />{{ form.processing ? 'A guardar...' : submitLabel }}</button></footer>
    </aside>
  </form>
</template>
