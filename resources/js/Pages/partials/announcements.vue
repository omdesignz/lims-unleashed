<template>
    <!-- Announcements -->
    <section aria-labelledby="announcements-title">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <h2 class="text-base font-medium text-gray-900" id="announcements-title">Announcements</h2>
                <div class="mt-6 flow-root">
                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                        <li v-for="announcement in announcements" :key="announcement.id" class="py-5">
                            <div class="relative focus-within:ring-2 focus-within:ring-cyan-500">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    <a :href="announcement.href" class="hover:underline focus:outline-none">
                                        <!-- Extend touch target to entire panel -->
                                        <span class="absolute inset-0" aria-hidden="true" />
                                        {{ announcement.title }}
                                    </a>
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ announcement.preview }}</p>
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
const announcements = [
    {
        id: 1,
        title: 'Office closed on July 2nd',
        href: '#',
        preview:
            'Cum qui rem deleniti. Suscipit in dolor veritatis sequi aut. Vero ut earum quis deleniti. Ut a sunt eum cum ut repudiandae possimus. Nihil ex tempora neque cum consectetur dolores.',
    },
    {
        id: 2,
        title: 'New password policy',
        href: '#',
        preview:
            'Alias inventore ut autem optio voluptas et repellendus. Facere totam quaerat quam quo laudantium cumque eaque excepturi vel. Accusamus maxime ipsam reprehenderit rerum id repellendus rerum. Culpa cum vel natus. Est sit autem mollitia.',
    },
    {
        id: 3,
        title: 'Office closed on July 2nd',
        href: '#',
        preview:
            'Tenetur libero voluptatem rerum occaecati qui est molestiae exercitationem. Voluptate quisquam iure assumenda consequatur ex et recusandae. Alias consectetur voluptatibus. Accusamus a ab dicta et. Consequatur quis dignissimos voluptatem nisi.',
    },
]
</script>

<style scoped>

</style>
