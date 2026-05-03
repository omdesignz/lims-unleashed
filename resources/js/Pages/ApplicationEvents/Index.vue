<!-- resources/js/Pages/Events/Index.vue -->
<template>
    <div class="container mx-auto p-6">
      <h1 class="text-2xl font-bold mb-4">Manage Events & Email Templates</h1>
  
      <button @click="syncEvents" class="btn btn-primary mb-4">Sync Events</button>
  
      <table class="table-auto w-full bg-white rounded shadow">
        <thead>
          <tr>
            <th class="px-4 py-2">Event Name</th>
            <th class="px-4 py-2">Associated Template</th>
            <th class="px-4 py-2">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="event in events" :key="event.id">
            <td class="border px-4 py-2">{{ event.name }}</td>
            <td class="border px-4 py-2">
              {{ event.email_template ? event.email_template.name : "None" }}
            </td>
            <td class="border px-4 py-2">
              <select v-model="selectedTemplate[event.id]" class="input">
                <option v-for="template in templates" :key="template.id" :value="template.id">
                  {{ template.name }}
                </option>
              </select>
              <button @click="associateTemplate(event.id)" class="btn btn-sm">
                Associate
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </template>
  
  <script setup>
  import { ref } from "vue";
  import { useForm, router } from "@inertiajs/vue3";

  const props = defineProps({
    events: Array,
    templates: Array,
  });

  const selectedTemplate = ref({});

  const syncEvents = () => {
    router.post("/app-events/sync");
  };

  const associateTemplate = (eventId) => {
    router.post(`/app-events/${eventId}/associate`, {
      email_template_id: selectedTemplate.value[eventId],
    });
  };
  
  </script>
  
  <style>
  .input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 0.25rem;
  }
  .btn {
    padding: 0.5rem 1rem;
    background-color: #4f46e5;
    color: white;
    border-radius: 0.25rem;
  }
  </style>
  