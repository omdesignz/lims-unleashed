<script setup>
import LabForm from '@/Pages/VAPLabs/LabForm.vue'

defineProps({
  labsCount: Number,
  activeLabsCount: Number,
  supervisors: { type: Array, default: () => [] },
  technicalHeads: { type: Array, default: () => [] },
  departments: { type: Array, default: () => [] },
  lastUpdated: String,
})
</script>

<template>
  <LabForm :labs-count="labsCount" :active-labs-count="activeLabsCount" :supervisors="supervisors" :technical-heads="technicalHeads" :departments="departments" :last-updated="lastUpdated" />
</template>
