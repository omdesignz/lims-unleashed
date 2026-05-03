<template>
  <!-- Main Container -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden file-list-container"
    @dragenter.prevent="handleDragEnter"
    @dragleave.prevent="handleDragLeave">
    
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
          <FolderIcon class="h-6 w-6 text-white" />
          <div>
            <h1 class="text-lg font-semibold text-white">
              {{ $t('gestlab.general.labels.vap_filemanager.page_title') }}
            </h1>
            <p class="text-xs text-blue-100">
              {{ filteredFiles.length }} visíveis / {{ fileStore.currentFiles.length }} no diretório atual
            </p>
          </div>
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-900">
            {{ filteredFiles.length }} {{ $t('gestlab.general.labels.vap_filemanager.items') }}
          </span>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="relative min-w-0 flex-1 sm:min-w-[18rem]">
            <input
              type="text"
              v-model="searchQuery"
              @input="debouncedSearch(searchQuery)"
              :placeholder="$t('gestlab.general.labels.vap_filemanager.search_files') + '...'"
              class="w-full pl-10 pr-4 py-2 rounded-lg border-0 bg-white/20 placeholder-blue-100 text-sm text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-800"
            />
            <MagnifyingGlassIcon class="h-4 w-4 text-blue-100 absolute left-3 top-1/2 transform -translate-y-1/2" />
          </div>
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-white/10 hover:bg-white/20 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/30 transition-colors duration-200"
            @click="showFilterDialog = true"
          >
            <FunnelIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_filemanager.filter') }}
          </button>
          <button
            v-if="hasActiveFilters"
            class="inline-flex items-center gap-2 rounded-lg bg-white/10 hover:bg-white/20 px-4 py-2 text-sm font-medium text-white ring-1 ring-white/30 transition-colors duration-200"
            @click="clearFilters"
          >
            <XMarkIcon class="h-4 w-4" />
            Limpar
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="hasActiveFilters"
      class="border-b border-blue-100 bg-blue-50/70 px-6 py-3"
    >
      <div class="flex flex-wrap gap-2">
        <span
          v-if="searchQuery"
          class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-blue-900 ring-1 ring-blue-100"
        >
          Pesquisa: {{ searchQuery }}
        </span>
        <span
          v-if="filterType.length"
          class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-blue-900 ring-1 ring-blue-100"
        >
          Tipo: {{ filterType.join(', ') }}
        </span>
        <span
          v-if="filterDateRange"
          class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-blue-900 ring-1 ring-blue-100"
        >
          Data: {{ filterDateRange }}
        </span>
        <span
          v-if="filterSize"
          class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-blue-900 ring-1 ring-blue-100"
        >
          Tamanho: {{ filterSize }}
        </span>
      </div>
    </div>

    <!-- Action Bar -->
    <div class="border-b border-gray-200 px-6 py-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <button
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="isUploading"
            @click="triggerFileUpload"
          >
            <CloudArrowUpIcon class="h-5 w-5" />
            <span v-if="isUploading">{{ $t('gestlab.general.labels.vap_filemanager.uploading') }}</span>
            <span v-else>{{ $t('gestlab.general.labels.vap_filemanager.upload_files') }}</span>
          </button>

          <Menu as="div" class="relative">
            <MenuButton
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <FolderPlusIcon class="h-5 w-5" />
              Mais ações
              <ChevronDownIcon class="h-4 w-4" />
            </MenuButton>
            <MenuItems class="absolute left-0 z-20 mt-2 w-56 origin-top-left rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-slate-900/5 focus:outline-none">
              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition"
                  :class="active ? 'bg-slate-100 text-slate-900' : ''"
                  :disabled="isUploading"
                  @click="triggerFolderUpload"
                >
                  <FolderPlusIcon class="h-4 w-4 text-slate-500" />
                  Importar pasta
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button
                  type="button"
                  class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition"
                  :class="active ? 'bg-slate-100 text-slate-900' : ''"
                  @click="startCreateFolder"
                >
                  <FolderIcon class="h-4 w-4 text-slate-500" />
                  Criar pasta
                </button>
              </MenuItem>
            </MenuItems>
          </Menu>
        </div>

        <!-- Breadcrumbs -->
        <div class="min-w-0">
          <Breadcrumbs />
        </div>
      </div>
    </div>

    <div class="border-b border-gray-200 bg-slate-50/80 px-6 py-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="filter in quickFilters"
            :key="filter.value"
            type="button"
            class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold transition"
            :class="statusFilter === filter.value
              ? 'bg-blue-900 text-white shadow-sm'
              : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100'"
            @click="statusFilter = filter.value"
          >
            <span>{{ filter.label }}</span>
            <span
              class="rounded-full px-2 py-0.5 text-[11px]"
              :class="statusFilter === filter.value ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'"
            >
              {{ filter.count }}
            </span>
          </button>
        </div>

        <div
          v-if="selectedCount"
          class="flex flex-wrap items-center gap-2 rounded-2xl border border-blue-200 bg-white px-3 py-2 shadow-sm"
        >
          <span class="text-sm font-medium text-slate-700">
            {{ selectedCount }} {{ selectedCount > 1 ? 'itens selecionados' : 'item selecionado' }}
          </span>
          <button
            type="button"
            class="rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-800"
            @click="archiveSelected"
          >
            Arquivar
          </button>
          <button
            v-if="singleSelectedFile?.type === 'file'"
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            @click="fileStore.downloadFile(singleSelectedFile.id)"
          >
            Transferir
          </button>
          <button
            type="button"
            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50"
            @click="deleteSelected"
          >
            Eliminar
          </button>
          <button
            type="button"
            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100"
            @click="clearSelection"
          >
            Limpar
          </button>
        </div>
      </div>
    </div>

    <!-- Root Drop Area Indicator -->
    <div 
      v-if="isDraggingFiles && !dragOverItem" 
      class="absolute inset-0 border-4 border-dashed border-blue-400 bg-blue-50/95 backdrop-blur-sm flex items-center justify-center z-10 pointer-events-none"
    >
      <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center gap-3">
        <FolderIcon class="h-12 w-12 text-blue-900" />
        <p class="text-lg font-medium text-blue-900">
          {{ isDraggingExternal 
            ? $t('gestlab.general.labels.vap_filemanager.drop_to_upload_to_current_folder')
            : $t('gestlab.general.labels.vap_filemanager.drop_to_move_to_current_folder')
          }}
        </p>
        <p class="text-sm text-gray-600">
          {{ $t('gestlab.general.labels.vap_filemanager.drop_files_anywhere') }}
        </p>
      </div>
    </div>

    <div class="divide-y divide-gray-200 md:hidden">
      <article
        v-for="file in filteredFiles"
        :key="`mobile-${file.id}`"
        class="space-y-4 px-5 py-4 transition"
        :class="fileStore.selectedItems.has(file.id) ? 'bg-blue-50/70' : ''"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex min-w-0 items-center gap-3">
            <input
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-900"
              :checked="fileStore.selectedItems.has(file.id)"
              @change="toggleSelection(file.id)"
            />
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50">
              <FolderIcon
                v-if="file.type === 'folder'"
                class="h-5 w-5 text-blue-900"
              />
              <DocumentIcon
                v-else
                class="h-5 w-5 text-blue-700"
              />
            </div>
            <div class="min-w-0">
              <button
                type="button"
                class="truncate text-left text-sm font-semibold text-gray-900 transition hover:text-blue-900"
                @click="file.type === 'folder' ? navigateToFolder(file.id) : handleItemClick(file, $event)"
              >
                {{ file.name }}
              </button>
              <p class="mt-1 text-xs text-gray-500">
                {{ file.type === 'folder' ? 'Pasta' : (file.document_type || 'Ficheiro') }}
              </p>
            </div>
          </div>

          <span
            v-if="file.status"
            class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
            :class="statusBadgeClass(file)"
          >
            {{ formatStatusLabel(file.status) }}
          </span>
        </div>

        <dl class="grid grid-cols-2 gap-3">
          <div class="rounded-xl bg-gray-50 px-3 py-2">
            <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
              {{ $t('gestlab.general.labels.vap_filemanager.size') }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ formatSize(file.size) || '—' }}
            </dd>
          </div>
          <div class="rounded-xl bg-gray-50 px-3 py-2">
            <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
              {{ $t('gestlab.general.labels.vap_filemanager.modified') }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ formatDate(file.modifiedAt) || '—' }}
            </dd>
          </div>
        </dl>

        <div class="flex flex-wrap gap-2">
          <span
            v-if="file.revision_code"
            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"
          >
            {{ file.revision_code }}
          </span>
          <span
            v-if="file.document_number"
            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"
          >
            {{ file.document_number }}
          </span>
          <span
            v-if="isReviewOverdue(file)"
            class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700"
          >
            Revisão em atraso
          </span>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3">
          <div class="flex flex-wrap items-center gap-2 text-sm font-medium">
            <button
              v-if="canPreview(file)"
              class="rounded-lg bg-purple-50 px-3 py-1.5 text-purple-700 transition hover:bg-purple-100 hover:text-purple-800"
              @click="previewItem(file.id)"
            >
              Pré-visualizar
            </button>
            <button
              v-if="file.type === 'file'"
              class="rounded-lg bg-blue-50 px-3 py-1.5 text-blue-700 transition hover:bg-blue-100 hover:text-blue-800"
              @click.stop="fileStore.downloadFile(file.id)"
            >
              Transferir
            </button>
          </div>
          <Menu as="div" class="relative">
            <MenuButton class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700">
              <EllipsisHorizontalIcon class="h-5 w-5" />
            </MenuButton>
            <MenuItems class="absolute right-0 z-20 mt-2 w-52 origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-slate-900/5 focus:outline-none">
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startMove(file.id)">
                  <ArrowsRightLeftIcon class="h-4 w-4 text-slate-500" />
                  Mover
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }" v-if="file.type === 'file'">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="showVersionHistory(file.id)">
                  <ClockIcon class="h-4 w-4 text-slate-500" />
                  Histórico de versões
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startRename(file.id)">
                  <PencilIcon class="h-4 w-4 text-slate-500" />
                  Renomear
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startShare(file.id)">
                  <ShareIcon class="h-4 w-4 text-slate-500" />
                  Partilhar
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="showTagManager(file.id)">
                  <TagIcon class="h-4 w-4 text-slate-500" />
                  Etiquetas
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="fileStore.archiveItem(file.id)">
                  <ArchiveBoxIcon class="h-4 w-4 text-slate-500" />
                  Arquivar
                </button>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-red-700 transition" :class="active ? 'bg-red-50 text-red-800' : ''" @click="startDelete(file.id)">
                  <TrashIcon class="h-4 w-4 text-red-500" />
                  Eliminar
                </button>
              </MenuItem>
            </MenuItems>
          </Menu>
        </div>
      </article>

      <div v-if="filteredFiles.length === 0" class="px-6 py-10 text-center">
        <FolderIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-3 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_filemanager.no_files_found') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery
            ? $t('gestlab.general.labels.vap_filemanager.try_different_search')
            : $t('gestlab.general.labels.vap_filemanager.upload_files_to_get_started')
          }}
        </p>
      </div>
    </div>

    <!-- File Table -->
    <div class="hidden overflow-x-auto md:block">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="w-12 px-4 py-3">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-900"
                :checked="allVisibleSelected"
                :indeterminate="someVisibleSelected && !allVisibleSelected"
                @change="toggleSelectVisible"
              />
            </th>
            <th 
              scope="col" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              <button 
                class="flex items-center gap-1 hover:text-blue-900 transition-colors duration-200"
                @click="sortField = 'name'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.name') }}</span>
                <ChevronUpIcon v-if="sortField === 'name' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'name' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th 
              scope="col" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              <button 
                class="flex items-center gap-1 hover:text-blue-900 transition-colors duration-200"
                @click="sortField = 'size'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.size') }}</span>
                <ChevronUpIcon v-if="sortField === 'size' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'size' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th 
              scope="col" 
              class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              <button 
                class="flex items-center gap-1 hover:text-blue-900 transition-colors duration-200"
                @click="sortField = 'modifiedAt'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
              >
                <span>{{ $t('gestlab.general.labels.vap_filemanager.modified') }}</span>
                <ChevronUpIcon v-if="sortField === 'modifiedAt' && sortDirection === 'asc'" class="h-4 w-4" />
                <ChevronDownIcon v-if="sortField === 'modifiedAt' && sortDirection === 'desc'" class="h-4 w-4" />
              </button>
            </th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">
              {{ $t('gestlab.general.labels.vap_filemanager.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr 
            v-for="file in filteredFiles" 
            :key="file.id" 
            class="hover:bg-blue-50/30 transition-colors duration-200 relative group"
            :class="{
              'bg-blue-50': dragOverItem === file.id && file.type === 'folder',
              'bg-blue-50/70 ring-1 ring-inset ring-blue-200': fileStore.selectedItems.has(file.id),
              'cursor-move': !isUploading
            }"
            draggable="true"
            @dragstart="handleDragStart($event, file.id)"
            @dragenter.prevent="handleDragEnter"
            @dragover.prevent="handleDragOver($event, file.id)"
            @dragleave.prevent="handleDragLeave"
            @drop.prevent="handleDrop($event, file.id)"
            >
            <td class="px-4 py-4 whitespace-nowrap">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-900 focus:ring-blue-900"
                :checked="fileStore.selectedItems.has(file.id)"
                @change="toggleSelection(file.id)"
              />
            </td>
            <!-- Folder Drop Indicator -->
            <td 
              v-if="file.type === 'folder' && dragOverItem === file.id"
              class="absolute inset-0 border-4 border-dashed border-blue-400 bg-blue-50/90 backdrop-blur-sm z-10 pointer-events-none"
              colspan="5"
            >
              <div class="absolute inset-0 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-lg px-4 py-2 flex items-center gap-2">
                  <FolderIcon class="h-5 w-5 text-blue-900" />
                  <span class="text-blue-900 font-medium">
                    {{ isDraggingExternal 
                      ? $t('gestlab.general.labels.vap_filemanager.upload_to') + ' "' + file.name + '"'
                      : $t('gestlab.general.labels.vap_filemanager.move_to') + ' "' + file.name + '"'
                    }}
                  </span>
                </div>
              </div>
            </td>

            <!-- Name Column -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 group-hover:bg-blue-100 transition-colors duration-200">
                  <FolderIcon
                    v-if="file.type === 'folder'"
                    class="h-5 w-5 text-blue-900"
                  />
                  <DocumentIcon
                    v-else
                    class="h-5 w-5 text-blue-700"
                  />
                </div>
                <span 
                  class="text-sm font-medium text-gray-900 cursor-pointer hover:text-blue-900 transition-colors duration-200"
                  @click="file.type === 'folder' ? navigateToFolder(file.id) : handleItemClick(file, $event)"
                >
                  {{ file.name }}
                </span>
                <div class="mt-1 flex flex-wrap gap-2">
                  <span
                    v-if="file.status"
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
                    :class="statusBadgeClass(file)"
                  >
                    {{ formatStatusLabel(file.status) }}
                  </span>
                  <span
                    v-if="file.revision_code"
                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                  >
                    {{ file.revision_code }}
                  </span>
                  <span
                    v-if="isReviewOverdue(file)"
                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                  >
                    Revisão em atraso
                  </span>
                </div>
              </div>
            </td>

            <!-- Size Column -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="text-sm text-gray-600">
                {{ formatSize(file.size) }}
              </span>
            </td>

            <!-- Modified Date Column -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <ClockIcon class="h-4 w-4 text-gray-400" />
                <span class="text-sm text-gray-600">
                  {{ formatDate(file.modifiedAt) }}
                </span>
              </div>
            </td>

            <!-- Actions Column -->
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="flex items-center justify-end gap-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                <button
                  v-if="canPreview(file)"
                  class="inline-flex items-center gap-2 rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700 transition hover:bg-purple-100"
                  @click="previewItem(file.id)"
                  :title="$t('gestlab.general.labels.vap_filemanager.preview')"
                >
                  <EyeIcon class="h-4 w-4" />
                  Pré-visualizar
                </button>

                <button
                  v-if="file.type === 'file'"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                  @click.stop="fileStore.downloadFile(file.id)"
                  :title="$t('gestlab.general.labels.vap_filemanager.download')"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  Transferir
                </button>

                <Menu as="div" class="relative">
                  <MenuButton class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700">
                    <EllipsisHorizontalIcon class="h-5 w-5" />
                  </MenuButton>
                  <MenuItems class="absolute right-0 z-20 mt-2 w-56 origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-xl ring-1 ring-slate-900/5 focus:outline-none">
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startMove(file.id)">
                        <ArrowsRightLeftIcon class="h-4 w-4 text-slate-500" />
                        Mover
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" v-if="file.type === 'file'">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="showVersionHistory(file.id)">
                        <ClockIcon class="h-4 w-4 text-slate-500" />
                        Histórico de versões
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startRename(file.id)">
                        <PencilIcon class="h-4 w-4 text-slate-500" />
                        Renomear
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="startShare(file.id)">
                        <ShareIcon class="h-4 w-4 text-slate-500" />
                        Partilhar
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="showTagManager(file.id)">
                        <TagIcon class="h-4 w-4 text-slate-500" />
                        Etiquetas
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition" :class="active ? 'bg-slate-100 text-slate-900' : ''" @click="fileStore.archiveItem(file.id)">
                        <ArchiveBoxIcon class="h-4 w-4 text-slate-500" />
                        Arquivar
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button type="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-red-700 transition" :class="active ? 'bg-red-50 text-red-800' : ''" @click="startDelete(file.id)">
                        <TrashIcon class="h-4 w-4 text-red-500" />
                        Eliminar
                      </button>
                    </MenuItem>
                  </MenuItems>
                </Menu>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="filteredFiles.length === 0">
            <td colspan="5" class="px-6 py-8 text-center">
              <div class="flex flex-col items-center gap-3">
                <FolderIcon class="h-12 w-12 text-gray-300" />
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.vap_filemanager.no_files_found') }}
                  </h3>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ searchQuery 
                      ? $t('gestlab.general.labels.vap_filemanager.try_different_search')
                      : $t('gestlab.general.labels.vap_filemanager.upload_files_to_get_started')
                    }}
                  </p>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Loading Overlay -->
    <div 
      v-if="isUploading" 
      class="absolute inset-0 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center z-20"
    >
      <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center gap-4">
        <div class="h-10 w-10 animate-spin rounded-full border-4 border-blue-200 border-t-blue-900"></div>
        <div class="text-center">
          <p class="text-sm font-medium text-gray-900">
            {{ $t('gestlab.general.labels.vap_filemanager.uploading') }}...
          </p>
          <p class="text-xs text-gray-500 mt-1">
            {{ $t('gestlab.general.labels.vap_filemanager.please_wait') }}
          </p>
        </div>
        <div v-if="Object.keys(uploadProgress).length > 0" class="w-64">
          <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div 
              class="h-full bg-gradient-to-r from-blue-900 to-blue-800 transition-all duration-300"
              :style="{ width: `${totalProgress}%` }"
            ></div>
          </div>
          <div class="text-xs text-gray-600 mt-2 text-center">
            {{ totalProgress }}% {{ $t('gestlab.general.labels.vap_filemanager.complete') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden Inputs -->
    <input
      ref="fileInput"
      type="file"
      multiple
      class="hidden"
      @change="handleFileUpload"
    />
    <input
      ref="folderInput"
      type="file"
      webkitdirectory
      class="hidden"
      @change="handleFolderUpload"
    />

    <!-- Move Dialog -->

    <Dialog :open="showMoveDialog" @close="showMoveDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <DialogTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
              <ArrowsRightLeftIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.vap_filemanager.move_item') }}
            </DialogTitle>
            <p class="mt-1 text-sm text-slate-500">Escolha a pasta de destino para manter a estrutura documental organizada.</p>
          </div>
          <div class="p-6">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.vap_filemanager.destination_folder') }}
            </label>
            <div class="mt-1">
              <comboboxEnhanced v-model="movingToFolderId" :load-options="loadFolders" @update:model-value="" />
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button
              type="button"
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="showMoveDialog = false"
            >
             {{ $t('gestlab.general.buttons.cancel') }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="confirmMove"
            >
              {{ $t('gestlab.general.buttons.move') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Preview Dialog -->
    <FilePreview
      :is-open="showPreviewDialog"
      :file="previewFile"
      @close="showPreviewDialog = false"
    />

    <!-- Version History Dialog -->
    <FileVersionHistory
      :is-open="showVersionHistoryDialog"
      :file-id="versionHistoryFileId"
      @close="showVersionHistoryDialog = false"
    />

     <!-- Tag Manager Dialog -->
    <Dialog :open="showTagDialog" @close="showTagDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <DialogTitle class="flex items-center justify-between text-lg font-semibold text-slate-900">
            <span>{{ $t('gestlab.general.labels.vap_filemanager.manage_tags') }}</span>
            <button @click="showTagDialog = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="h-6 w-6" />
            </button>
          </DialogTitle>
          <p class="mt-1 text-sm text-slate-500">Organize o ficheiro com etiquetas consistentes para facilitar pesquisa e rastreabilidade.</p>
          </div>
          <div class="p-6">
          <TagManager v-if="tagFileId" :file-id="tagFileId" />
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Filter Dialog -->
    <Dialog :open="showFilterDialog" @close="showFilterDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <DialogTitle class="text-lg font-semibold text-slate-900">{{ $t('gestlab.general.labels.vap_filemanager.filter_files') }}</DialogTitle>
            <p class="mt-1 text-sm text-slate-500">Refine a vista sem perder contexto operacional.</p>
          </div>
          <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_type') }}</label>
              <div class="space-x-2">
                <label class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="filterType"
                    value="file"
                    class="rounded border-gray-300 text-blue-600"
                  />
                  <span class="ml-2">{{ $t('gestlab.general.labels.vap_filemanager.files') }}</span>
                </label>
                <label class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="filterType"
                    value="folder"
                    class="rounded border-gray-300 text-blue-600"
                  />
                  <span class="ml-2">{{ $t('gestlab.general.labels.vap_filemanager.folders') }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_range') }}</label>
              <select
                v-model="filterDateRange"
                class="w-full rounded-md border-gray-300"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.all_time') }}</option>
                <option value="7days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.7days') }}</option>
                <option value="30days">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.30days') }}</option>
                <option value="custom">{{ $t('gestlab.general.labels.vap_filemanager.filter_date_ranges.custom') }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('gestlab.general.labels.vap_filemanager.filter_size') }}</label>
              <select
                v-model="filterSize"
                class="w-full rounded-md border-gray-300"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.any') }}</option>
                <option value="small">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.small') }}</option>
                <option value="medium">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.medium') }}</option>
                <option value="large">{{ $t('gestlab.general.labels.vap_filemanager.filter_sizes.large') }}</option>
              </select>
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="() => {
                filterType = []
                filterDateRange = null
                filterSize = null
                showFilterDialog = false
              }"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.filter_reset') }}
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              @click="showFilterDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.filter_apply') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Dialogs (unchanged from your code, just wrapped in styling) -->
    <!-- Rename Dialog -->
    <Dialog :open="showRenameDialog" @close="showRenameDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <DialogTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <PencilIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_filemanager.rename_item') }}
          </DialogTitle>
          <p class="mt-1 text-sm text-slate-500">Use nomes claros para manter a recuperação e a trilha de auditoria limpas.</p>
          </div>
          <div class="p-6">
          <input
            type="text"
            v-model="newItemName"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 mb-6"
            @keyup.enter="confirmRename"
            :placeholder="$t('gestlab.general.labels.vap_filemanager.enter_new_name')"
          />
          <div class="flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="showRenameDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="confirmRename"
              :disabled="!newItemName.trim()"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.rename') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Continue with other dialogs (Share, Move, Delete, Create Folder, etc.) using the same styling pattern -->
    <!-- Share Dialog -->
    <Dialog :open="showShareDialog" @close="showShareDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <DialogTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <ShareIcon class="h-5 w-5 text-green-600" /> 
            {{ $t('gestlab.general.labels.vap_filemanager.share_item') }}
          </DialogTitle>
          <p class="mt-1 text-sm text-slate-500">Partilhe com intenção e preserve a confidencialidade documental.</p>
          </div>
          <div class="p-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Destinatário
              </label>
              <comboboxEnhanced v-model="shareRecipient" :load-options="loadUsers" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ $t('gestlab.general.labels.vap_filemanager.permissions') }}
              </label>
              <select
                v-model="shareAccess"
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20"
              >
                <option value="read">{{ $t('gestlab.general.labels.vap_filemanager.read') }}</option>
                <option value="write">{{ $t('gestlab.general.labels.vap_filemanager.write') }}</option>
                <option value="admin">{{ $t('gestlab.general.labels.vap_filemanager.admin') }}</option>
              </select>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="showShareDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="confirmShare"
              :disabled="!selectedShareRecipientId"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.share') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog :open="showDeleteDialog" @close="showDeleteDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
          <div class="border-b border-red-100 bg-red-50 px-6 py-4">
          <DialogTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
            {{ $t('gestlab.general.labels.vap_filemanager.delete_item') }}
          </DialogTitle>
          <p class="mt-1 text-sm text-red-700/80">Esta acção remove permanentemente o registo e os seus dados de rastreabilidade.</p>
          </div>
          <div class="p-6">
          <p class="text-gray-600 mb-4">
            {{ $t('gestlab.general.labels.vap_filemanager.prompts.delete') }}
            <span class="font-semibold text-red-600">{{ itemToDelete?.name }}</span>?
            {{ $t('gestlab.general.labels.vap_filemanager.prompts.action_cannot_be_undone') }}.
          </p>
          <div class="flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="showDeleteDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-red-600 to-red-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-red-500 hover:to-red-400 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 transition-all duration-200"
              @click="confirmDelete"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.delete') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Create Folder Dialog -->
    <Dialog :open="showCreateFolderDialog" @close="showCreateFolderDialog = false" class="relative z-50">
      <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <DialogTitle class="flex items-center gap-2 text-lg font-semibold text-slate-900">
            <FolderPlusIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_filemanager.create_folder') }}
          </DialogTitle>
          <p class="mt-1 text-sm text-slate-500">Crie uma pasta bem identificada para agrupar procedimentos e registos de forma lógica.</p>
          </div>
          <div class="p-6">
          <input
            v-model="newFolderName"
            type="text"
            :placeholder="$t('gestlab.general.labels.vap_filemanager.create_folder_name')"
            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 mb-6"
            @keyup.enter="confirmCreateFolder"
          />
          <div class="flex justify-end gap-3">
            <button
              class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              @click="showCreateFolderDialog = false"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.cancel') }}
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              @click="confirmCreateFolder"
              :disabled="!newFolderName.trim()"
            >
              {{ $t('gestlab.general.labels.vap_filemanager.buttons.create') }}
            </button>
          </div>
          </div>
        </DialogPanel>
      </div>
    </Dialog>

    <!-- Other dialogs (Move, Filter, Tag Manager, Version History, File Preview, Override) -->
    <!-- Keep them as they were, just make sure they follow the same styling pattern if you want consistency -->

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { 
  FolderIcon, 
  DocumentIcon, 
  TrashIcon,
  PencilIcon,
  ArchiveBoxIcon,
  ShareIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  EllipsisHorizontalIcon,
  ClockIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  TagIcon,
  CloudArrowUpIcon,
  FolderPlusIcon,
  ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline'
import FilePreview from './file-preview.vue'
import FileVersionHistory from './file-version-history.vue'
import TagManager from './tag-manager.vue'
import { useFileStore } from '../../Stores/fileStore'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import { useDebounceFn } from '@vueuse/core'
import Breadcrumbs from './breadcrumbs.vue'
import { trans } from 'laravel-vue-i18n';
import comboboxEnhanced from '@/Components/combobox-enhanced.vue';

type ComboboxOption = {
  value: string
  label: string
}

const toast = useToast()
const fileStore = useFileStore()
const fileInput = ref<HTMLInputElement | null>(null)
const folderInput = ref<HTMLInputElement | null>(null)
const showRenameDialog = ref(false)
const showMoveDialog = ref(false)
const showShareDialog = ref(false)
const showPreviewDialog = ref(false)
const showFilterDialog = ref(false)
const showVersionHistoryDialog = ref(false)
const showDeleteDialog = ref(false)
const showTagDialog = ref(false)
const tagFileId = ref<string | null>(null)
const versionHistoryFileId = ref('')
const previewFile = ref<{
  name: string
  content: ArrayBuffer
  mimeType?: string
} | null>(null)
const renamingItemId = ref('')
const newItemName = ref('')
const sharingItemId = ref('')
const movingItemId = ref('')
const movingToFolderId = ref<ComboboxOption | string | null>(null)
const shareRecipient = ref<ComboboxOption | string | null>(null)
const shareAccess = ref<'read' | 'write' | 'admin'>('read')

// Dragging State
const isUploading = ref(false)
const uploadProgress = ref<{ [key: string]: number }>({})
const draggedItem = ref<string | null>(null)
const dragOverItem = ref<string | null>(null)
const isDraggingFiles = ref(false)
const isDraggingExternal = ref(false)

// Debounced search function
const debouncedSearch = useDebounceFn((query: string) => {
  fileStore.searchFiles(query)
}, 300)

const isDragging = ref(false)
const clipboard = ref<{ action: 'copy' | 'cut'; items: string[] } | null>(null)
const itemToDelete = ref<{ id: string; name: string; type: 'file' | 'folder' } | null>(null)

const isSelecting = ref(false)
const lastSelectedId = ref<string | null>(null)

// Search and filter state
const searchQuery = ref(fileStore.searchQuery)
const sortField = ref<'name' | 'size' | 'modifiedAt'>('name')
const sortDirection = ref<'asc' | 'desc'>('asc')
const filterType = ref<string[]>([])
const filterDateRange = ref<'7days' | '30days' | 'custom' | null>(null)
const filterSize = ref<'small' | 'medium' | 'large' | null>(null)

const showCreateFolderDialog = ref(false)
const newFolderName = ref('')
const statusFilter = ref<'all' | 'draft' | 'in_review' | 'effective' | 'review_due' | 'controlled'>('all')
const selectedFiles = computed(() => filteredFiles.value.filter((file) => fileStore.selectedItems.has(file.id)))
const selectedCount = computed(() => selectedFiles.value.length)
const singleSelectedFile = computed(() => selectedCount.value === 1 ? selectedFiles.value[0] : null)
const allVisibleSelected = computed(() => filteredFiles.value.length > 0 && filteredFiles.value.every((file) => fileStore.selectedItems.has(file.id)))
const someVisibleSelected = computed(() => filteredFiles.value.some((file) => fileStore.selectedItems.has(file.id)))
const selectedShareRecipientId = computed(() => extractOptionValue(shareRecipient.value))

const quickFilters = computed(() => {
  const visibleFiles = fileStore.currentFiles

  return [
    { value: 'all', label: 'Todos', count: visibleFiles.length },
    { value: 'draft', label: 'Rascunhos', count: visibleFiles.filter((file) => file.status === 'draft').length },
    { value: 'in_review', label: 'Em revisão', count: visibleFiles.filter((file) => file.status === 'in_review').length },
    { value: 'effective', label: 'Efetivos', count: visibleFiles.filter((file) => file.status === 'effective').length },
    { value: 'controlled', label: 'Controlados', count: visibleFiles.filter((file) => file.is_controlled).length },
    { value: 'review_due', label: 'Revisão vencida', count: visibleFiles.filter((file) => isReviewOverdue(file)).length },
  ]
})

const filteredFiles = computed(() => {
  let files = [...fileStore.currentFiles]

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    files = files.filter(file => file.name.toLowerCase().includes(query))
  }

  // Apply type filter
  if (filterType.value.length > 0) {
    files = files.filter(file => filterType.value.includes(file.type))
  }

  // Apply date filter
  if (filterDateRange.value) {
    const now = new Date()
    const days = filterDateRange.value === '7days' ? 7 : 30
    const cutoff = new Date(now.setDate(now.getDate() - days))
    files = files.filter(file => file.modifiedAt >= cutoff)
  }

  // Apply size filter
  if (filterSize.value) {
    const sizes = {
      small: 1024 * 1024, // 1MB
      medium: 10 * 1024 * 1024, // 10MB
      large: 100 * 1024 * 1024 // 100MB
    }
    files = files.filter(file => {
      if (!file.size) return false
      switch (filterSize.value) {
        case 'small': return file.size < sizes.small
        case 'medium': return file.size >= sizes.small && file.size < sizes.medium
        case 'large': return file.size >= sizes.medium
        default: return true
      }
    })
  }

  if (statusFilter.value !== 'all') {
    files = files.filter((file) => {
      if (statusFilter.value === 'controlled') {
        return file.is_controlled
      }

      if (statusFilter.value === 'review_due') {
        return isReviewOverdue(file)
      }

      return file.status === statusFilter.value
    })
  }

  // Apply sorting
  files.sort((a, b) => {
    if (a.type === 'folder' && b.type !== 'folder') return -1
    if (a.type !== 'folder' && b.type === 'folder') return 1

    let comparison = 0
    switch (sortField.value) {
      case 'name':
        comparison = a.name.localeCompare(b.name)
        break
      case 'size':
        comparison = (a.size || 0) - (b.size || 0)
        break
      case 'modifiedAt':
        comparison = a.modifiedAt.getTime() - b.modifiedAt.getTime()
        break
    }
    return sortDirection.value === 'asc' ? comparison : -comparison
  })

  return files
})

const hasActiveFilters = computed(() => {
  return Boolean(
    searchQuery.value ||
      filterType.value.length ||
      filterDateRange.value ||
      filterSize.value,
  )
})

function clearFilters() {
  searchQuery.value = ''
  fileStore.searchQuery = ''
  filterType.value = []
  filterDateRange.value = null
  filterSize.value = null
  statusFilter.value = 'all'
  fileStore.loadFiles()
}

function extractOptionValue(option: ComboboxOption | string | null): string | null {
  if (!option) {
    return null
  }

  if (typeof option === 'string') {
    return option
  }

  return option.value
}

function formatStatusLabel(status?: string | null): string {
  return (status || 'draft').replaceAll('_', ' ')
}

function statusBadgeClass(file: { status?: string | null }) {
  const status = file.status || 'draft'

  if (status === 'effective') {
    return 'bg-emerald-100 text-emerald-700'
  }

  if (status === 'in_review') {
    return 'bg-amber-100 text-amber-700'
  }

  if (status === 'approved') {
    return 'bg-sky-100 text-sky-700'
  }

  if (status === 'obsolete' || status === 'archived') {
    return 'bg-rose-100 text-rose-700'
  }

  return 'bg-slate-100 text-slate-700'
}

function isReviewOverdue(file: { review_due_at?: string | null; status?: string | null }) {
  if (!file.review_due_at || file.status === 'archived') {
    return false
  }

  return new Date(file.review_due_at).getTime() < Date.now()
}

function toggleSelection(id: string) {
  if (fileStore.selectedItems.has(id)) {
    fileStore.selectedItems.delete(id)
    return
  }

  fileStore.selectedItems.add(id)
}

function toggleSelectVisible() {
  if (allVisibleSelected.value) {
    filteredFiles.value.forEach((file) => fileStore.selectedItems.delete(file.id))
    return
  }

  filteredFiles.value.forEach((file) => fileStore.selectedItems.add(file.id))
}

function clearSelection() {
  fileStore.selectedItems.clear()
}

async function archiveSelected() {
  await Promise.all(selectedFiles.value.map((file) => fileStore.archiveItem(file.id)))
  clearSelection()
}

async function deleteSelected() {
  await Promise.all(selectedFiles.value.map((file) => fileStore.permanentlyDeleteItem(file.id)))
  clearSelection()
}

function handleDragEnter(event: DragEvent) {
  event.preventDefault()
  if (!draggedItem.value && event.dataTransfer?.types.includes('Files')) {
    isDraggingExternal.value = true
  }
}

function handleDragLeave(event: DragEvent) {
  event.preventDefault()
  if (!event.relatedTarget || !(event.relatedTarget as Element).closest('.file-list-container')) {
    isDraggingExternal.value = false
    dragOverItem.value = null
  }
}

// Working Drag Leave
// function handleDragLeave(event: DragEvent) {
//   event.preventDefault()
//   const target = event.relatedTarget as Node | null
//   if (!target || !event.currentTarget?.contains(target)) {
//     isDraggingFiles.value = false
//     isDraggingExternal.value = false
//   }
//   dragOverItem.value = null
// }

async function handleFileDrop(event: DragEvent, targetFolderId: string | null = null) {
  if (!event.dataTransfer?.items) return

  try {
    isUploading.value = true
    const items = Array.from(event.dataTransfer.items)
    
    for (const item of items) {
      const entry = item.webkitGetAsEntry && item.webkitGetAsEntry()
      
      if (!entry) {
        // Fallback for browsers that don't support webkitGetAsEntry
        const file = item.getAsFile()
        if (file) {
          const formData = new FormData()
          formData.append('file', file)
          formData.append('parent_id', targetFolderId || fileStore.currentFolder || '')
          
          try {
            await fileStore.uploadSingleFile(formData)
          } catch (error) {
            console.error('Error uploading file:', error)
            // toast.error(`Failed to upload: ${file.name}`)
            toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + file.name)
          }
        }
        continue
      }

      if (entry.isDirectory) {
        await processDirectoryEntry(entry, targetFolderId)
      } else if (entry.isFile) {
        await processFileEntry(entry, targetFolderId)
      }
    }
  } catch (error) {
    console.error('Error handling drop:', error)
    // toast.error('Failed to process dropped items')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_process_dropped_items'))
  } finally {
    isUploading.value = false
  }
}

async function processDirectoryEntry(entry: any, parentId: string | null = null) {
  try {
    // Create the folder
    const folder = await fileStore.createFolder(entry.name, parentId)
    
    // Read directory contents
    const dirReader = entry.createReader()
    const entries: any[] = await new Promise((resolve, reject) => {
      dirReader.readEntries(resolve, reject)
    })

    // Process all entries
    for (const childEntry of entries) {
      if (childEntry.isDirectory) {
        await processDirectoryEntry(childEntry, folder.id)
      } else {
        await processFileEntry(childEntry, folder.id)
      }
    }
  } catch (error) {
    console.error(`Error processing directory ${entry.name}:`, error)
    // toast.error(`Failed to process directory: ${entry.name}`)
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_process_folder') + ' - ' + entry.name)
  }
}

async function processFileEntry(entry: any, parentId: string | null = null) {
  try {
    const file: File = await new Promise((resolve, reject) => {
      entry.file(resolve, reject)
    })

    const formData = new FormData()
    formData.append('file', file)
    formData.append('parent_id', parentId || fileStore.currentFolder || '')

    await fileStore.uploadSingleFile(formData)
  } catch (error) {
    console.error(`Error processing file ${entry.name}:`, error)
    // toast.error(`Failed to upload: ${entry.name}`)
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + entry.name)
  } finally {
    delete uploadProgress.value[entry.fullPath]
  }
}


// File/folder movement dragging
function handleDragStart(event: DragEvent, fileId: string) {
  if (!event.dataTransfer) return
  draggedItem.value = fileId
  event.dataTransfer.effectAllowed = 'move'
  
  // Add drag image
  const draggedFile = fileStore.files.find(f => f.id === fileId)
  if (draggedFile) {
    const dragImage = document.createElement('div')
    dragImage.className = 'fixed top-0 left-0 bg-white shadow-lg rounded-lg p-2 pointer-events-none'
    dragImage.innerHTML = `
      <div class="flex items-center">
        <span class="mr-2">${draggedFile.type === 'folder' ? '📁' : '📄'}</span>
        <span>${draggedFile.name}</span>
      </div>
    `
    document.body.appendChild(dragImage)
    event.dataTransfer.setDragImage(dragImage, 0, 0)
    setTimeout(() => document.body.removeChild(dragImage), 0)
  }
}


function handleDragOver(event: DragEvent, fileId: string | null = null) {
  event.preventDefault()
  
  // Check if this is an external drag (files from outside)
  if (!draggedItem.value && event.dataTransfer?.types.includes('Files')) {
    isDraggingExternal.value = true
  }
  
  dragOverItem.value = fileId
  event.dataTransfer!.dropEffect = isDraggingExternal.value ? 'copy' : 'move'
}

async function handleDrop(event: DragEvent, targetId: string | null = null) {
  event.preventDefault()
  isDraggingFiles.value = false
  isDraggingExternal.value = false
  
  // Handle file/folder uploads if items are dragged from outside
  if (event.dataTransfer?.items && !draggedItem.value) {
    await handleFileDrop(event, targetId || fileStore.currentFolder)
    return
  }
  
  // Handle internal moves
  if (!draggedItem.value) return

  try {
    // Don't move if dropping onto itself
    if (draggedItem.value === targetId) return

    // Get the target file if dropping onto a specific item
    const targetFile = targetId ? fileStore.files.find(f => f.id === targetId) : null
    
    // Only allow dropping into folders or root
    if (targetId && (!targetFile || targetFile.type !== 'folder')) {
    //   toast.error('Files can only be moved to folders')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_files_can_only_be_moved_to_folders'))
      return
    }

    // Check if the dragged item exists
    const draggedFile = fileStore.files.find(f => f.id === draggedItem.value)
    if (!draggedFile) {
    //   toast.error('Source file not found')
      toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_source_file_not_found'))
      return
    }

    // If moving a folder, check for circular references
    if (draggedFile.type === 'folder' && targetId) {
      let current = targetFile
      let hasCircular = false
      const visited = new Set<string>()

      while (current && !hasCircular) {
        if (visited.has(current.id)) {
          hasCircular = true
          break
        }
        visited.add(current.id)
        
        if (current.id === draggedFile.id) {
          hasCircular = true
          break
        }
        
        if (!current.parentId) break
        current = fileStore.files.find(f => f.id === current?.parentId)
      }

      if (hasCircular) {
        // toast.error('Cannot move a folder into its own subfolder')
        toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_cannot_move_folder_into_its_own_subfolder'))
        return
      }
    }

    // If dropping in the file list area (not on a folder), use currentFolder as target
    const finalTargetId = targetId || fileStore.currentFolder

    await fileStore.moveFile(draggedItem.value, finalTargetId)
    fileStore.fetchFiles();
  } catch (error) {
    console.error('Error moving file:', error)
    // toast.error('Failed to move item')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_move_item'))
  } finally {
    draggedItem.value = null
    dragOverItem.value = null
  }
}

async function transferFile(fileId, targetId) {
  await fileStore.moveFile(fileId, targetId)
  fileStore.fetchFiles();
}

// Compute total upload progress
const totalProgress = computed(() => {
  const progressValues = Object.values(uploadProgress.value)
  if (progressValues.length === 0) return 0
  return Math.round(
    progressValues.reduce((sum, value) => sum + value, 0) / progressValues.length
  )
})


function showTagManager(id: string) {
  tagFileId.value = id
  showTagDialog.value = true
}

function canPreview(file: { type: string; mimeType?: string }) {
  if (file?.type !== 'file' || !file?.mimeType) return false

  const supportedTypes = [
    'image/',
    'application/pdf',
    'text/',
    'application/vnd.openxmlformats-officedocument.',
    'application/msword',
    'application/vnd.ms-',
    'audio/',
    'video/'
  ]

  return supportedTypes.some(type => file.mimeType?.startsWith(type))
}

const breadcrumb = computed(() => {
  const path: { id: string | null; name: string }[] = [{ id: null, name: 'Root' }]
  let current = fileStore.files.find(f => f.id === fileStore.currentFolder)
  
  while (current) {
    path.unshift({ id: current.id, name: current.name })
    current = fileStore.files.find(f => f.id === current?.parentId)
  }
  
  return path
})

async function previewItem(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (!file || !canPreview(file)) return

  try {
    const response = await axios.get(`/api/files/${id}/download`, {
      responseType: 'arraybuffer'
    })

    previewFile.value = {
      name: file.name,
      content: response.data,
      mimeType: file.mimeType
    }
    showPreviewDialog.value = true
  } catch (error) {
    // toast.error('Failed to load file preview')
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_failed_to_load_preview'))
    console.error('Error loading preview:', error)
  }
}

function handleItemClick(file: any, event: MouseEvent) {
  if (file.type === 'folder') {
    fileStore.navigateToFolder(file.id)
    fileStore.selectedItems.clear()
  } else {
    handleSelection(file.id, event)
  }
}

function handleSelection(id: string, event: MouseEvent) {
  if (event.ctrlKey || event.metaKey) {
    if (fileStore.selectedItems.has(id)) {
      fileStore.selectedItems.delete(id)
    } else {
      fileStore.selectedItems.add(id)
    }
    lastSelectedId.value = id
  } else if (event.shiftKey && lastSelectedId.value) {
    const files = filteredFiles.value
    const lastIndex = files.findIndex(f => f.id === lastSelectedId.value)
    const currentIndex = files.findIndex(f => f.id === id)
    const [start, end] = [Math.min(lastIndex, currentIndex), Math.max(lastIndex, currentIndex)]
    
    fileStore.selectedItems.clear()
    for (let i = start; i <= end; i++) {
      fileStore.selectedItems.add(files[i].id)
    }
  } else {
    fileStore.selectedItems.clear()
    fileStore.selectedItems.add(id)
    lastSelectedId.value = id
  }
}

function navigateToFolder(id: string | null) {
  fileStore.navigateToFolder(id)
  fileStore.selectedItems.clear()
}

async function handleFileUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    await fileStore.uploadFiles(input.files)
    input.value = '' // Reset input
    fileStore.fetchFiles()
  }
}

async function handleFolderUpload(event: Event) {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;

  const files = Array.from(input.files);
  if (files.length === 0) return;

  try {
    const rootFolderName = files[0].webkitRelativePath.split('/')[0];
    let rootFolderId: string;

    try {
      const formData = new FormData();
      formData.append('name', rootFolderName);
      formData.append('parent_id', fileStore.currentFolder || '');

      const { data: rootFolder } = await axios.post('/api/files/upload-folder', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      console.log("root folder data: ", rootFolder?.data?.id);
      rootFolderId = rootFolder?.data?.id;
    } catch (error: any) {
      if (error.response?.status === 409) {
        rootFolderId = error.response.data.existing_folder?.id;
      } else {
        throw error;
      }
    }

    const folderIds = new Map<string, string>();
    folderIds.set(rootFolderName, rootFolderId);

    const filesByDir = new Map<string, File[]>();
    files.forEach((file) => {
      const pathParts = file.webkitRelativePath.split('/');
      const fileName = pathParts.pop();
      const dirPath = pathParts.join('/');

      if (!filesByDir.has(dirPath)) {
        filesByDir.set(dirPath, []);
      }
      filesByDir.get(dirPath)!.push(file);
    });

    for (const dirPath of filesByDir.keys()) {
      const pathParts = dirPath.split('/');
      if (pathParts.length === 1) continue;

      for (let i = 1; i < pathParts.length; i++) {
        const currentPath = pathParts.slice(0, i + 1).join('/');
        const parentPath = pathParts.slice(0, i).join('/');
        const folderName = pathParts[i];

        if (!folderIds.has(currentPath)) {
          const formData = new FormData();
          formData.append('name', folderName);
          formData.append('parent_id', folderIds.get(parentPath) || '');

          try {
            const { data: newFolder } = await axios.post('/api/files/upload-folder', formData, {
              headers: { 'Content-Type': 'multipart/form-data' },
            });
            folderIds.set(currentPath, newFolder?.data?.id);
          } catch (error: any) {
            if (error.response?.status === 409) {
              folderIds.set(currentPath, error.response.data.existing_folder?.id);
            } else {
              throw error;
            }
          }
        }
      }
    }

    // Correct file upload section.
    for (const [dirPath, dirFiles] of filesByDir) {
      const folderId = folderIds.get(dirPath);
      if (!folderId) {
        console.warn(`Folder ID not found for path: ${dirPath}`); // Add this line
        continue;
        }

        if(dirFiles.length === 0){
            console.warn(`dirFiles array is empty for path: ${dirPath}`);
            continue;
        }

      for (const file of dirFiles) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('parent_id', folderId);

        try {
          const { data: uploadedFile } = await axios.post('/api/files/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });
          console.log(`File ${file.name} uploaded successfully:`, uploadedFile);
        } catch (error: any) {
          console.error(`Failed to upload file ${file.name}:`, error);
        //   toast.error(`Failed to upload ${file.name}`);
          toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_uploading_file') + ' - ' + file.name);
        }
      }
    }

    // toast.success('Folder uploaded successfully');
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.folder_uploaded'));

    fileStore.fetchFiles();
  } catch (error) {
    console.error('Error uploading folder:', error);
    toast.error('Failed to upload folder');
  }

  input.value = '';
}

function triggerFileUpload() {
  fileInput.value?.click()
}

function triggerFolderUpload() {
  folderInput.value?.click()
}

async function processEntries(entries: any[]) {
  for (const entry of entries) {
    if (entry.isFile) {
      entry.file((file: File) => {
        fileStore.uploadFiles([file])
      })
    } else if (entry.isDirectory) {
      fileStore.uploadFolder(entry.name)
      entry.createReader().readEntries((subEntries: any[]) => {
        processEntries(subEntries)
      })
    }
  }
}

function startRename(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (file) {
    renamingItemId.value = id
    newItemName.value = file.name
    showRenameDialog.value = true
  }
}

function confirmRename() {
  if (renamingItemId.value && newItemName.value.trim()) {
    fileStore.renameItem(renamingItemId.value, newItemName.value.trim())
    showRenameDialog.value = false
    renamingItemId.value = ''
    newItemName.value = ''
  }
}

function startShare(id: string) {
  sharingItemId.value = id
  shareRecipient.value = null
  shareAccess.value = 'read'
  showShareDialog.value = true
}

function startMove(id: string) {
  movingItemId.value = id
  movingToFolderId.value = null
  showMoveDialog.value = true
}

function confirmMove() {
  const destinationFolderId = extractOptionValue(movingToFolderId.value)

  if (movingItemId.value && destinationFolderId) {
    fileStore.moveItem(movingItemId.value, destinationFolderId)
    showMoveDialog.value = false
    movingItemId.value = ''
    movingToFolderId.value = null
  }
}

function confirmShare() {
  if (sharingItemId.value && selectedShareRecipientId.value) {
    fileStore.shareItem(sharingItemId.value, selectedShareRecipientId.value, shareAccess.value)
    showShareDialog.value = false
    sharingItemId.value = ''
    shareRecipient.value = null
  }
}

function showVersionHistory(id: string) {
  versionHistoryFileId.value = id
  showVersionHistoryDialog.value = true
}

function startDelete(id: string) {
  const file = fileStore.files.find(f => f.id === id)
  if (file) {
    itemToDelete.value = {
      id: file.id,
      name: file.name,
      type: file.type
    }
    showDeleteDialog.value = true
  }
}

function confirmDelete() {
  if (itemToDelete.value) {
    fileStore.permanentlyDeleteItem(itemToDelete.value.id)
    showDeleteDialog.value = false
    itemToDelete.value = null
  }
}

function formatDate(date: Date | undefined | null) {
  if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
    return ''
  }
  
  try {
    return new Intl.DateTimeFormat('en-US', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date)
  } catch (error) {
    console.error('Error formatting date:', error)
    return ''
  }
}

function formatSize(size: number | undefined) {
  if (!size) return ''
  const units = ['B', 'KB', 'MB', 'GB']
  let value = size
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024
    unit++
  }
  return `${value.toFixed(1)} ${units[unit]}`
}

function startCreateFolder() {
  newFolderName.value = ''
  showCreateFolderDialog.value = true
}

async function confirmCreateFolder() {
  if (newFolderName.value.trim()) {
    await fileStore.createFolder(newFolderName.value.trim())
    showCreateFolderDialog.value = false
    newFolderName.value = ''
    fileStore.fetchFiles()
  }
}

function loadFolders(query, setOptions) {
    fetch('/api/files/folders/getFolder?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    });
}

function loadUsers(query, setOptions) {
  fetch('/users/getUser?q=' + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(result => ({
          value: result.id,
          label: `${result.name} (${result.email})`,
        })),
      );
    });
}

</script>

<style scoped>
.cursor-move {
  cursor: move;
}

/* Smooth transition for opacity changes */
.opacity-0 {
  opacity: 0;
}

.group:hover .opacity-0 {
  opacity: 1;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
