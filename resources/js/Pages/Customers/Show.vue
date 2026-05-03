<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BuildingStorefrontIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.customers.customer_details') }}
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.customers.view_customer_details') }}
          </p>
        </div>
        <div class="flex items-center gap-4">
          <Link
            as="button"
            :href="route('customers.edit', { customer: record.data?.id })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.buttons.edit') }}
        </Link>
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ record.data?.warehouses?.length || 0 }} {{ $t('gestlab.general.labels.customers.items') }}
          </span>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Saúde comercial</h2>
            <p class="mt-1 text-sm text-gray-500">
              Leitura rápida do relacionamento comercial e da pressão operacional do cliente.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Itens monitorizados</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ commercialHealthTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="commercialHealthChartOptions" :series="commercialHealthChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Execução do cliente</h2>
              <p class="mt-1 text-sm text-gray-500">Amostras, certificados e comprovativos já gerados.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ executionMixTotal }} registos
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="executionMixChartOptions" :series="executionMixChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pressão financeira</h2>
              <p class="mt-1 text-sm text-gray-500">Montante em aberto versus compensações por notas de crédito.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="financialPressureChartOptions" :series="financialPressureChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-slate-900 to-slate-700 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <ChartBarIcon class="h-5 w-5" />
              Estado Global do Cliente
            </h2>
          </div>

          <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
              <div class="rounded-lg bg-emerald-50 p-4">
                <p class="text-sm font-medium text-emerald-900">Propostas aceites</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">{{ customerState?.summary?.accepted_proposals || 0 }}</p>
              </div>
              <div class="rounded-lg bg-amber-50 p-4">
                <p class="text-sm font-medium text-amber-900">Amostras em curso</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ customerState?.summary?.samples_in_progress || 0 }}</p>
              </div>
              <div class="rounded-lg bg-rose-50 p-4">
                <p class="text-sm font-medium text-rose-900">Faturas em aberto</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ customerState?.summary?.open_invoices || 0 }}</p>
                <p class="mt-1 text-xs text-rose-700">AOA {{ formatCurrency(customerState?.summary?.open_amount_due || 0) }}</p>
              </div>
              <div class="rounded-lg bg-cyan-50 p-4">
                <p class="text-sm font-medium text-cyan-900">Pedidos do portal</p>
                <p class="mt-2 text-2xl font-bold text-cyan-900">{{ customerState?.summary?.open_requests || 0 }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
              <div>
                <h3 class="text-sm font-semibold text-gray-900">Amostras recentes</h3>
                <div class="mt-3 space-y-3">
                  <div v-for="sample in customerState?.recent_samples || []" :key="sample.id" class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                      <div>
                        <p class="text-sm font-semibold text-gray-900">{{ sample.name }}</p>
                        <p class="text-xs text-gray-500">{{ sample.code || 'Sem código' }}</p>
                      </div>
                      <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                        {{ sample.status }}
                      </span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Recebida em {{ formatDate(sample.received_at) }}</p>
                  </div>
                  <p v-if="!(customerState?.recent_samples || []).length" class="text-sm text-gray-500">Sem amostras recentes.</p>
                </div>
              </div>

              <div>
                <h3 class="text-sm font-semibold text-gray-900">Pedidos recentes</h3>
                <div class="mt-3 space-y-3">
                  <div v-for="request in customerState?.recent_requests || []" :key="request.id" class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm font-semibold text-gray-900">{{ request.title }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ request.reference || 'Sem referência' }} · {{ request.request_type }}</p>
                    <p class="mt-2 text-xs text-gray-600">Estado: {{ request.status }}</p>
                  </div>
                  <p v-if="!(customerState?.recent_requests || []).length" class="text-sm text-gray-500">Sem pedidos recentes.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CUSTOMER INFORMATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.customer_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- NAME FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.name') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900 font-medium">{{ record.data?.name || '—' }}</p>
                </div>
              </div>

              <!-- CODE FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.code') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900 font-medium">{{ record.data?.code || '—' }}</p>
                </div>
              </div>

              <!-- DESCRIPTION FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.description') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900">{{ record.data?.description || '—' }}</p>
                </div>
              </div>

              <!-- CATEGORY FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.category_id') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900">{{ record.data?.category || '—' }}</p>
                </div>
              </div>

              <!-- CREATED AT FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.created_at') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900">
                    {{ formatDate(record.data?.created_at) }}
                  </p>
                </div>
              </div>

              <!-- UPDATED AT FIELD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.customers.updated_at') }}
                </label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-sm text-gray-900">
                    {{ formatDate(record.data?.updated_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- WAREHOUSES SECTION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <BuildingOfficeIcon class="h-5 w-5 text-blue-900" />
                {{ $t('gestlab.general.labels.customers.warehouses') }}
                <span class="text-sm font-normal text-gray-500 ml-2">
                  ({{ record.data?.warehouses?.length || 0 }} {{ $t('gestlab.general.labels.customers.items') }})
                </span>
              </h2>
              <span v-if="record.data?.warehouse_id" class="text-sm font-medium text-green-700 bg-green-50 px-3 py-1 rounded-full">
                {{ $t('gestlab.general.labels.customers.primary_warehouse_set') }}
              </span>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="!record.data?.warehouses || record.data?.warehouses.length === 0" class="p-12 text-center">
            <BuildingOffice2Icon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.customers.no_warehouses_found') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.customers.no_warehouses_for_customer') }}
            </p>
            <Link
              as="button"
              :href="route('customers.edit', { customer: record.data?.id })"
              class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.add_warehouses') }}
          </Link>
          </div>

          <!-- WAREHOUSES GRID -->
          <div v-else class="grid grid-cols-1 lg:grid-cols-1 gap-6 p-6">
            <div 
              v-for="(warehouse, index) in record.data?.warehouses"
              :key="warehouse.id"
              :class="[
                'group relative bg-white rounded-lg border overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md',
                warehouse.id === record.data?.warehouse_id 
                  ? 'border-green-300 hover:border-green-400' 
                  : 'border-gray-200 hover:border-blue-900'
              ]"
              v-motion
              :initial="{ opacity: 0, y: 20 }"
              :enter="{ opacity: 1, y: 0 }"
              :delay="index * 50"
            >
              <!-- PRIMARY WAREHOUSE BADGE -->
              <div v-if="warehouse.id === record.data?.warehouse_id" class="absolute top-0 right-0 z-10">
                <span class="inline-flex items-center rounded-bl-lg bg-green-500 px-3 py-1 text-xs font-semibold text-white">
                  <StarIcon class="h-3 w-3 mr-1" />
                  {{ $t('gestlab.general.status.primary') }}
                </span>
              </div>

              <!-- WAREHOUSE HEADER -->
              <div :class="[
                'px-4 py-3 border-b',
                warehouse.id === record.data?.warehouse_id 
                  ? 'bg-gradient-to-r from-green-50 to-white border-green-200' 
                  : 'bg-gradient-to-r from-blue-50 to-white border-gray-200'
              ]">
                <div class="flex items-center gap-3">
                  <div :class="[
                    'flex h-8 w-8 items-center justify-center rounded-lg',
                    warehouse.id === record.data?.warehouse_id 
                      ? 'bg-green-600' 
                      : 'bg-blue-900'
                  ]">
                    <BuildingOfficeIcon class="h-5 w-5 text-white" />
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ warehouse.name || $t('gestlab.general.labels.warehouses.warehouse') }}
                    </h3>
                    <div class="flex items-center gap-2 mt-1">
                      <span v-if="warehouse.code" class="text-xs text-blue-900 font-medium bg-blue-50 px-2 py-0.5 rounded-full">
                        {{ warehouse.code }}
                      </span>
                      <span class="text-xs text-gray-500">
                        #{{ warehouse.id }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- WAREHOUSE DETAILS -->
              <div class="p-4">
                <div class="space-y-4">
                  <!-- BASIC INFO -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        {{ $t('gestlab.general.labels.warehouses.contact_info') }}
                      </h4>
                      <div class="space-y-2">
                        <div v-if="warehouse.email" class="flex items-start gap-2">
                          <EnvelopeIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                          <span class="text-sm text-gray-900">{{ warehouse.email }}</span>
                        </div>
                        <div v-if="warehouse.primary_phone" class="flex items-start gap-2">
                          <PhoneIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                          <span class="text-sm text-gray-900">{{ warehouse.primary_phone }}</span>
                        </div>
                        <div v-if="warehouse.alternative_phone" class="flex items-start gap-2">
                          <PhoneIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                          <span class="text-sm text-gray-900">{{ warehouse.alternative_phone }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- LOCATION INFO -->
                    <div>
                      <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        {{ $t('gestlab.general.labels.warehouses.location') }}
                      </h4>
                      <div class="space-y-2">
                        <div v-if="warehouse.address" class="flex items-start gap-2">
                          <MapPinIcon class="h-4 w-4 text-gray-400 mt-0.5" />
                          <span class="text-sm text-gray-900">{{ warehouse.address }}</span>
                        </div>
                        <div v-if="warehouse.municipality" class="text-sm text-gray-900">
                          {{ warehouse.municipality }}
                          <span v-if="warehouse.province">, {{ warehouse.province }}</span>
                        </div>
                        <div v-if="warehouse.nif" class="text-sm text-gray-700 mt-2">
                          <span class="font-medium">{{ $t('gestlab.general.labels.warehouses.nif') }}:</span> {{ warehouse.nif }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- FOCAL POINT -->
                  <div v-if="warehouse.focal_point" class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                      {{ $t('gestlab.general.labels.warehouses.focal_point') }}
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-900">{{ warehouse.focal_point }}</p>
                        <p v-if="warehouse.focal_point_contact" class="text-sm text-gray-700">
                          {{ warehouse.focal_point_contact }}
                        </p>
                      </div>
                      <div v-if="warehouse.focal_point_email" class="text-sm text-blue-900">
                        {{ warehouse.focal_point_email }}
                      </div>
                    </div>
                  </div>

                  <!-- DESCRIPTION -->
                  <div v-if="warehouse.description" class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                      {{ $t('gestlab.general.labels.description') }}
                    </h4>
                    <p class="text-sm text-gray-700 leading-relaxed">
                      {{ warehouse.description }}
                    </p>
                  </div>

                  <!-- ACTION BUTTONS -->
                  <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                      <div class="text-xs text-gray-500">
                        {{ $t('gestlab.general.labels.warehouses.last_updated') }}: {{ formatDate(warehouse.updated_at) }}
                      </div>
                      <div class="flex items-center gap-2">
                        <Link
                          as="button"
                          :href="route('warehouses.edit', { warehouse: warehouse.id })"
                          class="inline-flex items-center gap-1 rounded-lg border border-blue-900 bg-white px-3 py-1.5 text-xs font-semibold text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                        >
                          <PencilSquareIcon class="h-3 w-3" />
                          {{ $t('gestlab.general.buttons.edit') }}
                      </Link>
                        <Link
                          as="button"
                          :href="route('warehouses.show', { warehouse: warehouse.id })"
                          class="inline-flex items-center gap-1 rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
                        >
                          <EyeIcon class="h-3 w-3" />
                          {{ $t('gestlab.general.buttons.view') }}
                      </Link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- STATISTICS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChartBarIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.customers.statistics') }}
          </h3>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm font-medium text-blue-900">
                  {{ $t('gestlab.general.labels.customers.total_warehouses') }}
                </p>
                <p class="text-2xl font-bold text-blue-900 mt-1">
                  {{ record.data?.warehouses?.length || 0 }}
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.customers.primary_warehouse') }}
                </p>
                <p class="text-lg font-bold text-gray-900 mt-1">
                  <span v-if="record.data?.warehouse_id" class="text-green-600">
                    {{ $t('gestlab.general.status.set') }}
                  </span>
                  <span v-else class="text-yellow-600">
                    {{ $t('gestlab.general.labels.customers.not_set') }}
                  </span>
                </p>
              </div>
            </div>

            <!-- WAREHOUSE BREAKDOWN -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">
                {{ $t('gestlab.general.labels.customers.warehouse_types') }}
              </h4>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.primary_warehouses') }}</span>
                  <span class="text-sm font-semibold text-green-600">
                    {{ record.data?.warehouse_id ? 1 : 0 }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.secondary_warehouses') }}</span>
                  <span class="text-sm font-semibold text-blue-900">
                    {{ Math.max((record.data?.warehouses?.length || 0) - (record.data?.warehouse_id ? 1 : 0), 0) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.quick_actions') }}
          </h3>
          <div class="space-y-3">
            <Link
              as="button"
              :href="route('customers.edit', { customer: record.data?.id })"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PencilSquareIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.edit_customer') }}
          </Link>
            
            <Link
              as="button"
              :href="route('warehouses.create')"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-blue-900 bg-white px-4 py-2.5 text-sm font-semibold text-blue-900 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.customers.new_warehouse') }}
          </Link>

            <!-- DIVIDER -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.customers.customer_id') }}
              </h4>
              <div class="flex items-center justify-between">
                <code class="text-sm font-mono text-gray-700 bg-gray-50 px-2 py-1 rounded">
                  #{{ record.data?.id }}
                </code>
                <button 
                  @click="copyToClipboard(record.data?.id)"
                  class="text-gray-400 hover:text-blue-900 transition-colors duration-200"
                  :title="$t('gestlab.general.labels.customers.copy_id')"
                >
                  <ClipboardDocumentIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- METADATA CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.customers.metadata') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.created_by') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ record.data?.created_by?.name || '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.customers.updated_by') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ record.data?.updated_by?.name || '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.status') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.status === 'active' 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-red-100 text-red-800'
              ]">
                {{ record.data?.status || 'active' }}
              </span>
            </div>
            <div class="pt-2 text-xs text-gray-500">
              <p>{{ $t('gestlab.general.labels.customers.last_synced') }}: {{ formatDate(new Date()) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { computed, ref, onMounted } from "vue";
import { router, Link } from "@inertiajs/vue3";
import { 
  BuildingStorefrontIcon,
  UserCircleIcon,
  BuildingOfficeIcon,
  BuildingOffice2Icon,
  PencilSquareIcon,
  PlusCircleIcon,
  StarIcon,
  PhoneIcon,
  MapPinIcon,
  EnvelopeIcon,
  EyeIcon,
  ChartBarIcon,
  ClipboardDocumentIcon,
  InformationCircleIcon
} from "@heroicons/vue/24/outline";
import { trans } from 'laravel-vue-i18n';

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object,
  customerState: Object,
  charts: {
    type: Object,
    default: () => ({})
  }
});

const customerState = props.customerState || {};

const commercialHealthChartSeries = computed(() => [
  {
    name: 'Indicadores',
    data: props.charts?.commercial_health?.series || [],
  }
]);

const commercialHealthTotal = computed(() => commercialHealthChartSeries.value[0]?.data?.reduce((sum, value) => sum + value, 0) || 0);

const executionMixChartSeries = computed(() => props.charts?.execution_mix?.series || []);
const executionMixTotal = computed(() => executionMixChartSeries.value.reduce((sum, value) => sum + value, 0));

const financialPressureChartSeries = computed(() => [
  {
    name: 'AOA',
    data: props.charts?.financial_pressure?.series || [],
  }
]);

const commercialHealthChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  plotOptions: {
    bar: {
      borderRadius: 10,
      columnWidth: '50%',
      distributed: true,
    },
  },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: props.charts?.commercial_health?.labels || [],
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  yaxis: {
    min: 0,
    forceNiceScale: true,
    labels: {
      style: { colors: '#6b7280' },
    },
  },
  colors: ['#10b981', '#ef4444', '#0ea5e9', '#f59e0b'],
  legend: { show: false },
}));

const executionMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  labels: props.charts?.execution_mix?.labels || [],
  colors: ['#f59e0b', '#16a34a', '#7c3aed', '#1d4ed8'],
  stroke: {
    colors: ['#ffffff'],
  },
  legend: {
    position: 'bottom',
    labels: { colors: '#334155' },
  },
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  plotOptions: {
    pie: {
      donut: {
        size: '68%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Execução',
            formatter: () => `${executionMixTotal.value}`,
          },
        },
      },
    },
  },
}));

const financialPressureChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 8,
      barHeight: '55%',
      distributed: true,
    },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.financial_pressure?.labels || [],
    labels: {
      style: { colors: '#6b7280' },
      formatter: (value) => Number(value).toLocaleString('pt-PT'),
    },
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4,
  },
  colors: ['#dc2626', '#f59e0b'],
  legend: { show: false },
  tooltip: {
    x: { show: true },
    y: {
      formatter: (value) => `AOA ${formatCurrency(value)}`,
    },
  },
}));

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatCurrency = (amount) => {
  return Number(amount || 0).toLocaleString('pt-PT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// Copy to clipboard function
const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text.toString()).then(() => {
    // You could add a toast notification here
    console.log('Copied to clipboard:', text);
  });
};

onMounted(() => {
  // Any initialization logic
});
</script>
