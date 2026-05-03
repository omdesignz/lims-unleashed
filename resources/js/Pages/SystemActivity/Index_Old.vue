<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot, Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import slideOver from '@/Components/slide-over.vue';
import { ref, computed, watch, reactive } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Pagination from '@/Components/pagination.vue'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n';



const props = defineProps({
    record: Object,
    users: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

let selectedUser = ref(null);

const query = reactive({
    search: props.query?.search,
    user_id: props.query?.user_id,
    page: null
  });

watch(query, debounce( function(value) {
  router.get(router.page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));

let changeUser = (user_id) => {
  query.user_id = user_id;
}

</script>

<template>
<div class="border-b border-gray-200 pb-5" scroll-region>
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.system_activity.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<div class="relative z-0 flex flex-1 overflow-hidden">
      <main class="relative z-0 flex-1 overflow-y-auto focus:outline-none xl:order-last">
        <!-- Breadcrumb -->
        <!-- <nav class="flex items-start px-4 py-3 sm:px-6 lg:px-8 xl:hidden" aria-label="Breadcrumb">
          <a href="#" class="inline-flex items-center space-x-3 text-sm font-medium text-gray-900">
            <svg class="-ml-2 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
            </svg>
            <span>Directory</span>
          </a>
        </nav> -->

        <article>
          <!-- Profile header -->
          <div v-if="selectedUser"
          v-motion
                :initial="{ opacity: 0, y: 100 }"
                :enter="{ opacity: 1, y: 0, scale: 1 }"
                :variants="{ custom: { scale: 2 } }"
                :delay="100"
          >
            <div>
              <img class="h-32 w-full object-cover lg:h-48" src="https://images.unsplash.com/photo-1444628838545-ac4016a5418a?ixid=MXwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHw%3D&ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="">
            </div>
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
              <div class="-mt-12 sm:-mt-16 sm:flex sm:items-end sm:space-x-5">
                <div class="flex">
                  <img class="h-24 w-24 rounded-full ring-4 ring-white sm:h-32 sm:w-32" :src="selectedUser.profile_photo_url" alt="" v-if="selectedUser?.profile_photo_url">
                  <span class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-blue-900 sm:h-32 sm:w-32 ring-4 ring-white" v-else>
                        <span class="text-xl font-medium leading-none text-white">{{ selectedUser?.name?.charAt(0) }}</span>
                    </span>
                </div>
                <div class="mt-6 sm:flex sm:min-w-0 sm:flex-1 sm:items-center sm:justify-end sm:space-x-6 sm:pb-1">
                  <div class="mt-6 min-w-0 flex-1 sm:hidden 2xl:block">
                    <h1 class="truncate text-2xl font-bold text-gray-900">{{ selectedUser?.name }}</h1>
                  </div>
                  <div class="mt-6 flex flex-col justify-stretch space-y-3 sm:flex-row sm:space-x-4 sm:space-y-0">
                    <button @click="changeUser(null), selectedUser=null" type="button" class="inline-flex justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                      <!-- <svg class="-ml-0.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h1.148a1.5 1.5 0 011.465 1.175l.716 3.223a1.5 1.5 0 01-1.052 1.767l-.933.267c-.41.117-.643.555-.48.95a11.542 11.542 0 006.254 6.254c.395.163.833-.07.95-.48l.267-.933a1.5 1.5 0 011.767-1.052l3.223.716A1.5 1.5 0 0118 15.352V16.5a1.5 1.5 0 01-1.5 1.5H15c-1.149 0-2.263-.15-3.326-.43A13.022 13.022 0 012.43 8.326 13.019 13.019 0 012 5V3.5z" clip-rule="evenodd" />
                      </svg> -->
                      {{ $t('gestlab.general.labels.system_activity.all_activities') }}
                    </button>
                  </div>
                </div>
              </div>
              <div class="mt-6 hidden min-w-0 flex-1 sm:block 2xl:hidden">
                <h1 class="truncate text-2xl font-bold text-gray-900">{{ selectedUser?.name }}</h1>
              </div>
            </div>
          </div>

          <!-- Tabs -->
          <div class="mt-6 sm:mt-2 2xl:mt-5">
            <div class="border-b border-gray-200">
              <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- something goes here -->

                <section aria-labelledby="timeline-title" class="lg:col-span-1 lg:col-start-3">
                    <div class="px-4 py-5 sm:rounded-lg sm:px-6">
                    <h2 id="timeline-title" class="text-lg font-medium text-gray-900">{{ selectedUser ? $t('gestlab.general.labels.system_activity.individual_activities') : $t('gestlab.general.labels.system_activity.all_activities') }}</h2>

                    <!-- Activity Feed -->
                    <div class="mt-6 flow-root">
                        <ul role="list" class="-mb-8">
                        <li v-if="props.record.data.length" v-for="activity in props.record.data" :key="activity.id"
                            v-motion
                            :initial="{ opacity: 0, y: 100 }"
                            :enter="{ opacity: 1, y: 0, scale: 1 }"
                            :variants="{ custom: { scale: 2 } }"
                            :delay="100"
                        >
                            <div class="relative pb-8">
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                                    <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                                    </svg>
                                </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                <div>
                                    <p>{{ activity?.causer?.name }}</p>
                                    <ul role="list" class="-mb-8 font-medium text-sm" v-if="activity.changes">
                                            <Disclosure>
                                                <DisclosureButton>
                                                    <p class="text-sm text-gray-500">{{ activity.description }}</p>
                                                </DisclosureButton>

                                                <!--
                                                By default, the `DisclosurePanel` will automatically show/hide
                                                when the `DisclosureButton` is pressed.
                                                -->
                                                <DisclosurePanel>
                                                    <li v-for="(item, index) in activity.changes" class="rounded-full px-1 inline-flex items-center text-sm font-semibold text-white m-1" :class="[index == 0 ? 'bg-red-600' : 'bg-green-600']">
                                                        <svg v-if="index == 0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="-ml-0.5 h-5 w-5">
                                                                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm-1.72 6.97a.75.75 0 10-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 101.06 1.06L12 13.06l1.72 1.72a.75.75 0 101.06-1.06L13.06 12l1.72-1.72a.75.75 0 10-1.06-1.06L12 10.94l-1.72-1.72z" clip-rule="evenodd" />
                                                            </svg>
                                                            <svg v-else class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                            </svg>
                                                        <span v-for="i in item" class="inline-flex items-center text-xs font-semibold text-white m-1">
                                                            {{ i }}
                                                        </span>
                                                    </li>
                                                </DisclosurePanel>
                                            </Disclosure>
                                    </ul>
                                </div>
                                <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                    <time datetime="2020-09-20">{{ activity.created_at }}</time>
                                </div>
                                </div>
                            </div>
                            </div>
                        </li>
                        </ul>
                    </div>
                    <div class="mt-6 flex flex-col justify-stretch">
                        <Pagination v-if="props.record.data.length" :links="props.record.meta.links" :from="props.record.meta.from" :to="props.record.meta.to" :total="props.record.meta.total" :current_page="props.record.meta.current_page" :last_page="props.record.meta.last_page" class="mt-4" />
                    </div>
                    </div>
                </section>
              </div>
            </div>
          </div>

        
        </article>
      </main>
      <aside class="hidden w-96 flex-shrink-0 border-r border-gray-200 xl:order-first xl:flex xl:flex-col">
        <div class="px-6 pb-4 pt-6">
          <!-- <h2 class="text-lg font-medium text-gray-900">Directory</h2>
          <p class="mt-1 text-sm text-gray-600">Search directory of 3,018 employees</p> -->
          <form class="mt-6 flex gap-x-4" action="#">
            <div class="min-w-0 flex-1">
              <label for="search" class="sr-only">{{ $t('gestlab.general.labels.system_activity.search_activity') }}</label>
              <div class="relative rounded-md shadow-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                  <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <input v-model="query.search" type="search" name="search" id="search" class="block w-full rounded-md border-0 py-1.5 pl-10 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-pink-500 sm:text-sm sm:leading-6" :placeholder="$t('gestlab.general.labels.system_activity.search_activity')">
              </div>
            </div>
            
          </form>
        </div>
        <!-- Directory list -->
        <nav class="min-h-0 flex-1 overflow-y-auto" aria-label="Directory">
          <div class="relative">
            <div class="sticky top-0 z-10 border-b border-t border-gray-200 bg-gray-50 px-6 py-1 text-sm font-medium text-gray-500">
              <!-- <h3>A1</h3> -->
            </div>
            <ul role="list" class="relative z-0 divide-y divide-gray-200">
              <li v-for="(user, index) in props.users.data" :key="user.id">
                <div class="relative flex items-center space-x-3 px-6 py-5 hover:bg-gray-50">
                  <div class="flex-shrink-0">
                    <img class="h-10 w-10 rounded-full" :src="user?.profile_photo_url" alt="" v-if="user?.profile_photo_url">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-900" v-else>
                        <span class="text-xl font-medium leading-none text-white">{{ user?.name?.charAt(0) }}</span>
                    </span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <a href="#" class="focus:outline-none" @click="selectedUser = user, changeUser(user?.id)">
                      <!-- Extend touch target to entire panel -->
                      <span class="absolute inset-0" aria-hidden="true"></span>
                      <p class="text-sm font-medium text-gray-900">{{ user?.name }}</p>
                      <p class="truncate text-xs text-gray-500">
                        <span v-for="(department, departmentIdx) in user.department" :key="department.id" class="px-1.8 py-1 text-xs font-semibold text-gray-900 shadow-sm">{{ department.name }}</span>
                      </p>
                    </a>
                  </div>
                </div>
              </li>
            </ul>
          </div>

        </nav>
      </aside>
    </div>
    
</template>