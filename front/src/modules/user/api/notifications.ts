import { apiClient } from '@/shared/api/client'

export interface TelegramChannelInfo {
  linked: boolean
  telegramId: number | null
  botName: string | null
}

export interface EmailChannelInfo {
  address: string | null
  verified: boolean
}

export interface NotificationPreferences {
  channel: 'telegram' | 'email' | null
  telegram: TelegramChannelInfo
  email: EmailChannelInfo
}

export interface TelegramLinkToken {
  token: string
  botName: string
  expiresInMinutes: number
}

export interface ApiResponse<T> {
  data: T
}

export function getNotificationPreferences(): Promise<NotificationPreferences> {
  return apiClient<NotificationPreferences>('/api/v1/notifications/status')
}

export function updateNotificationChannel(
  channel: 'telegram' | 'email' | null,
): Promise<ApiResponse<{ channel: 'telegram' | 'email' | null }>> {
  return apiClient<ApiResponse<{ channel: 'telegram' | 'email' | null }>>(
    '/api/v1/notifications/channel',
    {
      method: 'PUT',
      body: { channel },
    },
  )
}

export function updateNotificationEmail(email: string): Promise<ApiResponse<{ email: string }>> {
  return apiClient<ApiResponse<{ email: string }>>('/api/v1/notifications/email', {
    method: 'PUT',
    body: { email },
  })
}

export function sendEmailVerification(): Promise<ApiResponse<{ sent: boolean }>> {
  return apiClient<ApiResponse<{ sent: boolean }>>(
    '/api/v1/notifications/email/send-verification',
    {
      method: 'POST',
    },
  )
}

export function generateTelegramLinkToken(): Promise<TelegramLinkToken> {
  return apiClient<TelegramLinkToken>('/api/v1/telegram/link-token', { method: 'POST' })
}

export function disconnectTelegram(): Promise<{ message: string }> {
  return apiClient<{ message: string }>('/api/v1/telegram/disconnect', { method: 'POST' })
}
