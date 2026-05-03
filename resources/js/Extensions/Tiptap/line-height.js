import { Extension } from '@tiptap/core'

export const LineHeight = Extension.create({
  name: 'lineHeight',

  addOptions() {
    return {
      types: ['paragraph', 'heading', 'orderedList', 'bulletList'], // Specify the node types
    }
  },

  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          lineHeight: {
            default: null,
            renderHTML: attributes => {
              if (!attributes.lineHeight) {
                return {}
              }
              return { class: attributes.lineHeight }
            },
            parseHTML: element => element.getAttribute('class')?.split(' ').find(cls => cls.startsWith('leading-')) || null,
          },
        },
      },
    ]
  },

  addCommands() {
    return {
      setLineHeight:
        (lineHeight) =>
        ({ chain }) => {
          return chain().focus().setNode('paragraph', { lineHeight }).run()
        },
    }
  },
})

export default LineHeight
