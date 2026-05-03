<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <!-- Header Section -->
    <div class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb and Stats -->
        <div class="py-6">
          <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
              <li class="inline-flex items-center">
                <Link :href="route('portal.home')" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-900">
                  <HomeModernIcon class="h-4 w-4 mr-2" />
                  {{ $t('gestlab.portal_menu.dashboard') }}
                </Link>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <ChevronRightIcon class="h-4 w-4 text-gray-400 mx-2" />
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_quotes.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <DocumentIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_quotes.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_quotes.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_quotes.quotes_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <DocumentIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_quotes.quotes') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
           v-motion
           :initial="{ opacity: 0, y: 20 }"
           :enter="{ opacity: 1, y: 0 }"
      >
        <!-- Active Quotes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quotes.stats.active') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ activeQuotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.stats.active_description') }}</p>
          </div>
        </div>

        <!-- Converted to Invoice -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quotes.stats.converted') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ convertedQuotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <CheckCircleIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.stats.converted_description') }}</p>
          </div>
        </div>

        <!-- Expired Quotes -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quotes.stats.expired') }}</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ expiredQuotesCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <XCircleIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.stats.expired_description') }}</p>
          </div>
        </div>

        <!-- Total Value -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_quotes.stats.total_value') }}</p>
              <p class="mt-2 text-3xl font-bold text-purple-600">{{ formatCurrency(totalQuoteValue) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
              <CurrencyEuroIcon class="h-6 w-6 text-purple-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.stats.total_value_description') }}</p>
          </div>
        </div>
      </div>

      <!-- Search and Filter Bar -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex-1">
            <div class="relative max-w-md">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.pages.portal_quotes.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Status Filter -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <FunnelIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_quotes.filter') }}
                <ChevronDownIcon class="h-4 w-4" />
              </MenuButton>
              <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                  <div class="px-4 py-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.pages.portal_quotes.filter_by_status') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = ''"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          !query.status_filter ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quotes.all_quotes') }}
                        <span v-if="!query.status_filter" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'active'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'active' ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quotes.active') }}
                        <span v-if="query.status_filter === 'active'" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'converted'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'converted' ? 'font-semibold text-green-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quotes.converted') }}
                        <span v-if="query.status_filter === 'converted'" class="h-2 w-2 rounded-full bg-green-600"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.status_filter = 'expired'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.status_filter === 'expired' ? 'font-semibold text-red-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_quotes.expired') }}
                        <span v-if="query.status_filter === 'expired'" class="h-2 w-2 rounded-full bg-red-600"></span>
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- New Quote Button -->
            <button
              @click="requestNewQuote"
              class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_quotes.new_quote') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Quotes Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentIcon class="h-5 w-5" />
              {{ $t('gestlab.pages.portal_quotes.recent_quotes') }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-blue-100">
                {{ activeQuotesCount }} {{ $t('gestlab.pages.portal_quotes.active_now') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <DocumentIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_quotes.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_quotes.empty_state.description') }}
          </p>
          <button
            @click="requestNewQuote"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.pages.portal_quotes.request_first') }}
          </button>
        </div>

        <!-- Quotes List -->
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="(quote, index) in props.record.data" 
            :key="quote.id"
            class="group hover:bg-gray-50 transition-colors duration-200"
            v-motion
            :initial="{ opacity: 0, x: -20 }"
            :enter="{ opacity: 1, x: 0 }"
            :delay="index * 50"
          >
            <div class="px-6 py-4">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Quote Info -->
                <div class="flex-1">
                  <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                      <div :class="[
                        'h-12 w-12 rounded-lg flex items-center justify-center ring-1',
                        getQuoteStatusClass(quote)
                      ]">
                        <DocumentIcon :class="[
                          'h-5 w-5',
                          getQuoteStatusIconClass(quote)
                        ]" />
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3 mb-2">
                        <div>
                          <p class="text-sm font-semibold text-gray-900">
                            Proforma #{{ quote.quote_no }}
                          </p>
                          <p class="text-xs text-gray-500">
                            {{ $t('gestlab.pages.portal_quotes.issue_date') }}: {{ formatDate(quote.date) }}
                          </p>
                        </div>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                          getQuoteStatusBadgeClass(quote)
                        ]">
                          {{ getQuoteStatusText(quote) }}
                        </span>
                      </div>
                      
                      <!-- Quote Details -->
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.total_amount') }}</p>
                          <p class="text-lg font-bold" :class="getAmountColor(quote)">
                            {{ formatCurrency(quote.total) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.valid_until') }}</p>
                          <p class="text-sm font-medium" :class="getValidityColor(quote)">
                            {{ getValidityDate(quote) }}
                          </p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.conversion_status') }}</p>
                          <div class="flex items-center gap-2">
                            <div :class="[
                              'h-2 w-2 rounded-full',
                              quote.status ? 'bg-green-500' : 'bg-gray-300'
                            ]"></div>
                            <p class="text-sm font-medium text-gray-900">
                              {{ quote.status ? $t('gestlab.pages.portal_quotes.converted') : $t('gestlab.pages.portal_quotes.pending') }}
                            </p>
                          </div>
                        </div>
                      </div>

                      <!-- Validity Progress -->
                      <div v-if="!quote.status && quote.date" class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                          <span>{{ $t('gestlab.pages.portal_quotes.validity_period') }}</span>
                          <span>{{ getValidityProgress(quote) }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                          <div 
                            class="h-full rounded-full"
                            :class="getValidityProgressColor(quote)"
                            :style="{ width: `${getValidityProgress(quote)}%` }"
                          ></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_quotes.days_remaining') }}</p>
                    <p class="text-sm font-medium" :class="getDaysRemainingColor(quote)">
                      {{ getDaysRemaining(quote) }}
                    </p>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.quotes.getQuotePDF', { id: quote.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                    >
                      <EyeIcon class="h-4 w-4" />
                      {{ $t('gestlab.pages.portal_quotes.view') }}
                    </a>
                    
                    <Menu as="div" class="relative">
                      <MenuButton class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                      >
                        <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                          <MenuItem v-slot="{ active }">
                            <a
                              :href="route('portal.quotes.getQuotePDF', { id: quote.id })"
                              target="_blank"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                              @click.stop
                            >
                              <ArrowDownTrayIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_quotes.download') }}
                            </a>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="shareQuote(quote)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ShareIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_quotes.share') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }" v-if="!quote.status">
                            <button
                              @click="convertToInvoice(quote)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <DocumentTextIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_quotes.convert_to_invoice') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }" v-if="!quote.status">
                            <button
                              @click="requestExtension(quote)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <CalendarIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_quotes.request_extension') }}
                            </button>
                          </MenuItem>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-700">
              {{ $t('gestlab.pages.portal_quotes.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_quotes.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_quotes.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_quotes.results') }}
            </div>
            <Pagination 
              :links="props.record.meta.links" 
              :from="props.record.meta.from" 
              :to="props.record.meta.to" 
              :total="props.record.meta.total" 
              :current_page="props.record.meta.current_page" 
              :last_page="props.record.meta.last_page" 
              class="mt-2"
            />
          </div>
        </div>
      </div>

      <!-- Quote Information -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- About Proformas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_quotes.about.title') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <DocumentTextIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_quotes.about.what_is') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_quotes.about.description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <ClockIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_quotes.about.validity') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_quotes.about.validity_description') }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-5 w-5 text-green-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_quotes.about.conversion') }}</p>
                <p class="text-xs text-gray-600 mt-1">{{ $t('gestlab.pages.portal_quotes.about.conversion_description') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <BoltIcon class="h-5 w-5 text-yellow-600" />
            {{ $t('gestlab.pages.portal_quotes.quick_actions.title') }}
          </h3>
          <div class="space-y-3">
            <button
              @click="requestNewQuote"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <PlusCircleIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.pages.portal_quotes.quick_actions.new_quote') }}
              </span>
              <ChevronRightIcon class="h-4 w-4 text-gray-400" />
            </button>
            <button
              @click="exportQuotes"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <ArrowDownTrayIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.pages.portal_quotes.quick_actions.export') }}
              </span>
              <ChevronRightIcon class="h-4 w-4 text-gray-400" />
            </button>
            <button
              @click="viewExpiringSoon"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <ExclamationTriangleIcon class="h-4 w-4 text-yellow-600" />
                {{ $t('gestlab.pages.portal_quotes.quick_actions.expiring') }}
              </span>
              <ChevronRightIcon class="h-4 w-4 text-gray-400" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { useForm, router, Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import Pagination from '@/Components/pagination.vue'
import {
  HomeModernIcon,
  DocumentIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  CurrencyEuroIcon,
  PlusCircleIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  DocumentTextIcon,
  CalendarIcon,
  InformationCircleIcon,
  BoltIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import { trans } from 'laravel-vue-i18n';

  import Layout from "@/Shared/Layouts/PortalLayout.vue";

   defineOptions({
    layout: Layout
    });


const props = defineProps({
  record: Object,
  fields: Array,
  query: Object,
})

const page = usePage()

const query = reactive({
  search: props.query?.search || '',
  status_filter: props.query?.status_filter || '',
  page: props.query?.page || 1
})

// Computed properties for stats
const activeQuotesCount = computed(() => {
  // Count quotes from last 30 days that are not converted
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(quote => 
    new Date(quote.date) > thirtyDaysAgo && !quote.status
  ).length
})

const convertedQuotesCount = computed(() => {
  return props.record.data.filter(quote => quote.status).length
})

const expiredQuotesCount = computed(() => {
  // Count quotes older than 30 days that are not converted
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return props.record.data.filter(quote => 
    new Date(quote.date) <= thirtyDaysAgo && !quote.status
  ).length
})

const totalQuoteValue = computed(() => {
  return props.record.data.reduce((sum, quote) => sum + parseFloat(quote.total || 0), 0)
})

// Watch for query changes with debounce
watch(query, debounce(function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300))

// Helper functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const getQuoteStatusClass = (quote) => {
  if (quote.status) return 'bg-green-50 ring-green-100'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays > 30) return 'bg-red-50 ring-red-100'
  if (diffDays > 20) return 'bg-yellow-50 ring-yellow-100'
  return 'bg-blue-50 ring-blue-100'
}

const getQuoteStatusIconClass = (quote) => {
  if (quote.status) return 'text-green-600'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays > 30) return 'text-red-600'
  if (diffDays > 20) return 'text-yellow-600'
  return 'text-blue-900'
}

const getQuoteStatusBadgeClass = (quote) => {
  if (quote.status) return 'bg-green-100 text-green-800 ring-1 ring-green-600/20'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays > 30) return 'bg-red-100 text-red-800 ring-1 ring-red-600/20'
  if (diffDays > 20) return 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-600/20'
  return 'bg-blue-100 text-blue-800 ring-1 ring-blue-600/20'
}

const getQuoteStatusText = (quote) => {
  if (quote.status) return 'CONVERTIDA'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays > 30) return 'EXPIRADA'
  if (diffDays > 20) return 'A EXPIRAR'
  return 'ATIVA'
}

const getAmountColor = (quote) => {
  if (quote.status) return 'text-green-600'
  return 'text-gray-900'
}

const getValidityDate = (quote) => {
  if (!quote.date) return '-'
  const quoteDate = new Date(quote.date)
  const validUntil = new Date(quoteDate)
  validUntil.setDate(validUntil.getDate() + 30) // Quotes typically valid for 30 days
  return formatDate(validUntil)
}

const getValidityColor = (quote) => {
  if (quote.status) return 'text-green-600'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  if (diffDays > 30) return 'text-red-600'
  if (diffDays > 20) return 'text-yellow-600'
  return 'text-gray-900'
}

const getValidityProgress = (quote) => {
  if (quote.status) return 100
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  
  return Math.min(100, (diffDays / 30) * 100)
}

const getValidityProgressColor = (quote) => {
  const progress = getValidityProgress(quote)
  if (progress > 90) return 'bg-red-500'
  if (progress > 70) return 'bg-yellow-500'
  return 'bg-green-500'
}

const getDaysRemaining = (quote) => {
  if (quote.status) return '-'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  const daysRemaining = 30 - diffDays
  
  if (daysRemaining <= 0) return trans('gestlab.pages.portal_quotes.expired')
  return `${daysRemaining} ${trans('gestlab.pages.portal_quotes.days')}`
}

const getDaysRemainingColor = (quote) => {
  if (quote.status) return 'text-gray-500'
  
  const quoteDate = new Date(quote.date)
  const now = new Date()
  const diffDays = Math.floor((now - quoteDate) / (1000 * 60 * 60 * 24))
  const daysRemaining = 30 - diffDays
  
  if (daysRemaining <= 0) return 'text-red-600'
  if (daysRemaining <= 10) return 'text-yellow-600'
  return 'text-green-600'
}

const exportRowsToCsv = (filename, columns, rows) => {
  const header = columns.join(',')
  const content = rows.map((row) => row.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
  const blob = new Blob([[header, ...content].join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const copyToClipboard = async (value) => {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

const openBillingSupportRequest = (title, description, details = {}) => {
  router.get(route('portal.requests.index'), {
    new: 1,
    request_type: 'billing_support',
    title,
    description,
    details,
  })
}

const requestNewQuote = () => {
  openBillingSupportRequest(
    'Solicitar nova proposta comercial',
    'Pretendo solicitar uma nova proposta comercial.',
    { document_type: 'quote' },
  )
}

const shareQuote = (quote) => {
  copyToClipboard(route('portal.quotes.getQuotePDF', { id: quote.id }))
}

const convertToInvoice = (quote) => {
  if (!quote.status) {
    openBillingSupportRequest(
      `Solicitar conversão da proposta ${quote.quote_no} em fatura`,
      `Solicito a conversão da proposta ${quote.quote_no} em fatura.`,
      {
        document_reference: quote.quote_no,
        document_type: 'quote',
      },
    )
  }
}

const requestExtension = (quote) => {
  if (!quote.status) {
    openBillingSupportRequest(
      `Solicitar extensão da proposta ${quote.quote_no}`,
      `Solicito a extensão do prazo de validade da proposta ${quote.quote_no}.`,
      {
        document_reference: quote.quote_no,
        document_type: 'quote',
      },
    )
  }
}

const exportQuotes = () => {
  exportRowsToCsv(
    'portal-quotes.csv',
    ['Referência', 'Emissão', 'Cliente', 'Total', 'Estado'],
    props.record.data.map((quote) => [
      quote.quote_no,
      formatDate(quote.date),
      quote.customer,
      quote.total,
      getQuoteStatusText(quote),
    ]),
  )
}

const viewExpiringSoon = () => {
  query.status_filter = 'expired'
  query.page = 1
}
</script>
