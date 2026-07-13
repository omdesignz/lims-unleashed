<template>
  <InventoryOrderFormSurface
    mode="create"
    :items="items"
    :suppliers="suppliers"
    :warehouses="warehouses"
  />
</template>

<script setup>
import InventoryOrderFormSurface from '@/Components/vap-inventory/InventoryOrderFormSurface.vue'

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
})
</script>
