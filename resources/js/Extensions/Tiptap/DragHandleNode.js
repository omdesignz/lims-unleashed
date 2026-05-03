import { Node } from '@tiptap/core';

export const DragHandleNode = Node.create({
  name: 'dragHandleNode',

  group: 'block',

  draggable: true,

  content: 'inline*',

  atom: true,

  addAttributes() {
    return {
      hover: {
        default: false,
        renderHTML: (attributes) => ({
          'data-hover': attributes.hover ? 'true' : 'false',
        }),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'div[data-type="drag-handle-node"]',
      },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      { ...HTMLAttributes, 'data-type': 'drag-handle-node', class: 'drag-handle-container' },
      ['span', { class: 'drag-handle' }, '⠿'], // Drag handle
      ['div', { class: 'content' }, 0], // Content hole
    ];
  },

  addNodeView() {
    return ({ editor, node, getPos }) => {
      const dom = document.createElement('div');
      dom.setAttribute('data-type', 'drag-handle-node');
      dom.classList.add('drag-handle-container');

      const dragHandle = document.createElement('span');
      dragHandle.classList.add('drag-handle');
      dragHandle.textContent = '⠿';
      dragHandle.style.display = 'none'; // Initially hidden

      const content = document.createElement('div');
      content.classList.add('content');
      content.textContent = node.attrs.content || 'Drag me!';

      dom.append(dragHandle, content);

      // Show drag handle on hover
      dom.addEventListener('mouseenter', () => {
        dragHandle.style.display = 'inline-block';
      });

      // Hide drag handle on leave
      dom.addEventListener('mouseleave', () => {
        dragHandle.style.display = 'none';
      });

      return {
        dom,
      };
    };
  },
});

export default DragHandleNode;