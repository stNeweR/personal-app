import { apiClient } from '@/shared/api/client'
import type { CreateSessionRequest, PomodoroSession, TodaySessionsResponse, UpdateSessionRequest } from '../types/session'

export function getTodaySessions(): Promise<TodaySessionsResponse> {
  return apiClient<TodaySessionsResponse>('/api/v1/pomodoro/sessions')
}

export function createSession(data: CreateSessionRequest): Promise<PomodoroSession> {
  return apiClient<PomodoroSession>('/api/v1/pomodoro/sessions', {
    method: 'POST',
    body: data,
  })
}

export function updateSession(sessionId: number, data: UpdateSessionRequest): Promise<PomodoroSession> {
  return apiClient<PomodoroSession>(`/api/v1/pomodoro/sessions/${sessionId}`, {
    method: 'PATCH',
    body: data,
  })
}
