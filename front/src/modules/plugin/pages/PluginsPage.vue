<script setup lang="ts">
import { ref, watch } from 'vue'
import { usePluginStore } from '../stores/pluginStore'
import { AVAILABLE_PLUGINS } from '../types/plugin'

const pluginStore = usePluginStore()
const selectedPlugin = ref<string>('converter')
const selectedAction = ref<string>('currency')
const inputPayload = ref<string>('{}')
const result = ref<unknown>(null)

function updateActions(): void {
  const plugin = AVAILABLE_PLUGINS.find((p) => p.name === selectedPlugin.value)
  if (plugin) {
    selectedAction.value = plugin.actions[0]?.name ?? ''
    inputPayload.value = plugin.actions[0]?.defaultPayload ?? '{}'
  }
}

watch(selectedAction, (actionName) => {
  const plugin = AVAILABLE_PLUGINS.find((p) => p.name === selectedPlugin.value)
  const action = plugin?.actions.find((a) => a.name === actionName)
  if (action) {
    inputPayload.value = action.defaultPayload
  }
})

async function execute(): Promise<void> {
  result.value = null
  pluginStore.clearError()
  try {
    const payload = JSON.parse(inputPayload.value)
    result.value = await pluginStore.runPlugin(selectedPlugin.value, selectedAction.value, payload)
  } catch (e) {
    if (e instanceof SyntaxError) {
      pluginStore.error = 'Invalid JSON in payload'
    }
  }
}

function formatJson(data: unknown): string {
  return JSON.stringify(data, null, 2)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">Плагины</h1>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar: Plugin list -->
        <div class="space-y-3">
          <div
            v-for="plugin in AVAILABLE_PLUGINS"
            :key="plugin.name"
            class="bg-white rounded-xl shadow p-4 cursor-pointer transition border-2"
            :class="
              selectedPlugin === plugin.name
                ? 'border-blue-500 ring-1 ring-blue-500'
                : 'border-transparent hover:border-gray-200'
            "
            @click="
              selectedPlugin = plugin.name
              updateActions()
            "
          >
            <h3 class="font-semibold text-gray-800">{{ plugin.label }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ plugin.description }}</p>
          </div>
        </div>

        <!-- Main: Execution panel -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
              {{ AVAILABLE_PLUGINS.find((p) => p.name === selectedPlugin)?.label }}
            </h2>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Действие</label>
                <select
                  v-model="selectedAction"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option
                    v-for="action in AVAILABLE_PLUGINS.find((p) => p.name === selectedPlugin)
                      ?.actions"
                    :key="action.name"
                    :value="action.name"
                  >
                    {{ action.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payload (JSON)</label>
                <textarea
                  v-model="inputPayload"
                  rows="6"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  spellcheck="false"
                />
              </div>

              <button
                @click="execute"
                :disabled="pluginStore.isLoading"
                class="w-full bg-blue-600 text-white font-medium py-2.5 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ pluginStore.isLoading ? 'Выполняется...' : 'Выполнить' }}
              </button>
            </div>
          </div>

          <!-- Error -->
          <div
            v-if="pluginStore.error"
            class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl"
          >
            <p class="font-medium">Ошибка</p>
            <p class="text-sm mt-1">{{ pluginStore.error }}</p>
          </div>

          <!-- Result -->
          <div v-if="result !== null" class="bg-white rounded-xl shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
              Результат
            </h3>
            <pre class="bg-gray-50 rounded-lg p-4 text-sm font-mono overflow-auto max-h-96">{{
              formatJson(result)
            }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
