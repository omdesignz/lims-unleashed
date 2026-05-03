<template>
    <Head title="Two-factor Confirmation" />
    <div class="min-h-screen bg-white flex">
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div>
            <!-- <img class="h-12 w-auto" src="https://tailwindui.com/img/logos/workflow-mark-indigo-600.svg" alt="Workflow" /> -->
            <logo class="fill-white" width="320" height="72" />
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                Autenticação de dois fatores
            </h2>
            <p class="mt-2 text-sm text-gray-600" v-if="! recovery">
               Confirme o acesso à sua conta inserindo o código de autenticação fornecido pelo seu aplicativo autenticador.
            </p>

            <p class="mt-2 text-sm text-gray-600" v-else>
               Confirme o acesso à sua conta inserindo um dos seus códigos de recuperação de emergência.
            </p>
            </div>

            <div class="mt-8">

            <div class="mt-6">
                <form @submit.prevent="submit" class="space-y-6">

                <div class="space-y-1" v-if="! recovery">
                    <label for="code" class="block text-sm font-medium text-gray-700">
                    Código
                    </label>
                    <div class="mt-1">
                    <input ref="code" v-model="form.code" id="code" name="code" type="text" autocomplete="one-time-code" required="" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    </div>
                </div>

                <div class="space-y-1" v-else>
                    <label for="recovery_code" class="block text-sm font-medium text-gray-700">
                    Código de Recuperação
                    </label>
                    <div class="mt-1">
                    <input ref="recovery_code" v-model="form.recovery_code" id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" required="" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-ft-orange focus:border-ft-orange sm:text-sm" />
                    </div>
                </div>


                <div>
                    <span class="relative z-0 inline-flex shadow-sm rounded-md w-full">
                    <button type="button" @click.prevent="toggleRecovery" class="w-3/4 flex justify-center py-2 px-4 border border-transparent rounded-l-md shadow-sm text-sm font-medium text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                    <template v-if="! recovery">
                        Usar código de recuperação
                    </template>

                    <template v-else>
                        Usar código de autenticação
                    </template>
                    </button>

                    <button type="submit" class="w-1/4 flex justify-center py-2 px-4 border border-transparent rounded-r-md shadow-sm text-sm font-medium text-white bg-ft-orange hover:bg-ft-gray focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                    Entrar
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
<script>
import EmptyLayout from "../../Shared/EmptyLayout.vue";

export default {
    layout: EmptyLayout
}
</script>
<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3';
import Logo from '../../Shared/logo.vue'


defineProps({
    layout: null,
});

let recovery = ref(false);

const code = ref(null);
const recovery_code = ref(null);

let form = useForm({
    code: '',
    recovery_code: '',
})

let submit = () => {
    form.post('/two-factor-challenge', {
        onFinish: () => form.reset('email'),
    })
}

let toggleRecovery = () => {

    recovery.value = !recovery.value;
}

</script>
