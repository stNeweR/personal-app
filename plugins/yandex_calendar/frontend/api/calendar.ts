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

export interface CalendarStatus {
  connected: boolean
}

export interface CalendarEvent {
  id: string
  summary: string
  description: string
  start: string
  end: string
  start_tz?: string
  end_tz?: string
}

export interface CalendarTodayResponse {
  data: CalendarEvent[]
}

export interface ConnectYandexPayload {
  email: string
  app_password: string
}

export function getCalendarStatus(): Promise<CalendarStatus> {
  return apiClient<CalendarStatus>('/api/v1/yandex-calendar/status')
}

export function connectYandexCalendar(payload: ConnectYandexPayload): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/yandex-calendar/connect', {
    method: 'POST',
    body: payload,
  })
}

export function getTodayEvents(date: string): Promise<CalendarTodayResponse> {
  return apiClient<CalendarTodayResponse>(`/api/v1/yandex-calendar/today?date=${date}`)
}

export function useYandexCalendarWidget() {
  const status = ref<CalendarStatus | null>(null)
  const loading = ref(false)
  const events = ref<CalendarEvent[]>([])
  const error = ref<string | null>(null)
  const showConnectForm = ref(false)

  async function loadStatus(): Promise<void> {
    try {
      error.value = null
      status.value = await getCalendarStatus()
      if (status.value.connected) {
        await loadEvents()
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load calendar status'
    }
  }

  async function loadEvents(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const today = new Date().toLocaleDateString('en-CA')
      const res = await getTodayEvents(today)
      events.value = res.data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load calendar events'
    } finally {
      loading.value = false
    }
  }

  return {
    status,
    loading,
    events,
    error,
    showConnectForm,
    loadStatus,
    loadEvents,
  }
}
