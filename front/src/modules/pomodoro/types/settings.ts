export interface PomodoroSettings {
  workTime: number
  breakTime: number
  longBreakTime: number
  sessionsBeforeLongBreak: number
  totalPomodoros: number
}

export interface BackendPomodoroSettings {
  work_duration: number
  break_duration: number
  repeats_count: number
  long_break_duration: number | null
  cycles_before_long_break: number | null
}

export const DEFAULT_SETTINGS: PomodoroSettings = {
  workTime: 25,
  breakTime: 5,
  longBreakTime: 15,
  sessionsBeforeLongBreak: 4,
  totalPomodoros: 4,
}

export function toFrontendSettings(backend: BackendPomodoroSettings): PomodoroSettings {
  return {
    workTime: backend.work_duration,
    breakTime: backend.break_duration,
    longBreakTime: backend.long_break_duration ?? 15,
    sessionsBeforeLongBreak: backend.cycles_before_long_break ?? 4,
    totalPomodoros: backend.repeats_count,
  }
}

export function toBackendSettings(frontend: PomodoroSettings): BackendPomodoroSettings {
  return {
    work_duration: frontend.workTime,
    break_duration: frontend.breakTime,
    repeats_count: frontend.totalPomodoros,
    long_break_duration: frontend.longBreakTime,
    cycles_before_long_break: frontend.sessionsBeforeLongBreak,
  }
}
