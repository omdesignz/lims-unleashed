<script setup>
import { router } from '@inertiajs/vue3'
import {
  CircleCheck as CheckCircleIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Info as InformationCircleIcon,
  CircleX as XCircleIcon,
  X as XMarkIcon,
} from '@lucide/vue'
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
  success: { icon: CheckCircleIcon, tone: 'var(--pl-ok)', label: 'Concluído' },
  error: { icon: XCircleIcon, tone: 'var(--pl-bad)', label: 'Atenção' },
  warning: { icon: ExclamationTriangleIcon, tone: 'var(--pl-warn)', label: 'Aviso' },
  info: { icon: InformationCircleIcon, tone: 'var(--pl-accent-text)', label: 'Informação' },
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
    class="pl-toast pointer-events-auto relative"
    :style="{ '--c': configuration.tone }"
    :role="item.variant === 'error' || item.priority === 'urgent' ? 'alert' : 'status'"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
    @focusin="paused = true"
    @focusout="paused = false"
  >
    <div class="flex items-start gap-3 py-3 pl-3.5 pr-2.5">
      <component :is="configuration.icon" class="mt-0.5 h-4 w-4 shrink-0 text-[var(--c)]" aria-hidden="true" />

      <div class="min-w-0 flex-1">
        <p class="pl-k mb-1.5 text-[var(--c)]" aria-hidden="true">{{ configuration.label }}</p>
        <p class="text-sm font-semibold leading-5 text-[var(--pl-fg)]">
          <span class="sr-only">{{ configuration.label }}: </span>{{ item.title }}
          <span v-if="item.priority === 'high' || item.priority === 'urgent'" class="ds-badge ds-badge-danger ml-1.5 align-middle">
            {{ item.priority === 'urgent' ? 'Urgente' : 'Prioridade alta' }}
          </span>
        </p>
        <p v-if="item.message" class="mt-0.5 text-[13px] leading-5 text-[var(--pl-muted)]">{{ item.message }}</p>

        <button v-if="actionLabel && actionUrl" type="button" class="pl-k pl-acc mt-2.5" @click="followAction">
          {{ actionLabel }} →
        </button>
      </div>

      <button type="button" class="ds-icon-button h-7 w-7 shrink-0" title="Fechar notificação" @click="emit('remove')">
        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
      </button>
    </div>

    <div v-if="!isPersistent" class="h-0.5 bg-[var(--pl-raised)]">
      <div class="h-full bg-[var(--c)] opacity-60 transition-[width] duration-100 ease-linear" :style="{ width: `${progress}%` }" />
    </div>
  </article>
</template>
