import { Extension } from '@tiptap/core';

export const TableOfContents = Extension.create({
  name: 'tableOfContents',

  addStorage() {
    return {
      headings: [],
    };
  },

  addProseMirrorPlugins() {
    return [
      new Plugin({
        props: {
          decorations: ({ doc }) => {
            let headings = [];
            doc.descendants((node, pos) => {
              if (node.type.name === 'heading') {
                headings.push({
                  text: node.textContent,
                  level: node.attrs.level,
                  pos,
                });
              }
            });
            this.storage.headings = headings;
          },
        },
      }),
    ];
  },
});
