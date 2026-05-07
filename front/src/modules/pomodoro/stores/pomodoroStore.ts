import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { DEFAULT_SETTINGS, type PomodoroSettings, toBackendSettings } from '../types/settings'
import { getUserSettings, saveUserSettings } from '../api/settings'
import { createSession, updateSession } from '../api/sessions'

const SETTINGS_KEY = 'pomodoro_settings'

function loadLocalSettings(): PomodoroSettings {
  try {
    const raw = localStorage.getItem(SETTINGS_KEY)
    if (raw) {
      const parsed = JSON.parse(raw) as Partial<PomodoroSettings>
      return {
        workTime: parsed.workTime ?? DEFAULT_SETTINGS.workTime,
        breakTime: parsed.breakTime ?? DEFAULT_SETTINGS.breakTime,
        longBreakTime: parsed.longBreakTime ?? DEFAULT_SETTINGS.longBreakTime,
        sessionsBeforeLongBreak: parsed.sessionsBeforeLongBreak ?? DEFAULT_SETTINGS.sessionsBeforeLongBreak,
        totalPomodoros: parsed.totalPomodoros ?? DEFAULT_SETTINGS.totalPomodoros,
      }
    }
  } catch {
    // ignore
  }
  return { ...DEFAULT_SETTINGS }
}

function saveLocalSettings(settings: PomodoroSettings): void {
  localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings))
}

type TimerStatus = 'idle' | 'work' | 'break' | 'long_break' | 'paused' | 'finished'

export const usePomodoroStore = defineStore('pomodoro', () => {
  const settings = ref<PomodoroSettings>(loadLocalSettings())
  const hasBackendSettings = ref<boolean>(false)
  const sessionSettings = ref<PomodoroSettings | null>(null)
  const status = ref<TimerStatus>('idle')
  const previousStatus = ref<TimerStatus>('idle')
  const timeLeft = ref(0)
  const completedSessions = ref(0)
  const intervalId = ref<ReturnType<typeof setInterval> | null>(null)
  const sessionId = ref<number | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const activeSettings = computed<PomodoroSettings>(() => sessionSettings.value ?? settings.value)

  const isRunning = computed(() => status.value !== 'idle' && status.value !== 'paused' && status.value !== 'finished')
  const isSessionFinished = computed(() => status.value === 'finished')
  const isUsingSessionSettings = computed(() => sessionSettings.value !== null)

  const currentDuration = computed(() => {
    switch (status.value) {
      case 'work':
        return activeSettings.value.workTime * 60
      case 'break':
        return activeSettings.value.breakTime * 60
      case 'long_break':
        return activeSettings.value.longBreakTime * 60
      default:
        return 0
    }
  })

  const currentPomodoro = computed(() => {
    const effective = status.value === 'paused' ? previousStatus.value : status.value
    if (effective === 'work') return completedSessions.value + 1
    if (effective === 'break' || effective === 'long_break') return completedSessions.value
    return 0
  })

  async function loadUserSettings(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const backendSettings = await getUserSettings()
      settings.value = {
        workTime: backendSettings.work_duration,
        breakTime: backendSettings.break_duration,
        longBreakTime: backendSettings.long_break_duration ?? 15,
        sessionsBeforeLongBreak: backendSettings.cycles_before_long_break ?? 4,
        totalPomodoros: backendSettings.repeats_count,
      }
      saveLocalSettings(settings.value)
      hasBackendSettings.value = true
    } catch (e) {
      if (e instanceof Error && (e.message.includes('404') || e.message.includes('Settings not found'))) {
        hasBackendSettings.value = false
      } else {
        error.value = e instanceof Error ? e.message : 'Failed to load settings'
      }
    } finally {
      isLoading.value = false
    }
  }

  async function saveUserSettingsToBackend(newSettings: PomodoroSettings): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const backendSettings = toBackendSettings(newSettings)
      await saveUserSettings(backendSettings)
      settings.value = { ...newSettings }
      saveLocalSettings(settings.value)
      hasBackendSettings.value = true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to save settings'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  function updateSettings(newSettings: PomodoroSettings): void {
    settings.value = { ...newSettings }
    saveLocalSettings(settings.value)
  }

  function setSessionSettings(newSettings: PomodoroSettings): void {
    sessionSettings.value = { ...newSettings }
  }

  function clearSessionSettings(): void {
    sessionSettings.value = null
  }

  async function startSessionOnBackend(): Promise<void> {
    if (sessionId.value !== null) return
    try {
      const payload = sessionSettings.value !== null
        ? { settings: toBackendSettings(sessionSettings.value) }
        : { settings: null }
      const session = await createSession(payload)
      sessionId.value = session.id
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to create session'
      throw e
    }
  }

  async function syncSessionStatus(): Promise<void> {
    if (sessionId.value === null) return
    try {
      await updateSession(sessionId.value, {
        current_status: status.value,
        current_cycle: completedSessions.value + 1,
      })
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update session'
    }
  }

  async function endSessionOnBackend(): Promise<void> {
    if (sessionId.value === null) return
    try {
      await updateSession(sessionId.value, {
        current_status: 'finished',
        current_cycle: completedSessions.value,
      })
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to end session'
    }
  }

  function startWork(): void {
    status.value = 'work'
    timeLeft.value = activeSettings.value.workTime * 60
    startTick()
  }

  function startBreak(): void {
    status.value = 'break'
    timeLeft.value = activeSettings.value.breakTime * 60
    startTick()
  }

  function startLongBreak(): void {
    status.value = 'long_break'
    timeLeft.value = activeSettings.value.longBreakTime * 60
    startTick()
  }

  function finishSession(): void {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null
    status.value = 'finished'
    timeLeft.value = 0
    endSessionOnBackend()
  }

  function startTick(): void {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = setInterval(() => {
      if (timeLeft.value > 0) {
        timeLeft.value -= 1
      } else {
        handleTimerComplete()
      }
    }, 1000)
  }

  function pause(): void {
    if (status.value === 'paused' || status.value === 'idle' || status.value === 'finished') return
    previousStatus.value = status.value
    status.value = 'paused'
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null
    syncSessionStatus()
  }

  function resume(): void {
    if (status.value !== 'paused') return
    if (timeLeft.value <= 0) {
      handleTimerComplete()
      return
    }
    status.value = previousStatus.value
    startTick()
  }

  async function toggle(): Promise<void> {
    if (status.value === 'idle' || status.value === 'finished') {
      reset()
      await startSessionOnBackend()
      startWork()
    } else if (isRunning.value) {
      pause()
    } else {
      if (timeLeft.value <= 0) {
        handleTimerComplete()
        return
      }
      status.value = previousStatus.value
      startTick()
    }
  }

  function reset(): void {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null
    status.value = 'idle'
    previousStatus.value = 'idle'
    timeLeft.value = 0
    completedSessions.value = 0
    sessionId.value = null
    sessionSettings.value = null
    error.value = null
  }

  function handleTimerComplete(): void {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null

    if (status.value === 'work') {
      completedSessions.value += 1
      if (completedSessions.value >= activeSettings.value.totalPomodoros) {
        finishSession()
      } else if (completedSessions.value % activeSettings.value.sessionsBeforeLongBreak === 0) {
        startLongBreak()
        syncSessionStatus()
      } else {
        startBreak()
        syncSessionStatus()
      }
    } else if (status.value === 'break' || status.value === 'long_break') {
      if (completedSessions.value < activeSettings.value.totalPomodoros) {
        startWork()
        syncSessionStatus()
      } else {
        finishSession()
      }
    }
  }

  function skipPhase(): void {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null

    if (status.value === 'work') {
      completedSessions.value += 1
      if (completedSessions.value >= activeSettings.value.totalPomodoros) {
        finishSession()
      } else if (completedSessions.value % activeSettings.value.sessionsBeforeLongBreak === 0) {
        status.value = 'long_break'
        timeLeft.value = activeSettings.value.longBreakTime * 60
        syncSessionStatus()
        startTick()
      } else {
        status.value = 'break'
        timeLeft.value = activeSettings.value.breakTime * 60
        syncSessionStatus()
        startTick()
      }
    } else if (status.value === 'break' || status.value === 'long_break') {
      if (completedSessions.value < activeSettings.value.totalPomodoros) {
        status.value = 'work'
        timeLeft.value = activeSettings.value.workTime * 60
        syncSessionStatus()
        startTick()
      } else {
        finishSession()
      }
    }
  }

  function formatTime(seconds: number): string {
    const m = Math.floor(seconds / 60)
    const s = seconds % 60
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
  }

  const formattedTimeLeft = computed(() => formatTime(timeLeft.value))

  return {
    settings,
    hasBackendSettings,
    sessionSettings,
    activeSettings,
    status,
    timeLeft,
    completedSessions,
    currentPomodoro,
    isRunning,
    isSessionFinished,
    isUsingSessionSettings,
    isLoading,
    error,
    currentDuration,
    formattedTimeLeft,
    loadUserSettings,
    saveUserSettingsToBackend,
    updateSettings,
    setSessionSettings,
    clearSessionSettings,
    startWork,
    pause,
    resume,
    toggle,
    reset,
    skipPhase,
    formatTime,
  }
})
