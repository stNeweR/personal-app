<script setup lang="ts">
import { onMounted } from 'vue'
import { usePluginManagement } from '../composables/usePluginManagement'

const { plugins, isLoading, error, loadPlugins, enablePlugin, disablePlugin } = usePluginManagement()

onMounted(() => {
  loadPlugins()
})

async function togglePlugin(plugin: typeof plugins.value[0]): Promise<void> {
  if (plugin.enabled) {
    await disablePlugin(plugin.name)
  } else {
    await enablePlugin(plugin.name)
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-accent-purple to-accent-blue text-white shadow-lg">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold">Управление плагинами</h1>
        <router-link
          to="/dashboard"
          class="px-4 py-1.5 rounded-lg bg-white/20 hover:bg-white/30 transition text-sm font-medium"
        >
          Назад
        </router-link>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-6xl mx-auto px-4 py-10">
      <div v-if="isLoading" class="text-center py-8">
        <div class="text-gray-500">Загрузка плагинов...</div>
      </div>

      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        <div class="text-red-700">{{ error }}</div>
      </div>

      <div v-else-if="plugins.length === 0" class="text-center py-8">
        <div class="text-gray-500">Плагины не найдены</div>
      </div>

      <div v-else class="grid gap-4">
        <div
          v-for="plugin in plugins"
          :key="plugin.name"
          class="bg-white rounded-2xl shadow-lg p-6 flex items-center justify-between"
        >
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h3 class="text-lg font-bold text-gray-800">{{ plugin.name }}</h3>
              <span class="text-xs text-gray-500">v{{ plugin.version }}</span>
              <span
                :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium',
                  plugin.enabled
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-gray-100 text-gray-600',
                ]"
              >
                {{ plugin.enabled ? 'Включен' : 'Выключен' }}
              </span>
            </div>
            <p v-if="plugin.description" class="text-sm text-gray-600 mb-1">
              {{ plugin.description }}
            </p>
            <p v-if="plugin.author" class="text-xs text-gray-500">
              Автор: {{ plugin.author }}
            </p>
          </div>

          <button
            @click="togglePlugin(plugin)"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition',
              plugin.enabled
                ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                : 'bg-gradient-to-r from-accent-purple to-accent-blue text-white hover:opacity-90',
            ]"
          >
            {{ plugin.enabled ? 'Выключить' : 'Включить' }}
          </button>
        </div>
      </div>
    </main>
  </div>
</template>
