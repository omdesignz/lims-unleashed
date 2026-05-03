<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <!-- Header Section -->
    <div class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb and Stats -->
        <div class="py-6">
          <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
              <li class="inline-flex items-center">
                <Link :href="route('portal.home')" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-900">
                  <HomeModernIcon class="h-4 w-4 mr-2" />
                  {{ $t('gestlab.portal_menu.dashboard') }}
                </Link>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <ChevronRightIcon class="h-4 w-4 text-gray-400 mx-2" />
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_faqs.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <QuestionMarkCircleIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_faqs.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_faqs.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_faqs.questions_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <QuestionMarkCircleIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_faqs.questions') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Categories & Search -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <!-- Search Bar -->
        <div class="mb-6">
          <div class="relative max-w-2xl mx-auto">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="query.search"
              type="search"
              :placeholder="$t('gestlab.pages.portal_faqs.search_placeholder')"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
            />
          </div>
        </div>

        <!-- Quick Categories -->
        <div class="mb-6">
          <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('gestlab.pages.portal_faqs.quick_categories') }}</h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="category in quickCategories"
              :key="category.id"
              @click="filterByCategory(category)"
              :class="[
                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors duration-200',
                query.category === category.id
                  ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white shadow-sm'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <component :is="category.icon" class="h-4 w-4" />
              {{ category.name }}
            </button>
            <button
              @click="clearFilters"
              :class="[
                'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors duration-200',
                !query.category
                  ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white shadow-sm'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              <XMarkIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_faqs.all_categories') }}
            </button>
          </div>
        </div>

        <!-- Helpful Links -->
        <div>
          <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('gestlab.pages.portal_faqs.helpful_links') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a
              href="#"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200"
            >
              <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_faqs.live_chat') }}</p>
                <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_faqs.live_chat_description') }}</p>
              </div>
            </a>
            <a
              href="#"
              class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200"
            >
              <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                <PhoneIcon class="h-5 w-5 text-green-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_faqs.contact_support') }}</p>
                <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_faqs.contact_support_description') }}</p>
              </div>
            </a>
          </div>
        </div>
      </div>

      <!-- FAQs Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Section Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <QuestionMarkCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.pages.portal_faqs.frequently_asked') }}
            </h2>
            <div class="flex items-center gap-2">
              <span class="text-sm text-blue-100">
                {{ filteredQuestionsCount }} {{ $t('gestlab.pages.portal_faqs.questions_found') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <QuestionMarkCircleIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_faqs.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_faqs.empty_state.description') }}
          </p>
          <div class="mt-6">
            <button
              @click="clearFilters"
              class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <XMarkIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_faqs.clear_filters') }}
            </button>
          </div>
        </div>

        <!-- FAQs List -->
        <div v-else class="divide-y divide-gray-200">
          <Disclosure
            v-for="(faq, index) in props.record.data"
            :key="faq.id"
            as="div"
            class="transition-colors duration-200 hover:bg-gray-50"
            v-slot="{ open }"
            v-motion
            :initial="{ opacity: 0, y: 10 }"
            :enter="{ opacity: 1, y: 0 }"
            :delay="index * 30"
          >
            <DisclosureButton
              class="flex w-full items-center justify-between px-6 py-5 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-900"
            >
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                  <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-blue-50 to-white ring-1 ring-blue-100">
                    <span class="text-xs font-semibold text-blue-900">Q</span>
                  </div>
                  <h3 class="text-base font-semibold text-gray-900 pr-4">
                    {{ faq.description }}
                  </h3>
                </div>
                <div class="flex items-center gap-3 ml-9">
                  <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                    <TagIcon class="h-3 w-3" />
                    {{ getCategoryName(faq.category_id) }}
                  </span>
                  <span class="text-xs text-gray-500">
                    {{ $t('gestlab.pages.portal_faqs.updated') }} {{ formatRelativeTime(faq.updated_at) }}
                  </span>
                </div>
              </div>
              <div class="ml-4 flex-shrink-0">
                <ChevronDownIcon
                  :class="[
                    'h-5 w-5 transform transition-transform duration-200',
                    open ? 'rotate-180 text-blue-900' : 'text-gray-400'
                  ]"
                />
              </div>
            </DisclosureButton>
            
            <DisclosurePanel
              class="px-6 pb-5 pt-2"
              v-motion
              :initial="{ opacity: 0, height: 0 }"
              :enter="{ opacity: 1, height: 'auto' }"
              :leave="{ opacity: 0, height: 0 }"
            >
              <div class="ml-9 border-l-2 border-blue-200 pl-4">
                <div class="flex items-start gap-3 mb-3">
                  <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-r from-green-50 to-white ring-1 ring-green-100 mt-1">
                    <span class="text-xs font-semibold text-green-900">A</span>
                  </div>
                  <div class="space-y-3">
                    <div
                      v-for="answer in faq.answers"
                      :key="answer.id"
                      class="prose prose-sm max-w-none"
                    >
                      <p class="text-gray-700 leading-relaxed">
                        {{ answer.description }}
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- Helpful Actions -->
                <div class="mt-4 flex items-center gap-4">
                  <button
                    @click="markAsHelpful(faq)"
                    class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors duration-200"
                  >
                    <HandThumbUpIcon class="h-3 w-3" />
                    {{ $t('gestlab.pages.portal_faqs.helpful') }}
                    <span class="text-gray-500">({{ faq.helpful_count || 0 }})</span>
                  </button>
                  <button
                    @click="markAsUnhelpful(faq)"
                    class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors duration-200"
                  >
                    <HandThumbDownIcon class="h-3 w-3" />
                    {{ $t('gestlab.pages.portal_faqs.not_helpful') }}
                    <span class="text-gray-500">({{ faq.unhelpful_count || 0 }})</span>
                  </button>
                  <button
                    @click="shareQuestion(faq)"
                    class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors duration-200"
                  >
                    <ShareIcon class="h-3 w-3" />
                    {{ $t('gestlab.pages.portal_faqs.share') }}
                  </button>
                </div>
              </div>
            </DisclosurePanel>
          </Disclosure>
        </div>

        <!-- Pagination -->
        <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-700">
              {{ $t('gestlab.pages.portal_faqs.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_faqs.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_faqs.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_faqs.results') }}
            </div>
            <Pagination 
              :links="props.record.meta.links" 
              :from="props.record.meta.from" 
              :to="props.record.meta.to" 
              :total="props.record.meta.total" 
              :current_page="props.record.meta.current_page" 
              :last_page="props.record.meta.last_page" 
              class="mt-2"
            />
          </div>
        </div>
      </div>

      <!-- Submit Question & Contact -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Submit Question -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <PencilSquareIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.pages.portal_faqs.submit_question.title') }}
          </h3>
          <p class="text-sm text-gray-600 mb-4">
            {{ $t('gestlab.pages.portal_faqs.submit_question.description') }}
          </p>
          <div class="space-y-3">
            <textarea
              v-model="newQuestion"
              :placeholder="$t('gestlab.pages.portal_faqs.submit_question.placeholder')"
              rows="3"
              class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
            />
            <div class="flex items-center gap-3">
              <button
                @click="submitQuestion"
                :disabled="!newQuestion.trim()"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors duration-200',
                  newQuestion.trim()
                    ? 'bg-gradient-to-r from-blue-900 to-blue-800 text-white shadow-sm hover:from-blue-800 hover:to-blue-700'
                    : 'bg-gray-200 text-gray-500 cursor-not-allowed'
                ]"
              >
                <PaperAirplaneIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_faqs.submit_question.button') }}
              </button>
              <button
                @click="newQuestion = ''"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-200"
              >
                <XMarkIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_faqs.clear') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Still Need Help? -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <LifebuoyIcon class="h-5 w-5 text-green-600" />
            {{ $t('gestlab.pages.portal_faqs.still_need_help.title') }}
          </h3>
          <p class="text-sm text-gray-600 mb-4">
            {{ $t('gestlab.pages.portal_faqs.still_need_help.description') }}
          </p>
          <div class="space-y-3">
            <a
              href="#"
              class="flex items-center gap-3 rounded-lg border border-gray-300 bg-white p-4 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200"
            >
              <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_faqs.still_need_help.live_chat') }}</p>
                <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_faqs.still_need_help.live_chat_hours') }}</p>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 ml-auto" />
            </a>
            <a
              href="#"
              class="flex items-center gap-3 rounded-lg border border-gray-300 bg-white p-4 hover:border-blue-900 hover:bg-blue-50 transition-colors duration-200"
            >
              <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                <PhoneIcon class="h-5 w-5 text-green-900" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.pages.portal_faqs.still_need_help.call_us') }}</p>
                <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_faqs.still_need_help.call_us_hours') }}</p>
              </div>
              <ChevronRightIcon class="h-5 w-5 text-gray-400 ml-auto" />
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { useForm, router, Link, usePage } from '@inertiajs/vue3'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import Pagination from '@/Components/pagination.vue'
import {
  HomeModernIcon,
  QuestionMarkCircleIcon,
  MagnifyingGlassIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  XMarkIcon,
  TagIcon,
  HandThumbUpIcon,
  HandThumbDownIcon,
  ShareIcon,
  PencilSquareIcon,
  PaperAirplaneIcon,
  LifebuoyIcon,
  ChatBubbleLeftRightIcon,
  PhoneIcon,
  DocumentTextIcon,
  BanknotesIcon,
  DocumentCheckIcon,
  ArchiveBoxIcon,
  ClipboardDocumentCheckIcon,
  ReceiptRefundIcon
} from '@heroicons/vue/24/outline'

import Layout from "@/Shared/Layouts/PortalLayout.vue";

   defineOptions({
    layout: Layout
    });

const props = defineProps({
  record: Object,
  fields: Array,
  query: Object,
})

const page = usePage()
const portalUser = computed(() => page.props.auth?.user ?? {})

const query = reactive({
  search: props.query?.search || '',
  category: props.query?.category || '',
  page: props.query?.page || 1
})

const newQuestion = ref('')

const categoryIcons = [
  DocumentTextIcon,
  BanknotesIcon,
  DocumentCheckIcon,
  ArchiveBoxIcon,
  ClipboardDocumentCheckIcon,
  ReceiptRefundIcon,
]

const quickCategories = computed(() => {
  const categories = new Map()

  props.record.data.forEach((faq, index) => {
    if (faq.category_id && faq.category && !categories.has(faq.category_id)) {
      categories.set(faq.category_id, {
        id: faq.category_id,
        name: faq.category,
        icon: categoryIcons[index % categoryIcons.length],
      })
    }
  })

  return Array.from(categories.values())
})

// Computed properties
const filteredQuestionsCount = computed(() => {
  return props.record.data.length
})

// Watch for query changes with debounce
watch(query, debounce(function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300))

// Helper functions
const formatRelativeTime = (dateString) => {
  if (!dateString) return '-'
  const now = new Date()
  const date = new Date(dateString)
  const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Hoje'
  if (diffDays === 1) return 'Ontem'
  if (diffDays < 7) return `${diffDays} dias atrás`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} semanas atrás`
  return `${Math.floor(diffDays / 30)} meses atrás`
}

const getCategoryName = (categoryId) => {
  const category = quickCategories.find(c => c.id === categoryId)
  return category ? category.name : 'Geral'
}

const filterByCategory = (category) => {
  query.category = category.id
  query.page = 1
}

const clearFilters = () => {
  query.search = ''
  query.category = ''
  query.page = 1
}

const submitPortalSupportRequest = (title, description, details = {}) => {
  router.post(route('portal.request.store'), {
    request_type: 'general_support',
    title,
    description,
    email: portalUser.value.email ?? '',
    contact: portalUser.value.primary_phone ?? portalUser.value.contact ?? '900000000',
    priority: 'normal',
    details,
  }, {
    preserveScroll: true,
  })
}

const markAsHelpful = (faq) => {
  submitPortalSupportRequest(
    `Feedback positivo sobre FAQ #${faq.id}`,
    `A resposta "${faq.description}" foi útil para o cliente no portal.`,
    {
      document_reference: `FAQ-${faq.id}`,
      document_type: 'faq_feedback',
      feedback: 'helpful',
    },
  )
}

const markAsUnhelpful = (faq) => {
  submitPortalSupportRequest(
    `Pedido de esclarecimento sobre FAQ #${faq.id}`,
    `A resposta "${faq.description}" não foi suficiente e o cliente precisa de acompanhamento adicional.`,
    {
      document_reference: `FAQ-${faq.id}`,
      document_type: 'faq_feedback',
      feedback: 'unhelpful',
    },
  )
}

const shareQuestion = (faq) => {
  const shareUrl = `${window.location.origin}/portal/faqs#faq-${faq.id}`
  if (navigator.share) {
    navigator.share({
      title: faq.description,
      text: 'Encontrei esta resposta útil no portal:',
      url: shareUrl
    })
  } else {
    navigator.clipboard.writeText(shareUrl)
  }
}

const submitQuestion = () => {
  if (newQuestion.value.trim()) {
    submitPortalSupportRequest(
      'Nova pergunta enviada pelo portal',
      newQuestion.value.trim(),
      {
        document_type: 'faq_question',
      },
    )
    newQuestion.value = ''
  }
}
</script>
