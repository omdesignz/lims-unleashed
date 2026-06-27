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
    <tr class="ds-table-row">
        <td class="ds-table-cell whitespace-nowrap px-6 py-4 font-mono text-xs">{{ path }}</td>
        <td class="ds-table-cell whitespace-nowrap px-6 py-4">{{ date }}</td>
        <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-[color:var(--ds-text)]">{{ size }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
          <div class="inline-flex items-center justify-end gap-2">
            <a
                :href="downloadUrl"
                target="_blank"
                rel="noopener nofollow"
                :title="$t('gestlab.general.buttons.download')"
                :aria-label="$t('gestlab.general.buttons.download')"
                class="ds-icon-button"
            >
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0l-3-3m3 3l3-3m-8.25 6a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
              </svg>
            </a>
            <button
                :title="$t('gestlab.general.buttons.delete')"
                :aria-label="$t('gestlab.general.buttons.delete')"
                class="ds-icon-button"
                :class="deletable ? 'ds-table-action-danger' : 'cursor-not-allowed opacity-35'"
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
