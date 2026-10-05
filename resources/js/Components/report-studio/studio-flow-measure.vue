<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { collectFlowBlocks, paginateFlow } from '@/Support/report-studio-pagination.mjs'

/**
 * Lays the document out once, unseen, inside the page frame it sits in, and
 * reports where its body breaks across pages. The skeleton repeats the spacing
 * of the page surfaces on the canvas, so the room it measures is the room the
 * visible pages have.
 */
const props = defineProps({
  segments: { type: Array, default: () => [] },
  firstPageStyle: { type: Object, default: () => ({}) },
  nextPageStyle: { type: Object, default: () => ({}) },
  firstHeaderHtml: { type: String, default: '' },
  nextHeaderHtml: { type: String, default: '' },
  footerHtml: { type: String, default: '' },
})

// The least height the canvas gives the header and footer surfaces of a page.
const headerSurfaceStyle = { minHeight: '104px' }
const footerSurfaceStyle = { minHeight: '96px' }

// Page starts for each segment, or null while the frame is not on screen.
const emit = defineEmits(['measured'])

const root = ref(null)
let resizeObserver = null
let pendingFrame = null

function roomOf(surface) {
  const style = window.getComputedStyle(surface)

  return surface.clientHeight - parseFloat(style.paddingTop || 0) - parseFloat(style.paddingBottom || 0)
}

function measure() {
  pendingFrame = null

  const element = root.value

  if (!element) {
    return
  }

  if (element.clientWidth === 0 || element.clientHeight === 0) {
    emit('measured', null)

    return
  }

  const [firstSurface, nextSurface] = element.querySelectorAll('[data-flow-room]')

  if (!firstSurface || !nextSurface) {
    return
  }

  const firstRoom = roomOf(firstSurface)
  const nextRoom = roomOf(nextSurface)

  emit('measured', Array.from(element.querySelectorAll('[data-flow-segment]')).map((segment, index) => {
    return paginateFlow(collectFlowBlocks(segment), segment.scrollHeight, index === 0 ? firstRoom : nextRoom, nextRoom)
      .map((start) => Math.round(start * 100) / 100)
  }))
}

function scheduleMeasure() {
  if (pendingFrame !== null || typeof window === 'undefined') {
    return
  }

  pendingFrame = window.requestAnimationFrame(measure)
}

watch(() => [
  props.segments,
  props.firstPageStyle,
  props.nextPageStyle,
  props.firstHeaderHtml,
  props.nextHeaderHtml,
  props.footerHtml,
], () => nextTick(scheduleMeasure), { deep: true })

onMounted(() => {
  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(scheduleMeasure)
    resizeObserver.observe(root.value)
  }

  // Text measured before its font arrives would break in the wrong places.
  document.fonts?.ready?.then(scheduleMeasure)
  scheduleMeasure()
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()

  if (pendingFrame !== null) {
    window.cancelAnimationFrame(pendingFrame)
  }
})
</script>

<template>
  <div ref="root" class="pointer-events-none invisible absolute inset-0 overflow-hidden" aria-hidden="true" data-flow-measure>
    <div class="absolute flex flex-col gap-5" :style="firstPageStyle">
      <div class="border border-dashed p-5" :style="headerSurfaceStyle" v-html="firstHeaderHtml" />
      <div data-flow-room class="min-h-0 flex-1 overflow-hidden border p-6 text-sm xl:p-7">
        <div
          v-for="(segment, index) in segments"
          :key="index"
          data-flow-segment
          class="studio-preview-body"
          v-html="segment"
        />
      </div>
      <div class="border border-dashed p-5 text-xs" :style="footerSurfaceStyle" v-html="footerHtml" />
    </div>
    <div class="absolute flex flex-col gap-5" :style="nextPageStyle">
      <div class="border border-dashed p-5" :style="headerSurfaceStyle" v-html="nextHeaderHtml" />
      <div data-flow-room class="min-h-0 flex-1 overflow-hidden border p-6 text-sm xl:p-7" />
      <div class="border border-dashed p-5 text-xs" :style="footerSurfaceStyle" v-html="footerHtml" />
    </div>
  </div>
</template>
