<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate overflow-hidden px-6 py-8 text-slate-950 dark:text-white sm:px-8">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(var(--color-primary-500),0.22),transparent_32%),linear-gradient(135deg,rgba(248,250,252,1),rgba(236,254,255,0.82))] dark:bg-[radial-gradient(circle_at_top_left,rgba(var(--color-primary-400),0.18),transparent_34%),linear-gradient(135deg,rgba(15,23,42,1),rgba(8,47,73,0.82))]"></div>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <span class="inline-flex rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-primary-800 dark:border-primary-400/20 dark:bg-primary-400/10 dark:text-primary-200">
              ISO 17025 - garantia da validade dos resultados
            </span>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
              Ensaios de proficiência e comparações interlaboratoriais
            </h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300">
              Planeie rondas externas, acompanhe prazos, registe z-score, gere ações corretivas e mantenha evidência pronta para auditorias.
            </p>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400"
            @click="openCreate"
          >
            <PlusIcon class="h-5 w-5" />
            Novo programa
          </button>
        </div>
      </div>

      <div class="grid gap-4 border-t border-slate-200 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/40 sm:grid-cols-2 xl:grid-cols-4 sm:px-8">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
          <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ card.label }}</p>
            <component :is="card.icon" class="h-5 w-5 text-primary-600 dark:text-primary-300" />
          </div>
          <p class="mt-3 text-3xl font-semibold text-slate-950 dark:text-white">{{ card.value }}</p>
          <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ card.caption }}</p>
        </article>
      </div>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),16rem,16rem,auto]">
        <div class="relative">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
          <input
            v-model="filters.search"
            type="search"
            placeholder="Pesquisar por programa, provedor ou ronda"
            class="block w-full rounded-2xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 shadow-sm transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100 dark:placeholder:text-slate-500"
          />
        </div>

        <ComboboxEnhanced
          v-model="selectedScheme"
          :options="schemeFilterOptions"
          placeholder="Todos os tipos"
        />

        <ComboboxEnhanced
          v-model="selectedStatus"
          :options="statusFilterOptions"
          placeholder="Todos os estados"
        />

        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          @click="clearFilters"
        >
          Limpar
        </button>
      </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Estado das rondas</h3>
        <ChartWrapper class="mt-4" type="bar" height="260" :series="statusChartSeries" :options="statusChartOptions" />
      </article>
      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Resultados</h3>
        <ChartWrapper class="mt-4" type="donut" height="260" :series="outcomeChartSeries" :options="outcomeChartOptions" />
      </article>
      <article class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <h3 class="text-sm font-semibold text-slate-950 dark:text-white">Papel do laboratório</h3>
        <ChartWrapper class="mt-4" type="donut" height="260" :series="roleChartSeries" :options="roleChartOptions" />
      </article>
    </section>

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Monitorização operacional</h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Prazos, resultados e evidências críticas num único quadro.</p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          {{ records.length }} registos nesta página
        </span>
      </div>

      <div v-if="records.length">
        <div class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50/80 dark:bg-slate-950/60">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Programa</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Provedor</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prazo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</th>
                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Resultado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-for="item in records" :key="item.id" class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                <td class="max-w-sm px-6 py-5">
                  <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
                      <BeakerIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                      <Link :href="route('proficiency_tests.show', item.id)" class="truncate text-sm font-semibold text-slate-950 transition hover:text-primary-700 dark:text-white dark:hover:text-primary-300">{{ item.name }}</Link>
                      <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ item.round_reference || 'Sem referência' }}</p>
                      <div class="mt-2 flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ schemeLabel(item.scheme_type) }}</span>
                        <span class="inline-flex rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">{{ roleLabel(item.role) }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5 text-sm text-slate-700 dark:text-slate-300">{{ item.provider_name || '—' }}</td>
                <td class="px-6 py-5">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(item.deadline_date || item.date) }}</p>
                  <p :class="deadlineTextClass(item.deadline_state)" class="mt-1 text-xs font-semibold">{{ deadlineLabel(item) }}</p>
                </td>
                <td class="px-6 py-5">
                  <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset" :class="statusBadgeClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-6 py-5">
                  <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ outcomeLabel(item.outcome) }}</p>
                  <p v-if="item.z_score !== null && item.z_score !== ''" class="mt-1 text-xs text-slate-500 dark:text-slate-400">z-score {{ item.z_score }}</p>
                </td>
                <td class="px-6 py-5">
                  <div class="flex justify-end gap-2">
                    <Link :href="route('proficiency_tests.show', item.id)" class="rounded-xl border border-primary-200 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-500/40 dark:text-primary-200 dark:hover:bg-primary-500/10">Abrir</Link>
                    <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" @click="openEdit(item)">Editar</button>
                    <button
                      v-if="!item.deleted"
                      type="button"
                      class="rounded-xl border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10"
                      @click="destroy(item)"
                    >
                      Arquivar
                    </button>
                    <button
                      v-else
                      type="button"
                      class="rounded-xl border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                      @click="restore(item)"
                    >
                      Restaurar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-slate-200 dark:divide-slate-800 lg:hidden">
          <article v-for="item in records" :key="item.id" class="space-y-4 px-5 py-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ item.name }}</p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ item.provider_name || '—' }}</p>
              </div>
              <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset" :class="statusBadgeClass(item.status)">
                {{ statusLabel(item.status) }}
              </span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Ronda</p>
                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ item.round_reference || 'Sem referência' }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prazo</p>
                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ formatDate(item.deadline_date || item.date) }}</p>
                <p :class="deadlineTextClass(item.deadline_state)" class="mt-1 text-xs font-semibold">{{ deadlineLabel(item) }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo</p>
                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ schemeLabel(item.scheme_type) }} - {{ roleLabel(item.role) }}</p>
              </div>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Resultado</p>
                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ outcomeLabel(item.outcome) }}</p>
              </div>
            </div>
            <div class="flex gap-2">
              <Link :href="route('proficiency_tests.show', item.id)" class="flex-1 rounded-2xl border border-primary-200 px-4 py-3 text-center text-sm font-semibold text-primary-700 transition hover:bg-primary-50 dark:border-primary-500/40 dark:text-primary-200 dark:hover:bg-primary-500/10">Abrir</Link>
              <button type="button" class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" @click="openEdit(item)">Editar</button>
              <button
                v-if="!item.deleted"
                type="button"
                class="flex-1 rounded-2xl border border-rose-300 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-500/10"
                @click="destroy(item)"
              >
                Arquivar
              </button>
              <button
                v-else
                type="button"
                class="flex-1 rounded-2xl border border-emerald-300 px-4 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                @click="restore(item)"
              >
                Restaurar
              </button>
            </div>
          </article>
        </div>
      </div>

      <div v-else class="px-6 py-16 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
          <BeakerIcon class="h-7 w-7" />
        </div>
        <p class="mt-5 text-lg font-semibold text-slate-950 dark:text-white">Sem programas registados</p>
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-400">Crie o primeiro ensaio de proficiência ou interlaboratorial e acompanhe-o até ao encerramento.</p>
      </div>
    </section>

    <div v-if="paginationLinks.length" class="flex flex-wrap gap-2">
      <Link
        v-for="link in paginationLinks"
        :key="`${link.label}-${link.url}`"
        :href="link.url || '#'"
        class="rounded-xl border px-3 py-2 text-sm transition"
        :class="link.active ? 'border-primary-600 bg-primary-600 text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'"
        preserve-scroll
        v-html="link.label"
      />
    </div>

    <TransitionRoot as="template" :show="showEditor">
      <Dialog as="div" class="relative z-50" @close="closeEditor">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" />
        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <DialogPanel class="w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
              <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-5 dark:border-slate-800 dark:bg-slate-950/40">
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">{{ editorTitle }}</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Registe planeamento, prazo, resultado e ações corretivas associadas.</p>
              </div>

              <form class="space-y-6 px-6 py-6" @submit.prevent="submit">
                <div class="grid gap-5 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Nome</label>
                    <input v-model="form.name" type="text" class="form-field" />
                    <p v-if="form.errors.name" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                  </div>
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Provedor</label>
                    <input v-model="form.provider_name" type="text" class="form-field" />
                    <p v-if="form.errors.provider_name" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.provider_name }}</p>
                  </div>
                  <ComboboxEnhanced
                    v-model="selectedFormScheme"
                    :options="schemeFormOptions"
                    title-label="Tipo"
                    placeholder="Selecionar tipo"
                    :has-error="Boolean(form.errors.scheme_type)"
                  />
                  <ComboboxEnhanced
                    v-model="selectedFormRole"
                    :options="roleFormOptions"
                    title-label="Papel do laboratório"
                    placeholder="Selecionar papel"
                    :has-error="Boolean(form.errors.role)"
                  />
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Ronda / referência</label>
                    <input v-model="form.round_reference" type="text" class="form-field" />
                    <p v-if="form.errors.round_reference" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.round_reference }}</p>
                  </div>
                  <ComboboxEnhanced
                    v-model="selectedFormStatus"
                    :options="statusFormOptions"
                    title-label="Estado"
                    placeholder="Selecionar estado"
                    :has-error="Boolean(form.errors.status)"
                  />
                  <ComboboxEnhanced
                    v-model="selectedFormOutcome"
                    :options="outcomeFormOptions"
                    title-label="Resultado"
                    placeholder="Selecionar resultado"
                    :has-error="Boolean(form.errors.outcome)"
                  />
                  <DatePickerEnhanced
                    v-model="form.date"
                    label="Data base"
                    :required="true"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.date)"
                    :error-message="form.errors.date"
                  />
                  <DatePickerEnhanced
                    v-model="form.scheduled_at"
                    label="Prazo / data prevista"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.scheduled_at)"
                    :error-message="form.errors.scheduled_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.enrollment_deadline_at"
                    label="Prazo de inscrição"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.enrollment_deadline_at)"
                    :error-message="form.errors.enrollment_deadline_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.submission_deadline_at"
                    label="Prazo de submissão"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.submission_deadline_at)"
                    :error-message="form.errors.submission_deadline_at"
                  />
                  <DatePickerEnhanced
                    v-model="form.closed_at"
                    label="Data de fecho"
                    :is-dark="isDarkMode"
                    :has-error="Boolean(form.errors.closed_at)"
                    :error-message="form.errors.closed_at"
                  />
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">z-score</label>
                    <input v-model="form.z_score" type="number" step="0.01" class="form-field" />
                    <p v-if="form.errors.z_score" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.z_score }}</p>
                  </div>
                </div>

                <div v-if="form.role === 'organizer'" class="rounded-[1.5rem] border border-primary-100 bg-primary-50/60 p-5 dark:border-primary-500/20 dark:bg-primary-500/10">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-primary-950 dark:text-primary-100">Modo organizador</h3>
                      <p class="mt-1 text-sm text-primary-800/80 dark:text-primary-200/80">Registe participantes e parâmetros para que a ronda possa receber resultados estruturados.</p>
                    </div>
                    <div class="flex gap-2">
                      <button type="button" class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-primary-800 shadow-sm dark:bg-slate-900 dark:text-primary-100" @click="addParticipant">Adicionar participante</button>
                      <button type="button" class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-primary-800 shadow-sm dark:bg-slate-900 dark:text-primary-100" @click="addParameter">Adicionar parâmetro</button>
                    </div>
                  </div>

                  <div class="mt-5 space-y-2">
                    <label class="block text-sm font-medium text-primary-950 dark:text-primary-100">Organizador responsável</label>
                    <input v-model="form.organizer_name" type="text" class="form-field" placeholder="Nome da unidade, laboratório ou coordenação organizadora" />
                    <p v-if="form.errors.organizer_name" class="text-xs font-medium text-red-600 dark:text-red-400">{{ form.errors.organizer_name }}</p>
                  </div>

                  <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <div class="space-y-3">
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-800 dark:text-primary-200">Participantes</p>
                      <div v-for="(participant, index) in form.participants" :key="`participant-${index}`" class="grid gap-2 rounded-2xl bg-white p-3 dark:bg-slate-900 sm:grid-cols-[0.9fr,1.4fr,1fr,auto]">
                        <input v-model="participant.code" class="form-field" placeholder="Código" />
                        <input v-model="participant.name" class="form-field" placeholder="Laboratório / participante" />
                        <select v-model="participant.status" class="form-field">
                          <option v-for="option in participantStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <button type="button" class="rounded-xl px-3 text-xs font-semibold text-rose-600 dark:text-rose-300" @click="removeParticipant(index)">Remover</button>
                      </div>
                    </div>
                    <div class="space-y-3">
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-800 dark:text-primary-200">Parâmetros</p>
                      <div v-for="(parameter, index) in form.parameters" :key="`parameter-${index}`" class="grid gap-2 rounded-2xl bg-white p-3 dark:bg-slate-900 sm:grid-cols-[1fr,1.4fr,0.8fr,auto]">
                        <input v-model="parameter.code" class="form-field" placeholder="Código" />
                        <input v-model="parameter.name" class="form-field" placeholder="Parâmetro" />
                        <input v-model="parameter.unit" class="form-field" placeholder="Unidade" />
                        <button type="button" class="rounded-xl px-3 text-xs font-semibold text-rose-600 dark:text-rose-300" @click="removeParameter(index)">Remover</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                  <BaseTextarea v-model="form.scope" label="Escopo" :rows="3" :error="form.errors.scope" />
                  <BaseTextarea v-model="form.corrective_actions" label="Ações corretivas" :rows="3" :error="form.errors.corrective_actions" />
                </div>

                <BaseTextarea v-model="form.notes" label="Notas e evidências" :rows="3" :error="form.errors.notes" />

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                  <button type="button" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" @click="closeEditor">Cancelar</button>
                  <button type="submit" class="rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400" :disabled="form.processing">
                    {{ form.processing ? 'A guardar...' : 'Guardar programa' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import ChartWrapper from '@/Components/apex-chart/ChartWrapper.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import DatePickerEnhanced from '@/Components/date-picker-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  BeakerIcon,
  BellAlertIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import { Dialog, DialogPanel, TransitionRoot } from '@headlessui/vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  record: { type: Object, required: true },
  query: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  schemeOptions: { type: Array, default: () => [] },
  roleOptions: { type: Array, default: () => [] },
  charts: { type: Object, default: () => ({}) },
})

const records = computed(() => props.record?.data ?? [])
const paginationLinks = computed(() => props.record?.meta?.links ?? props.record?.links ?? [])

const filters = useForm({
  search: props.query?.search || '',
  status: props.query?.status || '',
  scheme_type: props.query?.scheme_type || '',
  filter: props.query?.filter || '',
})

let filterTimer = null
watch(() => [filters.search, filters.status, filters.scheme_type], () => {
  window.clearTimeout(filterTimer)
  filterTimer = window.setTimeout(() => {
    router.get(route('proficiency_tests.index'), filters.data(), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 250)
}, { deep: true })

const isDarkMode = computed(() => {
  if (typeof document === 'undefined') {
    return false
  }

  return document.documentElement.classList.contains('dark')
})

const schemeFormOptions = computed(() => props.schemeOptions.map((value) => ({ value, label: schemeLabel(value) })))
const roleFormOptions = computed(() => props.roleOptions.map((value) => ({ value, label: roleLabel(value) })))
const participantStatusOptions = computed(() => ['pending', 'enrolled', 'submitted', 'reviewed', 'requires_action'].map((value) => ({ value, label: participantStatusLabel(value) })))
const statusFormOptions = computed(() => props.statusOptions.map((value) => ({ value, label: statusLabel(value) })))
const outcomeFormOptions = computed(() => ['pending', 'satisfactory', 'questionable', 'unsatisfactory'].map((value) => ({ value, label: outcomeLabel(value) })))
const schemeFilterOptions = computed(() => [{ value: '', label: 'Todos os tipos' }, ...schemeFormOptions.value])
const statusFilterOptions = computed(() => [{ value: '', label: 'Todos os estados' }, ...statusFormOptions.value])

const optionProxy = (field, options, target = filters) => computed({
  get() {
    return options.value.find((option) => option.value === target[field]) ?? null
  },
  set(option) {
    target[field] = option?.value ?? ''
  },
})

const selectedScheme = optionProxy('scheme_type', schemeFilterOptions)
const selectedStatus = optionProxy('status', statusFilterOptions)

const summaryCards = computed(() => {
  const total = records.value.length
  const closed = records.value.filter((item) => item.status === 'closed').length
  const attention = records.value.filter((item) => ['questionable', 'unsatisfactory'].includes(item.outcome) || ['overdue', 'due_soon'].includes(item.deadline_state)).length
  const interlaboratory = records.value.filter((item) => item.scheme_type === 'interlaboratory').length

  return [
    { label: 'Programas visíveis', value: total, caption: 'Janela filtrada para acompanhamento operacional.', icon: BeakerIcon },
    { label: 'Encerrados', value: closed, caption: 'Rondas com evidência e fecho registados.', icon: CheckBadgeIcon },
    { label: 'Requer atenção', value: attention, caption: 'Prazos próximos, vencidos ou resultados críticos.', icon: BellAlertIcon },
    { label: 'Interlaboratoriais', value: interlaboratory, caption: 'Comparações externas entre laboratórios.', icon: ExclamationTriangleIcon },
  ]
})

const showEditor = ref(false)
const editingRecordId = ref(null)
const form = useForm({
  name: '',
  scheme_type: 'proficiency',
  role: 'participant',
  provider_name: '',
  organizer_name: '',
  participants: [],
  parameters: [],
  round_reference: '',
  status: 'planned',
  date: '',
  scheduled_at: '',
  enrollment_deadline_at: '',
  submission_deadline_at: '',
  closed_at: '',
  scope: '',
  outcome: 'pending',
  z_score: '',
  corrective_actions: '',
  notes: '',
  results: [],
  participant_results: [],
})

const selectedFormScheme = optionProxy('scheme_type', schemeFormOptions, form)
const selectedFormRole = optionProxy('role', roleFormOptions, form)
const selectedFormStatus = optionProxy('status', statusFormOptions, form)
const selectedFormOutcome = optionProxy('outcome', outcomeFormOptions, form)
const editorTitle = computed(() => editingRecordId.value ? 'Editar programa' : 'Novo programa')

const chartTheme = computed(() => ({
  chart: { toolbar: { show: false }, foreColor: isDarkMode.value ? '#cbd5e1' : '#475569' },
  grid: { borderColor: isDarkMode.value ? '#334155' : '#e2e8f0' },
  legend: { labels: { colors: isDarkMode.value ? '#cbd5e1' : '#475569' } },
}))

const statusChartSeries = computed(() => [{ name: 'Programas', data: props.charts?.status?.series ?? [] }])
const statusChartOptions = computed(() => ({
  ...chartTheme.value,
  xaxis: { categories: props.charts?.status?.labels ?? [] },
  colors: ['#0ea5e9'],
}))
const outcomeChartSeries = computed(() => props.charts?.outcome?.series ?? [])
const outcomeChartOptions = computed(() => ({
  ...chartTheme.value,
  labels: props.charts?.outcome?.labels ?? [],
  colors: ['#94a3b8', '#10b981', '#f59e0b', '#ef4444'],
}))
const roleChartSeries = computed(() => props.charts?.role?.series ?? [])
const roleChartOptions = computed(() => ({
  ...chartTheme.value,
  labels: props.charts?.role?.labels ?? [],
  colors: ['#6366f1', '#14b8a6'],
}))

function schemeLabel(value) {
  return {
    proficiency: 'Proficiência',
    interlaboratory: 'Interlaboratorial',
  }[value] || value || '—'
}

function roleLabel(value) {
  return {
    participant: 'Participante',
    organizer: 'Organizador',
  }[value] || value || '—'
}

function participantStatusLabel(value) {
  return {
    pending: 'Pendente',
    enrolled: 'Inscrito',
    submitted: 'Submetido',
    reviewed: 'Revisto',
    requires_action: 'Requer ação',
  }[value] || 'Pendente'
}

function statusLabel(value) {
  return {
    planned: 'Planeado',
    in_progress: 'Em curso',
    completed: 'Concluído',
    reviewed: 'Revisto',
    closed: 'Fechado',
  }[value] || value || '—'
}

function outcomeLabel(value) {
  return {
    pending: 'Pendente',
    satisfactory: 'Satisfatório',
    questionable: 'Questionável',
    unsatisfactory: 'Insatisfatório',
  }[value] || 'Pendente'
}

function statusBadgeClass(status) {
  return {
    planned: 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
    in_progress: 'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/20',
    completed: 'bg-blue-100 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/20',
    reviewed: 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/20',
    closed: 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/20',
  }[status] || 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700'
}

function deadlineTextClass(state) {
  return {
    overdue: 'text-rose-600 dark:text-rose-300',
    due_soon: 'text-amber-600 dark:text-amber-300',
    on_track: 'text-emerald-600 dark:text-emerald-300',
    closed: 'text-slate-500 dark:text-slate-400',
  }[state] || 'text-slate-500 dark:text-slate-400'
}

function deadlineLabel(item) {
  if (item.deadline_state === 'closed') {
    return 'Encerrado'
  }

  if (item.deadline_state === 'overdue') {
    return 'Vencido'
  }

  if (item.deadline_state === 'due_soon') {
    return `${Math.max(Number(item.days_until_deadline ?? 0), 0)} dias restantes`
  }

  if (item.deadline_state === 'unscheduled') {
    return 'Sem prazo'
  }

  return 'No prazo'
}

function formatDate(value) {
  return value ? new Date(`${value}T00:00:00`).toLocaleDateString('pt-PT') : '—'
}

function resetForm() {
  form.defaults({
    name: '',
    scheme_type: 'proficiency',
    role: 'participant',
    provider_name: '',
    organizer_name: '',
    participants: [],
    parameters: [],
    round_reference: '',
    status: 'planned',
    date: '',
    scheduled_at: '',
    enrollment_deadline_at: '',
    submission_deadline_at: '',
    closed_at: '',
    scope: '',
    outcome: 'pending',
    z_score: '',
    corrective_actions: '',
    notes: '',
    results: [],
    participant_results: [],
  })
  form.reset()
  form.clearErrors()
}

function openCreate() {
  editingRecordId.value = null
  resetForm()
  showEditor.value = true
}

function openEdit(item) {
  editingRecordId.value = item.id
  form.defaults({
    name: item.name || '',
    scheme_type: item.scheme_type || 'proficiency',
    role: item.role || 'participant',
    provider_name: item.provider_name || '',
    organizer_name: item.organizer_name || '',
    participants: item.participants || [],
    parameters: item.parameters || [],
    round_reference: item.round_reference || '',
    status: item.status || 'planned',
    date: item.date || '',
    scheduled_at: item.scheduled_at || '',
    enrollment_deadline_at: item.enrollment_deadline_at || '',
    submission_deadline_at: item.submission_deadline_at || '',
    closed_at: item.closed_at || '',
    scope: item.scope || '',
    outcome: item.outcome || 'pending',
    z_score: item.z_score ?? '',
    corrective_actions: item.corrective_actions || '',
    notes: item.notes || '',
    results: item.results || [],
    participant_results: item.participant_results || [],
  })
  form.reset()
  form.clearErrors()
  showEditor.value = true
}

function closeEditor() {
  showEditor.value = false
  editingRecordId.value = null
  resetForm()
}

function submit() {
  if (editingRecordId.value) {
    form.put(route('proficiency_tests.update', { test: editingRecordId.value }), {
      preserveScroll: true,
      onSuccess: closeEditor,
    })

    return
  }

  form.post(route('proficiency_tests.store'), {
    preserveScroll: true,
    onSuccess: closeEditor,
  })
}

function destroy(item) {
  router.get(route('proficiency_tests.destroy', { recordIds: [item.id] }), {}, { preserveScroll: true })
}

function restore(item) {
  router.get(route('proficiency_tests.restore', { recordIds: [item.id] }), {}, { preserveScroll: true })
}

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.scheme_type = ''
  filters.filter = ''
}

function addParticipant() {
  form.participants.push({ code: '', name: '', contact: '', status: 'pending' })
}

function removeParticipant(index) {
  form.participants.splice(index, 1)
}

function addParameter() {
  form.parameters.push({ code: '', name: '', unit: '', assigned_value: '', standard_deviation: '' })
}

function removeParameter(index) {
  form.parameters.splice(index, 1)
}
</script>

<style scoped>
.form-field {
  display: block;
  width: 100%;
  border-radius: 1rem;
  border: 1px solid rgb(203 213 225);
  background-color: rgb(255 255 255);
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: rgb(15 23 42);
  box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
  transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.form-field:focus {
  border-color: rgb(var(--color-primary-500));
  box-shadow: 0 0 0 3px rgb(var(--color-primary-500) / 0.18);
  outline: none;
}

:global(.dark) .form-field {
  border-color: rgb(51 65 85);
  background-color: rgb(15 23 42 / 0.72);
  color: rgb(241 245 249);
}
</style>
