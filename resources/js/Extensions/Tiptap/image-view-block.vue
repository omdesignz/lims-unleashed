<template>
  <div ref="containerRef" class="relative text-center leading-none" data-drag-handle>
    <div
      class="group/node-image relative mx-auto rounded-md object-contain"
      :style="{
        maxWidth: `min(${maxWidth}px, 100%)`,
        width: currentWidth + 'px',
        maxHeight: MAX_HEIGHT + 'px',
        aspectRatio: `${imageState.naturalSize.width} / ${imageState.naturalSize.height}`
      }"
    >
      <div
        class="relative flex h-full cursor-default flex-col items-center gap-2 rounded"
        :class="{
          'outline outline-2 outline-offset-1 outline-primary': selected || isResizing
        }"
      >
        <div class="h-full contain-paint">
          <div class="relative h-full">
            <div v-if="imageState.isServerUploading && !imageState.error" class="absolute inset-0 flex items-center justify-center">
              <Spinner class="size-7" />
            </div>

            <div v-if="imageState.error" class="absolute inset-0 flex flex-col items-center justify-center">
              <InfoCircledIcon class="size-8 text-destructive" />
              <p class="mt-2 text-sm text-muted-foreground">Failed to load image</p>
            </div>

            <controlled-zoom
              :is-zoomed="imageState.isZoomed"
              @zoom-change="() => (imageState.isZoomed = false)"
            >
              <img
                class="h-auto rounded object-contain transition-shadow"
                :class="{ 'opacity-0': !imageState.imageLoaded || imageState.error }"
                :style="{
                  maxWidth: `min(100%, ${maxWidth}px)`,
                  minWidth: `${MIN_WIDTH}px`,
                  maxHeight: MAX_HEIGHT + 'px'
                }"
                :width="currentWidth"
                :height="currentHeight"
                :src="imageState.src"
                @error="handleImageError"
                @load="handleImageLoad"
                :alt="node.attrs.alt || ''"
                :title="node.attrs.title || ''"
                :id="node.attrs.id"
              />
            </controlled-zoom>
          </div>

          <ImageOverlay v-if="imageState.isServerUploading" />

          <ResizeHandle
            v-if="editor.isEditable && imageState.imageLoaded && !imageState.error && !imageState.isServerUploading"
            @pointerdown="handleResizeStart('left')"
            :class="{ 'hidden': isResizing && activeResizeHandle === 'right' }"
            :isResizing="isResizing && activeResizeHandle === 'left'"
            class="left-1"
          />
          <ResizeHandle
            v-if="editor.isEditable && imageState.imageLoaded && !imageState.error && !imageState.isServerUploading"
            @pointerdown="handleResizeStart('right')"
            :class="{ 'hidden': isResizing && activeResizeHandle === 'left' }"
            :isResizing="isResizing && activeResizeHandle === 'right'"
            class="right-1"
          />
        </div>

        <ActionWrapper v-if="imageState.error">
          <ActionButton
            icon="TrashIcon"
            tooltip="Remove image"
            @click="onRemoveImg"
          />
        </ActionWrapper>

        <ImageActions
          v-if="!isResizing && !imageState.error && !imageState.isServerUploading"
          :shouldMerge="shouldMerge"
          :isLink="isLink"
          @view="onView"
          @download="onDownload"
          @copy="onCopy"
          @copyLink="onCopyLink"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useDragResize } from '../hooks/use-drag-resize';
import { useImageActions } from '../hooks/use-image-actions';
import { blobUrlToBase64, randomId } from '@/Extensions/Tiptap/utils';
import ResizeHandle from '@/Extensions/Tiptap/resize-handle';
import ImageOverlay from '@/Extensions/Tiptap/image-overlay';
// import ImageOverlay from './image-overlay';
// import Spinner from '../../../components/spinner';
// import ActionButton from './image-actions';
// import ControlledZoom from 'react-medium-image-zoom';

const MAX_HEIGHT = 600;
const MIN_HEIGHT = 120;
const MIN_WIDTH = 120;

const normalizeUploadResponse = res => ({
  src: typeof res === 'string' ? res : res.src,
  id: typeof res === 'string' ? randomId() : res.id
});

export default {
  props: ['editor', 'node', 'selected', 'updateAttributes'],
  setup(props) {
    const { editor, node, updateAttributes } = props;
    const containerRef = ref(null);
    const activeResizeHandle = ref(null);
    const uploadAttemptedRef = ref(false);

    const initSrc = computed(() => {
      if (typeof node.attrs.src === 'string') return node.attrs.src;
      return node.attrs.src.src;
    });

    const imageState = reactive({
      src: initSrc.value,
      isServerUploading: false,
      imageLoaded: false,
      isZoomed: false,
      error: false,
      naturalSize: { width: node.attrs.width, height: node.attrs.height }
    });

    // Resize & Image Actions setup
    const { onView, onDownload, onCopy, onCopyLink, onRemoveImg } = useImageActions({
      editor,
      node,
      src: imageState.src,
      onViewClick: isZoomed => (imageState.isZoomed = isZoomed)
    });

    const { currentWidth, currentHeight, updateDimensions, initiateResize, isResizing } = useDragResize({
      initialWidth: node.attrs.width || imageState.naturalSize.width,
      initialHeight: node.attrs.height || imageState.naturalSize.height,
      contentWidth: imageState.naturalSize.width,
      contentHeight: imageState.naturalSize.height,
      onDimensionsChange: ({ width, height }) => updateAttributes({ width, height }),
      minWidth: MIN_WIDTH,
      minHeight: MIN_HEIGHT,
      maxWidth: MAX_HEIGHT * (imageState.naturalSize.width / imageState.naturalSize.height)
    });

    const handleImageLoad = ev => {
      const img = ev.target;
      const newNaturalSize = {
        width: img.naturalWidth,
        height: img.naturalHeight
      };

      Object.assign(imageState, {
        naturalSize: newNaturalSize,
        imageLoaded: true
      });

      updateAttributes({
        width: img.width || newNaturalSize.width,
        height: img.height || newNaturalSize.height,
        alt: img.alt,
        title: img.title
      });

      updateDimensions(state => ({ ...state, width: newNaturalSize.width }));
    };

    const handleImageError = () => {
      imageState.error = true;
      imageState.imageLoaded = true;
    };

    const handleResizeStart = direction => event => {
      activeResizeHandle.value = direction;
      initiateResize(direction)(event);
    };

    const handleResizeEnd = () => {
      activeResizeHandle.value = null;
    };

    onMounted(() => {
      const handleImage = async () => {
        if (!initSrc.value.startsWith('blob:') || uploadAttemptedRef.value) return;

        uploadAttemptedRef.value = true;
        const imageExtension = editor.options.extensions.find(ext => ext.name === 'image');
        const uploadFn = imageExtension?.options?.uploadFn;

        if (!uploadFn) {
          try {
            const base64 = await blobUrlToBase64(initSrc.value);
            imageState.src = base64;
            updateAttributes({ src: base64 });
          } catch {
            imageState.error = true;
          }
          return;
        }

        try {
          imageState.isServerUploading = true;
          const response = await fetch(initSrc.value);
          const blob = await response.blob();
          const file = new File([blob], node.attrs.fileName, { type: blob.type });
          const url = await uploadFn(file, editor);
          const normalizedData = normalizeUploadResponse(url);

          Object.assign(imageState, {
            ...normalizedData,
            isServerUploading: false
          });

          updateAttributes(normalizedData);
        } catch {
          imageState.error = true;
          imageState.isServerUploading = false;
        }
      };

      handleImage();
    });

    return {
      containerRef,
      imageState,
      currentWidth,
      currentHeight,
      handleImageLoad,
      handleImageError,
      handleResizeStart,
      handleResizeEnd,
      onView,
      onDownload,
      onCopy,
      onCopyLink,
      onRemoveImg,
      activeResizeHandle,
      isResizing
    };
  }
};
</script>
