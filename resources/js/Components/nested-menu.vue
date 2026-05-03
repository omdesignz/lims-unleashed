<template>
    <div className="row">
        <div className="col-8">
            <h3>Nested draggable</h3>
            <nested-draggable :tasks="list"/>
        </div>

        <raw-data-container class="col-3" :value="list" title="List" />
    </div>
</template>

<script setup>
import {ref} from "vue";
import NestedDraggable from "../Components/nested-menu-structure.vue";
import RawDataContainer from "./raw-data-container.vue";

defineProps({

});

const list = ref([
    {
        name: "task 1",
        tasks: [
            {
                name: "task 2",
                tasks: [
                    {
                        name: 'task 2.1',

                        tasks: [
                            {
                                name: 'task 2.1.1',
                                tasks: []
                            }
                        ]
                    }
                ]
            }
        ]
    },
    {
        name: "task 3",
        tasks: [
            {
                name: "task 4",
                tasks: []
            }
        ]
    },
    {
        name: "task 5",
        tasks: []
    }
]);
</script>

<style scoped></style>
