<template>
    <div>
        <transition leave-active-class="transition ease-in duration-1000" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-show="on" class="text-sm font-semibold text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">
                <slot />
            </div>
        </transition>
    </div>
</template>

<script setup>
defineProps({
    on: Boolean,
});
</script>
