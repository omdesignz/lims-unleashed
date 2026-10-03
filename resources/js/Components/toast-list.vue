<script setup>
import ToastListItem from '@/Components/toast-list-item.vue'
import toast from '@/Stores/toast'
import { router, usePage } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

const page = usePage()

const enqueueFlash = () => {
  if (!page.props.toast) return

  toast.add({
    ...page.props.toast,
    dedupeKey: page.props.toast.dedupeKey || `flash:${page.url}:${page.props.toast.title || ''}:${page.props.toast.message || ''}`,
  })
}

const removeFinishEventListener = router.on('finish', enqueueFlash)

onMounted(enqueueFlash)
onUnmounted(removeFinishEventListener)
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 top-0 z-[90] flex justify-end p-3 sm:p-5" aria-live="polite" aria-atomic="false">
    <div class="flex w-full max-w-[26rem] flex-col items-stretch gap-2">
      <TransitionGroup
        enter-from-class="translate-y-[-0.5rem] opacity-0"
        enter-active-class="transition-[opacity,translate] duration-200 ease-[cubic-bezier(0.23,1,0.32,1)]"
        leave-active-class="transition-opacity duration-150 ease-out"
        leave-to-class="opacity-0"
        move-class="transition-transform duration-200 ease-[cubic-bezier(0.23,1,0.32,1)]"
      >
        <ToastListItem
          v-for="item in toast.items"
          :key="`${item.id}:${item.generation}`"
          :item="item"
          @remove="toast.remove(item.id)"
        />
      </TransitionGroup>
    </div>
  </div>
</template>
