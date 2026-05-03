<template>
  <!-- Compact Welcome Banner -->
  <div class="card overflow-hidden">
    <div class="relative bg-gradient-to-r from-primary-900 via-primary-800 to-primary-900 px-6 py-5">
      <!-- Subtle pattern overlay -->
      <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

      <div class="relative flex items-center justify-between">
        <div class="flex items-center gap-4">
          <!-- Avatar -->
          <div class="relative">
            <img
              v-if="auth?.profile_photo_url"
              :src="auth.profile_photo_url"
              :alt="auth.name"
              class="h-14 w-14 rounded-full border-2 border-white/30 shadow-lg object-cover"
            />
            <div
              v-else
              class="flex h-14 w-14 items-center justify-center rounded-full bg-white/15 border-2 border-white/20 shadow-lg backdrop-blur-sm"
            >
              <span class="text-xl font-bold text-white">{{ getInitials(auth?.name) }}</span>
            </div>
            <!-- Online dot -->
            <div class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full bg-green-400 border-2 border-primary-900 shadow-sm"></div>
          </div>

          <!-- Greeting -->
          <div>
            <p class="text-sm font-medium text-white/70">{{ greeting || $t('gestlab.greeting') }}</p>
            <h1 class="text-xl font-bold text-white">{{ auth?.name }}</h1>
            <div v-if="auth?.roles?.length > 0" class="flex flex-wrap gap-1.5 mt-1.5">
              <span
                v-for="role in auth.roles.slice(0, 2)"
                :key="role.id"
                class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-white/80 backdrop-blur-sm"
              >
                {{ role.label }}
              </span>
              <span
                v-if="auth.roles.length > 2"
                class="inline-flex items-center rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-white/80"
              >
                +{{ auth.roles.length - 2 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick info pills (desktop only) -->
        <div class="hidden md:flex items-center gap-3">
          <div class="flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-3 py-2">
            <CalendarIcon class="h-4 w-4 text-white/60" />
            <div>
              <p class="text-[10px] uppercase tracking-wider text-white/50 font-medium">{{ $t('gestlab.general.labels.users.last_login') }}</p>
              <p class="text-xs font-semibold text-white">{{ formatLastLogin(auth?.last_login_at) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-3 py-2">
            <BuildingOfficeIcon class="h-4 w-4 text-white/60" />
            <div>
              <p class="text-[10px] uppercase tracking-wider text-white/50 font-medium">{{ $t('gestlab.general.labels.users.department_id') }}</p>
              <p class="text-xs font-semibold text-white truncate max-w-[120px]">{{ getPrimaryDepartment(auth?.departments) }}</p>
            </div>
          </div>
          <Link
            :href="route('users.edit', {id: auth.id})"
            as="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-white/15 hover:bg-white/25 px-3 py-2 text-xs font-medium text-white transition-colors duration-150"
          >
            <PencilSquareIcon class="h-3.5 w-3.5" />
            {{ $t('gestlab.general.buttons.edit') }}
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  CalendarIcon,
  BuildingOfficeIcon,
  PencilSquareIcon
} from '@heroicons/vue/24/outline';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
  greeting: { type: String, default: null },
  auth: Object
});

const getInitials = (name) => {
  if (!name) return '';
  return name.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2);
};

const formatLastLogin = (dateString) => {
  if (!dateString) return trans('gestlab.general.labels.users.never_logged_in');
  const date = new Date(dateString);
  const now = new Date();
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return trans('gestlab.general.labels.users.today');
  if (diffDays === 1) return trans('gestlab.general.labels.users.yesterday');
  if (diffDays < 7) return trans('gestlab.general.labels.users.days_ago', { days: diffDays });
  return date.toLocaleDateString();
};

const getPrimaryDepartment = (departments) => {
  if (!departments || departments.length === 0) return trans('gestlab.general.labels.users.no_department');
  return departments[0]?.label || departments[0]?.name || '';
};
</script>