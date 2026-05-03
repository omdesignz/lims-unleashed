<template>
    <div class="flex items-start space-x-4">
      <div class="shrink-0">
        <img class="inline-block size-10 rounded-full" :src="photo" alt="" loading="lazy" v-if="photo" />
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-orange sm:h-8 sm:w-8 ring-4 ring-white dark:bg-gray-700" v-else>
            <span class="text-xl font-medium leading-none text-white dark:text-gray-400">{{ user?.charAt(0) }}</span>
        </span>
      </div>
      <div class="min-w-0 flex-1">
        <form class="relative">
          <div class="rounded-2xl bg-white outline outline-1 -outline-offset-1 outline-gray-300 dark:bg-slate-900 dark:outline-slate-700">
            <label for="comment" class="sr-only">Adicionar comentário</label>
            <textarea :value="props.modelValue" @input="updateValue" rows="3" name="comment" id="comment" class="block w-full resize-none border-none bg-transparent px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 outline-none focus:border-none focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500 sm:text-sm/6" placeholder="Adicione o seu comentário..." />
  
            <!-- Spacer element to match the height of the toolbar -->
            <div class="py-2" aria-hidden="true">
              <!-- Matches height of button in toolbar (1px border + 36px content height) -->
              <!-- <div class="py-px">
                <div class="h-9" />
              </div> -->

              <!-- Upload Container -->
              <div class="mt-0 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 m-6 pb-6">
          
                <!-- Upload Form -->
              <div class="col-span-full">
                <!-- <label for="cover-photo" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.files') }}</label> -->
                <div
                    @drop.prevent="onDroppedFiles"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    :class="[dragging ? 'border-indigo-500':'border-gray-400', 'mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10']"
                >
                  <div class="text-center">
                    <DocumentPlusIcon class="mx-auto h-8 w-8 text-gray-400 dark:text-slate-500" aria-hidden="true" />
                    <div class="mt-4 flex text-xs leading-6 text-gray-600 dark:text-slate-400">
                      <label for="files" class="relative cursor-pointer rounded-full bg-orange font-semibold text-black focus-within:outline-none focus-within:ring-2 focus-within:ring-orange focus-within:ring-offset-2 hover:text-white hover:bg-orange-600 px-2">
                        <span @click="files.click()">{{ $t('gestlab.general.labels.files.upload_file') }}</span>
                        <input ref="files" @input="onSelectedFiles" type="file" name="files" multiple class="sr-only" />
                      </label>
                      <p class="pl-1">{{ $t('gestlab.general.labels.files.or') }} {{ $t('gestlab.general.labels.files.drag_file') }}</p>
                    </div>
                    <p class="text-xs leading-5 text-gray-600 dark:text-slate-400">PNG, JPG, GIF e documentos {{ $t('gestlab.general.labels.files.up_to') }} 10MB</p>
                  </div>
                </div>
              </div>
              </div>

              <!-- End Upload Form -->

              <!-- List Files -->

              <div v-if="attachments.length" class="sm:col-span-full m-6">
                  <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="(file, index) in attachments" :key="file.name" class="flex items-center justify-between gap-x-6 py-5">
                      <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                        <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-slate-100">{{ file.name.split('.').shift() }}</p>
                          <p class="mt-0.5 whitespace-nowrap rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-white bg-orange ring-orange/20">{{ file.name.split('.').pop() }}</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-slate-400">
                          <p class="truncate">{{ readableFileSize(file.size) }}</p>
                        </div>
                      </div>
                      <div class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer">
                        <TrashIcon class="h-5 w-5 text-slate-400 transition-colors hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400" aria-hidden="true" @click="attachments.splice(index, 1)" />
                      </div>
                    </li>
                  </ul>
              </div>

              <!-- End List Files -->

                  </div>
                </div>

          
  
          <div class="absolute inset-x-0 bottom-0 flex justify-between py-2 pl-3 pr-2">
            <div class="flex items-center space-x-5">
              <div class="flex items-center">
                <!-- <button type="button" class="inline-flex items-center gap-x-1.5 rounded-full bg-green-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <PaperClipIcon class="-ml-0.5 size-5" aria-hidden="true" />
                    <span class="sr-only">Attach a file</span>

                    Adicionar Ficheiro(s)
                </button> -->
              </div>
            </div>
            <div class="shrink-0">
              <button type="button" class="inline-flex items-center gap-x-1.5 rounded-full bg-orange px-2.5 py-1.5 text-sm font-semibold text-black shadow-sm hover:bg-orange-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange" @click="emit('submit-form')">
                <ChatBubbleBottomCenterTextIcon class="-ml-0.5 size-5" aria-hidden="true" />
                Enviar
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import {
    ChatBubbleBottomCenterTextIcon,
    DocumentPlusIcon,
    TrashIcon
  } from '@heroicons/vue/24/outline'

  const props = defineProps({
    modelValue: String,
    user: String,
    photo: String,
  });

  const dragging = ref(false);
  const files = ref(null);
  const attachments = ref([]);

  const emit = defineEmits([
    'attach-files',
    'submit-form',
    'update:modelValue'
  ]);

  function updateValue(event) {
      emit('update:modelValue', event.target.value);
  };

  const onDroppedFiles = ($event) => {
      dragging.value = false;
 
      let droppedFiles = [...$event.dataTransfer.items]
          .filter(item => item.kind === 'file')
          .map(item => item.getAsFile());
 
}

const readableFileSize = (size) => {
    const units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    let i = 0;
    while (size >= 1024 && i < units.length - 1) {
      size /= 1024;
      i++;
    }
    return `${size.toFixed(2)} ${units[i]}`;
  };


  const onSelectedFiles = ($event) => {
      attachments.value = [...$event.target.files];

      emit('attach-files', attachments.value);
}
  
  </script>
