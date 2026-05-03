<template>
    <div class="flex flex-col">
        <Link :href="route('modern-folders.show', folder.slug)" class="text-sm font-medium leading-6 text-gray-900">
            {{ folder.name }}
        </Link>

        <div v-if="folder.children" class="pl-6">
            <folderItem v-for="child in folder.children" :key="child.id" :folder="child" />
        </div>
    </div>
</template>

<script setup>
import folderItem from '@/Pages/ModernFolders/modern-folder-item.vue';
const props = defineProps({
    folder: Object,
});
</script>