<template>
      <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-gray-50">
      <body class="h-full overflow-hidden">
      ```
    -->
    <div class="flex h-full">
  
  <!-- Content area -->
  <div class="flex flex-1 flex-col">
    <header class="w-full">

      <!-- <nav class="flex border-b border-gray-200 bg-white pb-2" aria-label="Breadcrumb">
          <ol role="list" class="mx-auto flex w-full max-w-screen-xl space-x-4 px-4 sm:px-6 lg:px-8">
            <li class="flex">
              <div class="flex items-center">
                <a href="#" class="text-gray-400 hover:text-gray-500">
                  <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
                  <span class="sr-only">Home</span>
                </a>
              </div>
            </li>
            <li v-for="page in pages" :key="page.name" class="flex">
              <div class="flex items-center">
                <svg class="h-full w-6 flex-shrink-0 text-gray-200" viewBox="0 0 24 44" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                  <path d="M.293 0l22 22-22 22h1.414l22-22-22-22H.293z" />
                </svg>
                <a :href="page.href" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700" :aria-current="page.current ? 'page' : undefined">{{ page.name }}</a>
              </div>
            </li>
          </ol>
        </nav> -->

      <!-- <breadcrumbs :pages="props.breadcrumbs" /> -->

        
      <div class="relative z-10 flex h-16 flex-shrink-0 border-b border-gray-200 bg-white shadow-sm">
          <!-- {{ selectedFiles }} -->
        <div class="flex flex-1 justify-between px-4 sm:px-6">
          <div class="flex flex-1">
            <form class="flex w-full md:ml-0" action="#" method="GET">
              <label for="desktop-search-field" class="sr-only">Search all files</label>
              <label for="mobile-search-field" class="sr-only">Search all files</label>
              <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center">
                  <MagnifyingGlassIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
                </div>
                <input name="mobile-search-field" id="mobile-search-field" class="h-full w-full border-0 py-2 pl-8 pr-3 text-base text-gray-900 focus:outline-none focus:ring-0 focus:placeholder:text-gray-400 sm:hidden" placeholder="Search" type="search" />
                <input name="desktop-search-field" id="desktop-search-field" class="hidden h-full w-full border-0 py-2 pl-8 pr-3 text-sm text-gray-900 focus:outline-none focus:ring-0 focus:placeholder:text-gray-400 sm:block" placeholder="Search all files" type="search" />
              </div>
            </form>
          </div>
          <div class="ml-2 flex items-center space-x-4 sm:ml-6 sm:space-x-6">
            <Menu as="div" class="relative flex-shrink-0">
              <div>
                <MenuButton class="relative flex rounded-full bg-blue-900 p-1.5 text-white text-sm ffocus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-900">
                  <span class="absolute -inset-1.5" />
                  <span class="sr-only">Open file menu</span>
                  <PlusIcon class="h-5 w-5" aria-hidden="true" />
                </MenuButton>
              </div>
              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <MenuItem v-slot="{ active }">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-900 hover:text-white" @click="showCreateFolderForm = true">{{ $t('gestlab.general.labels.files.create_folder') }}</a>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-900 hover:text-white" @click="uploadType = 'file', showUploadForm = true">{{ $t('gestlab.general.labels.files.upload_file') }}</a>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-900 hover:text-white" @click="uploadType = 'folder', showUploadForm = true">{{ $t('gestlab.general.labels.files.upload_folder') }}</a>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <a target="_blank" :href="route('files.downloadZip', {file_ids: [2], folder_ids: [1]})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-900 hover:text-white" @click="uploadType = 'folder', showUploadForm = true">{{ $t('gestlab.general.labels.files.upload_folder') }}</a>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <div class="flex flex-1 items-stretch">
      <main class="flex-1">
        <div class="mx-auto max-w-7xl pt-8">
          <div class="flex">
            <h1 class="flex-1 text-2xl font-bold text-gray-900">{{ props.folder?.name }}</h1>
            <div class="ml-6 flex items-center rounded-lg bg-gray-100 p-0.5 sm:hidden">
              <button type="button" class="rounded-md p-1.5 text-gray-400 hover:bg-white hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-900">
                <Bars4Icon class="h-5 w-5" aria-hidden="true" />
                <span class="sr-only">Use list view</span>
              </button>
              <button type="button" class="ml-0.5 rounded-md bg-white p-1.5 text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-900">
                <Squares2X2IconMini class="h-5 w-5" aria-hidden="true" />
                <span class="sr-only">Use grid view</span>
              </button>
            </div>
          </div>

          <!-- Tabs -->
          <div class="mt-3 sm:mt-2">
            <div class="sm:hidden">
              <label for="tabs" class="sr-only">Select a tab</label>
              <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
              <select id="tabs" name="tabs" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option selected="">Recently Viewed</option>
                <option>Recently Added</option>
                <option>Favorited</option>
              </select>
            </div>
            <div class="hidden sm:block">
              <div class="flex items-center border-b border-gray-200">
                <!-- <nav class="-mb-px flex flex-1 space-x-6 xl:space-x-8" aria-label="Tabs">
                  <a v-for="tab in tabs" :key="tab.name" :href="tab.href" :aria-current="tab.current ? 'page' : undefined" :class="[tab.current ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium']">{{ tab.name }}</a>
                </nav> -->
                <breadcrumbs :pages="props.breadcrumbs" :folder="props.folder" />

                <div class="ml-6 hidden items-center rounded-lg bg-gray-100 p-0.5 sm:flex">
                  <button type="button" class="rounded-md p-1.5 text-white hover:bg-blue-900 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <Bars4Icon class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Use list view</span>
                  </button>
                  <button type="button" class="ml-0.5 rounded-md bg-blue-900 p-1.5 text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <Squares2X2IconMini class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Use grid view</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Gallery -->
          <section class="mt-8 pb-16" aria-labelledby="gallery-heading">
            <h2 id="gallery-heading" class="sr-only">Recently viewed</h2>

              <!-- Divider: With Label Left -->
            <div class="my-6 flex items-center">
              <span
                class="rounded-full bg-blue-900 px-3 py-1 text-xs font-medium text-white dark:bg-gray-700 dark:text-gray-200"
                >{{ $t('gestlab.general.labels.files.folders') }}</span
              >
              <span
                aria-hidden="true"
                class="h-0.5 grow rounded bg-gray-200 dark:bg-gray-700/75"
              ></span>
            </div>
            <!-- END Divider: With Label Left -->
            <ul role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
              <folderItem v-for="(folder, index) in props.folders"
                  :key="index" 
                  @move="moveFolder" 
                  @download="downloadFolder" 
                  @rename="renameFolder" 
                  @delete="destroyFolder"
                  @share="shareFolder" 
                  :folder="folder" 
                  @navigate="navigateToFolder" 
                  v-if="!props.folder" 
              />

              <folderItem v-for="(child, index) in props.folder.children" 
                  :key="index" 
                  @move="moveFolder" 
                  @download="downloadFolder" 
                  @rename="renameFolder" 
                  @delete="destroyFolder"
                  @share="shareFolder" 
                  :folder="child" 
                  @navigate="navigateToFolder" 
                  v-if="props.folder" 
              />
              <!-- <slot /> -->
            </ul>

              <!-- Divider: With Label Left --> 
              <div class="my-6 flex items-center" v-if="props.folder && props.folder.files.length > 0">
              <span
                class="rounded-full bg-blue-900 px-3 py-1 text-xs font-medium text-white dark:bg-gray-700 dark:text-gray-200"
                >{{ $t('gestlab.general.labels.files.files') }}</span
              >
              <span
                aria-hidden="true"
                class="h-0.5 grow rounded bg-gray-200 dark:bg-gray-700/75"
              ></span>
            </div>
            <!-- END Divider: With Label Left -->

              <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8 mt-3" v-if="props.folder && props.folder.files.length > 0">
                <li v-for="(file, index) in props.folder.files" :key="index" class="overflow-hidden rounded-xl border" :class="[selectedFiles.includes(file.id) ? 'border-2 border-blue-900' : 'border-gray-200']">
                  <div class="flex items-center gap-x-4 border-b border-blue-900 bg-blue-900 p-4">
                    <!-- <img :src="client.imageUrl" :alt="client.name" class="h-12 w-12 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10" /> -->
                     <!-- <DocumentPlusIcon class="h-8 w-8 flex-none rounded-lg bg-white object-cover ring-1 ring-gray-900/10" /> -->
                    <div class="text-sm font-medium leading-6 text-white truncate">{{ file.name }}</div>
                    <Menu as="div" class="relative ml-auto">
                      <button class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500" v-if="selectedFiles.includes(file.id)" disabled>
                        <CheckCircleIcon :class="[selectedFiles.includes(file.id) ? 'text-white' : 'invisible', 'h-5 w-5']" aria-hidden="true" />
                      </button>
                      <MenuButton class="-m-2.5 block p-2.5 text-gray-400 hover:text-gray-500" v-else>
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                      </MenuButton>
                      <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95" v-if="!selectedFiles.includes(file.id)">
                        <MenuItems class="absolute right-0 z-10 mt-0.5 w-48 origin-top-right rounded-full bg-white py-1 shadow-lg ring-1 ring-gray-900/5 focus:outline-none flex space-x-3 justify-center default-cursor-pointer">
                          
                          <MenuItem as="button" v-slot="{ active }">
                            <button @click="name = file.name, file_id = file.id, showRenameFileForm = true" class="h-5 w-5" aria-hidden="true" preserve-scroll>
                              <PencilIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                          </MenuItem>
                          <MenuItem as="a" v-slot="{ active }">
                            <a :href="route('files.download', {file: file.path})" class="h-5 w-5" aria-hidden="true">
                              <CloudArrowDownIcon class="h-5 w-5" aria-hidden="true" />
                            </a>    
                          </MenuItem>
                          <MenuItem as="button" v-slot="{ active }">
                            <button @click="moveSelectedFilesForm.files = [file.id],showMoveFileForm = true" class="h-5 w-5" aria-hidden="true" preserve-scroll>
                              <ArrowsRightLeftIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                          </MenuItem>
                          <MenuItem as="a" v-slot="{ active }">
                            <Link :href="route('files.destroy', {file: file.id})" class="h-5 w-5" aria-hidden="true" preserve-scroll>
                              <TrashIcon class="h-5 w-5" aria-hidden="true" />
                            </Link>
                          </MenuItem>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                  <dl class="-my-3 divide-y divide-gray-100 px-6 py-4 text-sm leading-6 default-cursor-pointer" @click="toggleSelectedFile(file)">
                    <div class="flex justify-between gap-x-4 py-3">
                      <dt class="text-gray-500">{{ $t('gestlab.general.labels.files.size') }}</dt>
                      <dd class="text-gray-700">
                        {{ readableFileSize(file.size) }}
                      </dd>
                    </div>
                    <div class="flex justify-between gap-x-4 py-3">
                      <dt class="text-gray-500">{{ $t('gestlab.general.labels.files.mime_type') }}</dt>
                      <dd class="text-gray-700">
                        {{ file.extension }}
                      </dd>
                    </div>
                    <div class="flex justify-between gap-x-4 py-3">
                      <dt class="text-gray-500">{{ $t('gestlab.general.labels.files.user_id') }}</dt>
                      <dd class="text-gray-700">
                        {{ file.user.name }}
                      </dd>
                    </div>
                    <div class="flex justify-between gap-x-4 py-3">
                      <dt class="text-gray-500">{{ $t('gestlab.general.labels.files.created_at') }}</dt>
                      <dd class="text-gray-700">
                        {{ file.created_at }}
                      </dd>
                    </div>
                    
                  </dl>
                </li>
              </ul>

          </section>
        </div>
      </main>
    </div>
  </div>

<upload v-if="showUploadForm" :title="uploadFormTitle" :description="uploadFormDescription" confirm="Carregar" size="sm:max-w-2xl" cancel="Cancelar" @canceled="showUploadForm = false" :type="uploadType" @confirmed="uploadFolder">
  <div class="space-y-12">
        <div class="border-b border-gray-900/10 pb-12">
          <h2 class="text-base font-semibold leading-7 text-gray-900"></h2>
          <p class="mt-1 text-sm leading-6 text-gray-600">{{ dragging }}</p>

          <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

            <div class="col-span-full" v-if="uploadType === 'file'">
              <label for="cover-photo" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.files') }}</label>
              <div
                  @drop.prevent="onDroppedFiles"
                  @dragover.prevent="dragging = true"
                  @dragleave.prevent="dragging = false"
                  :class="[dragging ? 'border-indigo-500':'border-gray-400', 'mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10']"
              >
                <div class="text-center">
                  <DocumentPlusIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                  <div class="mt-4 flex text-sm leading-6 text-gray-600">
                    <label for="files" class="relative cursor-pointer rounded-full bg-blue-900 font-semibold text-white focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-900 focus-within:ring-offset-2 hover:text-white hover:bg-blue-800 px-2">
                      <span @click="files.click()">{{ $t('gestlab.general.labels.files.upload_file') }}</span>
                      <input ref="files" @input="onSelectedFiles" type="file" name="files" multiple class="sr-only" />
                    </label>
                    <p class="pl-1">{{ $t('gestlab.general.labels.files.or') }} {{ $t('gestlab.general.labels.files.drag_file') }}</p>
                  </div>
                  <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF, DOCUMENTOS {{ $t('gestlab.general.labels.files.up_to') }} 10MB</p>
                </div>
              </div>
            </div>

            <div class="col-span-full" v-else>
              <label for="cover-photo" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.files') }}</label>
              <div
                  @drop.prevent="handleFiles"
                  @dragover.prevent="dragging = true"
                  @dragleave.prevent="dragging = false"
                  :class="[dragging ? 'border-indigo-500' :'border-gray-400', 'mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10']"
              >
                <div class="text-center">
                  <FolderIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                  <div class="mt-4 flex text-sm leading-6 text-gray-600">
                    <label for="folders" class="relative cursor-pointer rounded-full bg-blue-900 font-semibold text-white focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-900 focus-within:ring-offset-2 hover:text-white hover:bg-blue-800 px-2">
                      <span @click="folders.click()">{{ $t('gestlab.general.labels.files.upload_file') }}</span>
                      <input type="file" ref="folders" name="folders" webkitdirectory multiple @change="handleFiles" class="sr-only" />
                    </label>
                    <p class="pl-1">{{ $t('gestlab.general.labels.files.or') }} {{ $t('gestlab.general.labels.files.drag_file') }}</p>
                  </div>
                  <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF, DOCUMENTOS {{ $t('gestlab.general.labels.files.up_to') }} 10MB</p>
                </div>
              </div>
            </div>

            <!-- Folder Upload Progress -->
            <div v-if="progress > 0">
              <progress :value="progress" max="100">{{ progress }}%</progress>
            </div>

            <div v-if="folderStructure.length" class="sm:col-span-full">
              <ul role="list" class="divide-y divide-gray-100">
                <li v-for="(file, index) in folderStructure" :key="file.relativePath" class="flex items-center justify-between gap-x-6 py-5">
                  <div class="min-w-0">
                    <div class="flex items-start gap-x-3">
                      <p class="text-sm font-semibold leading-6 text-gray-900">{{ file.file.name.split('.').shift() }}</p>
                      <p class="mt-0.5 whitespace-nowrap rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-white bg-blue-900 ring-blue-900/20">{{ file.file.name.split('.').pop() }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500">
                      <p class="whitespace-nowrap">
                        {{ file.relativePath }}
                      </p>
                      <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                        <circle cx="1" cy="1" r="1" />
                      </svg>
                      <p class="truncate">{{ readableFileSize(file.file.size) }}</p>
                    </div>
                  </div>
                  <div class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer">
                    <TrashIcon class="h-5 w-5" aria-hidden="true" @click="folderStructure.splice(index, 1)" />
                  </div>
                </li>
              </ul>
            </div>

          </div>
        </div>

  </div>
</upload>

<upload v-if="showCreateFolderForm" :title="trans('gestlab.general.labels.files.create_folder')" :description="trans('gestlab.general.labels.files.create_folder_description')" @canceled="showCreateFolderForm = false" @confirmed="createFolder" confirm="Create" cancel="Cancelar">
  <input type="text" v-model="name" placeholder="nome" class="block mt-2 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
</upload>

<upload v-if="showRenameFolderForm" :title="trans('gestlab.general.labels.files.update_folder')" :description="trans('gestlab.general.labels.files.update_folder_description')" @canceled="showRenameFolderForm = false, name=''" @confirmed="renameFolderForm" confirm="Rename" cancel="Cancelar">
  <input type="text" v-model="name" placeholder="nome" class="block mt-2 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
</upload>

<upload v-if="showRenameFileForm" :title="trans('gestlab.general.labels.files.update_file')" :description="trans('gestlab.general.labels.files.update_file_description')" @canceled="showRenameFileForm = false, name='', file_id=null" @confirmed="renameFileForm" confirm="Rename" cancel="Cancelar">
  <input type="text" v-model="name" placeholder="nome" class="block mt-2 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
</upload>

<upload v-if="showMoveFileForm" :title="trans('gestlab.general.labels.files.move_file')" :description="trans('gestlab.general.labels.files.move_file_description')" @canceled="showMoveFileForm = false" @confirmed="moveSelectedFiles" confirm="Mover" cancel="Cancelar">
<div class="mt-2">
  <combobox :hasError="moveSelectedFilesForm.errors.folder_id" v-model="moveSelectedFilesForm.folder_id" :load-options="loadFolders"/>
</div>
</upload>

<upload v-if="showMoveFolderForm" :title="trans('gestlab.general.labels.files.move_folder')" :description="trans('gestlab.general.labels.files.move_folder_description')" @canceled="showMoveFolderForm = false" @confirmed="moveSelectedFolders" confirm="Mover" cancel="Cancelar">
<div class="mt-2">
  <combobox :hasError="moveSelectedFoldersForm.errors.destination_folder_id" v-model="moveSelectedFoldersForm.destination_folder_id" :load-options="loadFolders"/>
</div>
</upload>

<upload v-if="showShareFolderForm" :title="trans('gestlab.general.labels.files.share_folder')" :description="trans('gestlab.general.labels.files.share_folder_description')" @canceled="showShareFolderForm = false" @confirmed="shareFolder" confirm="Share" cancel="Cancelar" size="sm:max-w-xl">
  
  <!-- Share with Users -->
  <div class="mt-4">
      <label for="email" class="block text-gray-700 font-medium mb-2">
        Add people and groups
      </label>
      <div class="flex items-center gap-2">
        <!-- <input
          type="email"
          id="email"
          v-model="email"
          placeholder="Enter email"
          class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
        /> -->
        <combobox v-model="user" :load-options="loadUsers"/>
        <Listbox as="div" v-model="selectedPermission">
          <!-- <ListboxLabel class="block text-sm/6 font-medium text-gray-900">Assigned to</ListboxLabel> -->
          <div class="relative w-64">
            <ListboxButton class="grid w-full cursor-default grid-cols-1 rounded-md bg-white py-1.5 pl-3 pr-2 text-left text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-blue-900 sm:text-sm/6">
              <span class="col-start-1 row-start-1 truncate pr-6">{{ selectedPermission.name }}</span>
              <ChevronUpDownIcon class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
            </ListboxButton>

            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
              <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-2 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                <ListboxOption as="template" v-for="permission in permissions" :key="permission.id" :value="permission" v-slot="{ active, selectedPermission }">
                  <li :class="[active ? 'bg-blue-900 text-white outline-none' : 'text-gray-900', 'relative cursor-default select-none py-1 pl-3 pr-9']">
                    <span :class="[selectedPermission ? 'font-semibold' : 'font-normal', 'block truncate']">{{ permission.name }}</span>

                    <span v-if="selectedPermission" :class="[active ? 'text-white' : 'text-blue-900', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                      <CheckIcon class="size-5" aria-hidden="true" />
                    </span>
                  </li>
                </ListboxOption>
              </ListboxOptions>
            </transition>
          </div>
        </Listbox>

        <!-- <select
          v-model="selectedPermission"
          class="border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="viewer">Viewer</option>
          <option value="commenter">Commenter</option>
          <option value="editor">Editor</option>
        </select> -->
        
        <button
          @click="addUser"
          class="bg-transparent text-blue-900 px-3 py-2"
        >
        <PlusIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
  </div>

  <!-- List of Shared Users -->
  <div v-if="sharedUsers.length" class="mt-6">
      <h4 class="text-gray-700 font-medium">Shared with:</h4>
      <ul class="mt-2 space-y-2">
        <li
          v-for="(user, index) in sharedUsers"
          :key="index"
          class="flex items-center justify-between border border-gray-200 rounded px-3 py-2"
        >
          <span>{{ user.email }}</span>
          <div class="flex items-center gap-2">
            <!-- <select
              v-model="user.permission"
              class="border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="viewer">Viewer</option>
              <option value="commenter">Commenter</option>
              <option value="editor">Editor</option>
            </select> -->

            <Listbox as="div" v-model="user.permission">
              <!-- <ListboxLabel class="block text-sm/6 font-medium text-gray-900">Assigned to</ListboxLabel> -->
              <div class="relative w-32">
                <ListboxButton class="grid w-full cursor-default grid-cols-1 rounded-md bg-white py-1.5 pl-3 pr-2 text-left text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-blue-900 sm:text-sm/6">
                  <span class="col-start-1 row-start-1 truncate pr-6">{{ user.permission.name }}</span>
                  <ChevronUpDownIcon class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                </ListboxButton>

                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                  <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-2 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
                    <ListboxOption as="template" v-for="permission in permissions" :key="permission.id" :value="permission" v-slot="{ active, user }">
                      <li :class="[active ? 'bg-blue-900 text-white outline-none' : 'text-gray-900', 'relative cursor-default select-none py-1 pl-3 pr-9']">
                        <span :class="[user ? 'font-semibold' : 'font-normal', 'block truncate']">{{ permission.name }}</span>

                        <span v-if="user" :class="[active ? 'text-white' : 'text-blue-900', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                          <CheckIcon class="size-5" aria-hidden="true" />
                        </span>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </div>
            </Listbox>

            <button
              @click="removeUser(index)"
              class="text-red-500 hover:text-red-700 focus:outline-none"
            >
            <TrashIcon class="h-5 w-5" aria-hidden="true" />
            </button>
          </div>
        </li>
      </ul>
    </div>
</upload>

</div>
</template>
<script setup>
  import { ref, computed, onMounted } from 'vue'
  import { router, useForm } from '@inertiajs/vue3'
  import combobox from '@/Components/combobox.vue';
  import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
  } from '@headlessui/vue'

  import {
    Bars3BottomLeftIcon,
    CogIcon,
    HeartIcon,
    HomeIcon,
    PhotoIcon,
    FolderPlusIcon,
    DocumentPlusIcon,
    RectangleStackIcon,
    Squares2X2Icon as Squares2X2IconOutline,
    UserGroupIcon,
    CloudArrowDownIcon,
    FolderIcon,
    PencilIcon,
    EyeIcon,
    ArrowsRightLeftIcon,
    CheckCircleIcon,
    XMarkIcon,
    EllipsisVerticalIcon,
    TrashIcon
  } from '@heroicons/vue/24/outline'

  import {
    Bars4Icon,
    MagnifyingGlassIcon,
    PlusIcon,
    Squares2X2Icon as Squares2X2IconMini,
    CheckIcon,
    ChevronUpDownIcon
  } from '@heroicons/vue/20/solid'

  import breadcrumbs from '@/Components/breadcrumbs.vue';
  import folderItem from '@/Pages/Folders/folder-item.vue';

  import Layout from "@/Shared/Layouts/Layout.vue";
  import Upload from "@/Components/upload.vue";
  import { trans } from 'laravel-vue-i18n';


  const props = defineProps({
    breadcrumbs: Array,
    folders: Array,
    folder: Object,
  });


defineOptions({
  layout: Layout
});

const uploadType = ref('file')
const showUploadForm = ref(false)
const showCreateFolderForm = ref(false)
const showRenameFolderForm = ref(false)
const showRenameFileForm = ref(false)
const showMoveFileForm = ref(false)
const showMoveFolderForm = ref(false)
const showShareFolderForm = ref(false)
const dragging = ref(false)
const files = ref(null)
const folders = ref(null)
const name = ref('');
const folder_id = ref(null);
const file_id = ref(null);
const destinationFolderId = ref(null);
const selectedFiles = ref([]);
const selectedFolders = ref([]);
const sharedUsers = ref([]);
const user = ref(null);

const permissions = ref([
  { id: 1, name: 'Viewer' },
  { id: 2, name: 'Commenter' },
  { id: 3, name: 'Editor' },
]);

const selectedPermission = ref(permissions.value[0]);

const folderStructure = ref([]);
const progress = ref(0);
const uploading = ref(false);

const uploadFormTitle = computed(() => {
  // return 'Upload ' + uploadType.value
  return uploadType.value == 'file' ? trans('gestlab.general.labels.files.upload_file') : trans('gestlab.general.labels.files.upload_folder')
})

const uploadFormDescription = computed(() => {
  // return 'Upload ' + uploadType.value + ' to this folder'
  return uploadType.value == 'file' ? trans('gestlab.general.labels.files.upload_file_description') : trans('gestlab.general.labels.files.upload_folder_description')
})

const navigateToFolder = (folder) => {
    router.get(route('modern-folders.show', folder.slug), {}, {
      preserveState: false,
      preserveScroll: true,
    });
  }

  const onDroppedFiles = ($event) => {
      dragging.value = false;
 
      let droppedFiles = [...$event.dataTransfer.items]
          .filter(item => item.kind === 'file')
          .map(item => item.getAsFile());
 
      uploadFiles(droppedFiles);
}

const downloadFolder = (folder) => {
    window.open(route('modern-folders.download', { folder: folder.slug }), '_blank');
}

const shareFolder = (folder) => {

    showShareFolderForm.value = true;
    // console.log(folder.slug);
}

const onSelectedFiles = ($event) => {
      let selectedFiles = [...$event.target.files];
      uploadFiles(selectedFiles);
      files.value = null;
}

onMounted(() => {
  files.value = document.querySelector('input[type="file"]')
})

const toggleSelectedFolder = (folder) => {
  if (selectedFolders.value.includes(folder.id)) {
    selectedFolders.value.splice(selectedFolders.value.indexOf(folder.id), 1);
  } else {
    selectedFolders.value.push(folder.id);
  }
}

const toggleSelectedFile = (file) => {
  if (selectedFiles.value.includes(file.id)) {
    selectedFiles.value.splice(selectedFiles.value.indexOf(file.id), 1);
  } else {
    selectedFiles.value.push(file.id);
  }
}

const uploadFiles = (files) => {
    files.forEach(file => {
      let formData = new FormData();
      formData.append('file', file);

      router.post(route('files.store', { folder_id: props.folder ? props.folder.id : null }), formData, {
        onProgress: (progressEvent) => {
          progress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        },
        onSuccess: (response) => {
          showUploadForm.value = false;
        },
      });

    });
};

const uploadFolder = async () => {
    const formData = new FormData();
    
    // Append each file along with its relative path to FormData
    folderStructure.value.forEach(item => {
      formData.append('folder_id', props.folder ? props.folder.id : null);
      formData.append('files[]', item.file);
      formData.append('relative_paths[]', item.relativePath); // Send the relative path for correct folder structure
    });

    try {

        // Set the progress to 0 before uploading
        uploading.value = true;
        progress.value = 0;
      // Submit the form data to the server
      await router.post('/files/upload-folder', formData, {
        onProgress: (event) => {
          if(event.lengthComputable) {
            progress.value = Math.round((event.loaded / event.total) * 100);
          }
        },
      });

      // Reset the progress and uploading state
      uploading.value = false;
      progress.value = 0;
      
    } catch (error) {
      console.error('Error uploading folder:', error);
    }
  };

  const handleFiles = (event) => {
    const files = event.target.files;
    folderStructure.value = Array.from(files).map(file => ({
      file,
      relativePath: file.webkitRelativePath, // This provides the folder structure
    }));
  };

  const readableFileSize = (size) => {
    const units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return `${size.toFixed(2)} ${units[i]}`;
  };

  const createFolder = () => {
    router.post(route('modern-folders.store'), {
      name: name.value,
      parent_id: props.folder ? props.folder.id : null,
    },{
      preserveScroll: true,
      onSuccess: () => {
        showCreateFolderForm.value = false;
        name.value = '';
      },
  });
  };

  const renameFolder = (folder) => {
    showRenameFolderForm.value = true;
    name.value = folder.name;
    folder_id.value = folder.slug;
  };

  const renameFolderForm = () => {
    router.put(route('modern-folders.update', { folder: folder_id.value }), {
      name: name.value,
    },{
      preserveScroll: true,
      onSuccess: () => {
        showRenameFolderForm.value = false;
        name.value = '';
        folder_id.value = null;
      },
  });
  };

  const moveFolder = (folder) => {
    folder_id.value = folder.slug;
    moveSelectedFoldersForm.origin_folder_id = folder.id;
    showMoveFolderForm.value = true;
  };

  const renameFile = (file) => {

    // console.log(file);
    name.value = file.name;
    file.value = file.id;
    showRenameFileForm.value = true;

    // console.log(name.value, file.value);
  };

  const renameFileForm = () => {
    router.put(route('files.update', { file: file_id.value }), {
      name: name.value,
    }, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        showRenameFileForm.value = false;
        name.value = '';
        file_id.value = null;
      },
    });
  };

  const destroyFolder = (folder) => {
    router.delete(route('modern-folders.destroy', { folder: folder.slug }), {
      preserveScroll: true,
      onSuccess: () => {
        showRenameFolderForm.value = false;
        name.value = '';
        folder_id.value = null;
      },
  });
  };

  const moveSelectedFilesForm = useForm({
    files: selectedFiles.value,
    folder_id: null,
  });

  const moveSelectedFoldersForm = useForm({
    folders: selectedFolders.value,
    destination_folder_id: null,
    origin_folder_id: null,
  });

  const moveSelectedFiles = () => {
    moveSelectedFilesForm.get(route('files.move'), {
      preserveScroll: true,
      onSuccess: () => {
        showMoveFileForm.value = false;
        selectedFiles.value = [];
        moveSelectedFilesForm.folder_id = null;
      },
    });
  }

  const moveSelectedFolders = () => {
    moveSelectedFoldersForm.post(route('modern-folders.move', {folder: folder_id.value}), {
      preserveScroll: true,
      onSuccess: () => {
        showMoveFolderForm.value = false;
        moveSelectedFoldersForm.destination_folder_id = null;
        moveSelectedFoldersForm.origin_folder_id = null;
        selectedFiles.value = [];
        selectedFolders.value = [];
      },
    });
  }

  function loadFolders(query, setOptions) {
      fetch('/modern-folders/getFolder?q=' + query)
      .then(response => response.json())
      .then(results => {
          setOptions(
          results.map(result => {
              return {
              value: result.id,
              label: result.name,
              path: result.path,
              };
          })
          );
      });
  }

  const addUser = () => {
    // console.log('Adding user:', user.value);

    // console.log(selectedPermission.value);
    
    if (user.value) {
      sharedUsers.value.push({
        name: user.value.label,
        email: user.value.email,
        permission: selectedPermission.value,
      });

      user.value = null;
      selectedPermission.value = permissions.value[0];
    }
  }

  const removeUser = (index) => {
    sharedUsers.value.splice(index, 1);
  }

  const saveChanges = () => {
  }

  const loadUsers = (query, setOptions) => {
      fetch('/users/getUser?q=' + query)
      .then(response => response.json())
      .then(results => {
          setOptions(
          results.map(result => {
              return {
              value: result.id,
              label: result.name,
              email: result.email,
              };
          })
          );
      });
  }

</script>
