<template>
  <div class="quality-certificate-show space-y-6" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 dark:bg-emerald-400 dark:text-slate-950">
            <CheckBadgeIcon class="h-6 w-6" />
          </div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
            {{ $t('gestlab.general.labels.quality_certificates.page_title') }}
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.quality_certificates.certificate') }}
            <span class="font-semibold text-emerald-700 dark:text-emerald-300">#{{ props.record.data?.code }}</span>
          </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
          <!-- Status Badge -->
          <div :class="[
            'inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset',
            props.record.data?.validated_at
              ? 'bg-green-100 text-green-800 ring-green-700/10 dark:bg-green-500/15 dark:text-green-200 dark:ring-green-400/20'
              : 'bg-yellow-100 text-yellow-800 ring-yellow-700/10 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/20'
          ]">
            <div :class="[
              'h-2 w-2 rounded-full',
              props.record.data?.validated_at ? 'bg-green-600' : 'bg-yellow-600'
            ]"></div>
            {{ props.record.data?.validated_at ? 'Validado' : 'Pendente' }}
          </div>
          
          <!-- Approve Button -->
          <button
            v-if="!props.record.data?.validated_at && hasPermission('validate_quality_certificates')"
            @click="approve"
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-700 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-emerald-600 hover:to-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition-all duration-200 dark:focus:ring-emerald-400 dark:focus:ring-offset-slate-950"
          >
            <CheckBadgeIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.quality_certificates.approve_certificate') }}
          </button>
          
          <!-- View PDF Button -->
          <button
            v-if="hasPermission('validate_quality_certificates')"
            @click="viewPDF"
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
          >
            <DocumentMagnifyingGlassIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.quality_certificates.view_pdf') }}
          </button>
        </div>
      </div>
    </div>

    <!-- CERTIFICATE DETAILS CARD -->
    <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <!-- Card Header -->
      <div class="bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-6 py-4 dark:from-[#07110f] dark:to-[rgb(var(--primary-900-rgb))]">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <DocumentTextIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.quality_certificates.certificate_details') }}
        </h2>
      </div>
      
      <!-- Card Content -->
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Certificate Number -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              {{ $t('gestlab.general.labels.quality_certificates.code') }}
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
              {{ props.record.data?.code || '-' }}
            </div>
          </div>
          
          <!-- Customer Information -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              {{ $t('gestlab.general.labels.quality_certificates.customer_id') }}
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
              <div class="font-semibold">{{ props.record.data?.customer || '-' }}</div>
              <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ props.record.data?.warehouse || '-' }}</div>
            </div>
          </div>
          
          <!-- Created By -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              {{ $t('gestlab.general.labels.quality_certificates.user_id') }}
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
              <div class="flex items-center gap-2">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))]">
                  <span class="text-xs font-semibold text-white uppercase">
                    {{ getInitials(props.record.data?.user?.name) }}
                  </span>
                </div>
                <span>{{ props.record.data?.user?.name || '-' }}</span>
              </div>
            </div>
          </div>
          
          <!-- Approval Status -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Status de Validação
            </label>
            <div :class="[
              'text-sm rounded-2xl px-3 py-2.5 border font-medium',
              props.record.data?.validated_at
                ? 'bg-green-50 text-green-800 border-green-200 dark:bg-green-500/10 dark:text-green-200 dark:border-green-500/20'
                : 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-amber-500/10 dark:text-amber-200 dark:border-amber-500/20'
            ]">
              <div class="flex items-center gap-2">
                <div :class="[
                  'h-2 w-2 rounded-full',
                  props.record.data?.validated_at ? 'bg-green-600' : 'bg-yellow-600'
                ]"></div>
                {{ props.record.data?.validated_at ? 'Validado' : 'Pendente de Validação' }}
              </div>
              <div v-if="props.record.data?.validated_at" class="mt-1 text-xs text-green-700 dark:text-green-300">
                Validado em: {{ formatDate(props.record.data.validated_at) }}
              </div>
            </div>
          </div>
          
          <!-- Creation Date -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Data de Criação
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
              {{ formatDate(props.record.data?.created_at) }}
            </div>
          </div>
          
          <!-- Last Updated -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Última Atualização
            </label>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
              {{ formatDate(props.record.data?.updated_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DOCUMENTS CARD -->
    <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <!-- Card Header -->
      <div class="bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-6 py-4 dark:from-[#07110f] dark:to-[rgb(var(--primary-900-rgb))]">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <DocumentIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.quality_certificates.documents') }}
        </h2>
      </div>
      
      <!-- Documents List -->
      <div class="p-6">
        <div class="space-y-4">
          <!-- PDF Document -->
          <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 p-4 transition-colors duration-150 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-[rgb(var(--primary-50-rgb)/0.5)] dark:border-slate-700 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.1)] lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 dark:bg-red-500/10">
                <DocumentIcon class="h-5 w-5 text-red-600" />
              </div>
              <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                  Certificado de Qualidade
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  Documento PDF gerado automaticamente
                </p>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <button
                @click="viewPDF"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:from-[rgb(var(--primary-800-rgb))] hover:to-[rgb(var(--primary-600-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
              >
                <DocumentMagnifyingGlassIcon class="h-3 w-3" />
                {{ $t('gestlab.general.buttons.view') }}
              </button>
              
              <a
                :href="props.record.data?.links?.pdf_path"
                target="_blank"
                download
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
              >
                <ArrowDownTrayIcon class="h-3 w-3" />
                {{ $t('gestlab.general.buttons.download') }}
              </a>
            </div>
          </div>
          
          <!-- Additional Documents (if any) -->
          <div
            v-for="(doc, index) in additionalDocuments"
            :key="index"
            class="flex flex-col gap-4 rounded-2xl border border-slate-200 p-4 transition-colors duration-150 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-[rgb(var(--primary-50-rgb)/0.5)] dark:border-slate-700 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.1)] lg:flex-row lg:items-center lg:justify-between"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                <DocumentTextIcon class="h-5 w-5 text-gray-600" />
              </div>
              <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ doc.name }}
                </h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {{ doc.description }}
                </p>
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <a
                :href="doc.url"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
              >
                <EyeIcon class="h-3 w-3" />
                {{ $t('gestlab.general.buttons.view') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ACTIONS CARD -->
    <div class="rounded-[26px] border border-slate-200 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
        <BoltIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-300-rgb))]" />
        {{ $t('gestlab.general.labels.quality_certificates.actions') }}
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        
        <!-- Edit Certificate -->
        <button
          v-if="hasPermission('edit_qualitycertificate') && !props.record.data?.validated_at"
          @click="editCertificate"
          class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
        >
          <PencilIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.edit') }}
        </button>
        <!-- Generate New Version -->
        <button
          @click="generateNewVersion"
          class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-200 hover:border-[rgb(var(--primary-700-rgb))] hover:bg-slate-50 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-700-rgb))] focus:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-[rgb(var(--primary-300-rgb))] dark:hover:bg-slate-800 dark:hover:text-[rgb(var(--primary-100-rgb))] dark:focus:ring-[rgb(var(--primary-300-rgb))] dark:focus:ring-offset-slate-950"
        >
          <ArrowPathRoundedSquareIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.quality_certificates.generate_new_version') }}
        </button>
      </div>
    </div>

    <!-- HISTORY CARD -->
    <div v-if="activityHistory.length > 0" class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
      <!-- Card Header -->
      <div class="bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))] px-6 py-4 dark:from-[#07110f] dark:to-[rgb(var(--primary-900-rgb))]">
        <h2 class="text-lg font-semibold text-white flex items-center gap-2">
          <ClockIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.quality_certificates.activity_history') }}
        </h2>
      </div>
      
      <!-- History List -->
      <div class="p-6">
        <div class="space-y-4">
          <div
            v-for="(activity, index) in activityHistory"
            :key="index"
            class="flex items-start gap-4"
          >
            <!-- Timeline Line -->
            <div class="relative">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-[rgb(var(--primary-900-rgb))] to-[rgb(var(--primary-700-rgb))]">
                <component :is="getActivityIcon(activity.type)" class="h-3 w-3 text-white" />
              </div>
              <div v-if="index !== activityHistory.length - 1" class="absolute left-1/2 top-6 h-full w-0.5 -translate-x-1/2 bg-slate-200 dark:bg-slate-800"></div>
            </div>
            
            <!-- Activity Content -->
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                  {{ activity.title }}
                </h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatRelativeTime(activity.timestamp) }}
                </span>
              </div>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                {{ activity.description }}
              </p>
              <div v-if="activity.user" class="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <UserIcon class="h-3 w-3" />
                Por: {{ activity.user }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref, computed } from "vue";
import { usePermission } from "@/Composables/usePermissions";
import { 
  ArrowPathRoundedSquareIcon, 
  DocumentIcon, 
  DocumentTextIcon, 
  PencilIcon, 
  TrashIcon, 
  EnvelopeIcon, 
  CheckBadgeIcon, 
  DocumentMagnifyingGlassIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  BoltIcon,
  ClockIcon,
  UserIcon
} from "@heroicons/vue/24/outline";
import { router } from "@inertiajs/vue3";

const { hasRole, hasPermission } = usePermission();
const props = defineProps({
  record: Object,
});

defineOptions({
  layout: Layout
});

// Helper Functions
const getInitials = (name) => {
  if (!name) return '??';
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .slice(0, 2)
    .join('')
    .toUpperCase();
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatRelativeTime = (dateString) => {
  if (!dateString) return '-';
  const now = new Date();
  const date = new Date(dateString);
  const diffTime = Math.abs(now - date);
  const diffMinutes = Math.floor(diffTime / (1000 * 60));
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffMinutes < 60) return `${diffMinutes} min atrás`;
  if (diffHours < 24) return `${diffHours} h atrás`;
  if (diffDays === 1) return 'Ontem';
  if (diffDays < 7) return `${diffDays} dias atrás`;
  return formatDate(dateString);
};

const getActivityIcon = (type) => {
  const iconMap = {
    created: DocumentTextIcon,
    validated: CheckBadgeIcon,
    updated: PencilIcon,
    viewed: EyeIcon,
    sent: EnvelopeIcon,
    verified: DocumentMagnifyingGlassIcon
  };
  return iconMap[type] || ClockIcon;
};

// Computed Properties
const additionalDocuments = computed(() => {
  const docs = [];
  
  if (props.record.data?.links?.additional_documents) {
    // Add additional documents here
  }
  
  return docs;
});

const activityHistory = computed(() => {
  const history = [];
  
  // Add creation activity
  if (props.record.data?.created_at) {
    history.push({
      type: 'created',
      title: 'Certificado criado',
      description: 'Certificado de qualidade criado no sistema',
      timestamp: props.record.data.created_at,
      user: props.record.data.user?.name
    });
  }
  
  // Add approval activity
  if (props.record.data?.validated_at) {
    history.push({
      type: 'validated',
      title: 'Certificado validado',
      description: 'Certificado verificado e validado',
      timestamp: props.record.data.validated_at,
      user: props.record.data?.validated_by_user || 'Sistema'
    });
  }
  
  // Add update activity
  if (props.record.data?.updated_at && props.record.data.updated_at !== props.record.data.created_at) {
    history.push({
      type: 'updated',
      title: 'Certificado atualizado',
      description: 'Informações do certificado atualizadas',
      timestamp: props.record.data.updated_at,
      user: 'Sistema'
    });
  }
  
  return history.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
});

// Actions
const verify = () => {
  router.get(route('qualitycertificates.getVerify', { id: props.record.data.id }), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      // Success feedback could be added here
    },
  });
};

const approve = () => {
  router.get(route('qualitycertificates.getApprove', { id: props.record.data.id }), {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      // Success feedback could be added here
    },
  });
};

const viewPDF = () => {
  window.open(route('qualitycertificates.getPDF', { id: props.record.data.id }), '_blank');
};

const editCertificate = () => {
  router.get(route('qualitycertificates.edit', { certificate: props.record.data.id }), {
    preserveScroll: true,
    preserveState: true,
  });
};

const sendCertificate = () => {
  router.post(route('qualitycertificates.send', { id: props.record.data.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Success feedback could be added here
    },
  });
};

// const generateNewVersion = () => {
//   router.post(route('qualitycertificates.regenerate', { id: props.record.data.id }), {}, {
//     preserveScroll: true,
//     onSuccess: () => {
//       // Success feedback could be added here
//     },
//   });
// };

const generateNewVersion = () => {
  router.get(route('qualitycertificates.iso-revisions.index', { certificate: props.record.data.id }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Success feedback could be added here
    },
  });
};
</script>

<style scoped>
.quality-certificate-show :is(button, a) {
  transition: all 0.2s ease-in-out;
}

.quality-certificate-show :is(button, a):focus-visible {
  outline: 2px solid rgb(var(--primary-700-rgb));
  outline-offset: 2px;
}

.quality-certificate-show button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
