<template>
    <Head title="Forgot Password" />
    <div class="min-h-screen bg-white flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div>
            <!-- <img class="h-12 w-auto" src="https://tailwindui.com/img/logos/workflow-mark-indigo-600.svg" alt="Workflow" /> -->
            <!-- <logo class="fill-white" width="320" height="72" /> -->
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Deixe-nos restaurar o seu acesso
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                preenchendo o seu endereço de email
            </p>
            </div>

            <div class="mt-8">


            <div class="mt-6">
                <form @submit.prevent="form.post('/forgot-password')" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                    Endereço de Email
                    </label>
                    <div class="mt-1">
                    <input v-model="form.email" id="email" name="email" type="email" autocomplete="email" required="" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div>
                    <span class="relative z-0 inline-flex shadow-sm rounded-md w-full">
                        <Link :href="route('login')" class="relative w-1/4 inline-flex items-center justify-center px-4 py-2 rounded-l-md border border-transparent shadow-sm bg-ft-orange text-sm font-medium text-white hover:bg-ft-gray focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                            Voltar
                        </Link>
                        <button :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="submit" class="-ml-px w-3/4 relative justify-center inline-flex items-center px-4 py-2 rounded-r-md border border-transparent shadow-sm bg-ft-orange text-sm font-medium text-white hover:bg-ft-gray focus:z-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                            Enviar instruções via email
                        </button>
                    </span>
                </div>
                </form>
            </div>
            </div>
        </div>
        </div>
        <div class="hidden lg:block relative w-0 flex-1">
        <img class="absolute inset-0 h-full w-full object-cover" src="https://images.unsplash.com/photo-1505904267569-f02eaeb45a4c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1908&q=80" alt="" />
        </div>
    </div>

</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
// import Logo from '@/Shared/Logo'

defineProps({
    layout: null,
});

let form = useForm({
    email: '',
})

let submit = () => {
    form.post('/forgot-password', {
        onFinish: () => form.reset('email'),
    })
}

</script>
