<template>
    <div
      ref="wrapperRef"
      :class="[
        'absolute right-3 top-3 flex flex-row rounded px-0.5 opacity-0 group-hover:opacity-100',
        'border-[0.5px] bg-[var(--mt-bg-secondary)] [backdrop-filter:saturate(1.8)_blur(20px)]',
        className
      ]"
    >
      <slot />
    </div>
  </template>
  
  <script>
  import { ref } from "vue";
  
  export default {
    name: "ActionWrapper",
    props: {
      className: {
        type: String,
        default: "",
      },
    },
    setup() {
      const wrapperRef = ref(null);
      return { wrapperRef };
    },
  };
  </script>
  