<script setup>
const props = defineProps({
    data: Array
});
</script>

<template>
    <div class="mt-2 overflow-hidden rounded-[1.75rem] border border-[#25443c]/30 bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-300-rgb),0.22),transparent_34%),linear-gradient(135deg,#143d37_0%,#07110f_68%,#1d170f_100%)] shadow-[0_24px_80px_rgb(7_17_15/0.22)] ring-1 ring-white/10">
        <div class="grid grid-cols-1 divide-y divide-white/10 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
            <div v-for="stat in props.data" :key="stat.name" class="px-5 py-6 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#d7e2dd]">{{ $t(stat.name) }}</p>
            <p class="mt-3 flex items-baseline gap-x-2">
                <span class="text-4xl font-semibold tracking-tight text-white">{{ stat.value }}</span>
                <span v-if="stat.unit" class="text-sm font-semibold text-[rgb(var(--primary-100-rgb))]">{{ stat.unit }}</span>
            </p>
            </div>
        </div>
    </div>
</template>
