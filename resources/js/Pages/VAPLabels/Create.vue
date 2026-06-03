<template>
  <div class="vap-label-editor space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[2.25rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_28px_90px_rgba(20,61,55,0.12)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="relative isolate flex flex-col gap-6 overflow-hidden p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.28),transparent_36%),linear-gradient(135deg,#fffdf7,#f7f1e7)] dark:bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.18),transparent_34%),linear-gradient(135deg,#07110f,#10231f)]"></div>
        <div>
          <div class="inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
            Label Studio
          </div>
          <h1 class="mt-5 flex items-center gap-3 text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-[#f1d78b] shadow-[0_16px_40px_rgba(20,61,55,0.22)] dark:bg-[#f1d78b] dark:text-[#07110f]">
              <TagIcon class="h-6 w-6" />
            </span>
            {{ pageTitle }}
          </h1>
          <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            {{ pageDescription }}
            <span v-if="selectedTemplate" class="font-black text-[#143d37] dark:text-[#f1d78b]">
              {{ selectedTemplate.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full border border-[#ded3bf] bg-white/80 px-4 py-2 text-sm font-black text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
            {{ templates.length }} {{ $t('gestlab.general.labels.vap_labels.available_templates') }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC SETTINGS CARD -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <!-- GRADIENT HEADER -->
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <Cog6ToothIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              {{ $t('gestlab.general.labels.vap_labels.basic_settings') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- NAME -->
              <div class="space-y-2">
                <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :placeholder="$t('gestlab.general.labels.vap_labels.name_placeholder')"
                  :class="[
                    'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500',
                    form.errors.name 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                      : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                  ]"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- TYPE -->
              <div class="space-y-2">
                <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.type"
                  :class="[
                    'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100',
                    form.errors.type 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                      : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                  ]"
                >
                  <option value="equipment">{{ $t('gestlab.general.labels.vap_labels.types.equipment') }}</option>
                  <option value="material">{{ $t('gestlab.general.labels.vap_labels.types.material') }}</option>
                  <option value="sample">{{ $t('gestlab.general.labels.vap_labels.types.sample') }}</option>
                  <option value="custom">{{ $t('gestlab.general.labels.vap_labels.types.custom') }}</option>
                </select>
                <p v-if="form.errors.type" class="text-xs text-red-600">
                  {{ form.errors.type }}
                </p>
              </div>

              <div class="space-y-3 md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  Origem operacional da etiqueta
                </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                  <button
                    v-for="option in sourceTypeOptions"
                    :key="option.value"
                    type="button"
                    @click="form.source_type = option.value; form.source_id = null"
                    :class="[
                      'rounded-2xl border px-4 py-3 text-left text-sm transition',
                      form.source_type === option.value
                        ? 'border-primary-400 bg-primary-50 text-primary-900 dark:border-primary-500/60 dark:bg-primary-500/10 dark:text-primary-100'
                        : 'border-slate-200 bg-white text-slate-700 hover:border-primary-200 hover:bg-primary-50/50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10'
                    ]"
                  >
                    <div class="font-semibold">{{ option.label }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ option.description }}</div>
                  </button>
                </div>
              </div>

              <div v-if="availableSources.length" class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  Registo de origem
                </label>
                <select
                  v-model="form.source_id"
                  class="block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                >
                  <option :value="null">Selecionar origem</option>
                  <option v-for="source in availableSources" :key="source.id" :value="source.id">
                    {{ source.label }}
                  </option>
                </select>
              </div>

              <!-- WIDTH -->
              <div class="space-y-2">
                <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
                  <ArrowsPointingOutIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.width') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.width"
                    type="number"
                    step="0.1"
                    min="1"
                    max="1000"
                    :placeholder="$t('gestlab.general.labels.vap_labels.width_placeholder')"
                    :class="[
                      'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500',
                      form.errors.width 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                    ]"
                  />
                  <span class="whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">mm</span>
                </div>
                <p v-if="form.errors.width" class="text-xs text-red-600">
                  {{ form.errors.width }}
                </p>
              </div>

              <!-- HEIGHT -->
              <div class="space-y-2">
                <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-200">
                  <ArrowsPointingOutIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.height') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.height"
                    type="number"
                    step="0.1"
                    min="1"
                    max="1000"
                    :placeholder="$t('gestlab.general.labels.vap_labels.height_placeholder')"
                    :class="[
                      'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500',
                      form.errors.height 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                    ]"
                  />
                  <span class="whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">mm</span>
                </div>
                <p v-if="form.errors.height" class="text-xs text-red-600">
                  {{ form.errors.height }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTENT CARD -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <DocumentTextIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              {{ $t('gestlab.general.labels.vap_labels.content') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ $t('gestlab.general.labels.vap_labels.label_content') }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.content"
                rows="4"
                :placeholder="$t('gestlab.general.labels.vap_labels.content_placeholder')"
                :class="[
                  'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500',
                  form.errors.content 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                    : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                ]"
              />
              <p v-if="form.errors.content" class="text-xs text-red-600">
                {{ form.errors.content }}
              </p>
              <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span>{{ $t('gestlab.general.labels.vap_labels.template_variables') }}:</span>
                <code
                  v-for="placeholder in supportedPlaceholders"
                  :key="placeholder"
                  class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                  {{ placeholder }}
                </code>
              </div>
              <div v-if="sourcePreview" class="rounded-2xl border border-primary-100 bg-primary-50 px-4 py-3 text-xs text-primary-900 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
                Esta etiqueta pode ser gerada automaticamente a partir do registo selecionado, reutilizando código, lote, cliente, armazém e datas relevantes.
              </div>
            </div>
          </div>
        </div>

        <!-- APPEARANCE CARD -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <PaintBrushIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              {{ $t('gestlab.general.labels.vap_labels.appearance') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- BACKGROUND COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.background_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.background_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-2xl border border-slate-300 dark:border-slate-700"
                  />
                  <input
                    v-model="form.background_color"
                    type="text"
                    :class="[
                      'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100',
                      form.errors.background_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                    ]"
                    placeholder="#ffffff"
                  />
                </div>
                <p v-if="form.errors.background_color" class="text-xs text-red-600">
                  {{ form.errors.background_color }}
                </p>
              </div>

              <!-- TEXT COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.text_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.text_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-2xl border border-slate-300 dark:border-slate-700"
                  />
                  <input
                    v-model="form.text_color"
                    type="text"
                    :class="[
                      'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100',
                      form.errors.text_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                    ]"
                    placeholder="#000000"
                  />
                </div>
                <p v-if="form.errors.text_color" class="text-xs text-red-600">
                  {{ form.errors.text_color }}
                </p>
              </div>

              <!-- FONT SIZE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.font_size') }}
                </label>
                <input
                  v-model="form.font_size"
                  type="range"
                  min="6"
                  max="72"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 dark:bg-slate-800"
                />
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                  <span>6px</span>
                  <span class="font-medium">{{ form.font_size }}px</span>
                  <span>72px</span>
                </div>
                <p v-if="form.errors.font_size" class="text-xs text-red-600">
                  {{ form.errors.font_size }}
                </p>
              </div>

              <!-- TEXT ALIGNMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
                </label>
                <div class="flex gap-2">
                  <button
                    v-for="align in ['left', 'center', 'right', 'justify']"
                    :key="align"
                    @click="form.text_alignment = align"
                    :class="[
                      'flex-1 rounded-2xl border py-2 text-sm font-medium transition',
                      form.text_alignment === align
                        ? 'border-primary-400 bg-primary-50 text-primary-900 dark:border-primary-500/60 dark:bg-primary-500/10 dark:text-primary-100'
                        : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'
                    ]"
                    :title="$t('gestlab.general.labels.vap_labels.align_' + align)"
                  >
                    {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                  </button>
                </div>
                <p v-if="form.errors.text_alignment" class="text-xs text-red-600">
                  {{ form.errors.text_alignment }}
                </p>
              </div>

              <!-- BORDER WIDTH -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.border_width') }}
                </label>
                <input
                  v-model="form.border_width"
                  type="range"
                  min="0"
                  max="10"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 dark:bg-slate-800"
                />
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                  <span>0px</span>
                  <span class="font-medium">{{ form.border_width }}px</span>
                  <span>10px</span>
                </div>
                <p v-if="form.errors.border_width" class="text-xs text-red-600">
                  {{ form.errors.border_width }}
                </p>
              </div>

              <!-- BORDER COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ $t('gestlab.general.labels.vap_labels.border_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.border_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-2xl border border-slate-300 dark:border-slate-700"
                  />
                  <input
                    v-model="form.border_color"
                    type="text"
                    :class="[
                      'block w-full rounded-2xl border bg-white text-sm text-slate-900 shadow-sm focus:ring-2 focus:ring-offset-0 dark:bg-slate-950 dark:text-slate-100',
                      form.errors.border_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-slate-300 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700'
                    ]"
                    placeholder="#000000"
                  />
                </div>
                <p v-if="form.errors.border_color" class="text-xs text-red-600">
                  {{ form.errors.border_color }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ADVANCED ELEMENTS CARD -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <QrCodeIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              Elementos de rastreabilidade
            </h2>
          </div>

          <div class="grid gap-5 p-6 xl:grid-cols-3">
            <article
              v-for="element in advancedElements"
              :key="element.key"
              class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-black text-slate-950 dark:text-white">{{ element.label }}</p>
                  <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ element.description }}</p>
                </div>
                <button
                  type="button"
                  @click="form[element.enabledField] = !form[element.enabledField]"
                  :class="[
                    'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                    form[element.enabledField]
                      ? 'bg-[#143d37] dark:bg-[#f1d78b]'
                      : 'bg-slate-300 dark:bg-slate-700'
                  ]"
                >
                  <span
                    :class="[
                      'inline-block h-5 w-5 transform rounded-full bg-white shadow transition dark:bg-[#07110f]',
                      form[element.enabledField] ? 'translate-x-6' : 'translate-x-1'
                    ]"
                  />
                </button>
              </div>

              <div v-if="form[element.enabledField]" class="mt-4 space-y-3">
                <label class="block">
                  <span class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ element.contentLabel }}</span>
                  <input
                    v-model="form[element.contentField]"
                    type="text"
                    class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    :placeholder="element.placeholder"
                  />
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                  <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ element.sizeLabel }}</span>
                    <input
                      v-model="form[element.sizeField]"
                      type="number"
                      min="1"
                      max="80"
                      class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    />
                  </label>
                  <label v-if="element.secondarySizeField" class="block">
                    <span class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ element.secondarySizeLabel }}</span>
                    <input
                      v-model="form[element.secondarySizeField]"
                      type="number"
                      min="1"
                      max="80"
                      class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    />
                  </label>
                </div>
              </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/50 xl:col-span-3">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                  <p class="text-sm font-black text-slate-950 dark:text-white">Logótipo ou imagem auxiliar</p>
                  <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400">
                    Use uma referência de media já carregada para incluir logo, selo ou marca visual na etiqueta. O PDF Chrome respeita a composição persistida.
                  </p>
                </div>
                <button
                  type="button"
                  @click="form.logo_path = form.logo_path ? null : ''"
                  :class="[
                    'relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                    form.logo_path !== null
                      ? 'bg-[#143d37] dark:bg-[#f1d78b]'
                      : 'bg-slate-300 dark:bg-slate-700'
                  ]"
                >
                  <span
                    :class="[
                      'inline-block h-5 w-5 transform rounded-full bg-white shadow transition dark:bg-[#07110f]',
                      form.logo_path !== null ? 'translate-x-6' : 'translate-x-1'
                    ]"
                  />
                </button>
              </div>
              <div v-if="form.logo_path !== null" class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_10rem]">
                <label class="block">
                  <span class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.logo_file') }}</span>
                  <input
                    v-model="form.logo_path"
                    type="text"
                    class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    placeholder="/storage/media/logo.svg"
                  />
                </label>
                <label class="block">
                  <span class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.logo_size') }}</span>
                  <input
                    v-model="form.logo_size"
                    type="number"
                    min="1"
                    max="80"
                    class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                  />
                </label>
              </div>
            </article>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- PREVIEW CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.preview') }}
          </h3>
          <div class="flex items-center justify-center rounded-3xl bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.12),transparent_36%),linear-gradient(135deg,#f8fafc,#eef2ff)] p-4 ring-1 ring-slate-200 dark:bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.16),transparent_36%),linear-gradient(135deg,#020617,#0f172a)] dark:ring-slate-800">
            <div 
              class="relative border border-slate-300 shadow-2xl shadow-slate-900/10 dark:border-slate-700 dark:shadow-black/30"
              :style="{
                width: (form.width * 3) + 'px',
                height: (form.height * 3) + 'px',
                backgroundColor: form.background_color,
                color: form.text_color,
                fontSize: form.font_size + 'px',
                borderWidth: form.border_width + 'px',
                borderColor: form.border_color,
                textAlign: form.text_alignment,
                padding: '10px',
                overflow: 'hidden'
              }"
            >
              <div class="h-full flex items-center justify-center">
                <div>
                  {{ form.content || $t('gestlab.general.labels.vap_labels.preview_text') }}
                </div>
              </div>
            </div>
          </div>
          <div class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
            {{ form.width }} × {{ form.height }} mm
          </div>
        </div>

        <!-- TEMPLATES CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </h3>
          <div class="space-y-3">
            <div 
              v-for="template in templates"
              :key="template.id"
              @click="applyTemplate(template)"
              :class="[
                'cursor-pointer rounded-2xl border p-3 transition-all duration-200',
                selectedTemplate?.id === template.id
                  ? 'border-primary-400 bg-primary-50 dark:border-primary-500/60 dark:bg-primary-500/10'
                  : 'border-slate-200 hover:border-primary-300 hover:bg-primary-50/70 dark:border-slate-800 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10'
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-medium text-slate-900 dark:text-white">{{ template.name }}</h4>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ template.description }}</p>
                </div>
                <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                  {{ $t('gestlab.general.labels.vap_labels.featured') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- LAB & DEPARTMENT CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.assignment') }}
          </h3>
          <div class="space-y-4">
            <!-- LAB -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ $t('gestlab.general.labels.vap_labels.lab') }}
              </label>
              <select
                v-model="form.lab_id"
                class="block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_labels.select_lab') }}</option>
                <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                  {{ lab.name }}
                </option>
              </select>
            </div>

            <!-- DEPARTMENT -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ $t('gestlab.general.labels.vap_labels.department') }}
              </label>
              <select
                v-model="form.department_id"
                class="block w-full rounded-2xl border border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_labels.select_department') }}</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- ACTIONS CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                  : 'bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900'
              ]"
            >
              <CheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : submitLabel }}
            </button>
            
            <Link
              :href="props.label ? route('vap_labels.labels.show', props.label.id) : route('vap_labels.labels.index')"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              <ArrowLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, Link } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { 
  TagIcon, 
  Cog6ToothIcon, 
  DocumentTextIcon, 
  PaintBrushIcon,
  CheckIcon, 
  ArrowLeftIcon,
  ArrowsPointingOutIcon,
  QrCodeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  templates: Array,
  selectedTemplateId: Number,
  labs: Array,
  departments: Array,
  defaultSettings: Object,
  label: Object,
  sourcePreview: Object,
  sourceOptions: Object,
  supportedPlaceholders: {
    type: Array,
    default: () => ['{name}', '{code}', '{lot}'],
  },
})

const selectedTemplate = ref(null)
const pageTitle = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.edit_title')
  : trans('gestlab.general.labels.vap_labels.create_title'))
const pageDescription = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.edit_description')
  : trans('gestlab.general.labels.vap_labels.create_description'))
const submitLabel = computed(() => props.label
  ? trans('gestlab.general.labels.vap_labels.buttons.update_label')
  : trans('gestlab.general.labels.vap_labels.buttons.save_label'))

const sourceTypeOptions = [
  { value: 'sample_entry', label: 'Amostra', description: 'Receção, retenção, QR e rastreabilidade analítica.' },
  { value: 'equipment', label: 'Equipamento', description: 'Identificação, estado metrológico e manutenção.' },
  { value: 'reagent', label: 'Reagente / material', description: 'Lote, validade, armazenagem e stock.' },
]

const advancedElements = [
  {
    key: 'qr',
    label: trans('gestlab.general.labels.vap_labels.qr_code'),
    description: trans('gestlab.general.labels.vap_labels.qr_code_description'),
    enabledField: 'has_qr_code',
    contentField: 'qr_code_content',
    sizeField: 'qr_code_size',
    contentLabel: trans('gestlab.general.labels.vap_labels.qr_content'),
    sizeLabel: trans('gestlab.general.labels.vap_labels.qr_code_size'),
    placeholder: '{verification_url}',
  },
  {
    key: 'barcode',
    label: trans('gestlab.general.labels.vap_labels.barcode'),
    description: trans('gestlab.general.labels.vap_labels.barcode_description'),
    enabledField: 'has_barcode',
    contentField: 'barcode_content',
    sizeField: 'barcode_width',
    secondarySizeField: 'barcode_height',
    contentLabel: trans('gestlab.general.labels.vap_labels.barcode_content'),
    sizeLabel: trans('gestlab.general.labels.vap_labels.barcode_width'),
    secondarySizeLabel: trans('gestlab.general.labels.vap_labels.barcode_height'),
    placeholder: '{code}',
  },
]

const form = useForm({
  name: props.label?.name || '',
  type: props.label?.type || 'custom',
  content: props.label?.content || '',
  width: props.label?.width || props.defaultSettings?.width || 50,
  height: props.label?.height || props.defaultSettings?.height || 25,
  background_color: props.label?.background_color || props.label?.template_data?.background_color || '#ffffff',
  text_color: props.label?.text_color || props.label?.template_data?.text_color || '#000000',
  font_size: props.label?.font_size || props.defaultSettings?.font_size || 12,
  border_width: props.label?.border_width || props.defaultSettings?.border_width || 1,
  border_color: props.label?.border_color || props.label?.template_data?.border_color || '#000000',
  text_alignment: props.label?.text_alignment || 'center',
  lab_id: props.label?.lab_id || null,
  department_id: props.label?.department_id || null,
  logo_path: props.label?.logo_path || null,
  logo_size: props.label?.logo_size || null,
  has_qr_code: props.label?.has_qr_code || false,
  qr_code_content: props.label?.qr_code_content || null,
  qr_code_size: props.label?.qr_code_size || null,
  has_barcode: props.label?.has_barcode || false,
  barcode_content: props.label?.barcode_content || null,
  barcode_type: props.label?.barcode_type || 'CODE128',
  barcode_width: props.label?.barcode_width || null,
  barcode_height: props.label?.barcode_height || null,
  is_active: props.label?.is_active ?? true,
  source_type: props.label?.template_data?.source_type || props.sourcePreview?.source_type || null,
  source_id: props.label?.template_data?.source_id || props.sourcePreview?.source_id || null,
  template_id: props.label?.template_data?.template_id || null,
})

const availableSources = computed(() => {
  if (form.source_type === 'sample_entry' || form.source_type === 'sample') {
    return props.sourceOptions?.samples ?? []
  }

  if (form.source_type === 'equipment' || form.source_type === 'reagent') {
    return props.sourceOptions?.inventory ?? []
  }

  if (form.source_type === 'collection_product') {
    return props.sourceOptions?.collection_products ?? []
  }

  return []
})

const applyTemplate = (template) => {
  selectedTemplate.value = template
  form.template_id = template.id
  if (template.template_data) {
    Object.keys(template.template_data).forEach(key => {
      if (key in form) {
        form[key] = template.template_data[key]
      }
    })
  }
}

onMounted(() => {
  if (props.label || ! props.selectedTemplateId) {
    return
  }

  const preselectedTemplate = props.templates?.find((template) => template.id === props.selectedTemplateId)

  if (preselectedTemplate) {
    applyTemplate(preselectedTemplate)
  }
})

const submit = () => {
  if (props.label) {
    form.put(route('vap_labels.labels.update', props.label.id))
  } else {
    form.post(route('vap_labels.labels.store'))
  }
}
</script>

<style scoped>
.vap-label-editor :is(.rounded-3xl.border.border-slate-200.bg-white, .rounded-3xl.border.border-slate-200.bg-white.p-6) {
  border-color: #ded3bf;
  background: #fffdf7;
  box-shadow: 0 22px 70px rgb(20 61 55 / 0.09);
}

.vap-label-editor :is(.border-b.border-slate-200.bg-slate-50) {
  border-color: #ded3bf;
  background: #f7f1e7;
}

.vap-label-editor :is(input:not([type='range']):not([type='color']), select, textarea) {
  border-color: #d8cfbe;
  background: #fffdf7;
  color: #15231f;
  font-weight: 500;
  transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.vap-label-editor :is(input:not([type='range']):not([type='color']), select, textarea):focus {
  border-color: #143d37;
  box-shadow: 0 0 0 4px rgb(20 61 55 / 0.12);
}

.vap-label-editor input[type='color'] {
  overflow: hidden;
  border-color: #d8cfbe;
  background: #fffdf7;
  padding: 0.2rem;
}

:global(.dark) .vap-label-editor :is(.rounded-3xl.border.border-slate-200.bg-white, .rounded-3xl.border.border-slate-200.bg-white.p-6) {
  border-color: #25443c;
  background: #07110f;
  box-shadow: 0 22px 70px rgb(0 0 0 / 0.24);
}

:global(.dark) .vap-label-editor :is(.border-b.border-slate-200.bg-slate-50) {
  border-color: #25443c;
  background: #10231f;
}

:global(.dark) .vap-label-editor :is(input:not([type='range']):not([type='color']), select, textarea) {
  border-color: #25443c;
  background: #081512;
  color: #f7f1e7;
  color-scheme: dark;
}

:global(.dark) .vap-label-editor :is(input:not([type='range']):not([type='color']), select, textarea):focus {
  border-color: #f1d78b;
  box-shadow: 0 0 0 4px rgb(241 215 139 / 0.14);
}

:global(.dark) .vap-label-editor input[type='color'] {
  border-color: #25443c;
  background: #081512;
}
</style>
