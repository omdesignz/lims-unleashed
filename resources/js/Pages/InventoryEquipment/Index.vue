<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from "@/Components/records-table.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { TransitionRoot } from "@headlessui/vue";
import slideOver from "@/Components/slide-over.vue";
import { ref, computed, watch } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import combobox from "@/Components/combobox.vue";
import datePicker from "@/Components/date-picker.vue";
import CommentTextArea from "@/Components/comment-text-area.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ArrowLongRightIcon, ArrowPathRoundedSquareIcon, CheckIcon, PencilIcon, ChevronDownIcon, TrashIcon, ArrowDownTrayIcon, EyeIcon } from "@heroicons/vue/24/outline";
import equipmentImportForm from "@/Pages/InventoryItems/equipments-import-form.vue";
import equipmentExportForm from "@/Pages/InventoryEquipment/equipments-export.vue";


const props = defineProps({
  record: Object,
  fields: Array,
  model: String,
  abilities: Array,
  query: Object,
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

defineOptions({
  layout: Layout,
});

let form = useForm({
  name: "",
  brand: "",
  location: "",
  model: "",
  software: "",
  firmware: "",
  internal_code: "",
  range: "",
  precision: "",
  resolution: "",
  code: "",
  barcode: "",
  serial_number: "",
  last_calibration_date: null,
  next_calibration_date: null,
  reagent_open_date: null,
  reagent_expiry_date: null,
  description: "",
  obs: "",
  refrigerated: false,
  category_id: "",
  department_id: "",
  unit_id: "",
  eq_cat_id: "",
  type_id: "",
  lot: "",
  supplier_id: "",
  user_id: "",
  status_id: {
    value: null,
    label: null,
  },
  has_safety_documentation: false,
  packaging_type_id: "",
  reorder_qty: 0,
  packed_weight: 0,
  packed_weight_unit: "",
  packed_height: 0,
  packed_height_unit: "",
  packed_width: 0,
  packed_width_unit: "",
  packed_depth: 0,
  packed_depth_unit: "",
  documents: [],
  id: null,
});

const categoryId = ref(null);

const deleteForm = useForm({
    model_id: null,
    id: null,
});

const statusOptions = ref([]);

const dragging = ref(false);
const files = ref(null);

const actionId = ref(null);

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});

const slideOverDescription = computed(() => {
  return !form.id
    ? trans("gestlab.slideover.creating.description") + form.name
    : trans("gestlab.slideover.updating.description") + form.name;
});

const slideOverTitle = computed(() => {
  return !form.id
    ? trans("gestlab.slideover.creating.title")
    : trans("gestlab.slideover.updating.description");
});

const confirmationDialogTitle = computed(() => {
  return trans("gestlab.actions.confirmation_dialog_title." + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans(
    "gestlab.actions.confirmation_dialog_description." + actionId.value,
  );
});

// Watch for changes in categoryId
watch(categoryId, (newCategoryId) => {
  // When categoryId changes, immediately load the item statuses
  loadItemStatuses();
});

if (categoryId.value) {
  loadItemStatuses();
}

const openslideover = ref(false);

let actions = [
  {
    id: null,
    label: "gestlab.actions.bulk_actions_text",
  },
  {
    id: "delete",
    label: "gestlab.actions.delete",
  },
  {
    id: "restore",
    label: "gestlab.actions.restore",
  },
];

const close = () => {
  openslideover.value = false;
  form.clearErrors();
  // form.reset(); 
};


const showDeleteConfirmation = ref(false);
const showDeleteConfirmationSlideover = ref(false);

const openSlideoverWithData = (data) => {
  openslideover.value = true;
  form.id = data.id;
  form.name = data.name;
  form.brand = data.brand;
  form.location = data.location;
  form.model = data.model;
  form.software = data.software;
  form.firmware = data.firmware;
  form.internal_code = data.internal_code;
  form.range = data.range;
  form.precision = data.precision;
  form.resolution = data.resolution;
  form.code = data.code;
  form.barcode = data.barcode;
  form.serial_number = data.serial_number;
  form.last_calibration_date = data.last_calibration_date;
  form.next_calibration_date = data.next_calibration_date;
  form.reagent_open_date = data.reagent_open_date;
  form.reagent_expiry_date = data.reagent_expiry_date;
  form.description = data.description;
  form.obs = data.obs;
  form.refrigerated = data.refrigerated;
  form.reorder_qty = data.reorder_qty;
  form.packed_weight = data.packed_weight;
  form.packed_weight_unit = data.packed_weight_unit;
  form.packed_depth = data.packed_depth;
  form.packed_depth_unit = data.packed_depth_unit;
  form.packed_height = data.packed_height;
  form.packed_height_unit = data.packed_height_unit;
  form.packed_width = data.packed_width;
  form.packed_width_unit = data.packed_width_unit;
  form.department_id = {
    value: data.department_id,
    label: data.department,
  };
  form.unit_id = {
    value: data.unit_id,
    label: data.unit,
  };
  form.eq_cat_id = {
    value: data.eq_cat_id,
    label: data.eq_cat,
  };
  form.type_id = {
    value: data.type_id,
    label: data.type,
  };
  form.lot = data.lot;
  form.supplier_id = {
    value: data.supplier_id,
    label: data.supplier,
  };
  form.user_id = {
    value: data.user_id,
    label: data.user,
  };
  form.status_id = {
    value: data.status_id,
    label: data.status,
  };
  form.has_safety_documentation = data.has_safety_documentation;
  form.documents = data.documents;
  form.packaging_type_id = {
    value: data.packaging_type_id,
    label: data.packaging_type,
  };
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

  const onSelectedFiles = ($event) => {
        form.documents = [...$event.target.files];
  }
  
  const onDroppedFiles = ($event) => {
      dragging.value = false;
 
      let droppedFiles = [...$event.dataTransfer.items]
          .filter(item => item.kind === 'file')
          .map(item => item.getAsFile());
 
}

const deleteAttachment = (model_id, id, index) => {
      deleteForm.model_id = model_id;
      deleteForm.id = id;

      deleteForm.delete(route('iequipments.delete-attachment', {model_id: model_id, id: id}), {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
              form.documents.splice(index, 1);
          },
      });
}

let submit = () => {
  if (!form.id) {
    form.post(route("iequipments.store"), {
      preserveScroll: true,
      preserveState: false,
      onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  } else {
    form.post(route("iequipments.update", { iitem: form.id, _method: 'PUT' }), {
      preserveScroll: true,
      preserveState: false,
      onError: () => {
            showDeleteConfirmationSlideover.value = false
            openslideover.value = true
          },
      onSuccess: () => {
        openslideover.value = false;
        form.reset();
      },
    });
  }
};

const confirmAction = () => {
  executeAction(actionId.value);
};

const executeAction = (actionId) => {
  const recordIds = props.record.data
    .filter((record) => record.selected)
    .map((record) => record.id);

  if (!recordIds.length) return;

  switch (actionId) {
    case "delete":
      router.get(
        route("iequipments.destroy"),
        {
          recordIds: recordIds,
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
      break;

    case "restore":
      router.get(
        route("iequipments.restore"),
        {
          recordIds: recordIds,
        },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
  }
};

function loadCategories(query, setOptions) {
  fetch("/itemcategories/getItemCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadEquipmentCategories(query, setOptions) {
  fetch("/equipmentcategories/getEquipmentCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadDepartments(query, setOptions) {
  fetch("/departments/getDepartment?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadUnits(query, setOptions) {
  fetch("/iunits/getInventoryUnit?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.code,
          };
        }),
      );
    });
}

function loadSuppliers(query, setOptions) {
  fetch("/isuppliers/getInventoryItemSupplier?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadUsers(query, setOptions) {
  fetch("/users/getUser?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

function loadItemTypes(query, setOptions) {
  
    fetch("/itypes/getInventoryItemType?q=" + query)
      .then((response) => response.json())
      .then((results) => {
        setOptions(
          results.map((result) => {
            return {
              value: result.id,
              label: result.name,
            };
          }),
        );
      });
}

// function loadItemStatuses(query, setOptions) {

//   fetch("/itemstatuses/getItemStatus?q=" + query + "&category_id=" + categoryId.value)
//     .then((response) => response.json())
//     .then((results) => {
//       setOptions(
//         results.map((result) => {
//           return {
//             value: result.id,
//             label: result.name,
//           };
//         }),
//       );
//     });
// }

const loadItemStatuses = async (query = "") => {
  if(categoryId.value) {
    const response = await fetch("/itemstatuses/getItemStatus?q=" + query + "&category_id=" + 1);
    const results = await response.json();
    
    statusOptions.value = results.map((result) => ({
      value: result.id,
      label: result.name,
    }));

    console.log(JSON.stringify(statusOptions.value));

  } else {
    statusOptions.value = [];
  }
};

function loadPackagingTypes(query, setOptions) {
  fetch("/packagingcategories/getPackagingCategory?q=" + query)
    .then((response) => response.json())
    .then((results) => {
      setOptions(
        results.map((result) => {
          return {
            value: result.id,
            label: result.name,
          };
        }),
      );
    });
}

const downloadAttachment = (file) => {
      console.log(file);
        axios.get(route('iequipments.download-single-attachment', {
            model_id: file.id
        })).then(response => {
            console.log(response);
        });
    }
</script>
<template>
<div class="space-y-6" :class="commercialDocumentThemeClasses">
  <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">
      {{ $t("gestlab.general.labels.iequipments.page_title") }}
    </h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
  </div>

  <equipment-import-form />
  <equipment-export-form />

  <records-table
    :record="props.record"
    :model="props.model"
    :abilities="props.abilities"
    :fields="props.fields"
    :slideOverEdit="props.slideOverEdit"
    :query="props.query"
    :actions="actions"
    @execute-action="
      ($event) => {
        showDeleteConfirmation = true;
        actionId = $event;
      }
    "
    @create-record="openslideover = true"
    @slideover-on="openSlideoverWithData"
  >
  <template #actions="{ id }">
      <Link
                    :href="route('iequipments.show', {iitem: id})"
                    class="text-ft-gray hover:text-blue-900 transform transition-all duration-200 hover:scale-150"
                  >
                  <EyeIcon class="h-4 w-4" />
            </Link>
    </template>
</records-table>
  <br>

  <slide-over
    v-if="openslideover"
    @close="close"
    :title="slideOverTitle"
    :description="slideOverDescription"
  >
    <template #content>
      <div
        class="space-y-6 py-6 sm:space-y-0 sm:divide-y sm:divide-gray-200 sm:py-0"
      >
        <!-- Equipment Category -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          v-if="form.category_id?.value === 1"
        >
          <div>
            <label
              for="eq_cat_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.eq_cat_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.eq_cat_id"
              v-model="form.eq_cat_id"
              :load-options="loadEquipmentCategories"
            />
            <p
              v-if="form.errors.eq_cat_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.eq_cat_id }}
            </p>
          </div>
        </div>

        <!-- Name -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="name"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.name") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.name"
              type="text"
              name="name"
              id="name"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.name
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.name"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.name }}
            </p>
          </div>
        </div>

        <!-- Brand -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="brand"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.brand") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.brand"
              type="text"
              name="brand"
              id="brand"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.brand
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.brand"
              class="mt-2 text-sm text-red-600"
              id="brand-error"
            >
              {{ form.errors.brand }}
            </p>
          </div>
        </div>

        <!-- Location -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          v-if="form.category_id?.value === 1"
        >
          <div>
            <label
              for="location"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.location") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.location"
              type="text"
              name="location"
              id="location"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.location
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.location"
              class="mt-2 text-sm text-red-600"
              id="location-error"
            >
              {{ form.errors.location }}
            </p>
          </div>
        </div>

        <!-- Department -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="department_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.department_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.department_id"
              v-model="form.department_id"
              :load-options="loadDepartments"
            />
            <p
              v-if="form.errors.department_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.department_id }}
            </p>
          </div>
        </div>

        <!-- Model -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="model"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.model") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.model"
              type="text"
              name="model"
              id="model"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.model
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.model"
              class="mt-2 text-sm text-red-600"
              id="model-error"
            >
              {{ form.errors.model }}
            </p>
          </div>          
        </div>

        <!-- Software -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="software"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.software") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.software"
              type="text"
              name="software"
              id="software"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.software
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.software"
              class="mt-2 text-sm text-red-600"
              id="software-error"
            >
              {{ form.errors.software }}
            </p>
          </div>
        </div>

        <!-- Firmware -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="firmware"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.firmware") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.firmware"
              type="text"
              name="firmware"
              id="firmware"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.firmware
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.firmware"
              class="mt-2 text-sm text-red-600"
              id="firmware-error"
            >
              {{ form.errors.firmware }}
            </p>
          </div>
        </div>

        <!-- Internal Code -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="internal_code"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.internal_code") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              disabled
              v-model="form.internal_code"
              type="text"
              name="internal_code"
              id="internal_code"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.internal_code
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.internal_code"
              class="mt-2 text-sm text-red-600"
              id="internal_code-error"
            >
              {{ form.errors.internal_code }}
            </p>
          </div>
        </div>

        <!-- Range -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="range"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.range") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.range"
              type="text"
              name="range"
              id="range"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.range
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.range"
              class="mt-2 text-sm text-red-600"
              id="range-error"
            >
              {{ form.errors.range }}
            </p>
          </div>
        </div>

        <!-- Precision -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="precision"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.precision") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.precision"
              type="text"
              name="precision"
              id="precision"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.precision
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.precision"
              class="mt-2 text-sm text-red-600"
              id="precision-error"
            >
              {{ form.errors.precision }}
            </p>
          </div>
        </div>

        <!-- Resolution -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="resolution"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.resolution") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.resolution"
              type="text"
              name="resolution"
              id="resolution"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.resolution
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.resolution"
              class="mt-2 text-sm text-red-600"
              id="resolution-error"
            >
              {{ form.errors.resolution }}
            </p>
          </div>
        </div>

        <!-- Code -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="code"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.code") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.code"
              type="text"
              name="code"
              id="code"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.code
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.code"
              class="mt-2 text-sm text-red-600"
              id="code-error"
            >
              {{ form.errors.code }}
            </p>
          </div>
        </div>

        <!-- Barcode -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="barcode"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.barcode") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.barcode"
              type="text"
              name="barcode"
              id="barcode"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.barcode
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.barcode"
              class="mt-2 text-sm text-red-600"
              id="barcode-error"
            >
              {{ form.errors.barcode }}
            </p>
          </div>
        </div>

        <!-- Serial Number -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="serial_number"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.serial_number") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.serial_number"
              type="text"
              name="serial_number"
              id="serial_number"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.serial_number
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.serial_number"
              class="mt-2 text-sm text-red-600"
              id="serial_number-error"
            >
              {{ form.errors.serial_number }}
            </p>
          </div>
        </div>

        <!-- Last Calibration Date -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="last_calibration_date"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{
                $t("gestlab.general.labels.iequipments.last_calibration_date")
              }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <date-picker
              class="py-1.5"
              v-model.string="form.last_calibration_date"
              locale="pt"
              color="yellow"
              mode="date"
              :input-debounce="500"
              @update:model-value="updateDate"
              :masks="masks"
            />
            <p
              v-if="form.errors.last_calibration_date"
              class="mt-2 text-sm text-red-600"
              id="last_calibration_date-error"
            >
              {{ form.errors.last_calibration_date }}
            </p>
          </div>
        </div>

        <!-- Next Calibration Date -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="next_calibration_date"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{
                $t("gestlab.general.labels.iequipments.next_calibration_date")
              }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <date-picker
              class="py-1.5"
              v-model.string="form.next_calibration_date"
              locale="pt"
              color="yellow"
              mode="date"
              :input-debounce="500"
              @update:model-value="updateDate"
              :masks="masks"
            />
            <p
              v-if="form.errors.next_calibration_date"
              class="mt-2 text-sm text-red-600"
              id="next_calibration_date-error"
            >
              {{ form.errors.next_calibration_date }}
            </p>
          </div>
        </div>

        <!-- Reagent Open Date -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="reagent_open_date"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.reagent_open_date") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <date-picker
              class="py-1.5"
              v-model.string="form.reagent_open_date"
              locale="pt"
              color="yellow"
              mode="date"
              :input-debounce="500"
              @update:model-value="updateDate"
              :masks="masks"
            />
            <p
              v-if="form.errors.reagent_open_date"
              class="mt-2 text-sm text-red-600"
              id="reagent_open_date-error"
            >
              {{ form.errors.reagent_open_date }}
            </p>
          </div>
        </div>

        <!-- Reagent Expiry Date -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="reagent_expiry_date"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.reagent_expiry_date") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <date-picker
              class="py-1.5"
              v-model.string="form.reagent_expiry_date"
              locale="pt"
              color="yellow"
              mode="date"
              :input-debounce="500"
              @update:model-value="updateDate"
              :masks="masks"
            />
            <p
              v-if="form.errors.reagent_expiry_date"
              class="mt-2 text-sm text-red-600"
              id="reagent_expiry_date-error"
            >
              {{ form.errors.reagent_expiry_date }}
            </p>
          </div>
        </div>

        <!-- Unit -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="unit_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.unit_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.unit_id"
              v-model="form.unit_id"
              :load-options="loadUnits"
            />
            <p
              v-if="form.errors.unit_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.unit_id }}
            </p>
          </div>
        </div>

        <!-- Type -->
        <div v-if="form.category_id?.value === 1"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="type_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.type_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.type_id"
              v-model="form.type_id"
              :load-options="loadItemTypes"
            />
            <p
              v-if="form.errors.type_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.type_id }}
            </p>
          </div>
        </div>

        <!-- Lot -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="lot"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.lot") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.lot"
              type="text"
              name="lot"
              id="lot"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.lot
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.lot"
              class="mt-2 text-sm text-red-600"
              id="lot-error"
            >
              {{ form.errors.lot }}
            </p>
          </div>
        </div>

        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="acceptance_criteria"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.acceptance_criteria") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.acceptance_criteria"
              type="text"
              name="acceptance_criteria"
              id="acceptance_criteria"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.acceptance_criteria
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.acceptance_criteria"
              class="mt-2 text-sm text-red-600"
              id="acceptance_criteria-error"
            >
              {{ form.errors.acceptance_criteria }}
            </p>
          </div>
        </div>

        <!-- Supplier -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="supplier_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.supplier_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.supplier_id"
              v-model="form.supplier_id"
              :load-options="loadSuppliers"
            />
            <p
              v-if="form.errors.supplier_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.supplier_id }}
            </p>
          </div>
        </div>

        <!-- User -->
          <!-- <div v-if="form.category_id?.value === 2"
            class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          > 
            <div>
              <label
                for="user_id"
                class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
                >{{ $t("gestlab.general.labels.iequipments.user_id") }}</label
              > 
            </div>
            <div class="sm:col-span-2">
              <combobox :hasError="form.errors.user_id" v-model="form.user_id" :load-options="loadUsers"/>
              <p
                v-if="form.errors.user_id"
                class="mt-2 text-sm text-red-600"
                id="name-error"
              >
                {{ form.errors.user_id }}
              </p>
            </div>
          </div> -->


        <!-- Description -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="description"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.description") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <textarea
              v-model="form.description"
              name="description"
              id="description"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.description
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.description"
              class="mt-2 text-sm text-red-600"
              id="description-error"
            >
              {{ form.errors.description }}
            </p>
          </div>
        </div>

        <!-- Observations -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="obs"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.obs") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <textarea
              v-model="form.obs"
              name="obs"
              id="obs"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.obs
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.obs"
              class="mt-2 text-sm text-red-600"
              id="obs-error"
            >
              {{ form.errors.obs }}
            </p>
          </div>
        </div>

        <!-- Refrigerated -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="refrigerated"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.refrigerated") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.refrigerated"
              type="checkbox"
              name="refrigerated"
              id="refrigerated"
              class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.refrigerated
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.refrigerated"
              class="mt-2 text-sm text-red-600"
              id="refrigerated-error"
            >
              {{ form.errors.refrigerated }}
            </p>
          </div>
        </div>

        <!-- Status -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="status"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.status_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <!-- <combobox :hasError="form.errors.status_id" v-model="form.status_id" :options="statusOptions"/> -->
          
          <div class="mt-2 grid grid-cols-1">
            <select id="status_id" name="status_id" v-model="form.status_id" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6">
              <option v-for="option in statusOptions" :key="option.value" :value="option">{{ option.label }}</option>
            </select>
          </div>
  
           
            <p
              v-if="form.errors.status_id"
              class="mt-2 text-sm text-red-600"
              id="status-error"
            >
              {{ form.errors.status_id }}
            </p>
          </div>
        </div>

        <!-- Safety Documentation -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="has_safety_documentation"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.has_safety_documentation") }}</label
            >
          </div>
          <div class="sm:col-span-full">
            <input
              v-model="form.has_safety_documentation"
              type="checkbox"
              name="has_safety_documentation"
              id="has_safety_documentation"
              class="h-5 w-5 rounded-full border-0 py-1.5 text-blue-900 shadow-sm ring-1 ring-inset ring-blue-900 placeholder:text-blue-900 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.has_safety_documentation
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <!-- <CommentTextArea v-model="form.content" :user="$page.props.auth.user.name" :photo="$page.props.auth.user.profile_photo_url" @submit-form="addComment" @attach-files="(files) => comment.attachments = files" /> -->

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
                    <DocumentPlusIcon class="mx-auto h-8 w-8 text-gray-400" aria-hidden="true" />
                    <div class="mt-4 flex text-xs leading-6 text-gray-600">
                      <label for="files" class="relative cursor-pointer rounded-full bg-orange font-semibold text-black focus-within:outline-none focus-within:ring-2 focus-within:ring-orange focus-within:ring-offset-2 hover:text-white hover:bg-orange-600 px-2">
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

              <!-- End Upload Form -->

              <!-- List Files -->

              <div v-if="form.documents.length" class="sm:col-span-full m-6">
                  <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="(file, index) in form.documents" :key="file.name" class="flex items-center justify-between gap-x-6 py-5">
                      <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                          <p class="text-sm font-semibold leading-6 text-gray-900">{{ file.name }}</p>
                          <p class="mt-0.5 whitespace-nowrap rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-white bg-blue-900 ring-blue-900">{{ file.extension || file.name.split('.').pop() }}</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500">
                          <p class="truncate">{{ readableFileSize(file.size) }}</p>
                        </div>
                      </div>
                      <div class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer">
                        <a :href="file.original_url" :download="file.name" class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer" v-if="file.original_url">
                          <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                        </a>
                        <TrashIcon class="h-5 w-5" aria-hidden="true" @click="form.documents.splice(index, 1)" v-if="!file.original_url" />
                        <TrashIcon class="h-5 w-5" aria-hidden="true" @click="deleteAttachment(form.id, file.id, index)" v-if="file.original_url" />
                      </div>
                    </li>
                  </ul>
              </div>
            </div>

              <!-- End List Files -->

            <p
              v-if="form.errors.has_safety_documentation"
              class="mt-2 text-sm text-red-600"
              id="has_safety_documentation-error"
            >
              {{ form.errors.has_safety_documentation }}
            </p>
          </div>

          <!-- <div v-if="form.documents.length" class="sm:w-full">
              <ul role="list" class="divide-y divide-gray-100">
                <li v-for="(attachment, index) in form.documents" :key="index" class="flex items-center justify-between gap-x-6 py-5">
                  <div class="min-w-0">
                    <div class="flex items-start gap-x-3">
                      <p class="text-sm font-semibold leading-6 text-gray-900">{{ attachment.name }}</p>
                      <p class="mt-0.5 whitespace-nowrap rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-white bg-blue-900 ring-blue-900">{{ attachment.extension }}</p>
                    </div>
                    <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500">
                      <p class="truncate">{{ readableFileSize(attachment.size) }}</p>
                    </div>
                  </div>
                  <a :href="attachment.original_url" :download="attachment.name" class="flex flex-none items-center gap-x-4 pointer-events-auto cursor-pointer">
                    <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                  </a>
                </li>
              </ul>
          </div> -->
        </div>

        <!-- Packaging Type -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
          v-if="form.category_id?.value !== 1"
        >
          <div>
            <label
              for="packaging_type_id"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packaging_type_id") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <combobox
              :hasError="form.errors.packaging_type_id"
              v-model="form.packaging_type_id"
              :load-options="loadPackagingTypes"
            />
            <p
              v-if="form.errors.packaging_type_id"
              class="mt-2 text-sm text-red-600"
              id="name-error"
            >
              {{ form.errors.packaging_type_id }}
            </p>
          </div>
        </div>

        <!-- Reorder Quantity -->
        <div
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="reorder_qty"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.reorder_qty") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.reorder_qty"
              type="number"
              name="reorder_qty"
              id="reorder_qty"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.reorder_qty
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.reorder_qty"
              class="mt-2 text-sm text-red-600"
              id="reorder_qty-error"
            >
              {{ form.errors.reorder_qty }}
            </p>
          </div>
        </div>

        <!-- Packed Weight -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_weight"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_weight") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_weight"
              type="number"
              name="packed_weight"
              id="packed_weight"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_weight
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.packed_weight"
              class="mt-2 text-sm text-red-600"
              id="packed_weight-error"
            >
              {{ form.errors.packed_weight }}
            </p>
          </div>
        </div>

        <!-- Packed Weight Unit -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_weight_unit"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_weight_unit") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_weight_unit"
              type="text"
              name="packed_weight_unit"
              id="packed_weight_unit"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_weight_unit
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p v-if="form.errors.packed_weight_unit" class="mt-2 text-sm text-red-600" id="packed_weight_unit-error">{{ form.errors.packed_weight_unit }}</p>
          </div>
        </div>

        <!-- Packed Height -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_height"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_height") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_height"
              type="number"
              name="packed_height"
              id="packed_height"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_height
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.packed_height"
              class="mt-2 text-sm text-red-600"
              id="packed_height-error"
            >
              {{ form.errors.packed_height }}
            </p>
          </div>
        </div>

        <!-- Packed Height Unit -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_height_unit"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_height_unit") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_height_unit"
              type="number"
              name="packed_height_unit"
              id="packed_height_unit"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_height_unit
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p v-if="form.errors.packed_height_unit" class="mt-2 text-sm text-red-600" id="packed_height_unit-error">{{ form.errors.packed_height_unit }}</p>
          </div>
        </div>

        <!-- Packed Width -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_width"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_width") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_width"
              type="number"
              name="packed_width"
              id="packed_width"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_width
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.packed_width"
              class="mt-2 text-sm text-red-600"
              id="packed_width-error"
            >
              {{ form.errors.packed_width }}
            </p>
          </div>
        </div>

        <!-- Packed Width Unit -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_width_unit"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_width_unit") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_width_unit"
              type="number"
              name="packed_width_unit"
              id="packed_width_unit"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_width_unit
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p v-if="form.errors.packed_width_unit" class="mt-2 text-sm text-red-600" id="packed_width_unit-error">{{ form.errors.packed_width_unit }}</p>
          </div>
        </div>

        <!-- Packed Depth -->
        <div v-if="form.category_id?.value === 2"
          class="space-y-2 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:space-y-0 sm:px-6 sm:py-5"
        >
          <div>
            <label
              for="packed_depth"
              class="block text-sm font-medium leading-6 text-gray-900 sm:mt-1.5"
              >{{ $t("gestlab.general.labels.iequipments.packed_depth") }}</label
            >
          </div>
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_depth"
              type="number"
              name="packed_depth"
              id="packed_depth"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_depth
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p
              v-if="form.errors.packed_depth"
              class="mt-2 text-sm text-red-600"
              id="packed_depth-error"
            >
              {{ form.errors.packed_depth }}
            </p>
          </div>

          <!-- Packed Depth Unit -->
          <div class="sm:col-span-2">
            <input
              v-model="form.packed_depth_unit"
              type="number"
              name="packed_depth_unit"
              id="packed_depth_unit"
              class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-900 sm:text-sm sm:leading-6"
              :class="[
                form.errors.packed_depth_unit
                  ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
                  : '',
              ]"
            />
            <p v-if="form.errors.packed_depth_unit" class="mt-2 text-sm text-red-600" id="packed_depth_unit-error">{{ form.errors.packed_depth_unit }}</p>
          </div>
        </div>
      </div>
    </template>

    <template #action_buttons>
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          @click="
            openslideover = false;
            form.reset();
          "
        >
          {{ $t("gestlab.general.buttons.cancel") }}
        </button>
        <!-- <TransitionRoot
            :show="!form.isDirty"
            enter="transition-opacity duration-75"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="transition-opacity duration-150"
            leave-from="opacity-100"
            leave-to="opacity-0"
        >
            I will appear and disappear.
        </TransitionRoot> -->
        <button
          v-if="form.isDirty"
          @click="showDeleteConfirmationSlideover = true"
          :disabled="form.processing"
          type="button"
          class="inline-flex justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-900"
        >
          {{
            !form.id
              ? $t("gestlab.general.buttons.submit")
              : $t("gestlab.general.buttons.update")
          }}
        </button>
      </div>
    </template>
  </slide-over>

  <confirm-dialog
    @canceled="showDeleteConfirmation = false"
    @close="showDeleteConfirmation = false"
    @confirmed="confirmAction"
    v-if="showDeleteConfirmation"
    :title="confirmationDialogTitle"
    :description="confirmationDialogDescription"
    confirm="Sim"
    cancel="Não"
  />

  <confirm-dialog
    size="sm:max-w-2xl"
    alignment="sm:items-start"
    @canceled="showDeleteConfirmationSlideover = false"
    @close="showDeleteConfirmationSlideover = false"
    @confirmed="submit"
    v-if="showDeleteConfirmationSlideover"
    :title="$t('gestlab.actions.confirmation_dialog_title.default')"
    :description="$t('gestlab.actions.confirmation_dialog_description.default')"
    confirm="Sim"
    cancel="Não"
  >
    <div class="mt-4">
      <div
        class="font-semibold inline-flex px-2 py-1 leading-4 text-xs rounded-full text-white bg-blue-900 sm:text-xs mb-2"
      >
        <p class="text-xs">{{ $t("gestlab.general.labels.summary") }}</p>
      </div>
      <div>
        <div class="px-4 sm:px-0 rounded-full text-white bg-blue-900">
          <!-- <h3 class="text-base font-semibold leading-7 text-gray-900">Resumo</h3>
          <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details and application.</p> -->
        </div>
        <div class="mt-6 border-t border-gray-100">
          <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 1">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.eq_cat_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.eq_cat_id?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.name") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.name }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0 sm:py-5">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.department_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.department_id?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.code") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.code }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.barcode") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.barcode }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 1">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.serial_number") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.serial_number }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 1">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.last_calibration_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.last_calibration_date }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 1">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.next_calibration_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.next_calibration_date }}
              </dd>
            </div>
            <div v-if="form.category_id?.value === 2" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.reagent_open_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.reagent_open_date }}
              </dd>
            </div>
            <div v-if="form.category_id?.value === 2" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.reagent_expiry_date") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.reagent_expiry_date }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.unit_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.unit_id?.label }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.description") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.description }}
              </dd>
            </div>
            <div v-if="form.category_id?.value === 2" class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.obs") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.obs }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 1">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.type_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.type_id?.label }}
              </dd>
            </div>
            <!-- Lot -->
             <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.lot") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.lot }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.acceptance_criteria") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.acceptance_criteria }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.refrigerated") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.refrigerated }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.reorder_qty") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.reorder_qty }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.packed_weight") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.packed_weight + ' ' + form.packed_weight_unit }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.packed_height") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.packed_height + ' ' + form.packed_height_unit }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.packed_width") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.packed_width + ' ' + form.packed_width_unit }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0" v-if="form.category_id?.value === 2">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.packed_depth") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.packed_depth + ' ' + form.packed_depth_unit }}
              </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
              <dt class="text-sm font-medium leading-6 text-gray-900">
                {{ $t("gestlab.general.labels.iequipments.status_id") }}
              </dt>
              <dd
                class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
              >
                {{ form.status_id?.label }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </confirm-dialog>
</div>
</template>
