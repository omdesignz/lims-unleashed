import { Mark, mergeAttributes } from '@tiptap/core';

export const CustomHighlight = Mark.create({
  name: 'highlight',

  addOptions() {
    return {
      multicolor: true,
      defaultColor: '#ffff00', // Default yellow
    };
  },

  addAttributes() {
    return {
      color: {
        default: this.options.defaultColor,
        parseHTML: (element) => element.style.backgroundColor || this.options.defaultColor,
        renderHTML: (attributes) => {
          return {
            style: `background-color: ${attributes.color || this.options.defaultColor}`,
          };
        },
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'span',
        getAttrs: (element) => element.style.backgroundColor && { color: element.style.backgroundColor },
      },
    ];
  },

  renderHTML({ HTMLAttributes }) {
    return ['span', mergeAttributes(HTMLAttributes), 0];
  },

  addCommands() {
    return {
      setHighlight:
        (color) =>
        ({ commands }) => {
          return commands.setMark(this.name, { color });
        },
      toggleHighlight:
        (color) =>
        ({ commands }) => {
          return commands.toggleMark(this.name, { color });
        },
      unsetHighlight:
        () =>
        ({ commands }) => {
          return commands.unsetMark(this.name);
        },
    };
  },
});
