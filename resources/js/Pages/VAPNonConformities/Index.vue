<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate flex flex-col gap-5 overflow-hidden p-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-r from-primary-600/15 via-rose-400/10 to-amber-400/10 dark:from-primary-500/20 dark:via-rose-500/10 dark:to-amber-500/10"></div>
        <div>
          <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-600/20">
              <ExclamationTriangleIcon class="h-6 w-6" />
            </span>
            {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_non_conformities.index_description') }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="route('vap_non_conformities.create')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition-colors duration-200 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.new_non_conformity') }}
          </Link>

          <Menu as="div" class="relative">
            <MenuButton
              class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.export') }}
              <ChevronDownIcon class="h-4 w-4 text-slate-400" />
            </MenuButton>
            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <MenuItems
                class="absolute right-0 z-20 mt-2 w-60 origin-top-right divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 ring-1 ring-black/5 focus:outline-none dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30"
              >
                <div class="px-2 py-2">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportExcel"
                    :class="[
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.excel_list') }}
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportPdf"
                    :class="[
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.pdf_list') }}
                  </button>
                </MenuItem>
              </div>
              <div class="px-2 py-2" v-if="hasFilters">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportFilteredExcel"
                    :class="[
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
                    ]"
                  >
                    <FunnelIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.excel_filtered') }}
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportFilteredPdf"
                    :class="[
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
                    ]"
                  >
                    <FunnelIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.pdf_filtered') }}
                  </button>
                </MenuItem>
              </div>
              </MenuItems>
            </transition>
          </Menu>
        </div>

      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- SEARCH -->
        <div class="w-full md:w-1/3">
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="text"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.search_placeholder')"
              class="block w-full rounded-2xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/40 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
              @keyup.enter="applyFilters"
            />
          </div>
        </div>

        <!-- FILTERS -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- STATUS FILTER -->
          <div class="relative">
            <Listbox v-model="statusFilter">
              <template #default="{ open }">
                <ListboxButton
                  :class="[
                    'relative w-full cursor-default rounded-2xl border bg-white py-2.5 pl-3 pr-10 text-left text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500/40 sm:text-sm dark:bg-slate-950 dark:text-slate-100',
                    statusFilter ? 'border-primary-500 dark:border-primary-500' : 'border-slate-300 dark:border-slate-700'
                  ]"
                >
                  <span class="block truncate">
                    {{ statusFilter ? $t(`gestlab.general.labels.vap_non_conformities.status.${statusFilter}`) : $t('gestlab.general.labels.vap_non_conformities.all_statuses') }}
                  </span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <transition
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <ListboxOptions
                    v-show="open"
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-2xl border border-slate-200 bg-white py-1 text-base shadow-xl ring-1 ring-black/5 focus:outline-none sm:text-sm dark:border-slate-800 dark:bg-slate-900"
                  >
                    <ListboxOption
                      v-slot="{ active, selected }"
                      :value="null"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-900 dark:text-slate-100']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t('gestlab.general.labels.vap_non_conformities.all_statuses') }}
                        </span>
                      </li>
                    </ListboxOption>
                    <ListboxOption
                      v-for="status in statuses"
                      v-slot="{ active, selected }"
                      :key="status"
                      :value="status"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-900 dark:text-slate-100']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t(`gestlab.general.labels.vap_non_conformities.status.${status}`) }}
                        </span>
                        <span
                          v-if="selected"
                          class="absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                          <CheckIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                        </span>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </template>
            </Listbox>
          </div>

          <!-- SEVERITY FILTER -->
          <div class="relative">
            <Listbox v-model="severityFilter">
              <template #default="{ open }">
                <ListboxButton
                  :class="[
                    'relative w-full cursor-default rounded-2xl border bg-white py-2.5 pl-3 pr-10 text-left text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500/40 sm:text-sm dark:bg-slate-950 dark:text-slate-100',
                    severityFilter ? 'border-primary-500 dark:border-primary-500' : 'border-slate-300 dark:border-slate-700'
                  ]"
                >
                  <span class="block truncate">
                    {{ severityFilter ? $t(`gestlab.general.labels.vap_non_conformities.severity.${severityFilter}`) : $t('gestlab.general.labels.vap_non_conformities.all_severities') }}
                  </span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <transition
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <ListboxOptions
                    v-show="open"
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-2xl border border-slate-200 bg-white py-1 text-base shadow-xl ring-1 ring-black/5 focus:outline-none sm:text-sm dark:border-slate-800 dark:bg-slate-900"
                  >
                    <ListboxOption
                      v-slot="{ active, selected }"
                      :value="null"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-900 dark:text-slate-100']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t('gestlab.general.labels.vap_non_conformities.all_severities') }}
                        </span>
                      </li>
                    </ListboxOption>
                    <ListboxOption
                      v-for="severity in severities"
                      v-slot="{ active, selected }"
                      :key="severity"
                      :value="severity"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-900 dark:text-slate-100']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${severity}`) }}
                        </span>
                        <span
                          v-if="selected"
                          class="absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                          <CheckIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
                        </span>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </template>
            </Listbox>
          </div>

          <!-- ACTION BUTTONS -->
          <button
            @click="applyFilters"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
          >
            <FunnelIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.apply_filters') }}
          </button>
          <button
            @click="clearFilters"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900"
          >
            <XMarkIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.clear_filters') }}
          </button>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.total') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.total }}</p>
          </div>
          <DocumentTextIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.open') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.open }}</p>
          </div>
          <ExclamationTriangleIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.critical') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.critical }}</p>
          </div>
          <ExclamationCircleIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-orange-500 to-orange-400 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.overdue') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.overdue }}</p>
          </div>
          <ClockIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>
    </div>

    <!-- ANALYTICS -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Tendência de não conformidades</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Registos e resoluções dos últimos meses.</p>
        <ChartWrapper class="mt-4" type="area" height="280" :series="ncTrendSeries" :options="ncTrendOptions" />
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Distribuição por severidade</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ajuda a priorizar CAPA e ações imediatas.</p>
        <ChartWrapper class="mt-4" type="donut" height="280" :series="severityChartSeries" :options="severityChartOptions" />
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Estado do fluxo</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Visão rápida de abertas, em curso e encerradas.</p>
        <ChartWrapper class="mt-4" type="bar" height="280" :series="statusChartSeries" :options="statusChartOptions" />
      </div>
    </div>

    <!-- NON-CONFORMITIES TABLE -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
        <h2 class="text-lg font-semibold text-slate-950 dark:text-white">
          {{ $t('gestlab.general.labels.vap_non_conformities.list_title') }}
        </h2>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="nonConformities.data.length === 0" class="p-12 text-center">
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_title') }}
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_description') }}
        </p>
        <Link
          :href="route('vap_non_conformities.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.create_first_nc') }}
        </Link>
      </div>

      <!-- TABLE -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
          <thead class="bg-slate-50 dark:bg-slate-950/70">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.nc_number') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.status.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.reported_at') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900/50">
            <tr 
              v-for="nc in nonConformities.data" 
              :key="nc.id"
              class="transition-colors duration-150 hover:bg-primary-50/40 dark:hover:bg-primary-500/5"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-primary-800 dark:text-primary-300">
                  {{ nc.nc_number }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ nc.title }}</div>
                <div class="max-w-xs truncate text-sm text-slate-500 dark:text-slate-400">
                  {{ truncateText(nc.description, 80) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="statusClasses[nc.status]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nc.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="severityClasses[nc.severity]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${nc.severity}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                {{ formatDate(nc.reported_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-slate-600 dark:text-slate-300" :class="{'!text-red-600 dark:!text-red-300 font-semibold': isOverdue(nc)}">
                  {{ nc.due_date ? formatDate(nc.due_date) : '--' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <Link
                    :href="route('vap_non_conformities.show', nc.id)"
                    class="rounded-xl p-1.5 text-primary-700 transition-colors duration-200 hover:bg-primary-50 hover:text-primary-800 dark:text-primary-300 dark:hover:bg-primary-500/10"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="route('vap_non_conformities.edit', nc.id)"
                    class="rounded-xl p-1.5 text-slate-600 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.edit')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </Link>
                  <button
                    @click="confirmDelete(nc)"
                    class="rounded-xl p-1.5 text-red-600 transition-colors duration-200 hover:bg-red-50 hover:text-red-800 dark:text-red-300 dark:hover:bg-red-500/10"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.delete')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div v-if="nonConformities.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <Pagination :links="nonConformities.links" />
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
    <template #title>
      {{ $t('gestlab.general.labels.vap_non_conformities.delete_title') }}
    </template>
    <template #content>
      {{ $t('gestlab.general.labels.vap_non_conformities.delete_message') }}
      <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70">
        <div class="font-semibold text-slate-900 dark:text-white">{{ ncToDelete?.nc_number }}</div>
        <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ ncToDelete?.title }}</div>
      </div>
    </template>
    <template #footer>
      <button
        @click="showDeleteModal = false"
        class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
      >
        {{ $t('gestlab.general.labels.vap_non_conformities.buttons.cancel') }}
      </button>
      <button
        @click="deleteNc"
        class="ml-3 rounded-2xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
      >
        {{ $t('gestlab.general.labels.vap_non_conformities.buttons.delete') }}
      </button>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router, Link } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  PlusCircleIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  XMarkIcon,
  DocumentTextIcon,
  ExclamationCircleIcon,
  ClockIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
  ChevronUpDownIcon,
  CheckIcon,
  ArrowDownTrayIcon,
  DocumentArrowDownIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
  Menu, MenuButton, MenuItems, MenuItem
} from '@headlessui/vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import ChartWrapper from '@/Components/apex-chart/ChartWrapper.vue'

// Props
const props = defineProps({
  nonConformities: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    required: true
  },
  charts: {
    type: Object,
    default: () => ({})
  },
  labs: {
    type: Array,
    default: () => []
  },
  departments: {
    type: Array,
    default: () => []
  }
})

// Reactive filters
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || null)
const severityFilter = ref(props.filters.severity || null)

// Delete modal
const showDeleteModal = ref(false)
const ncToDelete = ref(null)

// Constants
const statuses = ['opened', 'in_progress', 'resolved', 'closed']
const severities = ['low', 'medium', 'high', 'critical']

const chartTextColor = '#64748b'
const chartGridColor = '#e2e8f0'

const ncTrendSeries = computed(() => props.charts?.trend?.series || [])
const ncTrendOptions = computed(() => ({
  chart: { foreColor: chartTextColor },
  xaxis: { categories: props.charts?.trend?.categories || [] },
  grid: { borderColor: chartGridColor },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 0.4, opacityFrom: 0.35, opacityTo: 0.05 } },
  tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}))

const severityChartSeries = computed(() => props.charts?.severity?.series || [])
const severityChartOptions = computed(() => ({
  labels: props.charts?.severity?.labels || [],
  chart: { foreColor: chartTextColor },
  legend: { position: 'bottom' },
  tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}))

const statusChartSeries = computed(() => [{ name: 'Não conformidades', data: props.charts?.status?.series || [] }])
const statusChartOptions = computed(() => ({
  chart: { foreColor: chartTextColor },
  xaxis: { categories: props.charts?.status?.labels || [] },
  plotOptions: { bar: { borderRadius: 8, columnWidth: '45%' } },
  grid: { borderColor: chartGridColor },
  tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
}))

// Classes
const statusClasses = {
  opened: 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200',
  in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-200',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
  closed: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
}

const severityClasses = {
  low: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
  medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-200',
  high: 'bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-200',
  critical: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200'
}

// Methods
function applyFilters() {
  const filters = {}
  if (search.value) filters.search = search.value
  if (statusFilter.value) filters.status = statusFilter.value
  if (severityFilter.value) filters.severity = severityFilter.value
  
  router.get(route('vap_non_conformities.index'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  search.value = ''
  statusFilter.value = null
  severityFilter.value = null
  router.get(route('vap_non_conformities.index'))
}

function truncateText(text, length) {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

function formatDate(dateString) {
  if (!dateString) return '--'
  return new Date(dateString).toLocaleDateString('pt-Pt')
}

function isOverdue(nc) {
  if (!nc.due_date || nc.status === 'closed') return false
  return new Date(nc.due_date) < new Date() && nc.status !== 'closed'
}

function confirmDelete(nc) {
  ncToDelete.value = nc
  showDeleteModal.value = true
}

function deleteNc() {
  if (ncToDelete.value) {
    router.delete(route('vap_non_conformities.destroy', ncToDelete.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteModal.value = false
      }
    })
  }
}

// Watch for filter changes with debounce
let filterTimeout
watch([search, statusFilter, severityFilter], () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
})

const hasFilters = computed(() => {
  return search.value || statusFilter.value || severityFilter.value
})

function exportExcel() {
  window.location.href = route('vap_non_conformities.export.excel')
}

function exportPdf() {
  window.location.href = route('vap_non_conformities.export.pdf')
}

function exportFilteredExcel() {
  const params = new URLSearchParams()
  if (search.value) params.append('search', search.value)
  if (statusFilter.value) params.append('status', statusFilter.value)
  if (severityFilter.value) params.append('severity', severityFilter.value)
  
  window.location.href = route('vap_non_conformities.export.excel') + '?' + params.toString()
}

function exportFilteredPdf() {
  const params = new URLSearchParams()
  if (search.value) params.append('search', search.value)
  if (statusFilter.value) params.append('status', statusFilter.value)
  if (severityFilter.value) params.append('severity', severityFilter.value)
  
  window.location.href = route('vap_non_conformities.export.pdf') + '?' + params.toString()
}
</script>
