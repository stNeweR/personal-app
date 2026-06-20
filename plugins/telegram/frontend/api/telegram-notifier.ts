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

export interface TelegramNotifierStatus {
  connected: boolean
  chat_id: number | null
  bot_name: string | null
}

export interface TelegramNotifierHistoryItem {
  id: number
  chat_id: number
  message: string
  status: string
  message_id: string | null
  sent_at: string
}

export function getTelegramNotifierStatus(): Promise<TelegramNotifierStatus> {
  return apiClient<TelegramNotifierStatus>('/api/v1/telegram-notifier/status')
}

export function disconnectTelegramNotifier(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/telegram-notifier/disconnect', {
    method: 'POST',
  })
}

export function getTelegramNotifierHistory(): Promise<TelegramNotifierHistoryItem[]> {
  return apiClient<TelegramNotifierHistoryItem[]>('/api/v1/telegram-notifier/history')
}

export function useTelegramNotifierWidget() {
  const connected = ref(false)
  const chatId = ref<number | null>(null)
  const botName = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadStatus(): Promise<void> {
    try {
      const status = await getTelegramNotifierStatus()
      connected.value = status.connected
      chatId.value = status.chat_id
      botName.value = status.bot_name
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load telegram notifier status'
    }
  }

  return {
    connected,
    chatId,
    botName,
    loading,
    error,
    loadStatus,
  }
}
