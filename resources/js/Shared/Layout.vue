<template>
  <AppLayout :auth="page.props.auth" :impersonation="page.props.impersonation">
    <slot />
  </AppLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Shared/Layouts/Layout.vue'

const page = usePage()
</script>
