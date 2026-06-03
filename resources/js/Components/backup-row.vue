<script setup>
import { computed } from 'vue';

const props = defineProps({
    date: { required: true },
    path: { required: true },
    size: { required: true },
    disk: { required: true },
    deletable: { required: true },
    deleting: { required: true },
});

const emit = defineEmits([
    'delete'
]);

const downloadUrl = computed(() => {
    
    const endpoint = route('systembackups.download');

    return `${endpoint}?disk=${props?.disk}&path=${props?.path}`;
});

</script>

<template>
    <tr class="bg-white transition-colors duration-200 hover:bg-primary-50/70 dark:bg-slate-950/40 dark:hover:bg-slate-800/70">
        <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-slate-700 dark:text-slate-200">{{ path }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ date }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800 dark:text-slate-100">{{ size }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
          <div class="inline-flex items-center justify-end gap-2">
            <a
                :href="downloadUrl"
                target="_blank"
                rel="noopener nofollow"
                title="Download"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:-translate-y-0.5 hover:border-primary-300 hover:text-primary-700 hover:shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-primary-500 dark:hover:text-primary-200"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0l-3-3m3 3l3-3m-8.25 6a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
              </svg>
            </a>
            <button
                title="Delete"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition"
                :class="deletable ? 'border-rose-200 bg-white text-rose-500 hover:-translate-y-0.5 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 hover:shadow-sm dark:border-rose-500/30 dark:bg-slate-900 dark:text-rose-300 dark:hover:bg-rose-500/10' : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-300 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-700'"
                :disabled="!deletable"
                @click.prevent="emit('delete', {disk: disk, path: path})"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </td>
    </tr>
</template>
