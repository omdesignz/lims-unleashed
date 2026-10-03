<script setup>
import MatrixForm from "@/Components/matrixes/MatrixForm.vue";
import { createEmptyMatrixData } from "@/Components/matrixes/matrixFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeft as ArrowLeftIcon, LayoutDashboard as RectangleGroupIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const form = useForm(createEmptyMatrixData());

function submit() {
  form.post(route("matrixes.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("matrixes.index")),
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
          <div>
            <p class="ds-kicker">Novo âmbito comercial</p>
            <h1 class="ds-heading mt-1 text-2xl">Adicionar matriz</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Agrupe perfis analíticos compativeis e configure preço, imposto e controlo departamental.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('matrixes.index')" class="ds-button ds-button-secondary">Cancelar</Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : "Adicionar matriz" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <MatrixForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('matrixes.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Adicionar matriz" }}
        </button>
      </footer>
    </section>
  </form>
</template>
