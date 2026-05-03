<template>
    <!-- Welcome User panel -->
    <section aria-labelledby="profile-overview-title">
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-900 shadow mb-3">
            <h2 class="sr-only" id="profile-overview-title"></h2>
            <div class="bg-white dark:bg-gray-900 p-6">
                <div class="sm:flex sm:items-center sm:justify-between">
                    <div class="sm:flex sm:space-x-5">
                        <div class="flex items-center justify-center">
                            <img class="mx-auto h-20 w-20 rounded-full" :src="auth?.profile_photo_url" alt="" v-if="auth?.profile_photo_url" />
                            <span class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-blue-900 dark:bg-gray-800 sm:h-20 sm:w-20 ring-4 ring-white" v-else>
                                <span class="text-xl font-medium leading-none text-white">{{ auth?.name?.charAt(0) }}</span>
                            </span>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:pt-1 sm:text-left">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-400">{{ $t('gestlab.greeting') }},</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-400 sm:text-2xl">{{ auth?.name }}</p>
                            <p class="text-sm font-medium text-gray-600"></p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-center sm:mt-0">
                        <!-- <a href="#" class="flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-900 shadow-sm hover:bg-gray-50">View profile</a> -->
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 divide-y divide-gray-200 border-t border-gray-200 dark:border-gray-400 bg-gray-50 dark:bg-gray-900 sm:grid-cols-3 sm:divide-y-0 sm:divide-x">
                <!-- <div v-for="stat in stats" :key="stat.label" class="px-6 py-5 text-center text-sm font-medium">
                    <span class="text-gray-900">{{ ' ' }}</span>
                    {{ ' ' }}
                    <span class="text-gray-600">{{ ' ' }}</span>
                </div> -->
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
  greeting: {
    type: String,
    default: 'Bem-vindo(a),'
  },  
  auth: Object
});
</script>

<style scoped>

</style>
