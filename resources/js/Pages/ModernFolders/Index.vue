<template>
    <div>Hi</div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import FilemanagerLayout from "@/Shared/Layouts/FilemanagerLayout.vue";

defineOptions({
  layout: [Layout, FilemanagerLayout]
});

</script>