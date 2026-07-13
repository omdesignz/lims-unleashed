<script setup>
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  ArchiveBoxIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  ChevronRightIcon,
  ClockIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  DocumentArrowUpIcon,
  DocumentCheckIcon,
  DocumentTextIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  GlobeAltIcon,
  KeyIcon,
  MapPinIcon,
  PencilSquareIcon,
  PhoneIcon,
  ReceiptPercentIcon,
  StarIcon,
  UserCircleIcon,
} from '@heroicons/vue/24/outline'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
  stats: {
    type: Object,
    default: () => ({
      invoices: { total: 0, paid: 0, pending: 0 },
      collections: { total: 0, processed: 0, pending: 0 },
      quality_certificates: { total: 0, validated: 0 },
      requests: { total: 0, answered: 0, pending: 0 },
      quotes: { total: 0 },
      receipts: { total: 0 },
      credit_notes: { total: 0 },
      contract_guides: { total: 0 },
      imports: { total: 0, invoiced: 0, value: 0 },
      exports: { total: 0, invoiced: 0, value: 0 },
      financial: { total_revenue: 0, paid: 0, pending: 0, credit_notes: 0 },
    }),
  },
  recentActivity: { type: Array, default: () => [] },
  charts: { type: Object, default: () => ({}) },
})

const { hasPermission } = usePermission()
const site = computed(() => props.record?.data ?? {})
const hasPassword = computed(() => Boolean(site.value.has_password))
const showPasswordForm = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const resetEmailSending = ref(false)

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
const passwordStrengthLabel = computed(() => ['Muito fraca', 'Fraca', 'Normal', 'Boa', 'Forte'][passwordStrengthScore.value])
const passwordStrengthBarClass = computed(() => ['bg-rose-500', 'bg-rose-500', 'bg-amber-500', 'bg-cyan-600', 'bg-emerald-600'][passwordStrengthScore.value])
const isPasswordFormValid = computed(() => passwordForm.password.length >= 8
  && passwordForm.password === passwordForm.password_confirmation
  && passwordStrengthScore.value >= 2)

const accountHealthChartSeries = computed(() => [{
  name: 'Registos',
  data: props.charts?.account_health?.series ?? [],
}])
const operationsChartSeries = computed(() => props.charts?.operations?.series ?? [])
const accountHealthTotal = computed(() => accountHealthChartSeries.value[0].data.reduce((total, value) => total + Number(value || 0), 0))
const operationsTotal = computed(() => operationsChartSeries.value.reduce((total, value) => total + Number(value || 0), 0))

const accountHealthChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
  colors: ['#0891b2', '#059669', '#d97706', '#e11d48'],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '48%', distributed: true } },
  dataLabels: { enabled: false },
  grid: { borderColor: '#dbe3e8', strokeDashArray: 4 },
  xaxis: {
    categories: props.charts?.account_health?.labels ?? [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#64748b' } },
  },
  yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: '#64748b' } } },
  legend: { show: false },
}))

const operationsChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
  labels: props.charts?.operations?.labels ?? [],
  colors: ['#059669', '#d97706', '#0891b2', '#e11d48'],
  stroke: { colors: ['#ffffff'] },
  legend: { position: 'bottom', labels: { colors: '#64748b' } },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: { show: true, label: 'Fluxo', formatter: () => `${operationsTotal.value}` },
        },
      },
    },
  },
}))

const documentRows = computed(() => [
  { label: 'Proformas', count: props.stats.quotes?.total ?? 0, icon: DocumentTextIcon },
  { label: 'Recibos', count: props.stats.receipts?.total ?? 0, icon: ReceiptPercentIcon },
  { label: 'Notas de credito', count: props.stats.credit_notes?.total ?? 0, icon: CreditCardIcon },
  { label: 'Guias contratuais', count: props.stats.contract_guides?.total ?? 0, icon: DocumentArrowUpIcon },
  { label: 'Certificados fito', count: (props.stats.imports?.total ?? 0) + (props.stats.exports?.total ?? 0), icon: GlobeAltIcon },
])

function updatePassword() {
  if (!isPasswordFormValid.value) {
    return
  }

  passwordForm.put(route('warehouses.setpass', { warehouse: site.value.id }), {
    preserveScroll: true,
    onSuccess: () => {
      showPasswordForm.value = false
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

  resetEmailSending.value = true
  router.post(route('warehouses.send-password-reset', { warehouse: site.value.id }), {}, {
    preserveScroll: true,
    onFinish: () => {
      resetEmailSending.value = false
    },
  })
}

function formatDate(value) {
  if (!value) {
    return 'N/D'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'N/D' : new Intl.DateTimeFormat('pt-PT', { dateStyle: 'medium' }).format(date)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 2 }).format(Number(value || 0))
}

function resolveActivityIcon(icon) {
  return {
    invoice: DocumentTextIcon,
    collection: ArchiveBoxIcon,
    certificate: DocumentCheckIcon,
    request: ClockIcon,
  }[icon] ?? ClockIcon
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <nav aria-label="Breadcrumb">
          <Link :href="route('warehouses.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
            <ArrowLeftIcon class="h-4 w-4" />Locais operacionais
          </Link>
        </nav>

        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><BuildingOffice2Icon class="h-5 w-5" /></span>
            <div class="min-w-0">
              <p class="ds-kicker">Local #{{ site.id }}</p>
              <h1 class="ds-heading mt-1 break-words text-2xl">{{ site.name || site.code || 'Local operacional' }}</h1>
              <p class="ds-copy mt-1 break-words text-sm">{{ site.customer || 'Sem cliente associado' }} · {{ site.address || 'Morada por definir' }}</p>
              <div class="mt-3 flex flex-wrap gap-2">
                <span v-if="site.code" class="ds-chip font-mono">{{ site.code }}</span>
                <span v-if="site.is_primary" class="ds-chip bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20"><StarIcon class="h-3.5 w-3.5" />Principal</span>
                <span class="ds-chip" :class="site.status === 'active' ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'">{{ site.status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                <span class="ds-chip" :class="hasPassword ? 'text-emerald-700 dark:text-emerald-200' : 'text-amber-700 dark:text-amber-200'"><KeyIcon class="h-3.5 w-3.5" />{{ hasPassword ? 'Portal configurado' : 'Portal por configurar' }}</span>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap gap-2 lg:justify-end">
            <Link v-if="site.customer_id && hasPermission('view_customers')" :href="route('customers.show', { customer: site.customer_id })" class="ds-button ds-button-secondary">Ver cliente</Link>
            <Link v-if="hasPermission('edit_warehouses')" :href="route('warehouses.edit', { warehouse: site.id })" class="ds-button ds-button-primary"><PencilSquareIcon class="h-4 w-4" />Editar local</Link>
          </div>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Faturas</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ stats.invoices.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ stats.invoices.pending || 0 }} pendentes</p></div><DocumentTextIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Colheitas</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ stats.collections.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ stats.collections.processed || 0 }} processadas</p></div><ArchiveBoxIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Certificados</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ stats.quality_certificates.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ stats.quality_certificates.validated || 0 }} validados</p></div><DocumentCheckIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
        <div class="bg-[var(--ds-panel)] p-5"><div class="flex items-start justify-between gap-3"><div><dt class="ds-field-label">Pedidos</dt><dd class="mt-2 text-2xl font-bold text-[var(--ds-text)]">{{ stats.requests.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ stats.requests.pending || 0 }} em aberto</p></div><ClockIcon class="h-5 w-5 text-[var(--ds-text-soft)]" /></div></div>
      </dl>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <main class="space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><MapPinIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Identificacao e contacto</h2></header>
          <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2">
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Cliente</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.customer || 'Nao associado' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">NIF</dt><dd class="mt-2 break-words font-mono text-sm font-bold text-[var(--ds-text)]">{{ site.nif || 'Nao definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Email operacional</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.email || 'Nao definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><EnvelopeIcon class="h-4 w-4" />Email de faturacao</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.invoicing_email || 'Nao definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label inline-flex items-center gap-2"><PhoneIcon class="h-4 w-4" />Telefone principal</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.primary_phone || 'Nao definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5"><dt class="ds-field-label">Telefone alternativo</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.alternative_phone || 'Nao definido' }}</dd></div>
            <div class="bg-[var(--ds-panel)] p-5 sm:col-span-2"><dt class="ds-field-label inline-flex items-center gap-2"><MapPinIcon class="h-4 w-4" />Morada</dt><dd class="mt-2 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.address || 'Nao definida' }}<span v-if="site.municipality || site.province" class="font-semibold text-[var(--ds-text-muted)]"> · {{ [site.municipality, site.province].filter(Boolean).join(', ') }}</span></dd></div>
            <div class="bg-[var(--ds-panel)] p-5 sm:col-span-2"><dt class="ds-field-label">Descricao operacional</dt><dd class="ds-copy mt-2 text-sm">{{ site.description || 'Sem observacoes adicionais.' }}</dd></div>
          </dl>

          <div class="border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]"><UserCircleIcon class="h-4 w-4" /></span><div class="min-w-0"><p class="ds-field-label">Ponto focal</p><p class="mt-1 break-words text-sm font-bold text-[var(--ds-text)]">{{ site.focal_point || 'Nao definido' }}</p><p class="ds-copy mt-1 break-words text-xs">{{ [site.focal_point_email, site.focal_point_contact].filter(Boolean).join(' · ') || 'Sem canais directos registados' }}</p></div></div>
          </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
          <article class="ds-card overflow-hidden">
            <header class="flex items-start justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><div><h2 class="text-base font-bold text-[var(--ds-text)]">Saude da conta</h2><p class="ds-copy mt-1 text-xs">Faturacao e pedidos respondidos.</p></div><span class="ds-chip">{{ accountHealthTotal }} registos</span></header>
            <div class="p-4 sm:p-5"><apexchart type="bar" height="270" :options="accountHealthChartOptions" :series="accountHealthChartSeries" /></div>
          </article>
          <article class="ds-card overflow-hidden">
            <header class="flex items-start justify-between gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><div><h2 class="text-base font-bold text-[var(--ds-text)]">Execucao operacional</h2><p class="ds-copy mt-1 text-xs">Colheitas e certificados.</p></div><span class="ds-chip">{{ operationsTotal }} registos</span></header>
            <div class="p-4 sm:p-5"><apexchart type="donut" height="270" :options="operationsChartOptions" :series="operationsChartSeries" /></div>
          </article>
        </section>

        <section v-if="hasPermission('edit_warehouses')" class="ds-card overflow-hidden">
          <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]"><KeyIcon class="h-4 w-4" /></span><div><h2 class="text-base font-bold text-[var(--ds-text)]">Acesso ao portal</h2><p class="ds-copy mt-1 text-sm">{{ hasPassword ? 'O local tem credenciais activas.' : 'Defina credenciais antes de disponibilizar o portal.' }}</p></div></div>
            <button type="button" class="ds-button ds-button-secondary" @click="showPasswordForm ? cancelPasswordUpdate() : showPasswordForm = true">{{ showPasswordForm ? 'Cancelar' : hasPassword ? 'Alterar palavra-passe' : 'Definir palavra-passe' }}</button>
          </header>

          <form v-if="showPasswordForm" class="border-b border-[var(--ds-border)]" @submit.prevent="updatePassword">
            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
              <div class="ds-field-group">
                <label for="site-password" class="ds-field-label">Nova palavra-passe</label>
                <div class="relative"><input id="site-password" v-model="passwordForm.password" :type="showNewPassword ? 'text' : 'password'" class="ds-field pr-11" autocomplete="new-password" /><button type="button" class="ds-icon-button absolute right-1.5 top-1.5" :title="showNewPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showNewPassword = !showNewPassword"><EyeIcon v-if="showNewPassword" class="h-4 w-4" /><EyeSlashIcon v-else class="h-4 w-4" /></button></div>
                <p v-if="passwordForm.errors.password" class="ds-field-error">{{ passwordForm.errors.password }}</p>
              </div>
              <div class="ds-field-group">
                <label for="site-password-confirmation" class="ds-field-label">Confirmar palavra-passe</label>
                <div class="relative"><input id="site-password-confirmation" v-model="passwordForm.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" class="ds-field pr-11" autocomplete="new-password" /><button type="button" class="ds-icon-button absolute right-1.5 top-1.5" :title="showConfirmPassword ? 'Ocultar confirmacao' : 'Mostrar confirmacao'" @click="showConfirmPassword = !showConfirmPassword"><EyeIcon v-if="showConfirmPassword" class="h-4 w-4" /><EyeSlashIcon v-else class="h-4 w-4" /></button></div>
                <p v-if="passwordForm.errors.password_confirmation" class="ds-field-error">{{ passwordForm.errors.password_confirmation }}</p>
              </div>

              <div v-if="passwordForm.password" class="md:col-span-2">
                <div class="flex items-center justify-between gap-3 text-xs"><span class="font-bold text-[var(--ds-text-muted)]">Forca da credencial</span><span class="font-bold text-[var(--ds-text)]">{{ passwordStrengthLabel }}</span></div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-subtle)]"><div class="h-full transition-all" :class="passwordStrengthBarClass" :style="{ width: `${passwordStrengthScore * 25}%` }" /></div>
                <ul class="mt-3 grid gap-2 text-xs sm:grid-cols-2"><li v-for="(passed, label) in { '8 ou mais caracteres': passwordStrength.length, 'Maiusculas e minusculas': passwordStrength.mixed, 'Pelo menos um numero': passwordStrength.numbers, 'Pelo menos um simbolo': passwordStrength.special }" :key="label" class="flex items-center gap-2 font-semibold" :class="passed ? 'text-emerald-700 dark:text-emerald-200' : 'text-[var(--ds-text-soft)]'"><CheckCircleIcon class="h-4 w-4" />{{ label }}</li></ul>
              </div>
            </div>
            <footer class="flex justify-end border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6"><button type="submit" class="ds-button ds-button-primary" :disabled="passwordForm.processing || !isPasswordFormValid"><ArrowPathIcon v-if="passwordForm.processing" class="h-4 w-4 animate-spin" />{{ passwordForm.processing ? 'A actualizar...' : 'Guardar credencial' }}</button></footer>
          </form>

          <div v-if="hasPassword && !showPasswordForm" class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div><p class="text-sm font-bold text-[var(--ds-text)]">Recuperacao pelo cliente</p><p class="ds-copy mt-1 text-sm">Envie uma ligacao de reposicao para {{ site.email || 'o email operacional' }}.</p></div>
            <button type="button" class="ds-button ds-button-secondary" :disabled="resetEmailSending || !site.email" @click="sendPasswordResetEmail"><ArrowPathIcon v-if="resetEmailSending" class="h-4 w-4 animate-spin" /><EnvelopeIcon v-else class="h-4 w-4" />{{ resetEmailSending ? 'A enviar...' : 'Enviar reposicao' }}</button>
          </div>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6"><h2 class="flex items-center gap-2 text-base font-bold text-[var(--ds-text)]"><ClockIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Actividade recente</h2></header>
          <ul v-if="recentActivity.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 px-5 py-4 sm:px-6"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]"><component :is="resolveActivityIcon(activity.icon)" class="h-4 w-4" /></span><div class="min-w-0 flex-1"><p class="break-words text-sm font-bold text-[var(--ds-text)]">{{ activity.title }}</p><p class="ds-copy mt-1 break-words text-xs">{{ activity.description }}</p></div><time class="shrink-0 text-xs font-semibold text-[var(--ds-text-soft)]">{{ activity.time }}</time></li>
          </ul>
          <div v-else class="p-5 sm:p-6"><div class="ds-empty-state py-8 text-center"><ClockIcon class="mx-auto h-7 w-7 text-[var(--ds-text-soft)]" /><p class="mt-2 text-sm font-bold">Sem actividade recente</p></div></div>
        </section>
      </main>

      <aside class="space-y-6">
        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Fluxos relacionados</h2></header>
          <nav class="divide-y divide-[var(--ds-border)]">
            <Link v-if="hasPermission('view_invoices')" :href="route('invoices.index', { warehouse: site.id })" class="flex items-center gap-3 px-5 py-3.5 hover:bg-[var(--ds-panel-subtle)]"><DocumentTextIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><span class="min-w-0 flex-1 text-sm font-bold text-[var(--ds-text)]">Faturas</span><span class="ds-chip">{{ stats.invoices.total || 0 }}</span><ChevronRightIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /></Link>
            <Link v-if="hasPermission('view_direct_collections')" :href="route('directcollections.index', { warehouse: site.id })" class="flex items-center gap-3 px-5 py-3.5 hover:bg-[var(--ds-panel-subtle)]"><ArchiveBoxIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><span class="min-w-0 flex-1 text-sm font-bold text-[var(--ds-text)]">Colheitas</span><span class="ds-chip">{{ stats.collections.total || 0 }}</span><ChevronRightIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /></Link>
            <Link v-if="hasPermission('view_quality_certificates')" :href="route('qualitycertificates.index', { warehouse: site.id })" class="flex items-center gap-3 px-5 py-3.5 hover:bg-[var(--ds-panel-subtle)]"><DocumentCheckIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><span class="min-w-0 flex-1 text-sm font-bold text-[var(--ds-text)]">Certificados</span><span class="ds-chip">{{ stats.quality_certificates.total || 0 }}</span><ChevronRightIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /></Link>
            <Link v-if="hasPermission('view_contract_guides')" :href="route('contractguides.index', { warehouse: site.id })" class="flex items-center gap-3 px-5 py-3.5 hover:bg-[var(--ds-panel-subtle)]"><DocumentArrowUpIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /><span class="min-w-0 flex-1 text-sm font-bold text-[var(--ds-text)]">Guias</span><span class="ds-chip">{{ stats.contract_guides.total || 0 }}</span><ChevronRightIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /></Link>
          </nav>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><CurrencyDollarIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />Resumo financeiro</h2></header>
          <dl class="divide-y divide-[var(--ds-border)]">
            <div class="px-5 py-4"><dt class="ds-field-label">Receita total</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ formatCurrency(stats.financial.total_revenue) }}</dd></div>
            <div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Pago</dt><dd class="text-sm font-bold text-emerald-700 dark:text-emerald-200">{{ formatCurrency(stats.financial.paid) }}</dd></div>
            <div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Pendente</dt><dd class="text-sm font-bold text-amber-700 dark:text-amber-200">{{ formatCurrency(stats.financial.pending) }}</dd></div>
            <div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Notas de credito</dt><dd class="text-sm font-bold text-rose-700 dark:text-rose-200">{{ formatCurrency(stats.financial.credit_notes) }}</dd></div>
          </dl>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><GlobeAltIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />Importacao e exportacao</h2></header>
          <dl class="grid gap-px bg-[var(--ds-border)] grid-cols-2"><div class="bg-[var(--ds-panel)] p-4"><dt class="ds-field-label">Importacoes</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ stats.imports.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ formatCurrency(stats.imports.value) }}</p></div><div class="bg-[var(--ds-panel)] p-4"><dt class="ds-field-label">Exportacoes</dt><dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ stats.exports.total || 0 }}</dd><p class="ds-copy mt-1 text-xs">{{ formatCurrency(stats.exports.value) }}</p></div></dl>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="text-sm font-bold text-[var(--ds-text)]">Mapa documental</h2></header>
          <ul class="divide-y divide-[var(--ds-border)]"><li v-for="document in documentRows" :key="document.label" class="flex items-center gap-3 px-5 py-3"><component :is="document.icon" class="h-4 w-4 text-[var(--ds-text-soft)]" /><span class="min-w-0 flex-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ document.label }}</span><span class="ds-chip">{{ document.count }}</span></li></ul>
        </section>

        <section class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><Cog6ToothIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />Metadados</h2></header>
          <dl class="divide-y divide-[var(--ds-border)]"><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Criado</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ formatDate(site.created_at) }}</dd></div><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Actualizado</dt><dd class="text-xs font-bold text-[var(--ds-text)]">{{ formatDate(site.updated_at) }}</dd></div><div class="flex items-center justify-between gap-3 px-5 py-3"><dt class="text-xs font-semibold text-[var(--ds-text-muted)]">Estado</dt><dd class="ds-chip">{{ site.status || 'active' }}</dd></div></dl>
        </section>
      </aside>
    </div>
  </div>
</template>
