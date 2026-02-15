<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Write something amazing...',
  },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    Link.configure({
      openOnClick: false,
    }),
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
    Placeholder.configure({
      placeholder: props.placeholder,
    }),
  ],
  content: props.modelValue,
  onUpdate: () => {
    emit('update:modelValue', editor.value.getHTML())
  },
  editorProps: {
    attributes: {
      class: 'prose prose-sm sm:prose-base lg:prose-lg xl:prose-xl max-w-none m-5 focus:outline-none min-h-[150px] prose-headings:font-bold prose-headings:text-slate-800 prose-p:text-slate-600 prose-a:text-orange-600 prose-a:no-underline hover:prose-a:underline',
    },
  },
})

watch(() => props.modelValue, (value) => {
  const isSame = editor.value.getHTML() === value
  if (isSame) {
    return
  }
  editor.value.commands.setContent(value, false)
})

onBeforeUnmount(() => {
  editor.value.destroy()
})
</script>

<template>
  <div v-if="editor" class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-sm focus-within:ring-2 focus-within:ring-orange-500/20 focus-within:border-orange-500 transition-all">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-1 border-b border-slate-100 bg-slate-50/50 p-2">
      <button 
        @click="editor.chain().focus().toggleBold().run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('bold'), 'text-slate-500 hover:bg-slate-100': !editor.isActive('bold') }"
        class="rounded p-1.5 transition-colors"
        title="Bold"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
      </button>

      <button 
        @click="editor.chain().focus().toggleItalic().run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('italic'), 'text-slate-500 hover:bg-slate-100': !editor.isActive('italic') }"
        class="rounded p-1.5 transition-colors"
        title="Italic"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
      </button>

      <button 
        @click="editor.chain().focus().toggleUnderline().run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('underline'), 'text-slate-500 hover:bg-slate-100': !editor.isActive('underline') }"
        class="rounded p-1.5 transition-colors"
        title="Underline"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>
      </button>

      <div class="h-4 w-px bg-slate-200 mx-1"></div>

      <button 
        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('heading', { level: 1 }), 'text-slate-500 hover:bg-slate-100': !editor.isActive('heading', { level: 1 }) }"
        class="rounded p-1.5 text-xs font-bold transition-colors"
        title="Heading 1"
      >
        H1
      </button>
      
      <button 
        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('heading', { level: 2 }), 'text-slate-500 hover:bg-slate-100': !editor.isActive('heading', { level: 2 }) }"
        class="rounded p-1.5 text-xs font-bold transition-colors"
        title="Heading 2"
      >
        H2
      </button>

      <div class="h-4 w-px bg-slate-200 mx-1"></div>

      <button 
        @click="editor.chain().focus().toggleBulletList().run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('bulletList'), 'text-slate-500 hover:bg-slate-100': !editor.isActive('bulletList') }"
        class="rounded p-1.5 transition-colors"
        title="Bullet List"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
      </button>

      <button 
        @click="editor.chain().focus().toggleOrderedList().run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive('orderedList'), 'text-slate-500 hover:bg-slate-100': !editor.isActive('orderedList') }"
        class="rounded p-1.5 transition-colors"
        title="Ordered List"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
      </button>

      <div class="h-4 w-px bg-slate-200 mx-1"></div>

      <button 
        @click="editor.chain().focus().setTextAlign('left').run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive({ textAlign: 'left' }), 'text-slate-500 hover:bg-slate-100': !editor.isActive({ textAlign: 'left' }) }"
        class="rounded p-1.5 transition-colors"
        title="Align Left"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
      </button>

      <button 
        @click="editor.chain().focus().setTextAlign('center').run()" 
        :class="{ 'bg-slate-200 text-slate-800': editor.isActive({ textAlign: 'center' }), 'text-slate-500 hover:bg-slate-100': !editor.isActive({ textAlign: 'center' }) }"
        class="rounded p-1.5 transition-colors"
        title="Align Center"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="10" x2="6" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="18" y1="18" x2="6" y2="18"></line></svg>
      </button>
      
    </div>

    <!-- Content Area -->
    <editor-content :editor="editor" class="p-2 min-h-[300px]" />
  </div>
</template>

<style>
/* Typography improvements for the editor content */
.ProseMirror p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}
</style>
