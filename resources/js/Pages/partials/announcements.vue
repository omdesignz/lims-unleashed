<template>
    <!-- Announcements -->
    <section aria-labelledby="announcements-title">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="p-6">
                <h2 class="text-base font-medium text-gray-900" id="announcements-title">Comunicados</h2>
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
                    <a href="#" class="flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Ver tudo</a>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const announcements = [
    {
        id: 1,
        title: 'Serviços encerrados no dia 2 de Julho',
        href: '#',
        preview: 'Os serviços estarão encerrados nesta data. Consulte o comunicado para conhecer os contactos disponíveis durante o período.',
    },
    {
        id: 2,
        title: 'Nova política de palavras-passe',
        href: '#',
        preview: 'A política de acesso foi actualizada para reforçar a protecção das contas e dos registos laboratoriais.',
    },
    {
        id: 3,
        title: 'Manutenção programada do sistema',
        href: '#',
        preview: 'O sistema terá uma intervenção programada. Os detalhes e a janela prevista constam do comunicado.',
    },
]
</script>

<style scoped>

</style>
