<template>
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:max-w-7xl lg:px-8" :class="commercialDocumentThemeClasses">
        <h1 class="text-3xl font-bold text-slate-950 dark:text-white">GestLab workspace</h1>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Área técnica para validação de componentes internos e fluxos de apoio.</p>
        <p>Current route: {{ route().current() }}</p>
        <p>Time: {{ formatted }}</p>
        <br>
        <br>
        <br>

        <div class="mx-auto w-64">
        <Combobox
            :options="options"
            v-model="user"
            titleLabel="Assign User Single Static"
        />

        <Combobox
            :load-options="loadUsers"
            :create-option="createUser"
            v-model="loadUser"
            titleLabel="Assign User Single Dynamic"
        />
        </div>
        <hr>
        <br>
        <div class="mx-auto w-64">
        <ComboboxMultiple
            :options="options"
            v-model="users"
            titleLabel="Assign Users Multiple Static"
            multiple
        />

        <br>

        <ComboboxMultiple
            :load-options="loadUsers"
            :create-option="createUser"
            v-model="loadUser2"
            title-label="Assign Users Multiple Dynamic"
            noResultsLabel="Ooops!"
            multiple
        />
        
        </div>

        <br>

        <div class="mx-auto w-64">
        <date-picker label="Date" mode="date" color="indigo" title-position="left" locale="pt" v-model="default_date" />
        
        </div>

        <br>

        <div class="mx-auto w-full">

          <div>{{ tip }}</div> <br>
        <tiptap-textarea v-model="tip" />
        
        </div>

    </div>
</template>

<script setup>
import {ref, computed, watch} from "vue";
import { useNow, useDateFormat } from '@vueuse/core'
import Combobox from '@/Components/combobox.vue'
import ComboboxMultiple from '@/Components/combobox-multiple.vue'
import Combo from '@/Components/combo.vue'
import datePicker from '@/Components/date-picker.vue'
import tiptapTextarea from '@/Components/tiptap-textarea.vue'
import Layout from "../Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


defineProps({

});

defineOptions({
  layout: Layout
});

function loadUsers(query, setOptions) {

 

  fetch("https://gestlab.test/users?query=" + query)
    .then(response => response.json())
    .then(results => {
      setOptions(
        results.map(user => {
          return {
            value: user.id,
            label: user.name,
          };
        })
      );
    });
}

function createUser(option, setSelected) {
  fetch("https://gestlab.test:5173//users", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      name: option.label,
    }),
  })
    .then(response => response.json())
    .then(user => {
      setSelected({
        value: user.id,
        label: user.name,
      });
    });
}

const formatted = useDateFormat(useNow(), 'YYYY-MM-DD (ddd) HH:mm:ss', { locales: 'pt-Pt' })

const options = [
  {value: 1, label: "Wade Cooper"},
  {value: 2, label: "Arlene Mccoy"},
  {value: 3, label: "Devon Webb"},
  {value: 4, label: "Tom Cook"},
  {value: 5, label: "Tanya Fox"},
  {value: 6, label: "Hellen Schmidt"},
];
const user = ref();
const users = ref([]);
const loadUser = ref();
const loadUser2 = ref();
const default_date = ref(new Date());
const tip = ref(null);

</script>


<style lang="scss">
/* Basic editor styles */
.ProseMirror {
  > * + * {
    margin-top: 0.75em;
  }

  code {
    background-color: rgba(#616161, 0.1);
    color: #616161;
  }

  table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 0;
    overflow: hidden;

    td,
    th {
      min-width: 1em;
      border: 2px solid #ced4da;
      padding: 3px 5px;
      vertical-align: top;
      box-sizing: border-box;
      position: relative;

      > * {
        margin-bottom: 0;
      }
    }

    th {
      font-weight: bold;
      text-align: left;
      background-color: #f1f3f5;
    }

    .selectedCell:after {
      z-index: 2;
      position: absolute;
      content: "";
      left: 0; right: 0; top: 0; bottom: 0;
      background: rgba(200, 200, 255, 0.4);
      pointer-events: none;
    }

    .column-resize-handle {
      position: absolute;
      right: -2px;
      top: 0;
      bottom: -2px;
      width: 4px;
      background-color: #adf;
      pointer-events: none;
    }
  }

  .tableWrapper {
    padding: 1rem 0;
    overflow-x: auto;
  }

  .resize-cursor {
    cursor: ew-resize;
    cursor: col-resize;
  }
}

.content {
  padding: 1rem 0 0;

  h3 {
    margin: 1rem 0 0.5rem;
  }

  pre {
    border-radius: 5px;
    color: #333;
  }

  code {
    display: block;
    white-space: pre-wrap;
    font-size: 0.8rem;
    padding: 0.75rem 1rem;
    background-color:#e9ecef;
    color: #495057;
  }

  .character-count {
    margin-top: 1rem;
    color: #868e96;
  }
}
</style>
