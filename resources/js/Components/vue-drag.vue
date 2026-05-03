<script setup>
import { ref } from "vue";
import {VueDraggableNext} from "vue-draggable-next"


const dataList = ref({
    draggable: [
        [
            {
                id: 1,
                content: "draggableText1",
            },
        ],
        [
            {
                id: 2,
                content: "draggableText2",
            },
        ],
        [
            {
                id: 3,
                content: "draggableText3",
            },
        ],
    ],
    unDraggable: [
        {
            id: 1,
            content: "unDraggableText1",
        },
        {
            id: 2,
            content: "unDraggableText2",
        },
        {
            id: 3,
            content: "unDraggableText3",
        },
    ],
});
</script>

<template>
    <div v-for="(draggableData, index) in dataList.draggable" class="hello">
        <VueDraggableNext :list="draggableData" group="test" :key="draggableData" item-key="test">
            <template #item="{ element }">
                <div class="drag-item">{{ element.id }}: {{ element.content }}</div>
            </template>
        </VueDraggableNext>
        {{ dataList.unDraggable[index].content }}
    </div>
</template>

<style scoped>
.drag-item {
    background: paleturquoise;
    margin: 10px 0;
}
</style>
