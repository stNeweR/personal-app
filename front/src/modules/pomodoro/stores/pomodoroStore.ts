import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { DEFAULT_SETTINGS, type PomodoroSettings, toBackendSettings } from '../types/settings'
import { getUserSettings, saveUserSettings } from '../api/settings'
import { createSession, deleteSession, getActiveSession, updateSession } from '../api/sessions'
import { usePlaylistStore } from '@/modules/user/stores/playlistStore'
import { showPlaylistNotification } from '@/modules/user/composables/usePlaylistNotification'

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
        sessionsBeforeLongBreak:
          parsed.sessionsBeforeLongBreak ?? DEFAULT_SETTINGS.sessionsBeforeLongBreak,
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

  const isRunning = computed(
    () => status.value !== 'idle' && status.value !== 'paused' && status.value !== 'finished',
  )
  const isSessionFinished = computed(() => status.value === 'finished')
  const isUsingSessionSettings = computed(() => sessionSettings.value !== null)

  const currentDuration = computed(() => {
    const effective = status.value === 'paused' ? previousStatus.value : status.value
    switch (effective) {
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
      if (
        e instanceof Error &&
        (e.message.includes('404') || e.message.includes('Settings not found'))
      ) {
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
      const payload =
        sessionSettings.value !== null
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
    if (status.value === 'idle') return

    try {
      const payload: Parameters<typeof updateSession>[1] = {
        current_status: status.value,
        current_cycle: completedSessions.value + 1,
      }

      if (status.value === 'paused' && previousStatus.value !== 'idle') {
        payload.previous_status = previousStatus.value
      }

      if (status.value === 'paused') {
        payload.time_left = timeLeft.value
      } else if (status.value !== 'finished') {
        const elapsed = currentDuration.value - timeLeft.value
        payload.phase_started_at = new Date(Date.now() - elapsed * 1000).toISOString()
      }

      await updateSession(sessionId.value, payload)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update session'
    }
  }

  async function endSessionOnBackend(): Promise<void> {
    if (sessionId.value === null) return
    try {
      await updateSession(sessionId.value, {
        current_status: 'finished',
        current_cycle: Math.max(1, completedSessions.value),
        time_left: 0,
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
    syncSessionStatus()
  }

  async function toggle(): Promise<void> {
    if (status.value === 'idle' || status.value === 'finished') {
      await reset()
      await startSessionOnBackend()
      startWork()
      await syncSessionStatus()
      showPlaylistNotification(usePlaylistStore().url)
    } else if (isRunning.value) {
      pause()
    } else {
      if (timeLeft.value <= 0) {
        await handleTimerComplete()
        return
      }
      if (previousStatus.value === 'idle') {
        previousStatus.value = 'work'
      }
      status.value = previousStatus.value
      startTick()
      await syncSessionStatus()
    }
  }

  async function restoreSession(): Promise<void> {
    try {
      const session = await getActiveSession()
      if (!session || typeof session.id !== 'number' || session.current_status === 'finished') {
        return
      }

      sessionId.value = session.id
      status.value = session.current_status as TimerStatus
      previousStatus.value = (session.previous_status as TimerStatus) || 'idle'
      completedSessions.value = Math.max(0, session.current_cycle - 1)

      if (session.settings) {
        sessionSettings.value = {
          workTime: session.settings.work_duration,
          breakTime: session.settings.break_duration,
          longBreakTime: session.settings.long_break_duration ?? 15,
          sessionsBeforeLongBreak: session.settings.cycles_before_long_break ?? 4,
          totalPomodoros: session.settings.repeats_count,
        }
      }

      if (session.current_status === 'paused') {
        timeLeft.value = session.time_left ?? currentDuration.value
        return
      }

      if (session.phase_started_at) {
        const phaseStart = new Date(session.phase_started_at).getTime()
        const elapsed = Math.floor((Date.now() - phaseStart) / 1000)
        const duration = currentDuration.value
        timeLeft.value = Math.max(0, duration - elapsed)

        if (timeLeft.value === 0) {
          handleTimerComplete()
        } else {
          startTick()
        }
      } else {
        timeLeft.value = currentDuration.value
        startTick()
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to restore session'
    }
  }

  async function reset(): Promise<void> {
    if (sessionId.value !== null) {
      try {
        await deleteSession(sessionId.value)
      } catch {
        // ignore delete errors
      }
    }
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

  async function handleTimerComplete(): Promise<void> {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null

    if (status.value === 'work') {
      completedSessions.value += 1
      if (completedSessions.value >= activeSettings.value.totalPomodoros) {
        finishSession()
      } else if (completedSessions.value % activeSettings.value.sessionsBeforeLongBreak === 0) {
        startLongBreak()
        await syncSessionStatus()
      } else {
        startBreak()
        await syncSessionStatus()
      }
    } else if (status.value === 'break' || status.value === 'long_break') {
      if (completedSessions.value < activeSettings.value.totalPomodoros) {
        startWork()
        await syncSessionStatus()
      } else {
        finishSession()
      }
    }
  }

  async function skipPhase(): Promise<void> {
    clearInterval(intervalId.value ?? undefined)
    intervalId.value = null

    if (status.value === 'work') {
      completedSessions.value += 1
      if (completedSessions.value >= activeSettings.value.totalPomodoros) {
        finishSession()
      } else if (completedSessions.value % activeSettings.value.sessionsBeforeLongBreak === 0) {
        status.value = 'long_break'
        timeLeft.value = activeSettings.value.longBreakTime * 60
        await syncSessionStatus()
        startTick()
      } else {
        status.value = 'break'
        timeLeft.value = activeSettings.value.breakTime * 60
        await syncSessionStatus()
        startTick()
      }
    } else if (status.value === 'break' || status.value === 'long_break') {
      if (completedSessions.value < activeSettings.value.totalPomodoros) {
        status.value = 'work'
        timeLeft.value = activeSettings.value.workTime * 60
        await syncSessionStatus()
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

  const formattedTimeLeft = computed(() => {
    if (status.value === 'idle' || status.value === 'finished') {
      return formatTime(activeSettings.value.workTime * 60)
    }
    return formatTime(timeLeft.value)
  })

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
    restoreSession,
    skipPhase,
    formatTime,
  }
})
