<template>
    <div class="md:grid md:grid-cols-1 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="ds-card px-4 py-5 sm:p-6">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>

<script setup>
import SectionTitle from './section-title.vue';

</script>

<style scoped>

</style>
