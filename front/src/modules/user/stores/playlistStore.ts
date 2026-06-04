import { ref } from 'vue'
import { defineStore } from 'pinia'
import { getPlaylist, savePlaylist, deletePlaylist } from '../api/playlist'

export const usePlaylistStore = defineStore('playlist', () => {
  const url = ref<string | null>(null)
  const isLoading = ref(false)
  const isSaving = ref(false)
  const error = ref<string | null>(null)

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const response = await getPlaylist()
      url.value = response.data.url
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load playlist'
    } finally {
      isLoading.value = false
    }
  }

  async function save(newUrl: string): Promise<void> {
    isSaving.value = true
    error.value = null
    try {
      const response = await savePlaylist({ url: newUrl })
      url.value = response.data.url
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to save playlist'
      throw e
    } finally {
      isSaving.value = false
    }
  }

  async function clear(): Promise<void> {
    isSaving.value = true
    error.value = null
    try {
      await deletePlaylist()
      url.value = null
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to clear playlist'
      throw e
    } finally {
      isSaving.value = false
    }
  }

  return {
    url,
    isLoading,
    isSaving,
    error,
    load,
    save,
    clear,
  }
})
