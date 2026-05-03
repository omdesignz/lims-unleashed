<template>
  <div>
    <span class="isolate inline-flex rounded-md shadow-sm m-1">
      <button
        class="relative inline-flex items-center gap-x-1.5 rounded-l-md bg-white dark:bg-gray-900 px-2 py-1 text-xs font-semibold text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-10"
      >
        {{ $t("gestlab.quick_menu.title") }}
      </button>
      <button
        @click="isShowing = !isShowing"
        class="relative -ml-px inline-flex items-center rounded-r-md bg-white dark:bg-gray-900 px-2 py-1 text-xs font-semibold text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-10"
      >
        <EyeIcon v-if="!isShowing" class="w-5 h-5" />
        <EyeSlashIcon v-else class="w-5 h-5" />
      </button>
    </span>
  </div>

  <TransitionRoot :show="isShowing">
    <!-- Background overlay -->
    <TransitionChild
      enter="transition-opacity ease-linear duration-300"
      enter-from="opacity-0"
      enter-to="opacity-100"
      leave="transition-opacity ease-linear duration-300"
      leave-from="opacity-100"
      leave-to="opacity-0"
    >
      <!-- ... -->
    </TransitionChild>

    <!-- Sliding sidebar -->
    <TransitionChild
      enter="transition ease-in-out duration-300 transform"
      enter-from="-translate-x-full"
      enter-to="translate-x-0"
      leave="transition ease-in-out duration-300 transform"
      leave-from="translate-x-0"
      leave-to="-translate-x-full"
    >
      <div
        class="divide-y divide-gray-200 dark:divide-gray-800 overflow-hidden rounded-lg bg-white dark:bg-gray-900 shadow sm:grid sm:grid-cols-4 sm:gap-px sm:divide-y-0 mt-3"
      >
        <div
          v-for="(action, actionIdx) in actions"
          :key="action.title"
          :class="[
            actionIdx === 0
              ? 'rounded-tl-lg rounded-tr-lg sm:rounded-tr-none'
              : '',
            actionIdx === 1 ? 'sm:rounded-tr-none' : '',
            // actionIdx === actions.length - 2 ? 'sm:rounded-bl-lg' : '',
            actionIdx === actions.length
              ? 'rounded-bl-lg rounded-br-lg sm:rounded-bl-none'
              : '',
            'group relative p-6 focus-within:ring-2 focus-within:ring-inset transition-all duration-700 ' +
              `${action.cardHoverBackground} ${action.focusCardBackground} ${action.focus}`,
          ]"
        >
          <div>
            <!-- <span :class="[action.iconBackground, action.iconForeground, 'inline-flex rounded-lg p-3 ring-2 ring-white group-hover:animate-bounce']"> -->
            <component
              :is="action.icon"
              class="h-6 w-6 group-hover:animate-bounce group-hover:text-white group-hover:scale-150"
              aria-hidden="true"
              :class="[action.iconForeground, '']"
            />
            <!-- </span> -->
          </div>
          <div class="mt-8">
            <h3
              class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white"
            >
              <Link prefetch :href="action.href" class="focus:outline-none">
                <!-- Extend touch target to entire panel -->
                <span class="absolute inset-0" aria-hidden="true" />
                {{ $t(action.title) }}
              </Link>
            </h3>
            <p
              class="mt-2 text-sm text-gray-500 text-justify group-hover:text-white"
            >
              {{ $t(action.text) }}
            </p>
          </div>
          <span
            class="pointer-events-none absolute right-6 top-6 text-gray-300 group-hover:text-white"
            aria-hidden="true"
          >
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M20 4h1a1 1 0 00-1-1v1zm-1 12a1 1 0 102 0h-2zM8 3a1 1 0 000 2V3zM3.293 19.293a1 1 0 101.414 1.414l-1.414-1.414zM19 4v12h2V4h-2zm1-1H8v2h12V3zm-.707.293l-16 16 1.414 1.414 16-16-1.414-1.414z"
              />
            </svg>
          </span>
        </div>
      </div>
    </TransitionChild>
  </TransitionRoot>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { TransitionRoot, TransitionChild } from "@headlessui/vue";
import {
  ClipboardIcon,
  BanknotesIcon,
  CheckBadgeIcon,
  ClockIcon,
  ReceiptRefundIcon,
  UsersIcon,
  CubeTransparentIcon,
  BeakerIcon,
  ArchiveBoxIcon,
  UserGroupIcon,
  DocumentTextIcon,
  CubeIcon,
  DocumentCheckIcon,
  QueueListIcon,
  EyeIcon,
  EyeSlashIcon,
  ClipboardDocumentIcon,
  RectangleStackIcon,
  EyeDropperIcon,
  RectangleGroupIcon,
  StopCircleIcon,
  InboxStackIcon,
  VariableIcon,
  ChartBarSquareIcon,
  Square2StackIcon,
} from "@heroicons/vue/24/outline";
const isShowing = ref(false);

onMounted(() => {
  isShowing.value = true;
});
const actions = [
  {
    title: "gestlab.quick_menu.boards.customers.title",
    href: route("customers.index"),
    icon: UserGroupIcon,
    defaultBackground: "bg-teal-50",
    iconForeground: "text-teal-700",
    iconBackground: "bg-teal-50",
    cardHoverBackground: "hover:bg-teal-700",
    focusCardBackground: "focus-visible:bg-teal-700",
    focus: "focus:bg-teal-700",
    text: "gestlab.quick_menu.boards.customers.description",
  },
  {
    title: "gestlab.quick_menu.boards.collections.title",
    href: route("directcollections.index"),
    icon: ArchiveBoxIcon,
    defaultBackground: "bg-purple-50",
    iconForeground: "text-purple-700",
    iconBackground: "bg-purple-50",
    cardHoverBackground: "hover:bg-purple-700",
    focusCardBackground: "focus-visible:bg-purple-700",
    focus: "focus:bg-purple-700",
    text: "gestlab.quick_menu.boards.collections.description",
  },
  {
    title: "gestlab.quick_menu.boards.standards.title",
    href: route("standards.index"),
    icon: DocumentCheckIcon,
    defaultBackground: "bg-sky-50",
    iconForeground: "text-sky-700",
    iconBackground: "bg-sky-50",
    cardHoverBackground: "hover:bg-sky-700",
    focusCardBackground: "focus-visible:bg-sky-700",
    focus: "focus:bg-sky-700",
    text: "gestlab.quick_menu.boards.standards.description",
  },
  {
    title: "gestlab.quick_menu.boards.invoices.title",
    href: route("invoices.index"),
    icon: BanknotesIcon,
    defaultBackground: "bg-yellow-50",
    iconForeground: "text-yellow-700",
    iconBackground: "bg-yellow-50",
    cardHoverBackground: "hover:bg-yellow-700",
    focusCardBackground: "focus-visible:bg-yellow-700",
    focus: "focus:bg-yellow-700",
    text: "gestlab.quick_menu.boards.invoices.description",
  },
  {
    title: "gestlab.quick_menu.boards.protocols.title",
    href: route("protocols.index"),
    icon: ClipboardDocumentIcon,
    defaultBackground: "bg-rose-50",
    iconForeground: "text-rose-700",
    iconBackground: "bg-rose-50",
    cardHoverBackground: "hover:bg-rose-700",
    focusCardBackground: "focus-visible:bg-rose-700",
    focus: "focus:bg-rose-700",
    text: "gestlab.quick_menu.boards.protocols.description",
  },
  {
    title: "gestlab.quick_menu.boards.matrixes.title",
    href: route("matrixes.index"),
    icon: CubeTransparentIcon,
    defaultBackground: "bg-indigo-50",
    iconForeground: "text-indigo-700",
    iconBackground: "bg-indigo-50",
    cardHoverBackground: "hover:bg-indigo-700",
    focusCardBackground: "focus-visible:bg-indigo-700",
    focus: "focus:bg-indigo-700",
    text: "gestlab.quick_menu.boards.matrixes.description",
  },
  {
    title: "gestlab.quick_menu.boards.analysis.title",
    href: route("analysis.index"),
    icon: BeakerIcon,
    defaultBackground: "bg-teal-50",
    iconForeground: "text-teal-700",
    iconBackground: "bg-teal-50",
    cardHoverBackground: "hover:bg-teal-700",
    focusCardBackground: "focus-visible:bg-teal-700",
    focus: "focus:bg-teal-700",
    text: "gestlab.quick_menu.boards.analysis.description",
  },
  {
    title: "gestlab.quick_menu.boards.products.title",
    href: route("products.index"),
    icon: CubeIcon,
    defaultBackground: "bg-purple-50",
    iconForeground: "text-purple-700",
    iconBackground: "bg-purple-50",
    cardHoverBackground: "hover:bg-purple-700",
    focusCardBackground: "focus-visible:bg-purple-700",
    focus: "focus:bg-purple-700",
    text: "gestlab.quick_menu.boards.products.description",
  },
  {
    title: "gestlab.quick_menu.boards.departments.title",
    href: route("departments.index"),
    icon: RectangleStackIcon,
    defaultBackground: "bg-sky-50",
    iconForeground: "text-sky-700",
    iconBackground: "bg-sky-50",
    cardHoverBackground: "hover:bg-sky-700",
    focusCardBackground: "focus-visible:bg-sky-700",
    focus: "focus:bg-sky-700",
    text: "gestlab.quick_menu.boards.departments.description",
  },
  {
    title: "gestlab.quick_menu.boards.profiles.title",
    href: route("profiles.index"),
    icon: QueueListIcon,
    defaultBackground: "bg-yellow-50",
    iconForeground: "text-yellow-700",
    iconBackground: "bg-yellow-50",
    cardHoverBackground: "hover:bg-yellow-700",
    focusCardBackground: "focus-visible:bg-yellow-700",
    focus: "focus:bg-yellow-700",
    text: "gestlab.quick_menu.boards.profiles.description",
  },
  {
    title: "gestlab.quick_menu.boards.samples.title",
    href: route("samples.index"),
    icon: EyeDropperIcon,
    defaultBackground: "bg-rose-50",
    iconForeground: "text-rose-700",
    iconBackground: "bg-rose-50",
    cardHoverBackground: "hover:bg-rose-700",
    focusCardBackground: "focus-visible:bg-rose-700",
    focus: "focus:bg-rose-700",
    text: "gestlab.quick_menu.boards.samples.description",
  },
  {
    title: "gestlab.quick_menu.boards.analysis_reports.title",
    href: route("qualitycertificates.index"),
    icon: DocumentTextIcon,
    defaultBackground: "bg-indigo-50",
    iconForeground: "text-indigo-700",
    iconBackground: "bg-indigo-50",
    cardHoverBackground: "hover:bg-indigo-700",
    focusCardBackground: "focus-visible:bg-indigo-700",
    focus: "focus:bg-indigo-700",
    text: "gestlab.quick_menu.boards.analysis_reports.description",
  },
  {
    title: "gestlab.quick_menu.boards.kanban.title",
    href: route("boards"),
    icon: ClipboardIcon,
    defaultBackground: "bg-gray-50",
    iconForeground: "text-black",
    iconBackground: "bg-black",
    cardHoverBackground: "hover:bg-black",
    focusCardBackground: "focus-visible:bg-black",
    focus: "focus:bg-black",
    text: "gestlab.quick_menu.boards.kanban.description",
  },
  {
    title: "gestlab.quick_menu.boards.media.title",
    href: route("file-manager"),
    icon: RectangleGroupIcon,
    defaultBackground: "bg-yellow-50",
    iconForeground: "text-yellow-400",
    iconBackground: "bg-yellow-50",
    cardHoverBackground: "hover:bg-yellow-400",
    focusCardBackground: "focus-visible:bg-yellow-400",
    focus: "focus:bg-yellow-400",
    text: "gestlab.quick_menu.boards.media.description",
  },
  {
    title: "gestlab.quick_menu.boards.inventory.title",
    href: route("inventory.index"),
    icon: InboxStackIcon,
    defaultBackground: "bg-teal-50",
    iconForeground: "text-teal-700",
    iconBackground: "bg-teal-50",
    cardHoverBackground: "hover:bg-teal-700",
    focusCardBackground: "focus-visible:bg-teal-700",
    focus: "focus:bg-teal-700",
    text: "gestlab.quick_menu.boards.inventory.description",
  },
  {
    title: "gestlab.quick_menu.boards.equipments.title",
    href: route("equipment-connection-test"),
    icon: StopCircleIcon,
    defaultBackground: "bg-purple-50",
    iconForeground: "text-purple-700",
    iconBackground: "bg-purple-50",
    cardHoverBackground: "hover:bg-purple-700",
    focusCardBackground: "focus-visible:bg-purple-700",
    focus: "focus:bg-purple-700",
    text: "gestlab.quick_menu.boards.equipments.description",
  },
  {
    title: "gestlab.quick_menu.boards.formulas.title",
    href: route("formulas.index"),
    icon: VariableIcon,
    defaultBackground: "bg-sky-50",
    iconForeground: "text-sky-700",
    iconBackground: "bg-sky-50",
    cardHoverBackground: "hover:bg-sky-700",
    focusCardBackground: "focus-visible:bg-sky-700",
    focus: "focus:bg-sky-700",
    text: "gestlab.quick_menu.boards.formulas.description",
  },
  {
    title: "gestlab.quick_menu.boards.metrics.title",
    href: route("metrics.index"),
    icon: ChartBarSquareIcon,
    defaultBackground: "bg-yellow-50",
    iconForeground: "text-yellow-700",
    iconBackground: "bg-yellow-50",
    cardHoverBackground: "hover:bg-yellow-700",
    focusCardBackground: "focus-visible:bg-yellow-700",
    focus: "focus:bg-yellow-700",
    text: "gestlab.quick_menu.boards.metrics.description",
  },
  {
    title: "gestlab.quick_menu.boards.proposals.title",
    href: route("proposals.index"),
    icon: Square2StackIcon,
    defaultBackground: "bg-rose-50",
    iconForeground: "text-rose-700",
    iconBackground: "bg-rose-50",
    cardHoverBackground: "hover:bg-rose-700",
    focusCardBackground: "focus-visible:bg-rose-700",
    focus: "focus:bg-rose-700",
    text: "gestlab.quick_menu.boards.proposals.description",
  },
];
</script>
