<template>
  <div class="border-b border-gray-200 pb-5">
    <h3 class="text-base font-semibold leading-6 text-gray-900">
      {{ $t("gestlab.general.labels.equipment_connection_test.page_title") }}
    </h3>
    <p class="mt-2 max-w-4xl text-sm text-gray-500"></p>
  </div>

  <fieldset>
    <legend class="text-sm font-semibold leading-6 text-gray-900">
      {{
        $t("gestlab.general.labels.equipment_connection_test.connection_type")
      }}
    </legend>
    <RadioGroup
      v-model="selectedDevice"
      class="mt-6 grid grid-cols-1 gap-y-6 sm:grid-cols-3 sm:gap-x-4"
    >
      <RadioGroupOption
        as="template"
        v-for="device in deviceList"
        :key="device.id"
        :value="device"
        :aria-label="device.title"
        :aria-description="`${device.description} to ${device.icon}`"
        v-slot="{ active, checked }"
      >
        <div
          :class="[
            active ? 'border-blue-900 ring-2 ring-blue-900' : 'border-gray-300',
            'relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none',
          ]"
        >
          <span class="flex flex-1">
            <span class="flex flex-col">
              <span class="block text-sm font-medium text-gray-900">{{
                device.title
              }}</span>
              <!-- <span class="mt-1 flex items-center text-sm text-gray-500">{{ device.description }}</span> -->
              <span
                class="mt-6 text-sm font-medium w-8 h-8"
                :class="[checked ? 'text-blue-900' : 'text-gray-900']"
                v-html="device.icon"
              ></span>
            </span>
          </span>
          <CheckCircleIcon
            :class="[!checked ? 'invisible' : '', 'h-5 w-5 text-blue-900']"
            aria-hidden="true"
          />
          <span
            :class="[
              active ? 'border' : 'border-2',
              checked ? 'border-blue-900' : 'border-transparent',
              'pointer-events-none absolute -inset-px rounded-lg',
            ]"
            aria-hidden="true"
          />
        </div>
      </RadioGroupOption>
    </RadioGroup>
  </fieldset>
  <div class="relative mt-12">
    <div class="flex justify-end">
      <button
        @click="connectToDevice"
        type="button"
        class="-top-2 left-2 inline-block bg-blue-900 px-4 py-2 border border-transparent font-semibold text-xs text-white uppercase rounded-md tracking-widest hover:bg-blue-800"
      >
        {{ $t("gestlab.general.buttons.connect_equipment") }}
      </button>
    </div>
    <textarea
      v-model="deviceData"
      name="device_data"
      v-if="deviceData"
      id="device_data"
      class="block mt-2 w-full h-screen rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
      placeholder="Device data will appear here"
    />
  </div>
  <div v-if="errorMessage" style="color: red">
    <p>Error: {{ errorMessage }}</p>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { ref, watch, reactive } from "vue";
import { RadioGroup, RadioGroupOption } from "@headlessui/vue";
import { useForm, router } from "@inertiajs/vue3";
import { CheckCircleIcon } from "@heroicons/vue/24/outline";
import { usePermission } from "@/Composables/usePermissions";

const props = defineProps({
  auth: Object,
});

defineOptions({
  layout: Layout,
});

const device = ref(null);
const server = ref(null);
const characteristic = ref(null);
const deviceData = ref(null);
const errorMessage = ref(null);

const deviceList = [
  {
    id: 1,
    title: "USB",
    description: "Last message sent an hour ago",
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L15 6H13V13.381L16 11.882L15.999 11H15V7H19V11H17.999L18 13.118L13 15.618L13.0009 17.171C14.1656 17.5831 15 18.6941 15 20C15 21.6569 13.6569 23 12 23C10.3431 23 9 21.6569 9 20C9 18.813 9.68934 17.7871 10.6895 17.3006L6 14L5.99892 11.7318C5.40172 11.3858 5 10.7398 5 10C5 8.89543 5.89543 8 7 8C8.10457 8 9 8.89543 9 10C9 10.7403 8.59783 11.3866 8.00007 11.7324L8 13L11 15.086V6H9L12 1ZM12 19C11.4477 19 11 19.4477 11 20C11 20.5523 11.4477 21 12 21C12.5523 21 13 20.5523 13 20C13 19.4477 12.5523 19 12 19Z"></path></svg>',
  },
  {
    id: 2,
    title: "Bluetooth",
    description: "Last message sent 2 weeks ago",
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14.3113 12L18.6544 16.3431L12.9976 22H10.9976V15.3137L6.63359 19.6777L5.21938 18.2635L10.9976 12.4853V11.5147L5.21938 5.73654L6.63359 4.32233L10.9976 8.68629V2H12.9976L18.6544 7.65685L14.3113 12ZM12.9976 13.5147V19.1716L15.826 16.3431L12.9976 13.5147ZM12.9976 10.4853L15.826 7.65685L12.9976 4.82843V10.4853ZM19.5 13.5C18.6716 13.5 18 12.8284 18 12C18 11.1716 18.6716 10.5 19.5 10.5C20.3284 10.5 21 11.1716 21 12C21 12.8284 20.3284 13.5 19.5 13.5ZM6.5 13.5C5.67157 13.5 5 12.8284 5 12C5 11.1716 5.67157 10.5 6.5 10.5C7.32843 10.5 8 11.1716 8 12C8 12.8284 7.32843 13.5 6.5 13.5Z"></path></svg>',
  },
  {
    id: 3,
    title: "Serial",
    description: "Last message sent 4 days ago",
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.7931 5.79285 12.586 12 18.7931 18.2071 20.2073 16.7928 15.4144 12 20.2073 7.20706 18.7931 5.79285ZM5.20697 18.2072 11.4141 12.0001 5.20697 5.793 3.79276 7.20721 8.58565 12.0001 3.79276 16.793 5.20697 18.2072Z"></path></svg>',
  },
];

const selectedDevice = ref(deviceList[0]);

// const connectToDevice = async () => {
//   try {
//     device.value = await navigator.bluetooth.requestDevice({
//       // filters: [
//       //   {
//       //     name: "A33 de Gilson Marcos Colsoul",
//       //     services: ["battery_service"],
//       //   },
//       // ],
//       optionalServices: ["battery_service"],
//       acceptAllDevices: true,
//     });

//     server.value = await device.value.gatt.connect();
//     const service = await server.value.getPrimaryService("battery_service");

//     characteristic.value = await service.getCharacteristic("battery_level");

//     console.log(service.value);

//     const value = await characteristic.value.readValue();
//     deviceData.value = value.getUint8(0);

//     // Example: Subscribe to notifications
//     characteristic.value.addEventListener(
//       "characteristicvaluechanged",
//       handleCharacteristicValueChanged,
//     );
//     await characteristic.value.startNotifications();
//   } catch (error) {
//     handleError(error);
//   }
// };

// const connectToDevice = async () => {
//   try {
//     navigator.usb.getDevices().then((devices) => {
//       devices.forEach((device) => {
//         console.log(device.productName); // "Arduino Micro"
//         console.log(device.manufacturerName); // "Arduino LLC"
//         console.log(device);
//         const result = device.transferIn(1, 64); // Endpoint number and length
//         deviceData.value = new TextDecoder().decode(result.data);
//       });
//     });
//   } catch (error) {
//     handleError(error);
//   }
// };

async function connectToDevice() {
  try {
    const device = await navigator.usb.requestDevice({ filters: [] });
    console.log(device);
  } catch (e) {
    console.error(e);
  }
}

// const connectToDevice = async (devName) => {
//   switch (devName) {
//     case "USB":
//       try {
//         const filters = [{ vendorId: 0x1234 }]; // Replace with your device's vendorId
//         device.value = await navigator.usb.requestDevice({ filters });
//         await device.value.open();
//         await device.value.selectConfiguration(1);
//         await device.value.claimInterface(0);

//         // Example: Read data from the device
//         const result = await device.value.transferIn(1, 64); // Endpoint number and length
//         deviceData.value = new TextDecoder().decode(result.data);

//         // Example: Write data to the device
//         const encoder = new TextEncoder();
//         const data = encoder.encode("Hello Device");
//         await device.value.transferOut(1, data); // Endpoint number and data
//       } catch (error) {
//         handleError(error);
//       }

//       break;

//     case "Bluetooth":
//       console.log(JSON.stringify(selectedDevice));

//       break;

//     case "Serial":
//       console.log(JSON.stringify(selectedDevice));

//       break;

//     default:
//       console.log("No device");
//   }
// };

const handleCharacteristicValueChanged = (event) => {
  const value = event.target.value;
  deviceData.value = value.getUint8(0);
};

const handleError = (error) => {
  console.error("There was an error:", error);
  if (error.name === "NotFoundError") {
    errorMessage.value = "No compatible USB device found.";
  } else if (error.name === "SecurityError") {
    errorMessage.value = "Permission to access the device was denied.";
  } else if (error.name === "NetworkError") {
    errorMessage.value =
      "The device was disconnected or there was a communication issue.";
  } else {
    errorMessage.value = `An unexpected error occurred: ${error.message}`;
  }
};
</script>

<style scoped></style>
