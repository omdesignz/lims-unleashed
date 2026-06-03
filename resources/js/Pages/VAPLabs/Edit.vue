<template>
  <div :class="commercialDocumentThemeClasses">
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
  </div>
</template>

<script setup>
import LabForm from '@/Pages/VAPLabs/LabForm.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

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
