<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RevisionIndex from "./Index.vue";

defineOptions({
  layout: Layout,
});

defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  revisions: {
    type: Object,
    default: () => ({ data: [], links: [], total: 0 }),
  },
  currentRevision: {
    type: Object,
    default: null,
  },
  activityLogs: {
    type: Array,
    default: () => [],
  },
  complianceStats: {
    type: Object,
    default: () => ({}),
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <RevisionIndex
    :certificate="certificate"
    :revisions="revisions"
    :current-revision="currentRevision"
    :activity-logs="activityLogs"
    :compliance-stats="complianceStats"
    :approvers="approvers"
  />
</template>
