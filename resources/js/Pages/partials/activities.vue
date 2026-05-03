<template>
    <!-- Recent Activities -->
    <section aria-labelledby="recent-hires-title">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <h2 class="text-base font-medium text-gray-900" id="recent-hires-title">Recent Hires</h2>
                <div class="mt-6 flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                        <li v-for="person in recentHires" :key="person.handle" class="py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <img class="h-8 w-8 rounded-full" :src="person.imageUrl" alt="" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ person.name }}</p>
                                    <p class="truncate text-sm text-gray-500">{{ '@' + person.handle }}</p>
                                </div>
                                <div>
                                    <a :href="person.href" class="inline-flex items-center rounded-full border border-gray-300 bg-white px-2.5 py-0.5 text-sm font-medium leading-5 text-gray-700 shadow-sm hover:bg-gray-50">View</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a href="#" class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">View all</a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>

const recentHires = [
    {
        name: 'Leonard Krasner',
        handle: 'leonardkrasner',
        imageUrl:
            'https://images.unsplash.com/photo-1519345182560-3f2917c472ef?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
        href: '#',
    },
    {
        name: 'Floyd Miles',
        handle: 'floydmiles',
        imageUrl:
            'https://images.unsplash.com/photo-1463453091185-61582044d556?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
        href: '#',
    },
    {
        name: 'Emily Selman',
        handle: 'emilyselman',
        imageUrl:
            'https://images.unsplash.com/photo-1502685104226-ee32379fefbe?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
        href: '#',
    },
    {
        name: 'Kristin Watson',
        handle: 'kristinwatson',
        imageUrl:
            'https://images.unsplash.com/photo-1500917293891-ef795e70e1f6?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
        href: '#',
    },
]
</script>

<style scoped>

</style>
