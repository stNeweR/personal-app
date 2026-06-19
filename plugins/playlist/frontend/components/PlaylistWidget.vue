<script setup lang="ts">
import { onMounted } from 'vue'
import { usePlaylistWidget } from '../api/playlist'

const { url, input, isSaving, error, load, save, clear } = usePlaylistWidget()

onMounted(() => {
  load()
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
      <div>
        <h3 class="text-xl font-bold text-gray-800">🎧 Плейлист для работы</h3>
        <p class="text-xs text-gray-500 mt-1">
          Ссылка появится в всплывающем окне при запуске помодоро
        </p>
      </div>
      <div v-if="url" class="flex items-center gap-2">
        <a
          :href="url"
          target="_blank"
          rel="noopener noreferrer"
          class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200 transition"
        >
          Открыть
        </a>
        <button
          @click="clear"
          :disabled="isSaving"
          class="px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-sm font-medium hover:bg-red-100 transition disabled:opacity-50"
        >
          Удалить
        </button>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-2">
      <input
        v-model="input"
        type="url"
        placeholder="https://music.youtube.com/playlist?list=..."
        class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-accent-blue"
        :disabled="isSaving"
        @keyup.enter="save"
      />
      <button
        @click="save"
        :disabled="isSaving || input.trim() === ''"
        class="px-5 py-2 rounded-lg bg-gradient-to-r from-accent-purple to-accent-blue text-white text-sm font-medium shadow hover:opacity-90 transition disabled:opacity-50"
      >
        {{ isSaving ? 'Сохранение...' : url ? 'Обновить' : 'Сохранить' }}
      </button>
    </div>

    <div v-if="error" class="text-red-500 text-sm mt-3">
      {{ error }}
    </div>
  </div>
</template>
