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
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="ds-card px-4 py-5 sm:p-6"
                    :class="hasActions ? 'sm:rounded-t-[1.75rem] sm:rounded-b-none' : 'sm:rounded-[1.75rem]'"
                >
                    <div class="grid grid-cols-1 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex items-center justify-end border-x border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-4 text-right shadow-[var(--ds-shadow-card)] sm:rounded-b-[1.75rem] sm:px-6">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './section-title.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);

</script>

<style scoped>

</style>
