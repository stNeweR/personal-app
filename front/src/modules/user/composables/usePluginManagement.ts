import { ref } from 'vue'
import { apiClient } from '@/shared/api/client'
import type { Plugin } from '@/shared/plugins/PluginRegistry'

export function usePluginManagement() {
  const plugins = ref<Plugin[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function loadPlugins(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const response = await apiClient<{ data: Plugin[] }>('/api/v1/plugins')
      plugins.value = response.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load plugins'
    } finally {
      isLoading.value = false
    }
  }

  async function enablePlugin(name: string): Promise<void> {
    try {
      await apiClient(`/api/v1/plugins/${name}/enable`, {
        method: 'POST',
      })
      
      const plugin = plugins.value.find(p => p.name === name)
      if (plugin) {
        plugin.enabled = true
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : `Failed to enable plugin ${name}`
      throw e
    }
  }

  async function disablePlugin(name: string): Promise<void> {
    try {
      await apiClient(`/api/v1/plugins/${name}/disable`, {
        method: 'POST',
      })
      
      const plugin = plugins.value.find(p => p.name === name)
      if (plugin) {
        plugin.enabled = false
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : `Failed to disable plugin ${name}`
      throw e
    }
  }

  return {
    plugins,
    isLoading,
    error,
    loadPlugins,
    enablePlugin,
    disablePlugin,
  }
}
