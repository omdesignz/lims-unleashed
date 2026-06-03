<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <PlusIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.admin.notifications.create.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.admin.notifications.create.subtitle') }}
            <span class="font-semibold text-blue-900">
              {{ estimatedRecipients }}
            </span>
            {{ $t('gestlab.general.labels.admin.notifications.create.users_will_receive') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('admin.notifications.index')"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.create.buttons.cancel') }}
          </Link>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- NOTIFICATION CONTENT CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <BellIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.create.content.title') }}
          </h2>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Template Section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
              <SparklesIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.create.content.templates.label') }}
            </label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <button
                v-for="(template, key) in defaultTemplates"
                :key="key"
                type="button"
                @click="applyTemplate(template)"
                class="rounded-lg border border-gray-300 bg-white p-4 text-left hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 group"
              >
                <div class="flex items-center gap-2 mb-3">
                  <span :class="[
                    'inline-flex h-2 w-2 rounded-full',
                    getTypeColor(template.type)
                  ]"></span>
                  <span class="text-xs font-medium text-gray-600 uppercase">{{ template.type }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2 group-hover:text-blue-900 transition-colors duration-200">
                  {{ template.title }}
                </h3>
                <p class="text-xs text-gray-500 line-clamp-2">{{ template.message }}</p>
              </button>
            </div>
          </div>

          <!-- Title Field -->
          <div class="space-y-2">
            <label for="title" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <HashtagIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.title.label') }}
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              id="title"
              required
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
              :placeholder="$t('gestlab.general.labels.admin.notifications.create.content.fields.title.placeholder')"
            />
            <p v-if="form.errors.title" class="text-xs text-red-600">
              {{ form.errors.title }}
            </p>
          </div>

          <!-- Message Field -->
          <div class="space-y-2">
            <label for="message" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <ChatBubbleBottomCenterTextIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.message.label') }}
              <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.message"
              id="message"
              rows="4"
              required
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
              :placeholder="$t('gestlab.general.labels.admin.notifications.create.content.fields.message.placeholder')"
            ></textarea>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
              <div>
                {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.message.length') }}: 
                <strong>{{ messageLength }}</strong>
              </div>
              <div>
                {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.message.variables') }}: 
                <code class="bg-gray-100 px-1 py-0.5 rounded ml-1">{name}</code>, 
                <code class="bg-gray-100 px-1 py-0.5 rounded ml-1">{email}</code>
              </div>
            </div>
            <p v-if="form.errors.message" class="text-xs text-red-600">
              {{ form.errors.message }}
            </p>
          </div>

          <!-- Type & Priority Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label for="type" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <TagIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.type.label') }}
                <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.type"
                id="type"
                required
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
              >
                <option v-for="(typeData, typeKey) in notificationTypes" 
                        :key="typeKey" 
                        :value="typeKey"
                        :class="getTypeOptionClass(typeKey)">
                  {{ typeData.label }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <label for="priority" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <ExclamationTriangleIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.general.labels.admin.notifications.create.content.fields.priority.label') }}
                <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.priority"
                id="priority"
                required
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
              >
                <option value="low">{{ $t('gestlab.general.labels.admin.notifications.create.content.fields.priority.options.low') }}</option>
                <option value="normal">{{ $t('gestlab.general.labels.admin.notifications.create.content.fields.priority.options.normal') }}</option>
                <option value="high">{{ $t('gestlab.general.labels.admin.notifications.create.content.fields.priority.options.high') }}</option>
                <option value="urgent">{{ $t('gestlab.general.labels.admin.notifications.create.content.fields.priority.options.urgent') }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- RECIPIENTS CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <UsersIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.create.recipients.title') }}
          </h2>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Recipient Type Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3 flex items-center gap-1">
              <UserGroupIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.create.recipients.type.label') }}
              <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <label
                v-for="option in recipientOptions"
                :key="option.value"
                :class="[
                  'relative flex cursor-pointer rounded-lg border p-4 focus:outline-none transition-all duration-200',
                  form.recipient_type === option.value
                    ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500'
                    : 'border-gray-300 bg-white hover:border-blue-300 hover:bg-gray-50'
                ]"
              >
                <input
                  v-model="form.recipient_type"
                  type="radio"
                  :value="option.value"
                  class="sr-only"
                />
                <div class="flex w-full items-center justify-between">
                  <div class="flex items-center">
                    <div class="text-sm">
                      <p class="font-medium text-gray-900">{{ option.label }}</p>
                      <p class="text-gray-500 mt-1">{{ option.description }}</p>
                    </div>
                  </div>
                  <div v-if="option.count" class="flex-shrink-0 text-sm text-gray-500 ml-4">
                    {{ option.count }} {{ $t('gestlab.general.labels.admin.notifications.create.recipients.users') }}
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Specific Users Selection -->
          <div v-if="form.recipient_type === 'specific'" class="space-y-4">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                <UserIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.general.labels.admin.notifications.create.recipients.specific.label') }}
                <span class="text-red-500">*</span>
              </label>
              <button
                type="button"
                @click="toggleSelectAll"
                class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200"
              >
                {{ isAllSelected 
                    ? $t('gestlab.general.labels.admin.notifications.create.recipients.specific.deselect_all') 
                    : $t('gestlab.general.labels.admin.notifications.create.recipients.specific.select_all') 
                }}
              </button>
            </div>
            
            <div class="border border-gray-200 rounded-lg overflow-hidden max-h-80 overflow-y-auto">
              <div class="divide-y divide-gray-200">
                <label
                  v-for="user in users"
                  :key="user.id"
                  :class="[
                    'flex items-center justify-between p-4 hover:bg-gray-50 transition-colors duration-150 cursor-pointer',
                    form.recipients.includes(user.id) ? 'bg-blue-50' : 'bg-white'
                  ]"
                >
                  <div class="flex items-center gap-3">
                    <input
                      v-model="form.recipients"
                      type="checkbox"
                      :value="user.id"
                      class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                        <UserIcon class="h-5 w-5 text-blue-900" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                          {{ $t('gestlab.general.labels.admin.notifications.create.recipients.specific.registered') }}: 
                          {{ formatDate(user.created_at) }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-medium text-gray-900">{{ user.unread_count }}</span>
                    <p class="text-xs text-gray-500">
                      {{ $t('gestlab.general.labels.admin.notifications.create.recipients.specific.unread') }}
                    </p>
                  </div>
                </label>
              </div>
            </div>
            
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">
                {{ $t('gestlab.general.labels.admin.notifications.create.recipients.specific.selected') }}: 
                <span class="font-semibold text-blue-900">{{ form.recipients.length }}</span>
              </span>
              <span class="text-gray-500">
                {{ $t('gestlab.general.labels.admin.notifications.create.recipients.specific.total') }}: {{ users.length }}
              </span>
            </div>
            
            <p v-if="form.errors.recipients" class="text-xs text-red-600">
              {{ form.errors.recipients }}
            </p>
          </div>

          <!-- User Groups Selection -->
          <div v-if="form.recipient_type === 'group'" class="space-y-4">
            <label for="group" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
              <UserGroupIcon class="h-4 w-4 text-blue-900" />
              {{ $t('gestlab.general.labels.admin.notifications.create.recipients.group.label') }}
              <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.group"
              id="group"
              required
              class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
            >
              <option v-for="group in userGroups" 
                      :key="group.id" 
                      :value="group.id"
                      class="py-2">
                {{ group.name }} 
                <span class="text-gray-500">({{ group.count }} {{ $t('gestlab.general.labels.admin.notifications.create.recipients.group.users') }})</span>
                <span class="text-gray-400 text-xs ml-2">- {{ group.description }}</span>
              </option>
            </select>
            
            <div v-if="selectedGroup" class="rounded-lg bg-blue-50 p-4">
              <div class="flex items-center gap-3">
                <InformationCircleIcon class="h-5 w-5 text-blue-600 flex-shrink-0" />
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ selectedGroup.name }} ({{ selectedGroup.count }} {{ $t('gestlab.general.labels.admin.notifications.create.recipients.group.users') }})
                  </p>
                  <p class="text-sm text-gray-600 mt-1">{{ selectedGroup.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Recipient Preview -->
          <div class="rounded-lg bg-gradient-to-r from-blue-50 to-white border border-blue-200 p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                  <BellAlertIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.admin.notifications.create.recipients.preview.title') }}
                  </p>
                  <p class="text-sm text-gray-600">
                    {{ $t('gestlab.general.labels.admin.notifications.create.recipients.preview.description') }}
                  </p>
                </div>
              </div>
              <div class="rounded-full bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-1">
                <span class="text-sm font-semibold text-white">{{ estimatedRecipients }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SCHEDULING CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <CalendarIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.title') }}
          </h2>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Schedule Toggle -->
          <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200 bg-gray-50">
            <div class="flex items-center gap-3">
              <CalendarIcon class="h-5 w-5 text-blue-900" />
              <div>
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.schedule.label') }}
                </p>
                <p class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.schedule.description') }}
                </p>
              </div>
            </div>
            <button
              type="button"
              @click="form.schedule_send = !form.schedule_send"
              :class="[
                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                form.schedule_send ? 'bg-gradient-to-r from-blue-900 to-blue-800' : 'bg-gray-200'
              ]"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  form.schedule_send ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </div>

          <!-- Scheduling Options -->
          <div v-if="form.schedule_send" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Scheduled Date & Time -->
              <div class="space-y-2">
                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ClockIcon class="h-4 w-4 text-blue-900" />
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.scheduled_at.label') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.scheduled_at"
                  type="datetime-local"
                  id="scheduled_at"
                  required
                  :min="minDateTime"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
                />
                <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                  <CalendarDaysIcon class="h-4 w-4" />
                  <span>{{ $t('gestlab.general.labels.admin.notifications.create.scheduling.scheduled_at.hint') }}:</span>
                  <span class="font-medium text-blue-900">{{ formatScheduledTime }}</span>
                </div>
              </div>

              <!-- Expiration Date & Time -->
              <div class="space-y-2">
                <label for="expires_at" class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ExclamationTriangleIcon class="h-4 w-4 text-blue-900" />
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.expires_at.label') }}
                </label>
                <input
                  v-model="form.expires_at"
                  type="datetime-local"
                  id="expires_at"
                  :min="form.scheduled_at || minDateTime"
                  class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none transition-colors duration-200"
                />
                <div class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                  <ClockIcon class="h-4 w-4" />
                  <span>{{ $t('gestlab.general.labels.admin.notifications.create.scheduling.expires_at.hint') }}</span>
                </div>
              </div>
            </div>

            <!-- Schedule Summary -->
            <div class="rounded-lg bg-gradient-to-r from-blue-50 to-white border border-blue-200 p-4">
              <div class="flex items-center gap-3">
                <CalendarDaysIcon class="h-5 w-5 text-blue-900 flex-shrink-0" />
                <div>
                  <p class="text-sm font-medium text-gray-900">
                    {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.summary.title') }}
                  </p>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.summary.notification_will_send') }}
                    <span class="font-semibold text-blue-900">{{ formatScheduledTime }}</span>
                    <span v-if="form.expires_at">
                      {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.summary.and_expire') }}
                      <span class="font-semibold text-blue-900">{{ formatExpiresTime }}</span>
                    </span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Send Now Option -->
          <div v-else class="rounded-lg bg-gradient-to-r from-green-50 to-white border border-green-200 p-4">
            <div class="flex items-center gap-3">
              <BoltIcon class="h-5 w-5 text-green-900 flex-shrink-0" />
              <div>
                <p class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.send_now.title') }}
                </p>
                <p class="text-sm text-gray-600 mt-1">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.send_now.description') }}
                  <span class="font-semibold text-green-900">
                    {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.send_now.immediately') }}
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Timezone Info -->
          <div class="rounded-lg bg-gray-50 p-4">
            <div class="flex items-center gap-3">
              <GlobeAmericasIcon class="h-5 w-5 text-gray-600 flex-shrink-0" />
              <div>
                <p class="text-sm text-gray-600">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.timezone.info') }}
                  <span class="font-medium text-gray-900">{{ userTimezone }}</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  {{ $t('gestlab.general.labels.admin.notifications.create.scheduling.timezone.note') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SUMMARY CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <DocumentCheckIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.admin.notifications.create.summary.title') }}
          </h2>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Notification Summary -->
            <div class="space-y-4">
              <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <BellIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.title') }}
              </h3>
              
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.title_label') }}</span>
                  <span class="text-sm font-medium text-gray-900 truncate ml-4 max-w-xs">{{ form.title }}</span>
                </div>
                
                <div class="flex items-start justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.type') }}</span>
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    getTypeBadgeClass(form.type)
                  ]">
                    {{ getTypeLabel(form.type) }}
                  </span>
                </div>
                
                <div class="flex items-start justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.priority') }}</span>
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    getPriorityBadgeClass(form.priority)
                  ]">
                    {{ getPriorityLabel(form.priority) }}
                  </span>
                </div>
                
                <div class="flex items-start justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.length') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ messageLength }} {{ $t('gestlab.general.labels.admin.notifications.create.summary.notification.characters') }}</span>
                </div>
              </div>
            </div>

            <!-- Delivery Summary -->
            <div class="space-y-4">
              <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <TruckIcon class="h-4 w-4 text-blue-900" />
                {{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.title') }}
              </h3>
              
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.recipients') }}</span>
                  <span class="text-sm font-medium text-gray-900">
                    <span class="font-semibold text-blue-900">{{ estimatedRecipients }}</span>
                    {{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.users') }}
                  </span>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.type') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ getRecipientTypeLabel(form.recipient_type) }}</span>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.schedule') }}</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ form.schedule_send 
                        ? $t('gestlab.general.labels.admin.notifications.create.summary.delivery.scheduled') 
                        : $t('gestlab.general.labels.admin.notifications.create.summary.delivery.immediate') 
                    }}
                  </span>
                </div>
                
                <div v-if="form.schedule_send" class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.scheduled_for') }}</span>
                  <span class="text-sm font-medium text-blue-900">{{ formatScheduledTime }}</span>
                </div>
                
                <div v-if="form.expires_at" class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.admin.notifications.create.summary.delivery.expires_at') }}</span>
                  <span class="text-sm font-medium text-orange-900">{{ formatExpiresTime }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ACTIONS CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $t('gestlab.general.labels.admin.notifications.create.actions.title') }}</h3>
            <p class="mt-1 text-sm text-gray-600">
              {{ $t('gestlab.general.labels.admin.notifications.create.actions.subtitle') }}
            </p>
          </div>
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="previewNotification"
              :disabled="!isFormValid || form.processing"
              :class="[
                'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                (!isFormValid || form.processing)
                  ? 'opacity-50 cursor-not-allowed'
                  : 'hover:bg-gray-50'
              ]"
            >
              <EyeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.admin.notifications.create.actions.preview') }}
            </button>
            <button
              type="submit"
              :disabled="!isFormValid || form.processing"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                (!isFormValid || form.processing)
                  ? 'bg-gradient-to-r from-gray-400 to-gray-300 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              <ArrowRightIcon class="h-5 w-5" />
              {{ form.processing 
                  ? $t('gestlab.general.labels.admin.notifications.create.actions.processing') 
                  : form.schedule_send 
                    ? $t('gestlab.general.labels.admin.notifications.create.actions.schedule') 
                    : $t('gestlab.general.labels.admin.notifications.create.actions.send')
              }}
            </button>
          </div>
        </div>
        
        <!-- Validation Summary -->
        <div v-if="!isFormValid" class="mt-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
          <div class="flex items-center gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600 flex-shrink-0" />
            <div>
              <p class="text-sm font-medium text-yellow-800">
                {{ $t('gestlab.general.labels.admin.notifications.create.actions.validation.title') }}
              </p>
              <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                <li v-if="!form.title">{{ $t('gestlab.general.labels.admin.notifications.create.actions.validation.title_required') }}</li>
                <li v-if="!form.message">{{ $t('gestlab.general.labels.admin.notifications.create.actions.validation.message_required') }}</li>
                <li v-if="form.recipient_type === 'specific' && form.recipients.length === 0">
                  {{ $t('gestlab.general.labels.admin.notifications.create.actions.validation.recipients_required') }}
                </li>
                <li v-if="form.schedule_send && !form.scheduled_at">
                  {{ $t('gestlab.general.labels.admin.notifications.create.actions.validation.schedule_required') }}
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </form>

    <!-- PREVIEW MODAL -->
    <div v-if="showPreview" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="showPreview = false"></div>
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white shadow-xl transition-all">
          <!-- Modal Header -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                <EyeIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.admin.notifications.create.preview.title') }}
              </h3>
              <button @click="showPreview = false" class="text-blue-200 hover:text-white transition-colors duration-200">
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
          
          <!-- Preview Content -->
          <div class="p-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
              <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                  <div :class="[
                    'flex h-12 w-12 items-center justify-center rounded-full',
                    getNotificationIconClass({ type: form.type })
                  ]">
                    <component :is="getNotificationIcon({ type: form.type })" class="h-6 w-6 text-white" />
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-2">
                    <h4 class="text-lg font-semibold text-gray-900">{{ form.title }}</h4>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      getPriorityBadgeClass(form.priority)
                    ]">
                      {{ getPriorityLabel(form.priority) }}
                    </span>
                  </div>
                  <p class="text-gray-700 whitespace-pre-wrap mb-4">{{ form.message }}</p>
                  <p class="mb-4 text-xs uppercase tracking-[0.18em] text-gray-500">Remetente: {{ senderAlias }}</p>
                  <div class="flex flex-wrap gap-2">
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      getTypeBadgeClass(form.type)
                    ]">
                      {{ getTypeLabel(form.type) }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                      {{ $t('gestlab.general.labels.admin.notifications.create.preview.recipients') }}: {{ estimatedRecipients }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                      {{ form.schedule_send ? formatScheduledTime : $t('gestlab.general.labels.admin.notifications.create.preview.now') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Preview Actions -->
            <div class="mt-6 flex justify-end gap-3">
              <button
                @click="showPreview = false"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
              >
                {{ $t('gestlab.general.labels.admin.notifications.create.preview.close') }}
              </button>
              <button
                @click="submit"
                :disabled="!isFormValid || form.processing"
                :class="[
                  'rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200',
                  (!isFormValid || form.processing)
                    ? 'bg-gradient-to-r from-gray-400 to-gray-300 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 hover:from-blue-800 hover:to-blue-700'
                ]"
              >
                {{ form.schedule_send 
                    ? $t('gestlab.general.labels.admin.notifications.create.preview.confirm_schedule') 
                    : $t('gestlab.general.labels.admin.notifications.create.preview.confirm_send')
                }}
              </button>
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
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import {
    PlusIcon,
  ArrowLeftIcon,
  BellIcon,
  SparklesIcon,
  HashtagIcon,
  ChatBubbleBottomCenterTextIcon,
  TagIcon,
  ExclamationTriangleIcon,
  UsersIcon,
  UserGroupIcon,
  UserIcon,
  InformationCircleIcon,
  BellAlertIcon,
  CalendarIcon,
  ClockIcon,
  CalendarDaysIcon,
  BoltIcon,
  GlobeAmericasIcon,
  DocumentCheckIcon,
  TruckIcon,
  EyeIcon,
  ArrowRightIcon,
  XMarkIcon,
  CheckBadgeIcon,
  XCircleIcon,
  EnvelopeIcon,
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  users: Array,
  userGroups: Array,
  notificationTypes: Object,
  defaultTemplates: Object,
})

const page = usePage()
const whiteLabel = computed(() => page.props.settings ?? {})

const form = useForm({
  title: whiteLabel.value.notification_default_title || '',
  message: whiteLabel.value.notification_default_message || '',
  type: 'info',
  priority: 'normal',
  recipient_type: 'all',
  recipients: [],
  group: 'all',
  schedule_send: false,
  scheduled_at: '',
  expires_at: '',
})

const showPreview = ref(false)
const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const recipientOptions = computed(() => [
  { 
    value: 'all', 
    label: 'All Users', 
    description: 'Send to every registered user',
    count: props.users.length 
  },
  { 
    value: 'group', 
    label: 'User Group', 
    description: 'Send to specific user group',
    count: null 
  },
  { 
    value: 'specific', 
    label: 'Specific Users', 
    description: 'Select individual users',
    count: null 
  },
])

const selectedGroup = computed(() => {
  return props.userGroups.find(g => g.id === form.group)
})

const isAllSelected = computed(() => {
  return form.recipients.length === props.users.length
})

const estimatedRecipients = computed(() => {
  switch (form.recipient_type) {
    case 'specific':
      return form.recipients.length
    case 'group':
      return selectedGroup.value?.count || 0
    case 'all':
      return props.users.length
    default:
      return 0
  }
})

const messageLength = computed(() => form.message.length)
const senderAlias = computed(() => whiteLabel.value.notification_sender_alias || page.props.auth?.user?.name || 'Sistema')

const formatExpiresTime = computed(() => {
  if (!form.expires_at) return 'No expiration'
  return new Date(form.expires_at).toLocaleString()
})

const userTimezone = computed(() => {
  return Intl.DateTimeFormat().resolvedOptions().timeZone
})

const isFormValid = computed(() => {
  if (!form.title || !form.message) return false
  if (form.recipient_type === 'specific' && form.recipients.length === 0) return false
  if (form.schedule_send && !form.scheduled_at) return false
  return true
})

const getTypeColor = (type) => {
  const colors = {
    info: 'bg-blue-500',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500',
    alert: 'bg-orange-500',
  }
  return colors[type] || 'bg-gray-500'
}

const getTypeOptionClass = (type) => {
  const colors = {
    info: 'text-blue-600',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    error: 'text-red-600',
    alert: 'text-orange-600',
  }
  return colors[type] || 'text-gray-600'
}

const getTypeBadgeClass = (type) => {
  const classMap = {
    success: 'bg-green-100 text-green-800',
    error: 'bg-red-100 text-red-800',
    warning: 'bg-yellow-100 text-yellow-800',
    alert: 'bg-orange-100 text-orange-800',
    info: 'bg-blue-100 text-blue-800'
  }
  return classMap[type] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type) => {
  const labelMap = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    alert: 'Alert',
    info: 'Information'
  }
  return labelMap[type] || 'Notification'
}

const getPriorityBadgeClass = (priority) => {
  const classMap = {
    low: 'bg-gray-100 text-gray-800',
    normal: 'bg-blue-100 text-blue-800',
    high: 'bg-yellow-100 text-yellow-800',
    urgent: 'bg-red-100 text-red-800'
  }
  return classMap[priority] || 'bg-blue-100 text-blue-800'
}

const getPriorityLabel = (priority) => {
  const labelMap = {
    low: 'Low Priority',
    normal: 'Normal Priority',
    high: 'High Priority',
    urgent: 'Urgent Priority'
  }
  return labelMap[priority] || 'Normal Priority'
}

const getRecipientTypeLabel = (type) => {
  const labelMap = {
    all: 'All Users',
    group: 'User Group',
    specific: 'Specific Users'
  }
  return labelMap[type] || type
}

const getNotificationIcon = (notification) => {
  const type = notification.type || 'info'
  const iconMap = {
    success: CheckBadgeIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    alert: BellIcon,
    info: InformationCircleIcon,
    email: EnvelopeIcon
  }
  return iconMap[type] || BellIcon
}

const getNotificationIconClass = (notification) => {
  const type = notification.type || 'info'
  const classMap = {
    success: 'bg-gradient-to-r from-green-600 to-green-500',
    error: 'bg-gradient-to-r from-red-600 to-red-500',
    warning: 'bg-gradient-to-r from-yellow-600 to-yellow-500',
    alert: 'bg-gradient-to-r from-orange-600 to-orange-500',
    info: 'bg-gradient-to-r from-blue-900 to-blue-800'
  }
  return classMap[type] || 'bg-gradient-to-r from-blue-900 to-blue-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const applyTemplate = (template) => {
  form.title = template.title
  form.message = template.message
  form.type = template.type
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    form.recipients = []
  } else {
    form.recipients = props.users.map(user => user.id)
  }
}

const previewNotification = () => {
  if (!isFormValid.value) {
    alert('Please fill in all required fields first')
    return
  }
  showPreview.value = true
}

const submit = () => {
  if (!isFormValid.value) {
    alert('Please fill in all required fields')
    return
  }
  
  form.post(route('admin.notifications.store'), {
    onError: () => {
      // Error handled by form.errors
    }
  })
}

onMounted(() => {
  // Set scheduled_at to tomorrow 9 AM by default
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  tomorrow.setHours(9, 0, 0, 0)
  form.scheduled_at = tomorrow.toISOString().slice(0, 16)
})
</script>
