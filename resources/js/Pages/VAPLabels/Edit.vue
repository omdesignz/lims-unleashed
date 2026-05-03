<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TagIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.edit_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.edit_description') }}
            <span class="font-semibold text-blue-900">
              {{ label.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusBadgeClass(label.is_active)">
            {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
          </span>
          <Link
            :href="route('vap_labels.labels.show', label.id)"
            class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <EyeIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.view') }}
          </Link>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- BASIC SETTINGS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <Cog6ToothIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.basic_settings') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NAME -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.name') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.name 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <p v-if="form.errors.name" class="text-xs text-red-600">
                    {{ form.errors.name }}
                  </p>
                </div>

                <!-- TYPE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.type') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.type"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.type 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
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

                <!-- WIDTH & HEIGHT -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
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
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                        form.errors.width 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                  </div>
                  <p v-if="form.errors.width" class="text-xs text-red-600">
                    {{ form.errors.width }}
                  </p>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
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
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                        form.errors.height 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                    <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                  </div>
                  <p v-if="form.errors.height" class="text-xs text-red-600">
                    {{ form.errors.height }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- CONTENT CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <DocumentTextIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.content') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="space-y-4">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.label_content') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.content"
                    rows="6"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm font-mono',
                      form.errors.content 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <p v-if="form.errors.content" class="text-xs text-red-600">
                    {{ form.errors.content }}
                  </p>
                </div>
                
                <!-- TEMPLATE VARIABLES -->
                <div class="bg-blue-50 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-blue-900 mb-2">
                    {{ $t('gestlab.general.labels.vap_labels.dynamic_variables') }}
                  </h4>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}nome{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_name') }}</span>
                    </div>
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}data{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_date') }}</span>
                    </div>
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}lote{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_lot') }}</span>
                    </div>
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}batch{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_batch') }}</span>
                    </div>
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}serial{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_serial') }}</span>
                    </div>
                    <div class="text-xs">
                      <code class="bg-white px-2 py-1 rounded border border-blue-200">{"{"}laboratorio{"}"}</code>
                      <span class="text-blue-700 ml-1">{{ $t('gestlab.general.labels.vap_labels.variable_lab') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- APPEARANCE CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <PaintBrushIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.appearance') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- BACKGROUND COLOR -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.background_color') }}
                  </label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.background_color"
                      type="color"
                      class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                    />
                    <input
                      v-model="form.background_color"
                      type="text"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm font-mono',
                        form.errors.background_color 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                  </div>
                  <p v-if="form.errors.background_color" class="text-xs text-red-600">
                    {{ form.errors.background_color }}
                  </p>
                </div>

                <!-- TEXT COLOR -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.text_color') }}
                  </label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.text_color"
                      type="color"
                      class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                    />
                    <input
                      v-model="form.text_color"
                      type="text"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm font-mono',
                        form.errors.text_color 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                  </div>
                  <p v-if="form.errors.text_color" class="text-xs text-red-600">
                    {{ form.errors.text_color }}
                  </p>
                </div>

                <!-- FONT SIZE -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.font_size') }}
                  </label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.font_size"
                      type="range"
                      min="6"
                      max="72"
                      class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <span class="text-sm text-gray-600 w-12 text-right">
                      {{ form.font_size }}px
                    </span>
                  </div>
                  <p v-if="form.errors.font_size" class="text-xs text-red-600">
                    {{ form.errors.font_size }}
                  </p>
                </div>

                <!-- TEXT ALIGNMENT -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
                  </label>
                  <div class="flex gap-2">
                    <button
                      v-for="align in ['left', 'center', 'right', 'justify']"
                      :key="align"
                      type="button"
                      @click="form.text_alignment = align"
                      :class="[
                        'flex-1 py-2 text-sm font-medium rounded-lg border transition-all duration-200',
                        form.text_alignment === align
                          ? 'border-blue-900 bg-blue-50 text-blue-900'
                          : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                      ]"
                      :title="$t('gestlab.general.labels.vap_labels.align_' + align)"
                    >
                      {{ align.charAt(0).toUpperCase() }}
                    </button>
                  </div>
                  <p v-if="form.errors.text_alignment" class="text-xs text-red-600">
                    {{ form.errors.text_alignment }}
                  </p>
                </div>

                <!-- BORDER WIDTH -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.border_width') }}
                  </label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.border_width"
                      type="range"
                      min="0"
                      max="10"
                      class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <span class="text-sm text-gray-600 w-12 text-right">
                      {{ form.border_width }}px
                    </span>
                  </div>
                  <p v-if="form.errors.border_width" class="text-xs text-red-600">
                    {{ form.errors.border_width }}
                  </p>
                </div>

                <!-- BORDER COLOR -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.border_color') }}
                  </label>
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.border_color"
                      type="color"
                      class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                    />
                    <input
                      v-model="form.border_color"
                      type="text"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm font-mono',
                        form.errors.border_color 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                  </div>
                  <p v-if="form.errors.border_color" class="text-xs text-red-600">
                    {{ form.errors.border_color }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- ADVANCED FEATURES CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <CpuChipIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.advanced_settings') }}
              </h2>
            </div>
            
            <div class="p-6">
              <!-- QR CODE SETTINGS -->
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.enable_qr_code') }}
                    </label>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ $t('gestlab.general.labels.vap_labels.qr_code_description') }}
                    </p>
                  </div>
                  <div class="flex items-center">
                    <input
                      v-model="form.has_qr_code"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </div>
                </div>
                
                <div v-if="form.has_qr_code" class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6 border-l-2 border-blue-200">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.qr_content') }}
                    </label>
                    <input
                      v-model="form.qr_code_content"
                      type="text"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                        form.errors.qr_code_content 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                    <p v-if="form.errors.qr_code_content" class="text-xs text-red-600">
                      {{ form.errors.qr_code_content }}
                    </p>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.qr_code_size') }}
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model="form.qr_code_size"
                        type="number"
                        step="0.1"
                        min="1"
                        max="50"
                        :class="[
                          'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                          form.errors.qr_code_size 
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                            : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                        ]"
                      />
                      <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                    </div>
                    <p v-if="form.errors.qr_code_size" class="text-xs text-red-600">
                      {{ form.errors.qr_code_size }}
                    </p>
                  </div>
                </div>
              </div>
              
              <!-- BARCODE SETTINGS -->
              <div class="space-y-4 mt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.enable_barcode') }}
                    </label>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_description') }}
                    </p>
                  </div>
                  <div class="flex items-center">
                    <input
                      v-model="form.has_barcode"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </div>
                </div>
                
                <div v-if="form.has_barcode" class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6 border-l-2 border-green-200">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_content') }}
                    </label>
                    <input
                      v-model="form.barcode_content"
                      type="text"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                        form.errors.barcode_content 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    />
                    <p v-if="form.errors.barcode_content" class="text-xs text-red-600">
                      {{ form.errors.barcode_content }}
                    </p>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_type') }}
                    </label>
                    <select
                      v-model="form.barcode_type"
                      :class="[
                        'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                        form.errors.barcode_type 
                          ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                          : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                      ]"
                    >
                      <option value="CODE128">CODE128</option>
                      <option value="CODE39">CODE39</option>
                      <option value="EAN13">EAN13</option>
                      <option value="UPCA">UPCA</option>
                      <option value="QRCODE">QR Code</option>
                    </select>
                    <p v-if="form.errors.barcode_type" class="text-xs text-red-600">
                      {{ form.errors.barcode_type }}
                    </p>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_width') }}
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model="form.barcode_width"
                        type="number"
                        step="0.1"
                        min="1"
                        max="50"
                        :class="[
                          'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                          form.errors.barcode_width 
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                            : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                        ]"
                      />
                      <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                    </div>
                    <p v-if="form.errors.barcode_width" class="text-xs text-red-600">
                      {{ form.errors.barcode_width }}
                    </p>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_height') }}
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model="form.barcode_height"
                        type="number"
                        step="0.1"
                        min="1"
                        max="50"
                        :class="[
                          'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                          form.errors.barcode_height 
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                            : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                        ]"
                      />
                      <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                    </div>
                    <p v-if="form.errors.barcode_height" class="text-xs text-red-600">
                      {{ form.errors.barcode_height }}
                    </p>
                  </div>
                </div>
              </div>
              
              <!-- LOGO SETTINGS -->
              <div class="space-y-4 mt-6">
                <div class="flex items-center justify-between">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.enable_logo') }}
                    </label>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ $t('gestlab.general.labels.vap_labels.logo_description') }}
                    </p>
                  </div>
                  <div class="flex items-center">
                    <input
                      v-model="enableLogo"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </div>
                </div>
                
                <div v-if="enableLogo" class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-6 border-l-2 border-yellow-200">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.logo_size') }}
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model="form.logo_size"
                        type="number"
                        step="0.1"
                        min="1"
                        max="50"
                        :class="[
                          'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                          form.errors.logo_size 
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                            : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                        ]"
                      />
                      <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                    </div>
                    <p v-if="form.errors.logo_size" class="text-xs text-red-600">
                      {{ form.errors.logo_size }}
                    </p>
                  </div>
                  
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.logo_file') }}
                    </label>
                    <input
                      @change="handleLogoUpload"
                      type="file"
                      accept="image/*"
                      :class="[
                        'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100',
                        form.errors.logo_path 
                          ? 'border-red-300' 
                          : 'border-gray-300'
                      ]"
                    />
                    <p v-if="form.errors.logo_path" class="text-xs text-red-600">
                      {{ form.errors.logo_path }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN (1/3 width) -->
        <div class="space-y-6">
          <!-- PREVIEW CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.preview') }}
            </h3>
            <div class="flex items-center justify-center p-4 bg-gray-50 rounded-lg">
              <div 
                class="relative border border-gray-300"
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
                  overflow: 'hidden',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: form.text_alignment
                }"
              >
                <!-- QR Code Preview -->
                <div 
                  v-if="form.has_qr_code"
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
                  v-if="form.has_barcode"
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
                  BAR
                </div>
                
                <!-- Logo Preview -->
                <div 
                  v-if="enableLogo"
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
                  {{ form.content || $t('gestlab.general.labels.vap_labels.preview_text') }}
                </div>
              </div>
            </div>
            <div class="mt-4 text-center text-sm text-gray-500">
              {{ form.width }} × {{ form.height }} mm
            </div>
          </div>

          <!-- TEMPLATES CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
            </h3>
            
            <div class="space-y-3">
              <div 
                v-for="template in templates"
                :key="template.id"
                @click="applyTemplate(template)"
                :class="[
                  'cursor-pointer p-3 rounded-lg border transition-all duration-200',
                  selectedTemplate?.id === template.id
                    ? 'border-blue-900 bg-blue-50'
                    : 'border-gray-200 hover:border-blue-900 hover:bg-blue-50'
                ]"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
                    <p class="text-xs text-gray-500 mt-1">{{ template.description }}</p>
                    <div class="mt-2">
                      <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                        {{ template.category }}
                      </span>
                    </div>
                  </div>
                  <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                    {{ $t('gestlab.general.labels.vap_labels.featured') }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ASSIGNMENT CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.assignment') }}
            </h3>
            
            <div class="space-y-4">
              <!-- LAB -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.lab') }}
                </label>
                <select
                  v-model="form.lab_id"
                  class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                >
                  <option value="">{{ $t('gestlab.general.labels.vap_labels.select_lab') }}</option>
                  <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                    {{ lab.name }}
                  </option>
                </select>
                <p v-if="form.errors.lab_id" class="text-xs text-red-600">
                  {{ form.errors.lab_id }}
                </p>
              </div>

              <!-- DEPARTMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.department') }}
                </label>
                <select
                  v-model="form.department_id"
                  class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                >
                  <option value="">{{ $t('gestlab.general.labels.vap_labels.select_department') }}</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- STATUS -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.status') }}
                </label>
                <div class="flex gap-2">
                  <button
                    type="button"
                    @click="form.is_active = true"
                    :class="[
                      'flex-1 py-2 text-sm font-medium rounded-lg border transition-all duration-200',
                      form.is_active
                        ? 'border-green-600 bg-green-50 text-green-700'
                        : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ $t('gestlab.general.labels.vap_labels.active') }}
                  </button>
                  <button
                    type="button"
                    @click="form.is_active = false"
                    :class="[
                      'flex-1 py-2 text-sm font-medium rounded-lg border transition-all duration-200',
                      !form.is_active
                        ? 'border-gray-600 bg-gray-50 text-gray-700'
                        : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    ]"
                  >
                    {{ $t('gestlab.general.labels.vap_labels.inactive') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ACTIONS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('actions.title') }}
            </h3>
            
            <div class="space-y-4">
              <button 
                type="submit"
                :disabled="form.processing"
                :class="[
                  'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                  form.processing
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <CheckIcon class="h-5 w-5" />
                {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : $t('gestlab.general.labels.vap_labels.buttons.update_label') }}
              </button>
              
              <div class="grid grid-cols-2 gap-3">
                <Link
                  :href="route('vap_labels.labels.show', label.id)"
                  class="inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                >
                  <XMarkIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
                </Link>
                
                <button
                  @click="resetForm"
                  type="button"
                  class="inline-flex justify-center items-center gap-2 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm font-semibold text-yellow-700 shadow-sm hover:bg-yellow-100 transition-all duration-200"
                >
                  <ArrowPathIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_labels.buttons.reset') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  TagIcon, 
  Cog6ToothIcon, 
  DocumentTextIcon, 
  PaintBrushIcon,
  CpuChipIcon,
  CheckIcon, 
  XMarkIcon,
  ArrowPathIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  label: Object,
  templates: Array,
  labs: Array,
  departments: Array,
})

const selectedTemplate = ref(null)
const enableLogo = ref(!!props.label.logo_path)

const form = useForm({
  name: props.label.name,
  type: props.label.type,
  content: props.label.content,
  width: props.label.width,
  height: props.label.height,
  background_color: props.label.background_color,
  text_color: props.label.text_color,
  font_size: props.label.font_size,
  border_width: props.label.border_width,
  border_color: props.label.border_color,
  text_alignment: props.label.text_alignment,
  lab_id: props.label.lab_id,
  department_id: props.label.department_id,
  logo_path: props.label.logo_path,
  logo_size: props.label.logo_size,
  has_qr_code: props.label.has_qr_code,
  qr_code_content: props.label.qr_code_content,
  qr_code_size: props.label.qr_code_size,
  has_barcode: props.label.has_barcode,
  barcode_content: props.label.barcode_content,
  barcode_type: props.label.barcode_type,
  barcode_width: props.label.barcode_width,
  barcode_height: props.label.barcode_height,
  is_active: props.label.is_active,
  source_type: props.label.template_data?.source_type || null,
  source_id: props.label.template_data?.source_id || null,
  template_id: props.label.template_data?.template_id || null,
})

const applyTemplate = (template) => {
  if (confirm('Aplicar este modelo? As configurações atuais serão substituídas.')) {
    selectedTemplate.value = template
    form.template_id = template.id
    if (template.template_data) {
      Object.keys(template.template_data).forEach(key => {
        if (key in form) {
          form[key] = template.template_data[key]
        }
      })
      // Special handling for logo
      if (template.template_data.logo_path) {
        enableLogo.value = true
      }
    }
  }
}

const handleLogoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      form.logo_path = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const resetForm = () => {
  if (confirm('Restaurar configurações originais?')) {
    Object.keys(props.label).forEach(key => {
      if (key in form) {
        form[key] = props.label[key]
      }
    })
    enableLogo.value = !!props.label.logo_path
    form.source_type = props.label.template_data?.source_type || null
    form.source_id = props.label.template_data?.source_id || null
    form.template_id = props.label.template_data?.template_id || null
    selectedTemplate.value = null
  }
}

const submit = () => {
  form.put(route('vap_labels.labels.update', props.label.id))
}

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800'
}
</script>
