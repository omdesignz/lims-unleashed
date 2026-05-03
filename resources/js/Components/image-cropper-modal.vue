<template>
    <div v-if="show" class="cropper-modal">
      <Cropper
        :src="imageSrc"
        
        ref="cropper"
        :stencil-props="{ handlers: true }"
        @change="onCropChange"
      />
      <button type="button" @click="applyCrop">Crop</button>
      <button type="button" @click="closeModal">Cancel</button>
    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import { Cropper } from 'vue-advanced-cropper';
  import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.bubble.css';

  const props = defineProps({
    imageSrc: String,
    show: Boolean,
  });

  const emit = defineEmits(['crop', 'close']);
  const cropper = ref(null);

  const croppedDataUrl = ref(null);

  const onCropChange = (cropper) => {
    // croppedDataUrl.value = cropper.getResult().toDataURL();
    // const { coordinates, canvas} = cropper.value.getResult();
  };

  const applyCrop = () => {
    // console.log(cropper.value.getResult());
    const { coordinates, canvas, image } = cropper.value.getResult();
    console.log(image);
    croppedDataUrl.value = canvas.toDataURL();

    emit('crop', croppedDataUrl.value);
  };

  const closeModal = () => {
    emit('close');
  };
  
  </script>

  <!-- <style>
.cropper-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 80%;
  height: 80%;
  background: white;
  z-index: 1000;
  border: 1px solid #ccc;
  overflow: hidden;
}
</style> -->