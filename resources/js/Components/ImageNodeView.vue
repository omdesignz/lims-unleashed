<template>
    <NodeViewWrapper class="custom-image-node">
      <img :src="node.attrs.src" @click="openCropper" @dblclick="openCropper" />
      <image-cropper-modal
        :image-src="node.attrs.src"
        :show="isCropping"
        @crop="updateImage"
        @close="isCropping = false"
      />
    </NodeViewWrapper>
  </template>
  
  <script>
  import { NodeViewWrapper } from '@tiptap/vue-3';
  import ImageCropperModal from '@/Components/image-cropper-modal.vue';
  
  export default {
    components: {
      NodeViewWrapper,
      ImageCropperModal,
    },
    props: ['node', 'editor', 'getPos'],
    data() {
      return {
        isCropping: false,
      };
    },
    methods: {
      openCropper() {
        this.isCropping = true;
      },
      updateImage(newSrc) {
        this.isCropping = false;
        this.editor.commands.updateAttributes('image', { src: newSrc });
      },
    },
  };
  </script>
  