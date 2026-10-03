<script setup>
defineProps({ processing: Boolean, message: String, failed: Boolean })
defineEmits(['refresh'])
</script>

<template>
  <div v-if="processing || message" class="ds-card p-4">
    <p v-if="processing" role="status" class="ds-copy text-sm">A actualizar o arquivo…</p>
    <template v-else>
      <p :role="failed ? 'alert' : 'status'" class="ds-copy text-sm">{{ message }}</p>
      <button v-if="failed" type="button" class="ds-button ds-button-secondary mt-3" @click="$emit('refresh')">
        Actualizar lista
      </button>
    </template>
  </div>
</template>
