import { ref } from 'vue'
import { defineStore } from 'pinia'
import { executePlugin } from '../api/plugins'

export const usePluginStore = defineStore('plugin', () => {
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastResult = ref<unknown>(null)

  async function runPlugin(
    name: string,
    action: string,
    input: Record<string, unknown> = {},
  ): Promise<unknown> {
    isLoading.value = true
    error.value = null
    try {
      const response = await executePlugin(name, action, { input })
      lastResult.value = response.data
      return response.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Plugin execution failed'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  function clearError(): void {
    error.value = null
  }

  return {
    isLoading,
    error,
    lastResult,
    runPlugin,
    clearError,
  }
})
