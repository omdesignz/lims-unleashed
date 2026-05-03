<template>
    <div class="flex items-start space-x-4 tiptap-content" v-if="editor">

      <div class="min-w-0 flex-1">
        <form action="#" class="relative">
          <div class="rounded-lg bg-white dark:bg-gray-800 outline outline-1 -outline-offset-1 outline-gray-300">

              <!-- Button Groups: Toolbar -->
                <div class="text-left py-2 m-2">
                    <div>
                    <!-- Button Group (small) -->

                    <div class="inline-flex">
                    <!-- Tooltip on Bottom -->
                    <div class="group relative inline-block">
                        <button
                        type="button"
                        class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400"
                        >
                        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6V21H8V6H2V4H16V6H10ZM18 14V21H16V14H13V12H21V14H18Z"></path></svg>
                        </button>
                        <div
                        class="invisible absolute left-1/2 top-full z-10 -ml-20 flex w-40 origin-top -translate-y-2 scale-75 flex-col-reverse items-center justify-center pt-0.5 opacity-75 transition duration-150 ease-out will-change-auto group-hover:visible group-hover:translate-y-0 group-hover:scale-100 group-hover:opacity-100"
                        >
                        <div
                            class="flex-none rounded-full bg-blue-900 px-2.5 py-2 text-center text-xs font-semibold text-white dark:bg-gray-700 grid grid-col gap-y-2"
                        >

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setParagraph().run()" :class="{ 'is-active': editor.isActive('paragraph') }">
                                <span class="sr-only">Paragraph</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M9 20v-6q-2.075 0-3.538-1.463T4 9q0-2.075 1.463-3.538T9 4h9v2h-2v14h-2V6h-3v14H9Z"/></svg>
                            </button>
                        </div>
                            
                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }">
                                <span class="sr-only">H1</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M5 17V7h2v4h4V7h2v10h-2v-4H7v4H5Zm12 0V9h-2V7h4v10h-2Z"/></svg>
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }">
                                <span class="sr-only">H2</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17V7h2v4h4V7h2v10H9v-4H5v4H3Zm10 0v-4q0-.825.588-1.413T15 11h4V9h-6V7h6q.825 0 1.413.588T21 9v2q0 .825-.588 1.413T19 13h-4v2h6v2h-8Z"/></svg>
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }">
                                <span class="sr-only">H3</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17V7h2v4h4V7h2v10H9v-4H5v4H3Zm10 0v-2h6v-2h-4v-2h4V9h-6V7h6q.825 0 1.413.588T21 9v6q0 .825-.588 1.413T19 17h-6Z"/></svg>
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 4 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 4 }) }">
                                <span class="sr-only">H4</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17V7h2v4h4V7h2v10H9v-4H5v4H3Zm15 0v-3h-5V7h2v5h3V7h2v5h2v2h-2v3h-2Z"/></svg>
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 5 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 5 }) }">
                                <span class="sr-only">H5</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17V7h2v4h4V7h2v10H9v-4H5v4H3Zm10 0v-2h6v-2h-6V7h8v2h-6v2h4q.825 0 1.413.588T21 13v2q0 .825-.588 1.413T19 17h-6Z"/></svg>
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().toggleHeading({ level: 6 }).run()" :class="{ 'is-active': editor.isActive('heading', { level: 6 }) }">
                                <span class="sr-only">H6</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 24 24"><path fill="currentColor" d="M3 17V7h2v4h4V7h2v10H9v-4H5v4H3Zm12 0q-.825 0-1.413-.588T13 15V9q0-.825.588-1.413T15 7h6v2h-6v2h4q.825 0 1.413.588T21 13v2q0 .825-.588 1.413T19 17h-4Zm0-4v2h4v-2h-4Z"/></svg>
                            </button>
                        </div>


                        </div>
                        <div
                            class="h-0 w-0 flex-none border-b-4 border-l-4 border-r-4 border-b-gray-800 border-l-transparent border-r-transparent dark:border-b-gray-700"
                            aria-hidden="true"
                        ></div>
                        </div>
                    </div>
                    <!-- END Tooltip on Bottom -->
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleBold().run()" :disabled="!editor.can().chain().focus().toggleBold().run()" :class="{ 'is-active': editor.isActive('bold') }">
                            <span class="sr-only">Bold</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M6.8 19V5h5.525q1.625 0 3 1T16.7 8.775q0 1.275-.575 1.963t-1.075.987q.625.275 1.388 1.025T17.2 15q0 2.225-1.625 3.113t-3.05.887H6.8Zm3.025-2.8h2.6q1.2 0 1.463-.613t.262-.887q0-.275-.263-.887T12.35 13.2H9.825v3Zm0-5.7h2.325q.825 0 1.2-.425t.375-.95q0-.6-.425-.975t-1.1-.375H9.825V10.5Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleItalic().run()" :disabled="!editor.can().chain().focus().toggleItalic().run()" :class="{ 'is-active': editor.isActive('italic') }">
                            <span class="sr-only">Italic</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M5 19v-2.5h4l3-9H8V5h10v2.5h-3.5l-3 9H15V19H5Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleUnderline().run()" :disabled="!editor.can().chain().focus().toggleUnderline().run()" :class="{ 'is-active': editor.isActive('underline') }">
                            <span class="sr-only">Underline</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M5 21v-2h14v2H5Zm7-4q-2.525 0-3.925-1.575t-1.4-4.175V3H9.25v8.4q0 1.4.7 2.275t2.05.875q1.35 0 2.05-.875t.7-2.275V3h2.575v8.25q0 2.6-1.4 4.175T12 17Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleSuperscript().run()" :class="{ 'is-active': editor.isActive('superscript') }">
                        <span class="sr-only">Superscript</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M19 9V7q0-.425.288-.713T20 6h2V5h-3V4h3q.425 0 .713.288T23 5v1q0 .425-.288.713T22 7h-2v1h3v1h-4ZM5.875 20l4.625-7.275L6.2 6h2.65l3.1 5h.1l3.075-5H17.8l-4.325 6.725L18.125 20H15.45l-3.4-5.425h-.1L8.55 20H5.875Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleSubscript().run()" :class="{ 'is-active': editor.isActive('subscript') }">
                            <span class="sr-only">Subscript</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M19 20v-2q0-.425.288-.713T20 17h2v-1h-3v-1h3q.425 0 .713.288T23 16v1q0 .425-.288.713T22 18h-2v1h3v1h-4ZM5.875 18l4.625-7.275L6.2 4h2.65l3.1 5h.1l3.075-5H17.8l-4.325 6.725L18.125 18H15.45l-3.4-5.425h-.1L8.55 18H5.875Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex" v-if="!editor.isActive('link')">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="setLink" :class="{ 'is-active': editor.isActive('link') }">
                            <span class="sr-only">Set Link</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M11 17H7q-2.075 0-3.538-1.463T2 12q0-2.075 1.463-3.538T7 7h4v2H7q-1.25 0-2.125.875T4 12q0 1.25.875 2.125T7 15h4v2Zm-3-4v-2h8v2H8Zm5 4v-2h4q1.25 0 2.125-.875T20 12q0-1.25-.875-2.125T17 9h-4V7h4q2.075 0 3.538 1.463T22 12q0 2.075-1.463 3.538T17 17h-4Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex" v-if="editor.isActive('link')">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().unsetLink().run()" :disabled="!editor.isActive('link')">
                            <span class="sr-only">Unset Link</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m19.25 16.45l-1.5-1.55q1-.275 1.625-1.063T20 12q0-1.25-.875-2.125T17 9h-4V7h4q2.075 0 3.538 1.463T22 12q0 1.425-.738 2.625T19.25 16.45ZM15.85 13l-2-2H16v2h-.15Zm3.95 9.6L1.4 4.2l1.4-1.4l18.4 18.4l-1.4 1.4ZM11 17H7q-2.075 0-3.538-1.463T2 12q0-1.725 1.05-3.075t2.7-1.775L7.6 9H7q-1.25 0-2.125.875T4 12q0 1.25.875 2.125T7 15h4v2Zm-3-4v-2h1.625l1.975 2H8Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'is-active': editor.isActive('orderedList') }">
                            <span class="sr-only">Ordered List</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 22v-1.5h2.5v-.75H4v-1.5h1.5v-.75H3V16h3q.425 0 .713.288T7 17v1q0 .425-.288.713T6 19q.425 0 .713.288T7 20v1q0 .425-.288.713T6 22H3Zm0-7v-2.75q0-.425.288-.713T4 11.25h1.5v-.75H3V9h3q.425 0 .713.288T7 10v1.75q0 .425-.288.713T6 12.75H4.5v.75H7V15H3Zm1.5-7V3.5H3V2h3v6H4.5ZM9 19v-2h12v2H9Zm0-6v-2h12v2H9Zm0-6V5h12v2H9Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'is-active': editor.isActive('bulletList') }">
                            <span class="sr-only">Bullet List</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M10 19q-.425 0-.713-.288T9 18q0-.425.288-.713T10 17h10q.425 0 .713.288T21 18q0 .425-.288.713T20 19H10Zm0-6q-.425 0-.713-.288T9 12q0-.425.288-.713T10 11h10q.425 0 .713.288T21 12q0 .425-.288.713T20 13H10Zm0-6q-.425 0-.713-.288T9 6q0-.425.288-.713T10 5h10q.425 0 .713.288T21 6q0 .425-.288.713T20 7H10ZM5 20q-.825 0-1.413-.588T3 18q0-.825.588-1.413T5 16q.825 0 1.413.588T7 18q0 .825-.588 1.413T5 20Zm0-6q-.825 0-1.413-.588T3 12q0-.825.588-1.413T5 10q.825 0 1.413.588T7 12q0 .825-.588 1.413T5 14Zm0-6q-.825 0-1.413-.588T3 6q0-.825.588-1.413T5 4q.825 0 1.413.588T7 6q0 .825-.588 1.413T5 8Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">

                      <div class="flex items-center">
                        <Menu as="div" class="relative inline-block text-left">
                      <div>
                        <MenuButton class="flex items-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 focus:outline-none">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 21V3h18v18H3ZM5 9h14V5H5v4Zm5.325 5h3.35v-3h-3.35v3Zm0 5h3.35v-3h-3.35v3ZM5 14h3.325v-3H5v3Zm10.675 0H19v-3h-3.325v3ZM5 19h3.325v-3H5v3Zm10.675 0H19v-3h-3.325v3Z"/></svg>
                        </MenuButton>
                      </div>

                        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                          <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
                                  
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M4 3H20C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3ZM5 5V19H19V5H5ZM11 11V7H13V11H17V13H13V17H11V13H7V11H11Z"></path></svg>
                                  Insert
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().addColumnBefore().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H14C13.4477 21 13 20.5523 13 20V4C13 3.44772 13.4477 3 14 3H20ZM19 5H15V19H19V5ZM6 7C8.76142 7 11 9.23858 11 12C11 14.7614 8.76142 17 6 17C3.23858 17 1 14.7614 1 12C1 9.23858 3.23858 7 6 7ZM7 9H5V10.999L3 11V13L5 12.999V15H7V12.999L9 13V11L7 10.999V9Z"></path></svg>
                                  Add Column Before
                                </a>
                              </MenuItem>
                            </div>
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().addColumnAfter().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M10 3C10.5523 3 11 3.44772 11 4V20C11 20.5523 10.5523 21 10 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H10ZM9 5H5V19H9V5ZM18 7C20.7614 7 23 9.23858 23 12C23 14.7614 20.7614 17 18 17C15.2386 17 13 14.7614 13 12C13 9.23858 15.2386 7 18 7ZM19 9H17V10.999L15 11V13L17 12.999V15H19V12.999L21 13V11L19 10.999V9Z"></path></svg>
                                  Add Column After
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().deleteColumn().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3C12.5523 3 13 3.44772 13 4L12.9998 11.9998C13.8355 11.372 14.8743 11 16 11C18.7614 11 21 13.2386 21 16C21 18.7614 18.7614 21 16 21C14.9681 21 14.0092 20.6874 13.2129 20.1518L13 20C13 20.5523 12.5523 21 12 21H6C5.44772 21 5 20.5523 5 20V4C5 3.44772 5.44772 3 6 3H12ZM11 5H7V19H11V5ZM19 15H13V17H19V15Z"></path></svg>
                                  Delete Column
                                </a>
                              </MenuItem>
                            </div>
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().addRowBefore().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 13C20.5523 13 21 13.4477 21 14V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V14C3 13.4477 3.44772 13 4 13H20ZM19 15H5V19H19V15ZM12 1C14.7614 1 17 3.23858 17 6C17 8.76142 14.7614 11 12 11C9.23858 11 7 8.76142 7 6C7 3.23858 9.23858 1 12 1ZM13 3H11V4.999L9 5V7L11 6.999V9H13V6.999L15 7V5L13 4.999V3Z"></path></svg>
                                  Add Row Before
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().addRowAfter().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M12 13C14.7614 13 17 15.2386 17 18C17 20.7614 14.7614 23 12 23C9.23858 23 7 20.7614 7 18C7 15.2386 9.23858 13 12 13ZM13 15H11V16.999L9 17V19L11 18.999V21H13V18.999L15 19V17L13 16.999V15ZM20 3C20.5523 3 21 3.44772 21 4V10C21 10.5523 20.5523 11 20 11H4C3.44772 11 3 10.5523 3 10V4C3 3.44772 3.44772 3 4 3H20ZM5 5V9H19V5H5Z"></path></svg>
                                  Add Row After
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().deleteRow().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 5C20.5523 5 21 5.44772 21 6V12C21 12.5523 20.5523 13 20 13C20.628 13.8355 21 14.8743 21 16C21 18.7614 18.7614 21 16 21C13.2386 21 11 18.7614 11 16C11 14.8743 11.372 13.8355 11.9998 12.9998L4 13C3.44772 13 3 12.5523 3 12V6C3 5.44772 3.44772 5 4 5H20ZM13 15V17H19V15H13ZM19 7H5V11H19V7Z"></path></svg>
                                  Delete Row
                                </a>
                              </MenuItem>
                            </div>
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().mergeCells().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20ZM11 5H5V10.999H7V9L10 12L7 15V13H5V19H11V17H13V19H19V13H17V15L14 12L17 9V10.999H19V5H13V7H11V5ZM13 13V15H11V13H13ZM13 9V11H11V9H13Z"></path></svg>
                                  Merge Cells
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().splitCell().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20ZM11 5H5V19H11V15H13V19H19V5H13V9H11V5ZM15 9L18 12L15 15V13H9V15L6 12L9 9V11H15V9Z"></path></svg>
                                  Split Cell
                                </a>
                              </MenuItem>
                            </div>
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().toggleHeaderColumn().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M12 5V19H19V5H12ZM4 3H20C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3Z"></path></svg>
                                  Toggle Header Column
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().toggleHeaderRow().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500 rotate-90" viewBox="0 0 24 24"><path fill="currentColor" d="M12 5V19H19V5H12ZM4 3H20C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3Z"></path></svg>
                                  Toggle Header Row
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().toggleHeaderCell().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M14 6H16V8H21C21.5523 8 22 8.44772 22 9V16.5L16 13L16.0359 21.0622L18.2592 18.9129L20.041 22H9C8.44772 22 8 21.5523 8 21V16H6V14H8V9C8 8.44772 8.44772 8 9 8H14V6ZM22 17.338V21C22 21.107 21.9832 21.2101 21.9521 21.3068L19.9913 17.9129L22 17.338ZM4 14V16H2V14H4ZM4 10V12H2V10H4ZM4 6V8H2V6H4ZM4 2V4H2V2H4ZM8 2V4H6V2H8ZM12 2V4H10V2H12ZM16 2V4H14V2H16Z"></path></svg>
                                  Toggle Header Cell
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().mergeOrSplit().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20ZM11 5H5V10.999H7V9L10 12L7 15V13H5V19H11V17H13V19H19V13H17V15L14 12L17 9V10.999H19V5H13V7H11V5ZM13 13V15H11V13H13ZM13 9V11H11V9H13Z"></path></svg>
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M20 3C20.5523 3 21 3.44772 21 4V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V4C3 3.44772 3.44772 3 4 3H20ZM11 5H5V19H11V15H13V19H19V5H13V9H11V5ZM15 9L18 12L15 15V13H9V15L6 12L9 9V11H15V9Z"></path></svg>

                                  Merge or Split
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().setCellAttribute('colspan', 2).run()">
                                  <HeartIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                                  Set Cell Attribute
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().fixTables().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M11.602 13.7599L13.014 15.1719L21.4795 6.7063L22.8938 8.12051L13.014 18.0003L6.65 11.6363L8.06421 10.2221L10.189 12.3469L11.6025 13.7594L11.602 13.7599ZM11.6037 10.9322L16.5563 5.97949L17.9666 7.38977L13.014 12.3424L11.6037 10.9322ZM8.77698 16.5873L7.36396 18.0003L1 11.6363L2.41421 10.2221L3.82723 11.6352L3.82604 11.6363L8.77698 16.5873Z"></path></svg>
                                  Fix Tables
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().goToNextCell().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M10.0859 12.0001L5.29297 16.793L6.70718 18.2072L12.9143 12.0001L6.70718 5.79297L5.29297 7.20718L10.0859 12.0001ZM17.0001 6.00008L17.0001 18.0001H15.0001L15.0001 6.00008L17.0001 6.00008Z"></path></svg>
                                  Go to Next Cell
                                </a>
                              </MenuItem>
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().goToPreviousCell().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M13.9142 12.0001L18.7071 7.20718L17.2929 5.79297L11.0858 12.0001L17.2929 18.2072L18.7071 16.793L13.9142 12.0001ZM7 18.0001V6.00008H9V18.0001H7Z"></path></svg>
                                  Go to Previous Cell
                                </a>
                              </MenuItem>
                            </div>
                            <div class="py-1">
                              <MenuItem v-slot="{ active }">
                                <a as="button" href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm']" @click="editor.chain().focus().deleteTable().run()">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 24 24"><path fill="currentColor" d="M17 6H22V8H20V21C20 21.5523 19.5523 22 19 22H5C4.44772 22 4 21.5523 4 21V8H2V6H7V3C7 2.44772 7.44772 2 8 2H16C16.5523 2 17 2.44772 17 3V6ZM18 8H6V20H18V8ZM9 4V6H15V4H9Z"></path></svg>
                                  Delete Table
                                </a>
                              </MenuItem>
                            </div>
                          </MenuItems>
                        </transition>
                      </Menu>
                        
                      </div>

                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ 'is-active': editor.isActive('blockquote') }">
                            <span class="sr-only">Block Quote</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m15 17l2-4h-4V6h7v7l-2 4h-3Zm-9 0l2-4H4V6h7v7l-2 4H6Z"/></svg>
                        </button>
                    </div>      

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().setFontFamily('cursive').run()" :class="{ 'is-active': editor.isActive('textStyle', { fontFamily: 'cursive' }) }">
                            <span class="sr-only">Font Family</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M6.4 18h2.1l1.1-3.05h4.8L15.5 18h2.1L13.05 6h-2.1L6.4 18Zm3.8-4.8l1.75-4.95h.1l1.75 4.95h-3.6ZM2 22V2h20v20H2Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleStrike().run()" :disabled="!editor.can().chain().focus().toggleStrike().run()" :class="{ 'is-active': editor.isActive('strike') }">
                            <span class="sr-only">Strike</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 14v-2h20v2H2Zm8.5-4V7H5V4h14v3h-5.5v3h-3Zm0 10v-4h3v4h-3Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().unsetAllMarks().run()">
                            <span class="sr-only">Clear Marks</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M16.1 12.8L8.25 4.9L12 2l9 7l-4.9 3.8Zm2.875 2.85l-1.45-1.45l1.825-1.4L21 14.05l-2.025 1.6Zm.825 6.45l-4-4l-3.8 2.95l-9-7l1.65-1.25L12 18.5l2.35-1.825l-1.425-1.4L12 16L3 9l2.075-1.625l-3.7-3.65L2.8 2.3l18.4 18.4l-1.4 1.4Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().clearNodes().run()">
                            <span class="sr-only">Clear Nodes</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m13.2 10.35l-2.325-2.325L7.85 5H20v3h-5.8l-1 2.35Zm6.6 12.25l-8.3-8.3l-2 4.7H6.225L9.2 12L1.4 4.2l1.4-1.4l18.4 18.4l-1.4 1.4Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().chain().focus().undo().run()">
                            <span class="sr-only">Undo</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M7 19v-2h7.1q1.575 0 2.738-1T18 13.5q0-1.5-1.163-2.5T14.1 10H7.8l2.6 2.6L9 14L4 9l5-5l1.4 1.4L7.8 8h6.3q2.425 0 4.163 1.575T20 13.5q0 2.35-1.738 3.925T14.1 19H7Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().chain().focus().redo().run()">
                            <span class="sr-only">Redo</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M9.9 19q-2.425 0-4.163-1.575T4 13.5q0-2.35 1.738-3.925T9.9 8h6.3l-2.6-2.6L15 4l5 5l-5 5l-1.4-1.4l2.6-2.6H9.9q-1.575 0-2.738 1T6 13.5Q6 15 7.163 16T9.9 17H17v2H9.9Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().setHardBreak().run()">
                            <span class="sr-only">Hard Break</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5"><path fill="currentColor" d="M15 18H16.5C17.8807 18 19 16.8807 19 15.5C19 14.1193 17.8807 13 16.5 13H3V11H16.5C18.9853 11 21 13.0147 21 15.5C21 17.9853 18.9853 20 16.5 20H15V22L11 19L15 16V18ZM3 4H21V6H3V4ZM9 18V20H3V18H9Z"></path></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().toggleCodeBlock().run()" :class="{ 'is-active': editor.isActive('codeBlock') }">
                            <span class="sr-only">Code Block</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m9.6 15.6l1.4-1.425L8.825 12L11 9.825L9.6 8.4L6 12l3.6 3.6Zm4.8 0L18 12l-3.6-3.6L13 9.825L15.175 12L13 14.175l1.4 1.425ZM3 21V3h18v18H3Z"/></svg>
                        </button>
                    </div>

                    <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().setHorizontalRule().run()">
                            <span class="sr-only">Horizontal Rule</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M4 13v-2h16v2H4Z"/></svg>
                        </button>
                    </div>

                    <Popover class="relative inline-flex h-8 w-8 text-sm">
                      <PopoverButton class="inline-flex items-center gap-x-1 text-sm/6 font-semibold text-gray-400 outline-none hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white">
                        <span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 24v-4h20v4H2Zm8.6-16l5.4 5.425l-4 4q-.6.6-1.413.6t-1.412-.6L8.5 18h-5l3.15-3.125q-.6-.6-.625-1.438T6.6 12l4-4ZM12 6.575L16 2.6q.6-.6 1.413-.6t1.412.6l2.6 2.575q.6.6.6 1.413T21.425 8l-4 4L12 6.575Z"/></svg>
                        </span>
                        <!-- <ChevronDownIcon class="size-5" aria-hidden="true" /> -->
                      </PopoverButton>

                      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                        <PopoverPanel class="absolute left-1/2 z-10 mt-5 flex w-screen max-w-max -translate-x-1/2 px-4">
                          <div class="max-w-md flex-auto overflow-hidden rounded-3xl text-sm/6">
                            <div class="p-4">
                              <color-picker is-widget theme="black" @pureColorChange="(e)=> editor.chain().focus().toggleHighlight({ color: e }).run()" v-model:pure-color="editor.getAttributes('textStyle').color" format="hex" shape="circle" lang="En"/>  
                            </div>
                            <div class="grid grid-cols-2 divide-x divide-gray-900/5 bg-gray-50">
                              <!-- Footer -->
                            </div>
                          </div>
                        </PopoverPanel>
                      </transition>
                    </Popover>

                    <Popover class="relative inline-flex h-8 w-8 text-sm">
                      <PopoverButton class="inline-flex items-center gap-x-1 text-sm/6 font-semibold text-gray-400 outline-none hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white">
                        <span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 24v-4h20v4H2Zm3.5-7l5.25-14h2.5l5.25 14h-2.4l-1.25-3.6H9.2L7.9 17H5.5Zm4.4-5.6h4.2l-2.05-5.8h-.1L9.9 11.4Z"/></svg>

                        </span>
                        <!-- <ChevronDownIcon class="size-5" aria-hidden="true" /> -->
                      </PopoverButton>

                      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
                        <PopoverPanel class="absolute left-1/2 z-10 mt-5 flex w-screen max-w-max -translate-x-1/2 px-4">
                          <div class="max-w-md flex-auto overflow-hidden rounded-3xl text-sm/6">
                            <div class="p-4">
                              <color-picker is-widget theme="black" @pureColorChange="(e)=> editor.chain().focus().setColor(e).run()" format="hex" shape="circle" lang="En"/>  
                            </div>
                            <div class="grid grid-cols-2 divide-x divide-gray-900/5 bg-gray-50">
                              <!-- Footer -->
                            </div>
                          </div>
                        </PopoverPanel>
                      </transition>
                    </Popover>

                    <!-- <div class="inline-flex">
                        <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 hover:text-blue-900 transform transition-all duration-200 hover:scale-150 dark:hover:text-white" @click="editor.chain().focus().setColor('#958DF1').run()" :class="{ 'is-active': editor.isActive('textStyle', { color: '#958DF1' })}">
                            <span class="sr-only">Font Color</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 24v-4h20v4H2Zm3.5-7l5.25-14h2.5l5.25 14h-2.4l-1.25-3.6H9.2L7.9 17H5.5Zm4.4-5.6h4.2l-2.05-5.8h-.1L9.9 11.4Z"/></svg>
                        </button>
                    </div> -->


                    <div class="inline-flex">
                    <!-- Tooltip on Bottom -->
                    <div class="group relative inline-block">
                        <button
                        type="button"
                        class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 group-hover:text-blue-900 dark:group-hover:text-white"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg>
                        
                        <!-- <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6V21H8V6H2V4H16V6H10ZM18 14V21H16V14H13V12H21V14H18Z"></path></svg> -->
                        </button>
                        <div
                        class="invisible absolute left-1/2 top-full z-10 -ml-20 flex w-40 origin-top -translate-y-2 scale-75 flex-col-reverse items-center justify-center pt-0.5 opacity-75 transition duration-150 ease-out will-change-auto group-hover:visible group-hover:translate-y-0 group-hover:scale-100 group-hover:opacity-100"
                        >
                        <div
                            class="flex-none rounded-full bg-blue-900 px-2.5 py-2 text-center text-xs font-semibold text-white dark:bg-gray-700 grid grid-col gap-y-2"
                        >

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }">
                                <span class="sr-only">Text Align Left</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg>
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }">
                                <span class="sr-only">Text Align Center</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM5 19H19V21H5V19ZM3 14H21V16H3V14ZM5 9H19V11H5V9Z"></path></svg>
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M11 22v-5H6v-3h5v-4H3V7h8V2h2v5h8v3h-8v4h5v3h-5v5h-2Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }">
                                <span class="sr-only">Text Align Right</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM7 19H21V21H7V19ZM3 14H21V16H3V14ZM7 9H21V11H7V9Z"></path></svg>
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M20 22V2h2v20h-2ZM8 17v-3h10v3H8Zm-6-7V7h16v3H2Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setTextAlign('justify').run()" :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }">
                                <span class="sr-only">Text Align Justify</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H21V21H3V19ZM3 14H21V16H3V14ZM3 9H21V11H3V9Z"></path></svg>
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M20 22V2h2v20h-2ZM2 22V2h2v20H2Zm13-5V7h3v10h-3Zm-9 0V7h3v10H6Z"/></svg> -->
                            </button>
                        </div>


                        </div>
                        <div
                            class="h-0 w-0 flex-none border-b-4 border-l-4 border-r-4 border-b-gray-800 border-l-transparent border-r-transparent dark:border-b-gray-700"
                            aria-hidden="true"
                        ></div>
                        </div>
                    </div>
                    <!-- END Tooltip on Bottom -->
                    </div>



                    <div class="inline-flex">
                    <!-- Line Height Tooltip on Bottom -->
                    <div class="group relative inline-block">
                        <button
                        type="button"
                        class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-gray-400 group-hover:text-blue-900 dark:group-hover:text-white"
                        >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M11 4H21V6H11V4ZM6 7V11H4V7H1L5 3L9 7H6ZM6 17H9L5 21L1 17H4V13H6V17ZM11 18H21V20H11V18ZM9 11H21V13H9V11Z"></path></svg>
                        
                        <!-- <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6V21H8V6H2V4H16V6H10ZM18 14V21H16V14H13V12H21V14H18Z"></path></svg> -->
                        </button>
                        <div
                        class="invisible absolute left-1/2 top-full z-10 -ml-20 flex w-40 origin-top -translate-y-2 scale-75 flex-col-reverse items-center justify-center pt-0.5 opacity-75 transition duration-150 ease-out will-change-auto group-hover:visible group-hover:translate-y-0 group-hover:scale-100 group-hover:opacity-100"
                        >
                        <div
                            class="flex-none rounded-full bg-blue-900 px-2.5 py-2 text-center text-xs font-semibold text-white dark:bg-gray-700 grid grid-col gap-y-2"
                        >

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-none').run()">
                                <span class="sr-only">Text Align Left</span>
                                1.0
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-tight').run()">
                                <span class="sr-only">Text Align Left</span>
                                1.25
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-snug').run()">
                                <span class="sr-only">Text Align Left</span>
                                1.375
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-normal').run()">
                                <span class="sr-only">Text Align Left</span>
                                1.5
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-relaxed').run()">
                                <span class="sr-only">Text Align Left</span>
                                1.625
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>

                        <div class="inline-flex">
                            <button type="button" class="inline-flex h-8 w-8 text-sm items-center justify-center rounded-full text-white hover:text-white transform transition-all duration-200 hover:scale-150" @click="editor.chain().focus().setLineHeight('leading-loose').run()">
                                <span class="sr-only">Text Align Left</span>
                                2.0
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4H21V6H3V4ZM3 19H17V21H3V19ZM3 14H21V16H3V14ZM3 9H17V11H3V9Z"></path></svg> -->
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M2 22V2h2v20H2Zm4-5v-3h10v3H6Zm0-7V7h16v3H6Z"/></svg> -->
                            </button>
                        </div>


                        </div>
                        <div
                            class="h-0 w-0 flex-none border-b-4 border-l-4 border-r-4 border-b-gray-800 border-l-transparent border-r-transparent dark:border-b-gray-700"
                            aria-hidden="true"
                        ></div>
                        </div>
                    </div>
                    <!-- END Tooltip on Bottom -->
                    </div>

                    
                    <div class="inline-flex">

                                          <!-- Dropdowns: With Header and Badges -->
                  <div class="flex justify-end">
                    <!-- Dropdown Container -->
                    <Menu as="div" class="relative inline-block">
                      <!-- Dropdown Toggle Button -->
                      <MenuButton
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold leading-5 text-gray-400 hover:text-blue-900 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white"
                      >
                        <!-- <span>John Doe</span> -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M21 15V18H24V20H21V23H19V20H16V18H19V15H21ZM21.0082 3C21.556 3 22 3.44495 22 3.9934V13H20V5H4V18.999L14 9L17 12V14.829L14 11.8284L6.827 19H14V21H2.9918C2.44405 21 2 20.5551 2 20.0066V3.9934C2 3.44476 2.45531 3 2.9918 3H21.0082ZM8 7C9.10457 7 10 7.89543 10 9C10 10.1046 9.10457 11 8 11C6.89543 11 6 10.1046 6 9C6 7.89543 6.89543 7 8 7Z"></path></svg>
                        <svg
                          class="hi-mini hi-chevron-down inline-block size-4 opacity-50"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden="true"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </MenuButton>
                      <!-- END Dropdown Toggle Button -->

                      <!-- Dropdown -->
                      <transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-90"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-90"
                      >
                        <MenuItems
                          class="absolute right-0 z-10 mt-2 w-96 origin-top-right rounded-lg shadow-xl focus:outline-none dark:shadow-gray-900"
                        >
                          <div
                            class="divide-y divide-gray-100 rounded-lg bg-white ring-1 ring-black/5 dark:divide-gray-700 dark:bg-gray-800 dark:ring-gray-700"
                          >
                            <div class="flex items-center gap-3 px-5 py-3">
                              <img
                                src="https://cdn.tailkit.com/media/placeholders/avatar-iFgRcqHznqg-160x160.jpg"
                                alt="User Avatar"
                                class="inline-block size-10 flex-none rounded-full"
                              />
                              <div class="grow text-sm">
                                <a
                                  href="#"
                                  class="font-semibold text-gray-600 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-400"
                                  >John Doe</a
                                >
                                <p
                                  class="break-all text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                  john.doe@example.com
                                </p>
                              </div>
                            </div>
                            
                            <div class="space-y-1 p-2.5">

                              <div>
                                <label for="title" class="block text-sm/6 font-medium text-gray-900">Title</label>
                                <div class="mt-2">
                                  <input v-model="title" type="text" name="title" id="title" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" placeholder="you@example.com" />
                                </div>
                              </div>
                              
                              <div>
                                <label for="src" class="block text-sm/6 font-medium text-gray-900">Source</label>
                                <div class="mt-2">
                                  <input v-model="src" type="text" name="src" id="src" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" placeholder="" />
                                </div>
                              </div>

                              <div>
                                <label for="alt" class="block text-sm/6 font-medium text-gray-900">Alt</label>
                                <div class="mt-2">
                                  <input v-model="alt" type="text" name="alt" id="alt" class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" placeholder="you@example.com" />
                                </div>
                              </div>

                              
                            </div>
                            <div class="space-y-1 p-2.5">
                              <button type="button" @click="setImage(title, src, alt)" class="group flex items-center justify-between gap-2 rounded-lg border border-transparent px-2.5 py-2 text-sm font-medium">
                                Add Image
                              </button>
                            </div>
                          </div>
                        </MenuItems>
                      </transition>
                    </Menu>
                    <!-- END Dropdown Container -->
                  </div>
                  <!-- END Dropdowns: With Header and Badges -->
                   
                    </div>



                    <!-- END Button Group (small) -->
                    </div>

                    <div>
                </div>
                <!-- END Button Groups: Toolbar -->
                </div>

            <editor-content :editor="editor" />
  
            <!-- Spacer element to match the height of the toolbar -->
            <div class="py-2" aria-hidden="true">
              <!-- Matches height of button in toolbar (1px border + 36px content height) -->
              <div class="py-px">
                <div class="h-9" />
              </div>
            </div>
          </div>
  
          <div class="absolute inset-x-0 bottom-0 flex justify-between py-2 pl-3 pr-2">
            <div class="flex items-center space-x-5">

              <div class="flex items-center">
                <button type="button" class="-m-2.5 flex size-10 items-center justify-center rounded-full text-gray-400 hover:text-gray-500">
                  <PaperClipIcon class="size-5" aria-hidden="true" />
                  <span class="sr-only">Attach a file</span>
                </button>
              </div>
              <div class="flex items-center">
                <Listbox as="div" v-model="selected">
                  <ListboxLabel class="sr-only">Your mood</ListboxLabel>
                  <div class="relative">
                    <ListboxButton class="relative -m-2.5 flex size-10 items-center justify-center rounded-full text-gray-400 hover:text-gray-500">
                      <span class="flex items-center justify-center">
                        <span v-if="selected.value === null">
                          <FaceSmileIcon class="size-5 shrink-0" aria-hidden="true" />
                          <span class="sr-only">Add your mood</span>
                        </span>
                        <span v-if="!(selected.value === null)">
                          <span :class="[selected.bgColor, 'flex size-8 items-center justify-center rounded-full']">
                            <component :is="selected.icon" class="size-5 shrink-0 text-white" aria-hidden="true" />
                          </span>
                          <span class="sr-only">{{ selected.name }}</span>
                        </span>
                      </span>
                    </ListboxButton>
  
                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                      <ListboxOptions class="absolute z-10 -ml-6 mt-1 w-60 rounded-lg bg-white py-3 text-base shadow outline outline-1 outline-black/5 sm:ml-auto sm:w-64 sm:text-sm">
                        <ListboxOption as="template" v-for="mood in moods" :key="mood.value" :value="mood" v-slot="{ active }">
                          <li :class="[active ? 'relative bg-gray-100 outline-none' : 'bg-white', 'cursor-default select-none px-3 py-2']">
                            <div class="flex items-center">
                              <div :class="[mood.bgColor, 'flex size-8 items-center justify-center rounded-full']">
                                <component :is="mood.icon" :class="[mood.iconColor, 'size-5 shrink-0']" aria-hidden="true" />
                              </div>
                              <span class="ml-3 block truncate font-medium">{{ mood.name }}</span>
                            </div>
                          </li>
                        </ListboxOption>
                      </ListboxOptions>
                    </transition>
                  </div>
                </Listbox>
              </div>
            </div>
            <div class="shrink-0">

                <div :class="{'character-count': true, 'character-count--warning': editor.storage.characterCount.characters() === limit}" v-if="editor" class="flex items-center">
                    <svg
                        height="20"
                        width="20"
                        viewBox="0 0 20 20"
                    >
                        <circle
                        r="10"
                        cx="10"
                        cy="10"
                        fill="#e9ecef"
                        />
                        <circle
                        r="5"
                        cx="10"
                        cy="10"
                        fill="transparent"
                        stroke="currentColor"
                        stroke-width="10"
                        :stroke-dasharray="`calc(${percentage} * 31.4 / 100) 31.4`"
                        transform="rotate(-90) translate(-20)"
                        />
                        <circle
                        r="6"
                        cx="10"
                        cy="10"
                        fill="white"
                        />
                    </svg>

                    {{ editor.storage.characterCount.characters() }} / {{ limit }} caracteres
                    <br>
                    {{ editor.storage.characterCount.words() }} palavras
                    </div>
              <!-- <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Post</button> -->
            </div>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script setup>
  import {onBeforeUnmount, onMounted, ref, watch, computed} from 'vue'
  import { Editor, EditorContent } from '@tiptap/vue-3'
  // import CollaborationCursor from '@tiptap/extension-collaboration-cursor';
  import { LineHeight } from '@/Extensions/Tiptap/line-height'
  import ListKeymap from '@tiptap/extension-list-keymap';
  import Placeholder from '@tiptap/extension-placeholder';
  import fileHandler from '@/Extensions/Tiptap/file-handler'
  import {DragHandleNode} from '@/Extensions/Tiptap/DragHandleNode'
  import Image from '@/Extensions/Tiptap/image'
  import Underline from '@tiptap/extension-underline'
  import Highlight from '@tiptap/extension-highlight'
  import TextAlign from '@tiptap/extension-text-align'
  import Table from '@tiptap/extension-table'
  import TableCell from '@tiptap/extension-table-cell'
  import TableHeader from '@tiptap/extension-table-header'
  import TableRow from '@tiptap/extension-table-row'
  import Superscript from '@tiptap/extension-superscript'
  import Subscript from '@tiptap/extension-subscript'
  import TextStyle from '@tiptap/extension-text-style'
  import FontFamily from '@tiptap/extension-font-family'
  import { Color } from '@tiptap/extension-color'
  import { ColorPicker } from "vue3-colorpicker";
  import CharacterCount from '@tiptap/extension-character-count'
  import Link from '@tiptap/extension-link'
  import StarterKit from '@tiptap/starter-kit'
  // import { CustomHighlight } from '@/Extensions/Tiptap/CustomHighlight'
  import { fileToBase64 } from '@/Extensions/Tiptap/utils'
  import {
    FaceFrownIcon,
    FaceSmileIcon,
    FireIcon,
    HandThumbUpIcon,
    HeartIcon,
    PaperClipIcon,
    XMarkIcon,
    ArchiveBoxIcon,
    ArrowRightCircleIcon,
    ChevronDownIcon,
    DocumentDuplicateIcon,
    PencilSquareIcon,
    TrashIcon,
    UserPlusIcon,
  } from '@heroicons/vue/20/solid'
  import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions, Menu, MenuButton, MenuItem, MenuItems, Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { set } from '@vueuse/core/index.cjs';
  
  const moods = [
    { name: 'Excited', value: 'excited', icon: FireIcon, iconColor: 'text-white', bgColor: 'bg-red-500' },
    { name: 'Loved', value: 'loved', icon: HeartIcon, iconColor: 'text-white', bgColor: 'bg-pink-400' },
    { name: 'Happy', value: 'happy', icon: FaceSmileIcon, iconColor: 'text-white', bgColor: 'bg-green-400' },
    { name: 'Sad', value: 'sad', icon: FaceFrownIcon, iconColor: 'text-white', bgColor: 'bg-yellow-400' },
    { name: 'Thumbsy', value: 'thumbsy', icon: HandThumbUpIcon, iconColor: 'text-white', bgColor: 'bg-blue-500' },
    { name: 'I feel nothing', value: null, icon: XMarkIcon, iconColor: 'text-gray-400', bgColor: 'bg-transparent' },
  ]
  
  const selected = ref(moods[5])

  const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
  });

  const emit = defineEmits([
    'update:modelValue'
  ]);

  const editor = ref(null);
  const limit = ref(280);

  const showTextColorSelection = ref(false);

  const percentage = computed(() => {
    return Math.round((100 / limit.value) * editor.value?.storage.characterCount.characters())
  })

  const setImage = (title = '', src, alt = '') => {
    if (!src) {
      return;
    }

    editor.value.chain().focus().setImage({ src, alt, title }).run();
    
  }

  const data = ref(props.modelValue);

  const setLink = () => {
      const previousUrl = editor.value.getAttributes('link').href
      const url = window.prompt('URL', previousUrl)

      // cancelled
      if (url === null) {
        return
      }

      // empty
      if (url === '') {
        editor.value
          .chain()
          .focus()
          .extendMarkRange('link')
          .unsetLink()
          .run()

        return
      }

      // update link
      editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run()
    }

    onMounted(() => {
    editor.value = new Editor({
      extensions: [
      StarterKit,
      Underline,
      // Highlight,
      // ImageExtension,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Image.configure({
        inline: true,
        allowBase64: true,
        draggable: true,
        HTMLAttributes: {
          class: 'rounded-md'
        }
      }),
      // CustomHighlight.configure({ defaultColor: '#ffff00' }),
      Highlight.configure({ multicolor: true }),
      Table.configure({
        HTMLAttributes: { class: 'table-auto border-collapse' },
          resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        Superscript,
        Subscript,
        FontFamily,
        TextStyle,
        // DragHandleNode,
        ListKeymap,
        Placeholder.configure({
          placeholder: 'Escreva aqui...',
          showOnlyWhenEditable: true,
          showOnlyCurrent: false,
          emptyEditorClass: 'placeholder-shown',
          emptyNodeClass: 'placeholder-shown',
          emptyNodeText: 'Escreva aqui...',
        }),
        LineHeight.configure({
          types: ['paragraph', 'heading', 'orderedList', 'bulletedList'], // Specify where line-height applies
        }),
        // Collaboration,
        // CollaborationCursor,
        Color,
        // Document,
        fileHandler.configure({
          allowBase64: true,
          allowedMimeTypes: ['image/jpeg', 'image/png'],
          maxFileSize: 5 * 1024 * 1024, // 5 MB
          onPaste: (editor, files, pasteContent) => {
            files.forEach(async file => {
              const src = await fileToBase64(file)
              editor.commands.insertContent({
                type: 'image',
                attrs: { src }
              })
            })

            // console.log(editor)

            // const url = window.prompt('URL')



            // if (url) {
            //   editor.value?.chain().focus().setImage({ src: url }).run()
            // }

          },
          onDrop: (editor, files, pos) => {
            files.forEach(async file => {
              const src = await fileToBase64(file)

              editor.commands.insertContentAt(pos, {
                type: 'image',
                attrs: { src }
              })
            })

          },
          onValidationError: (errors) => {
            // Validation feedback is handled by the surrounding workflow UI.
          },
        }),
        CharacterCount.configure({limit: limit.value, mode: 'nodeSize'}),
        Link.configure({
          protocols: ['ftp', 'mailto'],
          HTMLAttributes: {
            class: 'text-blue-900 underline',
          },
        })
      ],
      // content: props.modelValue || '<p></p>',
      content: data.value,
      editorProps: {
        attributes: {
          class: 'prose dark:prose-invert prose-sm sm:prose-base lg:prose-lg xl:prose-2xl m-5 focus:outline-none leading-normal',
        },
      },
      parseOptions: {
        preserveWhitespace: false,
      },
      onUpdate: () => {
        // HTML
        emit('update:modelValue', editor.value.getHTML())

        // JSON
        // emits('update:modelValue', editor.value.getJSON())
      },
    })
  });

  onBeforeUnmount(() => {
    if (editor.value) {
      editor.value.destroy();
    }
  });
  
  watch(() => props.modelValue, (newValue) => {
      if (editor.value && newValue !== editor.value.getHTML()) {
        editor.value.commands.setContent(newValue || '<p></p>');
      }
    });
  </script>

<style>
.tiptap :first-child {
	 margin-top: 0;
}
/* Character count */
 .character-count {
	 align-items: center;
	 color: gray;
	 display: flex;
	 font-size: 0.75rem;
	 gap: 0.5rem;
	 margin: 0.5rem;
}
 .character-count svg {
	 color: #1e293b;
}
 .character-count--warning, .character-count--warning svg {
	 color: red;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
	.character-count {
		 color: lightgray;
	}
	.character-count svg {
		 color: #94a3b8; /* Adjusted for better visibility in dark mode */
	}
	.character-count--warning,
	.character-count--warning svg {
		 color: #f87171; /* Slightly lighter red for better contrast */
	}
}

.custom-image-node img {
  cursor: pointer;
}

/* .tiptap-content {
  line-height: 1.5 !important;
}

.tiptap-content p {
  line-height: 1.5 !important;
} */

/* Table-specific styling */
table {
	 border-collapse: collapse;
	 margin: 0;
	 overflow: hidden;
	 table-layout: fixed;
	 width: 100%;
}
 table td, table th {
	 border: 1px solid lightgray;
	 box-sizing: border-box;
	 min-width: 1em;
	 padding: 6px 8px;
	 position: relative;
	 vertical-align: top;
}
 table td > *, table th > * {
	 margin-bottom: 0;
}
 table th {
	 background-color: lightgray;
	 font-weight: bold;
	 text-align: left;
}
 table .selectedCell:after {
	 background: darkgray;
	 content: "";
	 left: 0;
	 right: 0;
	 top: 0;
	 bottom: 0;
	 pointer-events: none;
	 position: absolute;
	 z-index: 2;
}
 table .column-resize-handle {
	 background-color: purple;
	 bottom: -2px;
	 pointer-events: none;
	 position: absolute;
	 right: -2px;
	 top: 0;
	 width: 4px;
}
 .tableWrapper {
	 margin: 1.5rem 0;
	 overflow-x: auto;
}
 .resize-cursor {
	 cursor: ew-resize;
	 cursor: col-resize;
}

.tiptap p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.tiptap p.is-empty::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.drag-handle-container {
  position: relative;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #f9f9f9;
}

.drag-handle {
  position: absolute;
  top: 50%;
  left: -24px; /* Position to the left of the node */
  transform: translateY(-50%);
  cursor: grab;
  background: yellow;
  padding: 4px;
  border-radius: 4px;
  display: none; /* Default state */
}

.drag-handle-container:hover .drag-handle {
  display: inline-block; /* Show only on hover */
}
</style>
