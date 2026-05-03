<template>
    <div
      class="flex flex-row items-center justify-center absolute inset-0 rounded bg-[var(--mt-overlay)] opacity-100 transition-opacity"
    >
      <Spinner class="size-7" />
    </div>
  </template>
  
  <script>
  import Spinner from "../../../components/spinner";
  
  export default {
    name: "ImageOverlay",
    components: {
      Spinner,
    },
  };
  </script>
  
  <style scoped>
  /* Add any scoped styles here if needed */
  </style>
  