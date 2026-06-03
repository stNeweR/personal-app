import { apiClient } from '@/shared/api/client'
import type { BackendPomodoroSettings } from '../types/settings'

export function getUserSettings(): Promise<BackendPomodoroSettings> {
  return apiClient<BackendPomodoroSettings>('/api/v1/pomodoro/settings')
}

export function saveUserSettings(
  settings: BackendPomodoroSettings,
): Promise<BackendPomodoroSettings> {
  return apiClient<BackendPomodoroSettings>('/api/v1/pomodoro/settings', {
    method: 'POST',
    body: settings,
  })
}
