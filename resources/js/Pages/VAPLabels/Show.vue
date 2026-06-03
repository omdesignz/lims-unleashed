<template>
  <div class="vap-label-show space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[2.25rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_28px_90px_rgba(20,61,55,0.12)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div class="relative isolate flex flex-col gap-6 p-6 sm:p-8 xl:flex-row xl:items-center xl:justify-between">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.28),transparent_36%),linear-gradient(135deg,#fffdf7,#f7f1e7)] dark:bg-[radial-gradient(circle_at_8%_0%,rgba(217,176,95,0.18),transparent_34%),linear-gradient(135deg,#07110f,#10231f)]"></div>
        <div>
          <div class="inline-flex rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-black uppercase tracking-[0.24em] text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b]">
            Etiqueta controlada
          </div>
          <h1 class="mt-5 flex items-center gap-3 text-3xl font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-[#f1d78b] shadow-[0_16px_40px_rgba(20,61,55,0.22)] dark:bg-[#f1d78b] dark:text-[#07110f]">
              <TagIcon class="h-6 w-6" />
            </span>
            {{ label.name }}
          </h1>
          <p class="mt-4 max-w-3xl text-sm font-medium leading-6 text-[#475a53] dark:text-[#cbd8cf]">
            {{ $t('gestlab.general.labels.vap_labels.view_description') }}
            <span class="font-black text-[#143d37] dark:text-[#f1d78b]">
              {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
            </span>
            • {{ label.width }} × {{ label.height }} mm
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span :class="statusBadgeClass(label.is_active)">
            {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
          </span>
          <span :class="typeBadgeClass(label.type)">
            {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
          </span>
          <div class="flex items-center gap-2">
            <Link
              :href="route('vap_labels.labels.edit', label.id)"
              class="inline-flex items-center gap-2 rounded-2xl border border-[#ded3bf] bg-white px-4 py-2.5 text-sm font-black text-[#143d37] shadow-sm transition hover:border-[#d9b05f] hover:bg-[#fffdf7] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f1d78b] dark:hover:bg-[#16342e]"
            >
              <PencilIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}
            </Link>
            <button
              @click="previewPDF"
              class="inline-flex items-center gap-2 rounded-2xl bg-[#143d37] px-4 py-2.5 text-sm font-black text-white shadow-[0_16px_40px_rgba(20,61,55,0.18)] transition hover:bg-[#0f2f2a] dark:bg-[#f1d78b] dark:text-[#07110f] dark:hover:bg-[#f6e7bf]"
            >
              <EyeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.preview_pdf') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-3xl border border-primary-200 bg-gradient-to-br from-primary-50 to-cyan-50 p-5 shadow-sm dark:border-primary-900/60 dark:from-primary-950/40 dark:to-cyan-950/20">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-700 dark:text-primary-300">PDF premium</p>
            <h2 class="mt-2 text-lg font-bold text-slate-950 dark:text-slate-100">
              {{ pdfRenderer?.chrome?.available ? 'Chrome PDF activo' : 'Fallback mPDF activo' }}
            </h2>
          </div>
          <span :class="[
            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
            pdfRenderer?.chrome?.available
              ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200'
              : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200'
          ]">
            {{ pdfRenderer?.chrome?.available ? 'Alta fidelidade' : 'Seguro' }}
          </span>
        </div>
        <p class="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
          {{ pdfRenderer?.chrome?.description }}
        </p>
        <p v-if="pdfRenderer?.chrome?.binary_path" class="mt-3 truncate rounded-2xl bg-white/70 px-3 py-2 font-mono text-xs text-slate-600 ring-1 ring-primary-100 dark:bg-slate-950/50 dark:text-slate-300 dark:ring-primary-900/60">
          {{ pdfRenderer.chrome.binary_path }}
        </p>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Dimensão</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-slate-100">{{ label.width }} × {{ label.height }} mm</p>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Tamanho personalizado respeitado no preview, PDF singular e lote.</p>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Rastreabilidade</p>
        <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-slate-100">
          {{ [label.has_qr_code ? 'QR' : null, label.has_barcode ? 'Barcode' : null, label.logo_path ? 'Logo' : null].filter(Boolean).join(' + ') || 'Texto' }}
        </p>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Elementos técnicos preparados para impressão operacional.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LABEL PREVIEW CARD -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <EyeIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              <span class="text-slate-950 dark:text-white">
              {{ $t('gestlab.general.labels.vap_labels.preview') }}
              </span>
            </h2>
          </div>
          
          <div class="p-6">
            <div class="flex flex-col items-center justify-center rounded-3xl bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.12),transparent_36%),linear-gradient(135deg,#f8fafc,#eef2ff)] p-6 ring-1 ring-slate-200 dark:bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.16),transparent_36%),linear-gradient(135deg,#020617,#0f172a)] dark:ring-slate-800 sm:p-8">
              <!-- LABEL PREVIEW -->
              <div 
                class="relative border border-slate-300 shadow-2xl shadow-slate-900/10 dark:border-slate-700 dark:shadow-black/30"
                :style="{
                  width: (label.width * 3) + 'px',
                  height: (label.height * 3) + 'px',
                  backgroundColor: label.background_color,
                  color: label.text_color,
                  fontSize: label.font_size + 'px',
                  borderWidth: label.border_width + 'px',
                  borderColor: label.border_color,
                  textAlign: label.text_alignment,
                  padding: '15px',
                  overflow: 'hidden',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: label.text_alignment
                }"
              >
                <!-- QR Code Preview -->
                <div 
                  v-if="label.has_qr_code"
                  class="absolute"
                  :style="{
                    top: '5px',
                    left: '5px',
                    width: '30px',
                    height: '30px',
                    border: '1px solid #000',
                    fontSize: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: 'white'
                  }"
                >
                  QR
                </div>
                
                <!-- Barcode Preview -->
                <div 
                  v-if="label.has_barcode"
                  class="absolute"
                  :style="{
                    bottom: '5px',
                    left: '5px',
                    width: '80px',
                    height: '20px',
                    border: '1px solid #000',
                    fontSize: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: 'white'
                  }"
                >
                  BARCODE
                </div>
                
                <!-- Logo Preview -->
                <div 
                  v-if="label.logo_path"
                  class="absolute"
                  :style="{
                    top: '5px',
                    right: '5px',
                    width: '40px',
                    height: '40px',
                    border: '1px dashed #ccc',
                    fontSize: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }"
                >
                  LOGO
                </div>
                
                <div class="w-full">
                  {{ previewData.sample_text }}
                </div>
              </div>
              
              <!-- DIMENSIONS INFO -->
              <div class="mt-6 text-center">
                <div class="inline-flex flex-wrap items-center justify-center gap-4 text-sm text-slate-600 dark:text-slate-300">
                  <div class="flex items-center gap-2">
                    <ArrowsPointingOutIcon class="h-4 w-4" />
                    <span>{{ label.width }} × {{ label.height }} mm</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <TagIcon class="h-4 w-4" />
                    <span>{{ label.font_size }}px</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="h-4 w-4 rounded border" :style="{ backgroundColor: label.background_color, borderColor: label.border_color }"></div>
                    <span>{{ label.background_color }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- LABEL CONTENT -->
            <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
              <h3 class="mb-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.vap_labels.content') }}:</h3>
              <pre class="whitespace-pre-wrap rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ label.content }}</pre>
            </div>
          </div>
        </div>

        <!-- GENERATE LABELS FORM -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <PrinterIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
              {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- GENERATION OPTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.vap_labels.generate_single') }}</h3>
                <div class="space-y-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_labels.content') }}
                    </label>
                    <textarea
                      v-model="singleLabel.content"
                      rows="3"
                      class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                      :placeholder="label.content"
                    />
                  </div>
                  
                  <div v-if="label.has_qr_code" class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_labels.qr_content') }}
                    </label>
                    <input
                      v-model="singleLabel.qr_content"
                      type="text"
                      class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                      :placeholder="previewData.sample_qr"
                    />
                  </div>
                  
                  <div v-if="label.has_barcode" class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_content') }}
                    </label>
                    <input
                      v-model="singleLabel.barcode_content"
                      type="text"
                      class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                      :placeholder="previewData.sample_barcode"
                    />
                  </div>
                  
                  <button
                    @click="generateSinglePDF"
                    :disabled="!singleLabel.content"
                    :class="[
                      'w-full inline-flex justify-center items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                      !singleLabel.content
                        ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                        : 'bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900'
                    ]"
                  >
                    <PrinterIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
                  </button>
                </div>
              </div>
              
              <div class="space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.vap_labels.generate_multiple') }}</h3>
                <div class="space-y-4">
                  <!-- BATCH LABELS LIST -->
                  <div v-for="(item, index) in batchLabels" :key="index" class="space-y-2 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-medium text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.vap_labels.label') }} #{{ index + 1 }}</h4>
                      <button
                        @click="removeBatchLabel(index)"
                        type="button"
                        class="text-red-600 hover:text-red-800"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                    
                    <div class="space-y-2">
                      <input
                        v-model="item.content"
                        type="text"
                        :placeholder="label.content"
                        class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                      />
                      
                      <div v-if="label.has_qr_code" class="flex gap-2">
                        <input
                          v-model="item.qr_content"
                          type="text"
                          :placeholder="$t('gestlab.general.labels.vap_labels.qr_content')"
                          class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                        />
                      </div>
                      
                      <div v-if="label.has_barcode" class="flex gap-2">
                        <input
                          v-model="item.barcode_content"
                          type="text"
                          :placeholder="$t('gestlab.general.labels.vap_labels.barcode_content')"
                          class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex gap-4">
                    <button
                      @click="addBatchLabel"
                      type="button"
                      class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                      <PlusCircleIcon class="h-5 w-5" />
                      {{ $t('gestlab.general.labels.vap_labels.buttons.add_label') }}
                    </button>
                    
                    <button
                      @click="generateBatchPDF"
                      :disabled="batchLabels.length === 0 || !batchLabelsValid"
                      :class="[
                        'flex-1 inline-flex justify-center items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                        batchLabels.length === 0 || !batchLabelsValid
                          ? 'cursor-not-allowed bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-500'
                          : 'bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2'
                      ]"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                      {{ $t('gestlab.general.labels.vap_labels.batch_print') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- PRINT SETTINGS -->
            <LabelPrintSettings v-model="printSettings" />
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- LABEL DETAILS CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <InformationCircleIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
            {{ $t('gestlab.general.labels.vap_labels.details') }}
          </h3>
          
          <div class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.created_at') }}</dt>
              <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(label.created_at) }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.updated_at') }}</dt>
              <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(label.updated_at) }}</dd>
            </div>
            
            <div v-if="label.lab">
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.lab') }}</dt>
              <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ label.lab.name }}</dd>
            </div>
            
            <div v-if="label.department">
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.department') }}</dt>
              <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ label.department.name }}</dd>
            </div>
            
            <div v-if="label.user">
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.created_by') }}</dt>
              <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ label.user.name }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.vap_labels.text_alignment') }}</dt>
              <dd class="mt-1 text-sm capitalize text-slate-900 dark:text-slate-100">{{ label.text_alignment }}</dd>
            </div>
          </div>
        </div>

        <!-- FEATURES CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.features') }}
          </h3>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.qr_code') }}</span>
              <span :class="featureBadgeClass(label.has_qr_code)">
                {{ label.has_qr_code ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.barcode') }}</span>
              <span :class="featureBadgeClass(label.has_barcode)">
                {{ label.has_barcode ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.logo') }}</span>
              <span :class="featureBadgeClass(label.logo_path)">
                {{ label.logo_path ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div v-if="label.has_qr_code && label.qr_code_content" class="mt-4 rounded-2xl bg-primary-50 p-3 dark:bg-primary-500/10">
              <p class="text-xs font-medium text-primary-900 dark:text-primary-200">{{ $t('gestlab.general.labels.vap_labels.qr_content') }}:</p>
              <p class="mt-1 break-all text-xs text-primary-700 dark:text-primary-300">{{ label.qr_code_content }}</p>
            </div>
            
            <div v-if="label.has_barcode && label.barcode_content" class="mt-4 rounded-2xl bg-green-50 p-3 dark:bg-green-500/10">
              <p class="text-xs font-medium text-green-900 dark:text-green-200">{{ $t('gestlab.general.labels.vap_labels.barcode_content') }}:</p>
              <p class="mt-1 break-all text-xs text-green-700 dark:text-green-300">{{ label.barcode_content }}</p>
            </div>
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
              class="cursor-pointer rounded-2xl border border-slate-200 p-3 transition-all duration-200 hover:border-primary-300 hover:bg-primary-50/70 dark:border-slate-800 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-medium text-slate-900 dark:text-white">{{ template.name }}</h4>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ template.description }}</p>
                  <div class="mt-2">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                      {{ template.category }}
                    </span>
                  </div>
                </div>
                <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                  {{ $t('gestlab.general.labels.vap_labels.featured') }}
                </span>
              </div>
            </div>
            
            <div class="border-t border-slate-200 pt-4 dark:border-slate-800">
              <Link
                :href="route('vap_labels.label-templates.index')"
                class="flex items-center gap-2 text-sm font-medium text-primary-700 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200"
              >
                <span>{{ $t('gestlab.general.labels.vap_labels.view_all_templates') }}</span>
                <ArrowRightIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
          </h3>
          
          <div class="space-y-3">
            <button
              @click="duplicateLabel"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.duplicate_label') }}
            </button>
            
            <button
              @click="toggleStatus"
              :class="[
                'w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                label.is_active
                  ? 'border border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200'
                  : 'border border-green-300 bg-green-100 text-green-800 hover:bg-green-200 dark:border-green-500/40 dark:bg-green-500/10 dark:text-green-200'
              ]"
            >
              <PowerIcon class="h-5 w-5" />
              {{ label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}
            </button>
            
            <button
              @click="confirmDelete"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition-all duration-200 hover:bg-red-100 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200"
            >
              <TrashIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.delete_label') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { useToast } from 'vue-toastification'
import { 
  TagIcon, 
  PencilIcon, 
  EyeIcon, 
  PrinterIcon, 
  InformationCircleIcon,
  PlusCircleIcon,
  TrashIcon,
  PowerIcon,
  DocumentDuplicateIcon,
  ArrowRightIcon,
  ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline'
import LabelPrintSettings from '@/Components/LabelPrintSettings.vue'

const props = defineProps({
  label: Object,
  previewData: Object,
  templates: Array,
  pdfRenderer: Object,
  printSettings: Object,
})

const toast = useToast()

const singleLabel = ref({
  content: props.previewData.sample_text,
  qr_content: props.label.has_qr_code ? props.previewData.sample_qr : null,
  barcode_content: props.label.has_barcode ? props.previewData.sample_barcode : null,
})

const batchLabels = ref([
  {
    content: props.previewData.sample_text,
    qr_content: props.label.has_qr_code ? props.previewData.sample_qr : null,
    barcode_content: props.label.has_barcode ? props.previewData.sample_barcode : null,
  }
])

const printSettings = ref({
  include_cutouts: true,
  labels_per_page: 1,
  margin: 5,
  columns: 2,
  rows: 4,
  spacing: 5,
  page_size: 'A4',
  orientation: 'portrait',
  ...(props.printSettings || {}),
})

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'
}

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'bg-sky-100 text-sky-800 dark:bg-sky-500/10 dark:text-sky-200',
    material: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
    sample: 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-500/10 dark:text-fuchsia-200',
    custom: 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200',
  }
  return `inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${classes[type] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'}`
}

const featureBadgeClass = (hasFeature) => {
  return hasFeature
    ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200'
    : 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200'
}

const batchLabelsValid = computed(() => {
  return batchLabels.value.every(label => label.content.trim() !== '')
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const addBatchLabel = () => {
  batchLabels.value.push({
    content: '',
    qr_content: props.label.has_qr_code ? '' : null,
    barcode_content: props.label.has_barcode ? '' : null,
  })
}

const removeBatchLabel = (index) => {
  batchLabels.value.splice(index, 1)
}

const previewPDF = () => {
  window.open(route('vap_labels.preview-pdf', props.label.id), '_blank')
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const appendPrintSettings = (formData) => {
  Object.entries(printSettings.value).forEach(([key, value]) => {
    formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value ?? '')
  })
}

const openPdfResponse = async (url, formData, windowTitle) => {
  const popup = window.open('', '_blank')

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json, application/pdf',
      },
      credentials: 'same-origin',
    })

    if (!response.ok) {
      const message = response.status === 422
        ? trans('gestlab.general.labels.vap_labels.validation_error')
        : trans('gestlab.general.labels.vap_labels.pdf_generation_error')

      throw new Error(message)
    }

    const contentType = response.headers.get('Content-Type') || ''

    if (!contentType.includes('application/pdf')) {
      throw new Error(trans('gestlab.general.labels.vap_labels.pdf_generation_error'))
    }

    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)

    if (popup) {
      popup.document.title = windowTitle
      popup.location.href = blobUrl
    } else {
      window.open(blobUrl, '_blank')
    }

    toast.success(trans('gestlab.general.labels.vap_labels.print_settings_saved'))
  } catch (error) {
    if (popup) {
      popup.close()
    }

    toast.error(error.message || trans('gestlab.general.labels.vap_labels.pdf_generation_error'))
  }
}

const generateSinglePDF = async () => {
  const formData = new FormData()
  formData.append('data[0][content]', singleLabel.value.content || props.previewData.sample_text)

  if (props.label.has_qr_code) {
    formData.append('data[0][qr_content]', singleLabel.value.qr_content || props.previewData.sample_qr)
  }
  
  if (props.label.has_barcode) {
    formData.append('data[0][barcode_content]', singleLabel.value.barcode_content || props.previewData.sample_barcode)
  }

  appendPrintSettings(formData)
  await openPdfResponse(route('vap_labels.generate-pdf', props.label.id), formData, props.label.name)
}

const generateBatchPDF = async () => {
  const formData = new FormData()

  batchLabels.value.forEach((label, index) => {
    formData.append(`data[${index}][content]`, label.content)
    
    if (props.label.has_qr_code && label.qr_content) {
      formData.append(`data[${index}][qr_content]`, label.qr_content)
    }
    
    if (props.label.has_barcode && label.barcode_content) {
      formData.append(`data[${index}][barcode_content]`, label.barcode_content)
    }
  })

  appendPrintSettings(formData)
  await openPdfResponse(route('vap_labels.generate-batch-pdf', props.label.id), formData, `${props.label.name} - lote`)
}

const applyTemplate = (template) => {
  if (confirm(trans('gestlab.general.labels.vap_labels.confirm_apply_template'))) {
    router.post(route('vap_labels.apply-template', props.label.id), {
      template_id: template.id
    })
  }
}

const duplicateLabel = () => {
  if (confirm(trans('gestlab.general.labels.vap_labels.confirm_duplicate_label'))) {
    router.post(route('vap_labels.duplicate', props.label.id))
  }
}

const toggleStatus = () => {
  router.post(route('vap_labels.toggle-status', props.label.id))
}

const confirmDelete = () => {
  if (confirm(trans('gestlab.general.labels.vap_labels.confirm_delete_label_irreversible'))) {
    router.delete(route('vap_labels.labels.destroy', props.label.id))
  }
}
</script>

<style scoped>
.vap-label-show :is(.rounded-3xl.border.border-slate-200.bg-white, .overflow-hidden.rounded-3xl.border.border-slate-200.bg-white) {
  border-color: #ded3bf;
  background: #fffdf7;
  box-shadow: 0 22px 70px rgb(20 61 55 / 0.09);
}

.vap-label-show :is(.border-b.border-slate-200.bg-slate-50) {
  border-color: #ded3bf;
  background: #f7f1e7;
}

.vap-label-show :is(input, textarea, select) {
  border-color: #d8cfbe;
  background: #fffdf7;
  color: #15231f;
  font-weight: 500;
}

.vap-label-show :is(input, textarea, select):focus {
  border-color: #143d37;
  box-shadow: 0 0 0 4px rgb(20 61 55 / 0.12);
}

:global(.dark) .vap-label-show :is(.rounded-3xl.border.border-slate-200.bg-white, .overflow-hidden.rounded-3xl.border.border-slate-200.bg-white) {
  border-color: #25443c;
  background: #07110f;
  box-shadow: 0 22px 70px rgb(0 0 0 / 0.24);
}

:global(.dark) .vap-label-show :is(.border-b.border-slate-200.bg-slate-50) {
  border-color: #25443c;
  background: #10231f;
}

:global(.dark) .vap-label-show :is(input, textarea, select) {
  border-color: #25443c;
  background: #081512;
  color: #f7f1e7;
  color-scheme: dark;
}

:global(.dark) .vap-label-show :is(input, textarea, select):focus {
  border-color: #f1d78b;
  box-shadow: 0 0 0 4px rgb(241 215 139 / 0.14);
}
</style>
