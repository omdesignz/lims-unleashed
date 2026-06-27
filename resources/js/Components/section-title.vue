<template>
    <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 class="ds-heading text-lg">
                <slot name="title" />
            </h3>

            <p class="ds-copy mt-1 max-w-3xl text-sm">
                <slot name="description" />
            </p>
        </div>

        <div class="px-4 sm:px-0">
            <slot name="aside" />
        </div>
    </div>
</template>
