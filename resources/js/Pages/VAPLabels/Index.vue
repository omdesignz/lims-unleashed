<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[2.25rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_28px_90px_rgba(20,61,55,0.12)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_25rem]">
        <div class="relative isolate p-6 sm:p-8">
          <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.28),transparent_36%),linear-gradient(135deg,#fffdf7,#f7f1e7)] dark:bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.18),transparent_34%),linear-gradient(135deg,#07110f,#10231f)]" />
          <div class="inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
            Label Studio
          </div>
          <h1 class="mt-5 flex items-center gap-3 text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-[#f1d78b] shadow-[0_16px_40px_rgba(20,61,55,0.22)] dark:bg-[#f1d78b] dark:text-[#07110f]">
              <TagIcon class="h-6 w-6" />
            </span>
            {{ $t('gestlab.general.labels.vap_labels.title') }}
          </h1>
          <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            Desenhe, controle e imprima etiquetas para amostras, equipamentos, reagentes e materiais com dimensões, cores, QR, estado e laboratório associados.
          </p>
          <div class="mt-6 flex flex-wrap gap-3">
            <Link
              :href="route('vap_labels.labels.create')"
              class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#143d37] px-4 py-3 text-sm font-black text-white shadow-[0_16px_40px_rgba(20,61,55,0.20)] transition hover:bg-[#0f2f2a] dark:bg-[#f1d78b] dark:text-[#07110f] dark:hover:bg-[#f6e7bf]"
            >
              <PlusCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.create_label') }}
            </Link>
            <span class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] bg-white/80 px-4 py-3 text-sm font-black text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
              {{ stats.total }} {{ $t('gestlab.general.labels.vap_labels.total_labels') }}
            </span>
          </div>
        </div>
        <aside class="border-t border-[#ded3bf] bg-[#f7f1e7] p-6 dark:border-[#25443c] dark:bg-[#081512] lg:border-l lg:border-t-0">
          <div class="grid gap-4">
            <div
              v-for="statCard in labelStatCards"
              :key="statCard.label"
              class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]"
            >
              <p class="text-xs font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#83978d]">{{ statCard.label }}</p>
              <p class="mt-2 text-3xl font-black text-[#143d37] dark:text-[#f1d78b]">{{ statCard.value }}</p>
              <p class="mt-1 text-xs leading-5 text-[#475a53] dark:text-[#cbd8cf]">{{ statCard.hint }}</p>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-[0_24px_80px_rgba(20,61,55,0.09)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:p-7">
      <div class="mb-6 flex flex-col gap-2">
        <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">Filtro documental</p>
        <h2 class="text-xl font-black text-[#15231f] dark:text-[#f7f1e7]">Encontre a etiqueta certa antes de imprimir</h2>
      </div>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        <div class="space-y-2">
          <label class="block text-sm font-bold text-[#31413b] dark:text-[#d7e2dd]">
            {{ $t('gestlab.general.labels.vap_labels.search') }}
          </label>
          <input
            v-model="filters.search"
            type="search"
            :placeholder="$t('gestlab.general.labels.vap_labels.search_placeholder')"
            class="block w-full rounded-2xl border-[#d8cfbe] bg-white px-4 py-3 text-sm font-medium text-[#15231f] shadow-sm transition focus:border-[#143d37] focus:ring-4 focus:ring-[#143d37]/10 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-bold text-[#31413b] dark:text-[#d7e2dd]">
            {{ $t('gestlab.general.labels.vap_labels.type') }}
          </label>
          <select
            v-model="filters.type"
            class="block w-full rounded-2xl border-[#d8cfbe] bg-white px-4 py-3 text-sm font-medium text-[#15231f] shadow-sm transition focus:border-[#143d37] focus:ring-4 focus:ring-[#143d37]/10 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7]"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_types') }}</option>
            <option value="equipment">{{ $t('gestlab.general.labels.vap_labels.types.equipment') }}</option>
            <option value="material">{{ $t('gestlab.general.labels.vap_labels.types.material') }}</option>
            <option value="sample">{{ $t('gestlab.general.labels.vap_labels.types.sample') }}</option>
            <option value="custom">{{ $t('gestlab.general.labels.vap_labels.types.custom') }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-bold text-[#31413b] dark:text-[#d7e2dd]">
            {{ $t('gestlab.general.labels.vap_labels.lab') }}
          </label>
          <select
            v-model="filters.lab_id"
            class="block w-full rounded-2xl border-[#d8cfbe] bg-white px-4 py-3 text-sm font-medium text-[#15231f] shadow-sm transition focus:border-[#143d37] focus:ring-4 focus:ring-[#143d37]/10 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7]"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_labs') }}</option>
            <option v-for="lab in labs" :key="lab.id" :value="lab.id">
              {{ lab.name }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-bold text-[#31413b] dark:text-[#d7e2dd]">
            {{ $t('gestlab.general.labels.vap_labels.status') }}
          </label>
          <select
            v-model="filters.status"
            class="block w-full rounded-2xl border-[#d8cfbe] bg-white px-4 py-3 text-sm font-medium text-[#15231f] shadow-sm transition focus:border-[#143d37] focus:ring-4 focus:ring-[#143d37]/10 dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f7f1e7]"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.all_status') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_labels.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_labels.inactive') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STATS CARD -->
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-3xl border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-bold text-[#6b7b74] dark:text-[#a9bbb4]">{{ $t('gestlab.general.labels.vap_labels.total_labels') }}</p>
            <p class="mt-2 text-3xl font-black text-[#143d37] dark:text-[#f1d78b]">{{ stats.total }}</p>
          </div>
          <TagIcon class="h-10 w-10 text-primary-200 dark:text-primary-500/40" />
        </div>
      </div>
      <div class="rounded-3xl border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-bold text-[#6b7b74] dark:text-[#a9bbb4]">{{ $t('gestlab.general.labels.vap_labels.active_labels') }}</p>
            <p class="mt-2 text-3xl font-black text-emerald-600 dark:text-emerald-300">{{ stats.active }}</p>
          </div>
          <CheckCircleIcon class="h-10 w-10 text-green-200 dark:text-green-500/40" />
        </div>
      </div>
      <div v-for="typeStat in stats.by_type" :key="typeStat.type" class="rounded-3xl border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-sm dark:border-[#25443c] dark:bg-[#07110f]">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-bold text-[#6b7b74] dark:text-[#a9bbb4]">{{ $t('gestlab.general.labels.vap_labels.types.' + typeStat.type) }}</p>
            <p class="mt-2 text-3xl font-black text-[#15231f] dark:text-[#f7f1e7]">{{ typeStat.count }}</p>
          </div>
          <div class="h-10 w-10 rounded-full flex items-center justify-center" :class="typeColor(typeStat.type)">
            <TagIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- VISUAL GALLERY -->
    <section v-if="labels.data.length" class="rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-6 shadow-[0_24px_80px_rgba(20,61,55,0.09)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:p-7">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">Galeria operacional</p>
          <h2 class="mt-2 text-xl font-black text-[#15231f] dark:text-[#f7f1e7]">Pré-visualize antes de editar ou imprimir</h2>
          <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            As etiquetas aparecem como cartões físicos para facilitar validação visual rápida de cor, dimensão, tipo e destino.
          </p>
        </div>
        <Link
          :href="route('vap_labels.labels.create')"
          class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-white px-4 py-3 text-sm font-black text-[#143d37] shadow-sm transition hover:border-[#d9b05f] hover:bg-[#f7f1e7] dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f1d78b] dark:hover:bg-[#16342e]"
        >
          <PlusCircleIcon class="h-5 w-5" />
          Nova etiqueta
        </Link>
      </div>

      <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="label in labels.data.slice(0, 6)"
          :key="`gallery-${label.id}`"
          class="group overflow-hidden rounded-[1.75rem] border border-[#ded3bf] bg-white/78 shadow-sm transition hover:-translate-y-0.5 hover:border-[#d9b05f] hover:shadow-[0_24px_70px_rgba(20,61,55,0.13)] dark:border-[#25443c] dark:bg-[#081512]"
        >
          <div class="p-5">
            <div
              class="relative flex min-h-36 items-center justify-center overflow-hidden rounded-[1.35rem] border p-4 text-center shadow-inner"
              :style="labelPreviewStyle(label)"
            >
              <div class="absolute left-3 top-3 rounded-full bg-white/80 px-2 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-[#143d37] ring-1 ring-black/5">
                {{ label.width }} × {{ label.height }} mm
              </div>
              <p class="max-w-[15rem] whitespace-pre-line text-sm font-black leading-6">
                {{ labelContentPreview(label, 92) }}
              </p>
            </div>

            <div class="mt-4 flex items-start justify-between gap-3">
              <div>
                <h3 class="text-base font-black text-[#15231f] dark:text-[#f7f1e7]">{{ label.name }}</h3>
                <p class="mt-1 text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                  {{ label.lab?.name || 'Sem laboratório definido' }}
                </p>
              </div>
              <span :class="typeBadgeClass(label.type)">
                {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
              </span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2">
              <Link
                :href="route('vap_labels.labels.show', label.id)"
                class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] bg-[#f7f1e7] px-3 py-2 text-xs font-black text-[#143d37] transition hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f1d78b] dark:hover:bg-[#16342e]"
              >
                <EyeIcon class="h-4 w-4" />
              </Link>
              <Link
                :href="route('vap_labels.labels.edit', label.id)"
                class="inline-flex items-center justify-center rounded-2xl border border-[#ded3bf] bg-[#f7f1e7] px-3 py-2 text-xs font-black text-[#143d37] transition hover:bg-white dark:border-[#25443c] dark:bg-[#10231f] dark:text-[#f1d78b] dark:hover:bg-[#16342e]"
              >
                <PencilIcon class="h-4 w-4" />
              </Link>
              <button
                type="button"
                @click="previewPdf(label)"
                class="inline-flex items-center justify-center rounded-2xl bg-[#143d37] px-3 py-2 text-xs font-black text-white transition hover:bg-[#0f2f2a] dark:bg-[#f1d78b] dark:text-[#07110f] dark:hover:bg-[#f6e7bf]"
                title="Pré-visualizar PDF"
              >
                PDF
              </button>
            </div>
          </div>
        </article>
      </div>
    </section>

    <!-- LABELS LIST -->
    <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgba(20,61,55,0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="border-b border-[#ded3bf] bg-[#f7f1e7] px-6 py-5 dark:border-[#25443c] dark:bg-[#10231f]">
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-black text-[#15231f] dark:text-[#f7f1e7]">
            <ListBulletIcon class="h-5 w-5 text-[#143d37] dark:text-[#f1d78b]" />
            {{ $t('gestlab.general.labels.vap_labels.list') }}
            <span class="ml-2 rounded-full border border-[#ded3bf] bg-white px-3 py-1 text-xs font-bold text-[#6b7b74] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#a9bbb4]">
              ({{ labels.total }} {{ $t('gestlab.general.labels.vap_labels.general.items') }})
            </span>
          </h2>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="labels.data.length === 0" class="p-12 text-center">
        <TagIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          {{ $t('gestlab.general.labels.vap_labels.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.labels.vap_labels.empty_state.description') }}
        </p>
        <Link
          :href="route('vap_labels.labels.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_first_label') }}
        </Link>
      </div>

      <!-- LABELS TABLE -->
      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-[#f7f1e7]/90 dark:bg-[#10231f]/90">
              <tr>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.name') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.dimensions') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.lab') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.status') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#6b7b74] dark:text-[#a9bbb4]">
                  {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#ded3bf] bg-[#fffdf7] dark:divide-[#25443c] dark:bg-[#07110f]">
              <tr 
                v-for="label in labels.data" 
                :key="label.id"
                class="transition-colors duration-150 hover:bg-[#f7f1e7]/70 dark:hover:bg-[#10231f]/70"
                v-motion
                :initial="{ opacity: 0, y: 10 }"
                :enter="{ opacity: 1, y: 0 }"
                :delay="100"
              >
                <td class="whitespace-nowrap px-6 py-5">
                  <div class="flex items-center">
                    <div class="h-10 w-10 flex-shrink-0">
                      <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-sm ring-1 ring-black/5" :style="{ backgroundColor: label.background_color, color: label.text_color, border: `${label.border_width}px solid ${label.border_color}` }">
                        <TagIcon class="h-6 w-6" />
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ label.name }}
                      </div>
                      <div class="text-sm text-slate-500 dark:text-slate-400">
                        {{ labelContentPreview(label, 30) }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="whitespace-nowrap px-6 py-5">
                  <span :class="typeBadgeClass(label.type)">
                    {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-6 py-5 text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]">
                  {{ label.width }} × {{ label.height }} mm
                </td>
                <td class="whitespace-nowrap px-6 py-5 text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]">
                  {{ label.lab?.name || '-' }}
                </td>
                <td class="whitespace-nowrap px-6 py-5">
                  <span :class="statusBadgeClass(label.is_active)">
                    {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-6 py-5 text-sm font-medium">
                  <div class="flex items-center gap-2">
                    <Link
                      :href="route('vap_labels.labels.show', label.id)"
                      class="rounded-lg p-1 text-primary-700 hover:bg-primary-50 hover:text-primary-800 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.view')"
                    >
                      <EyeIcon class="h-5 w-5" />
                    </Link>
                    <Link
                      :href="route('vap_labels.labels.edit', label.id)"
                      class="rounded-lg p-1 text-primary-700 hover:bg-primary-50 hover:text-primary-800 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:hover:text-primary-200"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
                    >
                      <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button
                      @click="toggleStatus(label)"
                      :class="[
                        'p-1 rounded',
                        label.is_active
                          ? 'text-amber-500 hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10'
                          : 'text-green-500 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-500/10'
                      ]"
                      :title="label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')"
                    >
                      <PowerIcon class="h-5 w-5" />
                    </button>
                    <button
                      @click="confirmDelete(label)"
                      class="rounded-lg p-1 text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.delete')"
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
        <div class="border-t border-[#ded3bf] px-6 py-5 dark:border-[#25443c]">
          <Pagination :links="labels.links" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { 
  TagIcon, 
  PlusCircleIcon, 
  ListBulletIcon, 
  EyeIcon, 
  PencilIcon, 
  TrashIcon, 
  PowerIcon,
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  labels: Object,
  filters: Object,
  stats: Object,
  labs: Array,
  departments: Array,
})

const filters = ref(props.filters)

const labelStatCards = computed(() => [
  {
    label: 'Activas',
    value: props.stats?.active || 0,
    hint: 'Etiquetas disponíveis para uso e impressão.',
  },
  {
    label: 'Tipos controlados',
    value: props.stats?.by_type?.length || 0,
    hint: 'Famílias de etiquetas configuradas neste workspace.',
  },
  {
    label: 'Filtro actual',
    value: filters.value?.type
      ? trans('gestlab.general.labels.vap_labels.types.' + filters.value.type)
      : trans('gestlab.general.labels.vap_labels.all_types'),
    hint: 'Escopo actualmente aplicado à listagem.',
  },
])

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'bg-sky-100 text-sky-800 dark:bg-sky-500/10 dark:text-sky-200',
    material: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
    sample: 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/10 dark:text-fuchsia-200',
    custom: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
  }
  return `inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${classes[type] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}`
}

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'
}

const typeColor = (type) => {
  const colors = {
    equipment: 'bg-blue-500',
    material: 'bg-green-500',
    sample: 'bg-purple-500',
    custom: 'bg-gray-500',
  }
  return colors[type] || 'bg-gray-500'
}

const labelContentPreview = (label, length = 64) => {
  const content = String(label?.content || '').trim()

  if (!content) {
    return trans('gestlab.general.labels.vap_labels.preview_text')
  }

  return content.length > length ? `${content.substring(0, length)}...` : content
}

const labelPreviewStyle = (label) => ({
  backgroundColor: label?.background_color || '#fffdf7',
  borderColor: label?.border_color || '#d8cfbe',
  borderWidth: `${label?.border_width || 1}px`,
  color: label?.text_color || '#15231f',
  fontSize: `${Math.max(Number(label?.font_size || 12), 10)}px`,
  textAlign: label?.text_alignment || 'center',
})

const previewPdf = (label) => {
  window.open(route('vap_labels.preview-pdf', label.id), '_blank')
}

const confirmDelete = (label) => {
  if (confirm(trans('gestlab.general.labels.vap_labels.confirm_delete_label'))) {
    router.delete(route('vap_labels.labels.destroy', label.id))
  }
}

const toggleStatus = (label) => {
  router.post(route('vap_labels.toggle-status', label.id), {}, {
    preserveScroll: true,
  })
}

// Debounce filter changes
const applyFilters = debounce(() => {
  router.get(route('vap_labels.labels.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

watch(filters, applyFilters, { deep: true })
</script>
