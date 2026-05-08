import { apiClient } from '@/shared/api/client'
import type { CreateSessionRequest, PomodoroSession, TodaySessionsResponse } from '../types/session'

export function getTodaySessions(): Promise<TodaySessionsResponse> {
  return apiClient<TodaySessionsResponse>('/api/v1/pomodoro/sessions')
}

export function getActiveSession(): Promise<PomodoroSession | null> {
  return apiClient<PomodoroSession | null>('/api/v1/pomodoro/sessions/active')
}

export function createSession(data: CreateSessionRequest): Promise<PomodoroSession> {
  return apiClient<PomodoroSession>('/api/v1/pomodoro/sessions', {
    method: 'POST',
    body: data,
  })
}

export interface UpdateSessionPayload {
  current_status: string
  current_cycle: number
  previous_status?: string
  phase_started_at?: string
  time_left?: number
}

export function updateSession(sessionId: number, data: UpdateSessionPayload): Promise<PomodoroSession> {
  return apiClient<PomodoroSession>(`/api/v1/pomodoro/sessions/${sessionId}`, {
    method: 'PATCH',
    body: data,
  })
}

export function deleteSession(sessionId: number): Promise<void> {
  return apiClient<void>(`/api/v1/pomodoro/sessions/${sessionId}`, {
    method: 'DELETE',
  })
}
