<script setup>
import ParameterForm from "@/Components/parameters/ParameterForm.vue";
import { createParameterDataFromRecord } from "@/Components/parameters/parameterFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeftIcon, BeakerIcon } from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    required: true,
  },
  formulas: {
    type: Array,
    default: () => [],
  },
});

const parameter = props.record?.data ?? props.record;
const form = useForm(createParameterDataFromRecord(parameter));

function submit() {
  form.put(route("parameters.update", { parameter: parameter.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("parameters.index")),
  });
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb" class="mb-5">
        <Link :href="route('parameters.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Parametros analiticos
        </Link>
      </nav>

      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <BeakerIcon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Parametro #{{ parameter.id }}</p>
            <h1 class="ds-heading mt-1 break-words text-2xl">{{ parameter.name }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Atualize a definicao controlada usada nos perfis, worksheets e resultados laboratoriais.</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-if="parameter.code" class="ds-chip font-mono">{{ parameter.code }}</span>
              <span class="ds-chip">{{ parameter.result_is_qualitative ? "Qualitativo" : "Quantitativo" }}</span>
              <span v-if="parameter.requires_calculation" class="ds-chip">Calculado</span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('parameters.index')" class="ds-button ds-button-secondary">Cancelar</Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : "Guardar alteracoes" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <ParameterForm :form="form" :formulas="props.formulas" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('parameters.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alteracoes" }}
        </button>
      </footer>
    </section>
  </form>
</template>
