<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  RefreshCw as ArrowPathIcon,
  FlaskConical as BeakerIcon,
  CircleCheck as CheckCircleIcon,
  Clock as ClockIcon,
  Settings as Cog6ToothIcon,
  Mail as EnvelopeIcon,
  Eye as EyeIcon,
  EyeOff as EyeSlashIcon,
  Info as InformationCircleIcon,
  KeyRound as KeyIcon,
  MapPin as MapPinIcon,
  SquarePen as PencilSquareIcon,
  Phone as PhoneIcon,
  CircleUser as UserCircleIcon,
} from '@lucide/vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
  siteState: { type: Object, default: () => ({}) },
})

const { hasPermission } = usePermission()
const site = computed(() => props.record?.data ?? {})
const summary = computed(() => props.siteState?.summary ?? {})
const recentSamples = computed(() => props.siteState?.recent_samples ?? [])
const hasPassword = computed(() => Boolean(site.value.has_password))
const showPasswordForm = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const resetEmailSending = ref(false)
const resetEmailError = ref('')

const passwordForm = useForm({
  password: '',
  password_confirmation: '',
})

const passwordStrength = computed(() => {
  const password = passwordForm.password

  return {
    length: password.length >= 8,
    mixed: /[a-z]/.test(password) && /[A-Z]/.test(password),
    numbers: /\d/.test(password),
    special: /[^A-Za-z0-9]/.test(password),
  }
})

const passwordStrengthScore = computed(() => Object.values(passwordStrength.value).filter(Boolean).length)
const isPasswordFormValid = computed(() => passwordStrengthScore.value === 4
  && passwordForm.password === passwordForm.password_confirmation)
const metrics = computed(() => [
  { label: 'Amostras', value: summary.value.total_samples || 0, icon: BeakerIcon },
  { label: 'Em curso', value: summary.value.samples_in_progress || 0, icon: ClockIcon },
  { label: 'Concluídas', value: summary.value.completed_samples || 0, icon: CheckCircleIcon },
])

function updatePassword() {
  if (!isPasswordFormValid.value) {
    return
  }

  passwordForm.put(route('warehouses.setpass', { warehouse: site.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showPasswordForm.value = false
      showNewPassword.value = false
      showConfirmPassword.value = false
      passwordForm.reset()
    },
  })
}

function cancelPasswordUpdate() {
  showPasswordForm.value = false
  showNewPassword.value = false
  showConfirmPassword.value = false
  passwordForm.reset()
  passwordForm.clearErrors()
}

function sendPasswordResetEmail() {
  if (resetEmailSending.value) {
    return
  }

  resetEmailError.value = ''
  resetEmailSending.value = true
  router.post(route('warehouses.send-password-reset', { warehouse: site.value.id }), {}, {
    preserveScroll: true,
    onError: (errors) => {
      resetEmailError.value = errors.email || 'Não foi possível enviar a ligação de reposição.'
    },
    onFinish: () => {
      resetEmailSending.value = false
    },
  })
}

function formatDate(value) {
  if (!value) {
    return 'Não definido'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Não definido' : new Intl.DateTimeFormat('pt-PT', { dateStyle: 'medium' }).format(date)
}

function statusClass(status) {
  const normalized = String(status || '').toLowerCase()

  if (['completado', 'completed'].includes(normalized)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20'
  }

  if (['por_iniciar', 'en_progreso', 'en_pausa'].includes(normalized)) {
    return 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20'
  }

  return 'bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-[var(--ds-border)]'
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :trail="[{ title: 'Locais operacionais', url: route('warehouses.index') }, { title: site.name || site.code || 'Local operacional' }]" :title="site.name || site.code || 'Local operacional'" :lede="`${site.customer || 'Sem cliente associado'} · ${site.address || 'Morada por definir'}`">
      <template #badges>
          <span v-if="site.code" class="ds-chip font-mono">{{ site.code }}</span>
          <span class="ds-chip" :class="site.status === 'active' ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'">{{ site.status === 'active' ? 'Activo' : 'Inactivo' }}</span>
          <span class="ds-chip"><KeyIcon class="h-3.5 w-3.5" />{{ hasPassword ? 'Portal configurado' : 'Portal por configurar' }}</span>
      </template>
      <template #actions>
        <Link v-if="site.customer_id && hasPermission('view_customers')" :href="route('customers.show', { customer: site.customer_id })" class="ds-button ds-button-secondary">Ver cliente</Link>
        <Link v-if="hasPermission('edit_warehouses')" :href="route('warehouses.edit', { warehouse: site.id })" class="ds-button ds-button-primary"><PencilSquareIcon class="h-4 w-4" />Editar local</Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div v-for="metric in metrics" :key="metric.label" class="pl-cell">
        <dt class="pl-k pl-muted">{{ metric.label }}</dt>
        <dd class="pl-cell-value">{{ metric.value }}</dd>
      </div>
    </dl>
    <p class="text-[13px] text-[var(--pl-muted)]">Identidade partilhada; actividade laboratorial visível apenas para o laboratório activo.</p>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <main class="min-w-0 space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Identificação e contacto</h2></header>
          <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2">
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Cliente</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.customer || 'Não associado' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">NIF</dt><dd class="mt-2 break-words font-mono text-sm font-bold text-[var(--ds-text)]">{{ site.nif || 'Não definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Correio electrónico operacional</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.email || 'Não definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Correio electrónico de facturação</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.invoicing_email || 'Não definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><PhoneIcon class="h-4 w-4" />Telefone principal</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.primary_phone || 'Não definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Telefone alternativo</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.alternative_phone || 'Não definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5 sm:col-span-2"><dt class="ds-field-label">Morada</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.address || 'Não definida' }}<span v-if="site.municipality || site.province" class="font-semibold text-[var(--ds-text-muted)]"> · {{ [site.municipality, site.province].filter(Boolean).join(', ') }}</span></dd></div>
            <div class="bg-[var(--ds-panel)] p-5 sm:col-span-2"><dt class="ds-field-label">Descrição operacional</dt><dd class="ds-copy mt-2 text-sm">{{ site.description || 'Sem observações adicionais.' }}</dd></div>
          </dl>
          <div class="border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3"><UserCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" /><div class="min-w-0"><p class="ds-field-label">Ponto focal</p><p class="mt-1 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.focal_point || 'Não definido' }}</p><p class="ds-copy mt-1 break-words text-xs">{{ [site.focal_point_email, site.focal_point_contact].filter(Boolean).join(' · ') || 'Sem canais directos registados' }}</p></div></div>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><BeakerIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Amostras recentes</h2><p class="ds-copy mt-1 text-xs">Amostras deste local no laboratório activo.</p></header>
          <div v-if="recentSamples.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="sample in recentSamples" :key="sample.id" class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
              <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="break-words text-sm font-bold text-[var(--ds-text)]">{{ sample.name || 'Amostra sem nome' }}</h3><span :class="['ds-chip', statusClass(sample.status)]">{{ sample.status || 'Sem estado' }}</span></div><p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]"><span class="font-mono">{{ sample.code || 'Sem código' }}</span> · recebida {{ formatDate(sample.received_at) }}</p></div>
              <p class="text-xs font-semibold text-[var(--ds-text-muted)]">Fim: {{ formatDate(sample.analysis_end_date) }}</p>
            </article>
          </div>
          <div v-else class="ds-empty-state m-5 py-8 text-center sm:m-6"><BeakerIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" /><p class="mt-2 text-sm font-bold">Sem amostras deste laboratório neste local</p></div>
        </section>

        <section v-if="hasPermission('edit_warehouses')" class="ds-card overflow-hidden">
          <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div class="flex items-start gap-3"><KeyIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" /><div><h2 class="text-base font-bold text-[var(--ds-text)]">Acesso ao portal</h2><p class="ds-copy mt-1 text-sm">{{ hasPassword ? 'O local tem credenciais activas.' : 'Defina credenciais antes de disponibilizar o portal.' }}</p></div></div>
            <button type="button" class="ds-button ds-button-secondary" @click="showPasswordForm ? cancelPasswordUpdate() : showPasswordForm = true">{{ showPasswordForm ? 'Cancelar' : hasPassword ? 'Alterar palavra-passe' : 'Definir palavra-passe' }}</button>
          </header>

          <form v-if="showPasswordForm" class="border-b border-[var(--ds-border)]" @submit.prevent="updatePassword">
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
              <div class="ds-field-group"><label for="site-password" class="ds-field-label">Nova palavra-passe</label><div class="relative"><BaseInput id="site-password" v-model="passwordForm.password" :type="showNewPassword ? 'text' : 'password'" class="ds-field pr-11" autocomplete="new-password" /><button type="button" class="ds-icon-button absolute right-1.5 top-1.5" :title="showNewPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showNewPassword = !showNewPassword"><EyeIcon v-if="showNewPassword" class="h-4 w-4" /><EyeSlashIcon v-else class="h-4 w-4" /></button></div><p v-if="passwordForm.errors.password" class="ds-field-error">{{ passwordForm.errors.password }}</p></div>
              <div class="ds-field-group"><label for="site-password-confirmation" class="ds-field-label">Confirmar palavra-passe</label><div class="relative"><BaseInput id="site-password-confirmation" v-model="passwordForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" class="ds-field pr-11" autocomplete="new-password" /><button type="button" class="ds-icon-button absolute right-1.5 top-1.5" :title="showConfirmPassword ? 'Ocultar confirmação' : 'Mostrar confirmação'" @click="showConfirmPassword = !showConfirmPassword"><EyeIcon v-if="showConfirmPassword" class="h-4 w-4" /><EyeSlashIcon v-else class="h-4 w-4" /></button></div><p v-if="passwordForm.errors.password_confirmation" class="ds-field-error">{{ passwordForm.errors.password_confirmation }}</p></div>
              <div v-if="passwordForm.password" class="md:col-span-2"><p class="text-xs font-bold text-[var(--ds-text-muted)]">Requisitos da palavra-passe</p><ul class="mt-2 grid gap-2 text-xs sm:grid-cols-2"><li v-for="(passed, label) in { '8 ou mais caracteres': passwordStrength.length, 'Maiúsculas e minúsculas': passwordStrength.mixed, 'Pelo menos um número': passwordStrength.numbers, 'Pelo menos um símbolo': passwordStrength.special }" :key="label" class="flex items-center gap-2 font-semibold" :class="passed ? 'text-emerald-700 dark:text-emerald-200' : 'text-[var(--ds-text-soft)]'"><CheckCircleIcon class="h-4 w-4" />{{ label }}</li></ul></div>
            </div>
            <footer class="flex justify-end border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6"><button type="submit" class="ds-button ds-button-primary" :disabled="passwordForm.processing || !isPasswordFormValid"><ArrowPathIcon v-if="passwordForm.processing" class="h-4 w-4 animate-spin" />{{ passwordForm.processing ? 'A actualizar...' : 'Guardar credencial' }}</button></footer>
          </form>

          <div v-if="hasPassword && !showPasswordForm" class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-bold text-[var(--ds-text)]">Recuperação pelo cliente</p><p class="ds-copy mt-1 text-sm">Envie uma ligação de reposição para {{ site.email || 'o correio electrónico operacional' }}.</p></div><button type="button" class="ds-button ds-button-secondary" :disabled="resetEmailSending || !site.email" @click="sendPasswordResetEmail"><ArrowPathIcon v-if="resetEmailSending" class="h-4 w-4 animate-spin" /><EnvelopeIcon v-else class="h-4 w-4" />{{ resetEmailSending ? 'A enviar...' : 'Enviar reposição' }}</button></div>
            <p v-if="resetEmailError" role="alert" class="ds-field-error mt-3">{{ resetEmailError }}</p>
          </div>
        </section>
      </main>

      <aside class="space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><InformationCircleIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Âmbito deste local</h2></header>
          <p class="px-5 py-4 text-sm font-medium leading-6 text-[var(--ds-text-muted)]">O cliente e o local são partilhados. As amostras apresentadas pertencem apenas ao laboratório activo. Facturas, pedidos do portal e outros documentos sem titularidade laboratorial definida não são resumidos aqui.</p>
        </section>
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><Cog6ToothIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />Metadados</h2></header>
          <dl class="divide-y divide-[var(--ds-border)]"><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Criado</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ formatDate(site.created_at) }}</dd></div><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Actualizado</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ formatDate(site.updated_at) }}</dd></div><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Estado</dt><dd class="ds-chip">{{ site.status || 'active' }}</dd></div></dl>
        </section>
      </aside>
    </div>
  </div>
</template>
