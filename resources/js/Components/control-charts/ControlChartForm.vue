<script setup>
import Combobox from '@/Components/combobox.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import { loadSelectOptions } from '@/Utils/selectOptions'
import { computed } from 'vue'

/**
 * The fields of a control chart: what it controls and, when known, its limits.
 * The type is chosen once, when the chart is created.
 */
const props = defineProps({
  form: { type: Object, required: true },
  types: { type: Array, default: () => [] },
  creating: { type: Boolean, default: false },
})

const isRange = computed(() => props.form.chart_type === 'range')

function loadMaterials(query, setOptions) {
  return loadSelectOptions('/control-charts/materials', query, setOptions, (item) => ({ value: item.id, label: item.name }))
}

function loadParameters(query, setOptions) {
  return loadSelectOptions('/parameters/getParameter', query, setOptions, (item) => ({
    value: item.id,
    label: [item.code, item.name].filter(Boolean).join(' · '),
  }))
}
</script>

<template>
  <div class="space-y-6">
    <fieldset v-if="creating" class="space-y-2">
      <legend class="ds-field-label">Tipo de carta <span class="ds-field-required">*</span></legend>
      <div class="grid gap-2 sm:grid-cols-2">
        <label
          v-for="type in types"
          :key="type.value"
          class="flex cursor-pointer items-start gap-3 border p-3"
          :class="form.chart_type === type.value ? 'border-[var(--pl-accent)] bg-[var(--pl-layer)]' : 'border-[var(--pl-line)]'"
        >
          <RadioInput v-model="form.chart_type" name="chart_type" :value="type.value" class="mt-1" />
          <span>
            <span class="block text-sm font-bold text-[var(--pl-fg)]">{{ type.label }}</span>
            <span class="mt-1 block text-xs text-[var(--pl-muted)]">
              {{ type.value === 'range'
                ? 'Diferença entre duplicados da mesma amostra: controla a precisão.'
                : 'Valor de uma amostra ou material de controlo: controla a exactidão e a precisão.' }}
            </span>
          </span>
        </label>
      </div>
      <p v-if="form.errors.chart_type" class="ds-field-error">{{ form.errors.chart_type }}</p>
    </fieldset>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="ds-field-group md:col-span-2">
        <label class="ds-field-label" for="control-chart-name">Nome <span class="ds-field-required">*</span></label>
        <BaseInput id="control-chart-name" v-model="form.name" class="ds-field" maxlength="160" placeholder="Ex.: Chumbo em água · MRC 1643f" />
        <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label">Parâmetro</label>
        <Combobox v-model="form.parameter_id" :load-options="loadParameters" placeholder="Opcional" />
        <p v-if="form.errors.parameter_id" class="ds-field-error">{{ form.errors.parameter_id }}</p>
      </div>
      <div class="ds-field-group md:col-span-2">
        <label class="ds-field-label">Amostras de controlo (material do catálogo)</label>
        <Combobox v-model="form.control_product_id" :load-options="loadMaterials" placeholder="Opcional" />
        <p class="ds-field-hint">
          Com um parâmetro e um material marcado como «Material de controlo interno» no catálogo de produtos, os resultados aprovados
          {{ isRange ? 'em duplicado (análise e contra-análise)' : '' }} das amostras desse material entram na carta sozinhos.
        </p>
        <p v-if="form.errors.control_product_id" class="ds-field-error">{{ form.errors.control_product_id }}</p>
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label" for="control-chart-method">Método</label>
        <BaseInput id="control-chart-method" v-model="form.method" class="ds-field" maxlength="160" placeholder="Norma ou procedimento interno" />
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label" for="control-chart-matrix">Matriz</label>
        <BaseInput id="control-chart-matrix" v-model="form.matrix" class="ds-field" maxlength="160" />
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label" for="control-chart-unit">Unidade</label>
        <BaseInput id="control-chart-unit" v-model="form.unit" class="ds-field" maxlength="40" placeholder="Ex.: µg/L" />
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label" for="control-chart-material">{{ isRange ? 'Amostra em duplicado' : 'Material de controlo' }}</label>
        <BaseInput id="control-chart-material" v-model="form.control_material" class="ds-field" maxlength="160" :placeholder="isRange ? 'Ex.: amostras de rotina' : 'Ex.: MRC, padrão interno, amostra fortificada'" />
      </div>
      <div class="ds-field-group">
        <label class="ds-field-label" for="control-chart-lot">Lote do material</label>
        <BaseInput id="control-chart-lot" v-model="form.material_lot" class="ds-field" maxlength="80" />
      </div>
    </div>

    <fieldset class="space-y-4 border-t border-[var(--pl-line)] pt-5">
      <legend class="pl-k">Limites</legend>
      <p class="text-sm text-[var(--pl-muted)]">
        {{ isRange
          ? 'Indique a amplitude média (R̄), ou deixe em branco e calcule-a depois a partir dos pontos registados.'
          : 'Indique a linha central e o desvio-padrão (valor certificado, objectivo de precisão), ou deixe em branco e calcule-os depois a partir dos pontos registados.' }}
      </p>
      <div class="grid gap-4 md:grid-cols-2">
        <div class="ds-field-group">
          <label class="ds-field-label" for="control-chart-centre">{{ isRange ? 'Amplitude média (R̄)' : 'Linha central' }}</label>
          <BaseInput id="control-chart-centre" v-model="form.centre_line" class="ds-field" inputmode="decimal" />
          <p v-if="form.errors.centre_line" class="ds-field-error">{{ form.errors.centre_line }}</p>
        </div>
        <div v-if="!isRange" class="ds-field-group">
          <label class="ds-field-label" for="control-chart-sd">Desvio-padrão (s)</label>
          <BaseInput id="control-chart-sd" v-model="form.standard_deviation" class="ds-field" inputmode="decimal" />
          <p v-if="form.errors.standard_deviation" class="ds-field-error">{{ form.errors.standard_deviation }}</p>
        </div>
        <div class="md:col-span-2">
          <BaseTextarea
            v-model="form.limits_basis"
            label="Fundamento dos limites"
            :rows="2"
            :error="form.errors.limits_basis"
            placeholder="Ex.: valor certificado do MRC; s de reprodutibilidade intralaboratorial da validação."
          />
        </div>
      </div>
    </fieldset>

    <BaseTextarea v-model="form.notes" label="Notas" :rows="2" :error="form.errors.notes" />
  </div>
</template>
