<template>
  <InventoryOrderFormSurface
    mode="edit"
    :order="order"
    :items="items"
    :suppliers="suppliers"
    :warehouses="warehouses"
  />
</template>

<script setup>
import InventoryOrderFormSurface from '@/Components/vap-inventory/InventoryOrderFormSurface.vue'

defineProps({
  order: {
    type: Object,
    required: true,
  },
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
