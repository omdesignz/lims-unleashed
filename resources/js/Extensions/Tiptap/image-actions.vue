<template>
    <ActionWrapper :class="{ 'opacity-100': isOpen }">
      <DropdownMenu v-if="shouldMerge" v-model:open="isOpen">
        <template #trigger>
          <ActionButton
            :tooltip="'Open menu'"
            @click.prevent
          >
            <EllipsesHorizontalIcon class="size-4" />
          </ActionButton>
        </template>
        <DropdownMenuContent class="w-56" align="end">
          <DropdownMenuItem
            v-for="({ key, icon, tooltip }) in filteredActions"
            :key="key"
            @click="handleAction($event, actions[key])"
          >
            <div class="flex flex-row items-center gap-2">
              <slot :name="icon" />
              <span>{{ tooltip }}</span>
            </div>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <template v-else>
        <ActionButton
          v-for="({ key, icon, tooltip }) in filteredActions"
          :key="key"
          :tooltip="tooltip"
          @click="handleAction($event, actions[key])"
        >
          <slot :name="icon" />
        </ActionButton>
      </template>
    </ActionWrapper>
  </template>
  
  <script>
  import { ref, computed } from "vue";
  import ActionWrapper from "@/Extensions/Tiptap/action-wrapper.vue";
  import ActionButton from "@/Extensions/Tiptap/action-button.vue";
  import { EllipsesHorizontalIcon, ArrowsPointingOutIcon, CloudArrowDown, ClipboardDocumentCheckIcon, LinkIcon } from "@headlessui/vue/20/solid";
  
  const ActionItems = [
    { key: "onView", icon: ArrowsPointingOutIcon, tooltip: "View image" },
    { key: "onDownload", icon: CloudArrowDown, tooltip: "Download image" },
    { key: "onCopy", icon: ClipboardDocumentCheckIcon, tooltip: "Copy image to clipboard" },
    { key: "onCopyLink", icon: LinkIcon, tooltip: "Copy image link", isLink: true },
  ];
  
  export default {
    name: "ImageActions",
    components: { ActionWrapper, ActionButton },
    props: {
      shouldMerge: {
        type: Boolean,
        default: false,
      },
      isLink: {
        type: Boolean,
        default: false,
      },
      actions: {
        type: Object,
        default: () => ({}),
      },
    },
    setup(props) {
      const isOpen = ref(false);
  
      const handleAction = (event, action) => {
        event.preventDefault();
        event.stopPropagation();
        if (action) action();
      };
  
      const filteredActions = computed(() =>
        ActionItems.filter((item) => props.isLink || !item.isLink)
      );
  
      return { isOpen, handleAction, filteredActions };
    },
  };
  </script>
  