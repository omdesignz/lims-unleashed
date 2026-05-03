<template>
    <Tooltip>
      <template #trigger>
        <Button
          ref="buttonRef"
          variant="ghost"
          :class="[
            'relative flex h-7 w-7 flex-row rounded-none p-0 text-muted-foreground hover:text-foreground',
            'bg-transparent hover:bg-transparent',
            className,
          ]"
          v-bind="props"
        >
          <slot />
        </Button>
      </template>
      <TooltipContent side="bottom">{{ tooltip }}</TooltipContent>
    </Tooltip>
  </template>
  
  <script>
  import { ref } from "vue";
//   import Tooltip from "@/components/ui/tooltip/Tooltip.vue";
//   import TooltipContent from "@/components/ui/tooltip/TooltipContent.vue";
//   import Button from "@/components/ui/Button.vue";
  
  export default {
    name: "ActionButton",
    // components: { Tooltip, TooltipContent, Button },
    props: {
      tooltip: {
        type: String,
        required: true,
      },
      className: {
        type: String,
        default: "",
      },
      props: {
        type: Object,
        default: () => ({}),
      },
    },
    setup() {
      const buttonRef = ref(null);
      return { buttonRef };
    },
  };
  </script>
  