import type { BackendPomodoroSettings } from './settings'

export interface PomodoroSession {
  id: number
  current_status: string
  previous_status: string | null
  start_at: string | null
  end_at: string | null
  current_cycle: number
  phase_started_at: string | null
  time_left: number | null
  settings: BackendPomodoroSettings | null
}

export interface TodaySessionsResponse {
  sessions: PomodoroSession[]
}

export interface CreateSessionRequest {
  settings?: BackendPomodoroSettings | null
}


