const typeAliases = {
  success: 'success',
  completed: 'success',
  approved: 'success',
  error: 'error',
  failed: 'error',
  rejected: 'error',
  warning: 'warning',
  alert: 'alert',
  urgent: 'alert',
  info: 'info',
}

export const normalizeNotificationType = (value) => {
  const normalized = String(value || 'info').toLowerCase()
  const alias = Object.keys(typeAliases).find((key) => normalized.includes(key))

  return alias ? typeAliases[alias] : 'info'
}

export const notificationTypeLabel = (value) => ({
  success: 'Sucesso',
  error: 'Erro',
  warning: 'Aviso',
  alert: 'Alerta',
  info: 'Informação',
}[normalizeNotificationType(value)])

export const notificationTypeClasses = (value) => ({
  success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
  error: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
  warning: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
  alert: 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-300',
  info: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
}[normalizeNotificationType(value)])

export const notificationIndicatorClasses = (value) => ({
  success: 'bg-emerald-500',
  error: 'bg-rose-500',
  warning: 'bg-amber-500',
  alert: 'bg-orange-500',
  info: 'bg-sky-500',
}[normalizeNotificationType(value)])

export const notificationPriorityLabel = (value) => ({
  low: 'Baixa',
  normal: 'Normal',
  high: 'Alta',
  urgent: 'Urgente',
}[value] || 'Normal')

export const notificationPriorityClasses = (value) => ({
  low: 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)] ring-[var(--ds-border-strong)]',
  normal: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
  high: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
  urgent: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
}[value] || 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300')

export const formatNotificationDate = (value, withTime = true) => {
  if (!value) {
    return 'Nao registado'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
    ...(withTime ? { timeStyle: 'short' } : {}),
  }).format(new Date(value))
}
