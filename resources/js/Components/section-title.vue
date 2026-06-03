<template>
    <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-semibold tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
                <slot name="title" />
            </h3>

            <p class="mt-1 max-w-3xl text-sm leading-6 text-[#5f6f68] dark:text-[#a9bbb4]">
                <slot name="description" />
            </p>
        </div>

        <div class="px-4 sm:px-0">
            <slot name="aside" />
        </div>
    </div>
</template>
