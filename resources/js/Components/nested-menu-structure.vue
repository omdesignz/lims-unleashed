<template>


        <VueDraggableNext
            class="py-4 px-2 rounded-md bg-white overflow-hidden"
            role="list"
            tag="ul"
            :move="onMove"
            :list="items"
            :group="{ name: 'items' }"
            item-key="title"
        >
            <template #item="{ element, index }">
                
                <li class="">
                    <a href="#" class="bg-gradient-to-r from-sky-800 to-cyan-600 text-white group w-full flex items-center pl-2 py-2 text-sm font-medium rounded-r-3xl">
                        <component v-if="element.icon" :is="element.icon" class="text-white mr-3 flex-shrink-0 h-6 w-6" aria-hidden="true" />
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-gray-300 ml-3 h-5 w-5 flex-shrink-0 transform transition-colors duration-150 ease-in-out group-hover:text-white group-hover:transform group-hover:transition-all group-hover:duration-200 group-hover:scale-125">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 4.5l7.5 7.5-7.5 7.5m-6-15l7.5 7.5-7.5 7.5" />
                        </svg>
                        {{ element.title }} {{ index }}
                    </a>
                    <nested-menu-structure :items="element.children" class="px-6 py-4" />
                </li>
            </template>
        </VueDraggableNext>




</template>
<script setup>
import {VueDraggableNext} from "vue-draggable-next"
import NestedMenuStructure from "./nested-menu-structure.vue";
import RawDataContainer from "./raw-data-container.vue";
import MenuItem from "./menu-item.vue";

const props = defineProps({
    items: {
        required: true,
        type: Array
    }
});

function onMove(e) {
    console.log('Previous Index: ' + e.draggedContext.index);
    console.log('Current Index: ' + e.draggedContext.futureIndex);

    console.log(e);
}



function onEnd(e) {
    console.log(e);
}

function onDragEnd(e) {
    console.log(e.draggedContext);
}

</script>
<style scoped>
.dragArea {
    min-height: 50px;
    outline: 1px dashed;
}
</style>
