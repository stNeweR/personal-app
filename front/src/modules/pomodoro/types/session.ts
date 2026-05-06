export interface PomodoroSession {
  id: number
  current_status: string
  start_at: string | null
  end_at: string | null
  current_cycle: number
}

export interface TodaySessionsResponse {
  sessions: PomodoroSession[]
}
