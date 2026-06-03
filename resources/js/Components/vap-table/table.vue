<template>
  <div class="space-y-6">
    <!-- TABLE COMMAND SURFACE -->
    <section class="overflow-hidden rounded-[2.25rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgb(20_61_55/0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="px-5 py-5 sm:px-7 sm:py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">
              {{ $t('gestlab.general.titles.search_and_filters') }}
            </p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
              {{ $t('gestlab.general.titles.records_list') }}
            </h1>
            <p class="mt-1 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
              {{ props.pagination.total ?? props.data.length }} {{ $t('gestlab.general.labels.records') }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <span class="inline-flex items-center rounded-full border border-[#ded3bf] bg-white px-3 py-1.5 text-xs font-bold text-[#5f6f68] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4]">
              {{ resultSummary }}
            </span>

            <button
              v-if="props.createAction && hasPermission('add_' + props.model)"
              type="button"
              class="inline-flex h-12 items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 text-sm font-semibold text-white shadow-[0_12px_30px_rgb(var(--primary-900-rgb)/0.14)] transition-all duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-[#07110f]"
              @click="$emit('create-record')"
            >
              <SquaresPlusIcon class="h-5 w-5" />
              {{ $t("gestlab.general.buttons.new_record") }}
            </button>
          </div>
        </div>

        <div class="mt-5 rounded-[1.75rem] border border-[#ded3bf] bg-white/85 p-2 shadow-[inset_0_1px_0_rgb(255_255_255/0.75)] dark:border-[#25443c] dark:bg-[#10231f]/80 dark:shadow-none">
          <div class="grid gap-2 xl:grid-cols-[minmax(18rem,1fr)_auto] xl:items-center">
          <div class="relative min-w-0">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
              <MagnifyingGlassIcon class="h-5 w-5 text-[#8d9b94] dark:text-[#657970]" />
            </div>
            <input
              v-model="filters.globalFilter"
              type="search"
              :placeholder="$t('gestlab.general.search_input_placeholder')"
              class="block h-12 w-full rounded-[1.35rem] border border-transparent bg-[#fffdf7] pl-11 pr-3 text-sm font-semibold text-[#15231f] placeholder:text-[#8d9b94] transition-colors duration-200 focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.18)] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
              @input="updateQuery"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <button
              type="button"
              class="inline-flex h-12 items-center justify-center gap-2 rounded-[1.35rem] border px-4 text-sm font-black transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:focus:ring-offset-[#07110f]"
              :class="showFilterPanel
                ? 'border-[rgb(var(--primary-800-rgb))] bg-[rgb(var(--primary-800-rgb))] text-white shadow-[0_14px_34px_rgb(var(--primary-900-rgb)/0.16)] dark:border-[rgb(var(--primary-500-rgb))] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]'
                : 'border-transparent bg-[#f7f1e7] text-[#31413b] hover:bg-[#fffdf7] hover:text-[#143d37] dark:bg-[#07110f] dark:text-[#d7e2dd] dark:hover:bg-[#152f29]'"
              :aria-expanded="showFilterPanel"
              @click="showFilterPanel = !showFilterPanel"
            >
              <FunnelIcon class="h-4 w-4" />
              {{ $t('gestlab.filter.filters') }}
              <span
                v-if="activeFilterCount"
                class="inline-flex min-w-6 items-center justify-center rounded-full bg-[#f1d78b] px-2 py-0.5 text-[11px] font-black text-[#07110f]"
              >
                {{ activeFilterCount }}
              </span>
            </button>

            <ColumnVisibilityToggle
              compact
              :columns="columns"
              @update-columns="updateColumns"
            />

            <div class="relative">
              <select
                v-model="perPage"
                @change="changePerPage"
                class="block h-12 rounded-[1.35rem] border border-transparent bg-[#f7f1e7] py-0 pl-3 pr-9 text-sm font-bold text-[#31413b] transition-colors duration-200 hover:bg-[#fffdf7] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.18)] dark:bg-[#07110f] dark:text-[#d7e2dd] dark:hover:bg-[#152f29]"
              >
                <option value="10" :selected="props.pagination.per_page == 10">10 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="25" :selected="props.pagination.per_page == 25">25 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="50" :selected="props.pagination.per_page == 50">50 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
                <option value="100" :selected="props.pagination.per_page == 100">100 / {{ $t('gestlab.general.labels.per_page_short') }}</option>
              </select>
            </div>
          </div>
          </div>
        </div>

        <!-- Active Filters -->
        <div v-if="activeFilterChips.length" class="mt-4 flex flex-wrap items-center gap-2">
          <div
            v-for="column in activeFilterChips"
            :key="column.field"
            class="inline-flex items-center gap-2 rounded-full border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-3 py-1.5 text-xs dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]"
          >
            <span class="font-semibold text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">{{ $t(column.label) }}:</span>
            <span class="font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
              {{ formatFilterValue(column, filters[column.filter_field]) }}
            </span>
            <button
              @click="removeFilter(column.filter_field)"
              class="rounded-full p-0.5 text-[rgb(var(--primary-700-rgb))] hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] focus:ring-offset-1 focus:ring-offset-[#fffdf7] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:text-[rgb(var(--primary-50-rgb))] dark:focus:ring-offset-[#07110f]"
              :title="$t('gestlab.general.buttons.clear')"
            >
              <XMarkIcon class="h-3 w-3" />
            </button>
          </div>

          <button
            type="button"
            @click="clearActiveFilters"
            class="inline-flex items-center gap-1.5 rounded-full border border-[#ded3bf] bg-white px-3 py-1.5 text-xs font-semibold text-[#5f6f68] transition hover:border-[rgb(var(--primary-500-rgb))] hover:text-[#15231f] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#a9bbb4] dark:hover:bg-[#152f29] dark:hover:text-[#f7f1e7]"
          >
            <XMarkIcon class="h-3 w-3" />
            {{ $t('gestlab.general.buttons.clear') }}
          </button>
        </div>

        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-show="showFilterPanel"
            class="mt-4 rounded-[1.8rem] border border-[#ded3bf] bg-[#f7f1e7]/70 p-4 dark:border-[#25443c] dark:bg-[#10231f]/70 sm:p-5"
          >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-[#6b7b74] dark:text-[#83978d]">
                  {{ $t('gestlab.filter.available_filters') }}
                </p>
                <p class="mt-1 text-sm font-medium text-[#475a53] dark:text-[#cbd8cf]">
                  {{ visibleFilterableColumns.length }} {{ $t('gestlab.filter.filters') }}
                </p>
              </div>

              <button
                v-if="hasActiveFilters"
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-full border border-[#ded3bf] bg-white px-3 py-2 text-xs font-bold text-[#5f6f68] transition hover:text-[#143d37] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:text-[#f7f1e7]"
                @click="clearActiveFilters"
              >
                <XMarkIcon class="h-3.5 w-3.5" />
                {{ $t('gestlab.general.buttons.clear') }}
              </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
              <button
                v-for="column in visibleFilterableColumns"
                :key="column.field"
                @click="toggleFilter(column.filter_field)"
                :class="[
                  'inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.26)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:focus:ring-offset-[#07110f]',
                  isFilterActive(column.filter_field)
                    ? 'border-[rgb(var(--primary-800-rgb))] bg-[rgb(var(--primary-800-rgb))] text-white dark:border-[rgb(var(--primary-500-rgb))] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]'
                    : 'border-[#ded3bf] bg-[#fffdf7] text-[#5f6f68] hover:bg-white hover:text-[#15231f] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:bg-[#152f29] dark:hover:text-[#f7f1e7]'
                ]"
              >
                <FunnelIcon class="h-3 w-3" />
                {{ $t(column.label) }}
              </button>

              <button
                v-if="props.trashedFilter"
                @click="toggleTrashedFilter"
                :class="[
                  'inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-rose-500/25 focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:focus:ring-offset-[#07110f]',
                  filters.trashed
                    ? 'border-rose-600 bg-rose-600 text-white'
                    : 'border-[#ded3bf] bg-[#fffdf7] text-[#5f6f68] hover:bg-white hover:text-[#15231f] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:bg-[#152f29] dark:hover:text-[#f7f1e7]'
                ]"
              >
                <TrashIcon class="h-3 w-3" />
                {{ $t('gestlab.general.labels.trashed') }}
              </button>
            </div>

            <div v-if="hasActiveFilters" class="mt-5 border-t border-[#ded3bf] pt-5 dark:border-[#25443c]">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <!-- Dynamic Filters -->
                <div
                  v-for="column in visibleFilterableColumns"
                  :key="column.field"
                  v-show="isFilterActive(column.filter_field)"
                  :class="[
                    'space-y-2',
                    column.type === 'remote_select_multiple' ? 'md:col-span-2 lg:col-span-3' : ''
                  ]"
                >
                  <label :for="column.field" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                    {{ $t(column.label) }}
                    <span v-if="column.required" class="text-red-500 ml-0.5">*</span>
                  </label>

                  <!-- String Filter -->
                  <input
                    v-if="column.type === 'string'"
                    v-model="filters[column.filter_field]"
                    :id="column.field"
                    :name="column.field"
                    type="text"
                    @input="updateQuery"
                    :placeholder="$t('gestlab.general.search_input_placeholder')"
                    class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] placeholder:text-[#8d9b94] shadow-sm transition-colors duration-200 focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
                  />

                  <!-- Date Filter -->
                  <DatePicker
                    v-if="column.type === 'date'"
                    v-model.range.string="filters[column.filter_field]"
                    :select-attribute="selectDragAttribute"
                    :drag-attribute="selectDragAttribute"
                    @drag="dragValue = $event"
                    :is-dark="$page.props.darkMode ?? false"
                    :locale="$page.props.auth?.user?.language === 'en' ? 'en-US' : 'pt-PT'"
                    color="primary"
                    mode="date"
                    @update:model-value="updateQuery"
                    :masks="masks"
                  >
                    <template #default="{ togglePopover }">
                      <div class="relative">
                        <input
                          :value="formatDateRange(column)"
                          type="text"
                          readonly
                          @click="togglePopover"
                          :placeholder="$t('gestlab.general.calendar_input_placeholder')"
                          class="block w-full cursor-pointer rounded-2xl border border-[#d8cbb8] bg-white py-3 pl-3.5 pr-11 text-sm font-medium text-[#15231f] shadow-sm transition-all duration-200 placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
                        />
                        <CalendarIcon class="absolute right-3 top-3 h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" />
                      </div>
                    </template>
                  </DatePicker>

                  <!-- Boolean Filter -->
                  <div v-if="column.type === 'boolean'" class="pt-1">
                    <button
                      @click="toggleBooleanFilter(column.filter_field)"
                      :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:focus:ring-offset-[#07110f]',
                        filters[column.filter_field]
                          ? 'bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb))]'
                          : 'bg-[#d8cbb8] dark:bg-[#315149]'
                      ]"
                    >
                      <span
                        :class="[
                          'inline-block h-5 w-5 transform rounded-full bg-white transition duration-200',
                          filters[column.filter_field] ? 'translate-x-6' : 'translate-x-1'
                        ]"
                      />
                      <span class="sr-only">{{ column.label }}</span>
                    </button>
                    <span class="ml-3 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                      {{ filters[column.filter_field] ? 'Activo' : 'Inactivo' }}
                    </span>
                  </div>

                  <!-- Local Select Filter -->
                  <combobox
                    v-if="column.type === 'select'"
                    :name="column.field"
                    :hasError="false"
                    v-model="filters[column.filter_field]"
                    :options="column.options"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />

                  <!-- Remote Select Filter -->
                  <combobox
                    v-if="column.type === 'remote_select'"
                    :name="column.field"
                    :hasError="false"
                    v-model="filters[column.filter_field]"
                    :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />

                  <!-- Multiple Remote Select Filter -->
                  <comboboxMultiple
                    v-if="column.type === 'remote_select_multiple'"
                    :name="column.field"
                    v-model="filters[column.filter_field]"
                    :multiple="true"
                    :load-options="(query, setOptions) => fetchSelectOptions(query, setOptions, column)"
                    @update:modelValue="updateQuery"
                    class="w-full"
                  />
                </div>

                <!-- Trashed Filter -->
                <div v-if="props.trashedFilter && isFilterActive('trashed')" class="space-y-2">
                  <label for="trashed" class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                    {{ $t('gestlab.general.labels.trashed') }}
                  </label>
                  <combobox
                    name="trashed"
                    :hasError="false"
                    v-model="filters.trashed"
                    :options="props.trashedOptions || []"
                    @update:model-value="updateQuery"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Custom Filters Slot -->
            <div v-if="$slots['specific-filters']" class="mt-5">
              <slot name="specific-filters" />
            </div>
          </div>
        </transition>
      </div>
    </section>

    <!-- DATA TABLE CARD -->
    <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgb(20_61_55/0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <!-- Table Header -->
      <div v-if="props.actions?.length && (allSelected || selectedRows.length)" class="border-b border-[#ded3bf] bg-[#f7f1e7] px-5 py-4 dark:border-[#25443c] dark:bg-[#10231f] sm:px-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
            <TableCellsIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.selected_records') }}
            <span class="rounded-full border border-[#ded3bf] bg-white px-3 py-1 text-xs font-bold text-[#5f6f68] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4]">
              {{ selectedRows.length }} de {{ props.data.length }}
            </span>
          </h2>
          <BulkActions 
            :actions="props.actions"
            @bulk-action="handleBulkAction"
            class="text-sm self-start sm:self-auto"
          />
        </div>
      </div>

      <!-- Table Content -->
      <div class="md:hidden" v-if="props.data.length && visibleColumns.length">
        <div class="divide-y divide-[#ded3bf] dark:divide-[#25443c]">
          <article
            v-for="row in props.data"
            :key="row.id"
            class="space-y-4 px-5 py-5 transition-colors duration-150"
            :class="isRowSelected(row.id) ? 'bg-[rgb(var(--primary-50-rgb)/0.6)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]' : 'bg-[#fffdf7] dark:bg-[#07110f]'"
          >
            <div class="flex items-start justify-between gap-3">
              <label class="flex items-center gap-3">
                <input
                  type="checkbox"
                  :value="row.id"
                  :checked="isRowSelected(row.id)"
                  class="h-4 w-4 rounded border-[#d8cbb8] text-[rgb(var(--primary-700-rgb))] focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] dark:border-[#315149] dark:bg-[#07110f] dark:text-[rgb(var(--primary-300-rgb))]"
                  @change="toggleSelectRow"
                />
                <div>
                  <p class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
                    {{ row[visibleColumns[0]?.field] ?? `#${row.id}` }}
                  </p>
                  <p class="text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">ID {{ row.id }}</p>
                </div>
              </label>
            </div>

            <dl class="grid grid-cols-1 gap-3">
              <div
                v-for="column in visibleColumns"
                :key="`${row.id}-${column.field}`"
                class="rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/70 px-3 py-2 dark:border-[#25443c] dark:bg-[#10231f]/70"
              >
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-[#73827b] dark:text-[#8ea49b]">
                  {{ column.label }}
                </dt>
                <dd class="mt-1 break-words text-sm font-medium text-[#15231f] dark:text-[#f7f1e7]">
                  <slot :name="`column-${column.field}`" :row="row">
                    {{ row[column.field] }}
                  </slot>
                </dd>
              </div>
            </dl>
          </article>
        </div>
      </div>

      <div v-if="!props.data.length" class="p-10 text-center md:hidden">
        <TableCellsIcon class="mx-auto h-11 w-11 text-[#8d9b94] dark:text-[#657970]" />
        <h3 class="mt-4 text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
          {{ $t('gestlab.general.titles.no_records') }}
        </h3>
        <p class="mt-2 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
          {{ $t('gestlab.general.titles.start_creating') }}
        </p>
      </div>

      <div class="hidden overflow-x-auto md:block">
        <table class="min-w-full divide-y divide-[#ded3bf] dark:divide-[#25443c]" v-if="props.data.length && visibleColumns.length">
          <TableHeader
            :columns="visibleColumns"
            :sortField="sortField"
            :sortDirection="sortDirection"
            :allSelected="allSelected"
            @toggle-select-all="toggleSelectAll"
            @change-sort="changeSort"
          />
          <TableBody
            :rows="props.data"
            :columns="visibleColumns"
            :selectedRows="selectedRows"
            @toggle-select-row="toggleSelectRow"
            @single-action="handleSingleAction"
          >
            <template v-for="column in visibleColumns" v-slot:[`column-${column.field}`]="{ row }">
              <slot :name="`column-${column.field}`" :row="row">
                {{ row[column.field] }}
              </slot>
            </template>
          </TableBody>
        </table>
        
        <!-- Empty State -->
        <div v-if="!props.data.length" class="p-12 text-center">
          <TableCellsIcon class="mx-auto h-12 w-12 text-[#8d9b94] dark:text-[#657970]" />
          <h3 class="mt-4 text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
            {{ $t('gestlab.general.titles.no_records') }}
          </h3>
          <p class="mt-2 text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
            {{ $t('gestlab.general.titles.start_creating') }}
          </p>
          <button
            v-if="props.createAction && hasPermission('add_' + props.model)"
            @click="$emit('create-record')"
            type="button"
            class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-semibold text-white shadow-[0_12px_30px_rgb(var(--primary-900-rgb)/0.14)] transition-colors duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-[#07110f]"
          >
            <SquaresPlusIcon class="h-5 w-5" />
            {{ $t("gestlab.general.buttons.new_record") }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="props.data.length" class="border-t border-[#ded3bf] px-6 py-5 dark:border-[#25443c]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <Pagination
            :links="props.pagination.links"
            :from="props.pagination.from"
            :to="props.pagination.to"
            :total="props.pagination.total"
            :current_page="props.pagination.current_page"
            :last_page="props.pagination.last_page"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  SquaresPlusIcon,
  CalendarIcon,
  FunnelIcon,
  TrashIcon,
  TableCellsIcon
} from "@heroicons/vue/24/outline";
import debounce from "lodash/debounce";
import { usePage, router } from "@inertiajs/vue3";

import ColumnVisibilityToggle from "@/Components/vap-table/column-visibility-toggle.vue";
import BulkActions from "@/Components/vap-table/bulk-actions.vue";
import TableHeader from "@/Components/vap-table/table-header.vue";
import TableBody from "@/Components/vap-table/table-body.vue";
import Pagination from "@/Components/pagination.vue";
import { usePermission } from "@/Composables/usePermissions";
import { loadSelectOptions } from "@/Utils/selectOptions";
import { DatePicker } from 'v-calendar'
import combobox from '@/Components/vap-table/combobox.vue';
import comboboxMultiple from '@/Components/vap-table/combobox-multiple.vue';
import 'v-calendar/dist/style.css';

const { hasPermission } = usePermission();

const page = usePage();

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  actions: {
    type: Array,
    default: () => [],
  },
  query: Object,
  filters: Array,
  trashedFilter: Boolean,
  trashedOptions: Array,
  initialFilters: Object,
  initialSortField: String,
  initialSortDirection: String,
  initialIncludes: Array,
  initialGlobalFilter: String,
  pagination: {
    type: Object,
    default: () => ({}),
  },
  slideOverEdit: Boolean,
  model: {
    type: String,
    default: "",
  },
  abilities: {
    type: Array,
    default: [],
  },
  createAction: {
    type: Boolean,
    default: true,
  }
});

const emit = defineEmits(["execute-bulk-action", "slideover-on", "create-record", "update-selected-ids"]);

const columns = ref([...(props.columns || [])]);
const filters = ref({
  ...(props.initialFilters || {}),
  globalFilter: props.initialGlobalFilter || props.initialFilters?.globalFilter || '',
});
const sortField = ref(props.initialSortField || '');
const sortDirection = ref(props.initialSortDirection || 'asc');
const includes = ref(props.initialIncludes || []);
const selectedRows = ref([]);
const activeFilters = ref([]);
const dragValue = ref(null);
const perPage = ref(props.pagination?.per_page || 10);
const showFilterPanel = ref(false);

const visibleColumns = computed(() => columns.value.filter(column => column.visible));
const visibleFilterableColumns = computed(() => visibleColumns.value.filter(column => column.filterable));
const allSelected = computed(() => props.data.length > 0 && props.data.every(row => isRowSelected(row.id)));
const resultSummary = computed(() => {
  const total = props.pagination?.total ?? props.data.length;
  const from = props.pagination?.from ?? (total ? 1 : 0);
  const to = props.pagination?.to ?? props.data.length;

  return `${from}–${to} de ${total}`;
});
const hasActiveFilters = computed(() => activeFilters.value.length > 0);
const activeFilterCount = computed(() => activeFilters.value.length);
const activeFilterChips = computed(() => visibleFilterableColumns.value.filter(column => {
  return isFilterActive(column.filter_field) && hasFilterValue(filters.value[column.filter_field]);
}));
const selectedRecordIds = computed(() => selectedRows.value.map(selectedRow => {
  const row = props.data.find(item => rowKey(item.id) === selectedRow);

  return row?.id ?? selectedRow;
}));

const selectDragAttribute = {
  highlight: {
    color: 'primary',
    fillMode: 'light',
    contentClass: 'bg-primary-500 text-white',
  },
  contentStyle: {
    color: 'black',
  },
};

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});

// Helper functions
const rowKey = id => String(id);

const isRowSelected = id => selectedRows.value.includes(rowKey(id));

const emitSelectedRows = () => {
  emit("update-selected-ids", selectedRecordIds.value);
};

const hasFilterValue = value => {
  if (Array.isArray(value)) {
    return value.length > 0;
  }

  if (value && typeof value === 'object') {
    return Object.values(value).some(item => item !== null && item !== undefined && item !== '');
  }

  return value !== null && value !== undefined && value !== '';
};

const currentSort = () => {
  return sortField.value
    ? (sortDirection.value === 'asc' ? sortField.value : `-${sortField.value}`)
    : '';
};

const formatDateRange = (column) => {
  const value = filters.value[column.filter_field];
  if (!value || (!value.start && !value.end)) return '';
  return `${value.start || ''} - ${value.end || ''}`;
};

const formatFilterValue = (column, value) => {
  if (Array.isArray(value)) {
    return value.join(', ');
  }
  if (typeof value === 'object' && value !== null) {
    if (value.start || value.end) {
      return `${value.start || ''} - ${value.end || ''}`;
    }
    return JSON.stringify(value);
  }
  if (typeof value === 'boolean') {
    return value ? 'Sim' : 'Não';
  }
  return value;
};

const fetchSelectOptions = async (query, setOptions, column) => {
  return loadSelectOptions(
    column.config.url,
    query,
    setOptions,
    result => ({
      value: result[column.config.value],
      label: result[column.config.label],
    }),
  );
};

const changeSort = (field) => {
  if (sortField.value === field) {
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc';
    } else {
      sortField.value = '';
      sortDirection.value = 'asc';
    }
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  updateQuery();
};

const changePerPage = () => {
  router.get(page.url, {
    page: 1,
    per_page: perPage.value,
    filter: filters.value,
    sort: currentSort(),
    includes: includes.value,
    globalFilter: filters.value.globalFilter || ''
  }, {
    preserveScroll: false,
    preserveState: true,
    replace: true
  });
};

const toggleSelectAll = (event) => {
  if (event.target.checked) {
    selectedRows.value = props.data.map(item => rowKey(item.id));
  } else {
    selectedRows.value = [];
  }
  emitSelectedRows();
};

const updateQuery = debounce(() => {
  router.get(page.url, {
    filter: filters.value,
    sort: currentSort(),
    includes: includes.value,
    globalFilter: filters.value.globalFilter || '',
    per_page: perPage.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300);

const toggleFilter = (field) => {
  showFilterPanel.value = true;

  if (activeFilters.value.includes(field)) {
    activeFilters.value = activeFilters.value.filter(f => f !== field);
    removeFilter(field);
  } else {
    activeFilters.value.push(field);
  }
};

const isFilterActive = (field) => {
  return activeFilters.value.includes(field);
};

const toggleSelectRow = (event) => {
  const id = rowKey(event.target.value);

  if (event.target.checked) {
    selectedRows.value = [...new Set([...selectedRows.value, id])];
  } else {
    selectedRows.value = selectedRows.value.filter(rowId => rowId !== id);
  }
  emitSelectedRows();
};

const handleBulkAction = (action) => {
  emit("execute-bulk-action", {
    action: action,
    actionType: 'bulk',
  });
};

const handleSingleAction = ({ action, id }) => {
  emit("execute-bulk-action", {
    action: action,
    actionType: 'single',
    id: id
  });
};

const updateColumns = (updatedColumns) => {
  columns.value = updatedColumns;
  updateQuery();
};

const removeFilter = (field) => {
  filters.value[field] = '';
  updateQuery();
  if (field !== 'globalFilter') {
    activeFilters.value = activeFilters.value.filter(f => f !== field);
  }
};

const clearActiveFilters = () => {
  activeFilters.value.forEach(field => {
    filters.value[field] = '';
  });

  activeFilters.value = [];
  updateQuery();
};

const toggleBooleanFilter = (field) => {
  filters.value[field] = !filters.value[field];
  updateQuery();
};

const toggleTrashedFilter = () => {
  showFilterPanel.value = true;
  filters.value.trashed = !filters.value.trashed;
  if (filters.value.trashed && !isFilterActive('trashed')) {
    activeFilters.value.push('trashed');
  } else if (!filters.value.trashed && isFilterActive('trashed')) {
    activeFilters.value = activeFilters.value.filter(f => f !== 'trashed');
  }
  updateQuery();
};

const hydrateActiveFiltersFromValues = () => {
  const filterFields = visibleFilterableColumns.value
    .map(column => column.filter_field)
    .filter(field => hasFilterValue(filters.value[field]));

  if (props.trashedFilter && hasFilterValue(filters.value.trashed)) {
    filterFields.push('trashed');
  }

  activeFilters.value = [...new Set([...activeFilters.value, ...filterFields])];
};

// Watch for changes
watch(
  [() => filters.value, () => sortField.value, () => sortDirection.value, () => includes.value],
  updateQuery,
  { deep: true }
);

watch(
  () => props.pagination?.per_page,
  value => {
    perPage.value = value || perPage.value;
  }
);

// Initialize filters from query
onMounted(() => {
  if (props.query) {
    if (props.query.filter) {
      filters.value = { ...filters.value, ...props.query.filter };
    }
    if (props.query.sort) {
      const sort = props.query.sort;
      if (sort.startsWith('-')) {
        sortField.value = sort.substring(1);
        sortDirection.value = 'desc';
      } else {
        sortField.value = sort;
        sortDirection.value = 'asc';
      }
    }

  }

  hydrateActiveFiltersFromValues();
});
</script>

<style scoped>
/* Custom scrollbar for table */
div.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

div.overflow-x-auto::-webkit-scrollbar-track {
  background: #f7f1e7;
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #d8cbb8;
  border-radius: 3px;
}

div.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(var(--primary-500-rgb));
}

/* Smooth transitions */
button, input, select {
  transition: all 0.2s ease-in-out;
}

/* Date picker popover styling */
:deep(.vc-popover-content) {
  border-radius: 1.25rem !important;
  border: 1px solid #ded3bf !important;
  background: #fffdf7 !important;
  box-shadow: 0 22px 70px rgb(20 61 55 / 0.16) !important;
}

:global(.dark) :deep(.vc-popover-content) {
  border-color: #25443c !important;
  background: #07110f !important;
}
</style>
