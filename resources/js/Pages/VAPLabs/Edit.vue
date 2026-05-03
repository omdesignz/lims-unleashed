<template>
  <LabForm 
    :lab="lab"
    :labs-count="labsCount"
    :active-labs-count="activeLabsCount"
    :supervisors="supervisors"
    :technical-heads="technicalHeads"
    :departments="departments"
    :last-updated="lastUpdated"
    :is-edit="true"
  />
</template>

<script setup>
import LabForm from '@/Pages/VAPLabs/LabForm.vue'

const props = defineProps({
  lab: Object,
  labsCount: Number,
  activeLabsCount: Number,
  supervisors: Array,
  technicalHeads: Array,
  departments: Array,
  lastUpdated: String,
})
</script>