<template>
    <div class="overflow-hidden bg-gradient-to-r from-gray-100 to-gray-300 py-24 sm:py-32 min-h-screen">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto grid max-w-2xl grid-cols-1 gap-x-8 gap-y-16 sm:gap-y-20 lg:mx-0 lg:max-w-none lg:grid-cols-2">
          <div class="lg:pr-8 lg:pt-4">
            <div class="lg:max-w-lg">
              <h2 class="text-base font-semibold leading-7 text-blue-600">{{ $t('gestlab.pages.portal_login.portal_title') }}</h2>
              <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl">{{ $t('gestlab.pages.portal_login.portal_subtitle') }}</p>
              <p class="mt-6 text-lg leading-8 text-gray-600">{{ $t('gestlab.pages.portal_login.portal_slogan') }}</p>
              <dl class="mt-10 max-w-xl space-y-8 text-base leading-7 text-gray-600 lg:max-w-none">
                <div v-for="feature in features" :key="feature.name" class="relative pl-9">
                  <dt class="inline font-semibold text-gray-900">
                    <component :is="feature.icon" class="absolute left-1 top-1 h-5 w-5 text-blue-600" aria-hidden="true" />
                    {{ $t(feature.name) }}
                  </dt>
                  {{ ' ' }}
                  <dd class="inline">{{ $t(feature.description) }}</dd>
                </div>
              </dl>
            </div>
          </div>


          <Head title="Login" />
    <div class="flex rounded-bl-lg bg-gradient-to-r from-gray-100 to-gray-300"
    v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
    >
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <div>
            <img class="h-12 w-auto" src="../../../images/vap_light.svg" alt="VAP" />
            <logo class="fill-white" width="320" height="72" />
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
              {{ $t('gestlab.pages.portal_login.title') }}
            </h2>
            <!--  <p class="mt-2 text-sm text-gray-600">
                Ou
                {{ ' ' }}
                <Link href="/register" class="font-medium text-blue-800 hover:text-ft-gray">
                inscreva-se hoje
                </Link>
            </p>-->
            </div>

            <div class="mt-8">
            <div>
                <!-- <div>
                <p class="text-sm font-medium text-gray-700">
                    Precisa de mais informações?
                </p>

                <div class="mt-1 grid grid-cols-3 gap-3">
                    <div>
                    <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-blue-800">
                        <span class="sr-only">Voltar à página inicial</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>  clique aqui
                    </a>
                    </div>

                </div>
                </div> -->

                <div class="mt-62 relative">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300" />
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 text-gray-500">
                    <!-- Ou continue com -->
                    </span>
                </div>
                </div>
            </div>

            <div class="mt-6">
                <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.pages.portal_login.email_input_title') }}
                    </label>
                    <div class="mt-1">
                    <input v-model="form.email" id="email" name="email" type="text" autocomplete="email" :class="[form.errors.email ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-800 focus:border-blue-800 sm:text-sm" />
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.pages.portal_login.password_input_title') }}
                    </label>
                    <div class="mt-1">
                    <input v-model="form.password" id="password" name="password" type="password" autocomplete="current-password" :class="[form.errors.password ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : '']" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-800 focus:border-blue-800 sm:text-sm" />
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600" id="email-error">{{ form.errors.password }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                    <input v-model="form.remember" id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-800 focus:ring-blue-800 border-gray-300 rounded-full" />
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                      {{ $t('gestlab.pages.portal_login.remember_input_title') }}
                    </label>
                    </div>

                    <div class="text-sm">
                    <!-- <Link href="/forgot-password" class="font-medium text-blue-800 hover:text-ft-gray">
                        Esqueceu a sua senha?
                    </Link> -->
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#808285] hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-blue">
                      {{ $t('gestlab.pages.portal_login.login_button_title') }}
                    </button>
                    <Link :href="route('dashboard')" class="w-full mt-2 flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ft-orange">
                      {{ $t('gestlab.pages.portal_login.employee_portal_button_title') }}
                    </Link>
                </div>
                </form>
            </div>
            </div>
        </div>
        </div>
        <!-- <div class="hidden lg:block relative w-0 flex-1">
            
            
        <img class="relative inset-0 h-full w-full scale-75" src="../../../images/minsa_inis_2.svg" alt=""/>
        </div> -->
    </div>



        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { useForm } from '@inertiajs/vue3';
  import { CloudArrowUpIcon, LockClosedIcon, ServerIcon } from '@heroicons/vue/20/solid'
  import EmptyLayout from "../../Shared/EmptyLayout.vue";

  defineOptions({
    layout: EmptyLayout
  });
  
  const features = [
    {
      name: 'gestlab.pages.portal_login.points.first.title',
      description:
        'gestlab.pages.portal_login.points.first.description',
      icon: CloudArrowUpIcon,
    },
    {
      name: 'gestlab.pages.portal_login.points.second.title',
      description: 'gestlab.pages.portal_login.points.second.description',
      icon: LockClosedIcon,
    },
    {
      name: 'gestlab.pages.portal_login.points.third.title',
      description: 'gestlab.pages.portal_login.points.third.description',
      icon: ServerIcon,
    },
  ]


  let form = useForm({
    email: '',
    password: '',
    remember: false,
})

let submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : ''
    }))
    .post(route('portal.login.store'), {
        onFinish: () => form.reset('password'), 
    })
}
  </script>