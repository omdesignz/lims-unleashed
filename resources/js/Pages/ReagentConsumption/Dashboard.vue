<template>
      <!-- Card Headings: Search with Filter -->
  <form @submit.prevent>
    <div
      class="flex flex-col overflow-hidden rounded-lg bg-white shadow-xs dark:bg-gray-800 dark:text-gray-100 mt-2"
    >
      <!-- Card Header -->
      <div
        class="flex flex-col gap-3 bg-blue-900 text-white px-5 py-2 text-center sm:flex-row sm:items-center sm:justify-between sm:gap-0 sm:text-left dark:bg-gray-700/50"
      >
        <div>
          <div class="text-gray-900 text-sm font-medium leading-6">
                <label class="mr-2 text-white">Sensibilidade de Alerta (%):</label>
                <input class="text-gray-900 rounded-lg border border-gray-200 py-1 pr-3 pl-3 focus:border-blue-900 focus:ring-3 focus:ring-blue-900/50 dark:border-gray-700 dark:bg-gray-800 dark:focus:border-blue-900 text-sm font-medium leading-6" type="number" v-model="alertSensitivity" min="1" max="100" @change="fetchChartData" />
        
                <label class="mr-2 ml-4 text-white">Duração da Média (Dias):</label>
                <input class="text-gray-900 rounded-lg border border-gray-200 py-1 pr-3 pl-3 focus:border-blue-900 focus:ring-3 focus:ring-blue-900/50 dark:border-gray-700 dark:bg-gray-800 dark:focus:border-blue-900 text-sm font-medium leading-6" type="number" v-model="movingAverageLength" min="1" max="30" @change="fetchChartData" />
            </div>
        </div>
        <div class="sm:w-48">
          <select
            v-model="selectedFilter" @change="fetchChartData"
            id="date"
            name="date"
            class="block text-gray-900 w-full rounded-lg border border-gray-200 py-1 pr-10 pl-3 focus:border-blue-900 focus:ring-3 focus:ring-blue-900/50 dark:border-gray-700 dark:bg-gray-800 dark:focus:border-blue-900 text-sm font-medium leading-6"
          >
          <option value="7_days">Últimos 7 Dias vs. Próximos 7 Dias</option>
          <option value="30_days">Últimos 30 Dias vs. Próximos 30 Dias</option>
          <option value="this_month">Este Mês vs. Último Mês</option>
          <option value="custom">Personalizado</option>
          </select>

        <!-- <input v-if="selectedFilter === 'custom'" type="date" v-model="customStartDate" @change="fetchChartData" /> -->
        <div class="mt-2">
            <date-picker v-if="selectedFilter === 'custom'" class="py-1 text-sm font-medium leading-6 text-gray-900" v-model.string="customStartDate" locale="pt" color="blue" mode="date" :input-debounce="500" @update:model-value="updateDate" :masks="masks" />
        </div>

        </div>
      </div>
      <div
        class="grow border-b border-gray-100 p-5 dark:border-gray-700"
      >
        <div class="relative">
          
        <combobox v-model="selectedReagentId" :load-options="loadReagents" @update:modelValue="fetchChartData" />

        </div>
      </div>
      <!-- END Card Header -->

      <!-- Card Body -->
      <div class="grow p-5">
        <!-- Placeholder -->
        <div
          class="flex items-center overflow-x-auto justify-center rounded-xl border-2 border-gray-200 bg-gray-50 py-4 text-gray-400 dark:border-gray-700 dark:bg-gray-800"
        >
        <apexchart v-if="chartData" type="line" height="350" :options="chartOptions" :series="chartData.series" :key="chartKey" class="w-full"></apexchart>

        </div>
      </div>
      <!-- Card Body -->
    </div>
  </form>
  <!-- END Card Headings: Search with Filter -->
  </template>
  
  <script setup>
  import { ref, onMounted, watch } from "vue";
  import axios from "axios";
  import combobox from '@/Components/combobox.vue';
  import datePicker from '@/Components/date-picker.vue'

  const chartData = ref(null);
    const alerts = ref([]);
    const selectedFilter = ref("this_month");
    const selectedReagentId = ref(null);
    const customStartDate = ref(null);
    const alertSensitivity = ref(20); // Default 20%
    const movingAverageLength = ref(3); // Default 3 days
    const chartKey = ref(0);

    const updateDate = (e) => {
        customStartDate.value = e;
        fetchChartData();

        console.log(customStartDate.value);
    }

    const masks = ref({
        modelValue: 'YYYY-MM-DD',
        input: 'YYYY-MM-DD',
        data: 'YYYY-MM-DD',
    });

    const chartOptions = ref({
        chart: { type: "line", height: 350 },
        responsive: [
            {
            breakpoint: 640, // Tailwind's `sm`
            options: {
                chart: { height: 300 },
                legend: { position: 'bottom' },
            },
            },
            {
            breakpoint: 1024, // Tailwind's `lg`
            options: {
                chart: { height: 400 },
            },
            }
        ],
        stroke: { curve: 'smooth', width: 1.5 },
        xaxis: { 
            categories: [],
            labels: {
                rotate: -90
            } 
        },
        colors: ["#f39c12", "#00c6ff"],
        title: { text: "Consumo % Diário de Reagentes" },
        yaxis: {
            labels: {
                formatter: (val) => {
                if (typeof val === 'number' && !isNaN(val)) {
                    return val.toFixed(2);
                }
                return '0.00';
                },
            },
        },
        markers: { size: 3 },
        annotations: {
        xaxis: alerts.value.map(a => ({
            x: a.date,
            borderColor: '#FF4560',
            label: {
            style: { color: '#fff', background: '#FF4560' },
            text: `${a.change > 0 ? '+' : ''}${a.change}%`
            }
        }))
        },
        tooltip: { y: { formatter: (val) => `${val?.toFixed(2)}%` }, x: { format: 'yyyy-MM-dd' } },
        // legend: { position: 'top' },
      });
      
      function loadReagents(query, setOptions) {
            fetch('/iitems/getReagentInventoryItem?q=' + query)
            .then(response => response.json())
            .then(results => {
                setOptions(
                results.map(result => { 
                    return {
                    value: result.id,
                    label: result.name,
                    category_id: result.category_id,
                    };
                })
                );
            });
        }
  
      const fetchChartData = async () => {
        let url = `/reagent-dashboard?filter=${selectedFilter.value}&alert_sensitivity=${alertSensitivity.value}&moving_average_length=${movingAverageLength.value}` + (selectedReagentId.value ? `&reagent_id=${selectedReagentId.value.value}` : '');
        if (selectedFilter.value === "custom" && customStartDate.value) {
          url += `&start_date=${customStartDate.value}`;
        }
  
        const { data } = await axios.get(url);
        chartData.value = data;
        chartOptions.value.xaxis.categories = data.labels;
        alerts.value = data.alerts;

        chartKey.value = Math.random();

      };

      function updateChartTitle(reagentName = null) {
        chartOptions.value.title.text = reagentName
            ? `Consumo % Diário de ${reagentName}`
            : 'Consumo % Diário de Reagentes';
        }

        watch(selectedReagentId, (newReagent) => {
            updateChartTitle(newReagent?.label);
        });  
  
      onMounted(fetchChartData);  
  
  </script>  