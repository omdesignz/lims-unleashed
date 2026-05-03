<template>
    <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">Segurança</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500">A segurança em sistemas informáticos é uma questão crítica e importante para empresas e indivíduos.</p>
  </div>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div>
                <UpdateProfileInformationForm :user="$page.props.auth" />

                <SectionBorder />
            </div>

            <div>
                <UpdatePasswordForm class="mt-10 sm:mt-0" />

                <SectionBorder />
            </div>

            <div>
                <TwoFactorAuthenticationForm
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                    class="mt-10 sm:mt-0"
                />

                <SectionBorder />
            </div>

            <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

            <template>
                <SectionBorder />

                <DeleteUserForm class="mt-10 sm:mt-0" />
            </template>
        </div>
    </div>
</template>

<script setup>
import DeleteUserForm from "../../Shared/delete-user-form.vue";
import LogoutOtherBrowserSessionsForm from "./Partials/logout-other-browser-sessions-form.vue";
import SectionBorder from "../../Components/section-border.vue";
import TwoFactorAuthenticationForm from "./Partials/two-factor-authentication-form.vue";
import UpdatePasswordForm from "./Partials/update-password-form.vue";
import UpdateProfileInformationForm from "./Partials/update-profile-information-form.vue";
import Layout from "@/Shared/Layouts/Layout.vue"


defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

defineOptions({
    layout: Layout
});


</script>

<style scoped>

</style>
