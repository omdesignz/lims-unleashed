<template>
    <div
      :class="[
        'absolute top-1/2 h-10 max-h-full w-1.5 -translate-y-1/2 transform cursor-col-resize rounded border border-solid border-[var(--mt-transparent-foreground)] bg-[var(--mt-bg-secondary)] p-px transition-all',
        'opacity-0 [backdrop-filter:saturate(1.8)_blur(20px)]',
        isResizing ? 'opacity-80' : 'group-hover/node-image:opacity-80',
        'before:absolute before:inset-y-0 before:-left-1 before:-right-1',
        className
      ]"
      ref="resizeHandle"
      v-bind="props"
    ></div>
  </template>
  
  <script>
  export default {
    name: "ResizeHandle",
    props: {
      isResizing: {
        type: Boolean,
        default: false,
      },
      className: {
        type: String,
        default: "",
      },
      // Pass any other HTML attributes
      props: {
        type: Object,
        default: () => ({}),
      },
    },
    mounted() {
      // You can access the ref `resizeHandle` here if needed
    },
  };
  </script>
  
  <style scoped>
  /* Add any scoped styles here if necessary */
  </style>
  