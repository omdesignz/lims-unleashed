<script setup>
import ProfileForm from "@/Components/profiles/ProfileForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createEmptyProfileData } from "@/Components/profiles/profileFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const form = useForm(createEmptyProfileData());

function submit() {
  form.post(route("profiles.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("profiles.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Perfis analíticos', url: route('profiles.index') }, { title: 'Adicionar perfil' }]" title="Adicionar perfil" lede="Defina o âmbito departamental e componha os ensaios com métodos, critérios e rastreabilidade." />

    <section class="ds-card overflow-hidden">
      <ProfileForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('profiles.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Adicionar perfil" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
