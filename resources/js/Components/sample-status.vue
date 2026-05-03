<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Progresso da Amostra</h3>
        <p class="mt-1 text-sm text-gray-500">Acompanhe o estado atual da análise</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">Última atualização:</span>
        <span class="text-sm font-medium text-blue-900">{{ formatDate(latestUpdate) }}</span>
      </div>
    </div>

    <!-- ANALYSIS STATUS BAR -->
    <div class="bg-gray-50 rounded-lg p-4">
      <div class="mb-4">
        <div class="flex justify-between text-sm mb-2">
          <span class="text-gray-600">Progresso Geral</span>
          <span class="font-semibold text-blue-900">{{ overallProgress }}%</span>
        </div>
        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-blue-900 to-blue-800 transition-all duration-500"
            :style="{ width: overallProgress + '%' }"
          ></div>
        </div>
      </div>

      <!-- STATUS LEGEND -->
      <div class="flex flex-wrap gap-4 text-xs">
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full bg-green-500"></div>
          <span class="text-gray-600">Completo</span>
          <span class="font-semibold">{{ getCountByStatus('complete') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
          <span class="text-gray-600">Em Progresso</span>
          <span class="font-semibold">{{ getCountByStatus('current') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full bg-gray-300"></div>
          <span class="text-gray-600">Pendente</span>
          <span class="font-semibold">{{ getCountByStatus('pending') }}</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full bg-red-500"></div>
          <span class="text-gray-600">Critico</span>
          <span class="font-semibold">{{ getCriticalCount() }}</span>
        </div>
      </div>
    </div>

    <!-- TIMELINE VIEW -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <!-- STEP CARDS -->
      <div 
        v-for="(step, index) in analysisSteps" 
        :key="step.name"
        class="relative"
      >
        <!-- CONNECTION LINE -->
        <div 
          v-if="index < analysisSteps.length - 1"
          class="hidden md:block absolute top-1/2 left-full w-4 h-0.5"
          :class="[
            'transform -translate-y-1/2',
            step.status === 'complete' && analysisSteps[index + 1].status === 'complete' 
              ? 'bg-green-500' 
              : 'bg-gray-300'
          ]"
        ></div>

        <!-- STEP CARD -->
        <div 
          :class="[
            'relative rounded-lg border p-4 transition-all duration-200',
            getStepCardClasses(step)
          ]"
        >
          <!-- STEP NUMBER -->
          <div class="absolute -top-2 -right-2">
            <div :class="[
              'flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
              getStepBadgeClasses(step)
            ]">
              {{ index + 1 }}
            </div>
          </div>

          <!-- STEP ICON -->
          <div class="mb-3">
            <div :class="[
              'flex h-12 w-12 items-center justify-center rounded-lg',
              getStepIconBgClasses(step)
            ]">
              <component 
                :is="step.icon" 
                :class="[
                  'h-6 w-6',
                  getStepIconColorClasses(step)
                ]" 
              />
            </div>
          </div>

          <!-- STEP INFO -->
          <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-1">{{ step.name }}</h4>
            <p class="text-xs text-gray-500 mb-2">{{ step.description }}</p>
            
            <!-- STATUS BADGE -->
            <div class="mb-3">
              <span :class="[
                'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                getStatusBadgeClasses(step.status)
              ]">
                {{ getStatusLabel(step.status) }}
              </span>
            </div>

            <!-- STEP DETAILS -->
            <div v-if="step.details" class="space-y-1 text-xs">
              <div class="flex justify-between">
                <span class="text-gray-500">Início:</span>
                <span class="font-medium">{{ formatDate(step.details.startDate) }}</span>
              </div>
              <div class="flex justify-between" v-if="step.details.endDate">
                <span class="text-gray-500">Término:</span>
                <span class="font-medium">{{ formatDate(step.details.endDate) }}</span>
              </div>
              <div class="flex justify-between" v-if="step.details.duration">
                <span class="text-gray-500">Duração:</span>
                <span class="font-medium">{{ step.details.duration }}</span>
              </div>
            </div>

            <!-- ACTION BUTTON -->
            <button 
              v-if="step.status === 'current' && step.action"
              @click="handleStepAction(step)"
              :class="[
                'mt-3 w-full inline-flex justify-center items-center gap-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors duration-200',
                getActionButtonClasses(step)
              ]"
            >
              <PlayIcon class="h-3 w-3" />
              {{ step.action.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- DETAILED ANALYSIS STATUS -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-4 py-3">
        <h4 class="text-sm font-semibold text-gray-900">Análises Detalhadas</h4>
      </div>
      
      <div class="divide-y divide-gray-200">
        <div 
          v-for="(analysis, index) in detailedAnalyses" 
          :key="index"
          class="px-4 py-3 hover:bg-gray-50 transition-colors duration-150"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div :class="[
                'flex h-8 w-8 items-center justify-center rounded-full',
                getAnalysisStatusBgClasses(analysis.status)
              ]">
                <BeakerIcon :class="[
                  'h-4 w-4',
                  getAnalysisStatusIconClasses(analysis.status)
                ]" />
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900">{{ analysis.profile }}</div>
                <div class="text-xs text-gray-500">{{ analysis.parameter_count }} parâmetros</div>
              </div>
            </div>
            
            <div class="flex items-center gap-4">
              <div class="text-right">
                <div class="text-xs text-gray-500">Progresso</div>
                <div class="text-sm font-semibold text-blue-900">{{ analysis.progress }}%</div>
              </div>
              <div>
                <span :class="[
                  'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                  getAnalysisStatusBadgeClasses(analysis.status)
                ]">
                  {{ getAnalysisStatusLabel(analysis.status) }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- PROGRESS BAR FOR EACH ANALYSIS -->
          <div class="mt-3">
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-500">{{ analysis.completed_params }}/{{ analysis.total_params }} completos</span>
              <span class="font-medium text-gray-700">{{ formatDate(analysis.updated_at) }}</span>
            </div>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div 
                class="h-full transition-all duration-500"
                :class="getAnalysisProgressColor(analysis.progress)"
                :style="{ width: analysis.progress + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ALERTS & NOTIFICATIONS -->
    <div v-if="alerts.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-gray-900">Alertas & Notificações</h4>
        <span class="text-xs text-gray-500">{{ alerts.length }} alertas</span>
      </div>
      
      <div class="space-y-2">
        <div 
          v-for="(alert, index) in alerts" 
          :key="index"
          :class="[
            'rounded-lg border p-3 flex items-start gap-3',
            getAlertClasses(alert.level)
          ]"
        >
          <div :class="[
            'flex h-6 w-6 items-center justify-center rounded-full',
            getAlertIconBgClasses(alert.level)
          ]">
            <ExclamationTriangleIcon :class="[
              'h-3 w-3',
              getAlertIconColorClasses(alert.level)
            ]" />
          </div>
          <div class="flex-1">
            <div class="text-sm font-medium text-gray-900">{{ alert.title }}</div>
            <div class="text-xs text-gray-600 mt-1">{{ alert.message }}</div>
            <div class="text-xs text-gray-500 mt-2">{{ formatDate(alert.timestamp) }}</div>
          </div>
          <button 
            @click="handleAlertAction(alert)"
            class="text-xs font-medium text-blue-900 hover:text-blue-800"
          >
            {{ alert.action || 'Resolver' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  CheckIcon,
  PlayIcon,
  BeakerIcon,
  CircleStackIcon,
  InboxIcon,
  ClockIcon,
  CheckCircleIcon,
  DocumentCheckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/solid';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  collectionId: {
    type: [String, Number],
    required: true
  },
  steps: {
    type: Array,
    default: () => []
  }
});

// Default steps if none provided
const defaultSteps = [
  {
    name: 'Colheita',
    description: 'Coleta da amostra',
    status: 'complete',
    icon: CircleStackIcon,
    details: {
      startDate: new Date(),
      endDate: new Date(),
      duration: '2h'
    }
  },
  {
    name: 'Recepção',
    description: 'Registo no laboratório',
    status: 'complete',
    icon: InboxIcon,
    details: {
      startDate: new Date(),
      endDate: new Date(),
      duration: '1h'
    }
  },
  {
    name: 'Análise',
    description: 'Processamento da amostra',
    status: 'current',
    icon: BeakerIcon,
    action: {
      label: 'Iniciar Análise',
      handler: () => console.log('Start analysis')
    }
  },
  {
    name: 'Verificação',
    description: 'Validação de resultados',
    status: 'pending',
    icon: CheckCircleIcon
  },
  {
    name: 'Aprovação',
    description: 'Aprovação final',
    status: 'pending',
    icon: DocumentCheckIcon
  }
];

const analysisSteps = ref(props.steps.length > 0 ? props.steps : defaultSteps);
const detailedAnalyses = ref([]);
const alerts = ref([]);
const latestUpdate = ref(new Date());

// Fetch analysis data from database
const fetchAnalysisData = async () => {
  try {
    // In a real app, you would fetch this data from your API
    // Based on your database schema, we can fetch:
    // 1. Analysis records from the `analysis` table for this collection
    // 2. Results from the `results` table
    // 3. Samples from the `samples` table
    
    // Mock data for demonstration
    // detailedAnalyses.value = [
    //   {
    //     id: 1,
    //     profile: 'Análise Microbiológica',
    //     parameter_count: 8,
    //     total_params: 8,
    //     completed_params: 6,
    //     progress: 75,
    //     status: 'in_progress',
    //     updated_at: new Date(Date.now() - 3600000), // 1 hour ago
    //     department: 'Microbiologia'
    //   },
    //   {
    //     id: 2,
    //     profile: 'Análise Físico-Química',
    //     parameter_count: 12,
    //     total_params: 12,
    //     completed_params: 12,
    //     progress: 100,
    //     status: 'completed',
    //     updated_at: new Date(Date.now() - 7200000), // 2 hours ago
    //     department: 'Química'
    //   },
    //   {
    //     id: 3,
    //     profile: 'Análise Sensorial',
    //     parameter_count: 5,
    //     total_params: 5,
    //     completed_params: 0,
    //     progress: 0,
    //     status: 'pending',
    //     updated_at: new Date(Date.now() - 10800000), // 3 hours ago
    //     department: 'Sensorial'
    //   }
    // ];

    fetch('/labcodes/getSampleStatus?id=' + props.collectionId)
    .then(response => response.json())
    .then(results => {
        detailedAnalyses.value = results;
    });

    // Fetch alerts based on analysis status
    alerts.value = [
      {
        id: 1,
        level: 'warning',
        title: 'Amostra em atraso',
        message: 'A análise de sensorial está pendente há mais de 3 horas',
        timestamp: new Date(Date.now() - 10800000),
        action: 'Priorizar'
      },
      {
        id: 2,
        level: 'info',
        title: 'Verificação necessária',
        message: 'A análise microbiológica requer verificação',
        timestamp: new Date(Date.now() - 1800000),
        action: 'Verificar'
      }
    ];

    // Update latest update timestamp
    latestUpdate.value = new Date();

  } catch (error) {
    console.error('Error fetching analysis data:', error);
  }
};

// Computed properties
const overallProgress = computed(() => {
  if (detailedAnalyses.value.length === 0) return 0;
  const totalProgress = detailedAnalyses.value.reduce((sum, analysis) => sum + analysis.progress, 0);
  return Math.round(totalProgress / detailedAnalyses.value.length);
});

// Helper methods
const getCountByStatus = (status) => {
  return analysisSteps.value.filter(step => step.status === status).length;
};

const getCriticalCount = () => {
  // Count analyses with critical issues (e.g., behind schedule, failed parameters)
  return detailedAnalyses.value.filter(a => a.status === 'critical').length;
};

const getStepCardClasses = (step) => {
  const baseClasses = 'bg-white border-gray-200';
  const statusClasses = {
    'complete': 'border-green-500 bg-green-50/30',
    'current': 'border-blue-900 bg-blue-50/30 shadow-sm',
    'pending': 'border-gray-200'
  };
  return `${baseClasses} ${statusClasses[step.status] || ''}`;
};

const getStepBadgeClasses = (step) => {
  const baseClasses = 'text-white';
  const statusClasses = {
    'complete': 'bg-green-500',
    'current': 'bg-blue-900',
    'pending': 'bg-gray-400'
  };
  return `${baseClasses} ${statusClasses[step.status] || 'bg-gray-400'}`;
};

const getStepIconBgClasses = (step) => {
  const statusClasses = {
    'complete': 'bg-green-100',
    'current': 'bg-blue-100',
    'pending': 'bg-gray-100'
  };
  return statusClasses[step.status] || 'bg-gray-100';
};

const getStepIconColorClasses = (step) => {
  const statusClasses = {
    'complete': 'text-green-700',
    'current': 'text-blue-900',
    'pending': 'text-gray-400'
  };
  return statusClasses[step.status] || 'text-gray-400';
};

const getStatusBadgeClasses = (status) => {
  const statusClasses = {
    'complete': 'bg-green-100 text-green-800',
    'current': 'bg-blue-100 text-blue-800',
    'pending': 'bg-gray-100 text-gray-800'
  };
  return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
  const labels = {
    'complete': 'Completo',
    'current': 'Em Progresso',
    'pending': 'Pendente'
  };
  return labels[status] || status;
};

const getAnalysisStatusBgClasses = (status) => {
  const statusClasses = {
    'completed': 'bg-green-100',
    'in_progress': 'bg-blue-100',
    'pending': 'bg-gray-100',
    'critical': 'bg-red-100'
  };
  return statusClasses[status] || 'bg-gray-100';
};

const getAnalysisStatusIconClasses = (status) => {
  const statusClasses = {
    'completed': 'text-green-700',
    'in_progress': 'text-blue-900',
    'pending': 'text-gray-400',
    'critical': 'text-red-700'
  };
  return statusClasses[status] || 'text-gray-400';
};

const getAnalysisStatusBadgeClasses = (status) => {
  const statusClasses = {
    'completed': 'bg-green-100 text-green-800',
    'in_progress': 'bg-blue-100 text-blue-800',
    'pending': 'bg-gray-100 text-gray-800',
    'critical': 'bg-red-100 text-red-800'
  };
  return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const getAnalysisStatusLabel = (status) => {
  const labels = {
    'completed': 'Completo',
    'in_progress': 'Em Progresso',
    'pending': 'Pendente',
    'critical': 'Critico'
  };
  return labels[status] || status;
};

const getAnalysisProgressColor = (progress) => {
  if (progress === 100) return 'bg-green-500';
  if (progress >= 50) return 'bg-blue-900';
  if (progress > 0) return 'bg-yellow-500';
  return 'bg-gray-300';
};

const getAlertClasses = (level) => {
  const levelClasses = {
    'critical': 'border-red-300 bg-red-50',
    'warning': 'border-yellow-300 bg-yellow-50',
    'info': 'border-blue-300 bg-blue-50'
  };
  return levelClasses[level] || 'border-gray-300 bg-gray-50';
};

const getAlertIconBgClasses = (level) => {
  const levelClasses = {
    'critical': 'bg-red-100',
    'warning': 'bg-yellow-100',
    'info': 'bg-blue-100'
  };
  return levelClasses[level] || 'bg-gray-100';
};

const getAlertIconColorClasses = (level) => {
  const levelClasses = {
    'critical': 'text-red-700',
    'warning': 'text-yellow-700',
    'info': 'text-blue-700'
  };
  return levelClasses[level] || 'text-gray-700';
};

const getActionButtonClasses = (step) => {
  const baseClasses = 'bg-blue-900 text-white hover:bg-blue-800';
  return baseClasses;
};

// Event handlers
const handleStepAction = (step) => {
  if (step.action && step.action.handler) {
    step.action.handler();
  }
};

const handleAlertAction = (alert) => {
  console.log('Handle alert action:', alert);
  // Implement alert action logic
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleTimeString('pt-PT', {
    hour: '2-digit',
    minute: '2-digit',
    day: 'numeric',
    month: 'short'
  });
};

// Lifecycle
onMounted(() => {
  fetchAnalysisData();
  
  // Poll for updates every 30 seconds
  setInterval(() => {
    fetchAnalysisData();
  }, 30000);
});
</script>