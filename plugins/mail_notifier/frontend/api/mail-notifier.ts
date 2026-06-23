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

export interface MailNotifierStatus {
  connected: boolean
  verified: boolean
  email: string | null
}

export interface VerifyEmailPayload {
  token: string
}

export function getMailNotifierStatus(): Promise<MailNotifierStatus> {
  return apiClient<MailNotifierStatus>('/api/v1/mail-notifier/status')
}

export function connectMailNotifier(email: string): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/mail-notifier/connect', {
    method: 'POST',
    body: { email },
  })
}

export function disconnectMailNotifier(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/mail-notifier/disconnect', {
    method: 'POST',
  })
}

export function sendVerificationEmail(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/mail-notifier/send-verification', {
    method: 'POST',
  })
}

export function verifyEmail(code: string): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/mail-notifier/verify-email', {
    method: 'POST',
    body: { code },
  })
}

export function useMailNotifierWidget() {
  const connected = ref(false)
  const verified = ref(false)
  const email = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadStatus(): Promise<void> {
    try {
      const status = await getMailNotifierStatus()
      connected.value = status.connected
      verified.value = status.verified
      email.value = status.email
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load mail notifier status'
    }
  }

  return {
    connected,
    verified,
    email,
    loading,
    error,
    loadStatus,
  }
}
