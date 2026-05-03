<template>
    <Head title="Password Confirmation" />
    <div class="min-h-screen bg-white flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div>
            <!-- <img class="h-12 w-auto" src="https://tailwindui.com/img/logos/workflow-mark-indigo-600.svg" alt="Workflow" /> -->
            <logo class="fill-white" width="320" height="72" />
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Secure Area
            </h2>
            <p class="mt-2 text-sm text-gray-600">
               Please confirm your password before continuing.
            </p>
            </div>

            <div class="mt-8">

            <div class="mt-6">
                <form @submit.prevent="submit" class="space-y-6">

                <div class="space-y-1">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                    Password
                    </label>
                    <div class="mt-1">
                    <input v-model="form.password" id="password" name="password" type="password" autocomplete="current-password" required="" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    </div>
                </div>


                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                    Confirm
                    </button>
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
<script>
import EmptyLayout from "../../Shared/EmptyLayout.vue";

export default {
    layout: EmptyLayout
}
</script>
<script setup>
import { useForm } from '@inertiajs/vue3';
import Logo from '../../Shared/logo.vue'


defineProps({
    layout: null,
});

let form = useForm({
    password: '',
})

let submit = () => {
    form.post('/user/confirm-password ', {
        onFinish: () => form.reset(),
    })
}

</script>
