<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import RecordsTable from '@/Components/records-table.vue';
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from '@headlessui/vue'
import combobox from '@/Components/combobox.vue';
import slideOver from '@/Components/slide-over.vue';
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { PhotoIcon } from "@heroicons/vue/24/outline";


const props = defineProps({
    record: Object,
    fields: Array,
    model: String,
    abilities: Array,
    query: Object,
    slideOverEdit: {
      type: Boolean,
      default: false
    }
});

defineOptions({
  layout: Layout
});

const dragging = ref(false);
const media = ref([]);
const files = ref('files');

let form = useForm({ 
    
});

let submit = () => {
    
}

function onDroppedFiles($event) {
      dragging.value = false;
 
      let droppedFiles = [...$event.dataTransfer.items]
          .filter(item => item.kind === 'file')
          .map(item => item.getAsFile());
 
      uploadFiles(droppedFiles);
}

function onSelectedFiles($event) {
      let selectedFiles = [...$event.target.files];
      uploadFiles(selectedFiles);
      files.value = null;
}

function uploadFiles(files) {
      files.forEach(file => {
        media.value.unshift({
          file: file,
          progress: 0,
          error: null,
          uploaded: false,
          preview_url: null,
          id: null
        });
      });
 
      media.value.filter(media => !media.uploaded)
          .forEach(media => {
            let form = new FormData;
            form.append('file', media.file);
 
            axios.post(route('media.store'), form, {
              headers: {
                Accept: 'application/json',
              },
              onUploadProgress: (event) => {
                media.progress = Math.round(event.loaded * 100 / event.total);
              },
            })
                .then(({data}) => {
                  media.uploaded = true
                  media.id = data.id;
                  media.preview_url = data.preview_url;
                })
                .catch(error => {
                  media.error = `Não foi possível carregar o ficheiro. Tente novamente.`;
 
                  if (error?.response.status === 422) {
                    media.error = error.response.data.errors.file[0];
                  }
                });
          });
    }

</script>
<template>
<div class="border-b border-gray-200 pb-5" :class="commercialDocumentThemeClasses">
    <h3 class="text-base font-semibold leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.page_title') }}</h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
</div>

<div class="space-y-12" :class="commercialDocumentThemeClasses">
      <div class="border-b border-gray-900/10 pb-12">
        <h2 class="text-base font-semibold leading-7 text-gray-900"></h2>
        <p class="mt-1 text-sm leading-6 text-gray-600"></p>

        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

          <div class="col-span-full">
            <label for="cover-photo" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('gestlab.general.labels.files.files') }}</label>
            <div
                @drop.prevent="onDroppedFiles"
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                :class="[dragging ? 'border-indigo-500' :'border-gray-400', 'mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10']"
             >
              <div class="text-center">
                <PhotoIcon class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                <div class="mt-4 flex text-sm leading-6 text-gray-600">
                  <label for="files" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                    <span @click="files.click()">{{ $t('gestlab.general.labels.files.upload_file') }}</span>
                    <input ref="files" @input="onSelectedFiles" type="file" name="files" multiple class="sr-only" />
                  </label>
                  <p class="pl-1">{{ $t('gestlab.general.labels.files.or') }} {{ $t('gestlab.general.labels.files.drag_file') }}</p>
                </div>
                <p class="text-xs leading-5 text-gray-600">PNG, JPG, GIF, DOCUMENTOS {{ $t('gestlab.general.labels.files.up_to') }} 10MB</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <ul class="my-6 bg-white rounded divide-y divide-gray-200 shadow">
        <li v-for="(item, index) in media" :key="index"
            class="p-3 flex items-center space-x-2">
          <div class="w-9 h-9 bg-gray-300 flex-shrink-0">
            <img :src="item.preview_url" class="h-full w-full rounded" :alt="item.file.name">
          </div>
 
          <div class="text-sm text-gray-700 flex-1 truncate">{{ item.file.name }}</div>
 
          <div v-if="!item.uploaded && !item.error" class="w-40 bg-gray-200 rounded-full h-5 shadow-inner overflow-hidden relative flex items-center justify-center">
            <div class="inline-block h-full bg-indigo-600 absolute top-0 left-0" :style="`width: ${item.progress}%`"></div>
            <div class="relative z-10 text-xs font-semibold text-center text-white drop-shadow text-shadow">{{ item.progress }}%</div>
          </div>
 
          <div v-if="item.error" class="text-sm text-red-600">{{ item.error }}</div>
          <Link href="#" v-if="item.uploaded" class="text-sm text-indigo-600 underline">--</Link>
        </li>
      </ul>

</div>
</template>
