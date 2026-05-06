import { apiClient } from '@/shared/api/client'
import type { TodaySessionsResponse } from '../types/session'

export function getTodaySessions(): Promise<TodaySessionsResponse> {
  return apiClient<TodaySessionsResponse>('/api/v1/pomodoro/sessions')
}
