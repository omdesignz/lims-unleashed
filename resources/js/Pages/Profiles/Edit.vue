<script setup>
import ProfileForm from "@/Components/profiles/ProfileForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createProfileDataFromRecord } from "@/Components/profiles/profileFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { Eye as EyeIcon } from "@lucide/vue";

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
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Perfis analíticos', url: route('profiles.index') }, { title: profile.name }]" :title="profile.name" lede="Actualize a composição analítica, a rastreabilidade e os critérios usados na entrada e execução de amostras.">
      <template #badges>
        <span v-if="profile.code" class="ds-chip font-mono">{{ profile.code }}</span>
        <span class="ds-chip">{{ form.parameters.length }} ensaio(s)</span>
        <span v-if="form.category_id?.department_name" class="ds-chip">{{ form.category_id.department_name }}</span>
      </template>
    </PageHeader>

    <section class="ds-card overflow-hidden">
      <ProfileForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('profiles.show', { profile: profile.id })" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
