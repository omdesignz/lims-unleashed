import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { filterFiles} from '@/Extensions/Tiptap/utils';

const FileHandlePlugin = (options) => {
  const {
    key,
    editor,
    onPaste,
    onDrop,
    onValidationError,
    allowedMimeTypes,
    maxFileSize,
    allowBase64,
  } = options;

  return new Plugin({
    key: key || new PluginKey('fileHandler'),

    props: {
      handleDrop(view, event) {
        event.preventDefault();
        event.stopPropagation();

        const { dataTransfer } = event;
        if (!dataTransfer || !dataTransfer.files.length) {
          return false;
        }

        const pos = view.posAtCoords({
          left: event.clientX,
          top: event.clientY,
        });

        const [validFiles, errors] = filterFiles(Array.from(dataTransfer.files), {
          allowedMimeTypes,
          maxFileSize,
          allowBase64,
        });

        if (errors.length > 0 && typeof onValidationError === 'function') {
          onValidationError(errors);
        }

        if (validFiles.length > 0 && typeof onDrop === 'function') {
          onDrop(editor, validFiles, pos ? pos.pos : 0);
        }
      },

      handlePaste(view, event) {
        event.preventDefault();
        event.stopPropagation();

        const { clipboardData } = event;
        if (!clipboardData || !clipboardData.files.length) {
          return false;
        }

        const [validFiles, errors] = filterFiles(Array.from(clipboardData.files), {
          allowedMimeTypes,
          maxFileSize,
          allowBase64,
        });

        const html = clipboardData.getData('text/html');

        if (errors.length > 0 && typeof onValidationError === 'function') {
          onValidationError(errors);
        }

        if (validFiles.length > 0 && typeof onPaste === 'function') {
          onPaste(editor, validFiles, html);
        }
      },
    },
  });
};

export default Extension.create({
  name: 'fileHandler',

  addOptions() {
    return {
      allowBase64: false,
      allowedMimeTypes: [],
      maxFileSize: 0,
      onPaste: null,
      onDrop: null,
      onValidationError: null,
    };
  },

  addProseMirrorPlugins() {
    return [
      FileHandlePlugin({
        key: new PluginKey(this.name),
        editor: this.editor,
        ...this.options,
      }),
    ];
  },
});
