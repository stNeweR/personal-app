import type { BackendPomodoroSettings } from './settings'

export interface PomodoroSession {
  id: number
  current_status: string
  start_at: string | null
  end_at: string | null
  current_cycle: number
  settings: BackendPomodoroSettings | null
}

export interface TodaySessionsResponse {
  sessions: PomodoroSession[]
}

export interface CreateSessionRequest {
  settings?: BackendPomodoroSettings | null
}

export interface UpdateSessionRequest {
  current_status: string
  current_cycle: number
}
