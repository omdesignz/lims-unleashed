<script setup>
import { ref, watch } from 'vue'
import { CursorArrowRippleIcon} from '@heroicons/vue/24/outline'

defineProps({
    actions: Array,
    recordIds: {
        type: Array,
        default: []
    }
});

const emit = defineEmits(['execute']);

const actionId = ref(null); 
</script>
<template>
    <div>
    <div class="mt-1 flex rounded-md shadow-sm ml-4">
      <div class="relative flex items-stretch flex-grow focus-within:z-10">
        <select :disabled="!recordIds.length" v-model="actionId" id="location" name="location" class="block w-full rounded-none rounded-l-md pl-10 sm:text-sm border-gray-300">
            <option v-for="(action, index) in actions" :value="action.id" key="index">{{ $t(action.label) }}</option>
        </select>
      </div>
      <button :disabled="Object.is(actionId,null) || !recordIds.length" @click="$emit('execute', actionId)" :class="[recordIds.length && !Object.is(actionId,null) ? 'hover:bg-blue-900 hover:text-white' : '']" type="button" class="group -ml-px relative inline-flex items-center space-x-2 px-4 py-2 border border-gray-300 text-sm font-medium rounded-r-md text-gray-700 bg-gray-50 focus:outline-none">
        <CursorArrowRippleIcon :class="[recordIds.length && !Object.is(actionId,null) ? 'group-hover:text-white' : '']" class="h-5 w-5 text-gray-900" aria-hidden="true" />
        <span>{{ $t('gestlab.actions.apply') }}</span>
      </button>
    </div>
  </div>
</template>