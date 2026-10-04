<template>
  <table ref="table" class="ds-data-table" :data-stack="stackable || undefined">
    <slot />
  </table>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { labelStackedCells } from '@/Support/stackedTable'

/**
 * On phones each row becomes a stacked record: the first cell is its title, the
 * other cells carry their column name as a label. Matrices of readings keep their
 * grid (and scroll sideways) with `:stack="false"`; tables whose header spans
 * rows or columns keep it automatically.
 */
const props = defineProps({
  stack: { type: Boolean, default: true },
})

const table = ref(null)
const stackable = ref(false)
let observer = null

function relabel() {
  stackable.value = props.stack && labelStackedCells(table.value)
}

onMounted(() => {
  relabel()

  if (props.stack && typeof MutationObserver !== 'undefined' && table.value) {
    observer = new MutationObserver(relabel)
    observer.observe(table.value, { childList: true, subtree: true })
  }
})

onBeforeUnmount(() => observer?.disconnect())
</script>
