<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'

const editor = useEditor({
  content: '<p>Here is the content!</p>',
  editorProps: {
    attributes: {
      class: 'w-full prose my-6 mx-auto focus:outline-none',
    },
  },
  extensions: [
    StarterKit,
  ],
})
</script>

<template>
  <EditorContent :editor="editor" />
</template>