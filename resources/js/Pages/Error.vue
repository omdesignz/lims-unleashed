<template>
    <Head :title="title" />
  <!-- Pages: Errors: 403 -->
  <!-- Page Container -->
  <div
    v-if="status == 403"
    id="page-container"
    class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
  >
    <!-- Page Content -->
    <main id="page-content" class="flex max-w-full flex-auto flex-col">
      <div
        class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
      >
        <!-- Left/Right Background -->
        <div
          class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-orange-50 dark:bg-orange-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <div
          class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-orange-50 dark:bg-orange-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <!-- END Left/Right Background -->

        <!-- Error Content -->
        <div
          class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
        >
          <div>
            <div class="mb-5 text-orange-300 dark:text-orange-300/50">
              <svg
                class="hi-outline hi-hand-raised inline-block h-12 w-12"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.75 6.75 0 006.75 6.75h2.018a5.25 5.25 0 003.712-1.538l1.732-1.732a5.25 5.25 0 001.538-3.712l.003-2.024a.668.668 0 01.198-.471 1.575 1.575 0 10-2.228-2.228 3.818 3.818 0 00-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0116.35 15m.002 0h-.002"
                />
              </svg>
            </div>
            <div
              class="text-6xl font-extrabold text-blue-900 dark:text-orange-500 md:text-7xl"
            >
              {{ status }}
            </div>
            <div
              class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
              aria-hidden="true"
            ></div>
            <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
              {{ description }}
            </h1>
            <h2
              class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
            >
              {{ paragraph }}
            </h2>
          </div>
          <Link
            :href="route('dashboard')"
            class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
          >
            <svg
              class="hi-mini hi-arrow-left inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Voltar ao Dashboard</span>
        </Link>
        </div>
        <!-- END Error Content -->
      </div>
    </main>
    <!-- END Page Content -->
  </div>
  <!-- END Page Container -->
  <!-- END Pages: Errors: 403 -->


  <!-- Pages: Errors: 404 -->
  <!-- Page Container -->
  <div
    v-if="status == 404"
    id="page-container"
    class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
  >
    <!-- Page Content -->
    <main id="page-content" class="flex max-w-full flex-auto flex-col">
      <div
        class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
      >
        <!-- Left/Right Background -->
        <div
          class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-rose-50 dark:bg-rose-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <div
          class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-rose-50 dark:bg-rose-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <!-- END Left/Right Background -->

        <!-- Error Content -->
        <div
          class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
        >
          <div>
            <div class="mb-5 text-rose-300 dark:text-rose-300/50">
              <svg
                class="hi-outline hi-document-magnifying-glass inline-block h-12 w-12"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                />
              </svg>
            </div>
            <div
              class="text-6xl font-extrabold text-rose-600 dark:text-rose-500 md:text-7xl"
            >
              {{ status }}
            </div>
            <div
              class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
              aria-hidden="true"
            ></div>
            <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
              {{ description }}
            </h1>
            <h2
              class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
            >
              {{ paragraph }}
            </h2>
            <!-- <form class="mx-auto max-w-sm space-y-1">
              <div class="relative">
                <div
                  class="pointer-events-none absolute inset-y-0 left-0 my-px ml-px flex w-12 items-center justify-center rounded-l-lg text-gray-500 dark:text-gray-400"
                >
                  <svg
                    class="hi-mini hi-magnifying-glass inline-block h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </div>
                <input
                  type="text"
                  id="search"
                  name="search"
                  placeholder="Search.."
                  class="block w-full rounded-lg border border-gray-200 py-3 pl-12 pr-5 leading-6 placeholder-gray-500 focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:placeholder-gray-400 dark:focus:border-rose-500"
                />
              </div>
            </form> -->
          </div>
          <Link
          :href="route('dashboard')"
            class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
          >
            <svg
              class="hi-mini hi-arrow-left inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Voltar ao Dashboard</span>
        </Link>
        </div>
        <!-- END Error Content -->
      </div>
    </main>
    <!-- END Page Content -->
  </div>
  <!-- END Page Container -->
  <!-- END Pages: Errors: 404 -->


<!-- Pages: Errors: 500 -->
<!-- Page Container -->
<div
  v-if="status == 500"
  id="page-container"
  class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
>
  <!-- Page Content -->
  <main id="page-content" class="flex max-w-full flex-auto flex-col">
    <div
      class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
    >
      <!-- Left/Right Background -->
      <div
        class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-red-50 dark:bg-red-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
        aria-hidden="true"
      ></div>
      <div
        class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-red-50 dark:bg-red-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
        aria-hidden="true"
      ></div>
      <!-- END Left/Right Background -->

      <!-- Error Content -->
      <div
        class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
      >
        <div>
          <div class="mb-5 text-red-300 dark:text-red-300/50">
            <svg
              class="hi-outline hi-server h-21 inline-block w-12"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z"
              />
            </svg>
          </div>
          <div
            class="text-6xl font-extrabold text-red-600 dark:text-red-500 md:text-7xl"
          >
            {{ status }}
          </div>
          <div
            class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
            aria-hidden="true"
          ></div>
          <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
            {{ description }}
          </h1>
          <h2
            class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
          >
            {{ paragraph }}
          </h2>
        </div>
        <Link
        as="button"
        @click="router.reload()"
          href="#"
          class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
        >
          <svg
            class="hi-mini hi-arrow-path inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fill-rule="evenodd"
              d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
              clip-rule="evenodd"
            />
          </svg>
          <span>Tente Atualizar</span>
        </Link>
      </div>
      <!-- END Error Content -->
    </div>
  </main>
  <!-- END Page Content -->
</div>
<!-- END Page Container -->
<!-- END Pages: Errors: 500 -->


<!-- Pages: Errors: 401 -->
  <!-- Page Container -->
  <div
    v-if="status == 401"
    id="page-container"
    class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
  >
    <!-- Page Content -->
    <main id="page-content" class="flex max-w-full flex-auto flex-col">
      <div
        class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
      >
        <!-- Left/Right Background -->
        <div
          class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-purple-50 dark:bg-purple-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <div
          class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-purple-50 dark:bg-purple-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <!-- END Left/Right Background -->

        <!-- Error Content -->
        <div
          class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
        >
          <div>
            <div class="mb-5 text-purple-300 dark:text-purple-300/50">
              <svg
                class="hi-outline hi-shield-exclamation inline-block h-12 w-12"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z"
                />
              </svg>
            </div>
            <div
              class="text-6xl font-extrabold text-purple-600 dark:text-purple-500 md:text-7xl"
            >
              {{ status }}
            </div>
            <div
              class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
              aria-hidden="true"
            ></div>
            <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
              {{ description }}
            </h1>
            <h2
              class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
            >
              {{ paragraph }}
            </h2>
          </div>
          <!-- <Link
            :href="route('dashboard')"
            class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
          >
            <svg
              class="hi-mini hi-arrow-left inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Voltar ao Dashboard</span>
        </Link> -->
        </div>
        <!-- END Error Content -->
      </div>
    </main>
    <!-- END Page Content -->
  </div>
  <!-- END Page Container -->
  <!-- END Pages: Errors: 401 -->

  <!-- Pages: Errors: 503 -->
  <!-- Page Container -->
  <div
    v-if="status == 503"
    id="page-container"
    class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
  >
    <!-- Page Content -->
    <main id="page-content" class="flex max-w-full flex-auto flex-col">
      <div
        class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
      >
        <!-- Left/Right Background -->
        <div
          class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-gray-50 dark:bg-gray-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <div
          class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-gray-50 dark:bg-gray-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <!-- END Left/Right Background -->

        <!-- Error Content -->
        <div
          class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
        >
          <div>
            <div class="mb-5 text-gray-300 dark:text-gray-600">
              <svg
                class="hi-outline hi-wrench-screwdriver inline-block h-12 w-12"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"
                />
              </svg>
            </div>
            <div
              class="text-6xl font-extrabold text-gray-600 dark:text-gray-400 md:text-7xl"
            >
              {{ status }}
            </div>
            <div
              class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
              aria-hidden="true"
            ></div>
            <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
              {{ description }}
            </h1>
            <h2
              class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
            >
              {{ paragraph }}
            </h2>
          </div>
          <Link
          as="button"
            @click="router.reload()"
          href="#"
            class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
          >
            <svg
              class="hi-mini hi-arrow-path inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.989a.75.75 0 00-.75.75v4.242a.75.75 0 001.5 0v-2.43l.31.31a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm1.23-3.723a.75.75 0 00.219-.53V2.929a.75.75 0 00-1.5 0V5.36l-.31-.31A7 7 0 003.239 8.188a.75.75 0 101.448.389A5.5 5.5 0 0113.89 6.11l.311.31h-2.432a.75.75 0 000 1.5h4.243a.75.75 0 00.53-.219z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Tente Atualizar</span>
        </Link>
        </div>
        <!-- END Error Content -->
      </div>
    </main>
    <!-- END Page Content -->
  </div>
  <!-- END Page Container -->
  <!-- END Pages: Errors: 503 -->


  <!-- Pages: Errors: 400 -->
  <!-- Page Container -->
  <div
    v-if="status == 400"
    id="page-container"
    class="mx-auto flex min-h-screen w-full min-w-[320px] flex-col bg-gray-100 dark:bg-gray-900 dark:text-gray-100"
  >
    <!-- Page Content -->
    <main id="page-content" class="flex max-w-full flex-auto flex-col">
      <div
        class="relative flex min-h-screen items-center overflow-hidden bg-white dark:bg-gray-800"
      >
        <!-- Left/Right Background -->
        <div
          class="absolute bottom-0 left-0 top-0 -ml-44 w-48 transform bg-blue-50 dark:bg-blue-500 dark:bg-opacity-10 md:-ml-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <div
          class="absolute bottom-0 right-0 top-0 -mr-44 w-48 transform bg-blue-50 dark:bg-blue-500 dark:bg-opacity-10 md:-mr-28 md:skew-x-6"
          aria-hidden="true"
        ></div>
        <!-- END Left/Right Background -->

        <!-- Error Content -->
        <div
          class="container relative mx-auto space-y-16 px-8 py-16 text-center lg:py-32 xl:max-w-7xl"
        >
          <div>
            <div class="mb-5 text-blue-300 dark:text-blue-300/50">
              <svg
                class="hi-outline hi-exclamation-circle inline-block h-12 w-12"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                />
              </svg>
            </div>
            <div
              class="text-6xl font-extrabold text-blue-600 dark:text-blue-500 md:text-7xl"
            >
              {{ status }}
            </div>
            <div
              class="mx-auto my-6 h-1.5 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 md:my-10"
              aria-hidden="true"
            ></div>
            <h1 class="mb-3 text-2xl font-extrabold md:text-3xl">
              {{ description }}
            </h1>
            <h2
              class="mx-auto mb-5 font-medium text-gray-500 dark:text-gray-400 md:leading-relaxed lg:w-3/5"
            >
              {{ paragraph }}
            </h2>
            
          </div>
          <Link
            :href="route('dashboard')"
            class="group inline-flex items-center justify-center space-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-800 hover:border-gray-300 hover:text-gray-900 hover:shadow-sm focus:ring focus:ring-gray-300 focus:ring-opacity-25 active:border-gray-200 active:shadow-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-gray-200 dark:focus:ring-gray-600 dark:focus:ring-opacity-40 dark:active:border-gray-700"
          >
            <svg
              class="hi-mini hi-arrow-left inline-block h-5 w-5 opacity-50 transition group-hover:opacity-100"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z"
                clip-rule="evenodd"
              />
            </svg>
            <span>Voltar ao Dashboard</span>
        </Link>
        </div>
        <!-- END Error Content -->
      </div>
    </main>
    <!-- END Page Content -->
  </div>
  <!-- END Page Container -->
  <!-- END Pages: Errors: 400 -->
</template>

<script setup>
import EmptyLayout from '@/Shared/Layouts/empty-layout.vue';
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({
    layout: EmptyLayout
});

const props = defineProps({
    status: Number,
});

const title = computed(() => {
    return {
        400: '400. Bad Request',
        404: '404. Not found',
        403: '403. Forbidden',
        401: '401. Unauthorized',
        500: '500. Server',
        503: '500. Service Unavailable',
    }[props.status]
});

const description = computed(() => {
    return {
        400: 'Ufa! Sua solicitação está um pouco errada',
        404: 'Bem, isto é constrangedor...',
        403: 'Não é seu dia de sorte.',
        401: 'Espere aí, amigo',
        500: 'Ufa, nós temos um problema',
        503: 'Aguarde, voltaremos em breve!',
    }[props.status]
});

const paragraph = computed(() => {
    return {
        400: 'Parece que nosso servidor não consegue entender sua solicitação. Verifique seu URL ou volte para o Dashboard',
        404: 'Parece que não conseguimos encontrar a página que você procura. Talvez tenha sido movido ou excluído. Desculpe por isso! Por favor, tente pesquisar em nosso aplicativo da web.',
        403: 'Desculpe, mas parece que hoje não é seu dia de sorte. Você não tem permissão para acessar esta página. Por favor, tente novamente amanhã.',
        401: 'Desculpe, mas você não tem permissão para acessar esta página. Entre em contacto connosco se achar que isso é um erro.',
        500: 'Nosso servidor está tendo um colapso. Não se preocupe, nossa equipe de especialistas está cuidando disso. Por favor, tente novamente mais tarde.',
        503: 'Nossos servidores estão sobrecarregados e precisamos de um pouco de tempo para recuperar o atraso. Pedimos desculpas pelo inconveniente e agradecemos sua paciência.'
    }[props.status]
});
</script>