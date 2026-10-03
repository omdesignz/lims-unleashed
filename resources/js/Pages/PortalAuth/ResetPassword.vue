<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye as EyeIcon, EyeOff as EyeSlashIcon, KeyRound as KeyIcon } from '@lucide/vue'
import { ref } from 'vue'

defineOptions({ layout: EmptyLayout })
const showPassword = ref(false)
const form = useForm({ token: route().params.token, email: route().params.email, password: '', password_confirmation: '' })
const submit = () => form.post(route('portal.password.update'), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <Head title="Redefinir acesso ao portal" />
  <AuthExperienceShell mode="portal" title="Defina uma nova palavra-passe" eyebrow="Portal do cliente" description="Conclua a recuperação com uma credencial forte para proteger propostas, resultados e certificados." context-title="Acesso revalidado" context-description="Depois da redefinicao, o cliente regressa ao fluxo normal do portal com sessão segura.">
    <div><p class="ds-copy text-sm">Use uma credencial forte e exclusiva para esta conta.</p>
      <form class="mt-6 space-y-4" @submit.prevent="submit"><input v-model="form.token" type="hidden" /><div class="ds-field-group"><label for="portal-reset-email" class="ds-field-label">Correio electrónico</label><BaseInput id="portal-reset-email" v-model="form.email" name="email" type="email" autocomplete="email" readonly class="ds-field cursor-not-allowed bg-[var(--ds-panel-muted)]" /><p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p></div><div class="ds-field-group"><label for="portal-reset-password" class="ds-field-label">Palavra-passe</label><div class="relative"><BaseInput id="portal-reset-password" v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required class="ds-field pr-11" :aria-invalid="Boolean(form.errors.password)" /><button type="button" class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)]" :title="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showPassword = !showPassword"><EyeSlashIcon v-if="showPassword" class="h-4 w-4" /><EyeIcon v-else class="h-4 w-4" /></button></div><p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p></div><div class="ds-field-group"><label for="portal-reset-confirmation" class="ds-field-label">Confirmar palavra-passe</label><BaseInput id="portal-reset-confirmation" v-model="form.password_confirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required class="ds-field" :aria-invalid="Boolean(form.errors.password_confirmation)" /><p v-if="form.errors.password_confirmation" class="ds-field-error">{{ form.errors.password_confirmation }}</p></div><button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><KeyIcon class="h-4 w-4" />{{ form.processing ? 'A actualizar...' : 'Redefinir palavra-passe' }}</button></form>
    </div>
  </AuthExperienceShell>
</template>
