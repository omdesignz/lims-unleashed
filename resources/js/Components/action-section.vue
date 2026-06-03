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
            <div class="border border-[#ded3bf] bg-[#fffdf7] px-4 py-5 shadow-[0_20px_60px_rgb(20_61_55/0.08)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:rounded-[1.75rem] sm:p-6">
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
