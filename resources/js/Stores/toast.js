import { reactive } from 'vue'

let nextToastId = 1

const severityFrom = (toast) => {
  const value = String(toast?.variant || toast?.type || toast?.priority || 'info').toLowerCase()

  if (value.includes('urgent') || value.includes('error') || value.includes('failed')) return 'error'
  if (value.includes('high') || value.includes('warning') || value.includes('alert')) return 'warning'
  if (value.includes('success') || value.includes('approved') || value.includes('completed')) return 'success'

  return 'info'
}

const defaultDuration = (variant, priority) => {
  if (priority === 'urgent') return 0
  if (variant === 'error') return 10000
  if (variant === 'warning') return 8000
  return 6000
}

export default reactive({
  items: [],

  add(input = {}) {
    const variant = severityFrom(input)
    const priority = input.priority || 'normal'
    const fingerprint = input.dedupeKey || [input.key, input.title, input.message, input.action_url].filter(Boolean).join('|')
    const existing = fingerprint ? this.items.find((item) => item.fingerprint === fingerprint) : null
    const normalized = {
      ...input,
      variant,
      priority,
      title: input.title || (variant === 'error' ? 'Atenção necessária' : 'Actualização operacional'),
      message: input.message || '',
      duration: input.duration ?? defaultDuration(variant, priority),
      fingerprint,
      receivedAt: input.receivedAt || new Date().toISOString(),
    }

    if (existing) {
      Object.assign(existing, normalized, { generation: existing.generation + 1 })
      return existing.id
    }

    const id = nextToastId++
    this.items.unshift({ id, generation: 0, ...normalized })
    this.items.splice(5)

    return id
  },

  remove(id) {
    const index = this.items.findIndex((item) => item.id === id)
    if (index >= 0) this.items.splice(index, 1)
  },

  clear() {
    this.items.splice(0)
  },
})
