import { Node, mergeAttributes } from '@tiptap/core';

const ImageNode = Node.create({
  name: 'image',

  group: 'inline',

  inline: true,

  draggable: true,

  addOptions() {
    return {
      inline: false,
      HTMLAttributes: {},
    };
  },

  addAttributes() {
    return {
      src: {
        default: null,
      },
      alt: {
        default: null,
      },
      title: {
        default: null,
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'img[src]',
      },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'img',
      mergeAttributes(
        { ...this.options.HTMLAttributes || {} }, // Ensure this is an object
        { ...HTMLAttributes || {} }              // Ensure this is an object
      ),
    ];
  },
  

  addNodeView() {
    return ({ node, getPos, editor }) => {
      const dom = document.createElement('div');
      dom.style.display = 'inline-block';
      dom.style.position = 'relative';
      dom.innerHTML = `<img src="${node.attrs.src}" alt="${node.attrs.alt || ''}" title="${node.attrs.title || ''}" style="max-width: 100%;"/>`;
      return { dom };
    };
  },
});

const ImageDropPlugin = ({ editor }) => {
    if (!editor) {
      console.error('Editor instance is not defined in ImageDropPlugin.');
      return;
    }
  
    return {
      props: {
        handleDOMEvents: {
          drop: (view, event) => {
            const hasFiles = event.dataTransfer?.files?.length;
  
            if (!hasFiles) {
              return false;
            }
  
            const files = Array.from(event.dataTransfer.files);
            const images = files.filter((file) => /image/i.test(file.type));
  
            if (images.length === 0) {
              return false;
            }
  
            event.preventDefault();
  
            images.forEach((image) => {
              const reader = new FileReader();
              reader.onload = () => {
                const base64 = reader.result;
                if (editor?.chain) {
                  editor.chain().focus().setImage({ src: base64 }).run();
                }
              };
              reader.readAsDataURL(image);
            });
  
            return true;
          },
        },
      },
    };
  };
  
  
  

  export const ImageExtension = ImageNode.extend({
    addProseMirrorPlugins() {
        if (!this.editor) {
          console.error('Editor instance is not defined in addProseMirrorPlugins.');
          return [];
        }
        return [ImageDropPlugin({ editor: this.editor })];
      },
      
  
    addCommands() {
      return {
        setImage:
          (options) =>
          ({ commands }) => {
            if (!options || !options.src) {
              console.error('Invalid options provided to setImage command.');
              return false;
            }
  
            return commands.insertContent({
              type: this.name,
              attrs: options,
            });
          },
      };
    },
  });  

  export default ImageExtension;
