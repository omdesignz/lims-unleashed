<script setup>
import ProfileForm from "@/Components/profiles/ProfileForm.vue";
import { createProfileDataFromRecord } from "@/Components/profiles/profileFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ArrowLeft as ArrowLeftIcon, ClipboardCheck as ClipboardDocumentCheckIcon, Eye as EyeIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const profile = props.record?.data ?? props.record;
const form = useForm(createProfileDataFromRecord(profile));

function submit() {
  form.put(route("profiles.update", { profile: profile.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("profiles.show", { profile: profile.id })),
  });
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <nav aria-label="Breadcrumb" class="mb-5">
        <Link :href="route('profiles.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
          <ArrowLeftIcon class="h-4 w-4" /> Perfis analíticos </Link>
      </nav>

      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <ClipboardDocumentCheckIcon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="ds-kicker">Perfil #{{ profile.id }}</p>
            <h1 class="ds-heading mt-1 break-words text-2xl">{{ profile.name }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">Actualize a composição analítica, a rastreabilidade e os critérios usados na entrada e execução de amostras.</p>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-if="profile.code" class="ds-chip font-mono">{{ profile.code }}</span>
              <span class="ds-chip">{{ form.parameters.length }} ensaio(s)</span>
              <span v-if="form.category_id?.department_name" class="ds-chip">{{ form.category_id.department_name }}</span>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
          <Link :href="route('profiles.show', { profile: profile.id })" class="ds-button ds-button-secondary">
            <EyeIcon class="h-4 w-4" /> Ver perfil
          </Link>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : "Guardar alterações" }}
          </button>
        </div>
      </div>
    </section>

    <section class="ds-card overflow-hidden">
      <ProfileForm :form="form" />
      <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
        <Link :href="route('profiles.show', { profile: profile.id })" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </footer>
    </section>
  </form>
</template>
