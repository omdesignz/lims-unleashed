<template>
    <div :class="commercialDocumentThemeClasses">Hi</div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import FilemanagerLayout from "@/Shared/Layouts/FilemanagerLayout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineOptions({
  layout: [Layout, FilemanagerLayout]
});

</script>
