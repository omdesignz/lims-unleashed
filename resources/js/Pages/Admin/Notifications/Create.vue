<script setup>
import NotificationAdminHeader from '@/Components/notifications/NotificationAdminHeader.vue'
import {
  notificationIndicatorClasses,
  notificationPriorityClasses,
  notificationPriorityLabel,
  notificationTypeClasses,
  notificationTypeLabel,
} from '@/Composables/useNotificationPresentation'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  CheckIcon,
  ClockIcon,
  DocumentDuplicateIcon,
  EnvelopeIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  UserGroupIcon,
  UserIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  users: { type: Array, default: () => [] },
  userGroups: { type: Array, default: () => [] },
  notificationTypes: { type: Object, default: () => ({}) },
  defaultTemplates: { type: Object, default: () => ({}) },
})

const page = usePage()
const settings = computed(() => page.props.settings ?? {})
const userSearch = ref('')
const showValidation = ref(false)

const form = useForm({
  title: settings.value.notification_default_title || '',
  message: settings.value.notification_default_message || '',
  type: 'info',
  priority: 'normal',
  recipient_type: 'all',
  recipients: [],
  group: 'all',
  schedule_send: false,
  scheduled_at: '',
  expires_at: '',
})

const recipientOptions = [
  { value: 'all', label: 'Todos', description: 'Todos os utilizadores registados', icon: UsersIcon },
  { value: 'group', label: 'Grupo', description: 'Segmento operacional predefinido', icon: UserGroupIcon },
  { value: 'specific', label: 'Especificos', description: 'Selecao individual de utilizadores', icon: UserIcon },
]

const selectedGroup = computed(() => props.userGroups.find((group) => group.id === form.group))
const filteredUsers = computed(() => {
  const query = userSearch.value.trim().toLowerCase()
  if (!query) return props.users
  return props.users.filter((user) => `${user.name} ${user.email}`.toLowerCase().includes(query))
})
const isAllSelected = computed(() => props.users.length > 0 && form.recipients.length === props.users.length)
const estimatedRecipients = computed(() => {
  if (form.recipient_type === 'specific') return form.recipients.length
  if (form.recipient_type === 'group') return selectedGroup.value?.count || 0
  return props.users.length
})
const senderAlias = computed(() => settings.value.notification_sender_alias || page.props.auth?.user?.name || 'Sistema')
const isFormValid = computed(() => Boolean(
  form.title.trim()
  && form.message.trim()
  && (form.recipient_type !== 'specific' || form.recipients.length > 0)
))

const applyTemplate = (template) => {
  form.title = template.title
  form.message = template.message
  form.type = template.type
}

const toggleSelectAll = () => {
  form.recipients = isAllSelected.value ? [] : props.users.map((user) => user.id)
}

const submit = () => {
  showValidation.value = true
  if (!isFormValid.value) return
  form.post(route('admin.notifications.store'))
}
</script>

<template>
  <div class="space-y-5">
    <NotificationAdminHeader
      title="Compor notificacao"
      description="Prepare uma mensagem operacional, defina a audiencia e confirme o alcance antes da emissao."
    >
      <template #actions>
        <Link :href="route('admin.notifications.index')" class="ds-button ds-button-secondary">
          <ArrowLeftIcon class="h-4 w-4" /> Cancelar
        </Link>
      </template>
    </NotificationAdminHeader>

    <form class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.65fr)]" @submit.prevent="submit">
      <div class="space-y-5">
        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Ponto de partida</p>
            <h2 class="ds-heading mt-1 flex items-center gap-2 text-base"><DocumentDuplicateIcon class="h-4 w-4" /> Modelos de mensagem</h2>
          </header>
          <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6">
            <button
              v-for="(template, key) in defaultTemplates"
              :key="key"
              type="button"
              class="flex items-start gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-left transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[rgb(var(--primary-50-rgb))] dark:hover:bg-[rgb(var(--primary-950-rgb)/0.25)]"
              @click="applyTemplate(template)"
            >
              <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="notificationIndicatorClasses(template.type)" />
              <span class="min-w-0"><span class="block truncate text-sm font-bold text-[var(--ds-text)]">{{ template.title }}</span><span class="mt-1 line-clamp-2 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ template.message }}</span></span>
            </button>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Conteudo</p>
            <h2 class="ds-heading mt-1 flex items-center gap-2 text-base"><EnvelopeIcon class="h-4 w-4" /> Mensagem operacional</h2>
          </header>
          <div class="space-y-5 p-5 sm:p-6">
            <div class="ds-field-group">
              <label for="notification-title" class="ds-field-label">Titulo <span class="ds-field-required">*</span></label>
              <BaseInput id="notification-title" v-model="form.title" type="text" maxlength="255" class="ds-field" :aria-invalid="Boolean(form.errors.title || (showValidation && !form.title.trim()))" placeholder="Ex.: Resultado do ensaio disponivel" />
              <div class="flex justify-between gap-3"><p v-if="form.errors.title || (showValidation && !form.title.trim())" class="ds-field-error">{{ form.errors.title || 'Indique um titulo.' }}</p><p class="ml-auto text-xs font-semibold text-[var(--ds-text-soft)]">{{ form.title.length }}/255</p></div>
            </div>

            <div class="ds-field-group">
              <label for="notification-message" class="ds-field-label">Mensagem <span class="ds-field-required">*</span></label>
              <textarea id="notification-message" v-model="form.message" rows="7" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.message || (showValidation && !form.message.trim()))" placeholder="Descreva a acao, prazo ou informacao que o destinatario deve conhecer." />
              <div class="flex justify-between gap-3"><p v-if="form.errors.message || (showValidation && !form.message.trim())" class="ds-field-error">{{ form.errors.message || 'Introduza a mensagem.' }}</p><p class="ml-auto text-xs font-semibold text-[var(--ds-text-soft)]">{{ form.message.length }} caracteres</p></div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="ds-field-group">
                <label for="notification-type" class="ds-field-label">Tipo</label>
                <BaseSelect id="notification-type" v-model="form.type" class="ds-field">
                  <option v-for="(type, key) in notificationTypes" :key="key" :value="key">{{ type.label }}</option>
                </BaseSelect>
                <p v-if="form.errors.type" class="ds-field-error">{{ form.errors.type }}</p>
              </div>
              <div class="ds-field-group">
                <label for="notification-priority" class="ds-field-label">Prioridade</label>
                <BaseSelect id="notification-priority" v-model="form.priority" class="ds-field">
                  <option value="low">Baixa</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option>
                </BaseSelect>
                <p v-if="form.errors.priority" class="ds-field-error">{{ form.errors.priority }}</p>
              </div>
              <div class="ds-field-group sm:col-span-2">
                <label for="notification-expiry" class="ds-field-label">Expira em <span class="font-normal text-[var(--ds-text-soft)]">(opcional)</span></label>
                <DateTimePicker id="notification-expiry" v-model="form.expires_at" type="datetime-local" class="ds-field" />
                <p class="ds-field-help">A mensagem deixa de ser relevante depois desta data.</p>
                <p v-if="form.errors.expires_at" class="ds-field-error">{{ form.errors.expires_at }}</p>
              </div>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Audiencia</p>
            <h2 class="ds-heading mt-1 flex items-center gap-2 text-base"><UsersIcon class="h-4 w-4" /> Destinatarios</h2>
          </header>
          <div class="space-y-5 p-5 sm:p-6">
            <div class="grid gap-3 sm:grid-cols-3">
              <label v-for="option in recipientOptions" :key="option.value" class="cursor-pointer rounded-lg border p-4 transition" :class="form.recipient_type === option.value ? 'border-[rgb(var(--primary-500-rgb))] bg-[rgb(var(--primary-50-rgb))] ring-1 ring-[rgb(var(--primary-500-rgb))] dark:bg-[rgb(var(--primary-950-rgb)/0.25)]' : 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] hover:border-[rgb(var(--primary-300-rgb))]'">
                <RadioInput v-model="form.recipient_type" type="radio" :value="option.value" class="sr-only" />
                <component :is="option.icon" class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
                <span class="mt-3 block text-sm font-bold text-[var(--ds-text)]">{{ option.label }}</span>
                <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ option.description }}</span>
              </label>
            </div>

            <div v-if="form.recipient_type === 'group'" class="ds-field-group">
              <label for="notification-group" class="ds-field-label">Grupo de utilizadores</label>
              <BaseSelect id="notification-group" v-model="form.group" class="ds-field">
                <option v-for="group in userGroups" :key="group.id" :value="group.id">{{ group.name }} ({{ group.count }})</option>
              </BaseSelect>
              <p class="ds-field-help">{{ selectedGroup?.description }}</p>
            </div>

            <div v-if="form.recipient_type === 'specific'" class="overflow-hidden rounded-lg border border-[var(--ds-border)]">
              <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative min-w-0 flex-1">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
                  <BaseInput v-model="userSearch" type="search" class="ds-field pl-9" placeholder="Pesquisar utilizador" />
                </div>
                <button type="button" class="ds-button ds-button-ghost" @click="toggleSelectAll">{{ isAllSelected ? 'Desmarcar todos' : 'Selecionar todos' }}</button>
              </div>
              <div class="max-h-80 divide-y divide-[var(--ds-border)] overflow-y-auto">
                <label v-for="user in filteredUsers" :key="user.id" class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-[var(--ds-panel-subtle)]">
                  <CheckboxInput v-model="form.recipients" type="checkbox" :value="user.id" class="ds-checkbox" />
                  <span class="min-w-0 flex-1"><span class="block truncate text-sm font-bold text-[var(--ds-text)]">{{ user.name }}</span><span class="block truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ user.email }}</span></span>
                  <span class="text-xs font-bold tabular-nums text-[var(--ds-text-soft)]">{{ user.unread_count }} por ler</span>
                </label>
                <p v-if="!filteredUsers.length" class="px-4 py-8 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Nenhum utilizador corresponde a pesquisa.</p>
              </div>
              <p v-if="form.errors.recipients || (showValidation && form.recipients.length === 0)" class="ds-field-error border-t border-[var(--ds-border)] px-4 py-3">{{ form.errors.recipients || 'Selecione pelo menos um destinatario.' }}</p>
            </div>
          </div>
        </section>
      </div>

      <aside class="ds-panel overflow-hidden xl:sticky xl:top-5">
        <header class="border-b border-[var(--ds-border)] px-5 py-4">
          <p class="ds-kicker">Confirmacao</p>
          <h2 class="ds-heading mt-1 text-base">Pre-visualizacao</h2>
        </header>
        <div class="p-5">
          <div class="flex flex-wrap gap-2">
            <span class="ds-badge ring-1 ring-inset" :class="notificationTypeClasses(form.type)">{{ notificationTypeLabel(form.type) }}</span>
            <span class="ds-badge ring-1 ring-inset" :class="notificationPriorityClasses(form.priority)">{{ notificationPriorityLabel(form.priority) }}</span>
          </div>
          <h3 class="mt-4 break-words text-lg font-black text-[var(--ds-text)]">{{ form.title || 'Titulo da notificacao' }}</h3>
          <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-[var(--ds-text-muted)]">{{ form.message || 'A mensagem sera apresentada aqui enquanto escreve.' }}</p>
          <dl class="mt-6 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Emissor</dt><dd class="truncate text-sm font-bold text-[var(--ds-text)]">{{ senderAlias }}</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Alcance</dt><dd class="text-sm font-black tabular-nums text-[var(--ds-text)]">{{ estimatedRecipients }} utilizadores</dd></div>
            <div class="flex items-center justify-between gap-4 py-3"><dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Entrega</dt><dd class="flex items-center gap-1.5 text-sm font-bold text-[var(--ds-text)]"><ClockIcon class="h-4 w-4" /> Imediata</dd></div>
          </dl>

          <div v-if="showValidation && !isFormValid" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            Complete os campos obrigatorios e confirme os destinatarios antes de enviar.
          </div>
          <div v-if="form.hasErrors" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            Nao foi possivel emitir a mensagem. Reveja os campos assinalados.
          </div>
        </div>
        <footer class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
          <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing">
            <PaperAirplaneIcon v-if="!form.processing" class="h-4 w-4" />
            <span v-else class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" />
            {{ form.processing ? 'A emitir...' : `Emitir para ${estimatedRecipients}` }}
          </button>
          <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs font-semibold text-[var(--ds-text-soft)]"><CheckIcon class="h-3.5 w-3.5" /> A mensagem sera registada no historico.</p>
        </footer>
      </aside>
    </form>
  </div>
</template>
