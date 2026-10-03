<template>
  <div class="flex justify-between gap-4 lg:col-span-1">
    <div>
      <h3 class="ds-heading text-base">
        <slot name="title" />
      </h3>

      <p class="ds-copy mt-1 max-w-md text-sm">
        <slot name="description" />
      </p>
    </div>

    <div>
      <slot name="aside" />
    </div>
  </div>
</template>
