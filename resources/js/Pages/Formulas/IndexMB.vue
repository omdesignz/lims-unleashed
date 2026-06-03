<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";

import { ref, computed, watch, reactive } from "vue";
import debounce from 'lodash/debounce'
import { useForm, router } from "@inertiajs/vue3";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { usePermission } from '@/Composables/usePermissions'
import UFCCalculation from '@/Components/ufc-calculation.vue';
import standardDeviationCalculator from '@/Components/standard-deviation-calculator.vue';
import technicalUncertaintyCalculator from '@/Components/technical-uncertainty-calculator.vue';
import matrixUncertaintyCalculator from '@/Components/matrix-uncertainty-calculator.vue';
import confirmationUncertaintyCalculator from '@/Components/confirmation-uncertainty-calculator.vue';
import distributionalUncertaintyCalculator from '@/Components/distributional-uncertainty-calculator.vue';
import environmentalUncertaintyCalculator from '@/Components/environmental-uncertainty-calculator.vue';
import comprehensiveUncertaintyCalculator from '@/Components/comprehensive-uncertainty-calculator.vue';
import dynamicSeriesUncertaintyCalculator from '@/Components/dynamic-series-uncertainty-calculator.vue';
import dynamicSeriesAutoUncertaintyCalculator from '@/Components/dynamic-series-auto-uncertainty-calculator.vue';
import dynamicSeriesPoissonUncertaintyCalculator from '@/Components/dynamic-series-poisson-uncertainty-calculator.vue';
import waterUncertaintyCalculator from '@/Components/water-uncertainty-calculator.vue';
import confidenceInterval from '@/Components/confidence-interval.vue';


const { hasRole, hasPermission } = usePermission();

const props = defineProps({
    
});

defineOptions({
  layout: Layout
});




</script>
<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <UFCCalculation />
        <!-- <standardDeviationCalculator /> -->
        <!-- <technicalUncertaintyCalculator /> -->
        <!-- <matrixUncertaintyCalculator /> -->
        <!-- <confirmationUncertaintyCalculator/> -->
        <!-- <distributionalUncertaintyCalculator /> -->
        <!-- <environmentalUncertaintyCalculator /> -->
         <!-- <comprehensiveUncertaintyCalculator /> -->
         <!-- <dynamicSeriesUncertaintyCalculator /> -->
         <!-- <dynamicSeriesAutoUncertaintyCalculator /> -->
         <!-- <dynamicSeriesPoissonUncertaintyCalculator /> -->
         <hr> <br>
         
    <waterUncertaintyCalculator />

    <hr> <br>
    <confidenceInterval />
    
</div>
</template>
