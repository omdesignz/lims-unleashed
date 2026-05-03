<template>
    <div>
        <h3>{{ title }}</h3>
        <pre>{{ valueString }}</pre>
    </div>
</template>

<script setup>

import {computed} from "vue";

const props = defineProps({
    title: {
        required: true,
        type: String
    },
    value: {
        required: true
    }
});

const valueString = computed(()=> JSON.stringify(props.value, null, 2));


</script>
<style scoped>
pre {
    text-align: start;
}
</style>
