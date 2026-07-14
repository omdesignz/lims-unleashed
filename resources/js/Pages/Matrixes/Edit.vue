<script setup>
import MatrixForm from "@/Components/matrixes/MatrixForm.vue";
import { createMatrixDataFromRecord } from "@/Components/matrixes/matrixFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeftIcon, EyeIcon, RectangleGroupIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const matrix = props.record?.data ?? props.record;
const form = useForm(createMatrixDataFromRecord(matrix));

function submit() {
  form.put(route("matrixes.update", { matrix: matrix.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("matrixes.show", { matrix: matrix.id })),
  });
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb" class="mb-5">
        <Link :href="route('matrixes.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" />
          Matrizes
        </Link>
      </nav>
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <RectangleGroupIcon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Matriz #{{ matrix.id }}</p>
            <h1 class="ds-heading mt-1 break-words text-2xl">{{ matrix.code }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Actualize o âmbito de perfis, o preço comercial e a regra fiscal usada no catálogo.</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span class="ds-chip">{{ form.profiles.length }} perfil(is)</span>
              <span class="ds-chip">{{ form.charge_tax ? `${form.tax_percentage}% imposto` : "Isenta" }}</span>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('matrixes.show', { matrix: matrix.id })" class="ds-button ds-button-secondary">
            <EyeIcon class="h-4 w-4" />
            Ver matriz
          </Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : "Guardar alterações" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <MatrixForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('matrixes.show', { matrix: matrix.id })" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </footer>
    </section>
  </form>
</template>
