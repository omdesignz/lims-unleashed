<template>
    <aside class="flex w-64 flex-col rounded-[1.75rem] border border-slate-200 bg-white/95 p-4 text-slate-700 shadow-xl shadow-slate-900/5 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-300">
      <div class="px-2 py-3">
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Gestor documental</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Acesso rápido a documentos, partilhas e arquivo.</p>
      </div>
      <nav class="flex-1 px-2 py-3">
        <ul>
          <li class="mb-4">
            <a href="#" class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
              <FolderIcon class="h-6 w-6" />
              <span>Os meus ficheiros</span>
            </a>
          </li>
          <li class="mb-4">
            <a href="#" class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
              <ShareIcon class="h-6 w-6" />
              <span>Partilhados comigo</span>
            </a>
          </li>
          <li>
            <a href="#" class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
              <TrashIcon class="h-6 w-6" />
              <span>Eliminados</span>
            </a>
          </li>
        </ul>
      </nav>
      <div class="px-2 py-2">
        <button class="w-full rounded-xl bg-primary-700 py-2.5 font-semibold text-white transition-colors hover:bg-primary-800 dark:bg-primary-600 dark:hover:bg-primary-500">
          Nova pasta
        </button>
      </div>
    </aside>
  </template>
  
  <script setup>
  import { FolderIcon, ShareIcon, TrashIcon } from "@heroicons/vue/24/solid";
  </script>
  
