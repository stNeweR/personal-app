import { ref } from 'vue'
const API_URL = import.meta.env.VITE_API_URL || ''

interface RequestOptions {
  method?: string
  headers?: Record<string, string>
  body?: unknown
}

async function apiClient<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
  const token = localStorage.getItem('token')

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...options.headers,
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    method: options.method || 'GET',
    headers,
    body: options.body ? JSON.stringify(options.body) : null,
  })

  if (!response.ok) {
    const error = await response.json().catch(() => ({}))
    throw new Error(error.message || `HTTP ${response.status}`)
  }

  return response.json() as Promise<T>
}

export interface PlaylistResponse {
  data: {
    url: string | null
  }
}

export interface SavePlaylistPayload {
  url: string
}

export function getPlaylist(): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist')
}

export function savePlaylist(payload: SavePlaylistPayload): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist', {
    method: 'POST',
    body: payload,
  })
}

export function deletePlaylist(): Promise<PlaylistResponse> {
  return apiClient<PlaylistResponse>('/api/v1/playlist', {
    method: 'DELETE',
  })
}

export function usePlaylistWidget() {
  const url = ref<string | null>(null)
  const isLoading = ref(false)
  const isSaving = ref(false)
  const error = ref<string | null>(null)
  const input = ref('')

  async function load(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const response = await getPlaylist()
      url.value = response.data.url
      if (url.value) {
        input.value = url.value
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load playlist'
    } finally {
      isLoading.value = false
    }
  }

  async function save(): Promise<void> {
    const newUrl = input.value.trim()
    if (newUrl === '') return
    
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
      input.value = ''
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to clear playlist'
      throw e
    } finally {
      isSaving.value = false
    }
  }

  return {
    url,
    input,
    isLoading,
    isSaving,
    error,
    load,
    save,
    clear,
  }
}
