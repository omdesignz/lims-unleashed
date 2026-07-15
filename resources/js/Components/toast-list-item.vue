<script setup>
import { router } from '@inertiajs/vue3'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  item: { type: Object, required: true },
})

const emit = defineEmits(['remove'])
const paused = ref(false)
const progress = ref(100)
let timer = null
let lastTick = null
let remaining = props.item.duration

const configurations = {
  success: { icon: CheckCircleIcon, rail: 'bg-emerald-500', iconClass: 'text-emerald-600 dark:text-emerald-300', label: 'Concluído' },
  error: { icon: XCircleIcon, rail: 'bg-rose-500', iconClass: 'text-rose-600 dark:text-rose-300', label: 'Atenção' },
  warning: { icon: ExclamationTriangleIcon, rail: 'bg-amber-500', iconClass: 'text-amber-600 dark:text-amber-300', label: 'Aviso' },
  info: { icon: InformationCircleIcon, rail: 'bg-sky-500', iconClass: 'text-sky-600 dark:text-sky-300', label: 'Informação' },
}

const configuration = computed(() => configurations[props.item.variant] || configurations.info)
const isPersistent = computed(() => !props.item.duration || props.item.priority === 'urgent')
const actionLabel = computed(() => props.item.action_label || props.item.actionLabel)
const actionUrl = computed(() => props.item.action_url || props.item.actionUrl)

const tick = () => {
  if (paused.value || isPersistent.value) {
    lastTick = Date.now()
    return
  }

  const now = Date.now()
  remaining -= now - lastTick
  lastTick = now
  progress.value = Math.max(0, (remaining / props.item.duration) * 100)

  if (remaining <= 0) emit('remove')
}

onMounted(() => {
  if (isPersistent.value) return
  lastTick = Date.now()
  timer = window.setInterval(tick, 100)
})

onBeforeUnmount(() => {
  if (timer) window.clearInterval(timer)
})

const followAction = () => {
  if (!actionUrl.value) return
  emit('remove')
  router.visit(actionUrl.value)
}
</script>

<template>
  <article
    class="pointer-events-auto relative overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] shadow-[var(--ds-shadow-card)]"
    :role="item.variant === 'error' || item.priority === 'urgent' ? 'alert' : 'status'"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <span class="absolute inset-y-0 left-0 w-1" :class="configuration.rail" />
    <div class="flex items-start gap-3 px-4 py-3.5 pl-5">
      <component :is="configuration.icon" class="mt-0.5 h-5 w-5 shrink-0" :class="configuration.iconClass" aria-hidden="true" />

      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <span class="text-[0.64rem] font-black uppercase text-[var(--ds-text-soft)]">{{ configuration.label }}</span>
          <span v-if="item.priority === 'high' || item.priority === 'urgent'" class="rounded bg-rose-50 px-1.5 py-0.5 text-[0.62rem] font-black uppercase text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
            {{ item.priority === 'urgent' ? 'Urgente' : 'Prioridade alta' }}
          </span>
        </div>
        <p class="mt-1 text-sm font-bold leading-5 text-[var(--ds-text)]">{{ item.title }}</p>
        <p class="mt-0.5 text-sm font-medium leading-5 text-[var(--ds-text-muted)]">{{ item.message }}</p>

        <button v-if="actionLabel && actionUrl" type="button" class="mt-2 text-xs font-black text-[rgb(var(--primary-700-rgb))] hover:underline dark:text-cyan-200" @click="followAction">
          {{ actionLabel }}
        </button>
      </div>

      <button type="button" class="ds-icon-button -mr-1 -mt-1 h-8 w-8 shrink-0" title="Fechar notificação" @click="emit('remove')">
        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
      </button>
    </div>

    <div v-if="!isPersistent" class="h-0.5 bg-[var(--ds-panel-subtle)]">
      <div class="h-full transition-[width] duration-100 ease-linear" :class="configuration.rail" :style="{ width: `${progress}%` }" />
    </div>
  </article>
</template>
