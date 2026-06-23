import { ref, type Component, shallowRef } from 'vue'
import { apiClient } from '@/shared/api/client'

export interface PluginManifest {
  name: string
  version: string
  author: string | null
  description: string | null
  backend: {
    entry: string
    namespace: string
  }
  frontend: {
    entry: string
    widget: string
  }
}

export interface Plugin {
  name: string
  version: string
  author: string | null
  description: string | null
  enabled: boolean
  manifest: PluginManifest | null
}

export interface PluginWidget {
  name: string
  component: Component
  manifest: PluginManifest
}

class PluginRegistry {
  private widgets = ref<PluginWidget[]>([])
  private loadedPlugins = new Set<string>()

  getWidgets(): PluginWidget[] {
    return this.widgets.value
  }

  async loadPlugins(): Promise<void> {
    this.clear()

    try {
      const response = await apiClient<{ data: PluginManifest[] }>('/api/v1/plugins/enabled')
      const manifests = response.data

      for (const manifest of manifests) {
        if (this.loadedPlugins.has(manifest.name)) {
          continue
        }

        await this.loadPluginWidget(manifest)
      }
    } catch (e) {
      console.error('Failed to load plugins:', e)
    }
  }

  private async loadPluginWidget(manifest: PluginManifest): Promise<void> {
    try {
      const pluginUrl = import.meta.env.DEV
        ? `/plugins/${manifest.name}/${manifest.frontend.entry}`
        : `/plugins/${manifest.name}/dist/${manifest.name}.umd.js`

      const module = await import(/* @vite-ignore */ pluginUrl)

      const widgetComponent = import.meta.env.DEV
        ? module[manifest.frontend.widget] || module.default
        : module[manifest.frontend.widget] || module.default || module

      if (widgetComponent) {
        this.widgets.value.push({
          name: manifest.name,
          component: shallowRef(widgetComponent),
          manifest,
        })
        this.loadedPlugins.add(manifest.name)
      }
    } catch (e) {
      console.error(`Failed to load plugin widget ${manifest.name}:`, e)
    }
  }

  clear(): void {
    this.widgets.value = []
    this.loadedPlugins.clear()
  }
}

export const pluginRegistry = new PluginRegistry()
