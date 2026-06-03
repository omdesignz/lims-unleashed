<template>
    <div :style="brandingCssVariables" :data-theme-preset="themePreset">
        <slot />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'

const page = usePage()
const settings = computed(() => page.props?.settings ?? {})
const brandingCssVariables = computed(() => buildBrandingCssVariables(settings.value))
const themePreset = computed(() => settings.value.theme_preset || settings.value.app_theme_preset || 'corporate')
</script>
